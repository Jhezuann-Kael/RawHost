<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../models/User.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '-04:00'");

    if ($method === 'GET') {
        $page           = isset($_GET['page'])            ? (int) $_GET['page']    : 1;
        $limit          = isset($_GET['limit'])           ? (int) $_GET['limit']   : 10;
        $search         = isset($_GET['search'])          ? $_GET['search']        : '';
        $telegramFilter = isset($_GET['telegram_filter']) ? $_GET['telegram_filter'] : 'all';
        $offset         = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        if (!empty($search)) {
            $where[]          = "(username LIKE :search OR email LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($telegramFilter === 'with') {
            $where[] = "telegram_id IS NOT NULL";
        } elseif ($telegramFilter === 'without') {
            $where[] = "telegram_id IS NULL";
        }

        $whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        // Count total with telegram (always)
        $stmtTg = $pdo->query("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL");
        $totalTelegram = (int) $stmtTg->fetchColumn();

        // Count for pagination
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM users$whereSql");
        foreach ($params as $k => $v) $stmtCount->bindValue($k, $v);
        $stmtCount->execute();
        $totalUsers = $stmtCount->fetchColumn();
        $totalPages  = ceil($totalUsers / $limit);

        // Main query
        $sql  = "SELECT id, username, email, balance, is_superuser, telegram_id, tg_username, created_at FROM users$whereSql ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'users'          => $users,
            'total_telegram' => $totalTelegram,
            'pagination'     => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_users'  => $totalUsers,
                'limit'        => $limit
            ]
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
