<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userRepo = new UserRepository();
    $user = $userRepo->getById($_SESSION['user_id']);

    if ($user) {
        // Remove password from response
        unset($user['password']);
        unset($user['password_hash']); // just in case

        if (in_array($user['id'], [19, 20, 22, 23])) {
            $user['referrals_count'] = $userRepo->countReferrals($user['id']);
            $user['valid_referrals_count'] = $userRepo->countValidReferrals($user['id']);
        }

        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
