<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';

session_start();
$userId = authenticate_user();

// Only superusers
require_once __DIR__ . '/../../repositories/UserRepository.php';
$userRepo = new UserRepository();
$user = $userRepo->getById($userId);
if (!$user || empty($user['is_superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$planRepo = new PlanRepository();

// PUT /api/admin/plans.php — update plan price
// Body: { id, price }
if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($body['id']) || !isset($body['price'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id and price are required']);
        exit;
    }

    $price = floatval($body['price']);
    if ($price < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'price must be >= 0']);
        exit;
    }

    try {
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare("UPDATE plans SET price = :price, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':price' => $price, ':id' => $body['id']]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
