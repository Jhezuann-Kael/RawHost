<?php
/**
 * POST /api/transactions/expire
 * Body: { "id": 123 }
 *
 * Marks a PENDING transaction as EXPIRED if its expired_at
 * timestamp is in the past. Only touches transactions belonging
 * to the current session user.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$txId = intval($body['id'] ?? 0);

if ($txId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit;
}

$txRepo = new TransactionRepository();
$tx = $txRepo->getByIdAndUser($txId, (int) $_SESSION['user_id']);

if (!$tx) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

// Only update if PENDING and past expiry
$now = time();
$expiredAt = intval($tx['expired_at'] ?? 0);

if (strtoupper($tx['status']) === 'PENDING' && $expiredAt > 0 && $now >= $expiredAt) {
    $txRepo->updateStatus($tx['track_id'], 'EXPIRED', null);
    echo json_encode(['success' => true, 'expired' => true]);
} else {
    echo json_encode(['success' => true, 'expired' => false]);
}
