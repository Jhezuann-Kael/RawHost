<?php
// Script to run the migration for adding RECOMMENDATIONS category

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    $sql = file_get_contents(__DIR__ . '/add_recommendations_category.sql');

    $conn->exec($sql);

    echo "✓ Migration completed successfully: RECOMMENDATIONS category added to tickets table\n";
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
