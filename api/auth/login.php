<?php
header('Content-Type: application/json');
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../includes/lang_loader.php'; // Load language
require_once __DIR__ . '/../helpers/hcaptcha.php'; // hCaptcha verifier

$json = file_get_contents("php://input");
// ... (rest of input handling) ...

$data = json_decode($json);

// Verify hCaptcha first
$captchaToken = isset($data->{'h-captcha-response'}) ? $data->{'h-captcha-response'} : '';
if (empty($captchaToken)) {
    echo json_encode(['success' => false, 'message' => $lang['captcha_missing']]);
    exit;
}

$captchaResult = verify_hcaptcha($captchaToken, $_SERVER['REMOTE_ADDR'] ?? null);
if (!$captchaResult['success']) {
    echo json_encode(['success' => false, 'message' => $lang['captcha_fail']]);
    exit;
}

if (isset($data->username) && isset($data->password)) {
    $userRepo = new UserRepository();
    $user = $userRepo->findByUsername($data->username);

    if ($user && password_verify($data->password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_superuser'] = $user['is_superuser'];
        echo json_encode(['success' => true, 'message' => $lang['api_login_success']]);
    } else {
        echo json_encode(['success' => false, 'message' => $lang['api_login_failed']]);
    }
} else {
    echo json_encode(['success' => false, 'message' => $lang['api_incomplete_data']]);
}
