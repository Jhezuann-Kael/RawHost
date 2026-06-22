<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../models/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    $userId = 2;
    $amount = 100;

    // 1. Get current balance
    $stmt = $conn->prepare("SELECT balance, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User ID $userId not found.\n");
    }

    echo "User: " . $user['username'] . "\n";
    echo "Current Balance: $" . $user['balance'] . "\n";

    // 2. Update balance
    $update = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $update->execute([$amount, $userId]);

    // 3. Verify
    $stmt->execute([$userId]);
    $newUser = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "New Balance: $" . $newUser['balance'] . "\n";
    echo "Successfully added $$amount.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
