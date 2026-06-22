<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$vps_id = $_GET['id'] ?? null;

if (!$vps_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing server ID']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vpsData = $vpsRepo->getById($vps_id);

    if (!$vpsData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'VPS not found']);
        exit;
    }

    // Verify Ownership
    if ($vpsData['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $external_id = $vpsData['external_id'];
    if (!$external_id) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No external ID linked to this VPS']);
        exit;
    }

    // Call External API via Service
    $externalApi = new ExternalApiService();
    $result = $externalApi->getVnc($external_id);

    $httpCode = $result['http_code'];
    $jsonResponse = $result['response'];

    http_response_code($httpCode >= 200 && $httpCode < 300 ? 200 : $httpCode);

    if ($jsonResponse) {
        // Just forward the exact response from the external API (success, vnc_url, vnc_password, etc)
        // Ensure success is boolean
        $jsonResponse['success'] = $httpCode == 200 && ($jsonResponse['success'] ?? false);
        echo json_encode($jsonResponse);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'External API error: ' . $result['raw_response']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
