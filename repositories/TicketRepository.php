<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class TicketRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        return $this->getAllPaginated(1000);
    }

    public function getAllPaginated($limit = 10, $offset = 0, $search = null, $status = null)
    {
        $query = "SELECT t.*, u.username, u.email FROM tickets t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE 1=1";

        $params = [];

        if ($status) {
            $query .= " AND t.status = :status";
            $params[':status'] = $status;
        }

        if ($search) {
            $query .= " AND (t.id LIKE :search OR t.subject LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $query .= " ORDER BY t.updated_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = null, $status = null)
    {
        $query = "SELECT COUNT(*) FROM tickets t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE 1=1";

        $params = [];
        if ($status) {
            $query .= " AND t.status = :status";
            $params[':status'] = $status;
        }
        if ($search) {
            $query .= " AND (t.id LIKE :search OR t.subject LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getById($id)
    {
        $query = "SELECT * FROM tickets WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUserId($userId)
    {
        $query = "SELECT t.*,
                    (SELECT COUNT(*) FROM ticket_messages m
                     JOIN users u ON m.user_id = u.id
                     WHERE m.ticket_id = t.id
                       AND u.is_superuser = 1
                       AND m.user_id != t.user_id
                       AND m.is_read = 0) AS unread_count
                  FROM tickets t
                  WHERE t.user_id = :user_id
                  ORDER BY t.updated_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $query = "INSERT INTO tickets (user_id, subject, category, priority, status, created_at, updated_at) 
                  VALUES (:user_id, :subject, :category, :priority, 'OPEN', NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':subject' => $data['subject'],
            ':category' => $data['category'],
            ':priority' => $data['priority'] ?? 'MEDIUM'
        ]);

        return $this->conn->lastInsertId();
    }

    public function updateStatus($id, $status)
    {
        $query = "UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }

    public function countTotalUnreadForUser(int $userId): int
    {
        $query = "SELECT COUNT(*) FROM ticket_messages m
                  JOIN tickets t ON m.ticket_id = t.id
                  JOIN users u ON m.user_id = u.id
                  WHERE t.user_id = :user_id
                    AND u.is_superuser = 1
                    AND m.user_id != t.user_id
                    AND m.is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getRatingStats(): array
    {
        $stmt = $this->conn->query(
            "SELECT rating, COUNT(*) AS cnt FROM tickets WHERE rating IS NOT NULL GROUP BY rating"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map   = ['VERY_GOOD' => 0, 'GOOD' => 0, 'NOT_GOOD' => 0];
        $score = ['VERY_GOOD' => 5, 'GOOD' => 3, 'NOT_GOOD' => 1];

        foreach ($rows as $r) {
            if (isset($map[$r['rating']])) $map[$r['rating']] = (int) $r['cnt'];
        }

        $total = array_sum($map);
        $avg   = $total > 0
            ? round(($map['VERY_GOOD'] * 5 + $map['GOOD'] * 3 + $map['NOT_GOOD'] * 1) / $total, 2)
            : null;

        return [
            'VERY_GOOD'   => $map['VERY_GOOD'],
            'GOOD'        => $map['GOOD'],
            'NOT_GOOD'    => $map['NOT_GOOD'],
            'total_rated' => $total,
            'avg_score'   => $avg,
        ];
    }

    public function closeTicket($id, $rating)
    {
        $query = "UPDATE tickets SET status = 'CLOSED', rating = :rating, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':rating' => $rating,
            ':id' => $id
        ]);
    }
}
