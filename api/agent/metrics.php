<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/ApiKeyRepository.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../models/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing X-API-KEY']);
    exit;
}

$keyRepo = new ApiKeyRepository();
$userId  = $keyRepo->getUserIdByApiKey($apiKey);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$vpsId  = (int) ($body['vps_id'] ?? 0);

if (!$vpsId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing vps_id']);
    exit;
}

// Verify VPS belongs to this user
$vpsRepo = new VpsRepository();
$vps     = $vpsRepo->getById($vpsId);
if (!$vps || (int) $vps['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare('
    INSERT INTO agent_metrics (vps_id, cpu_pct, ram_pct, disk_pct, net_rx_kb, net_tx_kb)
    VALUES (?, ?, ?, ?, ?, ?)
');
$stmt->execute([
    $vpsId,
    isset($body['cpu_pct'])   ? (float) $body['cpu_pct']   : null,
    isset($body['ram_pct'])   ? (float) $body['ram_pct']   : null,
    isset($body['disk_pct'])  ? (float) $body['disk_pct']  : null,
    isset($body['net_rx_kb']) ? (float) $body['net_rx_kb'] : null,
    isset($body['net_tx_kb']) ? (float) $body['net_tx_kb'] : null,
]);

// Check alert thresholds and notify via Gotify
require_once __DIR__ . '/../../agents/gotify.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
$user  = (new UserRepository())->getById($userId);
$token = $user['gotify_token'] ?? null;

if ($token && ($user['notify_metrics'] ?? 1)) {
    $cfgStmt = $conn->prepare('SELECT alerts FROM agent_config WHERE vps_id = ?');
    $cfgStmt->execute([$vpsId]);
    $cfgRow = $cfgStmt->fetch(PDO::FETCH_ASSOC);
    $alerts = $cfgRow ? json_decode($cfgRow['alerts'], true) ?? [] : [];

    $checks = [
        'cpu_pct'  => ['key' => 'cpu_threshold',  'user_key' => 'alert_cpu_threshold',  'label' => 'CPU'],
        'ram_pct'  => ['key' => 'ram_threshold',  'user_key' => 'alert_ram_threshold',  'label' => 'RAM'],
        'disk_pct' => ['key' => 'disk_threshold', 'user_key' => 'alert_disk_threshold', 'label' => 'Disco'],
    ];
    foreach ($checks as $metric => $info) {
        // Per-VPS agent_config override → user-level default
        $threshold = $alerts[$info['key']] ?? $user[$info['user_key']] ?? null;
        $value     = $body[$metric] ?? null;
        if ($threshold !== null && $value !== null && (float) $value >= (float) $threshold) {
            gotify_send($token,
                "🔴 Alerta: {$info['label']} alto en {$vps['name']}",
                "{$info['label']}: " . round($value, 1) . "% (umbral: {$threshold}%)\nIP: {$vps['ip_address']}",
                9
            );
        }
    }
}

echo json_encode(['success' => true]);
