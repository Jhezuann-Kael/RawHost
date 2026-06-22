<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input)
    $input = $_POST;

$planId = $input['plan_id'] ?? null;
$osImageId = $input['os_image_id'] ?? null;
$appId = $input['application_id'] ?? null;
$serverName = $input['name_server'] ?? null;
$password = $input['password'] ?? null;
$payMethod = $input['payment_method'] ?? 'balance';
$duration = (int) ($input['duration'] ?? 0);

if (!$planId || (!$osImageId && !$appId) || !$serverName || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields (plan, OS/App, hostname, password)']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9-]{7,}$/', $serverName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid hostname. Minimum 7 chars, alphanumeric and hyphens only.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password too short. Minimum 6 chars.']);
    exit;
}

try {
    $planRepo = new PlanRepository();
    $orderRepo = new OrderRepository();

    $plan = $planRepo->getById($planId);
    if (!$plan)
        throw new Exception("Plan not found");

    if ($osImageId) {
        $allowedOsImages = is_string($plan['available_os_image_versions'] ?? '[]')
            ? json_decode($plan['available_os_image_versions'], true)
            : ($plan['available_os_image_versions'] ?? []);
        if (!is_array($allowedOsImages))
            $allowedOsImages = [];

        $osValid = false;
        foreach ($allowedOsImages as $img) {
            if (strval($img['id']) === strval($osImageId)) {
                $osValid = true;
                break;
            }
        }
        if (!$osValid)
            throw new Exception("Invalid OS Image for this plan or OS not allowed.");
    }

    $userId = authenticate_user();
    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);

    $price = (float) $plan['price'];
    if ($user && !empty($user['referred_by'])) {
        $price = max(0, $price - 1.00);
    }

    $shortTermMultiplier = getShortTermMultiplier($plan['fees'] ?? [], $duration);
    $orderDetails   = calculateOrderDetails($duration, $price * $shortTermMultiplier, null);
    $setupFeesTotal = getSetupFeesTotal($plan['fees'] ?? []);
    $recurring      = getRecurringFees($plan['fees'] ?? []);
    $baseAmount     = (float) $orderDetails['total_amount'] + $setupFeesTotal;
    $totalAmount    = round($baseAmount * (1 + $recurring['pct'] / 100.0) + $recurring['fixed'], 2);

    // ── CRYPTO: create PENDING order and return order_id for invoice step ──
    if ($payMethod === 'crypto') {
        $localOrderId = $orderRepo->create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'image_os_id' => ($appId !== null) ? null : $osImageId,
            'application_id' => $appId,
            'duration' => $duration,
            'total_amount' => $totalAmount,
            'currency' => 'USD',
            'status' => 'PENDING',
            'type' => 'vps',
        ]);

        echo json_encode([
            'success' => true,
            'requires_invoice' => true,
            'data' => [
                'order_id' => $localOrderId,
                'total_amount' => $totalAmount,
                'plan_name' => $plan['name'],
            ],
        ]);
        exit;
    }

    // ── BALANCE: deduct then provision via shared provisionVps() ──
    deductBalance($userId, $totalAmount, "Purchase of VPS Plan {$plan['name']} for {$duration}h");

    $createdVpsId = provisionVps([
        'type' => 'vps_purchase',
        'user_id' => $userId,
        'plan_id' => $planId,
        'os_image_id' => $osImageId,
        'application_id' => $appId,
        'duration' => $duration,
        'name_server' => $serverName,
        'password' => $password,
        'plan_name' => $plan['name'],
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'order_id' => null,
            'vps_id' => $createdVpsId,
            'message' => 'VPS provisioned.',
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
