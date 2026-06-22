<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

try {
    $userId = authenticate_user();
    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);

    if ($user) {
        echo json_encode(['success' => true, 'balance' => $user['balance'] ?? 0]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
