<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class MovementRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        // Not typically used for all users, but interface requires it.
        // Could throw exception or return empty.
        return [];
    }

    public function getById($id)
    {
        $query = "SELECT * FROM movements WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        // Create Movement - The trigger will automatically update the user balance
        $query = "INSERT INTO movements (user_id, type, amount, description, created_at) 
                  VALUES (:user_id, :type, :amount, :description, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':type' => $data['type'], // 'IN' or 'OUT'
            ':amount' => $data['amount'],
            ':description' => $data['description']
        ]);

        return $this->conn->lastInsertId();
    }

    // Custom method for specific user
    // Custom method for specific user
    public function getByUserId($userId, $limit = null, $offset = 0)
    {
        $query = "SELECT * FROM movements WHERE user_id = :user_id ORDER BY created_at DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $userId);

        if ($limit !== null) {
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByUserId($userId)
    {
        $query = "SELECT COUNT(*) FROM movements WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchColumn();
    }
}
