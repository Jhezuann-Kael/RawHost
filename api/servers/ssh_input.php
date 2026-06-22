<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sessionId = $_POST['session_id'] ?? '';
$data      = $_POST['data'] ?? '';

if ($sessionId === '' || $data === '') {
    echo json_encode(['success' => false, 'message' => 'Missing params']);
    exit;
}

$sess = $_SESSION['ssh_sessions'][$sessionId] ?? null;
if (!$sess) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit;
}

$fifo = $sess['input_fifo'];
if (!file_exists($fifo)) {
    echo json_encode(['success' => false, 'message' => 'FIFO not found — session may have ended']);
    exit;
}

// Open with O_RDWR ('r+') — never blocks on a FIFO regardless of reader state.
// 'w' (O_WRONLY) would block indefinitely if the SSH process isn't actively
// reading at that exact moment, hanging the HTTP request.
$fp = @fopen($fifo, 'r+');
if ($fp) {
    fwrite($fp, $data);
    fclose($fp);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not write to session']);
}
