<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class NewsRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Get all news with pagination and date filtering
     */
    public function getAll(int $page = 1, int $limit = 10, ?string $startDate = null, ?string $endDate = null, bool $onlyActive = false)
    {
        $offset = ($page - 1) * $limit;

        $query = "SELECT * FROM news WHERE 1=1";
        $params = [];

        if ($onlyActive) {
            $query .= " AND is_active = 1";
        }

        if ($startDate) {
            $query .= " AND created_at >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if ($endDate) {
            $query .= " AND created_at <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total news for pagination
     */
    public function countAll(?string $startDate = null, ?string $endDate = null, bool $onlyActive = false)
    {
        $query = "SELECT COUNT(*) FROM news WHERE 1=1";
        $params = [];

        if ($onlyActive) {
            $query .= " AND is_active = 1";
        }

        if ($startDate) {
            $query .= " AND created_at >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if ($endDate) {
            $query .= " AND created_at <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
    /**
     * Obtener el detalle completo de una noticia por ID
     * Incluye: title, content, category, is_active, created_at, updated_at
     */
    public function getById($id)
    {
        $query = "SELECT 
                    id, 
                    title, 
                    content, 
                    category, 
                    is_active, 
                    created_at, 
                    updated_at,
                    views 
                  FROM news 
                  WHERE id = :id 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        // Usamos PARAM_INT para asegurar que el ID sea tratado correctamente
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Retornamos el array con los datos o false si no existe
        return $result ? $result : null;
    }

    /**
     * Create news
     */
    public function create(array $data)
    {
        $query = "INSERT INTO news (title, content, category, is_active, created_at, updated_at) 
                  VALUES (:title, :content, :category, :is_active, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':category' => $data['category'] ?? 'GENERAL',
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Update news
     */
    public function update(int $id, array $data)
    {
        $allowedFields = ['title', 'content', 'category', 'is_active'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE news SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Delete news
     */
    public function delete($id)
    {
        $query = "DELETE FROM news WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Increment views count for a news item
     */
    public function incrementViews($id)
    {
        $query = "UPDATE news SET views = views + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
