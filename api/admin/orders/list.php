<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../repositories/OrderRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $repo = new OrderRepository();

    $page   = max(1, (int)($_GET['page']   ?? 1));
    $limit  = max(1, min(50, (int)($_GET['limit']  ?? 15)));
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $type   = $_GET['type']   ?? '';

    $result     = $repo->getAdminListPaginated($page, $limit, $search, $status, $type);
    $stats      = $repo->getAdminStats();
    $engagement = $repo->getEngagementStats();

    echo json_encode([
        'orders'     => $result['orders'],
        'stats'      => $stats,
        'engagement' => $engagement,
        'pagination' => $result['pagination'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
