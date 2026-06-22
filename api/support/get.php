<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TicketRepository.php';
require_once __DIR__ . '/../../repositories/TicketMessageRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID required']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $ticketRepo = new TicketRepository();
    $messageRepo = new TicketMessageRepository();

    $ticket = $ticketRepo->getById($id);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    // Security Check: Only owner or admin
    $isSuperuser = $_SESSION['is_superuser'] ?? false;

    if ($ticket['user_id'] != $userId && !$isSuperuser) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    $messages = $messageRepo->getByTicketId($id);

    // Mark admin messages as read when the ticket owner opens the chat
    if (!$isSuperuser) {
        $messageRepo->markAdminMessagesAsRead((int) $id);
    }

    echo json_encode([
        'status' => 'success',
        'ticket' => $ticket,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
