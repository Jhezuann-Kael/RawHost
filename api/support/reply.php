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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$ticketId = $_POST['ticket_id'] ?? null;
$message = $_POST['message'] ?? '';
$image = $_FILES['image'] ?? null;

if (!$ticketId || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ticket ID and Message are required']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $ticketRepo = new TicketRepository();
    $messageRepo = new TicketMessageRepository();

    // 1. Verify Ticket Ownership (user must own the ticket)
    $ticket = $ticketRepo->getById($ticketId);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }
    if ($ticket['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }

    // 2. Handle Image Upload
    $imagePath = null;
    if ($image && $image['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($image['type'], $allowedTypes)) {
            throw new Exception("Invalid image type. Allowed: JPG, PNG, GIF");
        }

        $uploadDir = __DIR__ . '/../../dashboard/uploads/support';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($image['name']);
        $targetPath = $uploadDir . '/' . $fileName;

        if (move_uploaded_file($image['tmp_name'], $targetPath)) {
            // Rel path for frontend
            $imagePath = 'uploads/support/' . $fileName;
        } else {
            throw new Exception("Failed to upload image");
        }
    }

    // 3. Create Message
    $messageRepo->create([
        'ticket_id' => $ticketId,
        'user_id' => $userId,
        'message' => $message,
        'image_path' => $imagePath
    ]);

    // 4. Update Ticket Status — user reply never sets ANSWERED
    if ($ticket['status'] === 'ANSWERED' || $ticket['status'] === 'CLOSED') {
        $ticketRepo->updateStatus($ticketId, 'OPEN');
    } else {
        $ticketRepo->updateStatus($ticketId, $ticket['status']);
    }

    // 5. Telegram notification — solo mensajes del cliente (no del admin)
    $username = $_SESSION['username'] ?? ('User #' . $userId);
    $preview = strlen($message) > 200 ? substr($message, 0, 200) . '...' : $message;
    notifySupportTelegram(
        "💬 <b>Nueva Respuesta - Ticket #$ticketId</b>\n" .
        "👤 <b>Usuario:</b> " . htmlspecialchars($username) . "\n" .
        "📋 <b>Asunto:</b> " . htmlspecialchars($ticket['subject']) . "\n\n" .
        "💬 <b>Mensaje:</b>\n" . htmlspecialchars($preview)
    );

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
