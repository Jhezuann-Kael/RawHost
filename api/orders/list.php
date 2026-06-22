<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';

// Start session to get user
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId    = $_SESSION['user_id'];
    $typeFilter = isset($_GET['type']) ? trim($_GET['type']) : null;
    $page       = max(1, (int) ($_GET['page']  ?? 1));
    $perPage    = max(1, (int) ($_GET['limit'] ?? 0)); // 0 = no pagination

    $orderRepo = new OrderRepository();
    $planRepo  = new PlanRepository();

    if ($typeFilter === 'managed_service') {
        if ($perPage > 0) {
            $result = $orderRepo->getServicesByUserPaginated($userId, $page, $perPage);
            $orders     = $result['orders'];
            $pagination = [
                'total'        => $result['total'],
                'pages'        => $result['pages'],
                'current_page' => $result['current_page'],
                'limit'        => $result['limit'],
            ];
        } else {
            $orders     = $orderRepo->getServicesByUser($userId);
            $pagination = null;
        }
    } else {
        $orders     = $orderRepo->getByUser($userId);
        $pagination = null;
    }

    // Enrich orders with additional information
    $enrichedOrders = [];

    foreach ($orders as $order) {
        $enriched = $order;

        // Add plan name if available
        if ($order['plan_id']) {
            $plan = $planRepo->getById($order['plan_id']);
            $enriched['plan_name'] = $plan ? $plan['name'] : 'N/A';
        } else {
            $enriched['plan_name'] = 'N/A';
        }

        // Determine order type label
        $orderType = $order['type'] ?? 'vps';
        switch ($orderType) {
            case 'vps':
                $enriched['type_label'] = 'VPS Server';
                break;
            case 'domain':
                $enriched['type_label'] = 'Domain';
                break;
            case 'renewal':
                $enriched['type_label'] = 'Renewal';
                break;
            case 'upgrade':
                $enriched['type_label'] = 'Upgrade';
                break;
            case 'managed_service':
                $enriched['type_label'] = 'Managed Service';
                break;
            default:
                $enriched['type_label'] = ucfirst($orderType);
        }

        // Add domain name if it's a domain order
        if ($orderType === 'domain' && $order['domain_name']) {
            $enriched['item_name'] = $order['domain_name'];
        } elseif ($enriched['plan_name'] !== 'N/A') {
            $enriched['item_name'] = $enriched['plan_name'];
        } else {
            $enriched['item_name'] = 'Order #' . $order['id'];
        }

        // Format amount
        $enriched['formatted_amount'] = '$' . number_format((float) $order['total_amount'], 2);

        // Format dates
        $enriched['formatted_date'] = date('Y-m-d H:i', strtotime($order['created_at']));

        // Status label
        switch (strtoupper($order['status'])) {
            case 'COMPLETED':
                $enriched['status_label'] = 'Completed';
                break;
            case 'PENDING':
                $enriched['status_label'] = 'Pending';
                break;
            case 'FAILED':
                $enriched['status_label'] = 'Failed';
                break;
            case 'CANCELLED':
                $enriched['status_label'] = 'Cancelled';
                break;
            default:
                $enriched['status_label'] = ucfirst(strtolower($order['status']));
        }

        $enrichedOrders[] = $enriched;
    }

    $response = [
        'success' => true,
        'data'    => $enrichedOrders,
        'count'   => count($enrichedOrders),
    ];
    if ($pagination) $response['pagination'] = $pagination;

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching orders: ' . $e->getMessage()
    ]);
}
