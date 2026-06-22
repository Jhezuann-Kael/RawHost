<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$userRepo = new UserRepository();

// ── GET: return current prefs ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = $userRepo->getById($userId);
    echo json_encode([
        'success' => true,
        'data'    => [
            'notify_expiry'         => (bool) ($user['notify_expiry']         ?? true),
            'notify_metrics'        => (bool) ($user['notify_metrics']        ?? true),
            'alert_cpu_threshold'   => (int)  ($user['alert_cpu_threshold']   ?? 90),
            'alert_ram_threshold'   => (int)  ($user['alert_ram_threshold']   ?? 90),
            'alert_disk_threshold'  => (int)  ($user['alert_disk_threshold']  ?? 85),
        ],
    ]);
    exit;
}

// ── PUT: save prefs ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $cpu  = max(1, min(100, (int) ($body['alert_cpu_threshold']  ?? 90)));
    $ram  = max(1, min(100, (int) ($body['alert_ram_threshold']  ?? 90)));
    $disk = max(1, min(100, (int) ($body['alert_disk_threshold'] ?? 85)));

    $userRepo->update($userId, [
        'notify_expiry'        => isset($body['notify_expiry'])  ? (int)(bool)$body['notify_expiry']  : 1,
        'notify_metrics'       => isset($body['notify_metrics']) ? (int)(bool)$body['notify_metrics'] : 1,
        'alert_cpu_threshold'  => $cpu,
        'alert_ram_threshold'  => $ram,
        'alert_disk_threshold' => $disk,
    ]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
