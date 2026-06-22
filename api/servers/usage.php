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

$vpsId = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';

if (!$vpsId || !$type) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing VPS ID or type']);
    exit;
}

// Validate type
$validTypes = ['network', 'cpu', 'memory', 'disks'];
if (!in_array($type, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid type. Must be: network, cpu, or memory']);
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

    // Verify Ownership
    if ($vpsData['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $externalId = $vpsData['external_id'];

    if (empty($externalId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'VPS has no external ID']);
        exit;
    }

    // Fetch usage data from external API
    $apiService = new ExternalApiService();
    $result = $apiService->getServerUsage($externalId, $type);

    if ($result['http_code'] !== 200) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'External API error', 'http_code' => $result['http_code']]);
        exit;
    }

    $responseData = $result['response'];

    // Return the data portion
    echo json_encode([
        'success' => true,
        'data' => $responseData['data'] ?? []
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
