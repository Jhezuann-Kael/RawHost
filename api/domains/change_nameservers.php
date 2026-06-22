<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';
require_once __DIR__ . '/../../repositories/DomainRepository.php';

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos JSON inválidos']);
    exit;
}

$domainId = isset($input['domain_id']) ? (int) $input['domain_id'] : 0;
$dns1 = isset($input['dns1']) ? trim($input['dns1']) : '';
$dns2 = isset($input['dns2']) ? trim($input['dns2']) : '';

if (empty($domainId) || empty($dns1) || empty($dns2)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'domain_id, dns1 y dns2 son requeridos']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $domainRepo = new DomainRepository();

    // Get domain and verify ownership
    $domain = $domainRepo->getById($domainId);

    if (!$domain) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Dominio no encontrado']);
        exit;
    }

    if ($domain['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para modificar este dominio']);
        exit;
    }

    // Call external API to change nameservers
    $apiService = new ExternalApiService();
    $result = $apiService->changeNameservers($domain['domain_name'], $dns1, $dns2);

    if ($result['http_code'] !== 200) {
        http_response_code($result['http_code']);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al cambiar nameservers en la API externa. Favor de comunicarse con soporte',
            'details' => $result['response']
        ]);
        exit;
    }

    // Update nameservers in local database
    $nameservers = [$dns1, $dns2];
    $updateData = [
        'nameservers' => $nameservers
    ];

    if ($domainRepo->update($domainId, $updateData)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Nameservers actualizados correctamente',
            'data' => [
                'domain' => $domain['domain_name'],
                'nameservers' => $nameservers
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Nameservers actualizados en la API externa pero no se pudo actualizar localmente',
            'data' => $result['response'] ?? 'Error al actualizar nameservers en la API externa'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
