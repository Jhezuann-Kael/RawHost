<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $servers = $vpsRepo->getByUserId($_SESSION['user_id']);

    // Format data for frontend if needed, or return as is
    // We might want to decode metadata for easy access to password/os
    // We might want to decode metadata for easy access to password/os
    $formattedServers = array_map(function ($server) {
        $meta = json_decode($server['metadata'] ?? '{}', true);
        $planMeta = json_decode($server['plan_metadata'] ?? '{}', true);

        $os = ucwords(strtolower($meta['os'] ?? 'Unknown'));
        $user = (stripos($os, 'windows') !== false) ? 'Administrator' : 'root';

        return [
            'id' => $server['id'],
            'name' => $server['name'],
            'ip' => $server['ip_address'],
            'status' => $server['status'],
            'os' => $os,
            'os_image_id' => $server['os_image_id'],
            'application_id' => $server['application_id'],
            'user' => $user,
            'plan_name' => $server['plan_name'] ?? null,
            'specs' => [
                'cpu' => $planMeta['cpu'] ?? $planMeta['vcpu'] ?? 'N/A',
                'ram' => isset($planMeta['ram']) ? ($planMeta['ram'] / 1073741824) . ' GB' : 'N/A',
                'disk' => $planMeta['disk'] ?? 'N/A'
            ],
            'created_at' => $server['created_at'] ?? null,
            'expires_at' => $server['expires_at'] ?? null,
            'password' => $meta['password'] ?? ''
        ];
    }, $servers);

    echo json_encode(['success' => true, 'data' => $formattedServers]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
