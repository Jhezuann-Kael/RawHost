<?php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$messageId = $input['message_id'] ?? null;

if (!$messageId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'message_id is required']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $isSuperuser = $_SESSION['is_superuser'] ?? false;
    $messageRepo = new TicketMessageRepository();
    $ticketRepo = new TicketRepository();

    $msg = $messageRepo->getById($messageId);
    if (!$msg) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Message not found']);
        exit;
    }

    // Owner or admin can delete
    if ($msg['user_id'] != $userId && !$isSuperuser) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    // Verify ticket is not closed (only admins can delete in closed tickets)
    $ticket = $ticketRepo->getById($msg['ticket_id']);
    if ($ticket && $ticket['status'] === 'CLOSED' && !$isSuperuser) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete messages in a closed ticket']);
        exit;
    }

    // Delete uploaded image file if present
    if (!empty($msg['image_path'])) {
        $filePath = __DIR__ . '/../../dashboard/' . $msg['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $messageRepo->delete($messageId);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
