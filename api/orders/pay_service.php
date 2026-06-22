<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';

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

$input   = json_decode(file_get_contents('php://input'), true);
$orderId = (int) ($input['order_id'] ?? 0);
$userId  = (int) $_SESSION['user_id'];

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit;
}

try {
    $orderRepo = new OrderRepository();
    $order     = $orderRepo->getById($orderId);

    if (!$order || (int) $order['user_id'] !== $userId) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if ($order['type'] !== 'managed_service') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order type']);
        exit;
    }

    if ($order['status'] !== 'PENDING') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order is not pending']);
        exit;
    }

    $amount   = (float) $order['total_amount'];
    $userRepo = new UserRepository();
    $user     = $userRepo->getById($userId);

    if ((float) $user['balance'] < $amount) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient balance. You need $' . number_format($amount, 2) . ', your balance is $' . number_format($user['balance'], 2),
        ]);
        exit;
    }

    // Deduct balance
    $movRepo = new MovementRepository();
    $movRepo->create([
        'user_id'     => $userId,
        'type'        => 'OUT',
        'amount'      => $amount,
        'description' => 'Managed service: ' . $order['description'],
    ]);

    // Mark order as completed
    $orderRepo->updateStatus($orderId, 'COMPLETED');

    $updatedUser = $userRepo->getById($userId);

    echo json_encode([
        'success'     => true,
        'message'     => 'Payment successful',
        'new_balance' => $updatedUser['balance'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
