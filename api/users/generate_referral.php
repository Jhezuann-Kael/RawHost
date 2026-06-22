<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$allowedUsers = [19, 20, 22, 23];

if (!in_array($userId, $allowedUsers)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You are not allowed to generate a referral code.']);
    exit;
}

$userRepo = new UserRepository();
$user = $userRepo->getById($userId);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if (!empty($user['referral_code'])) {
    echo json_encode(['success' => true, 'message' => 'Referral code already exists', 'code' => $user['referral_code']]);
    exit;
}

// Generate a random 8-character alphanumeric referral code
function generateRandomString($length = 8)
{
    return strtoupper(substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length));
}

$referralCode = generateRandomString(8);
// Ensure uniqueness (simple check)
while ($userRepo->findByReferralCode($referralCode)) {
    $referralCode = generateRandomString(8);
}

if ($userRepo->setReferralCode($userId, $referralCode)) {
    echo json_encode(['success' => true, 'message' => 'Referral code generated successfully', 'code' => $referralCode]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate code']);
}
