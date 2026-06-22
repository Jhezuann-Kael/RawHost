<?php
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

function daemonLog($message)
{
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
}

daemonLog("Starting Transaction Expiration Daemon (Loop mode)...");

while (true) {
    try {
        $db   = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare(
            "UPDATE transactions
             SET status = 'EXPIRED', updated_at = NOW()
             WHERE status = 'PENDING'
               AND expired_at > 0
               AND expired_at <= UNIX_TIMESTAMP()"
        );
        $stmt->execute();
        $expired = $stmt->rowCount();

        if ($expired > 0) {
            daemonLog("Expired {$expired} pending transaction(s).");
        } else {
            daemonLog("No pending transactions to expire.");
        }

    } catch (PDOException $e) {
        daemonLog("Database Error: " . $e->getMessage());
        daemonLog("Will retry on next iteration...");
    } catch (Exception $e) {
        daemonLog("Error: " . $e->getMessage());
    }

    daemonLog("Waiting 15 minutes before next check...");
    sleep(900);
}
