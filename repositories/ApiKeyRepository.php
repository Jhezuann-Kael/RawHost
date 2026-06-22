<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class ApiKeyRepository
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getUserIdByApiKey(string $apiKey): ?int
    {
        $stmt = $this->conn->prepare('SELECT user_id FROM apikeys WHERE apikey = ? LIMIT 1');
        $stmt->execute([$apiKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['user_id'] : null;
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT id, name, apikey, created_at FROM apikeys WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $userId, string $name): string
    {
        $key = bin2hex(random_bytes(24));
        $stmt = $this->conn->prepare('INSERT INTO apikeys (user_id, name, apikey) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $name, $key]);
        return $key;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM apikeys WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
