<?php

class Plan
{
    private $conn;
    public $id;
    public $name;
    public $price;
    public $currency;
    public $cpu;
    public $ram;
    public $disk;
    public $isFeatured;
    public $externalId;

    public function __construct($db)
    {
        $this->conn = $db->connect();
    }

    // Save or Update plan in DB
    public function save()
    {
        // Prepare metadata
        $metadataArr = [
            'cpu' => $this->cpu,
            'ram' => $this->ram,
            'disk' => $this->disk,
            'is_featured' => $this->isFeatured
        ];
        $metadataJson = json_encode($metadataArr);

        // Check if external ID exists
        $query = "SELECT id FROM plans WHERE external_id = :external_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':external_id', $this->externalId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Update
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $query = "UPDATE plans SET name = :name, price = :price, currency = :currency, metadata = :metadata, updated_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
        } else {
            // Insert
            $query = "INSERT INTO plans (name, price, currency, metadata, external_id, created_at, updated_at) VALUES (:name, :price, :currency, :metadata, :external_id, NOW(), NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':external_id', $this->externalId);
        }

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':currency', $this->currency);
        $stmt->bindParam(':metadata', $metadataJson);

        return $stmt->execute();
    }

    // Get all plans from DB
    public function getAll()
    {
        $query = "SELECT * FROM plans ORDER BY price ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $plans = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $meta = json_decode($row['metadata'] ?? '{}', true);

            // Map row to object structure for frontend
            $plans[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'currency' => $row['currency'],
                'cpu' => $meta['cpu'] ?? $meta['vcpu'] ?? 0, // Handle diverse keys if needed
                'ram' => $meta['ram'] ?? 0,
                'disk' => $meta['disk'] ?? 0,
                'isFeatured' => (bool) ($meta['is_featured'] ?? false),
                'available_os_image_versions' => json_decode($row['available_os_image_versions'] ?? '[]', true)
            ];
        }
        return $plans;
    }
}
