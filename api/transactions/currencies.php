<?php
require_once '../../services/OxaPayService.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $service = new OxaPayService();
    $result = $service->getCurrencies();

    // We might want to filter or format the result?
// Usually passes through 'data'
// Response structure: { "message": "...", "data": [ ... ], "status": 200 }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}