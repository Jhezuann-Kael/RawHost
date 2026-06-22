<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../repositories/TransactionRepository.php';
require_once '../../repositories/UserRepository.php';
require_once '../../services/OxaPayService.php';

session_start();
header('Content-Type: application/json');

// 3. Check Balance & Deduct
$userId = authenticate_user();
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get Input
$data = json_decode(file_get_contents('php://input'), true);

$amount = $data['amount'] ?? 0;
$paymentCurrency = $data['payment_currency'] ?? ''; // e.g. TRX
$network = $data['network'] ?? ''; // e.g. TRC20

if ($amount <= 0 || empty($paymentCurrency) || empty($network)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Minimum Amount Check
if ($paymentCurrency === 'BTC' && $amount < 10) {
    echo json_encode(['success' => false, 'message' => 'Minimum recharge for BTC is $10']);
    exit;
}

if ($amount < 5) {
    echo json_encode(['success' => false, 'message' => 'Minimum recharge is $5']);
    exit;
}

try {
    $repo = new TransactionRepository();
    $oxapay = new OxaPayService();
    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);

    // Generate unique Order ID
    $orderId = 'ORD-' . time() . '-' . rand(1000, 9999);
    $email = $user['email'] ?? 'user_' . $userId . '@rawhost.net';
    $description = "Wallet Recharge $amount USD";
    // Call OxaPay API
    $response = $oxapay->createPayment($amount, 'USD', $paymentCurrency, $network, $orderId, $email, $description);

    if (isset($response['result']) && $response['result'] == 100 && isset($response['data'])) {
        // According to user snippet:
        // status: 200, message: "...", data: { ... }
        // But some OxaPay docs say result: 100.
        // Let's assume the user snippet is key. User snippet shows "status": 200.
        // Wait, I should check the snippet again.
        // User snippet:
        // { "data": { ... }, "message": "...", "status": 200, ... }
        // So I should check $response['status'] == 200.
    }

    // Let's check status code
    if (isset($response['status']) && $response['status'] == 200) {
        $paymentData = $response['data'];

        $transactionData = [
            'user_id' => $userId,
            'amount' => $amount, // USD
            'currency' => 'USD',
            'payment_amount' => $paymentData['pay_amount'],
            'payment_currency' => $paymentData['pay_currency'], // Should match input
            'network' => $paymentData['network'],
            'address' => $paymentData['address'],
            'memo' => $paymentData['memo'] ?? '', // Might be empty
            'qr_code' => $paymentData['qr_code'],
            'expired_at' => $paymentData['expired_at'],
            'order_id' => $paymentData['order_id'], // Same as passed
            'description' => $paymentData['description'],
            'track_id' => $paymentData['track_id'],
            'tx_hash' => null, // Initially null
            'status' => 'PENDING'
        ];

        // Save to DB
        $repo->create($transactionData);
        echo json_encode(['success' => true, 'data' => $transactionData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'OxaPay Error: ' . ($response['message'] ?? 'Unknown')]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
