<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');   // nginx: disable proxy buffering
header('X-Content-Type-Options: nosniff');
header('Connection: keep-alive');
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level()) ob_end_flush();

set_time_limit(35);

$sessionId = $_GET['session_id'] ?? '';
$offset    = max(0, (int)($_GET['offset'] ?? 0));

$sess = $_SESSION['ssh_sessions'][$sessionId] ?? null;
if (!$sess) {
    echo "data: " . json_encode(['type' => 'error', 'msg' => 'Session not found']) . "\n\n";
    flush();
    exit;
}

$outputLog = $sess['output_log'];
$pidFile   = $sess['pid_file'];
$startTime = time();
$maxTime   = 28; // seconds before we send a reconnect hint

while (true) {
    clearstatcache(true, $outputLog);
    $size = @filesize($outputLog);

    $hadData = false;
    if ($size > $offset) {
        $fp = @fopen($outputLog, 'rb');
        if ($fp) {
            fseek($fp, $offset);
            $chunk  = fread($fp, min($size - $offset, 65536));
            fclose($fp);
            $offset += strlen($chunk);

            echo "data: " . json_encode([
                'type'   => 'output',
                'data'   => base64_encode($chunk),
                'offset' => $offset,
            ]) . "\n\n";
            flush();
            $hadData = true;
        }
    }

    // Check process liveness via /proc
    $pid = (int)@file_get_contents($pidFile);
    if ($pid > 0 && !file_exists("/proc/$pid")) {
        clearstatcache(true, $outputLog);
        $size = @filesize($outputLog);
        if ($size > $offset) {
            $fp = @fopen($outputLog, 'rb');
            if ($fp) {
                fseek($fp, $offset);
                $chunk = fread($fp, $size - $offset);
                fclose($fp);
                echo "data: " . json_encode([
                    'type'   => 'output',
                    'data'   => base64_encode($chunk),
                    'offset' => $size,
                ]) . "\n\n";
                flush();
            }
        }
        echo "data: " . json_encode(['type' => 'closed']) . "\n\n";
        flush();
        exit;
    }

    if ((time() - $startTime) >= $maxTime) {
        echo "data: " . json_encode(['type' => 'reconnect', 'offset' => $offset]) . "\n\n";
        flush();
        exit;
    }

    // Adaptive polling: 10ms after data (more likely coming), 50ms when idle
    usleep($hadData ? 10000 : 50000);
}
