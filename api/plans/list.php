<?php
header('Content-Type: application/json');

// Adjust paths relative to api/plans/
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';

// Check if user is logged in to apply discount
session_start();

try {
    $planRepo = new PlanRepository();
    $plans = $planRepo->getAll();

    $referralDiscount = 0;
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../../repositories/UserRepository.php';
        require_once __DIR__ . '/../../repositories/OrderRepository.php';

        $userRepo = new UserRepository();
        $orderRepo = new OrderRepository();

        $user = $userRepo->getById($_SESSION['user_id']);

        // Check if user has referred_by AND has no completed orders
        if ($user && !empty($user['referred_by'])) {
            $completedOrders = $orderRepo->countCompletedOrders($_SESSION['user_id']);
            if ($completedOrders == 0) {
                $referralDiscount = 1.00;
            }
        }
    }

    // Apply Discount
    if ($referralDiscount > 0) {
        foreach ($plans as &$plan) {
            $plan['original_price'] = $plan['price'];
            $plan['price'] = max(0, floatval($plan['price']) - $referralDiscount);
            $plan['discount_applied'] = true;
            $plan['discount_amount'] = $referralDiscount;
        }
    }

    echo json_encode(['success' => true, 'data' => $plans]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
