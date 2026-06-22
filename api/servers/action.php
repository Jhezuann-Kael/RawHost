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

// Get input
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    // Fallback to POST if not JSON
    $data = $_POST;
}

$vps_id = $data['vps_id'] ?? null;
$action = $data['action'] ?? null;

if (!$vps_id || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing vps_id or action']);
    exit;
}

$allowed_actions = ['start', 'stop', 'restart'];
if (!in_array($action, $allowed_actions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
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

    // Validate VPS status for each action
    $currentStatus = $vpsData['status'];

    if ($action === 'start' && $currentStatus === 'ACTIVE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El servidor ya está activo']);
        exit;
    }

    if (($action === 'stop' || $action === 'restart') && $currentStatus !== 'ACTIVE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo se puede ' . ($action === 'stop' ? 'apagar' : 'reiniciar') . ' un servidor activo']);
        exit;
    }

    // Call External API via Service
    $externalApi = new ExternalApiService();
    $result = $externalApi->serverAction($external_id, $action);

    $httpCode = $result['http_code'];
    $jsonResponse = $result['response'];

    // Update Status if successful
    if (($httpCode >= 200 && $httpCode < 300) && isset($jsonResponse['status'])) {
        $newStatus = strtoupper($jsonResponse['status']); // active -> ACTIVE
        $vpsRepo->updateStatus($vps_id, $newStatus);

        // Inject the updated status into the response for the frontend
        if (is_array($jsonResponse)) {
            $jsonResponse['new_status'] = $newStatus;
        }
    }

    // Forward the status code and response
    http_response_code($httpCode >= 200 && $httpCode < 300 ? 200 : $httpCode);

    // If the external API returns valid JSON, forward it. Otherwise wrap it.
    if ($jsonResponse) {
        // Ensure we keep our 'success' convention if possible, or just merge
        echo json_encode(array_merge(['success' => $httpCode == 200], $jsonResponse));
    } else {
        echo json_encode([
            'success' => $httpCode == 200,
            'message' => 'External API response: ' . $result['raw_response']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
