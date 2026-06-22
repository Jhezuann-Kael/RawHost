<?php
require_once __DIR__ . '/RepositoryInterface.php';
require_once __DIR__ . '/../models/Database.php';

class TicketMessageRepository implements RepositoryInterface
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll()
    {
        return [];
    }

    public function getById($id)
    {
        $query = "SELECT * FROM ticket_messages WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByTicketId($ticketId)
    {
        // Join with users to get sender name? Or frontend handles it?
        // Let's get generic data.
        $query = "SELECT m.*, u.username, u.display_name, u.is_superuser, u.profile_picture
                  FROM ticket_messages m
                  JOIN users u ON m.user_id = u.id
                  WHERE m.ticket_id = :ticket_id
                  ORDER BY m.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':ticket_id' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $query = "INSERT INTO ticket_messages (ticket_id, user_id, message, image_path, created_at)
                  VALUES (:ticket_id, :user_id, :message, :image_path, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':ticket_id' => $data['ticket_id'],
            ':user_id' => $data['user_id'],
            ':message' => $data['message'],
            ':image_path' => $data['image_path'] ?? null
        ]);

        return $this->conn->lastInsertId();
    }

    public function updateMessage($id, $message)
    {
        $query = "UPDATE ticket_messages SET message = :message, edited_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':message' => $message, ':id' => $id]);
    }

    public function delete($id)
    {
        $query = "DELETE FROM ticket_messages WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Mark all admin messages in a ticket as read (called when user opens the ticket).
     */
    public function markAdminMessagesAsRead(int $ticketId): void
    {
        // Only mark messages sent by a superuser that is NOT the ticket owner
        $query = "UPDATE ticket_messages m
                  JOIN users u ON m.user_id = u.id
                  JOIN tickets t ON m.ticket_id = t.id
                  SET m.is_read = 1
                  WHERE m.ticket_id = :ticket_id
                    AND u.is_superuser = 1
                    AND m.user_id != t.user_id
                    AND m.is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':ticket_id' => $ticketId]);
    }

    public function countUnreadAdminMessages(int $ticketId): int
    {
        $query = "SELECT COUNT(*) FROM ticket_messages m
                  JOIN users u ON m.user_id = u.id
                  JOIN tickets t ON m.ticket_id = t.id
                  WHERE m.ticket_id = :ticket_id
                    AND u.is_superuser = 1
                    AND m.user_id != t.user_id
                    AND m.is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':ticket_id' => $ticketId]);
        return (int) $stmt->fetchColumn();
    }
}
