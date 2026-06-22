<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['current_password']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos requeridos']);
        exit;
    }

    $currentPass = $input['current_password'];
    $newPass = trim($input['new_password']);

    if (strlen($newPass) < 6) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
        exit;
    }

    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Standard session user check is already done by getting ID from session.
    // Verify current password
    if (!password_verify($currentPass, $user['password'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta']);
        exit;
    }

    // Hash and Update
    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
    if ($userRepo->updatePassword($userId, $newHash)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la contraseña']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
