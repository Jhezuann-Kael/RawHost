<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/BotAnalyticsRepository.php';

session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $repo   = new BotAnalyticsRepository();
    $action = $_GET['action'] ?? 'list';

    if ($action === 'stats') {
        echo json_encode(['success' => true, 'stats' => $repo->getSummaryStats()]);
        exit;
    }

    if ($action === 'events') {
        $tgId    = trim($_GET['tg_id'] ?? '');
        $page    = max(1, (int) ($_GET['page']  ?? 1));
        $perPage = max(1, (int) ($_GET['limit'] ?? 30));

        if ($tgId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'tg_id is required']);
            exit;
        }

        $result = $repo->getEventsByUser($tgId, $page, $perPage);
        echo json_encode(['success' => true] + $result);
        exit;
    }

    // Default: paginated list by user
    $page    = max(1, (int) ($_GET['page']   ?? 1));
    $perPage = max(1, (int) ($_GET['limit']  ?? 20));
    $search  = trim($_GET['search'] ?? '');

    $result = $repo->getUsageByUser($page, $perPage, $search);
    echo json_encode(['success' => true] + $result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
