<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../repositories/TicketRepository.php';
require_once __DIR__ . '/../../repositories/TicketMessageRepository.php';

session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_superuser'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ticket_id is required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Delete uploaded images for all messages in this ticket
    $stmt = $conn->prepare("SELECT image_path FROM ticket_messages WHERE ticket_id = :id AND image_path IS NOT NULL");
    $stmt->execute([':id' => $ticketId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
        $file = __DIR__ . '/../../dashboard/' . $path;
        if (file_exists($file)) unlink($file);
    }

    // Delete messages then ticket (FK cascade may handle it, but explicit is safer)
    $conn->prepare("DELETE FROM ticket_messages WHERE ticket_id = :id")->execute([':id' => $ticketId]);
    $conn->prepare("DELETE FROM tickets WHERE id = :id")->execute([':id' => $ticketId]);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
