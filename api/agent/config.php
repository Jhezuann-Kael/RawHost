<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/ApiKeyRepository.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../models/Database.php';

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

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: agent pulls its config ──────────────────────────────────────────────
if ($method === 'GET') {
    $vpsId = (int) ($_GET['vps_id'] ?? 0);
    if (!$vpsId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing vps_id']);
        exit;
    }

    $vpsRepo = new VpsRepository();
    $vps     = $vpsRepo->getById($vpsId);
    if (!$vps || (int) $vps['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare('SELECT interval_s, metrics, alerts FROM agent_config WHERE vps_id = ?');
    $stmt->execute([$vpsId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Default config if not configured yet
    if (!$row) {
        echo json_encode(['success' => true, 'config' => defaultConfig()]);
        exit;
    }

    echo json_encode(['success' => true, 'config' => [
        'interval_s' => (int) $row['interval_s'],
        'metrics'    => json_decode($row['metrics'], true),
        'alerts'     => json_decode($row['alerts'], true),
    ]]);
    exit;
}

// ── PUT: user updates config for a VPS ──────────────────────────────────────
if ($method === 'PUT') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $vpsId = (int) ($body['vps_id'] ?? 0);

    if (!$vpsId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing vps_id']);
        exit;
    }

    $vpsRepo = new VpsRepository();
    $vps     = $vpsRepo->getById($vpsId);
    if (!$vps || (int) $vps['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $default    = defaultConfig();
    $intervalS  = max(10, (int) ($body['interval_s'] ?? $default['interval_s']));
    $metrics    = $body['metrics'] ?? $default['metrics'];
    $alerts     = $body['alerts']  ?? $default['alerts'];

    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare('
        INSERT INTO agent_config (vps_id, interval_s, metrics, alerts)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE interval_s = VALUES(interval_s), metrics = VALUES(metrics), alerts = VALUES(alerts)
    ');
    $stmt->execute([$vpsId, $intervalS, json_encode($metrics), json_encode($alerts)]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);

function defaultConfig(): array
{
    return [
        'interval_s' => 60,
        'metrics'    => ['cpu', 'ram', 'disk', 'network'],
        'alerts'     => [
            'cpu_threshold'  => 90,
            'ram_threshold'  => 90,
            'disk_threshold' => 85,
        ],
    ];
}
