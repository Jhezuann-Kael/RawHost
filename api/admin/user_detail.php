<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if (!isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

try {
    $userRepo = new UserRepository();
    $user = $userRepo->getUserWithDetails($userId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    echo json_encode(['success' => true, 'user' => $user]);

} catch (Exception $e) {
    error_log("Error al obtener usuario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener información del usuario']);
}
