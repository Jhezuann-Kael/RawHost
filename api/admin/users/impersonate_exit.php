<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['impersonating_admin'])) {
    echo json_encode(['success' => false, 'message' => 'No estás en modo inspección']);
    exit;
}

$admin = $_SESSION['impersonating_admin'];

$_SESSION['user_id']      = $admin['user_id'];
$_SESSION['username']     = $admin['username'];
$_SESSION['is_superuser'] = $admin['is_superuser'];
unset($_SESSION['impersonating_admin']);

echo json_encode(['success' => true, 'redirect' => '/dashboard/admin/users']);
