<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/DomainRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$domainId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (empty($domainId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de dominio requerido']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $domainRepo = new DomainRepository();

    // Get domain by ID
    $domain = $domainRepo->getById($domainId);

    if (!$domain) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Dominio no encontrado']);
        exit;
    }

    // Verify ownership
    if ($domain['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para acceder a este dominio']);
        exit;
    }

    // Parse nameservers if JSON
    $nameservers = [];
    if (!empty($domain['nameservers'])) {
        $nameservers = is_array($domain['nameservers'])
            ? $domain['nameservers']
            : (json_decode($domain['nameservers'], true) ?? []);
    }

    // Parse contacts if JSON
    $contacts = null;
    if (!empty($domain['contacts'])) {
        $contacts = is_array($domain['contacts'])
            ? $domain['contacts']
            : (json_decode($domain['contacts'], true) ?? null);
    }

    // Calculate days until expiration
    $daysUntilExpiry = null;
    if ($domain['expiration_date']) {
        $expiryDate = new DateTime($domain['expiration_date']);
        $now = new DateTime();
        $interval = $now->diff($expiryDate);
        $daysUntilExpiry = $interval->invert ? -$interval->days : $interval->days;
    }

    // Format response
    $formattedDomain = [
        'id' => $domain['id'],
        'domain_name' => $domain['domain_name'],
        'status' => $domain['status'],
        'external_id' => $domain['external_id'],
        'nameservers' => $nameservers,
        'contacts' => $contacts,
        'expiration_date' => $domain['expiration_date'],
        'created_at' => $domain['created_at'],
        'updated_at' => $domain['updated_at'],
        'registration_term' => $domain['registration_term'],
        'product_id' => $domain['product_id'],
        'price_domain' => $domain['price_domain'],
        'last_checked' => $domain['last_checked'],
        'user_id' => $domain['user_id'],
        'days_until_expiry' => $daysUntilExpiry,
        'is_expired' => $daysUntilExpiry !== null && $daysUntilExpiry < 0,
        'nameserver_count' => count($nameservers)
    ];

    echo json_encode(['success' => true, 'data' => $formattedDomain]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor', 'details' => $e->getMessage()]);
}
