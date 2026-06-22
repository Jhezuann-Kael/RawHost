<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../services/OxaPayService.php';

$logFile = __DIR__ . '/../../logs/vps_invoice.log';
function invoiceLog($msg) {
    global $logFile;
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $userId = authenticate_user();
} catch (Exception $ex) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$orderId         = (int) ($input['order_id']         ?? 0);
$serverName      = $input['name_server']             ?? '';
$password        = $input['password']                ?? '';
$paymentCurrency = $input['payment_currency']        ?? '';
$network         = $input['network']                 ?? '';

if (!$orderId || empty($serverName) || empty($password) || empty($paymentCurrency) || empty($network)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $orderRepo = new OrderRepository();
    $order     = $orderRepo->getById($orderId);

    if (!$order || (int)$order['user_id'] !== $userId) {
        throw new Exception("Order not found");
    }
    if ($order['status'] !== 'PENDING') {
        throw new Exception("Order is no longer pending");
    }

    $planRepo = new PlanRepository();
    $plan     = $planRepo->getById($order['plan_id']);
    if (!$plan) throw new Exception("Plan not found");

    $userRepo = new UserRepository();
    $user     = $userRepo->getById($userId);

    $totalAmount = (float) $order['total_amount'];
    $duration    = (int)   $order['duration'];
    $osImageId   = $order['image_os_id'];

    invoiceLog("INFO: create_invoice — user=$userId, order=$orderId, plan={$plan['name']}, amount=$totalAmount");

    if ($paymentCurrency === 'BTC' && $totalAmount < 10) {
        throw new Exception('Minimum payment for BTC is $10');
    }
    if ($totalAmount < 1) {
        throw new Exception('Minimum order amount is $1');
    }

    $oxapay      = new OxaPayService();
    $invoiceId   = 'VPS-' . time() . '-' . rand(1000, 9999);
    $email       = $user['email'] ?? 'user_' . $userId . '@rawhost.net';
    $description = "VPS Purchase - {$plan['name']} ({$duration}h)";

    invoiceLog("INFO: Requesting OxaPay invoice — currency=$paymentCurrency, network=$network, amount=$totalAmount");

    $response = $oxapay->createPayment($totalAmount, 'USD', $paymentCurrency, $network, $invoiceId, $email, $description);

    invoiceLog("INFO: OxaPay response: " . json_encode($response));

    if (!isset($response['status']) || $response['status'] != 200) {
        $errMsg = $response['message'] ?? 'Unknown OxaPay error';
        throw new Exception('OxaPay Error: ' . $errMsg);
    }

    $paymentData = $response['data'];

    $orderMeta = json_encode([
        'plan_id'        => $order['plan_id'],
        'os_image_id'    => $order['image_os_id'],
        'application_id' => $order['application_id'],
        'duration'       => $duration,
        'name_server'    => $serverName,
        'password'       => $password,
        'user_id'        => $userId,
        'plan_name'      => $plan['name'],
        'local_order_id' => $orderId,
    ]);

    $transRepo = new TransactionRepository();
    $localId   = $transRepo->createVpsPurchase([
        'user_id'          => $userId,
        'amount'           => $totalAmount,
        'currency'         => 'USD',
        'payment_amount'   => $paymentData['pay_amount'],
        'payment_currency' => $paymentData['pay_currency'],
        'network'          => $paymentData['network'],
        'address'          => $paymentData['address'],
        'memo'             => $paymentData['memo'] ?? '',
        'qr_code'          => $paymentData['qr_code'],
        'expired_at'       => $paymentData['expired_at'],
        'order_id'         => $paymentData['order_id'],
        'description'      => $paymentData['description'],
        'track_id'         => $paymentData['track_id'],
        'tx_hash'          => null,
        'status'           => 'PENDING',
        'type'             => 'vps_purchase',
        'order_metadata'   => $orderMeta,
    ]);

    invoiceLog("SUCCESS: VPS invoice created — track_id={$paymentData['track_id']}, local_id=$localId, order_id=$orderId, user_id=$userId");

    echo json_encode([
        'success' => true,
        'data' => [
            'local_id'     => $localId,
            'track_id'     => $paymentData['track_id'],
            'address'      => $paymentData['address'],
            'memo'         => $paymentData['memo'] ?? null,
            'qr_code'      => $paymentData['qr_code'],
            'pay_amount'   => $paymentData['pay_amount'],
            'pay_currency' => $paymentData['pay_currency'],
            'network'      => $paymentData['network'],
            'expired_at'   => $paymentData['expired_at'],
            'amount_usd'   => $totalAmount,
            'plan_name'    => $plan['name'],
        ],
    ]);

} catch (Exception $e) {
    invoiceLog("ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
