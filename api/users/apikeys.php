<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/ApiKeyRepository.php';

try {
    $userId = authenticate_user();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method  = $_SERVER['REQUEST_METHOD'];
$keyRepo = new ApiKeyRepository();

// GET — list keys
if ($method === 'GET') {
    $keys = array_filter($keyRepo->getByUserId($userId), fn($k) => $k['name'] !== 'default');
    foreach ($keys as &$k) {
        $k['apikey_masked'] = '••••••••••••' . substr($k['apikey'], -6);
    }
    echo json_encode(['success' => true, 'data' => array_values($keys)]);
    exit;
}

// POST — create key
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($body['name'] ?? 'My Key');
    if ($name === '') $name = 'My Key';

    // Max 5 keys per user
    if (count($keyRepo->getByUserId($userId)) >= 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Maximum 5 API keys allowed']);
        exit;
    }

    $key = $keyRepo->create($userId, $name);
    echo json_encode(['success' => true, 'apikey' => $key, 'name' => $name]);
    exit;
}

// DELETE — remove key
if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing id']);
        exit;
    }

    // Prevent deleting the bot's default key
    $all = $keyRepo->getByUserId($userId);
    foreach ($all as $k) {
        if ((int)$k['id'] === $id && $k['name'] === 'default') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Cannot delete default key']);
            exit;
        }
    }
    $deleted = $keyRepo->delete($id, $userId);
    echo json_encode(['success' => $deleted]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
