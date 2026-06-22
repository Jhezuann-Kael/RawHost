<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';

try {
    $userId = authenticate_user();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userRepo = new UserRepository();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = $userRepo->getById($userId);

    $txRepo = new TransactionRepository();
    $lastTx = $txRepo->getByUserId($userId, 1);
    $lastPaymentCurrency = null;
    if (!empty($lastTx[0]['payment_currency']) && !empty($lastTx[0]['network'])) {
        $lastPaymentCurrency = $lastTx[0]['payment_currency'] . ':' . $lastTx[0]['network'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'preferred_currency'      => $user['preferred_currency'] ?? null,
            'auto_renew'              => (bool) ($user['auto_renew'] ?? false),
            'last_payment_currency'   => $lastPaymentCurrency,
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $updates = [];

    if (array_key_exists('preferred_currency', $input)) {
        $preferred = $input['preferred_currency'];
        if ($preferred !== null && $preferred !== '') {
            if (!preg_match('/^[A-Z0-9]+:[A-Za-z0-9 ]+$/', $preferred)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid format. Expected SYMBOL:NETWORK']);
                exit;
            }
        }
        $updates['preferred_currency'] = $preferred ?: null;
    }

    if (array_key_exists('auto_renew', $input)) {
        $updates['auto_renew'] = $input['auto_renew'] ? 1 : 0;
    }

    if (array_key_exists('display_name', $input)) {
        $dn = trim($input['display_name'] ?? '');
        $updates['display_name'] = $dn !== '' ? substr($dn, 0, 60) : null;
    }

    if (!empty($updates)) {
        $userRepo->update($userId, $updates);
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
