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
$container  = trim($_POST['container'] ?? '');
$action     = trim($_POST['action'] ?? '');

// Only allow safe container name characters and known actions
if (!$server_id || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $container) || !in_array($action, ['stop', 'remove', 'remove_with_volume'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or VPS not found']);
    exit;
}

if ($vps['status'] !== 'ACTIVE') {
    echo json_encode(['success' => false, 'message' => 'VPS must be ACTIVE']);
    exit;
}

$meta     = json_decode($vps['metadata'] ?? '{}', true);
$ip       = $vps['ip_address'] ?? '';
$password = $meta['password'] ?? '';
$sshUser  = 'root';

if (!$ip || $ip === 'Pending') {
    echo json_encode(['success' => false, 'message' => 'No IP assigned']);
    exit;
}

$safeContainer = escapeshellarg($container);
$dockerCmd = match ($action) {
    'stop'                => "docker stop $safeContainer",
    'remove_with_volume'  => "docker rm -fv $safeContainer",
    default               => "docker rm -f $safeContainer",
};

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
$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=10"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget "
     . escapeshellarg("timeout 30 $dockerCmd");

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    echo json_encode(['success' => false, 'message' => 'Failed to start SSH']);
    exit;
}

$stdout   = stream_get_contents($pipes[1]);
$stderr   = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);

if ($exitCode !== 0) {
    echo json_encode(['success' => false, 'message' => trim($stderr) ?: 'Command failed']);
    exit;
}

echo json_encode(['success' => true, 'message' => trim($stdout)]);
