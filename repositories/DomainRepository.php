<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class DomainRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /*
     * Find a domain by ID.
     */
    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM domains WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $domain = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($domain) {
            $domain = $this->decodeJsonFields($domain);
        }

        return $domain;
    }

    public function getAll()
    {
        // Implement if needed, required by interface
        $stmt = $this->conn->query("SELECT * FROM domains");
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'decodeJsonFields'], $domains);
    }

    /*
     * Get all domains for a specific user.
     */
    public function getByUserId($userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM domains WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'decodeJsonFields'], $domains);
    }

    /*
     * Get a domain by external ID.
     */
    public function getByExternalId($externalId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM domains WHERE external_id = :external_id");
        $stmt->execute(['external_id' => $externalId]);
        $domain = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($domain) {
            $domain = $this->decodeJsonFields($domain);
        }

        return $domain;
    }

    /*
     * Create a new domain record.
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO domains (
                    user_id, domain_name, external_id,
                    nameservers, contacts, expiration_date, status, 
                    domain_password, registration_term, product_id, 
                    price_domain, last_checked
                ) VALUES (
                    :user_id, :domain_name, :external_id,
                    :nameservers, :contacts, :expiration_date, :status,
                    :domain_password, :registration_term, :product_id,
                    :price_domain, :last_checked
                )";

        $stmt = $this->conn->prepare($sql);

        // Procesar nameservers (convertir array a JSON si es necesario)
        $nameservers = null;
        if (isset($data['nameservers'])) {
            $nameservers = is_string($data['nameservers'])
                ? $data['nameservers']
                : json_encode($data['nameservers']);
        }

        // Procesar contacts (convertir array a JSON si es necesario)
        $contacts = null;
        if (isset($data['contacts'])) {
            $contacts = is_string($data['contacts'])
                ? $data['contacts']
                : json_encode($data['contacts']);
        }

        $params = [
            'user_id' => $data['user_id'],
            'domain_name' => $data['domain_name'],
            'external_id' => $data['external_id'] ?? null,
            'nameservers' => $nameservers,
            'contacts' => $contacts,
            'expiration_date' => $data['expiration_date'] ?? null,
            'status' => $data['status'] ?? 'PENDING',
            'domain_password' => $data['domain_password'] ?? null,
            'registration_term' => $data['registration_term'] ?? 1,
            'product_id' => $data['product_id'] ?? null,
            'price_domain' => $data['price_domain'] ?? 0.00,
            'last_checked' => $data['last_checked'] ?? date('Y-m-d H:i:s'),
        ];

        if ($stmt->execute($params)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /*
     * Update an existing domain record.
     */
    public function update($id, $data)
    {
        // Procesar campos JSON si están presentes
        if (isset($data['nameservers']) && is_array($data['nameservers'])) {
            $data['nameservers'] = json_encode($data['nameservers']);
        }
        if (isset($data['contacts']) && is_array($data['contacts'])) {
            $data['contacts'] = json_encode($data['contacts']);
        }

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
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute($params);
    }

    /*
     * Delete a domain record.
     */
    public function delete($id)
    {
        $sql = "DELETE FROM domains WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /*
     * Helper method to decode JSON fields in domain records.
     */
    private function decodeJsonFields($domain)
    {
        if (!$domain) {
            return $domain;
        }

        // Decodificar nameservers
        if (isset($domain['nameservers']) && is_string($domain['nameservers'])) {
            $decoded = json_decode($domain['nameservers'], true);
            $domain['nameservers'] = $decoded !== null ? $decoded : [];
        }

        // Decodificar contacts
        if (isset($domain['contacts']) && is_string($domain['contacts'])) {
            $decoded = json_decode($domain['contacts'], true);
            $domain['contacts'] = $decoded !== null ? $decoded : null;
        }

        return $domain;
    }
}
