<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class AddonRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $query = "SELECT * FROM addons ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT * FROM addons WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUserId($userId)
    {
        $query = "SELECT a.*, v.name as vps_name 
                  FROM addons a 
                  LEFT JOIN vps v ON a.vps_id = v.id 
                  WHERE a.user_id = :user_id 
                  ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByVpsId($vpsId)
    {
        $query = "SELECT * FROM addons WHERE vps_id = :vps_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vps_id', $vpsId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $query = "INSERT INTO addons (user_id, vps_id, type, value, price, status, error_message, external_id, expires_at, created_at, updated_at) 
                  VALUES (:user_id, :vps_id, :type, :value, :price, :status, :error_message, :external_id, :expires_at, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':vps_id' => $data['vps_id'],
            ':type' => $data['type'], // 'IPV4', 'IPV6', or 'STORAGE'
            ':value' => $data['value'] ?? null, // IP address or storage details
            ':price' => $data['price'],
            ':status' => $data['status'] ?? 'PENDING',
            ':error_message' => $data['error_message'] ?? null,
            ':external_id' => $data['external_id'] ?? null,
            ':expires_at' => $data['expires_at'] ?? null
        ]);

        return $this->conn->lastInsertId();
    }

    public function update($id, array $data)
    {
        $query = "UPDATE addons SET ";
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['value'])) {
            $fields[] = "value = :value";
            $params[':value'] = $data['value'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        if (isset($data['error_message'])) {
            $fields[] = "error_message = :error_message";
            $params[':error_message'] = $data['error_message'];
        }
        if (isset($data['external_id'])) {
            $fields[] = "external_id = :external_id";
            $params[':external_id'] = $data['external_id'];
        }
        if (isset($data['expires_at'])) {
            $fields[] = "expires_at = :expires_at";
            $params[':expires_at'] = $data['expires_at'];
        }

        if (empty($fields)) {
            return false;
        }

        $query .= implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function updateStatus($id, $status, $errorMessage = null)
    {
        $query = "UPDATE addons SET status = :status";
        $params = [':status' => $status, ':id' => $id];

        if ($errorMessage) {
            $query .= ", error_message = :error_message";
            $params[':error_message'] = $errorMessage;
        }

        $query .= ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $query = "DELETE FROM addons WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    public function getActiveByVpsId($vpsId)
    {
        $query = "SELECT * FROM addons WHERE vps_id = :vps_id AND status = 'ACTIVE' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vps_id', $vpsId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpiredActiveAddons()
    {
        $query = "SELECT * FROM addons WHERE status = 'ACTIVE' AND expires_at IS NOT NULL AND expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get cumulative monthly price of all active addons for a VPS
     * @param int $vpsId
     * @return float
     */
    public function getCumulativePriceByVpsId($vpsId)
    {
        $query = "SELECT SUM(price) as total_price FROM addons WHERE vps_id = :vps_id AND status = 'ACTIVE'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vps_id', $vpsId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total_price'] ?? 0);
    }

    /**
     * Get all addons that don't have a value assigned yet
     * Used by the IP sync daemon
     * @return array
     */
    public function getAddonsWithoutValue()
    {
        $query = "SELECT * FROM addons WHERE (value IS NULL OR value = '') AND external_id IS NOT NULL ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
