<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

echo "Starting plan import...\n";

$db = new Database();
$conn = $db->connect();

$apiUrl = EXTERNAL_API_BASE . '/plans/list_plan';
echo "Fetching plans from: $apiUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . EXTERNAL_API_KEY]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch) . "\n");
}
curl_close($ch);

if ($httpCode !== 200) {
    die("API request failed with status code: $httpCode\nResponse: $response\n");
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON Decode Error: " . json_last_error_msg() . "\n");
}

$plans = $data['data'] ?? $data['result'] ?? $data;

if (!is_array($plans)) {
    die("Invalid data format received from API.\n");
}

echo "Found " . count($plans) . " plans. Processing...\n";

$stmtCheck  = $conn->prepare("SELECT id FROM plans WHERE external_id = :external_id LIMIT 1");
$stmtUpdate = $conn->prepare("UPDATE plans SET name = :name, metadata = :metadata, available_os_image_versions = :available_os_image_versions, available_applications = :available_applications, updated_at = NOW() WHERE id = :id");

$countUpdated = 0;
$countSkipped = 0;

foreach ($plans as $plan) {
    $externalId = $plan['external_id'] ?? $plan['id'] ?? $plan['plan_id'] ?? null;
    $name       = $plan['name'] ?? $plan['title'] ?? 'Unknown Plan';
    $rawPrice   = floatval($plan['price'] ?? $plan['amount'] ?? 0);

    if (!$externalId) {
        echo "Skipping plan without ID: " . json_encode($plan) . "\n";
        continue;
    }

    // Extract available_os_image_versions
    if (isset($plan['available_os_image_versions'])) {
        $osImages = is_string($plan['available_os_image_versions'])
            ? json_decode($plan['available_os_image_versions'], true)
            : $plan['available_os_image_versions'];

        // Cheapest plan (4.99 at provider) excludes Windows images
        if (abs($rawPrice - 4.99) < 0.01) {
            $osImages = array_values(array_filter($osImages, fn($img) => stripos($img['name'] ?? '', 'Windows') === false));
        }

        $availableOsImageVersions = json_encode($osImages);
    } else {
        $availableOsImageVersions = null;
    }

    $availableApplications = isset($plan['available_applications'])
        ? json_encode($plan['available_applications'])
        : null;

    $metadata = isset($plan['params'])
        ? (is_string($plan['params']) ? $plan['params'] : json_encode($plan['params']))
        : '{}';

    // Only update existing plans — never create new ones, never touch price/currency
    $stmtCheck->execute([':external_id' => $externalId]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmtUpdate->execute([
            ':name'                        => $name,
            ':metadata'                    => $metadata,
            ':available_os_image_versions' => $availableOsImageVersions,
            ':available_applications'      => $availableApplications,
            ':id'                          => $existing['id'],
        ]);
        $countUpdated++;
    } else {
        echo "Skipped (not found locally): $name ($externalId)\n";
        $countSkipped++;
    }
}

echo "Import complete. Updated: $countUpdated, Skipped: $countSkipped\n";
