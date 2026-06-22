<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';

function checkTelegramAuthorization($auth_data) {
    if (!defined('TOKEN_TELEGRAM')) {
        throw new Exception("Telegram token not defined.");
    }

    if (!isset($auth_data['hash'])) {
        throw new Exception("Hash missing from Telegram data.");
    }

    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);
    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    $data_check_string = implode("\n", $data_check_arr);
    $secret_key = hash('sha256', TOKEN_TELEGRAM, true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);
    
    if (strcmp($hash, $check_hash) !== 0) {
        throw new Exception('Data is NOT from Telegram');
    }
    if ((time() - $auth_data['auth_date']) > 86400) {
        throw new Exception('Data is outdated');
    }
    return $auth_data;
}

try {
    $tg_user = checkTelegramAuthorization($_GET);
    
    $userRepo = new UserRepository();
    $user = $userRepo->findByTelegramId($tg_user['id']);
    
    if ($user) {
        // User exists — refresh tg_username in case it changed
        if (!empty($tg_user['username'])) {
            $userRepo->update($user['id'], ['tg_username' => $tg_user['username']]);
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_superuser'] = (bool)$user['is_superuser'];
    } else {
        // Create new user
        // Generate random password
        $random_password = bin2hex(random_bytes(8));
        $password_hash = password_hash($random_password, PASSWORD_BCRYPT);
        
        $base_username = '';
        if (isset($tg_user['username']) && !empty($tg_user['username'])) {
            $base_username = $tg_user['username'];
        } elseif (isset($tg_user['first_name']) && !empty($tg_user['first_name'])) {
            $base_username = $tg_user['first_name'];
        } else {
            $base_username = 'user_' . $tg_user['id'];
        }

        $base_username = preg_replace('/[^a-zA-Z0-9_]/', '', $base_username); // sanitize
        
        if (empty($base_username)) {
            $base_username = 'user_' . $tg_user['id'];
        }

        $final_username = $base_username;
        // Check for username conflict
        if ($userRepo->findByUsername($final_username)) {
            // Conflict! Append telegram id
            $final_username = $base_username . '_' . $tg_user['id'];
        }
        
        // Handle potential session referral code here if it exists? We don't have standard referral code in session usually, but we could add if needed.
        // For now, no referral code linked to Telegram login via this direct path unless we pass it as a parameter, but widget doesn't allow custom pass-through nicely.
        
        $newUserId = $userRepo->create([
            'username'    => $final_username,
            'email'       => null,
            'password_hash' => $password_hash,
            'telegram_id' => $tg_user['id'],
            'tg_username' => $tg_user['username'] ?? null,
            'referred_by' => null,
            'referral_code' => null,
        ]);
        
        if ($newUserId) {
            $newUser = $userRepo->getById($newUserId);
            $_SESSION['user_id'] = $newUser['id'];
            $_SESSION['username'] = $newUser['username'];
            $_SESSION['is_superuser'] = (bool)$newUser['is_superuser'];
        } else {
            throw new Exception("Failed to create user.");
        }
    }
    
    header('Location: /dashboard/index');
    exit;
} catch (Exception $e) {
    echo "<h1>Telegram Authentication Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='/login'>Return to Login</a>";
    die();
}
