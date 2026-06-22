<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../domains/domain_prices.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/DomainRepository.php';

session_start();
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? $_GET['user_id'];

if (empty($userId)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}





if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}


try {
    // 1. Find User using Repository
    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);


    if (!$user) {
        throw new Exception("User not found");
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $domainName = $body['domain_name'] ?? '';
    $domainSuffix = $body['domain_suffix'] ?? '';
    $years = $body['vyear'] ?? 1;
    $password = $body['password'] ?? '';

    // Construct full domain name if suffix is separate and not in domain_name
    // But usually APIs might want them separate or together. 
    // The previous code passed them separately to orderData.

    if (empty($domainName) || empty($domainSuffix)) {
        throw new Exception("Domain name and suffix are required");
    }

    $suffixClean = ltrim($domainSuffix, '.');
    $basePrice  = $domainPricesRegister[$suffixClean] ?? DOMAIN_PRICE_DEFAULT;
    $renewPrice = $domainPricesRenew[$suffixClean]    ?? DOMAIN_PRICE_DEFAULT;

    $price = $basePrice;
    if ($years > 1) {
        $price += ($years - 1) * $renewPrice;
    }

    $orderData = [
        'domain_name' => $domainName,
        'domain_suffix' => $domainSuffix,
        'vyear' => $years,
        'password' => $password,
        'type_payment' => 'crypto'
    ];

    $api = new ExternalApiService();


    // Security Check: Verify availability and premium status again
    // We need to pass the name component without the suffix to the check function
    // Assuming $domainName includes the suffix (e.g. google.com) based on frontend logic,
    // OR it follows the previous logic where $domainName was the full name?
    // Let's check logic: Frontend sends `domain_name` = "name.suffix" and `domain_suffix` = "suffix".
    // So to get the query part, we strip the suffix.

    $queryName = str_replace('.' . $domainSuffix, '', $domainName);

    $checkResult = $api->checkDomainAvailability($queryName, $domainSuffix);
    $checkData = $checkResult['response']['data'][0];


    // Check if available
    $isAvailable = false;
    if (isset($checkData['status']) && $checkData['status'] === 'available') {
        $isAvailable = true;
    }
    if (isset($checkData['available']) && $checkData['available']) {
        $isAvailable = true;
    }

    if (!$isAvailable) {
        // Allow if status is unknown/different but not explicitly unavailable? 
        // For safety, let's assume if the API call worked, we trust strict availability.
        // But sticking to the loose logic from frontend:

        if ($checkData['status'] === 'unavailable' || $checkData['status'] === 'unknown') {
            throw new Exception("Domain is not available");
        }
    }

    // Check Premium
    $isPremium = false;
    if (isset($checkData['premium']) && ($checkData['premium'] === true || $checkData['premium'] === 1))
        $isPremium = true;
    if (isset($checkData['is_premium']) && $checkData['is_premium'])
        $isPremium = true;
    if (isset($checkData['type']) && $checkData['type'] === 'premium')
        $isPremium = true;

    if ($isPremium) {
        throw new Exception("Premium domains cannot be purchased.");
    }

    // Deduct Balance (Centralized logic)
    // Note: deductBalance throws Exception if insufficient funds
    deductBalance($user['id'], $price, "Purchase of Domain " . $domainName . '.' . $domainSuffix . " for {$years} year(s)");

    // 2. Call External API
    $result = $api->createDomainOrderProvider($orderData);
    $response = $result;


    if (!isset($response['status']) || $response['status'] != 'success') {
        // Refund if API failed by creating an IN movement
        $movRepo->create([
            'user_id' => $user['id'],
            'type' => 'IN',
            'amount' => $price,
            'description' => "Refund for failed domain purchase: " . $domainName . '.' . $domainSuffix
        ]);

        $msg = $response['msg'] ?? 'Unknown error from provider';
        throw new Exception("Order failed: " . $msg);
    }
    // 3. Save to DB using DomainRepository
    $domainRepo = new DomainRepository();

    // Extract info from response if available, otherwise use request data
    $newDomainId = $domainRepo->create([
        'user_id' => $user['id'],
        'domain_name' => $domainName . '.' . $domainSuffix,
        'expiration_date' => date('Y-m-d H:i:s', strtotime("+$years years")),
        'status' => 'ACTIVE',
        'domain_password' => $password,
        'registration_term' => $years,
        'product_id' => $domainSuffix,
        'price_domain' => $price,
        'last_checked' => date('Y-m-d H:i:s'),
        'external_id' => $result['domain']['id']
    ]);

    // 4. Create Order Record
    require_once __DIR__ . '/../../repositories/OrderRepository.php';
    $orderRepo = new OrderRepository();

    $localOrderId = $orderRepo->create([
        'user_id' => $user['id'],
        'domain_id' => $newDomainId,
        'total_amount' => $price, // Use checked price or 0
        'currency' => 'USD',
        'status' => 'COMPLETED', // API success implies completion
        'domain_name' => $domainName . '.' . $domainSuffix,
        'domain_password' => $password,
        'product_domain' => $domainSuffix,
        'domain_year' => $years,
        'type' => 'domain'
    ]);

    $msg = "🌐 <b>Nueva Compra de Dominio</b>\n\n";
    $msg .= "🆔 <b>Orden ID:</b> " . $localOrderId . "\n";
    $msg .= "👤 <b>Usuario ID:</b> " . $user['id'] . "\n";
    $msg .= "🔗 <b>Dominio:</b> " . $domainName . '.' . $domainSuffix . "\n";
    $msg .= "💰 <b>Precio:</b> $" . number_format($price, 2) . "\n";
    $msg .= "📅 <b>Años:</b> " . $years . "\n";
    sendTelegramNotification($msg);

    echo json_encode(['success' => true, 'data' => $response, 'local_id' => $newDomainId]);


} catch (Exception $e) {
    error_log("Domain Order Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
