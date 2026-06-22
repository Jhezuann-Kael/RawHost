<?php
header("Content-Type: application/json");
require_once '../../api/config.php';
require_once '../../repositories/NewsRepository.php';
session_start();

// Admin security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$newsRepo = new NewsRepository();

try {
    switch ($method) {
        case 'GET':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $onlyActive = isset($_GET['only_active']) && $_GET['only_active'] === 'true';

            $news = $newsRepo->getAll($page, $limit, $startDate, $endDate, $onlyActive);
            $totalItems = $newsRepo->countAll($startDate, $endDate, $onlyActive);
            $totalPages = ceil($totalItems / $limit);

            echo json_encode([
                'success' => true,
                'data' => $news,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'limit' => $limit
                ]
            ]);
            break;

        case 'POST':
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (empty($data['title']) || empty($data['content'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and content are required.']);
                exit;
            }

            $newsId = $newsRepo->create($data);
            if ($newsId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'News created successfully',
                    'id' => $newsId
                ]);
            } else {
                throw new Exception("Failed to create news.");
            }
            break;

        case 'PUT':
            // PHP doesn't populate $_PUT so we read from input
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $id = isset($_GET['id']) ? (int)$_GET['id'] : ($data['id'] ?? null);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'News ID is required for updates.']);
                exit;
            }

            if ($newsRepo->update($id, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'News updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No changes made or news not found.'
                ]);
            }
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'News ID is required for deletion.']);
                exit;
            }

            if ($newsRepo->delete($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'News deleted successfully'
                ]);
            } else {
                throw new Exception("Failed to delete news entry.");
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
