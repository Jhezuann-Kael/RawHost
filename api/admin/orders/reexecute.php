<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../../repositories/TransactionRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $data       = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId    = (int)($data['order_id']   ?? 0);
    $serverName = trim($data['name_server'] ?? '');
    $password   = trim($data['password']    ?? '');
    $setPending = (bool)($data['set_pending'] ?? false);

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id requerido']);
        exit;
    }

    $repo   = new OrderRepository();
    $txRepo = new TransactionRepository();

    if ($setPending) {
        $repo->updateStatusAndClearVps($orderId);
    }

    $order = $repo->getById($orderId);

    if ($serverName === '' || $password === '') {
        $tx   = $txRepo->getCompletedByLocalOrderId($orderId);
        $meta = $tx ? (json_decode($tx['order_metadata'], true) ?? []) : [];
        if ($serverName === '' && !empty($meta['name_server'])) $serverName = $meta['name_server'];
        if ($password   === '' && !empty($meta['password']))    $password   = $meta['password'];
    }

    $provisionData = ['type' => 'from_order', 'order_id' => $orderId, 'user_id' => (int)($order['user_id'] ?? 0)];
    if ($serverName !== '') $provisionData['name_server'] = $serverName;
    if ($password   !== '') $provisionData['password']    = $password;

    $result = provisionVps($provisionData);

    @file_put_contents(__DIR__ . '/../../../logs/admin_actions.log',
        date('[Y-m-d H:i:s] ') . "Admin #{$_SESSION['user_id']} reexecute order #$orderId (set_pending=$setPending)\n",
        FILE_APPEND);

    echo json_encode([
        'success' => true,
        'message' => "Orden #$orderId ejecutada via provisionVps.",
        'result'  => $result,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
