<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/DomainRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $domainRepo = new DomainRepository();
    $domains = $domainRepo->getByUserId($_SESSION['user_id']);

    // Format data for frontend
    $formattedDomains = array_map(function ($domain) {
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

        // Extract TLD from domain name
        $parts = explode('.', $domain['domain_name']);
        $tld = count($parts) > 1 ? end($parts) : ($domain['product_id'] ?? 'unknown');

        return [
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
            'tld' => $tld,
            'price_domain' => $domain['price_domain'],
            'last_checked' => $domain['last_checked'],
            'user_id' => $domain['user_id'],
            // Additional computed fields
            'days_until_expiry' => $daysUntilExpiry,
            'is_expired' => $daysUntilExpiry !== null && $daysUntilExpiry < 0,
            'nameserver_count' => count($nameservers)
        ];
    }, $domains);

    echo json_encode(['success' => true, 'data' => $formattedDomains]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
