<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


header('Content-Type: application/json');
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

    if ($userRepo->findByUsername($data->username)) {
        echo json_encode(['success' => false, 'message' => $lang['api_user_exists']]);
    } elseif (isset($data->email) && !empty($data->email) && $userRepo->findByEmail($data->email)) {
        // Enforce unique email if provided
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado']);
    } else {
        $hash = password_hash($data->password, PASSWORD_BCRYPT);
        $referralCode = isset($data->referral_code) ? $data->referral_code : null;

        $userByReferd = $userRepo->findByReferralCode($referralCode);

        $newUserId = $userRepo->create([
            'username' => $data->username,
            'email' => isset($data->email) ? $data->email : null,
            'password_hash' => $hash,
            'referred_by' => isset($userByReferd['id']) ? $userByReferd['id'] : null,
            'referral_code' => null,
        ]);

        if ($newUserId) {
            echo json_encode(['success' => true, 'message' => $lang['api_register_success']]);
        } else {
            echo json_encode(['success' => false, 'message' => $lang['api_register_failed']]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => $lang['api_incomplete_data']]);
}
