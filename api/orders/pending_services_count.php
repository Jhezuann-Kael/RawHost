<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$repo  = new OrderRepository();
$count = $repo->countPendingServices((int) $_SESSION['user_id']);

echo json_encode(['success' => true, 'count' => $count]);
