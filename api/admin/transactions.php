<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$type   = ($_GET['type'] ?? '') !== '' ? $_GET['type'] : null;

$repo = new TransactionRepository();
$offset = ($page - 1) * $limit;

try {
    $transactions = $repo->getAllPaginated($limit, $offset, $search, $status, $type);
    $total = $repo->countAll($search, $status, $type);
    $pages = ceil($total / $limit);

    $stats = $repo->getFinancialStats();

    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'pagination' => [
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'limit' => $limit
        ],
        'stats' => $stats
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
