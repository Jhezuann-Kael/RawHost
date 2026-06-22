<?php
/**
 * Cron: notify users of expiring VPS via Gotify push.
 *
 * Sends alerts at 7 days, 3 days, and 1 day before expiration.
 * Run every hour: 0 * * * * php /var/www/veneko/agents/notify_expiring.php
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/gotify.php';

$db   = new Database();
$conn = $db->connect();

// Thresholds in hours
$thresholds = [
    168 => ['days' => 7,  'priority' => 4],
    72  => ['days' => 3,  'priority' => 6],
    24  => ['days' => 1,  'priority' => 10],
];

$now = new DateTime();

foreach ($thresholds as $hours => $info) {
    $windowStart = (clone $now)->modify("+{$hours} hours");
    $windowEnd   = (clone $windowStart)->modify('+1 hour');

    $stmt = $conn->prepare("
        SELECT v.id, v.name, v.ip_address, v.expires_at,
               u.id AS user_id, u.username, u.gotify_token, u.preferred_currency
        FROM vps v
        JOIN users u ON u.id = v.user_id
        WHERE v.status = 'ACTIVE'
          AND u.gotify_token IS NOT NULL
          AND u.notify_expiry = 1
          AND v.expires_at BETWEEN ? AND ?
    ");
    $stmt->execute([
        $windowStart->format('Y-m-d H:i:s'),
        $windowEnd->format('Y-m-d H:i:s'),
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $days    = $info['days'];
        $expiry  = date('d/m/Y H:i', strtotime($row['expires_at']));
        $vpsName = $row['name'] ?? 'VPS';
        $ip      = $row['ip_address'] ?? '';

        $currency = $row['preferred_currency'] ?? null;
        $payLine  = $currency
            ? "Puedes pagar con $currency desde tu panel."
            : "Puedes renovar con cripto o saldo desde tu panel.";

        $title   = $days <= 1
            ? "🔴 Tu VPS expira en {$days} día — ¡renueva ahora!"
            : "⚠️ Tu VPS expira en {$days} día" . ($days > 1 ? 's' : '');
        $message = "{$vpsName} ({$ip})\nExpira: {$expiry}\n\n{$payLine}\nrawhost.net/dashboard/vps";

        $sent = gotify_send($row['gotify_token'], $title, $message, $info['priority']);

        $status = $sent ? 'OK' : 'FAIL';
        echo "[" . date('Y-m-d H:i:s') . "] [{$status}] user={$row['username']} vps={$vpsName} days={$days}\n";
    }
}
