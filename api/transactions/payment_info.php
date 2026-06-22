<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
/**
 * GET /api/transactions/payment_info?id={transaction_id}
 *
 * Fetches live payment details from OxaPay using the
 * track_id stored for the given local transaction ID.
 * Also auto-updates the local status to EXPIRED when
 * OxaPay reports the payment as expired.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

// --- Auth check ---
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$txId = intval($_GET['id'] ?? 0);
if ($txId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit;
}

try {
    $txRepo = new TransactionRepository();
    $tx = $txRepo->getByIdAndUser($txId, (int) $_SESSION['user_id']);

    if (!$tx) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    $trackId = $tx['track_id'] ?? null;
    if (!$trackId) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'local' => $tx,
            'message' => 'No track_id available for this transaction'
        ]);
        exit;
    }

    // --- Fetch from OxaPay ---
    $url = "https://api.oxapay.com/v1/payment/{$trackId}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'merchant_api_key: ' . OXAPAY_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw) {
        throw new Exception('Empty response from OxaPay');
    }

    $oxaResponse = json_decode($raw, true);
    if (!$oxaResponse || ($oxaResponse['status'] ?? 0) !== 200) {
        throw new Exception('OxaPay error: ' . ($oxaResponse['message'] ?? 'unknown'));
    }

    $payData = $oxaResponse['data'] ?? [];

    // --- Auto-expire: update local DB if OxaPay says expired ---
    $oxaStatus = strtolower($payData['status'] ?? '');
    $localStatus = strtoupper($tx['status'] ?? '');

    if (in_array($oxaStatus, ['expired', 'cancelled', 'canceled']) && $localStatus === 'PENDING') {
        $txRepo->updateStatus($trackId, 'EXPIRED', null);
    }

    echo json_encode([
        'success' => true,
        'data' => $payData,
        'local' => $tx,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
