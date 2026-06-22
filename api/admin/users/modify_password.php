<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../repositories/UserRepository.php';

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

// Check if user is superuser
if (!isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Solo administradores pueden modificar contraseñas']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['user_id']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID de usuario y nueva contraseña requeridos']);
        exit;
    }

    $userId = (int) $input['user_id'];
    $newPassword = trim($input['new_password']);

    if (empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'La contraseña no puede estar vacía']);
        exit;
    }

    $userRepo = new UserRepository();

    // Check if user exists
    $existingUser = $userRepo->getById($userId);
    if (!$existingUser) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Hash and update password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    if ($userRepo->updatePassword($userId, $passwordHash)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al actualizar la contraseña'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
