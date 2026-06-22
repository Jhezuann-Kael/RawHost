<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class PlanRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $query = "SELECT * FROM plans ORDER BY price ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $plans = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $meta = json_decode($row['metadata'] ?? '{}', true);

            $plans[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'currency' => $row['currency'],
                'cpu' => $meta['cpu'] ?? $meta['vcpu'] ?? 0,
                'ram' => isset($meta['ram']) ? ($meta['ram'] / 1073741824) : 0,
                'disk' => $meta['disk'] ?? 0,
                'isFeatured' => (bool) ($meta['is_featured'] ?? false),
                'available_os_image_versions' => json_decode($row['available_os_image_versions'] ?? '[]', true),
                'available_applications' => json_decode($row['available_applications'] ?? '[]', true),
                'external_id' => $row['external_id'],
                'fees' => $this->getFeesByPlanId($row['id'])
            ];
        }
        return $plans;
    }

    public function getById($id)
    {
        $query = "SELECT * FROM plans WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            return null;

        $meta = json_decode($row['metadata'] ?? '{}', true);

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'currency' => $row['currency'],
            'cpu' => $meta['cpu'] ?? $meta['vcpu'] ?? 0,
            'ram' => isset($meta['ram']) ? ($meta['ram'] / 1073741824) : 0,
            'disk' => $meta['disk'] ?? 0,
            'available_os_image_versions' => json_decode($row['available_os_image_versions'] ?? '[]', true),
            'available_applications' => json_decode($row['available_applications'] ?? '[]', true),
            'external_id' => $row['external_id'],
            'fees' => $this->getFeesByPlanId($row['id'])
        ];
    }

    public function create(array $data)
    {
        $query = "INSERT INTO plans (name, price, currency, metadata, available_os_image_versions, external_id, created_at, updated_at)
                  VALUES (:name, :price, :currency, :metadata, :available_os_image_versions, :external_id, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':currency' => $data['currency'] ?? 'USD',
            ':metadata' => is_array($data['metadata']) ? json_encode($data['metadata']) : $data['metadata'],
            ':available_os_image_versions' => is_array($data['available_os_image_versions']) ? json_encode($data['available_os_image_versions']) : $data['available_os_image_versions'],
            ':external_id' => $data['external_id']
        ]);

        return $this->conn->lastInsertId();
    }

    public function getFeesByPlanId($planId)
    {
        $query = "SELECT id, name, type, billing_type, value, currency FROM plan_fees WHERE plan_id = :plan_id ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plan_id', $planId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addFee($planId, array $fee)
    {
        $query = "INSERT INTO plan_fees (plan_id, name, type, billing_type, value, currency) VALUES (:plan_id, :name, :type, :billing_type, :value, :currency)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':plan_id'      => $planId,
            ':name'         => $fee['name'],
            ':type'         => $fee['type'],
            ':billing_type' => $fee['billing_type'] ?? 'recurring',
            ':value'        => $fee['value'],
            ':currency'     => $fee['currency'] ?? 'USD'
        ]);

        return $this->conn->lastInsertId();
    }

    public function updateFee($feeId, array $fee)
    {
        $query = "UPDATE plan_fees SET name = :name, type = :type, billing_type = :billing_type, value = :value, currency = :currency, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id'           => $feeId,
            ':name'         => $fee['name'],
            ':type'         => $fee['type'],
            ':billing_type' => $fee['billing_type'] ?? 'recurring',
            ':value'        => $fee['value'],
            ':currency'     => $fee['currency'] ?? 'USD'
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteFee($feeId)
    {
        $query = "DELETE FROM plan_fees WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $feeId);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
