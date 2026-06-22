<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/ExpenseRepository.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_superuser'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$repo   = new ExpenseRepository();

try {
    if ($method === 'GET') {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = max(1, (int) ($_GET['limit'] ?? 20));
        $offset = ($page - 1) * $limit;

        $data   = $repo->getAllPaginated($limit, $offset);
        $total  = $repo->countAll();
        $totals = $repo->getTotals();

        echo json_encode([
            'success' => true,
            'data'    => $data,
            'totals'  => $totals,
            'pagination' => [
                'total'        => $total,
                'pages'        => (int) ceil($total / $limit),
                'current_page' => $page,
                'limit'        => $limit,
            ],
        ]);

    } elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        if (empty($body['currency'])) {
            http_response_code(422);
            echo json_encode(['error' => 'El campo "currency" es obligatorio']);
            exit;
        }
        if (!isset($body['amount_fiat']) || !is_numeric($body['amount_fiat'])) {
            http_response_code(422);
            echo json_encode(['error' => 'El campo "amount_fiat" es obligatorio y debe ser numérico']);
            exit;
        }
        if (isset($body['fiat_currency']) && !in_array(strtoupper($body['fiat_currency']), ['USD', 'EUR'])) {
            http_response_code(422);
            echo json_encode(['error' => 'fiat_currency debe ser USD o EUR']);
            exit;
        }

        $id      = $repo->create($body);
        $expense = $repo->getById($id);

        echo json_encode(['success' => true, 'data' => $expense]);

    } elseif ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(422);
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $deleted = $repo->delete($id);
        echo json_encode(['success' => $deleted]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
