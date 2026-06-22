<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../agents/gotify.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $userRepo = new UserRepository();
    $user     = $userRepo->getById((int) $_SESSION['user_id']);
    $token    = $user['gotify_token'] ?? null;

    $locale   = $user['language'] ?? 'en';
    $langFile = __DIR__ . '/../../languages/' . $locale . '.php';
    $lang     = file_exists($langFile) ? require $langFile : require __DIR__ . '/../../languages/en.php';

    if (!$token) {
        echo json_encode(['success' => false, 'no_token' => true, 'message' => $lang['notif_no_token']]);
        exit;
    }

    $ok = gotify_send($token, '✅ RawHost — Test', 'Push notifications are working correctly.', 5);

    echo json_encode(['success' => $ok, 'message' => $ok ? $lang['notif_sent'] : $lang['notif_fail']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
