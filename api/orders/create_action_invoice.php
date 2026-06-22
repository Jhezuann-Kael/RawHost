<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../services/OxaPayService.php';

$logFile = __DIR__ . '/../../logs/action_invoice.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
function actionLog($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $userId = authenticate_user();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;

actionLog("create_action_invoice called by user_id=$userId — " . json_encode($input));

$orderId     = $input['order_id']         ?? null;
$payCurrency = $input['payment_currency'] ?? null;
$network     = $input['network']          ?? null;

if (!$orderId || !$payCurrency || !$network) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: order_id, payment_currency, network.']);
    exit;
}

try {
    $orderRepo = new OrderRepository();
    $order     = $orderRepo->getById((int) $orderId);

    if (!$order || (int) $order['user_id'] !== $userId) {
        throw new Exception("Order not found");
    }
    if ($order['status'] !== 'PENDING') {
        throw new Exception("Order is no longer pending");
    }

    $vpsRepo = new VpsRepository();
    $vps     = $vpsRepo->getById((int) $order['vps_id']);
    if (!$vps) throw new Exception("Server not found");

    $totalAmount   = (float) $order['total_amount'];
    $orderDuration = (int)   $order['duration'];
    $orderPlanId   = (int)   $order['plan_id'];

    $action = ($orderPlanId && $orderPlanId !== (int) $vps['plan_id']) ? 'upgrade' : 'renew';

    if ($totalAmount < 1) {
        throw new Exception("Minimum amount for crypto payment is \$1.00 USD. Current: \$" . number_format($totalAmount, 4));
    }

    $planName = $vps['plan_name'] ?? 'VPS Plan';
    if ($action === 'upgrade') {
        $planRepo = new PlanRepository();
        $newPlan  = $planRepo->getById($orderPlanId);
        if ($newPlan) $planName = $newPlan['name'] ?? $planName;
    }

    $userRepo = new UserRepository();
    $user     = $userRepo->getById($userId);
    $email    = $user['email'] ?? "user_{$userId}@rawhost.net";

    $oxaOrderId = 'VPS-ACT-' . time() . '-' . rand(1000, 9999);
    $desc       = "VPS {$action} - " . ($vps['name'] ?? "Server#{$order['vps_id']}")
                . ($action === 'renew' ? " ({$orderDuration}h)" : " (Upgrade)");

    actionLog("Calling OxaPay — currency={$payCurrency}, network={$network}, amount={$totalAmount} USD, oxaOrderId={$oxaOrderId}");

    $oxapay  = new OxaPayService();
    $response = $oxapay->createPayment($totalAmount, 'USD', $payCurrency, $network, $oxaOrderId, $email, $desc);

    actionLog("OxaPay response: " . json_encode($response));

    if (!isset($response['status']) || $response['status'] != 200) {
        throw new Exception("OxaPay Error: " . ($response['message'] ?? 'Unknown error'));
    }

    $payData = $response['data'];
    $typeStr = ($action === 'renew') ? 'vps_renew' : 'vps_upgrade';

    $orderMeta = json_encode([
        'action'        => $action,
        'server_id'     => (int) $order['vps_id'],
        'plan_id'       => $orderPlanId,
        'duration'      => $orderDuration,
        'name_server'   => $vps['name'] ?? $vps['hostname'] ?? "Server#{$order['vps_id']}",
        'user_id'       => $userId,
        'plan_name'     => $planName,
        'local_order_id'=> (int) $orderId,
    ]);

    $transRepo = new TransactionRepository();
    $localTxId = $transRepo->createVpsPurchase([
        'user_id'          => $userId,
        'amount'           => $totalAmount,
        'currency'         => 'USD',
        'payment_amount'   => $payData['pay_amount']   ?? 0,
        'payment_currency' => $payData['pay_currency'] ?? $payCurrency,
        'network'          => $payData['network']      ?? $network,
        'address'          => $payData['address']      ?? null,
        'memo'             => $payData['memo']         ?? null,
        'qr_code'          => $payData['qr_code']      ?? null,
        'expired_at'       => $payData['expired_at']   ?? null,
        'track_id'         => $payData['track_id'],
        'type'             => $typeStr,
        'order_metadata'   => $orderMeta,
        'description'      => "$typeStr Server: " . ($vps['name'] ?? "VPS#{$order['vps_id']}"),
    ]);

    actionLog("SUCCESS — track_id=" . $payData['track_id'] . ", local_id={$localTxId}, order_id={$orderId}");

    echo json_encode([
        'success' => true,
        'data'    => [
            'track_id'     => $payData['track_id'],
            'local_id'     => $localTxId,
            'address'      => $payData['address']      ?? null,
            'memo'         => $payData['memo']         ?? null,
            'qr_code'      => $payData['qr_code']      ?? null,
            'pay_amount'   => $payData['pay_amount']   ?? null,
            'pay_currency' => $payData['pay_currency'] ?? null,
            'network'      => $payData['network']      ?? null,
            'expired_at'   => $payData['expired_at']   ?? null,
            'amount_usd'   => $totalAmount,
        ],
    ]);

} catch (Throwable $e) {
    actionLog("FATAL: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
