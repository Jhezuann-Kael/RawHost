<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';

session_start();
$userId = authenticate_user();

$method = $_SERVER['REQUEST_METHOD'];
$planRepo = new PlanRepository();

// Write operations require superuser
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    require_once __DIR__ . '/../../repositories/UserRepository.php';
    $userRepo = new UserRepository();
    $user = $userRepo->getById($userId);
    if (!$user || empty($user['is_superuser'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

// GET /api/plans/fees.php?plan_id=X — list fees for a plan
if ($method === 'GET') {
    $planId = $_GET['plan_id'] ?? null;
    if (!$planId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'plan_id is required']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $planRepo->getFeesByPlanId($planId)]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// POST /api/plans/fees.php — add fee to a plan
// Body: { plan_id, name, type, value, currency }
if ($method === 'POST') {
    $required = ['plan_id', 'name', 'type', 'value'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }

    if (!in_array($body['type'], ['percentage', 'fixed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "type must be 'percentage' or 'fixed'"]);
        exit;
    }

    if (!in_array($body['billing_type'] ?? '', ['setup', 'recurring', 'short_term'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "billing_type must be 'setup', 'recurring' or 'short_term'"]);
        exit;
    }

    $id = $planRepo->addFee($body['plan_id'], [
        'name'         => $body['name'],
        'type'         => $body['type'],
        'billing_type' => $body['billing_type'],
        'value'        => $body['value'],
        'currency'     => $body['currency'] ?? 'USD'
    ]);

    echo json_encode(['success' => true, 'data' => ['id' => $id]]);
    exit;
}

// PUT /api/plans/fees.php — update a fee
// Body: { id, name, type, value, currency }
if ($method === 'PUT') {
    $required = ['id', 'name', 'type', 'value'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }

    if (!in_array($body['type'], ['percentage', 'fixed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "type must be 'percentage' or 'fixed'"]);
        exit;
    }

    if (!in_array($body['billing_type'] ?? '', ['setup', 'recurring', 'short_term'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "billing_type must be 'setup', 'recurring' or 'short_term'"]);
        exit;
    }

    $updated = $planRepo->updateFee($body['id'], [
        'name'         => $body['name'],
        'type'         => $body['type'],
        'billing_type' => $body['billing_type'],
        'value'        => $body['value'],
        'currency'     => $body['currency'] ?? 'USD'
    ]);

    echo json_encode(['success' => $updated]);
    exit;
}

// DELETE /api/plans/fees.php — delete a fee
// Body: { id }
if ($method === 'DELETE') {
    $id = $body['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    $deleted = $planRepo->deleteFee($id);
    echo json_encode(['success' => $deleted]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
