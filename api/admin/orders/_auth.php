<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

session_start();

if (!isset($_SESSION['user_id']) || !($_SESSION['is_superuser'] ?? false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
