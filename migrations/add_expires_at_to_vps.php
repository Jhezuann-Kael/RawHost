<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    // Check if column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM vps LIKE 'expires_at'");
    $stmt->execute();
    $exists = $stmt->fetch();

    if (!$exists) {
        $conn->exec("ALTER TABLE vps ADD COLUMN expires_at DATETIME NULL");
        echo "Successfully added 'expires_at' column to 'vps' table.\n";
    } else {
        echo "'expires_at' column already exists in 'vps' table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
