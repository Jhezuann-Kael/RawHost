<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../services/OxaPayService.php';

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

$input           = json_decode(file_get_contents('php://input'), true);
$orderId         = (int)    ($input['order_id']         ?? 0);
$paymentCurrency = trim(    $input['payment_currency']  ?? '');
$network         = trim(    $input['network']           ?? '');
$userId          = (int)    $_SESSION['user_id'];

if (!$orderId || !$paymentCurrency || !$network) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
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

    if ($order['type'] !== 'managed_service' || $order['status'] !== 'PENDING') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order is not a pending managed service']);
        exit;
    }

    $amount      = (float) $order['total_amount'];
    $description = $order['description'] ?? 'Managed Service #' . $orderId;

    $userRepo = new UserRepository();
    $user     = $userRepo->getById($userId);
    $email    = $user['email'] ?? 'user_' . $userId . '@rawhost.net';

    $oxapay   = new OxaPayService();
    $oxaOrderId = 'SVC-' . $orderId . '-' . time();
    $response = $oxapay->createPayment($amount, 'USD', $paymentCurrency, $network, $oxaOrderId, $email, $description);

    if (!isset($response['status']) || $response['status'] != 200) {
        echo json_encode(['success' => false, 'message' => $response['message'] ?? 'OxaPay error']);
        exit;
    }

    $payData = $response['data'];

    // Record transaction — type managed_service so webhook knows what to do
    $txRepo  = new TransactionRepository();
    $txRepo->create([
        'user_id'          => $userId,
        'amount'           => $amount,
        'currency'         => 'USD',
        'payment_amount'   => $payData['pay_amount'],
        'payment_currency' => $payData['pay_currency'],
        'network'          => $payData['network'],
        'address'          => $payData['address'],
        'memo'             => $payData['memo'] ?? null,
        'qr_code'          => $payData['qr_code'],
        'expired_at'       => $payData['expired_at'],
        'order_id'         => (string) $orderId,
        'description'      => $description,
        'track_id'         => $payData['track_id'],
        'tx_hash'          => null,
        'type'             => 'managed_service',
    ]);

    echo json_encode([
        'success' => true,
        'data'    => [
            'payment_amount'   => $payData['pay_amount'],
            'payment_currency' => $payData['pay_currency'],
            'network'          => $payData['network'],
            'address'          => $payData['address'],
            'memo'             => $payData['memo'] ?? null,
            'qr_code'          => $payData['qr_code'],
            'expired_at'       => $payData['expired_at'],
            'track_id'         => $payData['track_id'],
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
