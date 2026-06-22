<?php
header("Content-Type: application/json");
require_once '../../api/config.php';
require_once '../../repositories/AddonRepository.php';
require_once '../../repositories/VpsRepository.php';
require_once '../../repositories/UserRepository.php';
require_once '../../repositories/OrderRepository.php';
require_once '../../repositories/MovementRepository.php';
require_once '../../services/ExternalApiService.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$userId    = $_SESSION['user_id'];
$addonRepo = new AddonRepository();
$vpsRepo   = new VpsRepository();
$userRepo  = new UserRepository();
$orderRepo = new OrderRepository();
$movRepo   = new MovementRepository();

try {
    $data  = json_decode(file_get_contents('php://input'), true);
    $vpsId = (int)($data['vps_id'] ?? 0);

    if (!$vpsId) {
        throw new Exception("VPS ID requerido");
    }

    $vps = $vpsRepo->getById($vpsId);
    if (!$vps || $vps['user_id'] != $userId) {
        throw new Exception("VPS no encontrado o sin permisos");
    }

    $user    = $userRepo->getById($userId);
    $ipPrice = IPV4_ADDON_PRICE;

    if (floatval($user['balance']) < $ipPrice) {
        throw new Exception("Saldo insuficiente. Necesitas $$ipPrice");
    }

    // Call external API first — no money moves until we know it worked
    $externalApi     = new ExternalApiService();
    $externalAddonId = defined('EXTERNAL_IPV4_ADDON_ID')
        ? EXTERNAL_IPV4_ADDON_ID
        : 'CHANGE_ME';

    $apiResult = $externalApi->createAddonOrder(
        $vps['external_id'],
        $externalAddonId,
        'balance'
    );

    if ($apiResult['http_code'] !== 200 || empty($apiResult['response']['success'])) {
        $errMsg = $apiResult['response']['message'] ?? 'Error desconocido del proveedor';
        throw new Exception("Error al provisionar IP: $errMsg");
    }

    // Extract data from provider response
    $addonServer     = $apiResult['response']['addon_server'] ?? [];
    $externalOrderId = $addonServer['id']        ?? null;
    $ipAddress       = $addonServer['ipaddress'] ?? $addonServer['ip'] ?? $addonServer['value'] ?? null;

    // Deduct balance and create records now that the API succeeded
    deductBalance($userId, $ipPrice, "Compra de IP adicional para VPS #$vpsId");

    $addonId = $addonRepo->create([
        'user_id'     => $userId,
        'vps_id'      => $vpsId,
        'type'        => 'IPV4',
        'value'       => $ipAddress,
        'price'       => $ipPrice,
        'status'      => 'ACTIVE',
        'external_id' => $externalOrderId,
    ]);

    $orderRepo->create([
        'user_id'      => $userId,
        'vps_id'       => $vpsId,
        'addon_id'     => $addonId,
        'plan_id'      => null,
        'duration'     => 720,
        'total_amount' => $ipPrice,
        'status'       => 'COMPLETED',
    ]);

    // Telegram notification (non-fatal)
    $msg  = "➕ <b>Nueva IP Adicional</b>\n\n";
    $msg .= "👤 <b>Usuario:</b> $userId\n";
    $msg .= "🖥️ <b>VPS:</b> $vpsId\n";
    $msg .= "🌐 <b>IP:</b> " . ($ipAddress ?? 'pendiente') . "\n";
    $msg .= "💰 <b>Precio:</b> $" . number_format($ipPrice, 2) . "\n";
    try { sendTelegramNotification($msg); } catch (Exception $_) {}

    echo json_encode([
        'success'    => true,
        'message'    => 'IP adquirida exitosamente',
        'addon_id'   => $addonId,
        'ip_address' => $ipAddress,
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
