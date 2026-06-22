<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TicketRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $isSuperuser = $_SESSION['is_superuser'] ?? false;
    $ticketRepo = new TicketRepository();


    $tickets = $ticketRepo->getByUserId($userId);

    echo json_encode(['status' => 'success', 'tickets' => $tickets]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
