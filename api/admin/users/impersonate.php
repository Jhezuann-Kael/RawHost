<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../repositories/UserRepository.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Prevent impersonating while already impersonating
if (isset($_SESSION['impersonating_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Ya estás en modo inspección. Sal primero antes de entrar a otra cuenta.']);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$targetId = (int)($data['user_id'] ?? 0);

if (!$targetId) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

$userRepo = new UserRepository();
$target   = $userRepo->getById($targetId);

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Save admin identity
$_SESSION['impersonating_admin'] = [
    'user_id'      => $_SESSION['user_id'],
    'username'     => $_SESSION['username'],
    'is_superuser' => $_SESSION['is_superuser'],
];

// Switch to target user
$_SESSION['user_id']      = $target['id'];
$_SESSION['username']     = $target['username'];
$_SESSION['is_superuser'] = 0;

echo json_encode(['success' => true, 'redirect' => '/dashboard']);
