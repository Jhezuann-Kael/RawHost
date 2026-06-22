<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

function verifyTelegramData(array $auth_data): array {
    if (!isset($auth_data['hash'])) {
        throw new Exception('Missing hash');
    }
    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);

    $parts = [];
    foreach ($auth_data as $k => $v) {
        $parts[] = "$k=$v";
    }
    sort($parts);

    $secret = hash('sha256', TOKEN_TELEGRAM, true);
    $hash   = hash_hmac('sha256', implode("\n", $parts), $secret);

    if (!hash_equals($hash, $check_hash)) {
        throw new Exception('Invalid Telegram signature');
    }
    if ((time() - (int)$auth_data['auth_date']) > 86400) {
        throw new Exception('Auth data expired');
    }
    return $auth_data;
}

try {
    $tg = verifyTelegramData($_GET);

    $userRepo  = new UserRepository();
    $existing  = $userRepo->findByTelegramId($tg['id']);

    if ($existing && $existing['id'] != $_SESSION['user_id']) {
        header('Location: /dashboard/profile?tg=already_used');
        exit;
    }

    $userRepo->update($_SESSION['user_id'], [
        'telegram_id'  => $tg['id'],
        'tg_username'  => $tg['username'] ?? null,
    ]);

    header('Location: /dashboard/profile?tg=linked');
    exit;

} catch (Exception $e) {
    header('Location: /dashboard/profile?tg=error&msg=' . urlencode($e->getMessage()));
    exit;
}
