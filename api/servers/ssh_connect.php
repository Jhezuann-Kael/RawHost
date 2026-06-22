<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

$server_id = (int)($_POST['server_id'] ?? 0);
if (!$server_id) {
    echo json_encode(['success' => false, 'message' => 'Missing server_id']);
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or VPS not found']);
    exit;
}

if ($vps['status'] !== 'ACTIVE') {
    echo json_encode(['success' => false, 'message' => 'VPS must be ACTIVE to open console']);
    exit;
}

$meta     = json_decode($vps['metadata'] ?? '{}', true);
$ip       = $vps['ip_address'] ?? '';
$password = $meta['password'] ?? '';

if (!$ip || $ip === 'Pending') {
    echo json_encode(['success' => false, 'message' => 'VPS has no IP address yet']);
    exit;
}

// Reap expired sessions (> 2 h)
if (!empty($_SESSION['ssh_sessions'])) {
    foreach ($_SESSION['ssh_sessions'] as $sid => $sess) {
        if ((time() - ($sess['created_at'] ?? 0)) > 7200) {
            if (!empty($sess['pid_file']) && file_exists($sess['pid_file'])) {
                $pid = (int)file_get_contents($sess['pid_file']);
                if ($pid > 0) { posix_kill($pid, SIGTERM); }
            }
            foreach (['input_fifo', 'output_log', 'pid_file', 'askpass'] as $k) {
                if (!empty($sess[$k])) @unlink($sess[$k]);
            }
            unset($_SESSION['ssh_sessions'][$sid]);
        }
    }
}

$sessionId = bin2hex(random_bytes(16));
$inputFifo = "/tmp/vps_ssh_in_{$sessionId}";
$outputLog = "/tmp/vps_ssh_out_{$sessionId}";
$pidFile   = "/tmp/vps_ssh_pid_{$sessionId}";
$askpass   = "/tmp/vps_ssh_ask_{$sessionId}";

file_put_contents($askpass, "#!/bin/sh\nprintf '%s' " . escapeshellarg($password) . "\n");
chmod($askpass, 0700);
touch($outputLog);

$wrapper = realpath(__DIR__ . '/../../vps_scripts/ssh_session_wrapper.sh');
$cmd = 'nohup ' . escapeshellarg($wrapper)
    . ' ' . escapeshellarg($inputFifo)
    . ' ' . escapeshellarg($outputLog)
    . ' ' . escapeshellarg($pidFile)
    . ' ' . escapeshellarg($ip)
    . ' ' . escapeshellarg($askpass)
    . ' >/dev/null 2>&1 &';

exec($cmd);

// Poll for PID file — wrapper writes it once it has opened the FIFO and is ready (up to 8 s)
$maxWait = 80;
for ($i = 0; $i < $maxWait; $i++) {
    usleep(100000);
    if (file_exists($pidFile)) break;
}

if (!file_exists($pidFile)) {
    @unlink($askpass);
    @unlink($outputLog);
    echo json_encode(['success' => false, 'message' => 'Failed to start SSH session (timeout)']);
    exit;
}

if (!isset($_SESSION['ssh_sessions'])) {
    $_SESSION['ssh_sessions'] = [];
}
$_SESSION['ssh_sessions'][$sessionId] = [
    'server_id'  => $server_id,
    'input_fifo' => $inputFifo,
    'output_log' => $outputLog,
    'pid_file'   => $pidFile,
    'askpass'    => $askpass,
    'created_at' => time(),
];

echo json_encode(['success' => true, 'session_id' => $sessionId]);
