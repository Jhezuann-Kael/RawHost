<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$userId      = $_SESSION['user_id'];
$isSuperuser = $_SESSION['is_superuser'] ?? false;

if (!isset($input['ticket_id']) || (!$isSuperuser && !isset($input['rating']))) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing ticket_id or rating']);
    exit;
}

$ticketId = $input['ticket_id'];
$rating   = $input['rating'] ?? null;

// Validate rating for regular users; admins close without a rating
$allowedRatings = ['VERY_GOOD', 'GOOD', 'NOT_GOOD'];
if (!$isSuperuser && !in_array($rating, $allowedRatings)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid rating']);
    exit;
}

// Admin always closes with NULL rating (no customer satisfaction score)
if ($isSuperuser) {
    $rating = null;
}

try {
    $ticketRepo = new TicketRepository();

    // Check ownership
    $ticket = $ticketRepo->getById($ticketId);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    if ($ticket['user_id'] != $userId && !$isSuperuser) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    // Close ticket
    $success = $ticketRepo->closeTicket($ticketId, $rating);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Ticket closed successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
