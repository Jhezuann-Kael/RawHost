<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

if (!isset($input['user_id']) || !isset($input['type']) || !isset($input['amount'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
    exit;
}

$user_id = (int) $input['user_id'];
$type = strtoupper($input['type']);
$amount = (float) $input['amount'];
$description = $input['description'] ?? 'Ajuste de saldo por administrador';

if (!in_array($type, ['IN', 'OUT'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de movimiento inválido']);
    exit;
}

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a 0']);
    exit;
}

try {
    $userRepo = new UserRepository();
    $user = $userRepo->getById($user_id);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // If type is OUT, verify user has enough balance
    if ($type === 'OUT' && $user['balance'] < $amount) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Saldo insuficiente. Saldo actual: $' . number_format($user['balance'], 2)
        ]);
        exit;
    }

    // Create movement (trigger will update balance automatically)
    $movementRepo = new MovementRepository();
    $movementRepo->create([
        'user_id' => $user_id,
        'type' => $type,
        'amount' => $amount,
        'description' => $description
    ]);

    // Get updated user balance
    $updatedUser = $userRepo->getById($user_id);

    echo json_encode([
        'success' => true,
        'message' => 'Saldo actualizado correctamente',
        'data' => [
            'user_id' => $user_id,
            'username' => $user['username'],
            'old_balance' => $user['balance'],
            'new_balance' => $updatedUser['balance'],
            'movement_type' => $type,
            'amount' => $amount
        ]
    ]);

} catch (Exception $e) {
    error_log("Error al crear movimiento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar la operación']);
}
