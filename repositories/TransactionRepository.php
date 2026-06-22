<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class TransactionRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $query = "SELECT * FROM transactions ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPaginated($limit = 10, $offset = 0, $search = null, $status = null, $type = null)
    {
        $query = "SELECT t.*, u.username, u.email,
                         CAST(JSON_UNQUOTE(JSON_EXTRACT(t.order_metadata, '$.duration')) AS UNSIGNED) as duration,
                         (SELECT p.price FROM plans p
                          WHERE p.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(t.order_metadata, '$.plan_id')) AS UNSIGNED)
                          LIMIT 1) as plan_price
                  FROM transactions t
                  LEFT JOIN users u ON t.user_id = u.id
                  WHERE (u.is_superuser = 0 OR u.is_superuser IS NULL)";

        $params = [];

        if ($status) {
            $query .= " AND t.status = :status";
            $params[':status'] = $status;
        }

        if ($type) {
            $query .= " AND t.type = :type";
            $params[':type'] = $type;
        }

        if ($search) {
            $query .= " AND (t.id LIKE :search OR t.track_id LIKE :search OR u.username LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $query .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = null, $status = null, $type = null)
    {
        $query = "SELECT COUNT(*) FROM transactions t
                  LEFT JOIN users u ON t.user_id = u.id
                  WHERE (u.is_superuser = 0 OR u.is_superuser IS NULL)";

        $params = [];
        if ($status) {
            $query .= " AND t.status = :status";
            $params[':status'] = $status;
        }
        if ($type) {
            $query .= " AND t.type = :type";
            $params[':type'] = $type;
        }
        if ($search) {
            $query .= " AND (t.id LIKE :search OR t.track_id LIKE :search OR u.username LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getFinancialStats()
    {
        $noSuperuser = "LEFT JOIN users u ON t.user_id = u.id WHERE (u.is_superuser = 0 OR u.is_superuser IS NULL)";

        // Base plan price for inferring duration on recharge transactions (no order_metadata)
        $priceStmt = $this->conn->prepare("SELECT MIN(price) FROM plans WHERE price > 0");
        $priceStmt->execute();
        $startPlanPrice = (float) ($priceStmt->fetchColumn() ?: 6.91);

        // 1. Total Revenue + count + hours (Completed, non-superuser)
        // vps_purchase/renew: use actual duration from order_metadata
        // recharge (no duration): infer hours from amount using start plan price
        $stmt = $this->conn->prepare(
            "SELECT SUM(t.amount), COUNT(*),
                    SUM(
                        CASE
                            WHEN COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(t.order_metadata, '$.duration')) AS UNSIGNED), 0) > 0
                            THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(t.order_metadata, '$.duration')) AS UNSIGNED)
                            ELSE (t.amount / :start_price) * 720
                        END
                    )
             FROM transactions t $noSuperuser AND t.status = 'COMPLETED'"
        );
        $stmt->bindValue(':start_price', $startPlanPrice);
        $stmt->execute();
        [$totalUSD, $completedCount, $totalHours] = $stmt->fetch(PDO::FETCH_NUM);
        $totalUSD = $totalUSD ?: 0;
        $completedCount = $completedCount ?: 0;

        // 2. Month Revenue (Current Month)
        $stmt = $this->conn->prepare(
            "SELECT SUM(t.amount) FROM transactions t $noSuperuser AND t.status = 'COMPLETED'
             AND DATE_FORMAT(t.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
        );
        $stmt->execute();
        $monthUSD = $stmt->fetchColumn() ?: 0;

        // 3. Today Revenue (Current Day)
        $stmt = $this->conn->prepare(
            "SELECT SUM(t.amount) FROM transactions t $noSuperuser AND t.status = 'COMPLETED'
             AND DATE(t.created_at) = DATE(NOW())"
        );
        $stmt->execute();
        $todayUSD = $stmt->fetchColumn() ?: 0;

        // 4. Pending Revenue (non-superuser)
        $stmt = $this->conn->prepare(
            "SELECT SUM(t.amount) FROM transactions t $noSuperuser AND t.status = 'PENDING'"
        );
        $stmt->execute();
        $pendingUSD = $stmt->fetchColumn() ?: 0;

        // Crypto Breakdown
        $stmt = $this->conn->prepare(
            "SELECT t.payment_currency,
                    SUM(CASE WHEN t.status = 'COMPLETED' THEN t.payment_amount ELSE 0 END) as total_crypto_completed,
                    SUM(CASE WHEN t.status = 'COMPLETED' THEN t.amount ELSE 0 END) as total_usd_completed,
                    SUM(CASE WHEN t.status = 'PENDING' THEN t.payment_amount ELSE 0 END) as total_crypto_pending,
                    SUM(CASE WHEN t.status = 'PENDING' THEN t.amount ELSE 0 END) as total_usd_pending
             FROM transactions t $noSuperuser AND t.status IN ('COMPLETED', 'PENDING')
             GROUP BY t.payment_currency"
        );
        $stmt->execute();
        $cryptoStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total refunded (sum of amount_refund across all non-superuser transactions)
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(t.amount_refund), 0) FROM transactions t $noSuperuser AND t.amount_refund > 0"
        );
        $stmt->execute();
        $totalRefunded = (float) ($stmt->fetchColumn() ?: 0);

        return [
            'total_revenue'    => $totalUSD,
            'completed_count'  => (int) $completedCount,
            'total_hours'      => (float) ($totalHours ?: 0),
            'start_plan_price' => $startPlanPrice,
            'month_revenue'    => $monthUSD,
            'today_revenue'    => $todayUSD,
            'pending_revenue'  => $pendingUSD,
            'total_refunded'   => $totalRefunded,
            'crypto_breakdown' => $cryptoStats
        ];
    }

    public function getById($id)
    {
        $query = "SELECT * FROM transactions WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByIdAndUser($id, $userId)
    {
        $query = "SELECT * FROM transactions WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUserId($userId, $limit = null, $offset = 0)
    {
        $query = "SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC";

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
        $query = "SELECT COUNT(*) FROM transactions WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchColumn();
    }

    public function getByTrackId($trackId)
    {
        $query = "SELECT * FROM transactions WHERE track_id = :track_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':track_id' => $trackId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByOrderId($orderId)
    {
        $query = "SELECT * FROM transactions WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteByOrderId(string $orderId): int
    {
        $stmt = $this->conn->prepare("DELETE FROM transactions WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->rowCount();
    }

    public function create(array $data)
    {
        $query = "INSERT INTO transactions (user_id, amount, currency, payment_amount, payment_currency, network, address, memo, qr_code, expired_at, order_id, description, track_id, tx_hash, type, status, created_at, updated_at)
                  VALUES (:user_id, :amount, :currency, :payment_amount, :payment_currency, :network, :address, :memo, :qr_code, :expired_at, :order_id, :description, :track_id, :tx_hash, :type, 'PENDING', NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id'          => $data['user_id'],
            ':amount'           => $data['amount'],
            ':currency'         => $data['currency'] ?? 'USD',
            ':payment_amount'   => $data['payment_amount'],
            ':payment_currency' => $data['payment_currency'],
            ':network'          => $data['network'] ?? null,
            ':address'          => $data['address'] ?? null,
            ':memo'             => $data['memo'] ?? null,
            ':qr_code'          => $data['qr_code'] ?? null,
            ':expired_at'       => $data['expired_at'] ?? null,
            ':order_id'         => $data['order_id'] ?? null,
            ':description'      => $data['description'] ?? null,
            ':track_id'         => $data['track_id'],
            ':tx_hash'          => $data['tx_hash'] ?? null,
            ':type'             => $data['type'] ?? 'recharge',
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Records a professional service payment deducted from user balance.
     * type = 'service' — no crypto fields required.
     */
    public function createServicePayment(int $userId, float $amount, string $description, int $orderId): int
    {
        $query = "INSERT INTO transactions
                    (user_id, amount, currency, payment_amount, payment_currency,
                     order_id, description, track_id, type, status, created_at, updated_at)
                  VALUES
                    (:user_id, :amount, 'USD', :amount, 'USD',
                     :order_id, :description, :track_id, 'managed_service', 'COMPLETED', NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id'     => $userId,
            ':amount'      => $amount,
            ':order_id'    => (string) $orderId,
            ':description' => $description,
            ':track_id'    => 'SVC-' . $orderId . '-' . time(),
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function updateStatus($trackId, $status, $txHash = null)
    {
        $query = "UPDATE transactions SET status = :status, updated_at = NOW()";
        $params = [':status' => $status, ':track_id' => $trackId];

        if ($txHash) {
            $query .= ", tx_hash = :tx_hash";
            $params[':tx_hash'] = $txHash;
        }

        $query .= " WHERE track_id = :track_id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Creates a transaction record for a direct VPS purchase via crypto.
     * Includes `type` = 'vps_purchase' and `order_metadata` JSON.
     */
    public function createVpsPurchase(array $data)
    {
        $query = "INSERT INTO transactions 
                    (user_id, amount, currency, payment_amount, payment_currency, network, address, memo, qr_code, 
                     expired_at, order_id, description, track_id, tx_hash, status, `type`, order_metadata, created_at, updated_at) 
                  VALUES 
                    (:user_id, :amount, :currency, :payment_amount, :payment_currency, :network, :address, :memo, :qr_code, 
                     :expired_at, :order_id, :description, :track_id, :tx_hash, 'PENDING', :type, :order_metadata, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id'          => $data['user_id'],
            ':amount'           => $data['amount'],
            ':currency'         => $data['currency'] ?? 'USD',
            ':payment_amount'   => $data['payment_amount'],
            ':payment_currency' => $data['payment_currency'],
            ':network'          => $data['network'] ?? null,
            ':address'          => $data['address'] ?? null,
            ':memo'             => $data['memo'] ?? null,
            ':qr_code'          => $data['qr_code'] ?? null,
            ':expired_at'       => $data['expired_at'] ?? null,
            ':order_id'         => $data['order_id'] ?? null,
            ':description'      => $data['description'] ?? null,
            ':track_id'         => $data['track_id'],
            ':tx_hash'          => $data['tx_hash'] ?? null,
            ':type'             => $data['type'] ?? 'vps_purchase',
            ':order_metadata'   => $data['order_metadata'] ?? null,
        ]);

        return $this->conn->lastInsertId();
    }

    public function getCompletedByLocalOrderId(int $localOrderId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM transactions
             WHERE JSON_EXTRACT(order_metadata, '$.local_order_id') = :oid
             AND status = 'COMPLETED' LIMIT 1"
        );
        $stmt->execute([':oid' => $localOrderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateRefundAmount(int $id, ?float $amount): bool
    {
        $newStatus = ($amount !== null && $amount > 0) ? 'REFUND' : 'COMPLETED';
        $stmt = $this->conn->prepare(
            "UPDATE transactions SET amount_refund = :amount, status = :status, updated_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([':amount' => $amount, ':status' => $newStatus, ':id' => $id]);
    }
}
