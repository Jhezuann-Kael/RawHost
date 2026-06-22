<?php
/**
 * GET /api/orders/invoice_status?track_id={track_id}
 *
 * Returns the current status of a transaction by track_id,
 * as stored in the local DB. Used for client-side polling
 * to detect when an invoice moves from PENDING to COMPLETED.
 */

if (session_status() === PHP_SESSION_NONE)
    session_start();

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

// ── Auth ──────────────────────────────────────────────────
try {
    $userId = authenticate_user();
} catch (Exception $ex) {
    invoiceLog('ERROR: Auth failed — ' . $ex->getMessage());
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}


$trackId = trim($_GET['track_id'] ?? '');
if (!$trackId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing track_id']);
    exit;
}

$repo = new TransactionRepository();
$tx = $repo->getByTrackId($trackId);

if (!$tx || (int) $tx['user_id'] !== (int) $userId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'status' => $tx['status'],        // PENDING / COMPLETED / FAILED / EXPIRED
    'type' => $tx['type'] ?? null,   // recharge / vps_purchase
]);
