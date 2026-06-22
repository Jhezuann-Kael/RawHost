<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_superuser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method    = $_SERVER['REQUEST_METHOD'];
$orderRepo = new OrderRepository();

// ── GET: list all managed_service orders ─────────────────────────────────────
if ($method === 'GET') {
    $page    = max(1, (int) ($_GET['page']   ?? 1));
    $perPage = max(1, (int) ($_GET['limit']  ?? 15));
    $search  = trim($_GET['search'] ?? '');

    $result = $orderRepo->getAllServicesPaginated($page, $perPage, $search);

    echo json_encode([
        'success'    => true,
        'data'       => $result['orders'],
        'pagination' => [
            'total'        => $result['total'],
            'pages'        => $result['pages'],
            'current_page' => $result['current_page'],
            'limit'        => $result['limit'],
        ],
    ]);
    exit;
}

// ── POST: create a new managed_service order ─────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $userId     = (int)   ($input['user_id']    ?? 0);
    $amount     = (float) ($input['amount']     ?? 0);
    $description = trim(  $input['description'] ?? '');
    $chargeNow  = !empty( $input['charge_now']);   // true = deduct balance now; false = leave PENDING

    if (!$userId || $amount <= 0 || $description === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
        exit;
    }

    $userRepo = new UserRepository();
    $user     = $userRepo->getById($userId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if ($chargeNow && (float) $user['balance'] < $amount) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient balance. Current balance: $' . number_format($user['balance'], 2),
        ]);
        exit;
    }

    try {
        $status  = $chargeNow ? 'COMPLETED' : 'PENDING';
        $orderId = $orderRepo->createService($userId, $amount, $description, $status);

        if ($chargeNow) {
            // Deduct balance via movement — no transaction record (balance payment ≠ crypto)
            $movRepo = new MovementRepository();
            $movRepo->create([
                'user_id'     => $userId,
                'type'        => 'OUT',
                'amount'      => $amount,
                'description' => 'Managed service: ' . $description,
            ]);
        }

        $updatedUser = $userRepo->getById($userId);

        echo json_encode([
            'success'     => true,
            'message'     => $chargeNow
                ? 'Order created and balance charged successfully'
                : 'Order created as pending — waiting for user payment',
            'order_id'    => $orderId,
            'status'      => $status,
            'new_balance' => $updatedUser['balance'],
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── DELETE: remove order and its transactions ────────────────────────────────
if ($method === 'DELETE') {
    $orderId = (int) ($_GET['id'] ?? 0);

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing order ID']);
        exit;
    }

    $order = $orderRepo->getById($orderId);
    if (!$order || $order['type'] !== 'managed_service') {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    try {
        $txRepo  = new TransactionRepository();
        $deleted = $txRepo->deleteByOrderId((string) $orderId);
        $orderRepo->deleteById($orderId);

        echo json_encode([
            'success' => true,
            'message' => 'Order deleted',
            'transactions_removed' => $deleted,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
