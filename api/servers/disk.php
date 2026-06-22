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

$vpsId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$vpsId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing VPS ID']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vpsData = $vpsRepo->getById($vpsId);

    if (!$vpsData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'VPS not found']);
        exit;
    }

    if ($vpsData['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    if (empty($vpsData['external_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'VPS has no external ID']);
        exit;
    }

    $apiService = new ExternalApiService();
    $result = $apiService->getDetailDisk($vpsData['external_id']);

    if ($result['http_code'] !== 200) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'External API error', 'http_code' => $result['http_code']]);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $result['response']['data'] ?? $result['response']]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
