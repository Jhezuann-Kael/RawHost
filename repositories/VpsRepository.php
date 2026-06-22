<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class VpsRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $query = "SELECT * FROM vps ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT v.*, p.name as plan_name, p.metadata as plan_metadata, p.price as plan_price 
                  FROM vps v 
                  LEFT JOIN plans p ON v.plan_id = p.id 
                  WHERE v.id = :id 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $query = "INSERT INTO vps (user_id, plan_id, duration, expires_at, status, name, ip_address, external_id, os_image_id, application_id, metadata, created_at, updated_at) 
                  VALUES (:user_id, :plan_id, :duration, :expires_at, :status, :name, :ip_address, :external_id, :os_image_id, :application_id, :metadata, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':plan_id' => $data['plan_id'] ?? null,
            ':name' => $data['name'],
            ':status' => $data['status'] ?? 'PROVISIONING',
            ':ip_address' => $data['ip_address'],
            ':external_id' => $data['external_id'] ?? null,
            ':os_image_id' => $data['os_image_id'] ?? null,
            ':application_id' => $data['application_id'] ?? null,
            ':duration' => $data['duration'] ?? 0,
            ':expires_at' => $data['expires_at'] ?? null, // Expect calculated date
            ':metadata' => $data['metadata'] ?? json_encode([])
        ]);

        return $this->conn->lastInsertId();
    }

    public function getByUserId($userId)
    {
        $query = "SELECT v.*, p.name as plan_name, p.metadata as plan_metadata, p.price as plan_price 
                  FROM vps v 
                  LEFT JOIN plans p ON v.plan_id = p.id 
                  WHERE v.user_id = :user_id 
                  ORDER BY v.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateDuration($id, $duration)
    {
        $query = "UPDATE vps SET duration = :duration WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':duration' => $duration,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateExpiration($id, $newDate)
    {
        $query = "UPDATE vps SET expires_at = :expires_at WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':expires_at' => $newDate,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus($id, $status)
    {
        $query = "UPDATE vps SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }
    public function updateMetadata($id, $metadata)
    {
        $query = "UPDATE vps SET metadata = :metadata WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':metadata' => json_encode($metadata),
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }
    public function updateOs($id, $osId)
    {
        $query = "UPDATE vps SET os_image_id = :os_image_id, application_id = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':os_image_id' => $osId, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function updateApp($id, $appId)
    {
        $query = "UPDATE vps SET application_id = :application_id, os_image_id = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':application_id' => $appId, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function updatePlan($id, $planId)
    {
        $query = "UPDATE vps SET plan_id = :plan_id WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':plan_id' => $planId,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

public function getExpiredActiveVps()
    {
        $query = "SELECT * FROM vps WHERE status = 'ACTIVE' AND expires_at IS NOT NULL AND expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get VPS that have been suspended for more than X days
     * @param int $days Number of days
     * @return array
     */
    public function getSuspendedVpsOverDays($days = 4)
    {
        // Check VPS that are SUSPENDED and were last updated more than X days ago
        $query = "SELECT * FROM vps 
                  WHERE status = 'SUSPENDED' 
                  AND updated_at IS NOT NULL 
                  AND updated_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
