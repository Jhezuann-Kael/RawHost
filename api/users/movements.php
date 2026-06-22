<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $movementRepo = new MovementRepository();

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $movements = $movementRepo->getByUserId($_SESSION['user_id'], $limit, $offset);
    $total = $movementRepo->countByUserId($_SESSION['user_id']);
    $totalPages = ceil($total / $limit);

    echo json_encode([
        'success' => true,
        'data' => $movements,
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
