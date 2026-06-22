<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

session_start();

// Admin check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || $_SESSION['is_superuser'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Pagination
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Search
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $where = "WHERE u.referred_by IS NOT NULL";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (u.username LIKE :search OR u.email LIKE :search OR r.username LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Count
    $countQuery = "SELECT COUNT(*) FROM users u LEFT JOIN users r ON u.referred_by = r.id $where";
    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->execute($params);
    $totalUsers = $stmtCount->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);

    // Data
    $query = "SELECT u.id, u.username, u.email, u.created_at, u.balance,
                     r.username as referrer_username, r.id as referrer_id, r.referral_code,
                     (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status = 'COMPLETED') as completed_orders_count
              FROM users u 
              LEFT JOIN users r ON u.referred_by = r.id 
              $where
              ORDER BY u.created_at DESC 
              LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get count of VALID referrals (purchased)
    $validCountQuery = "SELECT COUNT(DISTINCT u.id) 
                        FROM users u 
                        INNER JOIN orders o ON u.id = o.user_id 
                        WHERE u.referred_by IS NOT NULL AND o.status = 'COMPLETED'";
    // Note: If search is applied, we might want to filter valid count too, but typically "valid referrals" implies total system-wide or per-referrer. 
    // The request implies we need to see number of valid users. Let's send the total valid count for context if needed, 
    // or just rely on the table data 'completed_orders_count' to show status on frontend.

    // Actually, let's just send the table data with the new column. The frontend can verify "completed_orders_count > 0" -> Valid.

    echo json_encode([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
