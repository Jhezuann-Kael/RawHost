<?php
session_start();
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$vpsId = $data['vps_id'] ?? 0;
$name  = trim($data['name'] ?? '');

if (!$vpsId || $name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing vps_id or name']);
    exit;
}

if (strlen($name) > 64) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name too long (max 64 chars)']);
    exit;
}

try {
    $vpsRepo = new VpsRepository();
    $vps = $vpsRepo->getById($vpsId);

    if (!$vps || $vps['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("UPDATE vps SET name = :name, updated_at = NOW() WHERE id = :id AND user_id = :uid");
    $stmt->execute([':name' => $name, ':id' => $vpsId, ':uid' => $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Renamed successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
