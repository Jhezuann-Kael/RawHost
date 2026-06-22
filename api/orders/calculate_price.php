<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../repositories/AddonRepository.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$serverId = $_GET['server_id'] ?? null;
$duration = (int) ($_GET['duration'] ?? 0);

if (!$serverId || $duration <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vps = $vpsRepo->getById($serverId);

    if (!$vps) {
        throw new Exception("Server not found");
    }

    // Verify Ownership
    if ($vps['user_id'] != $userId && !($_SESSION['is_superuser'] ?? false)) {
        throw new Exception("Unauthorized access to this server");
    }

    // Get plan price
    $planPrice = (float) ($vps['plan_price'] ?? 0);

    // Apply Referral Discount (if owner validation passed above)
    // We already checked ownership or admin.
    // If it's the owner, check if they are referred.
    // Optimization: we could check session user, assuming they are the owner.
    require_once __DIR__ . '/../../repositories/UserRepository.php';
    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);

    // If user is owner (verified above) and has referrer
    if ($user && !empty($user['referred_by'])) {
        $planPrice = max(0, $planPrice - 1.00);
    }

    // Get active addons with detail
    $addonRepo    = new AddonRepository();
    $activeAddons = $addonRepo->getActiveByVpsId($serverId);
    $addonsPrice  = array_sum(array_column($activeAddons, 'price'));

    // Build breakdown: group by type
    $addonsBreakdown = [];
    foreach ($activeAddons as $a) {
        $type = $a['type'] ?? 'ADDON';
        if (!isset($addonsBreakdown[$type])) {
            $addonsBreakdown[$type] = ['count' => 0, 'unit_price' => (float)$a['price'], 'total' => 0];
        }
        $addonsBreakdown[$type]['count']++;
        $addonsBreakdown[$type]['total'] = round($addonsBreakdown[$type]['total'] + (float)$a['price'], 2);
    }

    // Short-term surcharge
    $planRepo    = new PlanRepository();
    $planData    = $planRepo->getById($vps['plan_id'] ?? 0);
    $stMultiplier = getShortTermMultiplier($planData['fees'] ?? [], $duration);
    $stFeePct    = 0;
    if ($stMultiplier > 1.0 && $planData) {
        foreach ($planData['fees'] ?? [] as $f) {
            if (($f['billing_type'] ?? '') === 'short_term' && ($f['type'] ?? '') === 'percentage') {
                $stFeePct = (float) $f['value'];
                break;
            }
        }
    }

    // Calculate totals
    $totalMonthlyPrice    = ($planPrice + $addonsPrice) * $stMultiplier;
    $totalMonthlyNoFee    = $planPrice + $addonsPrice;
    $hourlyPrice          = ($totalMonthlyPrice > 0) ? ($totalMonthlyPrice / 720) : 0;
    $baseAmount           = $hourlyPrice * $duration;
    $feeAmount            = round($baseAmount - (($totalMonthlyNoFee / 720) * $duration), 2);

    // Recurring fees applied on top of base amount
    $recurring            = getRecurringFees($planData['fees'] ?? []);
    $recurringPctAmt      = round($baseAmount * ($recurring['pct'] / 100.0), 2);
    $recurringFixedAmt    = round($recurring['fixed'], 2);
    $totalAmount          = $baseAmount + $recurringPctAmt + $recurringFixedAmt;

    echo json_encode([
        'success' => true,
        'data' => [
            'plan_price'           => round($planPrice, 2),
            'addons_price'         => round($addonsPrice, 2),
            'total_monthly_price'  => round($totalMonthlyPrice, 2),
            'hourly_rate'          => round($hourlyPrice, 4),
            'duration'             => $duration,
            'total_amount'         => round($totalAmount, 2),
            'short_term_fee_pct'   => $stFeePct,
            'short_term_fee_amt'   => $feeAmount > 0 ? $feeAmount : 0,
            'recurring_fee_pct'    => $recurring['pct'],
            'recurring_fee_amt'    => $recurringPctAmt + $recurringFixedAmt > 0 ? round($recurringPctAmt + $recurringFixedAmt, 2) : 0,
            'addons_breakdown'     => array_values(array_map(function($type, $d) {
                return ['type' => $type, 'count' => $d['count'], 'unit_price' => $d['unit_price'], 'total' => $d['total']];
            }, array_keys($addonsBreakdown), $addonsBreakdown)),
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
