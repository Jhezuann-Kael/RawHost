<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];
$is_superuser = $_SESSION['is_superuser'] ?? false;
session_write_close(); // Release session lock for other requests

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level()) ob_end_flush();

set_time_limit(0); // Logs can run for a long time

function sse(string $type, string $msg): void {
    $safe = @iconv('UTF-8', 'UTF-8//IGNORE', $msg);
    if ($safe === false) $safe = '';
    echo 'data: ' . json_encode(['type' => $type, 'msg' => $safe]) . "\n\n";
    if (connection_aborted()) exit;
    flush();
}

$server_id = (int)($_GET['id'] ?? 0);
$container = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET['container'] ?? '');

if (!$server_id || !$container) {
    sse('error', 'Missing ID or container name');
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $user_id && !$is_superuser)) {
    sse('error', 'Unauthorized or VPS not found');
    exit;
}

$meta = json_decode($vps['metadata'] ?? '{}', true);
$ip       = $vps['ip_address'] ?? '';
$password = $meta['password'] ?? '';
$sshUser  = 'root';

// Build SSH_ASKPASS helper
$tmpAsk = tempnam(sys_get_temp_dir(), 'vask_');
file_put_contents($tmpAsk, "#!/bin/sh\nprintf '%s' " . escapeshellarg($password) . "\n");
chmod($tmpAsk, 0700);
register_shutdown_function(function () use ($tmpAsk) { @unlink($tmpAsk); });

$env = [
    'SSH_ASKPASS'        => $tmpAsk,
    'SSH_ASKPASS_REQUIRE' => 'force',
    'DISPLAY'            => 'dummy',
    'HOME'               => sys_get_temp_dir(),
    'PATH'               => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
];

$sshTarget = escapeshellarg($sshUser . '@' . $ip);
// Stream logs with tail 100
$dockerCmd = "docker logs -f --tail 100 " . escapeshellarg($container);
$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=10"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget " . escapeshellarg($dockerCmd);

$descriptors = [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    sse('error', 'Failed to start SSH process');
    exit;
}

sse('output', "Connecting to {$ip} logs for '{$container}'...\r\n");

stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

$lastHeartbeat = time();
$startTime = time();

while (true) {
    $status = proc_get_status($proc);
    
    $out = fread($pipes[1], 4096);
    $err = fread($pipes[2], 4096);

    if ($out !== false && $out !== '') sse('output', $out);
    if ($err !== false && $err !== '') sse('output', $err);

    // Heartbeat every 20s
    if (time() - $lastHeartbeat > 20) {
        echo ": heartbeat\n\n";
        flush();
        $lastHeartbeat = time();
    }

    if (!$status['running']) {
        if ($out === '' && $err === '') {
            // Check if it exited very quickly with an error
            if (time() - $startTime < 3 && $status['exitcode'] !== 0) {
                sse('error', "Failed to fetch logs. Container '{$container}' might not exist or VPS is unreachable.");
            }
            break;
        }
    }

    if (connection_aborted()) {
        proc_terminate($proc);
        break;
    }

    usleep(100000); // 100ms
}

foreach ($pipes as $p) @fclose($p);
proc_close($proc);
