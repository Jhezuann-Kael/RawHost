<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

require_once __DIR__ . '/UserRepositoryInterface.php';

class UserRepository implements UserRepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $query = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $query = "INSERT INTO users (username, email, password, telegram_id, referred_by, referral_code, created_at, updated_at) 
                  VALUES (:username, :email, :password, :telegram_id, :referred_by, :referral_code, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':username' => $data['username'],
            ':email' => isset($data['email']) ? $data['email'] : null,
            ':password' => $data['password_hash'],
            ':telegram_id' => isset($data['telegram_id']) ? $data['telegram_id'] : null,
            ':referred_by' => isset($data['referred_by']) ? $data['referred_by'] : null,
            ':referral_code' => isset($data['referral_code']) ? $data['referral_code'] : null
        ]);

        return $this->conn->lastInsertId();
    }

    public function findByUsername($username)
    {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByTelegramId($telegramId)
    {
        $query = "SELECT * FROM users WHERE telegram_id = :telegram_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':telegram_id', $telegramId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email)
    {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function exists($username, $email)
    {
        $query = "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function findByCode($code)
    {
        $query = "SELECT * FROM users WHERE code = :code LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByReferralCode($code)
    {
        $query = "SELECT * FROM users WHERE referral_code = :code LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function clearCode($userId)
    {
        $query = "UPDATE users SET code = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    public function setReferralCode($userId, $code)
    {
        $query = "UPDATE users SET referral_code = :code WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':code' => $code,
            ':id' => $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function countReferrals($userId)
    {
        $query = "SELECT COUNT(*) FROM users WHERE referred_by = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function countValidReferrals($userId)
    {
        // A valid referral is a user who was referred by $userId AND has at least one COMPLETED order
        $query = "SELECT COUNT(DISTINCT u.id) 
                  FROM users u 
                  INNER JOIN orders o ON u.id = o.user_id 
                  WHERE u.referred_by = :id AND o.status = 'COMPLETED'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function updatePassword($userId, $passwordHash)
    {
        $query = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':password' => $passwordHash,
            ':id' => $userId
        ]);
        return $stmt->rowCount() > 0;
    }

    // Admin methods
    public function getUserWithDetails($userId)
    {
        $query = "SELECT id, username, email, telegram_id, tg_username, balance, is_superuser, referral_code, preferred_currency, support_blocked, notify_expiry, notify_metrics, alert_cpu_threshold, alert_ram_threshold, alert_disk_threshold, created_at, updated_at
                  FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserVPS($userId)
    {
        $query = "SELECT 
                    v.id,
                    v.name,
                    v.ip_address,
                    v.status,
                    v.external_id,
                    v.os_image_id,
                    v.application_id,
                    v.created_at,
                    v.updated_at,
                    JSON_UNQUOTE(JSON_EXTRACT(v.metadata, '$.os_image_version.name')) as os_name
                  FROM vps v
                  WHERE v.user_id = :user_id
                  ORDER BY v.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserTickets($userId)
    {
        $query = "SELECT id, subject, category, status, priority, created_at, updated_at
                  FROM tickets
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update user data (admin)
     * @param int $userId
     * @param array $data Array with fields to update (username, email, is_superuser, balance)
     * @return bool
     */
    public function update($userId, array $data)
    {
        $allowedFields = ['username', 'display_name', 'email', 'is_superuser', 'balance', 'telegram_id', 'tg_username', 'referred_by', 'referral_code', 'preferred_currency', 'auto_renew', 'support_blocked', 'notify_expiry', 'notify_metrics', 'alert_cpu_threshold', 'alert_ram_threshold', 'alert_disk_threshold'];
        $updates = [];
        $params = [':id' => $userId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Delete user (admin)
     * @param int $userId
     * @return bool
     */
    public function delete($userId)
    {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
}
