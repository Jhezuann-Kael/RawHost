<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../agents/gotify.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $userRepo = new UserRepository();
    $user     = $userRepo->getById((int) $_SESSION['user_id']);

    $token = gotify_create_app((int) $user['id'], $user['username']);

    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear la app en Gotify. Verifica que el servidor esté activo.']);
        exit;
    }

    echo json_encode(['success' => true, 'token' => $token]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
