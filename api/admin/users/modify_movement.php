<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../models/Database.php';

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

if (!$input || !isset($input['movement_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta movement_id']);
    exit;
}

$movementId  = (int) $input['movement_id'];
$newAmount   = isset($input['amount'])      ? (float) $input['amount']        : null;
$newDesc     = isset($input['description']) ? trim($input['description'])      : null;

if ($newAmount !== null && $newAmount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a 0']);
    exit;
}

try {
    $db   = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT id FROM movements WHERE id = :id");
    $stmt->execute([':id' => $movementId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Movimiento no encontrado']);
        exit;
    }

    $sets   = [];
    $params = [':id' => $movementId];

    if ($newAmount !== null) {
        $sets[]             = 'amount = :amount';
        $params[':amount']  = $newAmount;
    }
    if ($newDesc !== null) {
        $sets[]                  = 'description = :description';
        $params[':description']  = $newDesc;
    }

    if (empty($sets)) {
        echo json_encode(['success' => false, 'message' => 'Nada que actualizar']);
        exit;
    }

    $conn->prepare("UPDATE movements SET " . implode(', ', $sets) . " WHERE id = :id")
         ->execute($params);

    echo json_encode(['success' => true, 'message' => 'Movimiento actualizado']);

} catch (Exception $e) {
    error_log("modify_movement: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
