<?php

header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

session_start();
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $repo = new TransactionRepository();

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $transactions = $repo->getByUserId($_SESSION['user_id'], $limit, $offset);
    $total = $repo->countByUserId($_SESSION['user_id']);
    $totalPages = ceil($total / $limit);

    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'pagination' => [
            'total' => $total,
            'pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}