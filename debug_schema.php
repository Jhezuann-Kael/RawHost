<?php
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/models/Database.php';

$db = new Database();
$conn = $db->connect();

try {
    $stmt = $conn->query("SHOW CREATE TABLE users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($result);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
