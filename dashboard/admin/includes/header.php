<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Superuser Check
if (!isset($_SESSION['is_superuser']) || !$_SESSION['is_superuser']) {
    header('Location: /dashboard/admin/index.php');
    exit;
}

require_once __DIR__ . '/../../../api/config.php';
require_once __DIR__ . '/../../../models/User.php';

// Set page title if not already set
$pageTitle = $pageTitle ?? (SITE_NAME . ' - Admin Panel');
$extraHead = '';
ob_start();
include __DIR__ . '/styles.php';
$extraHead = ob_get_clean();

// Include main dashboard header
include __DIR__ . '/../../header.php';
?>