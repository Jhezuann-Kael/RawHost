<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../domains/domain_prices.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';
require_once __DIR__ . '/../../services/OxaPayService.php';

$logFile = __DIR__ . '/../../logs/domain_invoice.log';
function domainInvoiceLog($msg)
{
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


try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $domainName      = $input['domain_name']     ?? '';
    $domainSuffix    = $input['domain_suffix']    ?? '';
    $years           = (int) ($input['vyear']     ?? 1);
    $password        = $input['password']         ?? '';
    $paymentCurrency = $input['payment_currency'] ?? '';
    $network         = $input['network']          ?? '';

    if (empty($domainName) || empty($domainSuffix)) {
        throw new Exception("Domain name and suffix are required");
    }
    if (empty($paymentCurrency) || empty($network)) {
        throw new Exception("Payment currency and network are required");
    }

    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);
    if (!$user) throw new Exception("User not found");

    $suffixClean = ltrim($domainSuffix, '.');
    $basePrice  = $domainPricesRegister[$suffixClean] ?? DOMAIN_PRICE_DEFAULT;
    $renewPrice = $domainPricesRenew[$suffixClean]    ?? DOMAIN_PRICE_DEFAULT;
    $price = $basePrice;
    if ($years > 1) {
        $price += ($years - 1) * $renewPrice;
    }

    if ($price < 1) throw new Exception('Minimum order amount is $1');

    // Security: re-check availability before charging
    $api = new ExternalApiService();
    $queryName   = str_replace('.' . $suffixClean, '', $domainName);
    $checkResult = $api->checkDomainAvailability($queryName, $suffixClean);
    $checkData   = $checkResult['response']['data'][0] ?? [];

    $isAvailable = false;
    if (isset($checkData['status']) && $checkData['status'] === 'available') $isAvailable = true;
    if (isset($checkData['available']) && $checkData['available'])           $isAvailable = true;
    if (!$isAvailable) {
        $st = $checkData['status'] ?? '';
        if ($st === 'unavailable' || $st === 'unknown') {
            throw new Exception("Domain is not available");
        }
    }

    $isPremium = false;
    if (isset($checkData['premium'])    && ($checkData['premium']   === true || $checkData['premium'] === 1)) $isPremium = true;
    if (isset($checkData['is_premium']) && $checkData['is_premium'])                                           $isPremium = true;
    if (isset($checkData['type'])       && $checkData['type'] === 'premium')                                   $isPremium = true;
    if ($isPremium) throw new Exception("Premium domains cannot be purchased.");

    // Create PENDING order so the user can track/re-pay if the transaction expires
    $orderRepo      = new OrderRepository();
    $pendingOrderId = $orderRepo->create([
        'user_id'         => $userId,
        'domain_id'       => null,
        'total_amount'    => $price,
        'currency'        => 'USD',
        'status'          => 'PENDING',
        'domain_name'     => $domainName . '.' . $suffixClean,
        'domain_password' => $password,
        'product_domain'  => $suffixClean,
        'domain_year'     => $years,
        'type'            => 'domain',
    ]);

    // Create OxaPay invoice
    $oxapay      = new OxaPayService();
    $orderId     = 'DOM-' . time() . '-' . rand(1000, 9999);
    $email       = $user['email'] ?? 'user_' . $userId . '@rawhost.net';
    $description = "Domain Purchase - {$domainName}.{$suffixClean} ({$years} year(s))";

    domainInvoiceLog("Requesting OxaPay invoice — domain={$domainName}.{$suffixClean}, currency=$paymentCurrency, network=$network, amount=$price USD");

    $response = $oxapay->createPayment($price, 'USD', $paymentCurrency, $network, $orderId, $email, $description);

    domainInvoiceLog("OxaPay response: " . json_encode($response));

    if (!isset($response['status']) || $response['status'] != 200) {
        $errMsg = $response['message'] ?? 'Unknown OxaPay error';
        throw new Exception('OxaPay Error: ' . $errMsg);
    }

    $paymentData = $response['data'];

    $orderMeta = json_encode([
        'domain_name'    => $domainName,
        'domain_suffix'  => $suffixClean,
        'vyear'          => $years,
        'password'       => $password,
        'user_id'        => $userId,
        'price'          => $price,
        'local_order_id' => $pendingOrderId,
    ]);

    $transRepo = new TransactionRepository();
    $localId   = $transRepo->createVpsPurchase([
        'user_id'          => $userId,
        'amount'           => $price,
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
        'type'             => 'domain_purchase',
        'order_metadata'   => $orderMeta,
    ]);

    domainInvoiceLog("Domain invoice created — track_id={$paymentData['track_id']}, local_id=$localId, user_id=$userId, domain={$domainName}.{$suffixClean}");

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
            'amount_usd'   => $price,
            'domain'       => $domainName . '.' . $suffixClean,
        ]
    ]);

} catch (Exception $e) {
    domainInvoiceLog("ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
