<?php
/**
 * Import Script: Update OS names in VPS metadata from plan data
 * 
 * This script fetches all VPS records, looks up their plan's available_os_image_versions,
 * matches the os_image_id to get the correct OS name, and updates the metadata.
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../repositories/VpsRepository.php';
require_once __DIR__ . '/../repositories/PlanRepository.php';

echo "Starting OS name import from plan data...\n\n";

try {
    $vpsRepo = new VpsRepository();
    $planRepo = new PlanRepository();

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
        $planId = $vps['plan_id'];
        $osImageId = $vps['os_image_id'];
        $currentMetadata = json_decode($vps['metadata'] ?? '{}', true);

        echo "Processing VPS #{$vpsId} (Name: {$vps['name']})...\n";

        if (empty($planId)) {
            echo "  ⚠ Skipped: No plan_id found\n\n";
            $skipped++;
            continue;
        }

        if (empty($osImageId)) {
            echo "  ⚠ Skipped: No os_image_id found\n\n";
            $skipped++;
            continue;
        }

        try {
            // Get plan details
            $plan = $planRepo->getById($planId);

            if (!$plan) {
                echo "  ✗ Error: Plan #{$planId} not found\n\n";
                $errors++;
                continue;
            }

            // Parse available OS images from plan (already decoded by PlanRepository)
            $availableOsImages = $plan['available_os_image_versions'] ?? [];

            if (!is_array($availableOsImages)) {
                echo "  ✗ Error: Invalid available_os_image_versions format\n\n";
                $errors++;
                continue;
            }

            // Find matching OS name
            $osName = null;
            foreach ($availableOsImages as $img) {
                if (strval($img['id']) === strval($osImageId)) {
                    $osName = ucwords(strtolower($img['name'] ?? ''));
                    break;
                }
            }

            if (empty($osName)) {
                echo "  ⚠ Warning: OS Image ID {$osImageId} not found in plan's available versions\n";
                echo "  Available IDs: " . implode(', ', array_column($availableOsImages, 'id')) . "\n\n";
                $errors++;
                continue;
            }

            // Update metadata with new OS name
            $currentMetadata['os'] = $osName;

            // Update VPS record
            $success = $vpsRepo->updateMetadata($vpsId, $currentMetadata);

            if ($success) {
                echo "  ✓ Updated OS name to: {$osName}\n\n";
                $updated++;
            } else {
                echo "  ✗ Error: Failed to update metadata\n\n";
                $errors++;
            }

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
