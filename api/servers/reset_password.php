<?php
session_start();
require_once '../../repositories/VpsRepository.php';
require_once '../../services/ExternalApiService.php';
require_once '../../api/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$vpsId = $data['vps_id'] ?? 0;

if (!$vpsId) {
    echo json_encode(['success' => false, 'message' => 'Invalid VPS ID']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vps = $vpsRepo->getById($vpsId);

    if (!$vps || $vps['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Check if VPS is active
    if ($vps['status'] !== 'ACTIVE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo se puede resetear la contraseña cuando el VPS está activo']);
        exit;
    }

    $apiService = new ExternalApiService();
    $result = $apiService->resetPassword($vps['external_id']);

    if ($result['http_code'] === 200 && $result['response']['success']) {
        // The API returns the new password in the data
        // Assumed structure based on similar endpoints: data.data.password or similar.
        // User didn't specify exact response structure for password reset success, 
        // but asked to "save the new one in metadata".

        // Let's assume the response structure. Usually it's in 'data' key or top level.
        // If we look at previous interactions, the API usually returns { success: true, data: { ... } }

        $newPassword = $result['response']['data']['password'] ?? null;

        // Fallback: maybe it is in 'password' key directly?
        if (!$newPassword) {
            $newPassword = $result['response']['password'] ?? null;
        }

        if ($newPassword) {
            // Update metadata
            $metadata = json_decode($vps['metadata'] ?? '{}', true);
            $metadata['password'] = $newPassword; // storing it in metadata as requested

            $vpsRepo->updateMetadata($vpsId, $metadata);

            echo json_encode(['success' => true, 'new_password' => $newPassword, 'message' => 'Password reset successfully']);
        } else {
            // If we can't find the password in the response, we might have an issue.
            // But we should log what we got.
            error_log("Password reset response missing password: " . print_r($result['response'], true));
            echo json_encode(['success' => false, 'message' => 'Password reset initiated but new password not found in response.']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => $result['response']['message'] ?? 'Failed to reset password']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
