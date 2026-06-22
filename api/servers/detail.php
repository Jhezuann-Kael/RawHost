<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../repositories/AddonRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$server_id = $_GET['id'] ?? 0;

if (!$server_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing server ID']);
    exit;
}

try {
    $vpsRepo   = new VpsRepository();
    $addonRepo = new AddonRepository();
    $addonsPrice = (float)$addonRepo->getCumulativePriceByVpsId($server_id);

    $vpsData = $vpsRepo->getById($server_id);

    if (!$vpsData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'VPS not found']);
        exit;
    }

    // Verify Ownership
    if ($vpsData['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Fetch Orders
    // Metadata decoding
    $meta = json_decode($vpsData['metadata'] ?? '{}', true);
    $planMeta = json_decode($vpsData['plan_metadata'] ?? '{}', true);

    $os = ucwords(strtolower($meta['os'] ?? 'Unknown'));
    if (isset($meta['password']))
        $password = $meta['password'];
    else
        $password = '******'; // Should we send this? Manage.php did.

    $rawLoginLink = $meta['app_login_link'] ?? null;
    $appLoginLink = $rawLoginLink
        ? preg_replace('/\{\{\s*hostname\s*\}\}/', $vpsData['ip_address'] ?? '', $rawLoginLink)
        : null;

    $user = (stripos($os, 'windows') !== false) ? 'Administrator' : 'root';

    // Calculate Expiry from latest order
    // Expiration from DB
    $expiresAt = $vpsData['expires_at'];
    $remainingHours = 0;
    $remainingMinutes = 0;
    $remainingDisplay = '0 horas';

    if ($expiresAt) {
        $expiresTimestamp = strtotime($expiresAt);
        $remainingSeconds = $expiresTimestamp - time();
        $hoursDecimal = $remainingSeconds / 3600;

        // If negative, clamp to 0 or show negative? "SI ESTA EN 0" implies 0.
        if ($hoursDecimal < 0) {
            $hoursDecimal = 0;
        }

        $remainingDisplay = number_format($hoursDecimal, 1) . ' horas';
        // Update remainingHours for progress bar logic consistency
        $remainingHours = $hoursDecimal;
        $remainingMinutes = 0; // Not used/needed for this format
    }

    $dbDuration = (float)($vpsData['duration'] ?? 0);
    if ($dbDuration <= 0) $dbDuration = 720;

    // Prepare Response Data
    $response = [
        'id' => $vpsData['id'],
        'name' => $vpsData['name'],
        'ip' => $vpsData['ip_address'] ?? 'Pending',
        'status' => $vpsData['status'] ?? 'PROVISIONING',
        'os' => $os,
        'os_image_id' => $vpsData['os_image_id'],
        'application_id' => $vpsData['application_id'],
        'plan' => $vpsData['plan_name'] ?? 'Unknown Plan',
        'plan_price'   => $vpsData['plan_price'] ?? 0,
        'addons_price' => $addonsPrice,
        'expires_at'   => $expiresAt,
        'duration' => $dbDuration,
        'remaining_hours' => $remainingHours,
        'remaining_display' => $remainingDisplay,
        'price' => 0.00, // Placeholder
        'process' => 'root@vps:~# uptime' . "\n" . ' 10:23:41 up 1 day,  1:23,  1 user,  load average: 0.00, 0.00, 0.00',
        'password' => $password,
        'user' => $user,
        'app_login_link' => $appLoginLink,
        'specs' => [
            'cpu' => $planMeta['cpu'] ?? $planMeta['vcpu'] ?? 'N/A',
            'ram' => isset($planMeta['ram']) ? ($planMeta['ram'] / 1073741824) . ' GB' : 'N/A',
            'disk' => $planMeta['disk'] ?? 'N/A'
        ],
        'disk_usage' => [
            'total' => (int) ($planMeta['disk'] ?? 0), // GB
            'used' => 0,  // GB
        ],
    ];

    echo json_encode(['success' => true, 'data' => $response]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
