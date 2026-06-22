<?php
require_once __DIR__ . '/models/Database.php';

$db = new Database();
$conn = $db->connect();

$stmt = $conn->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Columns in users table:\n";
print_r($columns);
