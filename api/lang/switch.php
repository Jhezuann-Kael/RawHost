<?php
/**
 * api/lang/switch.php
 * Central language switcher — sets session + cookie, then redirects back.
 * Used by the language flag toggle in profile and any other page.
 *
 * GET params:
 *   lang  = 'en' | 'es'
 *   back  = URL to redirect to after switching (must be same host)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed = ['en', 'es'];
$lang    = isset($_GET['lang']) && in_array($_GET['lang'], $allowed) ? $_GET['lang'] : 'en';

// Persist in session + 30-day cookie
$_SESSION['lang'] = $lang;
setcookie('lang', $lang, time() + (86400 * 30), '/');

// Determine safe redirect target (same host only)
$back    = isset($_GET['back']) ? $_GET['back'] : null;
$host    = $_SERVER['HTTP_HOST'];
$default = '/dashboard/profile.php';

if ($back) {
    $parsed = parse_url($back);
    // Only redirect if host matches (prevents open-redirect)
    if (isset($parsed['host']) && $parsed['host'] === $host) {
        $target = $back;
    } else {
        // Same-server relative path only
        $target = $default;
    }
} else {
    $target = $default;
}

header('Location: ' . $target);
exit;
