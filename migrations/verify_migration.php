<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

$db = new Database();
$conn = $db->connect();

$query = "SHOW COLUMNS FROM tickets WHERE Field = 'category'";
$stmt = $conn->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Category column type: " . $result['Type'] . "\n";
