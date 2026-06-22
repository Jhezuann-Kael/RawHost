<?php
require_once __DIR__ . '/../models/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    echo "Columns in vps table:\n";
    $stmt = $conn->query("DESCRIBE vps");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
