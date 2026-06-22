<?php
header("Content-Type: application/json");
require_once '../../api/config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '-04:00'");

    // 1. Pending Transactions (status = 'PENDING' or 'Waiting')
    // Assuming 'PENDING' based on TransactionRepository
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'PENDING' OR status = 'Waiting'");
    $pendingTransactions = $stmt->fetchColumn();

    // 2. Pending Tickets (status != 'CLOSED')
    // Assuming 'OPEN', 'ANSWERED' are pending attention? Or just 'OPEN'? user usually means "to be answered".
    // Let's count 'OPEN' and 'CUSTOMER-REPLY' if that exists, or just ALL NON-CLOSED.
    // Based on previous code badges: OPEN, ANSWERED, CLOSED. 'ANSWERED' means answered by admin.
    // So pending for admin is usually 'OPEN' or 'CUSTOMER-REPLY'.
    // Let's count 'OPEN'.
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'OPEN'");
    $pendingTickets = $stmt->fetchColumn();

    // 3. Active Clients
    // Definition of 'Active Client': User with at least one active VPS? Or just User account?
    // "Total de clientes activos" usually means users. Let's count total users for now, or users with balance > 0/active vps.
    // Let's count ALL users as "clients".
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $activeClients = $stmt->fetchColumn();

    // 4. Billing (from Transactions status = 'COMPLETED')
    // Today
    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'COMPLETED' AND DATE(created_at) = CURDATE()");
    $billingToday = $stmt->fetchColumn() ?: 0;

    // This Month
    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'COMPLETED' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $billingMonth = $stmt->fetchColumn() ?: 0;

    // This Year
    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'COMPLETED' AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $billingYear = $stmt->fetchColumn() ?: 0;

    // Total Ever
    $stmt = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'COMPLETED'");
    $billingTotal = $stmt->fetchColumn() ?: 0;

    echo json_encode([
        'success' => true,
        'stats' => [
            'pending_transactions' => (int) $pendingTransactions,
            'pending_tickets' => (int) $pendingTickets, // Tickets "OPEN"
            'active_clients' => (int) $activeClients
        ],
        'billing' => [
            'today' => (float) $billingToday,
            'month' => (float) $billingMonth,
            'year' => (float) $billingYear,
            'total' => (float) $billingTotal
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
