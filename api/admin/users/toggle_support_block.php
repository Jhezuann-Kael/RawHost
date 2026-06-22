<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../repositories/UserRepository.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

if (!isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userIdToBlock = (int) ($input['user_id'] ?? 0);

    if (!$userIdToBlock) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID de usuario requerido']);
        exit;
    }

    if ($userIdToBlock === $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No puedes bloquearte a ti mismo']);
        exit;
    }

    $userRepo = new UserRepository();
    $user = $userRepo->getById($userIdToBlock);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    $newValue = $user['support_blocked'] ? 0 : 1;
    $userRepo->update($userIdToBlock, ['support_blocked' => $newValue]);

    echo json_encode([
        'status'          => 'success',
        'support_blocked' => $newValue,
        'message'         => $newValue ? 'Usuario bloqueado de soporte' : 'Usuario desbloqueado de soporte',
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
