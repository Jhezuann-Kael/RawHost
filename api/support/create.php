<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Maybe invalid JSON?
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$subject = $input['subject'] ?? '';
$category = $input['category'] ?? 'OTHER';
$message = $input['message'] ?? '';
$priority = $input['priority'] ?? 'MEDIUM';

if (empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Subject and Message are required']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    require_once __DIR__ . '/../../repositories/UserRepository.php';
    $userRepo = new UserRepository();
    $currentUser = $userRepo->getById($userId);
    if ($currentUser && !empty($currentUser['support_blocked'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Tu cuenta no tiene permitido contactar soporte.']);
        exit;
    }

    $ticketRepo = new TicketRepository();
    $messageRepo = new TicketMessageRepository();

    // Ideally wrap in transaction
    // 1. Create Ticket
    $ticketId = $ticketRepo->create([
        'user_id' => $userId,
        'subject' => $subject,
        'category' => $category,
        'priority' => $priority
    ]);

    // 2. Create Initial Message
    $messageRepo->create([
        'ticket_id' => $ticketId,
        'user_id' => $userId,
        'message' => $message,
        'image_path' => null
    ]);

    // 3. Telegram notification
    $username = $_SESSION['username'] ?? ('User #' . $userId);
    $preview  = strlen($message) > 200 ? substr($message, 0, 200) . '...' : $message;
    notifySupportTelegram(
        "🎫 <b>Nuevo Ticket #$ticketId</b>\n" .
        "👤 <b>Usuario:</b> " . htmlspecialchars($username) . "\n" .
        "📋 <b>Asunto:</b> " . htmlspecialchars($subject) . "\n" .
        "🏷 <b>Categoría:</b> $category\n" .
        "⚡ <b>Prioridad:</b> $priority\n\n" .
        "💬 <b>Mensaje:</b>\n" . htmlspecialchars($preview)
    );

    echo json_encode(['status' => 'success', 'ticket_id' => $ticketId]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
