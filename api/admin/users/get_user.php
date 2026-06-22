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
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Solo administradores pueden ver detalles de usuarios']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID de usuario requerido']);
        exit;
    }

    $userId = (int) $_GET['id'];
    $userRepo = new UserRepository();

    // Get user details
    $user = $userRepo->getUserWithDetails($userId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'user' => $user
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
