<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);


// Validate required fields
if (!isset($data['vps_id']) || (!isset($data['os_id']) && !isset($data['application_id']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos (vps_id + os_id o application_id)']);
    exit;
}

$vpsId = intval($data['vps_id']);
$osId = isset($data['os_id']) ? intval($data['os_id']) : null;
$appId = isset($data['application_id']) ? intval($data['application_id']) : null;

try {
    $vpsRepo = new VpsRepository();

    // Get VPS and verify ownership
    $vps = $vpsRepo->getById($vpsId);

    if (!$vps) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Servidor no encontrado']);
        exit;
    }

    if ($vps['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para reinstalar este servidor']);
        exit;
    }

    // Check if VPS is active
    if ($vps['status'] !== 'ACTIVE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo se puede reinstalar el sistema operativo cuando el servidor está activo']);
        exit;
    }


    $externalApi = new ExternalApiService();
    $result = $externalApi->reinstallServer($vps['external_id'], $osId, $appId);

    // Check response
    if ($result['http_code'] === 200 || $result['http_code'] === 201) {

        // 1. Resolve display name from plan
        $planRepo = new PlanRepository();
        $plan = $planRepo->getById($vps['plan_id']);
        $displayName = 'Unknown';

        if ($appId && $plan) {
            $apps = is_string($plan['available_applications'] ?? '') ? json_decode($plan['available_applications'], true) : ($plan['available_applications'] ?? []);
            foreach ((array) $apps as $app) {
                if ($app['id'] == $appId) { $displayName = $app['name']; break; }
            }
        } elseif ($osId && $plan) {
            $images = is_string($plan['available_os_image_versions'] ?? '') ? json_decode($plan['available_os_image_versions'], true) : ($plan['available_os_image_versions'] ?? []);
            foreach ((array) $images as $img) {
                if (($img['id'] ?? $img['os_image_id'] ?? null) == $osId) { $displayName = $img['name'] ?? $img['os_name'] ?? 'Unknown'; break; }
            }
        }

        // 2. Update metadata
        $currentMetadata = json_decode($vps['metadata'] ?? '{}', true);
        $currentMetadata['os'] = $displayName;
        unset($currentMetadata['app_login_link']);

        if ($appId) {
            $appLoginLink = $result['response']['login_link'] ?? null;
            if ($appLoginLink) {
                $currentMetadata['app_login_link'] = $appLoginLink;
            }
        }

        $vpsRepo->updateMetadata($vpsId, $currentMetadata);

        // 3. Update OS/app columns (mutually exclusive)
        if ($appId) {
            $vpsRepo->updateApp($vpsId, $appId);
        } else {
            $vpsRepo->updateOs($vpsId, $osId);
        }
        $vpsRepo->updateStatus($vpsId, 'ACTIVE');

        echo json_encode([
            'success' => true,
            'message' => 'Servidor siendo reinstalado correctamente',
            'data' => $result['response']
        ]);
    } else {
        http_response_code($result['http_code']);
        echo json_encode([
            'success' => false,
            'message' => 'Error al reinstalar el servidor',
            'error' => $result['response']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
    exit;
}
