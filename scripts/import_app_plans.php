<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Plans to import by external_id
$TARGET_IDS = [19, 20];

echo "Fetching plans from external provider API...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, EXTERNAL_API_BASE . '/plans/list_plan');
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
    die("API returned HTTP $httpCode\nResponse: $response\n");
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON decode error: " . json_last_error_msg() . "\n");
}

$allPlans = $data['data'] ?? $data['result'] ?? $data;
if (!is_array($allPlans)) {
    die("Unexpected response format.\n");
}

// Index by external_id for quick lookup
$byId = [];
foreach ($allPlans as $p) {
    $eid = $p['external_id'] ?? $p['id'] ?? $p['plan_id'] ?? null;
    if ($eid !== null) {
        $byId[(int)$eid] = $p;
    }
}

$db   = new Database();
$conn = $db->connect();

$stmtCheck  = $conn->prepare("SELECT id FROM plans WHERE external_id = :eid LIMIT 1");
$stmtInsert = $conn->prepare(
    "INSERT INTO plans
        (name, price, currency, metadata, available_os_image_versions, available_applications, external_id, created_at, updated_at)
     VALUES
        (:name, :price, :currency, :metadata, :os_versions, :applications, :eid, NOW(), NOW())"
);
$stmtUpdate = $conn->prepare(
    "UPDATE plans SET
        name = :name,
        metadata = :metadata,
        available_os_image_versions = :os_versions,
        available_applications = :applications,
        updated_at = NOW()
     WHERE id = :id"
);

foreach ($TARGET_IDS as $targetId) {
    if (!isset($byId[$targetId])) {
        echo "Plan $targetId not found in API response — skipping.\n";
        continue;
    }

    $plan  = $byId[$targetId];
    $name  = $plan['name'] ?? $plan['title'] ?? "Plan $targetId";
    $price = floatval($plan['price'] ?? $plan['amount'] ?? 0) + PLAN_MARKUP;

    $metadata = isset($plan['params'])
        ? (is_string($plan['params']) ? $plan['params'] : json_encode($plan['params']))
        : '{}';

    $osVersions = isset($plan['available_os_image_versions'])
        ? (is_string($plan['available_os_image_versions']) ? $plan['available_os_image_versions'] : json_encode($plan['available_os_image_versions']))
        : '[]';

    $applications = isset($plan['available_applications'])
        ? (is_string($plan['available_applications']) ? $plan['available_applications'] : json_encode($plan['available_applications']))
        : '[]';

    $stmtCheck->execute([':eid' => $targetId]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmtUpdate->execute([
            ':name'         => $name,
            ':metadata'     => $metadata,
            ':os_versions'  => $osVersions,
            ':applications' => $applications,
            ':id'           => $existing['id'],
        ]);
        echo "Updated  plan $targetId: $name\n";
    } else {
        $stmtInsert->execute([
            ':name'         => $name,
            ':price'        => $price,
            ':currency'     => 'USD',
            ':metadata'     => $metadata,
            ':os_versions'  => $osVersions,
            ':applications' => $applications,
            ':eid'          => $targetId,
        ]);
        echo "Inserted plan $targetId: $name (price: \$$price USD)\n";
    }
}

echo "Done.\n";
