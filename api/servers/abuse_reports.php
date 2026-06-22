<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$serverId = $_GET['id'] ?? 0;
$ip = $_GET['ip'] ?? null;

if (!$serverId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing server ID']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vps = $vpsRepo->getById($serverId);

    if (!$vps) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'VPS not found']);
        exit;
    }

    if ($vps['user_id'] != $_SESSION['user_id'] && !($_SESSION['is_superuser'] ?? false)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $externalId = $vps['external_id'] ?? null;
    if (!$externalId) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $api = new ExternalApiService();
    $reportIp = $ip ?: ($vps['ip_address'] ?? null);
    $result = $api->getAbuseReports($externalId, $reportIp);

    // 404 = no reports on the provider side, treat as empty
    if ($result['http_code'] === 404) {
        echo json_encode(['success' => false, 'message' => 'No abuse reports found']);
        exit;
    }

    if ($result['http_code'] >= 400) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Provider error: HTTP ' . $result['http_code']]);
        exit;
    }

    $raw = $result['response'] ?? [];
    $reports = $raw['data'] ?? $raw['reports'] ?? $raw ?? [];
    if (!is_array($reports)) {
        $reports = [];
    }
    // Associative (single report as object) → wrap in list
    if (count($reports) > 0 && !isset($reports[0])) {
        $reports = [$reports];
    }

    echo json_encode(['success' => true, 'data' => $reports]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
