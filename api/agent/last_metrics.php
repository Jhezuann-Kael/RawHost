<?php
/**
 * GET /api/agent/last_metrics?vps_id=X
 * Fetches real-time metrics from SolusVM and sends a Gotify summary.
 * Also fires an urgent Gotify alert if the VPS expires in < 48 h.
 * Auth: session or X-API-KEY header.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';
require_once __DIR__ . '/../../agents/gotify.php';
require_once __DIR__ . '/../../models/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $userId = authenticate_user();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$vpsId = (int) ($_GET['vps_id'] ?? 0);
if (!$vpsId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing vps_id']);
    exit;
}

$vpsRepo = new VpsRepository();
$vps     = $vpsRepo->getById($vpsId);
if (!$vps || ((int) $vps['user_id'] !== $userId && !($_SESSION['is_superuser'] ?? false))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if (empty($vps['external_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'VPS has no external ID']);
    exit;
}

$api     = new ExternalApiService();
$extId   = $vps['external_id'];
$vpsName = $vps['name'] ?? "VPS #$vpsId";

// Pull all metric types from SolusVM
$raw = [];
foreach (['cpu', 'memory', 'network', 'disks'] as $type) {
    $res = $api->getServerUsage($extId, $type);
    if ($res['http_code'] === 200) {
        $raw[$type] = $res['response']['data'] ?? [];
    }
}

// Disk detail gives used/total for a reliable percentage
$diskDetail = null;
$diskRes = $api->getDetailDisk($extId);
if ($diskRes['http_code'] === 200) {
    $diskDetail = $diskRes['response']['data'] ?? $diskRes['response'];
}

// SolusVM returns {name, interval, items:[{field, time}, ...]}
// Zeros are filler entries between real readings — skip them.

function solusvm_last_cpu(array $items): ?float {
    foreach (array_reverse($items) as $item) {
        if (!empty($item['load_average'])) {
            return round(min((float) $item['load_average'], 100), 1);
        }
    }
    return null;
}

function solusvm_last_ram_mb(array $items): ?float {
    foreach (array_reverse($items) as $item) {
        if (!empty($item['memory'])) {
            return (float) $item['memory'];
        }
    }
    return null;
}

function solusvm_last_net(array $items): array {
    foreach (array_reverse($items) as $item) {
        $d = $item['derivative'] ?? [];
        if (!empty($d['read_kb']) || !empty($d['write_kb'])) {
            return [
                'rx' => round((float) ($d['read_kb'] ?? 0), 2),
                'tx' => round((float) ($d['write_kb'] ?? 0), 2),
            ];
        }
    }
    return ['rx' => null, 'tx' => null];
}

// Returns disk used in GB from getDetailDisk response (actual_size in bytes)
function solusvm_disk_gb($d): ?float {
    if (empty($d)) return null;
    $item = isset($d[0]) ? $d[0] : $d;
    $bytes = $item['actual_size'] ?? $item['used'] ?? null;
    if ($bytes === null) return null;
    return round($bytes / 1073741824, 2); // bytes → GB
}

$cpuItems  = $raw['cpu']['items']     ?? [];
$memItems  = $raw['memory']['items']  ?? [];
$netItems  = $raw['network']['items'] ?? [];

$cpuPct  = solusvm_last_cpu($cpuItems);
$ramMb   = solusvm_last_ram_mb($memItems);
$net     = solusvm_last_net($netItems);
$netRx   = $net['rx'];
$netTx   = $net['tx'];
$diskGb = solusvm_disk_gb($diskDetail);

// --- Gotify push ---
$user  = (new UserRepository())->getById($userId);
$token = $user['gotify_token'] ?? null;

if ($token) {
    $parts = [];
    if ($cpuPct !== null) $parts[] = "CPU: {$cpuPct}%";
    if ($ramMb  !== null) $parts[] = "RAM: " . round($ramMb / 1024, 1) . " GB";
    if ($diskGb !== null) $parts[] = "Disk: {$diskGb} GB";
    if ($netRx  !== null) $parts[] = "↓ " . round($netRx, 1) . " KB/s";
    if ($netTx  !== null) $parts[] = "↑ " . round($netTx, 1) . " KB/s";

    if (!empty($parts)) {
        gotify_send($token, "📊 $vpsName", implode("  |  ", $parts), 3);
    }

    // Expiry alert if < 48 h remaining
    if (!empty($vps['expires_at'])) {
        $diff      = (new DateTime($vps['expires_at']))->diff(new DateTime());
        $hoursLeft = ($diff->days * 24) + $diff->h;
        if ($diff->invert && $hoursLeft < 48) {
            $currency = $user['preferred_currency'] ?? null;
            $payLine  = $currency
                ? "Puedes renovar pagando con $currency desde tu panel."
                : "Puedes renovar desde tu panel con cripto o saldo.";
            $timeLeft = $diff->days > 0
                ? "{$diff->days}d {$diff->h}h"
                : "{$diff->h}h {$diff->i}min";
            gotify_send(
                $token,
                "🔴 $vpsName — expira en $timeLeft",
                "Tu VPS expira pronto. Si no renuevas, se apagará automáticamente.\n\n$payLine\nrawhost.net/dashboard/vps",
                10
            );
        }
    }
}

// Return alert thresholds from agent_config if configured
$db      = new Database();
$conn    = $db->connect();
$cfgStmt = $conn->prepare('SELECT alerts FROM agent_config WHERE vps_id = ?');
$cfgStmt->execute([$vpsId]);
$cfg     = $cfgStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => 'Metrics pulled successfully.',
]);
