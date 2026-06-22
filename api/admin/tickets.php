<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TicketRepository.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page   = (int) ($_GET['page']  ?? 1);
$limit  = (int) ($_GET['limit'] ?? 10);
$search = $_GET['search'] ?? null;
$status = ($_GET['status'] ?? '') !== '' ? $_GET['status'] : null;

$repo = new TicketRepository();
$offset = ($page - 1) * $limit;

try {
    $tickets = $repo->getAllPaginated($limit, $offset, $search, $status);
    $total = $repo->countAll($search, $status);
    $pages = ceil($total / $limit);

    // Stats
    // We could add simple stats here like Open Tickets Count

    echo json_encode([
        'success'    => true,
        'data'       => $tickets,
        'rating_stats' => $repo->getRatingStats(),
        'pagination' => [
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $page,
            'limit'        => $limit
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
