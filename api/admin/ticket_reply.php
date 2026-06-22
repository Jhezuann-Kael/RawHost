<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
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

$ticketId = $_POST['ticket_id'] ?? null;
$message  = trim($_POST['message'] ?? '');
$image    = $_FILES['image'] ?? null;
$hasImage = $image && $image['error'] === UPLOAD_ERR_OK;

if (!$ticketId || ($message === '' && !$hasImage)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ticket ID and a message or image are required']);
    exit;
}

try {
    $userId      = $_SESSION['user_id'];
    $ticketRepo  = new TicketRepository();
    $messageRepo = new TicketMessageRepository();

    $ticket = $ticketRepo->getById($ticketId);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    // Handle image upload
    $imagePath = null;
    if ($image && $image['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($image['type'], $allowedTypes)) {
            throw new Exception("Invalid image type. Allowed: JPG, PNG, GIF, WEBP");
        }

        $uploadDir = __DIR__ . '/../../dashboard/uploads/support';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($image['name']);
        if (move_uploaded_file($image['tmp_name'], $uploadDir . '/' . $fileName)) {
            $imagePath = 'uploads/support/' . $fileName;
        } else {
            throw new Exception("Failed to upload image");
        }
    }

    // Create message
    $messageRepo->create([
        'ticket_id'  => $ticketId,
        'user_id'    => $userId,
        'message'    => $message,
        'image_path' => $imagePath
    ]);

    // Admin reply always sets ticket to ANSWERED
    $ticketRepo->updateStatus($ticketId, 'ANSWERED');

    // No Telegram notification for admin replies

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
