<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/NewsRepository.php';

header('Content-Type: application/json');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$id) {
        throw new Exception("ID de noticia no proporcionado");
    }

    $newsRepo = new NewsRepository();
    $newsRepo->incrementViews($id);
    $news = $newsRepo->getById($id);

    if (!$news || $news['is_active'] == 0) {
        http_response_code(404);
        throw new Exception("Noticia no encontrada o no disponible");
    }

    echo json_encode([
        'success' => true,
        'data' => $news
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
