<?php
/**
 * Pull SolusVM metrics for all active VPS and send Gotify alerts
 * when CPU, RAM or disk exceed configured thresholds.
 *
 * Run manually:  screen -dmS metrics php /var/www/veneko/agents/notify_metrics.php
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../services/ExternalApiService.php';
require_once __DIR__ . '/gotify.php';

$db   = new Database();
$conn = $db->connect();

$api      = new ExternalApiService();
$lang     = [];
$interval = 15 * 60; // 15 minutos
$defaultL = load_lang('en');

function load_lang(string $locale): array
{
    $file = __DIR__ . '/../languages/' . $locale . '.php';
    return file_exists($file) ? require $file : require __DIR__ . '/../languages/en.php';
}

function t(array $lang, string $key, array $replace = []): string
{
    $str = $lang[$key] ?? $key;
    foreach ($replace as $k => $v) {
        $str = str_replace(':' . $k, $v, $str);
    }
    return $str;
}

// SolusVM item structures (zeros are filler between real readings):
// cpu:    items[].load_average  (% — may exceed 100 on multi-core)
// memory: items[].memory        (MB used)
// disk:   response.data.actual_size (bytes)

function solusvm_last_cpu(array $items): ?float {
    foreach (array_reverse($items) as $item) {
        if (!empty($item['load_average'])) {
            return round(min((float) $item['load_average'], 100), 1);
        }
    }
    return null;
}

function solusvm_last_ram_mb(array $items): ?float {
    foreach (array_reverse($items) as $item) {
        if (!empty($item['memory'])) {
            return (float) $item['memory'];
        }
    }
    return null;
}

function solusvm_disk_gb($d): ?float {
    if (empty($d)) return null;
    $item  = $d[0] ?? $d;
    $bytes = $item['actual_size'] ?? $item['used'] ?? null;
    return $bytes !== null ? round($bytes / 1073741824, 2) : null;
}

while (true) {
$stmt = $conn->query("
    SELECT v.id, v.name, v.external_id,
           u.gotify_token, u.language,
           u.alert_cpu_threshold, u.alert_ram_threshold, u.alert_disk_threshold,
           ac.alerts,
           p.metadata AS plan_metadata
    FROM vps v
    JOIN users u ON u.id = v.user_id
    LEFT JOIN agent_config ac ON ac.vps_id = v.id
    LEFT JOIN plans p ON p.id = v.plan_id
    WHERE v.status = 'ACTIVE'
      AND u.gotify_token IS NOT NULL
      AND u.notify_metrics = 1
      AND v.external_id IS NOT NULL
      AND v.external_id != ''
");

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $alerts   = $row['alerts']        ? json_decode($row['alerts'],        true) : [];
    $planMeta = $row['plan_metadata'] ? json_decode($row['plan_metadata'], true) : [];
    $extId    = $row['external_id'];
    $token    = $row['gotify_token'];
    $vpsName  = $row['name'] ?? "VPS #{$row['id']}";
    $locale   = $row['language'] ?? 'en';

    if (!isset($lang[$locale])) {
        $lang[$locale] = load_lang($locale);
    }
    $L = $lang[$locale];

    // Per-VPS override → user-level default (both in %)
    $cpuThreshold  = $alerts['cpu_threshold']  ?? (int) $row['alert_cpu_threshold'];
    $ramThreshold  = $alerts['ram_threshold']  ?? (int) $row['alert_ram_threshold'];
    $diskThreshold = $alerts['disk_threshold'] ?? (int) $row['alert_disk_threshold'];

    // Plan capacity: agent_config override → plan metadata (ram bytes→MB, disk GB)
    $planRamMb  = $alerts['plan_ram_mb']  ?? (isset($planMeta['ram'])  ? round($planMeta['ram'] / 1048576) : null);
    $planDiskGb = $alerts['plan_disk_gb'] ?? ($planMeta['disk'] ?? null);

    // CPU — API returns response.data.items
    $cpuRes = $api->getServerUsage($extId, 'cpu');
    if ($cpuRes['http_code'] === 200) {
        $cpuPct = solusvm_last_cpu($cpuRes['response']['data']['items'] ?? []);
        if ($cpuPct !== null && $cpuPct >= $cpuThreshold) {
            gotify_send(
                $token,
                t($L, 'notif_cpu_title', ['vps' => $vpsName]),
                t($L, 'notif_cpu_body',  ['pct' => $cpuPct, 'threshold' => $cpuThreshold]),
                9
            );
            echo "[" . date('Y-m-d H:i:s') . "] [CPU] " . t($L, 'notif_sent') . "\n";
        }
    }

    // RAM — API returns response.data.items; SolusVM gives MB used, plan total from metadata
    $ramRes = $api->getServerUsage($extId, 'memory');
    if ($ramRes['http_code'] === 200) {
        $ramMb = solusvm_last_ram_mb($ramRes['response']['data']['items'] ?? []);
        if ($ramMb !== null && $planRamMb !== null && $planRamMb > 0) {
            $ramPct = round($ramMb / $planRamMb * 100, 1);
            if ($ramPct >= $ramThreshold) {
                gotify_send(
                    $token,
                    t($L, 'notif_ram_title', ['vps' => $vpsName]),
                    t($L, 'notif_ram_body',  ['pct' => $ramPct, 'threshold' => $ramThreshold]),
                    9
                );
                echo "[" . date('Y-m-d H:i:s') . "] [RAM] " . t($L, 'notif_sent') . "\n";
            }
        }
    }

    // Disco — API returns response.data.actual_size in bytes
    $diskRes = $api->getDetailDisk($extId);
    if ($diskRes['http_code'] === 200) {
        $diskGb = solusvm_disk_gb($diskRes['response']['data'] ?? $diskRes['response']);
        if ($diskGb !== null && $planDiskGb !== null && $planDiskGb > 0) {
            $diskPct = round($diskGb / $planDiskGb * 100, 1);
            if ($diskPct >= $diskThreshold) {
                gotify_send(
                    $token,
                    t($L, 'notif_disk_title', ['vps' => $vpsName]),
                    t($L, 'notif_disk_body',  ['pct' => $diskPct, 'threshold' => $diskThreshold]),
                    9
                );
                echo "[" . date('Y-m-d H:i:s') . "] [DISK] " . t($L, 'notif_sent') . "\n";
            }
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] " . t($L, 'daemon_checked', ['vps' => $vpsName]) . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] " . t($defaultL, 'daemon_cycle', ['seconds' => $interval]) . "\n";
sleep($interval);
}
