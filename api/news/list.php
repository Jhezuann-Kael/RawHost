<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/NewsRepository.php';

header('Content-Type: application/json');

try {
    $newsRepo = new NewsRepository();
    
    // Pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    
    // Get active news only for users
    $newsList = $newsRepo->getAll($page, $limit, null, null, true);
    $totalNews = $newsRepo->countAll(null, null, true);
    
    $totalPages = ceil($totalNews / $limit);

    echo json_encode([
        'success' => true,
        'data' => $newsList,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalNews,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
