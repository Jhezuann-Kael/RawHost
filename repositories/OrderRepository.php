<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class OrderRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $query = "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Order details.
     */
    public function getById($id)
    {
        $query = "SELECT * FROM orders WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new Order.
     * Expected keys in $data: user_id, plan_id, image_os_id, duration, total_amount, status
     */
    public function create(array $data)
    {
        $query = "INSERT INTO orders (user_id, plan_id, vps_id, domain_id, image_os_id, application_id, duration, total_amount, description, currency, status, domain_name, domain_password, product_domain, domain_year, type, created_at, updated_at)
                  VALUES (:user_id, :plan_id, :vps_id, :domain_id, :image_os_id, :application_id, :duration, :total_amount, :description, :currency, :status, :domain_name, :domain_password, :product_domain, :domain_year, :type, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id'        => $data['user_id'],
            ':plan_id'        => $data['plan_id'] ?? null,
            ':vps_id'         => $data['vps_id'] ?? null,
            ':domain_id'      => $data['domain_id'] ?? null,
            ':image_os_id'    => !empty($data['application_id']) ? null : ($data['image_os_id'] ?? null),
            ':application_id' => $data['application_id'] ?? null,
            ':duration'       => $data['duration'] ?? 1,
            ':total_amount'   => $data['total_amount'],
            ':description'    => $data['description'] ?? null,
            ':currency'       => $data['currency'] ?? 'USD',
            ':status'         => $data['status'] ?? 'PENDING',
            ':domain_name'    => $data['domain_name'] ?? null,
            ':domain_password'=> $data['domain_password'] ?? null,
            ':product_domain' => $data['product_domain'] ?? null,
            ':domain_year'    => $data['domain_year'] ?? null,
            ':type'           => $data['type'] ?? 'vps',
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Creates a professional service order paid from user balance.
     * Skips all VPS/domain-specific fields.
     */
    public function createService(int $userId, float $amount, string $description, string $status = 'COMPLETED'): int
    {
        $query = "INSERT INTO orders (user_id, total_amount, description, currency, status, type, created_at, updated_at)
                  VALUES (:user_id, :total_amount, :description, 'USD', :status, 'managed_service', NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id'      => $userId,
            ':total_amount' => $amount,
            ':description'  => $description,
            ':status'       => $status,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function getServicesByUser(int $userId): array
    {
        $query = "SELECT * FROM orders WHERE user_id = :user_id AND type = 'managed_service' ORDER BY created_at DESC";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServicesByUserPaginated(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND type = 'managed_service'"
        );
        $countStmt->execute([':user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->conn->prepare(
            "SELECT * FROM orders WHERE user_id = :user_id AND type = 'managed_service'
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit',   $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders'      => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'       => $total,
            'pages'       => max(1, (int) ceil($total / $perPage)),
            'current_page'=> $page,
            'limit'       => $perPage,
        ];
    }

    // Additional helpful method potentially needed
    public function getByUser($userId)
    {
        $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByVpsId($vpsId)
    {
        $query = "SELECT o.*, p.name as plan_name
                  FROM orders o
                  LEFT JOIN plans p ON o.plan_id = p.id
                  WHERE o.vps_id = :vps_id
                  ORDER BY o.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vps_id', $vpsId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByVpsIdPaginated($vpsId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM orders WHERE vps_id = :vps_id"
        );
        $countStmt->bindParam(':vps_id', $vpsId);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->conn->prepare(
            "SELECT o.*, p.name AS plan_name
               FROM orders o
               LEFT JOIN plans p ON o.plan_id = p.id
              WHERE o.vps_id = :vps_id
           ORDER BY o.created_at DESC
              LIMIT :limit OFFSET :offset"
        );
        $stmt->bindParam(':vps_id', $vpsId);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders'      => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function updateStatus($id, $status)
    {
        $query = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updatePaymentDetails($id, $txHash, $txsData)
    {
        // Assuming your schema currently supports tx_hash. If not, this might fail or need migration.
        // Based on user request "pasalo a php", I assume columns exist or will be added. 
        // Looking at python code: `update_order_payment` updates tx_hash, txs_data, status='COMPLETED'

        $query = "UPDATE orders SET tx_hash = :tx_hash, txs_data = :txs_data, status = 'COMPLETED', updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':tx_hash' => $txHash,
            ':txs_data' => $txsData,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateVpsAndStatus($id, $vpsId, $status)
    {
        $query = "UPDATE orders SET vps_id = :vps_id, status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':vps_id' => $vpsId,
            ':status' => $status,
            ':id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateDomainAndStatus($id, $domainId, $status)
    {
        $query = "UPDATE orders SET domain_id = :domain_id, status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':domain_id' => $domainId,
            ':status'    => $status,
            ':id'        => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function getAllServicesPaginated(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE o.type = 'managed_service'";
        $params = [];

        if ($search !== '') {
            $where .= " AND (u.username LIKE :search OR u.email LIKE :search OR o.description LIKE :search OR o.id LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $where");
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->conn->prepare(
            "SELECT o.*, u.username, u.email
               FROM orders o
               LEFT JOIN users u ON o.user_id = u.id
               $where
           ORDER BY o.created_at DESC
              LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders'       => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'current_page' => $page,
            'limit'        => $perPage,
        ];
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countPendingServices(int $userId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND type = 'managed_service' AND status = 'PENDING'"
        );
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countCompletedOrders($userId)
    {
        $query = "SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND status = 'COMPLETED'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getAdminListPaginated(int $page, int $limit, string $search = '', string $status = '', string $type = ''): array
    {
        $offset = ($page - 1) * $limit;
        $where  = 'WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $where .= ' AND (o.id LIKE :search OR u.username LIKE :search OR u.email LIKE :search OR v.name LIKE :search OR v.ip_address LIKE :search)';
            $params[':search'] = "%$search%";
        }
        if ($status !== '') {
            $where .= ' AND o.status = :status';
            $params[':status'] = $status;
        }
        if ($type !== '') {
            $where .= ' AND o.type = :type';
            $params[':type'] = $type;
        }

        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN vps v   ON o.vps_id  = v.id
             $where"
        );
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->conn->prepare(
            "SELECT o.*, u.username, u.email,
                    v.name AS vps_name, v.ip_address AS vps_ip,
                    p.name AS plan_name
               FROM orders o
               LEFT JOIN users u ON o.user_id = u.id
               LEFT JOIN vps v   ON o.vps_id  = v.id
               LEFT JOIN plans p ON o.plan_id  = p.id
               $where
            ORDER BY o.created_at DESC
               LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders'     => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'total'        => $total,
                'pages'        => max(1, (int) ceil($total / $limit)),
                'current_page' => $page,
                'limit'        => $limit,
            ],
        ];
    }

    public function getAdminStats(): array
    {
        $stmt = $this->conn->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'PENDING'   THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = 'COMPLETED' THEN total_amount ELSE 0 END) AS revenue
             FROM orders"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEngagementStats(): array
    {
        $stmt = $this->conn->query(
            "SELECT
                (SELECT COUNT(DISTINCT user_id) FROM orders)                                                              AS users_with_order,
                (SELECT COUNT(DISTINCT user_id) FROM transactions WHERE status = 'COMPLETED')                             AS users_paid,
                (SELECT COUNT(DISTINCT user_id) FROM transactions WHERE type = 'vps_renew' AND status = 'COMPLETED')      AS users_renewed
            "
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateStatusAndClearVps(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE orders SET status = 'PENDING', vps_id = NULL, updated_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }
}
