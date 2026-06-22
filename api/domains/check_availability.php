<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// If not JSON, check $_POST (for flexibility if needed, but we'll stick to JSON input from frontend)
if (!$input) {
    $input = $_POST;
}

$query = isset($input['query']) ? trim($input['query']) : '';
$suffixes = isset($input['suffixes']) ? $input['suffixes'] : '';

if (is_string($suffixes)) {
    $suffixes = trim($suffixes);
}

if (empty($query) || empty($suffixes)) {
    echo json_encode(['success' => false, 'error' => 'Query (domain name) and suffixes (extension) are required']);
    exit;
}

try {
    $apiService = new ExternalApiService();
    $result = $apiService->checkDomainAvailability($query, $suffixes);

    // The external API response structure needs to be passed through.
    // Based on `ExternalApiService`, we get `response` (decoded) and `raw_response`.

    // We should inspect the `response` to determine success/availability.
    // Since I don't know the exact structure of the external API's availability response (other than it's a check),
    // I will return the raw response from the external API to the frontend for now, or assume a standard structure.
    // The user didn't provide the *response* of the curl, only the request. 
    // I'll return the full response from the service.

    echo json_encode(['success' => true, 'data' => $result['response']]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
