<?php
// Silence PHP errors — any HTML output would corrupt the SSE stream
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];
$is_superuser = $_SESSION['is_superuser'] ?? false;
session_write_close(); // Release session lock for other requests

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level()) ob_end_flush();

set_time_limit(600);

function sse(string $type, string $msg): void {
    // Strip invalid bytes so json_encode never returns false (iconv used; mbstring not required)
    $safe = @iconv('UTF-8', 'UTF-8//IGNORE', $msg);
    if ($safe === false) $safe = '';
    echo 'data: ' . json_encode(['type' => $type, 'msg' => $safe]) . "\n\n";
    flush();
}

function sse_done(bool $ok, string $msg = ''): void {
    echo 'data: ' . json_encode(['type' => 'done', 'ok' => $ok, 'msg' => $msg]) . "\n\n";
    flush();
}

$server_id = (int)($_GET['server_id'] ?? 0);
$script_id  = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['script_id'] ?? ''));

if (!$server_id || !$script_id) {
    sse('error', 'Missing server_id or script_id');
    sse_done(false);
    exit;
}

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $user_id && !$is_superuser)) {
    sse('error', 'Unauthorized or VPS not found');
    sse_done(false);
    exit;
}

if ($vps['status'] !== 'ACTIVE') {
    sse('error', 'VPS must be ACTIVE to run scripts');
    sse_done(false);
    exit;
}

// Load catalog and find script
$catalogPath = __DIR__ . '/../../vps_scripts/scripts_catalog.json';
$catalog = json_decode(file_get_contents($catalogPath), true) ?? [];
$scriptMeta = null;
foreach ($catalog as $s) {
    if ($s['id'] === $script_id) {
        $scriptMeta = $s;
        break;
    }
}

if (!$scriptMeta) {
    sse('error', 'Script not found in catalog');
    sse_done(false);
    exit;
}

$remoteScriptPath = __DIR__ . '/../../vps_scripts/' . $scriptMeta['remote_file'];
if (!file_exists($remoteScriptPath)) {
    sse('error', 'Script file missing on server');
    sse_done(false);
    exit;
}

$meta = json_decode($vps['metadata'] ?? '{}', true);
$ip       = $vps['ip_address'] ?? '';
$password = $meta['password'] ?? '';
$sshUser  = 'root';

if (!$ip || $ip === 'Pending') {
    sse('error', 'VPS has no IP address assigned yet');
    sse_done(false);
    exit;
}

// Build SSH_ASKPASS helper — echoes password without exposing it in the command line
$tmpAsk = tempnam(sys_get_temp_dir(), 'vask_');
file_put_contents($tmpAsk, "#!/bin/sh\nprintf '%s' " . escapeshellarg($password) . "\n");
chmod($tmpAsk, 0700);
// Clean up after script finishes (not immediately — SSH reads the file during auth)
register_shutdown_function(function () use ($tmpAsk) { @unlink($tmpAsk); });

$scriptContent = file_get_contents($remoteScriptPath);

// Inject validated args as exported env vars at the top of the script
if (!empty($scriptMeta['args']) && !empty($_GET['args']) && is_array($_GET['args'])) {
    $allowedArgs = [];
    foreach ($scriptMeta['args'] as $argDef) {
        $allowedArgs[$argDef['id']] = $argDef;
    }

    $envLines = '';
    foreach ($_GET['args'] as $key => $value) {
        // Key must be in catalog definition
        if (!isset($allowedArgs[$key])) continue;
        // Key must be a valid env var name
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) continue;

        $argDef = $allowedArgs[$key];
        if ($argDef['type'] === 'select') {
            // Value must be one of the defined options
            $validValues = array_column($argDef['options'], 'value');
            if (!in_array($value, $validValues, true)) continue;
        }

        $envLines .= 'export ' . $key . '=' . escapeshellarg($value) . "\n";
    }

    if ($envLines !== '') {
        $scriptContent = "#!/usr/bin/env bash\n" . $envLines . "\n" .
            preg_replace('/^#!.*\n/', '', $scriptContent, 1);
    }
}

$env = [
    'SSH_ASKPASS'        => $tmpAsk,
    'SSH_ASKPASS_REQUIRE' => 'force',   // OpenSSH 8.4+ skips the DISPLAY check
    'DISPLAY'            => 'dummy',    // Older OpenSSH fallback
    'HOME'               => sys_get_temp_dir(), // Writable home so SSH can manage state
    'PATH'               => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
];

$sshTarget = escapeshellarg($sshUser . '@' . $ip);
$cmd = "setsid ssh -o StrictHostKeyChecking=no -o BatchMode=no"
     . " -o PasswordAuthentication=yes -o ConnectTimeout=20"
     . " -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR $sshTarget 'bash -s'";

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

sse('output', "Connecting to {$ip}...\r\n");

$proc = proc_open($cmd, $descriptors, $pipes, null, $env);

if (!is_resource($proc)) {
    sse('error', 'Failed to start SSH process');
    sse_done(false);
    exit;
}

fwrite($pipes[0], $scriptContent);
fclose($pipes[0]);

stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

$startTime = time();
$maxSeconds = 540;

$lastHeartbeat = time();

while (true) {
    $status = proc_get_status($proc);

    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);

    if ($out !== false && $out !== '') sse('output', $out);
    if ($err !== false && $err !== '') sse('output', $err);

    // Heartbeat every 20s to prevent Cloudflare 524 timeout
    if (time() - $lastHeartbeat > 20) {
        echo ": heartbeat\n\n";
        flush();
        $lastHeartbeat = time();
    }

    if (!$status['running']) break;

    if ((time() - $startTime) > $maxSeconds) {
        proc_terminate($proc);
        sse('error', 'Timeout: script exceeded 9 minutes');
        sse_done(false);
        foreach ($pipes as $p) @fclose($p);
        exit;
    }

    usleep(120000); // 120ms polling
}

// Drain remaining output
$out = stream_get_contents($pipes[1]);
$err = stream_get_contents($pipes[2]);
if ($out) sse('output', $out);
if ($err) sse('output', $err);

foreach ($pipes as $p) @fclose($p);
$exitCode = proc_close($proc);

sse_done($exitCode === 0, $exitCode === 0 ? 'Script completed successfully' : "Script exited with code $exitCode");
