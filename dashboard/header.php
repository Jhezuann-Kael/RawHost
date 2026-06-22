<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/lang_loader.php'; // Load language

// Helper to determine relative path to dashboard root and site root
$currentPath = $_SERVER['PHP_SELF'];
$dashboardPos = strpos($currentPath, '/dashboard/');
if ($dashboardPos !== false) {
    $afterDashboard = substr($currentPath, $dashboardPos + strlen('/dashboard/'));
    // Count how many directories deep we are from /dashboard/
    $depth = substr_count(trim($afterDashboard, '/'), '/');
    $dashboardBase = str_repeat('../', $depth);
    if ($dashboardBase === '')
        $dashboardBase = './';
    $siteRoot = str_repeat('../', $depth + 1);
} else {
    // Fallback if not in /dashboard/ (shouldn't happen here)
    $dashboardBase = './';
    $siteRoot = '../';
}


if (!isset($_SESSION['user_id'])) {
    header("Location: " . $siteRoot . "login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="/assets/fonts/inter/latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>

    <title>
        <?php echo $pageTitle ?? $lang['dash_title']; ?>
    </title>
    <meta name="description" content="<?php echo $lang['dash_desc']; ?>">

    <!-- OpenGraph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://rawhost.net/dashboard">
    <meta property="og:title" content="<?php echo $pageTitle ?? $lang['dash_title']; ?>">
    <meta property="og:description" content="<?php echo $lang['dash_desc']; ?>">
    <meta property="og:image" content="https://rawhost.net/assets/op-image.webp">
    <meta property="og:locale" content="<?php echo $_SESSION['lang'] == 'es' ? 'es_ES' : 'en_US'; ?>">
    <meta property="og:site_name" content="RawHost">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://rawhost.net/dashboard">
    <meta name="twitter:title" content="<?php echo $pageTitle ?? $lang['dash_title']; ?>">
    <meta name="twitter:description" content="<?php echo $lang['dash_desc']; ?>">
    <meta name="twitter:image" content="https://rawhost.net/assets/op-image.webp">

    <!-- Discord / General Embeds -->
    <meta name="theme-color" content="#6366F1">

    <?php
    $bundleFiles = ['variables','base','layout','cards','buttons','forms','modals','manage','pagination','animations','empty-hero','responsive','sidebar'];
    $bundleMtime = 0;
    foreach ($bundleFiles as $_bf) {
        $_bp = __DIR__ . '/css/' . $_bf . '.css';
        if (file_exists($_bp)) { $t = filemtime($_bp); if ($t > $bundleMtime) $bundleMtime = $t; }
    }
    ?>
    <link rel="stylesheet" href="<?php echo $dashboardBase; ?>css/bundle?v=<?php echo $bundleMtime; ?>">
    <link rel="stylesheet" href="<?php echo $dashboardBase; ?>css/themes/light.css?v=<?php echo filemtime(__DIR__.'/css/themes/light.css'); ?>">
    <link rel="stylesheet" href="/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="/assets/fonts/webfonts.css">
    <?php if (isset($extraHead))
        echo $extraHead; ?>
    <script>
        /* Apply theme before paint to avoid flash */
        (function(){
            var t = localStorage.getItem('rh_theme');
            if (t === 'light') document.documentElement.setAttribute('data-theme','light');
        })();
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-closed');
            }
        }
    </script>
</head>

<body class="<?php echo $bodyClass ?? ''; ?>">
    <?php if (isset($_SESSION['impersonating_admin'])): ?>
    <div id="impersonation-bar" style="position:fixed; top:0; left:0; right:0; z-index:9999; background:linear-gradient(90deg,#f59e0b,#d97706); color:#1a1a1a; font-size:0.82rem; font-weight:700; padding:7px 18px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(245,158,11,0.35);">
        <span><i class="fas fa-user-secret" style="margin-right:7px;"></i>Inspección activa como <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> &mdash; Las acciones se realizan como este usuario.</span>
        <button onclick="exitImpersonation()" style="background:#1a1a1a; color:#f59e0b; border:none; border-radius:6px; padding:4px 14px; font-weight:700; font-size:0.8rem; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:opacity .15s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
            <i class="fas fa-arrow-left"></i> Volver a Admin
        </button>
    </div>
    <div style="height:36px;"></div>
    <script>
    function exitImpersonation() {
        fetch('/api/admin/users/impersonate_exit', { method: 'POST' })
            .then(r => r.json())
            .then(d => { if (d.success) window.location.href = d.redirect; })
            .catch(() => alert('Error al salir del modo inspección'));
    }
    </script>
    <?php endif; ?>
    <div id="loading" class="loading-overlay" style="display:none;">
        <!-- Default hidden, manage.php might override style -->
        <div class="spinner"></div>
    </div>

    <?php include __DIR__ . '/bg_effect.php'; ?>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <?php include __DIR__ . '/sidebar.php'; ?>