<?php
header("Content-Type: application/json");
require_once '../../api/config.php';
require_once '../../repositories/AddonRepository.php';
require_once '../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use GET para listar addons.']);
    exit;
}

$addonRepo = new AddonRepository();
$vpsRepo = new VpsRepository();

try {
    // Get addons for a VPS
    $vpsId = $_GET['vps_id'] ?? 0;

    if (!$vpsId) {
        throw new Exception("ID de VPS requerido");
    }

    // Verify ownership (superusers can view any VPS)
    $vps = $vpsRepo->getById($vpsId);
    $isSuperuser = !empty($_SESSION['is_superuser']);
    if (!$vps || ($vps['user_id'] != $userId && !$isSuperuser)) {
        throw new Exception("VPS no encontrado o no tienes permisos");
    }

    // Get Addons
    $addons = $addonRepo->getByVpsId($vpsId);

    // Format if needed (ensure type is consistent, currently only IPV4)
    // AddonRepository usually returns raw rows. 
    // If needed we can iterate and format, but raw is usually fine.

    echo json_encode([
        'success' => true,
        'addons' => $addons
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
