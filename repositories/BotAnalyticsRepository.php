<?php
require_once __DIR__ . '/../models/Database.php';

class BotAnalyticsRepository
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Returns paginated bot usage grouped by Telegram user.
     * Each row has event counts per type, user_state, and activity range.
     */
    public function getUsageByUser(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = '';
        $params = [];

        if ($search !== '') {
            $where = 'WHERE tg_id LIKE :search OR tg_username LIKE :search';
            $params[':search'] = "%$search%";
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(DISTINCT tg_id) FROM bot_analytics $where");
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->conn->prepare("
            SELECT
                tg_id,
                tg_username,
                MAX(user_state)                                              AS user_state,
                COUNT(*)                                                     AS total_events,
                SUM(event_type = 'start')                                    AS ev_start,
                SUM(event_type = 'register')                                 AS ev_register,
                SUM(event_type = 'link')                                     AS ev_link,
                SUM(event_type = 'unlink')                                   AS ev_unlink,
                SUM(event_type = 'nav')                                      AS ev_nav,
                MIN(created_at)                                              AS first_seen,
                MAX(created_at)                                              AS last_seen
            FROM bot_analytics
            $where
            GROUP BY tg_id, tg_username
            ORDER BY last_seen DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'current_page' => $page,
            'limit'        => $perPage,
        ];
    }

    /**
     * Returns aggregate summary stats for the bot.
     */
    public function getSummaryStats(): array
    {
        $row = $this->conn->query("
            SELECT
                COUNT(DISTINCT tg_id)                AS unique_users,
                COUNT(*)                             AS total_events,
                SUM(event_type = 'start')            AS ev_start,
                SUM(event_type = 'register')         AS ev_register,
                SUM(event_type = 'link')             AS ev_link,
                SUM(event_type = 'unlink')           AS ev_unlink,
                SUM(event_type = 'nav')              AS ev_nav,
                SUM(user_state = 'connected')        AS connected_users,
                SUM(user_state = 'registered')       AS registered_users,
                SUM(user_state = 'new')              AS new_users
            FROM bot_analytics
        ")->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    /**
     * Returns the event history for a specific tg_id, paginated.
     */
    public function getEventsByUser(string $tgId, int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM bot_analytics WHERE tg_id = :tg_id");
        $countStmt->execute([':tg_id' => $tgId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->conn->prepare("
            SELECT id, event_type, user_state, section, created_at
            FROM bot_analytics
            WHERE tg_id = :tg_id
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':tg_id', $tgId);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'current_page' => $page,
            'limit'        => $perPage,
        ];
    }
}
