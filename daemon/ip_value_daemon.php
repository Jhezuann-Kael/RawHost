<?php
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../repositories/AddonRepository.php';
require_once __DIR__ . '/../repositories/VpsRepository.php';
require_once __DIR__ . '/../services/ExternalApiService.php';

function daemonLog($message)
{
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
}

daemonLog("Starting IP Value Sync Daemon (Loop mode)...");

// ExternalApiService doesn't hold DB connections, so we can create it once
$externalApi = new ExternalApiService();

while (true) {
    try {
        // Create fresh repository instances each iteration to avoid stale DB connections
        $addonRepo = new AddonRepository();
        $vpsRepo = new VpsRepository();

        // Get all addons that don't have an IP assigned (value is NULL or empty)
        $addonsWithoutIp = $addonRepo->getAddonsWithoutValue();
        $count = count($addonsWithoutIp);

        if ($count > 0) {
            daemonLog("Found {$count} addons without IP assigned.");

            foreach ($addonsWithoutIp as $addon) {
                $addonId = $addon['id'];
                $vpsId = $addon['vps_id'];
                $externalAddonId = $addon['external_id'];

                // Get VPS to obtain its external_id
                $vps = $vpsRepo->getById($vpsId);
                if (!$vps || empty($vps['external_id'])) {
                    daemonLog("VPS not found or missing external_id for addon ID: {$addonId}");
                    continue;
                }

                $externalServerId = $vps['external_id'];

                daemonLog("Checking addon ID: {$addonId} (External ID: {$externalAddonId}) for VPS: {$externalServerId}");

                try {
                    // Call the external API to get server addons
                    $result = $externalApi->listServerAddons($externalServerId);

                    if ($result['http_code'] !== 200) {
                        daemonLog("API returned HTTP {$result['http_code']} for server {$externalServerId}");
                        continue;
                    }

                    $serverAddons = $result['response'];

                    // Check if response contains data
                    if (!isset($serverAddons['data']) || !is_array($serverAddons['data'])) {
                        daemonLog("No addon data received for server {$externalServerId}");
                        continue;
                    }

                    // Find matching addon by external_id
                    $matchedAddon = null;
                    foreach ($serverAddons['data'] as $serverAddon) {
                        if (isset($serverAddon['id']) && $serverAddon['id'] == $externalAddonId) {
                            $matchedAddon = $serverAddon;
                            break;
                        }
                    }

                    if ($matchedAddon && isset($matchedAddon['assigned_value']) && !empty($matchedAddon['assigned_value'])) {
                        $assignedValue = $matchedAddon['assigned_value'];

                        daemonLog("Found IP assignment: {$assignedValue} for addon ID: {$addonId}");

                        // Update the addon with the assigned value
                        if ($addonRepo->update($addonId, ['value' => $assignedValue])) {
                            daemonLog("Successfully updated addon ID: {$addonId} with IP: {$assignedValue}");

                            // Optionally update status to ACTIVE if it's still PENDING
                            if ($addon['status'] === 'PENDING') {
                                $addonRepo->updateStatus($addonId, 'ACTIVE');
                                daemonLog("Updated addon ID: {$addonId} status to ACTIVE");
                            }
                        } else {
                            daemonLog("Failed to update addon ID: {$addonId}");
                        }
                    } else {
                        daemonLog("No assigned IP found for addon ID: {$addonId} (External ID: {$externalAddonId})");
                    }

                } catch (Exception $e) {
                    daemonLog("Error processing addon ID {$addonId}: " . $e->getMessage());
                }
            }
        } else {
            daemonLog("No addons without IP found.");
        }

    } catch (PDOException $e) {
        // Specific handling for database connection errors
        daemonLog("Database Error: " . $e->getMessage());
        daemonLog("Will retry on next iteration...");
    } catch (Exception $e) {
        daemonLog("Error: " . $e->getMessage());
    }

    // Wait 30 seconds before next check
    daemonLog("Waiting 30 seconds before next check...");
    sleep(30);
}
