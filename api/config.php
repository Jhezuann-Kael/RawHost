<?php
date_default_timezone_set('America/Caracas');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'CHANGE_ME');
define('DB_NAME', 'dummiesvps'); // Using existing DB name

// API Configuration
define('EXTERNAL_API_BASE', 'CHANGE_ME');
define('EXTERNAL_API_KEY', 'CHANGE_ME');
define('EXTERNAL_USER_ID', 'CHANGE_ME');

// NiceNIC Domain Registrar
define('NICENIC_API_BASE', 'http://api.nicenic.net/v2/');
define('NICENIC_USER', 'CHANGE_ME');
define('NICENIC_PASS', 'CHANGE_ME');
define('NICENIC_EMAIL', 'CHANGE_ME'); // TODO: email registrado en la cuenta NiceNIC
define('OXAPAY_API_KEY', 'CHANGE_ME');
define('PLAN_MARKUP', 1.00);
define('EXTERNAL_IPV4_ADDON_ID', '');
define('IPV4_ADDON_PRICE', 6.99);
define('TOKEN_TELEGRAM', 'CHANGE_ME');

define('TELEGRAM_CHAT_ID', 'CHANGE_ME');
define('BOT_USERNAME', 'raw_host_bot');

define('SITE_NAME', 'RawHost');

// Gotify Push Notifications
define('GOTIFY_URL', 'https://push.rawhost.net');
define('GOTIFY_ADMIN_USER', 'admin');
define('GOTIFY_ADMIN_PASS', 'CHANGE_ME');

// hCaptcha Configuration
define('HCAPTCHA_SITE_KEY', 'cb7ce2b6-8af6-46aa-9904-7410d81e3e48');
define('HCAPTCHA_SECRET_KEY', 'CHANGE_ME');

// Session Configuration (30 Days)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 2592000);
    session_set_cookie_params(2592000);
}

/**
 * Returns the short-term surcharge multiplier (e.g. 1.20 for 20%) when the
 * requested duration is less than one full month (720 h) and the plan has a
 * short_term fee defined.  Returns 1.0 otherwise.
 */
function getShortTermMultiplier(array $fees, int $hours): float
{
    if ($hours >= 720)
        return 1.0;
    foreach ($fees as $fee) {
        if (($fee['billing_type'] ?? '') === 'short_term' && ($fee['type'] ?? '') === 'percentage') {
            return 1.0 + ((float) $fee['value'] / 100.0);
        }
    }
    return 1.0;
}

function getSetupFeesTotal(array $fees): float
{
    $total = 0.0;
    foreach ($fees as $fee) {
        if (($fee['billing_type'] ?? '') === 'setup' && ($fee['type'] ?? '') === 'fixed') {
            $total += (float) $fee['value'];
        }
    }
    return $total;
}

/**
 * Returns ['pct' => totalPct, 'fixed' => totalFixed] for all recurring fees.
 * pct is the combined percentage (e.g. 6.0 for 6%), fixed is a flat amount per order.
 */
function getRecurringFees(array $fees): array
{
    $pct = 0.0;
    $fixed = 0.0;
    foreach ($fees as $fee) {
        if (($fee['billing_type'] ?? '') === 'recurring') {
            if (($fee['type'] ?? '') === 'percentage') {
                $pct += (float) $fee['value'];
            } elseif (($fee['type'] ?? '') === 'fixed') {
                $fixed += (float) $fee['value'];
            }
        }
    }
    return ['pct' => $pct, 'fixed' => $fixed];
}

function calculateOrderDetails($hours, $planPrice, $currentExpiresAt = null, $startTimestampOverride = null)
{
    $hourlyPrice = ($planPrice > 0) ? ($planPrice / 720) : 0;
    $totalAmount = $hourlyPrice * $hours;

    if ($startTimestampOverride !== null) {
        $startTimestamp = $startTimestampOverride;
    } else {
        $startTimestamp = time();
        if ($currentExpiresAt && strtotime($currentExpiresAt) > time()) {
            $startTimestamp = strtotime($currentExpiresAt);
        }
    }

    $newExpiryTimestamp = $startTimestamp + ($hours * 3600);
    $newExpiryDate = date('Y-m-d H:i:s', $newExpiryTimestamp);

    return [
        'total_amount' => number_format($totalAmount, 2, '.', ''),
        'new_expires_at' => $newExpiryDate
    ];
}



function sendTelegramNotification($message)
{
    if (!defined('TOKEN_TELEGRAM') || !defined('TELEGRAM_CHAT_ID')) {
        error_log("Telegram configuration missing");
        return false;
    }

    $url = "https://api.telegram.org/bot" . TOKEN_TELEGRAM . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Timeout to prevent hanging the order process
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

function sendUserTelegramNotification(string $telegramId, string $message): void
{
    if (!defined('TOKEN_TELEGRAM') || !$telegramId)
        return;
    $ch = curl_init("https://api.telegram.org/bot" . TOKEN_TELEGRAM . "/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $telegramId, 'text' => $message, 'parse_mode' => 'HTML']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

define('SUPPORT_TG_TOKEN', 'CHANGE_ME');
define('SUPPORT_TG_CHAT_ID', 'CHANGE_ME');

function notifySupportTelegram(string $text): void
{
    $url = 'https://api.telegram.org/bot' . SUPPORT_TG_TOKEN . '/sendMessage';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'chat_id' => SUPPORT_TG_CHAT_ID,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Deduct balance from user
 * Throws Exception if insufficient funds.
 */
function deductBalance($userId, $amount, $description)
{
    require_once __DIR__ . '/../repositories/UserRepository.php';
    require_once __DIR__ . '/../repositories/MovementRepository.php';

    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);

    if (!$user) {
        throw new Exception("User not found");
    }

    $currentBalance = floatval($user['balance']);
    if ($currentBalance < $amount) {
        throw new Exception("Saldo insuficiente. Por favor recarga tu cuenta.");
    }

    $movRepo = new MovementRepository();
    $movRepo->create([
        'user_id' => $userId,
        'type' => 'OUT',
        'amount' => $amount,
        'description' => $description
    ]);

    return true;
}

/**
 * Authenticate User via Session or X-API-KEY
 * @return int User ID
 * @throws Exception if authentication fails
 */
function authenticate_user()
{
    // 1. Check Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }

    // 2. Check X-API-KEY Header
    $apiKey = null;
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $apiKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? null;
    }

    if (!$apiKey) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['X-API-KEY'] ?? null;
    }

    if ($apiKey) {
        require_once __DIR__ . '/../repositories/ApiKeyRepository.php';
        $apiKeyRepo = new ApiKeyRepository();
        $userId = $apiKeyRepo->getUserIdByApiKey($apiKey);

        if ($userId) {
            return $userId;
        }
    }

    throw new Exception("Authentication failed. Invalid Session or API Key.");
}

/**
 * Provision, Renew or Upgrade a VPS based on order data.
 * Handles External API communication and DB updates.
 * @param array $orderData
 * @return mixed Created VPS ID or success boolean
 * @throws Exception
 */
function provisionVps(array $orderData)
{
    require_once __DIR__ . '/../repositories/OrderRepository.php';
    require_once __DIR__ . '/../repositories/VpsRepository.php';
    require_once __DIR__ . '/../repositories/PlanRepository.php';
    require_once __DIR__ . '/../repositories/UserRepository.php';
    require_once __DIR__ . '/../services/ExternalApiService.php';

    $orderRepo = new OrderRepository();
    $vpsRepo = new VpsRepository();
    $planRepo = new PlanRepository();
    $userRepo = new UserRepository();
    $apiService = new ExternalApiService();

    $type = $orderData['type'] ?? 'vps_purchase';
    $userId = (int) ($orderData['user_id'] ?? 0);

    if (!$userId) {
        throw new Exception("provisionVps: Missing user_id in order data");
    }

    switch ($type) {
        case 'vps_purchase':
        case 'purchase':
            $planId = $orderData['plan_id'] ?? null;
            $osImageId = $orderData['os_image_id'] ?? null;
            $duration = (int) ($orderData['duration'] ?? 1);
            $serverName = $orderData['name_server'] ?? 'vps-server';
            $vpsPassword = $orderData['password'] ?? '';
            $planName = $orderData['plan_name'] ?? 'Plan';

            $plan = $planRepo->getById($planId);
            if (!$plan)
                throw new Exception("Plan not found: $planId");

            // Calculate expiry and amount
            $orderDetails = calculateOrderDetails($duration, (float) $plan['price'], null);
            $newExpiresAt = $orderDetails['new_expires_at'];
            $totalAmount = $orderDetails['total_amount'];
            $appId = !empty($orderData['application_id']) ? (int) $orderData['application_id'] : null;

            $localOrderId = !empty($orderData['local_order_id']) ? (int) $orderData['local_order_id'] : null;
            if (!$localOrderId) {
                $localOrderId = $orderRepo->create([
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'image_os_id' => ($appId !== null) ? null : $osImageId,
                    'application_id' => $appId,
                    'duration' => $duration,
                    'total_amount' => $totalAmount,
                    'currency' => 'USD',
                    'status' => 'PENDING',
                    'type' => 'vps',
                ]);
            }

            // Provision via External API
            $provisionParams = [
                'rental_duration' => $duration,
                'name_server' => $serverName,
                'password' => $vpsPassword,
                'type_payment' => 'balance',
                'plan_id' => $plan['external_id'],
                'auto_renew' => 1,
                'versions' => 'dummiesvps'
            ];

            if ($appId) {
                $provisionParams['application_id'] = $appId;
            } else {
                $provisionParams['os_image_id'] = $osImageId;
            }

            $apiResp = $apiService->createOrder($provisionParams);

            if (!$apiResp)
                throw new Exception("External API failed to provision VPS for order $localOrderId");

            if (isset($apiResp['server_status'])) {
                $serverStatus = $apiResp['server_status'];
                $newId = $serverStatus['new_id'] ?? null;
                $idSolusvm = $serverStatus['id_solusvm'] ?? null;
                $primaryIp = $serverStatus['primary_ip'] ?? null;
                $returnedName = $serverStatus['name'] ?? $serverName;
                $metaString = $serverStatus['metadata'] ?? '';

                // Parse metadata string: password=X,username=Y,app_login_link=Z
                $metaParsed = [];
                foreach (explode(',', $metaString) as $pair) {
                    [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
                    $metaParsed[trim($k)] = trim($v);
                }
                if (!empty($metaParsed['password'])) {
                    $vpsPassword = $metaParsed['password'];
                }
                // Prefer the direct field; fall back to the parsed metadata string
                $appLoginLink = $serverStatus['app_login_link']
                    ?? $metaParsed['app_login_link']
                    ?? null;

                // Determine display name (OS or App)
                $displayName = 'Unknown';
                if ($appId) {
                    $rawApps = $plan['available_applications'] ?? '[]';
                    $apps = is_string($rawApps) ? json_decode($rawApps, true) : $rawApps;
                    if (is_array($apps)) {
                        foreach ($apps as $app) {
                            if (strval($app['id']) === strval($appId)) {
                                $displayName = $app['name'] ?? 'Unknown';
                                break;
                            }
                        }
                    }
                } else {
                    $rawVersions = $plan['available_os_image_versions'] ?? '[]';
                    $allowedOsImages = is_string($rawVersions) ? json_decode($rawVersions, true) : $rawVersions;
                    if (is_array($allowedOsImages)) {
                        foreach ($allowedOsImages as $img) {
                            if (strval($img['id']) === strval($osImageId)) {
                                $displayName = ucwords(strtolower($img['name'] ?? 'Unknown'));
                                break;
                            }
                        }
                    }
                }

                $metaData = [
                    'password' => $vpsPassword,
                    'id_solusvm' => $idSolusvm,
                    'os' => $displayName,
                ];
                if ($appLoginLink) {
                    $metaData['app_login_link'] = $appLoginLink;
                }
                $metaJson = json_encode($metaData);

                $createdVpsId = $vpsRepo->create([
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'status' => 'ACTIVE',
                    'name' => $returnedName,
                    'ip_address' => $primaryIp,
                    'external_id' => $newId,
                    'os_image_id' => ($appId !== null) ? null : $osImageId,
                    'application_id' => $appId,
                    'duration' => $duration,
                    'expires_at' => $newExpiresAt,
                    'metadata' => $metaJson
                ]);

                $orderRepo->updateVpsAndStatus($localOrderId, $createdVpsId, 'COMPLETED');

                // Telegram notification for admins
                // Telegram notification for user
                try {
                    $userInfo = $userRepo->getById($userId);
                    $userTgId = $userInfo['telegram_id'] ?? null;
                    if ($userTgId) {
                        sendUserTelegramNotification(
                            $userTgId,
                            "✅ <b>¡Tu VPS ha sido entregada!</b>\n\n" .
                            "🏷️ <b>Plan:</b> $planName\n" .
                            "🖥️ <b>Hostname:</b> $returnedName\n" .
                            "🌐 <b>IP:</b> " . ($primaryIp ?? 'Asignando...') . "\n" .
                            "💻 <b>Sistema:</b> $displayName\n" .
                            "⏱️ <b>Duración:</b> {$duration}h\n\n" .
                            "Tu VPS ya está activa. Puedes gestionarla desde tu panel."
                        );
                    }
                } catch (Exception $e) {
                    // Ignore user notification errors
                }

                return $createdVpsId;
            }
            break;

        case 'vps_renew':
        case 'renew':
            $serverId = (int) ($orderData['server_id'] ?? 0);
            $duration = (int) ($orderData['duration'] ?? 0);

            if (!$serverId || !$duration)
                throw new Exception("Missing server_id or duration for renewal");

            $vps = $vpsRepo->getById($serverId);
            if (!$vps)
                throw new Exception("Server not found: $serverId");

            $apiResp = $apiService->createOrder([
                'server_id' => $vps['external_id'],
                'type_payment' => 'balance',
                'rental_duration' => $duration
            ]);

            if ($apiResp) {
                $vpsRepo->updateDuration($serverId, $duration);
                $currentExpiry = $vps['expires_at'] ? strtotime($vps['expires_at']) : time();
                if ($currentExpiry < time())
                    $currentExpiry = time();

                $newExpiryDate = date('Y-m-d H:i:s', $currentExpiry + ($duration * 3600));
                $vpsRepo->updateExpiration($serverId, $newExpiryDate);

                $localOrderId = !empty($orderData['local_order_id']) ? (int) $orderData['local_order_id'] : null;
                if ($localOrderId) {
                    $orderRepo->updateStatus($localOrderId, 'COMPLETED');
                } else {
                    $orderRepo->create([
                        'user_id' => $userId,
                        'vps_id' => $serverId,
                        'duration' => $duration,
                        'total_amount' => $orderData['amount'] ?? 0,
                        'status' => 'COMPLETED',
                        'type' => 'vps_renew',
                    ]);
                }

                try {
                    $userInfo = $userRepo->getById($userId);
                    $userTgId = $userInfo['telegram_id'] ?? null;
                    if ($userTgId) {
                        sendUserTelegramNotification(
                            $userTgId,
                            "🔄 <b>Tu VPS ha sido renovada</b>\n\n" .
                            "🖥️ <b>Hostname:</b> " . ($vps['name'] ?? 'Unknown') . "\n" .
                            "📅 <b>Duración añadida:</b> {$duration}h\n" .
                            "⏰ <b>Nueva expiración:</b> $newExpiryDate"
                        );
                    }
                } catch (Exception $e) {
                }

                return true;
            }
            break;

        case 'vps_upgrade':
        case 'upgrade':
            $serverId = (int) ($orderData['server_id'] ?? 0);
            $planId = $orderData['plan_id'] ?? null;

            if (!$serverId || !$planId)
                throw new Exception("Missing server_id or plan_id for upgrade");

            $vps = $vpsRepo->getById($serverId);
            $newPlan = $planRepo->getById($planId);
            if (!$vps || !$newPlan)
                throw new Exception("Server or Plan not found for upgrade");

            $apiResp = $apiService->createOrder([
                'server_id' => $vps['external_id'],
                'type_payment' => 'balance',
                'plan_id' => $newPlan['external_id']
            ]);

            if ($apiResp) {
                $vpsRepo->updatePlan($serverId, $newPlan['id']);
                $localOrderId = !empty($orderData['local_order_id']) ? (int) $orderData['local_order_id'] : null;
                if ($localOrderId) {
                    $orderRepo->updateStatus($localOrderId, 'COMPLETED');
                } else {
                    $orderRepo->create([
                        'user_id' => $userId,
                        'vps_id' => $serverId,
                        'plan_id' => $planId,
                        'total_amount' => $orderData['amount'] ?? 0,
                        'status' => 'COMPLETED',
                        'type' => 'vps_upgrade',
                    ]);
                }

                try {
                    $userInfo = $userRepo->getById($userId);
                    $userTgId = $userInfo['telegram_id'] ?? null;
                    if ($userTgId) {
                        sendUserTelegramNotification(
                            $userTgId,
                            "⬆️ <b>Tu VPS ha sido mejorada</b>\n\n" .
                            "🖥️ <b>Hostname:</b> " . ($vps['name'] ?? 'Unknown') . "\n" .
                            "🏷️ <b>Nuevo Plan:</b> " . ($newPlan['name'] ?? 'Plan') . "\n\n" .
                            "El upgrade ya está aplicado. Puedes verificarlo desde tu panel."
                        );
                    }
                } catch (Exception $e) {
                }

                return true;
            }
            break;

        // ── from_order: derive everything from a DB order record ──────────────
        case 'from_order':
            $orderId = (int) ($orderData['order_id'] ?? 0);
            if (!$orderId)
                throw new Exception("provisionVps[from_order]: missing order_id");

            $order = $orderRepo->getById($orderId);
            if (!$order)
                throw new Exception("Order #$orderId not found");

            $resolvedUserId = (int) $order['user_id'];

            if (!empty($order['vps_id'])) {
                // Existing VPS → treat as renewal
                return provisionVps([
                    'type' => 'vps_renew',
                    'user_id' => $resolvedUserId,
                    'server_id' => (int) $order['vps_id'],
                    'duration' => (int) $order['duration'],
                    'local_order_id' => $orderId,
                    'amount' => $order['total_amount'],
                ]);
            }

            // No VPS yet → initial purchase
            $serverName = trim($orderData['name_server'] ?? '');
            $vpsPassword = trim($orderData['password'] ?? '');

            if ($serverName === '') {
                $user = $userRepo->getById($resolvedUserId);
                $uname = preg_replace('/[^a-z0-9]/', '', strtolower($user['username'] ?? 'vps'));
                $serverName = 'vps-' . substr($uname, 0, 8) . substr(md5(uniqid('', true)), 0, 4);
            }
            if ($vpsPassword === '') {
                $vpsPassword = 'Rawh0st#' . substr(md5(uniqid('', true)), 0, 8);
            }

            return provisionVps([
                'type' => 'vps_purchase',
                'user_id' => $resolvedUserId,
                'plan_id' => $order['plan_id'],
                'os_image_id' => $order['image_os_id'],
                'application_id' => $order['application_id'] ?: null,
                'duration' => (int) $order['duration'],
                'name_server' => $serverName,
                'password' => $vpsPassword,
                'plan_name' => $orderData['plan_name'] ?? '',
                'local_order_id' => $orderId,
            ]);

        default:
            throw new Exception("Unknown VPS provisioning type: $type");
    }

    return false;
}
