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
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Solo administradores pueden modificar usuarios']);
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

    if (!isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID de usuario requerido']);
        exit;
    }

    $userIdToEdit = (int) $input['user_id'];

    // Prevent self-demotion from superuser
    if ($userIdToEdit === $_SESSION['user_id'] && isset($input['is_superuser']) && !$input['is_superuser']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No puedes quitarte a ti mismo los permisos de administrador']);
        exit;
    }

    $userRepo = new UserRepository();

    // Check if user exists
    $existingUser = $userRepo->getById($userIdToEdit);
    if (!$existingUser) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Prepare data to update
    $dataToUpdate = [];

    if (isset($input['username']) && !empty($input['username'])) {
        $dataToUpdate['username'] = trim($input['username']);
    }

    if (isset($input['email']) && !empty($input['email'])) {
        $dataToUpdate['email'] = trim($input['email']);
    }

    if (isset($input['is_superuser'])) {
        $dataToUpdate['is_superuser'] = $input['is_superuser'] ? 1 : 0;
    }

    if (isset($input['telegram_id'])) {
        $dataToUpdate['telegram_id'] = $input['telegram_id'] ?: null;
    }

    if (isset($input['referral_code'])) {
        $dataToUpdate['referral_code'] = !empty($input['referral_code']) ? trim($input['referral_code']) : null;
    }

    if (array_key_exists('preferred_currency', $input)) {
        $pref = trim($input['preferred_currency'] ?? '');
        $dataToUpdate['preferred_currency'] = $pref !== '' ? $pref : null;
    }

    if (empty($dataToUpdate)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No hay datos para actualizar']);
        exit;
    }

    // Update user
    if ($userRepo->update($userIdToEdit, $dataToUpdate)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Usuario actualizado correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al actualizar usuario'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
