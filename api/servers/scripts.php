<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$server_id = (int)($_GET['id'] ?? 0);
if (!$server_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing server ID']);
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$meta = json_decode($vps['metadata'] ?? '{}', true);
$osRaw = strtolower($meta['os'] ?? $vps['os_image_id'] ?? '');

$osFamily = 'unknown';
if (str_contains($osRaw, 'debian'))    $osFamily = 'debian';
elseif (str_contains($osRaw, 'ubuntu')) $osFamily = 'ubuntu';
elseif (str_contains($osRaw, 'centos')) $osFamily = 'centos';
elseif (str_contains($osRaw, 'alma'))   $osFamily = 'almalinux';
elseif (str_contains($osRaw, 'rocky'))  $osFamily = 'rocky';

$catalogPath = __DIR__ . '/../../vps_scripts/scripts_catalog.json';
$catalog = json_decode(file_get_contents($catalogPath), true) ?? [];

$available = array_values(array_filter($catalog, function ($s) use ($osFamily) {
    return !($s['hidden'] ?? false) && in_array($osFamily, $s['os_compat'] ?? [], true);
}));

// Strip internal file paths from response
foreach ($available as &$s) {
    unset($s['remote_file']);
}

echo json_encode([
    'success' => true,
    'os'      => $osFamily,
    'scripts' => $available,
]);
