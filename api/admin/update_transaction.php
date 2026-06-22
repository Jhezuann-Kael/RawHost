<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

$txRepo    = new TransactionRepository();
$orderRepo = new OrderRepository();

try {
    // ── Complete Transaction ──────────────────────────────────────────────────
    if ($action === 'complete_transaction') {
        $txId          = (int) ($input['transaction_id'] ?? 0);
        $txHash        = trim($input['tx_hash'] ?? '');
        $creditBalance = isset($input['credit_balance']) ? (bool) $input['credit_balance'] : true;

        if (!$txId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing transaction_id']);
            exit;
        }

        $tx = $txRepo->getById($txId);
        if (!$tx) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            exit;
        }

        if ($tx['status'] === 'COMPLETED') {
            echo json_encode(['success' => false, 'message' => 'Transaction is already COMPLETED']);
            exit;
        }

        // Update transaction status + tx_hash
        $txRepo->updateStatus($tx['track_id'], 'COMPLETED', $txHash ?: null);

        $type    = $tx['type'] ?? 'recharge';
        $sideEffects = [];

        // Recharge → credit user balance
        if ($type === 'recharge') {
            $movRepo = new MovementRepository();
            $movRepo->create([
                'user_id'     => $tx['user_id'],
                'type'        => 'IN',
                'amount'      => $tx['amount'],
                'description' => 'Manual payment confirmation by admin (Track: ' . $tx['track_id'] . ')',
            ]);
            $sideEffects[] = 'Balance acreditado: $' . number_format($tx['amount'], 2);
        }

        // Managed service → complete the linked order
        if ($type === 'managed_service' && !empty($tx['order_id'])) {
            $orderRepo->updateStatus((int) $tx['order_id'], 'COMPLETED');
            $sideEffects[] = 'Orden #' . $tx['order_id'] . ' marcada como COMPLETED';
        }

        // VPS/domain purchases require external provisioning — just flag it
        if (in_array($type, ['vps_purchase', 'vps_renew', 'vps_upgrade', 'domain_purchase'])) {
            $sideEffects[] = "AVISO: tipo \"$type\" requiere aprovisionamiento externo manual";
        }

        $adminId = $_SESSION['user_id'];
        $logFile = __DIR__ . '/../../logs/admin_actions.log';
        @file_put_contents(
            $logFile,
            date('[Y-m-d H:i:s] ') . "Admin #$adminId completed transaction #$txId (Track: {$tx['track_id']}) hash: $txHash\n",
            FILE_APPEND
        );

        echo json_encode([
            'success'      => true,
            'message'      => 'Transacción marcada como COMPLETED',
            'side_effects' => $sideEffects,
        ]);
        exit;
    }

    // ── Complete Order ────────────────────────────────────────────────────────
    if ($action === 'complete_order') {
        $orderId = (int) ($input['order_id'] ?? 0);

        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing order_id']);
            exit;
        }

        $order = $orderRepo->getById($orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        if ($order['status'] === 'COMPLETED') {
            echo json_encode(['success' => false, 'message' => 'Order is already COMPLETED']);
            exit;
        }

        $orderRepo->updateStatus($orderId, 'COMPLETED');

        $adminId = $_SESSION['user_id'];
        $logFile = __DIR__ . '/../../logs/admin_actions.log';
        @file_put_contents(
            $logFile,
            date('[Y-m-d H:i:s] ') . "Admin #$adminId completed order #$orderId\n",
            FILE_APPEND
        );

        echo json_encode([
            'success' => true,
            'message' => "Orden #$orderId marcada como COMPLETED",
        ]);
        exit;
    }

    // ── Save Refund Amount ────────────────────────────────────────────────────
    if ($action === 'save_refund') {
        $txId   = (int) ($input['transaction_id'] ?? 0);
        $amount = isset($input['amount_refund']) ? (float) $input['amount_refund'] : null;

        if (!$txId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing transaction_id']);
            exit;
        }

        if ($amount !== null && $amount < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'amount_refund no puede ser negativo']);
            exit;
        }

        $tx = $txRepo->getById($txId);
        if (!$tx) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            exit;
        }

        $txRepo->updateRefundAmount($txId, $amount);

        $newStatus = ($amount !== null && $amount > 0) ? 'REFUND' : 'COMPLETED';
        $msg = $amount > 0
            ? "Reembolso de \$$amount guardado — estado cambiado a REFUND"
            : "Reembolso eliminado — estado restaurado a COMPLETED";

        $adminId = $_SESSION['user_id'];
        $logFile = __DIR__ . '/../../logs/admin_actions.log';
        @file_put_contents(
            $logFile,
            date('[Y-m-d H:i:s] ') . "Admin #$adminId set refund on tx #$txId: $" . number_format($amount ?? 0, 2) . " → $newStatus\n",
            FILE_APPEND
        );

        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
