<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$server_id = (int)($_POST['id'] ?? 0);
$container  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['container'] ?? '');
$command    = trim($_POST['command'] ?? '');

if (!$server_id || !$container || $command === '') {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Limit command length
if (strlen($command) > 512) {
    echo json_encode(['success' => false, 'message' => 'Command too long']);
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
$dockerCmd = escapeshellarg("timeout 15 docker exec " . escapeshellarg($container) . " sh -c " . escapeshellarg($command));
$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=10"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget $dockerCmd";

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    echo json_encode(['success' => false, 'message' => 'SSH failed']);
    exit;
}

$stdout   = stream_get_contents($pipes[1]);
$stderr   = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);

$output = trim($stdout . ($stderr ? "\n" . $stderr : ''));

echo json_encode([
    'success'  => $exitCode === 0,
    'output'   => $output,
    'exitCode' => $exitCode,
]);
