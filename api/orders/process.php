<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';
require_once __DIR__ . '/../../repositories/AddonRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';

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

$input     = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$serverId  = $input['server_id']      ?? null;
$action    = $input['action']         ?? null; // 'renew' | 'upgrade'
$value     = $input['value']          ?? null; // duration hours | plan_id
$payMethod = $input['payment_method'] ?? 'balance'; // 'balance' | 'crypto'

if (!$serverId || !$action || !$value) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vps     = $vpsRepo->getById($serverId);

    if (!$vps) throw new Exception("Server not found");

    if ($vps['user_id'] != $userId && !($_SESSION['is_superuser'] ?? false)) {
        throw new Exception("Unauthorized access to this server");
    }

    $externalVpsId = $vps['external_id'];
    if (!$externalVpsId) throw new Exception("External Server ID not found. Cannot process order.");

    $addonRepo    = new AddonRepository();
    $addonsPrice  = $addonRepo->getCumulativePriceByVpsId($serverId);
    $monthlyPrice = (float) ($vps['plan_price'] ?? 0);
    $orderPlanId  = $vps['plan_id'];
    $orderDuration = 0;
    $totalAmount   = 0;
    $newPlan       = null;

    if ($action === 'renew') {
        $orderDuration = intval($value);
        $planRepo      = new PlanRepository();
        $planData      = $planRepo->getById($orderPlanId);
        $stMultiplier  = getShortTermMultiplier($planData['fees'] ?? [], $orderDuration);
        $totalMonthly  = ($monthlyPrice + $addonsPrice) * $stMultiplier;
        $hourlyPrice   = ($totalMonthly > 0) ? ($totalMonthly / 720) : 0;
        $baseAmount    = round($hourlyPrice * $orderDuration, 2);
        $recurring     = getRecurringFees($planData['fees'] ?? []);
        $totalAmount   = round($baseAmount * (1 + $recurring['pct'] / 100.0) + $recurring['fixed'], 2);

    } elseif ($action === 'upgrade') {
        $planRepo = new PlanRepository();
        $newPlan  = $planRepo->getById($value);
        if (!$newPlan) throw new Exception("Target plan not found");

        $oldPlanPrice = (float) ($vps['plan_price'] ?? 0);
        $newPlanPrice = (float) ($newPlan['price'] ?? 0);

        if ($newPlanPrice <= $oldPlanPrice) {
            throw new Exception("Cannot downgrade. Only upgrades are allowed.");
        }

        $orderPlanId   = $newPlan['id'];
        $currentExpiry = $vps['expires_at'] ? strtotime($vps['expires_at']) : time();
        if ($currentExpiry < time()) throw new Exception("Cannot upgrade an expired server. Please renew first.");

        $remainingHours = ceil(($currentExpiry - time()) / 3600);
        $orderDuration  = $remainingHours;

        $oldHourly   = (($oldPlanPrice + $addonsPrice) / 720);
        $newHourly   = (($newPlanPrice + $addonsPrice) / 720);
        $totalAmount = round(($newHourly * $remainingHours) - ($oldHourly * $remainingHours), 2);

    } else {
        throw new Exception("Invalid action type");
    }

    // Apply referral discount (first order only)
    $userRepo  = new UserRepository();
    $orderRepo = new OrderRepository();
    $user      = $userRepo->getById($userId);
    if ($user && !empty($user['referred_by']) && $orderRepo->countCompletedOrders($userId) == 0) {
        $monthlyPrice = max(0, $monthlyPrice - 1.00);
        // Recalculate after discount (preserve short-term and recurring fees)
        if ($action === 'renew') {
            $totalMonthly  = ($monthlyPrice + $addonsPrice) * $stMultiplier;
            $baseAmount    = round(($totalMonthly / 720) * $orderDuration, 2);
            $totalAmount   = round($baseAmount * (1 + $recurring['pct'] / 100.0) + $recurring['fixed'], 2);
        }
    }

    // ── CRYPTO: create PENDING order, let create_action_invoice handle the rest ──
    if ($payMethod === 'crypto') {
        $localOrderId = $orderRepo->create([
            'user_id'      => $userId,
            'vps_id'       => $serverId,
            'plan_id'      => $orderPlanId,
            'image_os_id'  => null,
            'duration'     => $orderDuration,
            'total_amount' => number_format($totalAmount, 2, '.', ''),
            'currency'     => 'USD',
            'status'       => 'PENDING',
            'type'         => 'vps',
        ]);

        echo json_encode([
            'success'          => true,
            'requires_invoice' => true,
            'data' => [
                'order_id'     => $localOrderId,
                'total_amount' => $totalAmount,
                'action'       => $action,
            ],
        ]);
        exit;
    }

    // ── BALANCE: full flow ──
    if ($totalAmount > 0) {
        $user = $userRepo->getById($userId);
        if ((float) $user['balance'] < $totalAmount) {
            throw new Exception("Insufficient balance. Please deposit funds first.");
        }

        $movRepo     = new MovementRepository();
        $description = ($action === 'renew')
            ? "Renewal of server {$vps['name']} for {$orderDuration} hours"
            : "Upgrade of server {$vps['name']} to new plan for {$orderDuration} hours";

        $movRepo->create([
            'user_id'     => $userId,
            'type'        => 'OUT',
            'amount'      => $totalAmount,
            'description' => $description,
        ]);
    }

    $postData = ['server_id' => $externalVpsId, 'type_payment' => 'balance'];

    if ($action === 'renew') {
        $postData['rental_duration'] = $orderDuration;

        try { $vpsRepo->updateDuration($serverId, $orderDuration); } catch (Exception $e) {}

        $currentExpiry      = $vps['expires_at'] ? strtotime($vps['expires_at']) : time();
        if ($currentExpiry < time()) $currentExpiry = time();
        $newExpiryDate      = date('Y-m-d H:i:s', $currentExpiry + ($orderDuration * 3600));
        $vpsRepo->updateExpiration($serverId, $newExpiryDate);

    } elseif ($action === 'upgrade' && $newPlan) {
        $postData['plan_id'] = $newPlan['external_id'];
        try { $vpsRepo->updatePlan($serverId, $newPlan['id']); } catch (Exception $e) {}
    }

    $apiService = new ExternalApiService();
    $apiResp    = $apiService->createOrder($postData);

    $orderRepo->create([
        'user_id'      => $userId,
        'vps_id'       => $serverId,
        'plan_id'      => $orderPlanId,
        'image_os_id'  => null,
        'duration'     => $orderDuration,
        'total_amount' => number_format($totalAmount, 2, '.', ''),
        'currency'     => 'USD',
        'status'       => 'COMPLETED',
        'type'         => 'vps',
    ]);

    echo json_encode([
        'success'           => true,
        'message'           => 'Order processed successfully',
        'external_response' => $apiResp,
    ]);

    $msgTitle = ($action === 'renew') ? "🔄 <b>Renovación de VPS</b>" : "⬆️ <b>Upgrade de VPS</b>";
    $msg = "$msgTitle\n\n👤 <b>Usuario ID:</b> $userId\n🖥️ <b>VPS:</b> " . ($vps['name'] ?? 'Unknown') .
        "\n💰 <b>Monto:</b> $" . number_format($totalAmount, 2);
    if ($action === 'renew') $msg .= "\n📅 <b>Duración:</b> {$orderDuration} horas";
    sendTelegramNotification($msg);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
