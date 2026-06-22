<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/DomainRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $domainRepo = new DomainRepository();
    $userRepo = new UserRepository();

    // GET - List domains with pagination
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $offset = ($page - 1) * $limit;

        // Get all domains
        $allDomains = $domainRepo->getAll();

        // Filter by search if provided
        if ($search !== '') {
            $allDomains = array_filter($allDomains, function ($domain) use ($search) {
                $searchLower = strtolower($search);
                return stripos($domain['domain_name'], $searchLower) !== false ||
                    stripos($domain['status'], $searchLower) !== false ||
                    stripos($domain['external_id'] ?? '', $searchLower) !== false;
            });
        }

        $totalItems = count($allDomains);
        $totalPages = ceil($totalItems / $limit);

        // Apply pagination
        $paginatedDomains = array_slice($allDomains, $offset, $limit);

        // Enrich with owner info
        $enrichedDomains = array_map(function ($domain) use ($userRepo) {
            $owner = $userRepo->getById($domain['user_id']);

            // Parse nameservers
            $nameservers = [];
            if (!empty($domain['nameservers'])) {
                $nameservers = is_array($domain['nameservers'])
                    ? $domain['nameservers']
                    : (json_decode($domain['nameservers'], true) ?? []);
            }

            // Calculate days until expiration
            $daysUntilExpiry = null;
            if ($domain['expiration_date']) {
                $expiryDate = new DateTime($domain['expiration_date']);
                $now = new DateTime();
                $interval = $now->diff($expiryDate);
                $daysUntilExpiry = $interval->invert ? -$interval->days : $interval->days;
            }

            // Extract TLD
            $parts = explode('.', $domain['domain_name']);
            $tld = count($parts) > 1 ? end($parts) : ($domain['product_id'] ?? 'unknown');

            return [
                'id' => $domain['id'],
                'domain_name' => $domain['domain_name'],
                'status' => $domain['status'],
                'tld' => $tld,
                'external_id' => $domain['external_id'],
                'user_id' => $domain['user_id'],
                'owner_username' => $owner ? $owner['username'] : 'Unknown',
                'owner_email' => $owner ? $owner['email'] : 'N/A',
                'nameservers' => $nameservers,
                'nameserver_count' => count($nameservers),
                'expiration_date' => $domain['expiration_date'],
                'days_until_expiry' => $daysUntilExpiry,
                'is_expired' => $daysUntilExpiry !== null && $daysUntilExpiry < 0,
                'created_at' => $domain['created_at'],
                'updated_at' => $domain['updated_at'],
                'price_domain' => $domain['price_domain'],
                'registration_term' => $domain['registration_term']
            ];
        }, $paginatedDomains);

        echo json_encode([
            'domains' => array_values($enrichedDomains),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
        exit;
    }

    // POST - Reassign domain
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $action = $input['action'];

        if ($action === 'assign') {
            $domainId = isset($input['domain_id']) ? (int) $input['domain_id'] : 0;
            $newUserId = isset($input['user_id']) ? (int) $input['user_id'] : 0;

            if (!$domainId || !$newUserId) {
                http_response_code(400);
                echo json_encode(['error' => 'domain_id and user_id are required']);
                exit;
            }

            // Check if domain exists
            $domain = $domainRepo->getById($domainId);
            if (!$domain) {
                http_response_code(404);
                echo json_encode(['error' => 'Domain not found']);
                exit;
            }

            // Check if user exists
            $newUser = $userRepo->getById($newUserId);
            if (!$newUser) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }

            // Update domain ownership
            if ($domainRepo->update($domainId, ['user_id' => $newUserId])) {
                echo json_encode([
                    'message' => 'Domain reassigned successfully',
                    'domain_id' => $domainId,
                    'new_user_id' => $newUserId,
                    'new_username' => $newUser['username']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update domain']);
            }
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    // DELETE - Delete domain
    if ($method === 'DELETE') {
        $domainId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$domainId) {
            http_response_code(400);
            echo json_encode(['error' => 'Domain ID is required']);
            exit;
        }

        // Check if domain exists
        $domain = $domainRepo->getById($domainId);
        if (!$domain) {
            http_response_code(404);
            echo json_encode(['error' => 'Domain not found']);
            exit;
        }

        // Delete domain (only local, not external API)
        if ($domainRepo->delete($domainId)) {
            echo json_encode([
                'message' => 'Domain deleted successfully',
                'domain_id' => $domainId
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete domain']);
        }
        exit;
    }

    // Method not allowed
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
