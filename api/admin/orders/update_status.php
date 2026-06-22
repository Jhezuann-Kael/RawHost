<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../repositories/OrderRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $data      = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId   = (int)($data['order_id'] ?? 0);
    $newStatus = strtoupper(trim($data['status'] ?? ''));
    $allowed   = ['PENDING', 'COMPLETED', 'CANCELLED'];

    if (!$orderId || !in_array($newStatus, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    $repo = new OrderRepository();
    $repo->updateStatus($orderId, $newStatus);

    @file_put_contents(__DIR__ . '/../../../logs/admin_actions.log',
        date('[Y-m-d H:i:s] ') . "Admin #{$_SESSION['user_id']} set order #$orderId → $newStatus\n",
        FILE_APPEND);

    echo json_encode(['success' => true, 'message' => "Orden #$orderId → $newStatus"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
