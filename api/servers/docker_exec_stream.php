<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id      = $_SESSION['user_id'];
$is_superuser = $_SESSION['is_superuser'] ?? false;
session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level()) ob_end_flush();

function sse(string $type, string $msg): void {
    $safe = @iconv('UTF-8', 'UTF-8//IGNORE', $msg);
    echo 'data: ' . json_encode(['type' => $type, 'msg' => $safe ?? '']) . "\n\n";
    flush();
}

$server_id = (int)($_GET['id'] ?? 0);
$container  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET['container'] ?? '');
$command    = trim($_GET['command'] ?? '');

if (!$server_id || !$container || $command === '') {
    sse('error', 'Missing parameters');
    sse('done', '');
    exit;
}

if (strlen($command) > 512) {
    sse('error', 'Command too long');
    sse('done', '');
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $user_id && !$is_superuser)) {
    sse('error', 'Unauthorized');
    sse('done', '');
    exit;
}

$meta     = json_decode($vps['metadata'] ?? '{}', true);
$ip       = $vps['ip_address'] ?? '';
$password = $meta['password'] ?? '';
$sshUser  = 'root';

$tmpAsk = tempnam(sys_get_temp_dir(), 'vask_');
file_put_contents($tmpAsk, "#!/bin/sh\nprintf '%s' " . escapeshellarg($password) . "\n");
chmod($tmpAsk, 0700);
register_shutdown_function(function () use ($tmpAsk) { @unlink($tmpAsk); });

$env = [
    'SSH_ASKPASS'         => $tmpAsk,
    'SSH_ASKPASS_REQUIRE' => 'force',
    'DISPLAY'             => 'dummy',
    'HOME'                => sys_get_temp_dir(),
    'PATH'                => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
];

$sshTarget = escapeshellarg($sshUser . '@' . $ip);
// No hard timeout — user can stop via the UI; SIGPIPE kills the SSH when client disconnects
$dockerCmd = escapeshellarg("docker exec " . escapeshellarg($container) . " sh -c " . escapeshellarg($command));
$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=10"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget $dockerCmd";

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    sse('error', 'Failed to start SSH');
    sse('done', '');
    exit;
}

stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

set_time_limit(0);

while (true) {
    if (connection_aborted()) {
        proc_terminate($proc, 9);
        break;
    }

    $status = proc_get_status($proc);
    $out = fread($pipes[1], 4096);
    $err = fread($pipes[2], 4096);

    if ($out !== false && $out !== '') sse('output', $out);
    if ($err !== false && $err !== '') sse('output', $err);

    if (!$status['running']) {
        // Drain remaining output
        while (($out = fread($pipes[1], 4096)) !== false && $out !== '') sse('output', $out);
        while (($err = fread($pipes[2], 4096)) !== false && $err !== '') sse('output', $err);
        sse('done', (string)$status['exitcode']);
        break;
    }

    usleep(80000); // 80ms
}

foreach ($pipes as $p) @fclose($p);
proc_close($proc);
