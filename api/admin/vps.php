<?php
header("Content-Type: application/json");
require_once '../../api/config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '-04:00'");

    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $offset = ($page - 1) * $limit;

        // Base Query
        $sql = "SELECT v.*, u.username as owner_username, p.metadata as specs 
                FROM vps v 
                LEFT JOIN users u ON v.user_id = u.id 
                LEFT JOIN plans p ON v.plan_id = p.id
                WHERE 1=1";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (v.name LIKE :search OR v.ip_address LIKE :search OR u.username LIKE :search OR v.id LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // Count for pagination
        $countSql = "SELECT COUNT(*) FROM vps v LEFT JOIN users u ON v.user_id = u.id WHERE 1=1";
        if (!empty($search)) {
            $countSql .= " AND (v.name LIKE :search OR v.ip_address LIKE :search OR u.username LIKE :search OR v.id LIKE :search)";
        }
        $stmtCount = $pdo->prepare($countSql);
        foreach ($params as $k => $v)
            $stmtCount->bindValue($k, $v);
        $stmtCount->execute();
        $totalItems = $stmtCount->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Fetch Data
        $sql .= " ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $vpsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'vps' => $vpsList,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'limit' => $limit
            ]
        ]);

    } elseif ($method === 'POST') {
        // Create or Assign
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['action'])) {
            if ($data['action'] === 'assign' && isset($data['vps_id']) && isset($data['user_id'])) {
                // Reassign VPS
                $stmt = $pdo->prepare("UPDATE vps SET user_id = :user_id WHERE id = :vps_id");
                $stmt->execute([':user_id' => $data['user_id'], ':vps_id' => $data['vps_id']]);
                echo json_encode(['message' => 'VPS reassigned successfully']);

            } elseif ($data['action'] === 'create') {
                // Admin Create logic - simplified for now
                // Needs strict validation in real scenario
                $stmt = $pdo->prepare("INSERT INTO vps (user_id, name, ip_address, status, external_id) VALUES (:user_id, :name, :ip, 'ACTIVE', :external_id)");
                $stmt->execute([
                    ':user_id' => $data['user_id'],
                    ':name' => $data['name'],
                    ':ip' => $data['ip_address'] ?? null,
                    ':external_id' => $data['external_id'] ?? uniqid('vps_')
                ]);
                echo json_encode(['message' => 'VPS created successfully']);

            } elseif ($data['action'] === 'rename' && isset($data['vps_id']) && isset($data['name'])) {
                $name = trim($data['name']);
                if (empty($name)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'El nombre no puede estar vacío']);
                    exit;
                }
                $stmt = $pdo->prepare("UPDATE vps SET name = :name, updated_at = NOW() WHERE id = :vps_id");
                $stmt->execute([':name' => $name, ':vps_id' => $data['vps_id']]);
                echo json_encode(['message' => 'VPS renombrado correctamente']);

            } elseif ($data['action'] === 'change_password' && isset($data['vps_id']) && isset($data['password'])) {
                $password = $data['password'];
                if (empty($password)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'La contraseña no puede estar vacía']);
                    exit;
                }
                $stmtGet = $pdo->prepare("SELECT metadata FROM vps WHERE id = :vps_id");
                $stmtGet->execute([':vps_id' => $data['vps_id']]);
                $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
                $meta = json_decode($row['metadata'] ?? '{}', true) ?: [];
                $meta['password'] = $password;
                $stmt = $pdo->prepare("UPDATE vps SET metadata = :metadata, updated_at = NOW() WHERE id = :vps_id");
                $stmt->execute([':metadata' => json_encode($meta), ':vps_id' => $data['vps_id']]);
                echo json_encode(['message' => 'Contraseña actualizada correctamente']);
            }
        }

    } elseif ($method === 'DELETE') {
        // Delete VPs
        $vps_id = $_GET['id'] ?? null;
        if ($vps_id) {
            $stmt = $pdo->prepare("DELETE FROM vps WHERE id = :id");
            $stmt->execute([':id' => $vps_id]);
            echo json_encode(['message' => 'VPS deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
