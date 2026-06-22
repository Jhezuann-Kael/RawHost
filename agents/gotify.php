<?php
/**
 * Gotify helper — send push notifications to a user's app token.
 *
 * Usage:
 *   require_once __DIR__ . '/gotify.php';
 *   gotify_send($userToken, 'Title', 'Message body', 7);
 *   gotify_create_app($userId, $username);   // creates app, saves token to DB
 */

require_once __DIR__ . '/../api/config.php';

function gotify_send(string $token, string $title, string $message, int $priority = 5): bool
{
    $ch = curl_init(GOTIFY_URL . '/message?token=' . urlencode($token));
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['title' => $title, 'message' => $message, 'priority' => $priority]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        error_log("Gotify send failed (HTTP $code): $res");
        return false;
    }
    return true;
}

function gotify_create_app(int $userId, string $username): ?string
{
    $ch = curl_init(GOTIFY_URL . '/application');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => GOTIFY_ADMIN_USER . ':' . GOTIFY_ADMIN_PASS,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'name'        => "$username - VPS Alerts",
            'description' => "Notificaciones de VPS para $username",
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        error_log("Gotify create_app failed (HTTP $code): $res");
        return null;
    }

    $data = json_decode($res, true);
    $appId = (int) ($data['id'] ?? 0);
    $token = $data['token'] ?? null;

    if (!$appId || !$token) return null;

    require_once __DIR__ . '/../models/Database.php';
    $db   = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare('UPDATE users SET gotify_app_id = ?, gotify_token = ? WHERE id = ?');
    $stmt->execute([$appId, $token, $userId]);

    return $token;
}
