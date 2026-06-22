<?php
/**
 * Import Script: Fetch all servers from external API and update os_image_id
 * 
 * This script fetches all VPS records from the local database,
 * retrieves their detailed information from the external API,
 * and updates the os_image_id field.
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../repositories/VpsRepository.php';
require_once __DIR__ . '/../services/ExternalApiService.php';

echo "Starting server import to populate os_image_id...\n\n";

try {
    $vpsRepo = new VpsRepository();
    $apiService = new ExternalApiService();

    // Get all VPS records
    $allVps = $vpsRepo->getAll();

    if (empty($allVps)) {
        echo "No VPS records found in database.\n";
        exit(0);
    }

    echo "Found " . count($allVps) . " VPS records to process.\n\n";

    $updated = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($allVps as $vps) {
        $vpsId = $vps['id'];
        $externalId = $vps['external_id'];
        $currentOsImageId = $vps['os_image_id'];

        echo "Processing VPS #{$vpsId} (Name: {$vps['name']})...\n";

        if (empty($externalId)) {
            echo "  ⚠ Skipped: No external_id found\n\n";
            $skipped++;
            continue;
        }

        if (!empty($currentOsImageId)) {
            echo "  ℹ Already has os_image_id: {$currentOsImageId}\n\n";
            $skipped++;
            continue;
        }

        try {
            // Fetch server details from external API
            echo "  → Fetching details from external API (ID: {$externalId})...\n";
            $result = $apiService->getOneServer($externalId);

            if ($result['http_code'] !== 200) {
                echo "  ✗ Error: HTTP {$result['http_code']}\n\n";
                $errors++;
                continue;
            }

            $serverData = $result['response'];

            // Extract os_image_id from response
            $osImageId = null;

            // Check different possible locations in the response
            if (isset($serverData['os_image_id'])) {
                $osImageId = $serverData['os_image_id'];
            } elseif (isset($serverData['data']['os_image_id'])) {
                $osImageId = $serverData['data']['os_image_id'];
            } elseif (isset($serverData['server']['os_image_id'])) {
                $osImageId = $serverData['server']['os_image_id'];
            }

            if (empty($osImageId)) {
                echo "  ⚠ Warning: os_image_id not found in API response\n";
                echo "  Response structure: " . json_encode(array_keys($serverData)) . "\n\n";
                $errors++;
                continue;
            }

            // Update VPS record with os_image_id
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("UPDATE vps SET os_image_id = :os_image_id WHERE id = :id");
            $stmt->execute([
                ':os_image_id' => $osImageId,
                ':id' => $vpsId
            ]);

            echo "  ✓ Updated os_image_id to: {$osImageId}\n\n";
            $updated++;

            // Small delay to avoid overwhelming the API
            usleep(500000); // 0.5 seconds

        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n\n";
            $errors++;
        }
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Import completed!\n";
    echo "  ✓ Updated: {$updated}\n";
    echo "  ⊘ Skipped: {$skipped}\n";
    echo "  ✗ Errors:  {$errors}\n";
    echo str_repeat("=", 50) . "\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
