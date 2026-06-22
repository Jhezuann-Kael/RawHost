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
$container  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET['container'] ?? '');

if (!$server_id || !$container) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
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

$sshTarget  = escapeshellarg($sshUser . '@' . $ip);
$dockerCmd  = escapeshellarg("docker inspect " . escapeshellarg($container));
$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=10"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget $dockerCmd";

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    echo json_encode(['success' => false, 'message' => 'SSH failed']);
    exit;
}

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($proc);

if ($exitCode !== 0 || empty($stdout)) {
    echo json_encode(['success' => false, 'message' => trim($stderr) ?: 'Container not found']);
    exit;
}

$inspect = json_decode($stdout, true);
if (!$inspect || !isset($inspect[0])) {
    echo json_encode(['success' => false, 'message' => 'Invalid inspect output']);
    exit;
}

$data = $inspect[0];

// Parse env vars into key=>value map
$envRaw  = $data['Config']['Env'] ?? [];
$envMap  = [];
foreach ($envRaw as $entry) {
    $pos = strpos($entry, '=');
    if ($pos !== false) {
        $envMap[substr($entry, 0, $pos)] = substr($entry, $pos + 1);
    } else {
        $envMap[$entry] = '';
    }
}

// Parse port mappings: HostPort from NetworkSettings.Ports
$portsRaw = $data['NetworkSettings']['Ports'] ?? [];
$ports    = [];
foreach ($portsRaw as $containerPort => $bindings) {
    if (!$bindings) continue;
    foreach ($bindings as $b) {
        $ports[] = [
            'container' => $containerPort,
            'host'      => ($b['HostIp'] && $b['HostIp'] !== '0.0.0.0' ? $b['HostIp'] . ':' : '') . $b['HostPort'],
        ];
    }
}

echo json_encode([
    'success'  => true,
    'name'     => $data['Name'] ?? $container,
    'image'    => $data['Config']['Image'] ?? '',
    'status'   => $data['State']['Status'] ?? '',
    'started'  => $data['State']['StartedAt'] ?? '',
    'env'      => $envMap,
    'ports'    => $ports,
    'server_ip' => $vps['ip_address'],
]);
