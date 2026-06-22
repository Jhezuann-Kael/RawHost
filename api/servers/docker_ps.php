<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$server_id = (int)($_GET['id'] ?? 0);

if (!$server_id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
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

$meta = json_decode($vps['metadata'] ?? '{}', true);
$ip       = $vps['ip_address'] ?? '';
$password = $meta['password'] ?? '';
$sshUser  = 'root';

if (!$ip || $ip === 'Pending') {
    echo json_encode(['success' => false, 'message' => 'No IP assigned']);
    exit;
}

// Serve cached result if fresh (15 s) — avoids opening a new SSH connection on every load
// v2: cache key includes format version so old entries are ignored after upgrades
$cacheFile = sys_get_temp_dir() . "/docker_ps_v2_{$server_id}.json";
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 15) {
    echo file_get_contents($cacheFile);
    exit;
}

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
$dockerCmd = 'timeout 20 docker ps --no-trunc --format \'{{.ID}}|{{.Names}}|{{.Status}}|{{.Ports}}|{{.Mounts}}|{{.Labels}}|{{.Image}}\'';

$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=5"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget " . escapeshellarg($dockerCmd);

$descriptors = [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    echo json_encode(['success' => false, 'message' => 'Failed to start SSH']);
    exit;
}

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);

if ($exitCode !== 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Docker command failed or Docker is not installed',
        'error' => $stderr
    ]);
    exit;
}

$lines = explode("\n", trim($stdout));
$containers = [];
foreach ($lines as $line) {
    if (empty($line)) continue;
    $parts = explode('|', $line);
    if (count($parts) < 5) continue;

    $labels_raw = $parts[5] ?? '';
    preg_match('/(?:^|,)com\.docker\.compose\.project=([^,]+)/', $labels_raw, $mp);
    preg_match('/(?:^|,)com\.docker\.compose\.service=([^,]+)/',  $labels_raw, $ms);

    $containers[] = [
        'id'              => $parts[0],
        'name'            => $parts[1],
        'status'          => $parts[2],
        'ports'           => $parts[3],
        'volumes'         => $parts[4],
        'compose_project' => $mp[1] ?? '',
        'compose_service' => $ms[1] ?? '',
        'image'           => $parts[6] ?? '',
    ];
}

$response = json_encode(['success' => true, 'containers' => $containers]);
file_put_contents($cacheFile, $response);
echo $response;
