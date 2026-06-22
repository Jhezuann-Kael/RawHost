<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../helpers/hcaptcha.php';
require_once __DIR__ . '/../../includes/lang_loader.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

// Verify hCaptcha
$captchaToken = $data['h-captcha-response'] ?? '';
if (empty($captchaToken)) {
    echo json_encode(['success' => false, 'message' => $lang['captcha_missing']]);
    exit;
}
$captchaResult = verify_hcaptcha($captchaToken, $_SERVER['REMOTE_ADDR'] ?? null);
if (!$captchaResult['success']) {
    echo json_encode(['success' => false, 'message' => $lang['captcha_fail']]);
    exit;
}

$password = $data['password'] ?? null;
$code = $data['code'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos: falta contraseña']);
    exit;
}

if (!$userId && empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos: falta código o sesión']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

$repo = new UserRepository();
$user = null;

if ($userId) {
    $user = $repo->getById($userId);
} elseif ($code) {
    $user = $repo->findByCode($code);
}

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o código inválido']);
    exit;
}

// Check if we need to enforce username update
$newUsername = null;
if ($user['username'] === 'Unknown' || empty($user['username'])) {
    if (!isset($data['username']) || empty(trim($data['username']))) {
        echo json_encode(['success' => false, 'message' => 'Debes crear un nombre de usuario']);
        exit;
    }

    $checkUsername = trim($data['username']);
    if (strlen($checkUsername) < 3) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario es muy corto']);
        exit;
    }

    // Check availability
    if ($repo->findByUsername($checkUsername)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
        exit;
    }

    $newUsername = $checkUsername;
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Update password
$updateData = [];

// Modify this logic to support username update if needed
// Core updatePassword method only updates password, so we might need a general update here or specific one
$success = $repo->updatePassword($user['id'], $passwordHash);

if ($success && $newUsername) {
    // If we have a new username, update it too
    $repo->update($user['id'], ['username' => $newUsername]);
}

if ($success) {
    // Clear the code so it can't be used again
    $repo->clearCode($user['id']);

    // Handle Referral Code if present
    if (isset($data['referral_code']) && !empty($data['referral_code'])) {
        $referrer = $repo->findByReferralCode($data['referral_code']);
        if ($referrer) {
            $repo->update($user['id'], ['referred_by' => $referrer['id']]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Contraseña guardada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la contraseña']);
}
