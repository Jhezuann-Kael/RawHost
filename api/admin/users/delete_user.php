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
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Solo administradores pueden eliminar usuarios']);
    exit;
}

// Only accept DELETE or POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID de usuario requerido']);
        exit;
    }

    $userIdToDelete = (int) $input['user_id'];

    // Prevent self-deletion
    if ($userIdToDelete === $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No puedes eliminar tu propia cuenta de administrador']);
        exit;
    }

    $userRepo = new UserRepository();

    // Check if user exists
    $existingUser = $userRepo->getById($userIdToDelete);
    if (!$existingUser) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Delete user
    if ($userRepo->delete($userIdToDelete)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Usuario eliminado correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al eliminar usuario'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
