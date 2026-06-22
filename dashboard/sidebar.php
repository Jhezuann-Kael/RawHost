<?php
// Determine context
$currentPath = $_SERVER['PHP_SELF'];
$inAdmin = strpos($currentPath, '/dashboard/admin/') !== false;
$currentPage = pathinfo(basename($currentPath), PATHINFO_FILENAME);
if ($currentPage === 'index') {
    $currentPage = basename(dirname($currentPath));
}

// Dynamic depth calculation
$dashboardPos = strpos($currentPath, '/dashboard/');
if ($dashboardPos !== false) {
    $afterDashboard = substr($currentPath, $dashboardPos + strlen('/dashboard/'));
    $depth = substr_count(trim($afterDashboard, '/'), '/');
    $dashboardBase = str_repeat('../', $depth);
    if ($dashboardBase === '')
        $dashboardBase = './';
} else {
    $dashboardBase = $inAdmin ? '../' : './';
}

// Admin Mode logic: If we are in /admin/ folder OR in legacy admin
$isAdminMode = $inAdmin || $currentPage == 'admin';

// News section check
$isUserNewsPath = strpos($currentPath, '/dashboard/news/') !== false || $currentPage == 'news';
$isAdminNewsPath = strpos($currentPath, '/dashboard/admin/news/') !== false;


?>
<aside class="sidebar">
    <div class="logo">
        <img src="/assets/logo/logo_standar.svg" alt="RawHost" style="height: 50px; border-radius: 100%; object-fit: contain;"><span style="color:#f8fafc;">Raw<span style="color:var(--primary);">/Host</span></span>
        <?php echo $isAdminMode ? '<span style="font-size:0.5em; background:var(--primary); padding:2px 5px; border-radius:4px; margin-left:5px; vertical-align: middle;">ADMIN</span>' : ''; ?>
    </div>
    <ul class="menu">
        <?php if ($isAdminMode): ?>
            <!-- ADMIN MENU -->
            <li>
                <a href="/dashboard/admin" class="<?php echo ($currentPage == 'admin') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <?php echo $lang['dash_menu_dashboard']; ?>
                </a>
            </li>

            <li class="menu-section-label"></li>

            <li>
                <a href="/dashboard/admin/vps" class="<?php echo ($currentPage == 'vps') ? 'active' : ''; ?>">
                    <i class="fas fa-server"></i> <?php echo $lang['dash_menu_vps_list']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/domains" class="<?php echo ($currentPage == 'domains') ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> <?php echo $lang['dash_menu_domains']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/managed_services" class="<?php echo ($currentPage == 'managed_services') ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i> Managed Services
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/plans" class="<?php echo $currentPage == 'plans' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i> Planes
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/news"
                    class="<?php echo ($currentPage == 'news' || $isAdminNewsPath) ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i> <?php echo $lang['dash_menu_news']; ?>
                </a>
            </li>

            <li class="menu-section-label"></li>

            <li>
                <a href="/dashboard/admin/users" class="<?php echo ($currentPage == 'users' || $currentPage == 'referrals') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <?php echo $lang['dash_menu_users']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/orders"
                    class="<?php echo ($currentPage == 'orders') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Órdenes
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/transactions"
                    class="<?php echo ($currentPage == 'transactions') ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> <?php echo $lang['dash_menu_transactions']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/tickets" class="<?php echo ($currentPage == 'tickets') ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i> <?php echo $lang['dash_menu_tickets']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/expenses" class="<?php echo ($currentPage == 'expenses') ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Gastos
                </a>
            </li>
            <li>
                <a href="/dashboard/admin/logs" class="<?php echo $currentPage == 'logs' || $currentPage == 'bot_analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Logs & Analytics
                </a>
            </li>

            <li class="menu-section-label" style="margin-top:auto;"></li>
            <li>
                <a href="/dashboard" style="color:#64748b;">
                    <i class="fas fa-arrow-left"></i> <?php echo $lang['dash_menu_back_user']; ?>
                </a>
            </li>

        <?php else: ?>
            <!-- USER MENU -->
            <li>
                <a href="/dashboard" class="<?php echo ($currentPage == 'index' || $currentPage == '') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <?php echo $lang['dash_menu_dashboard']; ?>
                </a>
            </li>

            <li class="menu-section-label"></li>

            <li>
                <a href="/dashboard/vps"
                    class="<?php echo ($currentPage == 'vps' || $currentPage == 'manage') ? 'active' : ''; ?>">
                    <i class="fas fa-server"></i> <?php echo $lang['dash_menu_my_servers']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/domains" class="<?php echo ($currentPage == 'domains') ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> <?php echo $lang['dash_menu_domains']; ?>
                </a>
            </li>
            <li>
                <a href="https://proxy.rawhost.net" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-shield-alt"></i> <?php echo $lang['dash_menu_proxies']; ?>
                    <span style="font-size:0.6em; background:var(--primary); color:#fff; padding:1px 5px; border-radius:3px; margin-left:6px; vertical-align:middle; font-weight:700;">NEW</span>
                </a>
            </li>

            <li class="menu-section-label"></li>

            <li>
                <a href="/dashboard/profile" class="<?php echo ($currentPage == 'profile') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> <?php echo $lang['dash_menu_my_profile']; ?>
                </a>
            </li>
            <li>
                <a href="/dashboard/transactions" class="<?php echo ($currentPage == 'transactions') ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> <?php echo $lang['dash_menu_transactions']; ?>
                    <span id="sidebarSvcBadge" style="display:none; background:#ef4444; color:#fff; border-radius:10px; font-size:0.65rem; font-weight:700; padding:1px 6px; margin-left:auto; vertical-align:middle;"></span>
                </a>
            </li>
            <li>
                <a href="/dashboard/support" class="<?php echo ($currentPage == 'support') ? 'active' : ''; ?>">
                    <i class="fas fa-life-ring"></i> <?php echo $lang['dash_menu_support']; ?>
                    <span id="sidebarUnreadBadge" style="display:none; background:#ef4444; color:#fff; border-radius:10px; font-size:0.65rem; font-weight:700; padding:1px 6px; margin-left:auto; vertical-align:middle;"></span>
                </a>
            </li>
            <li>
                <a href="/dashboard/news" class="<?php echo $isUserNewsPath ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i> <?php echo $lang['dash_menu_news']; ?>
                </a>
            </li>

            <?php if (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']): ?>
                <li style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <a href="/dashboard/admin" style="color: var(--primary); font-weight: 600;">
                        <i class="fas fa-user-shield"></i> <?php echo $lang['dash_menu_admin_panel']; ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>



    <?php if (!empty($_SESSION['is_superuser'])): ?>
    <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggleBtn" title="Toggle theme">
        <i class="fas fa-sun" id="themeIcon"></i>
        <span id="themeLabel">Light mode</span>
    </button>
    <?php endif; ?>

    <div class="user-profile">
        <?php
            $uname   = $_SESSION['username'] ?? 'U';
            $initial = strtoupper(substr($uname, 0, 1));
        ?>
        <div class="user-avatar"><?php echo $initial; ?></div>
        <div class="user-info">
            <div><?php echo htmlspecialchars($uname); ?></div>
            <div><?php echo (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']) ? $lang['dash_user_role_admin'] : $lang['dash_user_role_client']; ?></div>
        </div>
        <div class="btn-logout" onclick="window.location.href='/logout'" title="<?php echo $lang['dash_logout']; ?>">
            <i class="fas fa-sign-out-alt"></i>
        </div>
    </div>
</aside>

<script>
(function () {
    function applyTheme(theme) {
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
        var icon  = document.getElementById('themeIcon');
        var label = document.getElementById('themeLabel');
        if (icon)  icon.className  = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        if (label) label.textContent = theme === 'light' ? 'Dark mode' : 'Light mode';
    }

    window.toggleTheme = function () {
        var current = localStorage.getItem('rh_theme') || 'dark';
        var next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('rh_theme', next);
        applyTheme(next);
    };

    // Sync button label with current theme on load
    applyTheme(localStorage.getItem('rh_theme') || 'dark');
})();
</script>
<?php if (!$isAdminMode): ?>
<script>
(function () {
    fetch('/api/orders/pending_services_count')
        .then(r => r.json())
        .then(d => {
            if (d.success && d.count > 0) {
                const badge = document.getElementById('sidebarSvcBadge');
                if (badge) { badge.textContent = d.count; badge.style.display = 'inline-block'; }
            }
        })
        .catch(() => {});

    function refreshUnreadTickets() {
        fetch('/api/support/unread_count')
            .then(r => r.json())
            .then(d => {
                const badge = document.getElementById('sidebarUnreadBadge');
                if (!badge) return;
                const n = parseInt(d.count) || 0;
                if (n > 0) {
                    badge.textContent = n > 99 ? '99+' : n;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(() => {});
    }

    refreshUnreadTickets();
    setInterval(refreshUnreadTickets, 60000);
})();
</script>
<?php endif; ?>