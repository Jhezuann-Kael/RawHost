<?php
require_once __DIR__ . '/Database.php';

class Domain
{
    private $pdo;

    public function __construct()
    {
        $db = new Database();
        $this->pdo = $db->connect();
    }

    /*
     * Find a domain by ID.
     */
    public static function findById($id)
    {
        $db = new Database();
        $pdo = $db->connect();
        $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
     * Get all domains for a specific user.
     */
    public static function getByUserId($userId)
    {
        $db = new Database();
        $pdo = $db->connect();
        $stmt = $pdo->prepare("SELECT * FROM domains WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Create a new domain record.
     */
    public static function create($data)
    {
        $db = new Database();
        $pdo = $db->connect();
        $sql = "INSERT INTO domains (
                    user_id, domain_name, expiration_date, status, 
                    domain_password, registration_term, product_id, 
                    price_domain, last_checked
                ) VALUES (
                    :user_id, :domain_name, :expiration_date, :status,
                    :domain_password, :registration_term, :product_id,
                    :price_domain, :last_checked
                )";

        $stmt = $pdo->prepare($sql);

        $params = [
            'user_id' => $data['user_id'],
            'domain_name' => $data['domain_name'],
            'expiration_date' => $data['expiration_date'] ?? null,
            'status' => $data['status'] ?? 'PENDING',
            'domain_password' => $data['domain_password'] ?? null,
            'registration_term' => $data['registration_term'] ?? 1,
            'product_id' => $data['product_id'] ?? null,
            'price_domain' => $data['price_domain'] ?? 0.00,
            'last_checked' => $data['last_checked'] ?? date('Y-m-d H:i:s'),
        ];

        if ($stmt->execute($params)) {
            return $pdo->lastInsertId();
        }
        return false;
    }

    /*
     * Update an existing domain record.
     */
    public static function update($id, $data)
    {
        $db = new Database();
        $pdo = $db->connect();

        // Build query dynamically based on provided data
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id') { // Prevent updating ID
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $sql = "UPDATE domains SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }
}
