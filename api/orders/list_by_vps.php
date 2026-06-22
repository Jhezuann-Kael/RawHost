<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$vpsId = (int)($_GET['vps_id'] ?? 0);
$page  = max(1, (int)($_GET['page']  ?? 1));

if (!$vpsId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing vps_id']);
    exit;
}

$vpsRepo = new VpsRepository();
$vps     = $vpsRepo->getById($vpsId);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderRepo = new OrderRepository();
$result    = $orderRepo->getByVpsIdPaginated($vpsId, $page);

echo json_encode(['success' => true] + $result);
