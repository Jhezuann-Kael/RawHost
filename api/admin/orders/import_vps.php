<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../../repositories/PlanRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $data       = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId    = (int)($data['order_id']   ?? 0);
    $ipAddress  = trim($data['ip_address']  ?? '');
    $serverName = trim($data['name_server'] ?? '');

    if (!$orderId || $ipAddress === '') {
        http_response_code(400);
        echo json_encode(['error' => 'order_id e ip_address son requeridos']);
        exit;
    }

    $repo     = new OrderRepository();
    $txRepo   = new TransactionRepository();
    $vpsRepo  = new VpsRepository();
    $planRepo = new PlanRepository();

    $order = $repo->getById($orderId);
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => "Orden #$orderId no encontrada"]);
        exit;
    }
    if (!empty($order['vps_id'])) {
        http_response_code(409);
        echo json_encode(['error' => "La orden #$orderId ya tiene una VPS asignada (ID {$order['vps_id']})"]);
        exit;
    }

    $tx   = $txRepo->getCompletedByLocalOrderId($orderId);
    $meta = $tx ? (json_decode($tx['order_metadata'], true) ?? []) : [];

    if ($serverName === '') $serverName = $meta['name_server'] ?? ('vps-' . $orderId);
    $password = $meta['password'] ?? '';

    $osName = 'Unknown';
    if (!empty($order['plan_id'])) {
        $plan  = $planRepo->getById($order['plan_id']);
        $osId  = $order['image_os_id'] ?? null;
        $appId = $order['application_id'] ?? null;

        if ($appId && !empty($plan['available_applications'])) {
            foreach ($plan['available_applications'] as $app) {
                if ((string)$app['id'] === (string)$appId) { $osName = $app['name']; break; }
            }
        } elseif ($osId && !empty($plan['available_os_image_versions'])) {
            foreach ($plan['available_os_image_versions'] as $img) {
                if ((string)$img['id'] === (string)$osId) { $osName = ucwords(strtolower($img['name'])); break; }
            }
        }
    }

    $expiresAt = date('Y-m-d H:i:s', strtotime($order['created_at']) + ((int)$order['duration'] * 3600));

    $vpsId = $vpsRepo->create([
        'user_id'        => (int)$order['user_id'],
        'plan_id'        => $order['plan_id'],
        'status'         => 'ACTIVE',
        'name'           => $serverName,
        'ip_address'     => $ipAddress,
        'external_id'    => null,
        'os_image_id'    => $order['application_id'] ? null : $order['image_os_id'],
        'application_id' => $order['application_id'] ?: null,
        'duration'       => (int)$order['duration'],
        'expires_at'     => $expiresAt,
        'metadata'       => json_encode(['password' => $password, 'id_solusvm' => null, 'os' => $osName]),
    ]);

    $repo->updateVpsAndStatus($orderId, $vpsId, 'COMPLETED');

    @file_put_contents(__DIR__ . '/../../../logs/admin_actions.log',
        date('[Y-m-d H:i:s] ') . "Admin #{$_SESSION['user_id']} imported VPS #$vpsId for order #$orderId (IP: $ipAddress)\n",
        FILE_APPEND);

    echo json_encode([
        'success' => true,
        'message' => "VPS #$vpsId importada correctamente para la orden #$orderId.",
        'vps_id'  => $vpsId,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
