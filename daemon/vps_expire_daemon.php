<?php
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../repositories/VpsRepository.php';
require_once __DIR__ . '/../services/ExternalApiService.php';

function daemonLog($message)
{
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
}

daemonLog("Starting VPS Expiration Daemon (Loop mode)...");

// ExternalApiService doesn't hold DB connections, so we can create it once
$externalApi = new ExternalApiService();

while (true) {
    try {
        // Create fresh repository instance each iteration to avoid stale DB connections
        $vpsRepo = new VpsRepository();

        // PART 1: Handle expired ACTIVE VPS (suspend them)
        $expiredVpsList = $vpsRepo->getExpiredActiveVps();
        $expiredCount = count($expiredVpsList);

        if ($expiredCount > 0) {
            daemonLog("Found {$expiredCount} active VPS that have expired.");

            foreach ($expiredVpsList as $vps) {
                $vpsId = $vps['id'];
                $vpsName = $vps['name'];
                $expiresAt = $vps['expires_at'];
                $externalId = $vps['external_id'];

                daemonLog("Suspending VPS ID: {$vpsId} ({$vpsName}), expired at: {$expiresAt}");

                try {
                    // Stop the server remotely
                    if (!empty($externalId)) {
                        $result = $externalApi->serverAction($externalId, 'stop');
                        if ($result['http_code'] === 200) {
                            daemonLog("Server {$externalId} stopped remotely.");
                        } else {
                            daemonLog("Failed to stop server {$externalId} remotely. HTTP: {$result['http_code']}");
                        }
                    }

                    // Update status to SUSPENDED
                    if ($vpsRepo->updateStatus($vpsId, 'SUSPENDED')) {
                        daemonLog("VPS {$vpsId} status updated to SUSPENDED.");
                    } else {
                        daemonLog("Failed to suspend VPS {$vpsId} locally.");
                    }
                } catch (Exception $e) {
                    daemonLog("Error suspending VPS {$vpsId}: " . $e->getMessage());
                }
            }
        }

        // PART 2: Handle VPS that have been SUSPENDED for more than 4 days (terminate them)
        $suspendedVpsList = $vpsRepo->getSuspendedVpsOverDays(4);
        $suspendedCount = count($suspendedVpsList);

        if ($suspendedCount > 0) {
            daemonLog("Found {$suspendedCount} VPS suspended for more than 4 days.");

            foreach ($suspendedVpsList as $vps) {
                $vpsId = $vps['id'];
                $vpsName = $vps['name'];
                $externalId = $vps['external_id'];
                $updatedAt = $vps['updated_at'];

                daemonLog("Terminating VPS ID: {$vpsId} ({$vpsName}), suspended since: {$updatedAt}");

                try {
                    // Delete/terminate the server remotely
                    if (!empty($externalId)) {
                        // Use 'delete' action to terminate the server
                        $result = $externalApi->serverAction($externalId, 'delete');
                        if ($result['http_code'] === 200) {
                            daemonLog("Server {$externalId} terminated remotely.");
                        } else {
                            daemonLog("Failed to terminate server {$externalId} remotely. HTTP: {$result['http_code']}");
                            // Continue anyway to mark it as terminated locally
                        }
                    }

                    // Update status to TERMINATED
                    if ($vpsRepo->updateStatus($vpsId, 'TERMINATED')) {
                        daemonLog("VPS {$vpsId} status updated to TERMINATED.");
                    } else {
                        daemonLog("Failed to terminate VPS {$vpsId} locally.");
                    }
                } catch (Exception $e) {
                    daemonLog("Error terminating VPS {$vpsId}: " . $e->getMessage());
                }
            }
        }

        if ($expiredCount === 0 && $suspendedCount === 0) {
            daemonLog("No VPS to process.");
        }

    } catch (PDOException $e) {
        // Specific handling for database connection errors
        daemonLog("Database Error: " . $e->getMessage());
        daemonLog("Will retry on next iteration...");
    } catch (Exception $e) {
        daemonLog("Error: " . $e->getMessage());
    }

    // Wait 60 seconds before next check
    daemonLog("Waiting 60 seconds before next check...");
    sleep(60);
}

