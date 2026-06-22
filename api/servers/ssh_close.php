<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$sessionId = $_POST['session_id'] ?? '';
$sess = $_SESSION['ssh_sessions'][$sessionId] ?? null;

if ($sess) {
    if (!empty($sess['pid_file']) && file_exists($sess['pid_file'])) {
        $pid = (int)file_get_contents($sess['pid_file']);
        if ($pid > 0) {
            posix_kill($pid, SIGTERM);
            usleep(300000);
            if (file_exists("/proc/$pid")) posix_kill($pid, SIGKILL);
        }
    }
    foreach (['input_fifo', 'output_log', 'pid_file', 'askpass'] as $k) {
        if (!empty($sess[$k])) @unlink($sess[$k]);
    }
    unset($_SESSION['ssh_sessions'][$sessionId]);
}

echo json_encode(['success' => true]);
