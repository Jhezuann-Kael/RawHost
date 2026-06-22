<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

$db = new Database();
$conn = $db->connect();

try {
    echo "Migrating users table...\n";

    // 1. Make telegram_id nullable
    // Check if it's already nullable or just try to modify it
    // MODIFY COLUMN telegram_id VARCHAR(50) NULL
    $sql1 = "ALTER TABLE users MODIFY telegram_id VARCHAR(50) NULL";
    $conn->exec($sql1);
    echo "Modified telegram_id to be NULLABLE.\n";

    // 2. Add password column if not exists
    // It's harder to check "IF NOT EXISTS" in ALTER TABLE in pure SQL in some versions,
    // so we'll check schema first or try/catch.
    try {
        $sql2 = "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email";
        $conn->exec($sql2);
        echo "Added password column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Password column already exists.\n";
        } else {
            throw $e;
        }
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
