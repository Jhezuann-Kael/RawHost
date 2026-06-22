<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    // Check if column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM vps LIKE 'duration'");
    $stmt->execute();
    $exists = $stmt->fetch();

    if (!$exists) {
        $conn->exec("ALTER TABLE vps ADD COLUMN duration INT DEFAULT 24");
        echo "Successfully added 'duration' column to 'vps' table.\n";
    } else {
        echo "'duration' column already exists in 'vps' table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
