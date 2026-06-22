<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class ExpenseRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        $stmt = $this->conn->prepare("SELECT * FROM expenses ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPaginated(int $limit, int $offset)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM expenses ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM expenses")->fetchColumn();
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM expenses WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO expenses (currency, amount_currency, amount_fiat, fiat_currency, description, created_at)
             VALUES (:currency, :amount_currency, :amount_fiat, :fiat_currency, :description, NOW())"
        );
        $stmt->execute([
            ':currency'        => strtoupper(trim($data['currency'])),
            ':amount_currency' => isset($data['amount_currency']) && $data['amount_currency'] !== '' ? $data['amount_currency'] : null,
            ':amount_fiat'     => $data['amount_fiat'],
            ':fiat_currency'   => strtoupper($data['fiat_currency'] ?? 'USD'),
            ':description'     => $data['description'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function delete($id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM expenses WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getTotals(): array
    {
        $stmt = $this->conn->query(
            "SELECT fiat_currency, SUM(amount_fiat) as total
             FROM expenses GROUP BY fiat_currency"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totals = ['USD' => 0, 'EUR' => 0];
        foreach ($rows as $row) {
            $totals[$row['fiat_currency']] = (float) $row['total'];
        }
        return $totals;
    }
}
