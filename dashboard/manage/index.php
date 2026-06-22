<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit;
}

require_once '../../api/config.php';
require_once '../../includes/lang_loader.php';
require_once '../../repositories/VpsRepository.php';

$server_id = $_GET['id'] ?? 0;

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || $vps['user_id'] != $_SESSION['user_id'] && empty($_SESSION['is_superuser'])) {
    header("Location: ../index");
    exit;
}
$isAdminViewingOthers = $_SESSION['user_id'] != $vps['user_id'] && !empty($_SESSION['is_superuser']);
$pageTitle = $lang['man_title'] . ' - ' . SITE_NAME;

// ── Versioning for Cache Busting ─────────────────────────────────────────────
define('V', '1.0.9'); // Increment this to force refresh across Cloudflare

$jsBase = __DIR__ . '/../../dashboard/js/manage/';
function jsv(string $file, string $base): string {
    $path = "$base$file";
    $mt = file_exists($path) ? filemtime($path) : time();
    return "$mt." . V;
}

$bodyClass = 'sidebar-closed';
$cssFiles = ['base','server-info','connection','modals','stats','resources','notifications','finder','orders','scripts'];
$cssLinks = '';
foreach ($cssFiles as $f) {
    $path = __DIR__ . "/css/{$f}.css";
    $v = jsv("$f.min.css", __DIR__ . '/css/');
    $cssLinks .= "<link rel=\"stylesheet\" href=\"css/{$f}.min.css?v={$v}\">\n";
}
$extraHead = "
<meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\">
<meta http-equiv=\"Pragma\" content=\"no-cache\">
<meta http-equiv=\"Expires\" content=\"0\">
<script src=\"https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js\"></script>
<script type=\"module\">
        import RFB from 'https://cdn.jsdelivr.net/npm/@novnc/novnc@1.4.0/core/rfb.js';
        window.RFB = RFB;
    </script>
{$cssLinks}";
include '../header.php';

?>
<!-- Content follows -->

<!-- Notification Container -->
<div class="notification-container" id="notificationContainer"></div>

<!-- Main Content -->
<main class="main-content hidden" id="main-content">
    <div class="header" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center;">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
            <h1 style="margin: 0; margin-right: 15px;">
                <span id="vps-name"><?php echo $lang['man_loading']; ?></span>
            </h1>
            <span id="vps-status" class="status-badge">...</span>
            <?php if ($isAdminViewingOthers): ?>
                <span class="status-badge" style="background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.2); margin-left: 10px;">
                    <i class="fas fa-user-shield"></i> <?php echo $lang['man_viewing_as_admin']; ?>
                </span>
            <?php endif; ?>
        </div>
        <div style="text-align: right; display: flex; align-items: center; gap: 10px;">
            <span style="color: var(--text-muted); font-size: 0.9rem;"><?php echo $lang['man_label_time_rem']; ?></span>
            <div class="time-progress-container" title="<?php echo $lang['man_label_time_rem']; ?>">
                <div class="time-progress-fill" id="vps-time-fill" style="width: 0%;"></div>
                <div class="time-progress-text" id="time-remaining"><?php echo $lang['man_verifying']; ?></div>
            </div>
        </div>
    </div>

    <!-- 1️⃣ STATUS & POWER CONTROLS -->
    <div class="card full-width" style="margin-bottom: 12px;">
        <h3 class="card-title"><i class="fas fa-bolt"></i> <?php echo $lang['man_pwr_title']; ?></h3>
        <div class="power-controls-grid">
            <button class="action-btn primary" title="<?php echo $lang['man_pwr_start']; ?>"
                onclick="performServerAction('start')">
                <i class="fas fa-play"></i> <?php echo $lang['man_pwr_start']; ?>
            </button>
            <button class="action-btn" onclick="performServerAction('restart')"
                title="<?php echo $lang['man_pwr_restart']; ?>">
                <i class="fas fa-sync-alt"></i> <?php echo $lang['man_pwr_restart']; ?>
            </button>
            <button class="action-btn danger" onclick="performServerAction('stop')"
                title="<?php echo $lang['man_pwr_stop']; ?>">
                <i class="fas fa-power-off"></i> <?php echo $lang['man_pwr_stop']; ?>
            </button>
            <button class="action-btn" onclick="openVncModal()" title="<?php echo $lang['man_pwr_vnc']; ?>">
                <i class="fas fa-desktop"></i> <?php echo $lang['man_pwr_vnc']; ?>
            </button>
            <button class="action-btn" onclick="openReinstallModal()" title="<?php echo $lang['man_pwr_reinstall']; ?>">
                <i class="fas fa-compact-disc"></i> <?php echo $lang['man_pwr_reinstall']; ?>
            </button>
            <button class="action-btn" id="btn-reset-password" onclick="resetPassword()"
                title="<?php echo $lang['man_pwr_reset_pw']; ?>">
                <i class="fas fa-key"></i> <?php echo $lang['man_pwr_reset_pw']; ?>
            </button>
            <button class="action-btn primary" onclick="openBuyIpModal()"
                title="<?php echo $lang['man_pwr_add_ip']; ?>">
                <i class="fas fa-plus-circle"></i> <?php echo $lang['man_pwr_add_ip']; ?>
            </button>
        </div>
    </div>

    <!-- SERVER INFO + CONNECTION DETAILS (side by side) -->
    <div class="main-split-grid" style="margin-bottom: 14px;">

        <!-- LEFT: Server Info -->
        <div class="finder-window">
            <div class="finder-titlebar">
                <div class="finder-lights">
                    <span class="l-close"></span>
                    <span class="l-min"></span>
                    <span class="l-max"></span>
                </div>
                <span class="finder-title-text">
                    <i class="fas fa-server"></i> <?php echo $lang['man_info_title']; ?>
                </span>
            </div>
            <div class="finder-body">
                <div class="specs-grid" style="grid-template-columns: repeat(4,1fr); margin-bottom: 14px;">
                    <div class="spec-item">
                        <i class="fas fa-microchip"></i>
                        <span class="value" id="spec-cpu">1</span>
                        <span class="label">vCPU</span>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-memory"></i>
                        <span class="value" id="spec-ram">1 GB</span>
                        <span class="label">RAM</span>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-hdd"></i>
                        <span class="value" id="spec-disk">20 GB</span>
                        <span class="label">NVMe</span>
                    </div>
                    <div class="spec-item">
                        <i class="fab fa-linux"></i>
                        <span class="value" id="spec-os">Debian</span>
                        <span class="label"><?php echo $lang['man_info_os']; ?></span>
                    </div>
                </div>
                <div class="finder-info-row">
                    <span><?php echo $lang['man_info_plan']; ?></span>
                    <strong id="vps-plan" style="color:var(--primary);">Basic</strong>
                </div>
                <div class="finder-info-row">
                    <span><?php echo $lang['man_info_price']; ?></span>
                    <strong>$<span id="vps-price">0.00</span> /mo</strong>
                </div>
                <div class="finder-info-row" style="margin-bottom: 14px;">
                    <span><?php echo $lang['man_info_exp']; ?></span>
                    <strong id="vps-expiry">...</strong>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <button class="btn btn-manage" onclick="window.location.href='../orders/renew_vps?id=<?php echo $server_id; ?>'" style="width:100%;">
                        <?php echo $lang['man_btn_renew']; ?>
                    </button>
                    <button class="btn btn-outline" onclick="window.location.href='../orders/upgrade_vps?id=<?php echo $server_id; ?>'" style="width:100%;">
                        <?php echo $lang['man_btn_upgrade']; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT: Connection Details + Scripts -->
        <div class="finder-window finder-primary" id="scripts-section">
            <div class="finder-titlebar">
                <div class="finder-lights">
                    <span class="l-close"></span>
                    <span class="l-min"></span>
                    <span class="l-max"></span>
                </div>
                <span class="finder-title-text">
                    <i class="fas fa-terminal"></i> <?php echo $lang['man_conn_title']; ?>
                </span>
            </div>
            <div class="finder-tabs-bar">
                <button class="stats-tab active" onclick="switchFinderTab('conn', 'details', this)">
                    <i class="fas fa-plug"></i> <?php echo $lang['man_conn_title']; ?>
                </button>
                <button class="stats-tab" onclick="switchFinderTab('conn', 'scripts', this)">
                    <i class="fas fa-scroll"></i> Scripts
                    <span style="background:#ef4444; color:#fff; font-size:0.6rem; font-weight:800; padding:1px 5px; border-radius:3px; letter-spacing:0.05em; line-height:1.4; vertical-align:middle;">NEW</span>
                </button>

            </div>
            <div class="finder-body">
                <div id="conn-tab-details" class="finder-tab-content active">
                    <div class="access-row">
                        <span class="access-label"><?php echo $lang['man_conn_ip']; ?></span>
                        <div class="access-value">
                            <span id="vps-ip">...</span>
                            <button class="btn-icon-tiny" id="copy-ip"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="access-row">
                        <span class="access-label"><?php echo $lang['man_conn_user']; ?></span>
                        <div class="access-value">
                            <span id="vps-user">root</span>
                            <button class="btn-icon-tiny" id="copy-user"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="access-row">
                        <span class="access-label"><?php echo $lang['man_conn_pass']; ?></span>
                        <div class="access-value">
                            <span id="vps-password-display">••••••••••</span>
                            <input type="hidden" id="vps-password-raw">
                            <button class="btn-icon-tiny" id="toggle-password"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon-tiny" id="copy-password"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="finder-ssh-hint">
                        <i class="fas fa-terminal"></i>
                        <code>ssh <span class="ssh-user">root</span>@<span id="ssh-ip-hint">...</span></code>
                    </div>
                    <div class="access-row" id="app-login-row" style="display:none;">
                        <span class="access-label"><?php echo $lang['man_conn_app_login'] ?? 'App Login'; ?></span>
                        <div class="access-value">
                            <span id="app-login-url">...</span>
                            <button class="btn-icon-tiny" id="copy-app-login"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>
                <div id="conn-tab-scripts" class="finder-tab-content">
                    <!-- Modular Managers List -->
                    <div id="scripts-list-view">
                        <div id="scripts-list-container">
                            <!-- Docker Manager Module -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; background:rgba(255,255,255,0.03); padding:15px; border-radius:12px; border:1px solid rgba(255,255,255,0.06); transition: transform 0.2s; cursor:pointer;" onclick="location.href='docker?id=<?php echo $server_id; ?>'">
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div style="width:40px; height:40px; background:rgba(36,150,237,0.1); border-radius:10px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fab fa-docker" style="color:#2496ed; font-size:1.4rem;"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin:0; font-size:1rem;">Docker Manager</h4>
                                        <p style="margin:2px 0 0; font-size:0.75rem; color:var(--text-muted);">Manage containers, images and volumes</p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right" style="color:var(--text-muted); opacity:0.5;"></i>
                            </div>

                            <!-- Placeholder for future modules -->
                            <div style="text-align:center; color:var(--text-muted); padding:20px; font-size:0.8rem; border:1px dashed rgba(255,255,255,0.05); border-radius:12px;">
                                <i class="fas fa-plus-circle" style="margin-bottom:5px; display:block;"></i>
                                More modules coming soon
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- RESOURCE USAGE -->
    <div class="finder-window" style="margin-bottom: 14px;">
        <div class="finder-titlebar">
            <div class="finder-lights">
                <span class="l-close"></span>
                <span class="l-min"></span>
                <span class="l-max"></span>
            </div>
            <span class="finder-title-text">
                <i class="fas fa-chart-bar"></i> <?php echo $lang['man_res_title']; ?>
            </span>
        </div>
        <div class="finder-tabs-bar">
            <button class="stats-tab active" onclick="switchResourceTab('graphs')">
                <i class="fas fa-chart-line"></i> <?php echo $lang['man_res_tab_graphs']; ?>
            </button>
            <button class="stats-tab" onclick="switchResourceTab('summary')">
                <i class="fas fa-tachometer-alt"></i> <?php echo $lang['man_res_tab_summary']; ?>
            </button>
        </div>
        <div class="finder-body">
            <div id="resource-tab-summary" class="resource-tab-content">
                <div class="resource-usage-container">
                    <div class="resource-meter">
                        <div class="resource-meter-header">
                            <span class="resource-meter-label"><i class="fas fa-microchip"></i> CPU</span>
                            <span class="resource-meter-value" id="cpu-usage-value">0%</span>
                        </div>
                        <div class="resource-meter-bar">
                            <div class="resource-meter-fill cpu-fill" id="cpu-usage-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div class="resource-meter">
                        <div class="resource-meter-header">
                            <span class="resource-meter-label"><i class="fas fa-memory"></i> RAM</span>
                            <span class="resource-meter-value" id="ram-usage-value">0 MB / 0 MB</span>
                        </div>
                        <div class="resource-meter-bar">
                            <div class="resource-meter-fill ram-fill" id="ram-usage-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div class="resource-meter">
                        <div class="resource-meter-header">
                            <span class="resource-meter-label"><i class="fas fa-hdd"></i> Disk</span>
                            <span class="resource-meter-value" id="disk-usage-value">— GB</span>
                        </div>
                        <div class="resource-meter-bar">
                            <div class="resource-meter-fill disk-fill" id="disk-usage-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div class="resource-meter">
                        <div class="resource-meter-header">
                            <span class="resource-meter-label"><i class="fas fa-network-wired"></i> Red</span>
                            <span class="resource-meter-value" id="network-usage-value">0 KB/s</span>
                        </div>
                        <div class="resource-meter-bar">
                            <div class="resource-meter-fill network-fill" id="network-usage-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="resource-tab-graphs" class="resource-tab-content active">
                <div class="stats-tabs" style="margin-bottom: 12px;">
                    <button class="stats-tab active" data-tab="network" onclick="switchStatsTab('network')">
                        <i class="fas fa-network-wired"></i> <?php echo $lang['man_res_net']; ?>
                    </button>
                    <button class="stats-tab" data-tab="cpu" onclick="switchStatsTab('cpu')">
                        <i class="fas fa-microchip"></i> <?php echo $lang['man_res_cpu']; ?>
                    </button>
                    <button class="stats-tab" data-tab="memory" onclick="switchStatsTab('memory')">
                        <i class="fas fa-memory"></i> <?php echo $lang['man_res_ram']; ?>
                    </button>
                    <button class="stats-tab" data-tab="disks" onclick="switchStatsTab('disks')">
                        <i class="fas fa-hdd"></i> <?php echo $lang['man_res_disk']; ?>
                    </button>
                </div>
                <div class="stats-content">
                    <div id="network-stats" class="stats-panel active"><canvas id="networkChart"></canvas></div>
                    <div id="cpu-stats" class="stats-panel"><canvas id="cpuChart"></canvas></div>
                    <div id="memory-stats" class="stats-panel"><canvas id="memoryChart"></canvas></div>
                    <div id="disks-stats" class="stats-panel"><canvas id="diskChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional IPs -->
    <div class="finder-window" id="ips-section" style="margin-bottom: 14px; margin-top: 14px;">
        <div class="finder-titlebar">
            <div class="finder-lights">
                <span class="l-close"></span><span class="l-min"></span><span class="l-max"></span>
            </div>
            <span class="finder-title-text">
                <i class="fas fa-network-wired"></i> <?php echo $lang['ip_sec_title']; ?>
            </span>
        </div>
        <div class="finder-tabs-bar">
            <button class="stats-tab active" onclick="switchFinderTab('ip', 'list', this)">
                <i class="fas fa-list"></i> <?php echo $lang['ip_tbl_ip']; ?>
            </button>
            <button class="stats-tab" onclick="switchFinderTab('ip', 'add', this)">
                <i class="fas fa-plus-circle"></i> <?php echo $lang['ip_sec_btn_add']; ?>
            </button>
        </div>
        <div class="finder-body">
            <div id="ip-tab-list" class="finder-tab-content active">
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo $lang['ip_tbl_type']; ?></th>
                                <th><?php echo $lang['ip_tbl_ip']; ?></th>
                                <th><?php echo $lang['ip_tbl_price']; ?></th>
                                <th><?php echo $lang['ip_tbl_status']; ?></th>
                                <th><?php echo $lang['ip_tbl_expiry']; ?></th>
                                <th><?php echo $lang['ip_tbl_action']; ?></th>
                            </tr>
                        </thead>
                        <tbody id="addons-body">
                            <tr>
                                <td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">
                                    <?php echo $lang['ip_sec_none']; ?>
                                    <a href="#" onclick="switchFinderTab('ip','add',document.querySelector('[onclick*=\'ip\',\'add\']')); return false;" style="color:var(--primary);">
                                        <?php echo $lang['ip_sec_buy_now']; ?>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="ip-tab-add" class="finder-tab-content">
                <div class="finder-info-row"><span><?php echo $lang['man_modal_ip_type']; ?></span><strong>IPv4</strong></div>
                <div class="finder-info-row"><span><?php echo $lang['man_modal_ip_price']; ?></span><strong style="color:var(--primary);">$6.99/mes</strong></div>
                <div class="finder-info-row" style="margin-bottom:14px;">
                    <span>Tu saldo</span>
                    <strong id="user-balance-ip">$0.00</strong>
                </div>
                <p style="font-size:0.82rem; color:var(--text-muted); line-height:1.55; margin-bottom:10px;">
                    <i class="fas fa-clock" style="color:#eab308;"></i> <?php echo $lang['man_modal_ip_warn1']; ?>
                </p>
                <p style="font-size:0.82rem; color:var(--text-muted); line-height:1.55; margin-bottom:16px;">
                    <i class="fas fa-info-circle" style="color:var(--primary);"></i> <?php echo $lang['man_modal_ip_warn2']; ?>
                </p>
                <button class="btn btn-manage" id="btnBuyIpInline" onclick="confirmBuyIp(true)" style="width:100%;">
                    <i class="fas fa-shopping-cart"></i> <?php echo $lang['man_modal_ip_btn']; ?>
                </button>
                <div id="ip-purchase-result" style="margin-top:10px; padding:10px; border-radius:8px; display:none;"></div>
            </div>
        </div>
    </div>

    <!-- Abuse Reports -->
    <div class="finder-window" id="abuse-section" style="margin-bottom: 14px;">
        <div class="finder-titlebar">
            <div class="finder-lights">
                <span class="l-close"></span><span class="l-min"></span><span class="l-max"></span>
            </div>
            <span class="finder-title-text">
                <i class="fas fa-exclamation-circle" style="color:#ef4444;"></i> <?php echo $lang['abuse_title'] ?? 'Abuse Reports'; ?>
            </span>
            <span id="abuse-loading" style="display:none; margin-left:8px;">
                <i class="fas fa-spinner fa-spin" style="color:var(--primary); font-size:0.8rem;"></i>
            </span>
        </div>
        <div class="finder-body" style="padding-bottom:0;">
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo $lang['abuse_col_ip'] ?? 'IP'; ?></th>
                            <th><?php echo $lang['abuse_col_type'] ?? 'Type'; ?></th>
                            <th><?php echo $lang['abuse_col_date'] ?? 'Date'; ?></th>
                            <th><?php echo $lang['abuse_col_desc'] ?? 'Description'; ?></th>
                        </tr>
                    </thead>
                    <tbody id="abuse-body">
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">
                                <i class="fas fa-spinner fa-spin"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Order History -->
    <div class="finder-window" style="margin-bottom: 14px;">
        <div class="finder-titlebar">
            <div class="finder-lights">
                <span class="l-close"></span><span class="l-min"></span><span class="l-max"></span>
            </div>
            <span class="finder-title-text">
                <i class="fas fa-history"></i> <?php echo $lang['man_hist_title']; ?>
            </span>
            <span id="orders-loading-indicator" style="display:none; margin-left:8px;">
                <i class="fas fa-spinner fa-spin" style="color:var(--primary); font-size:0.8rem;"></i>
            </span>
        </div>
        <div class="finder-body" style="padding-bottom: 0;">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo $lang['man_hist_id']; ?></th>
                            <th><?php echo $lang['man_hist_plan']; ?></th>
                            <th><?php echo $lang['man_hist_duration']; ?></th>
                            <th><?php echo $lang['man_hist_amount']; ?></th>
                            <th><?php echo $lang['man_hist_date']; ?></th>
                            <th><?php echo $lang['man_hist_status']; ?></th>
                        </tr>
                    </thead>
                    <tbody id="orders-body"></tbody>
                </table>
            </div>
            <div id="orders-pagination" class="orders-pagination"></div>
        </div>
    </div>
    </div>
</main>

<!-- Modals (via partials) -->
<?php
include __DIR__ . '/partials/modal_vnc.php';
include __DIR__ . '/partials/modal_upgrade.php';
include __DIR__ . '/partials/modal_reinstall.php';
include __DIR__ . '/partials/modal_confirm.php';
include __DIR__ . '/partials/modal_buy_ip.php';
include __DIR__ . '/partials/modal_terminal.php';
?>

<script>
    // Initialize globals for external scripts
    window.serverId = "<?php echo $server_id; ?>";
    const LANG_MAN = <?php echo json_encode([
        'title'              => $lang['man_title'],
        'loading'            => $lang['man_loading'],
        'stat_active'        => $lang['man_stat_active'],
        'stat_prov'          => $lang['man_stat_provisioning'],
        'stat_inactive'      => $lang['man_stat_inactive'],
        'js_err'             => $lang['man_js_err'],
        'js_success'         => $lang['man_js_success'],
        'js_warn'            => $lang['man_js_warn'],
        'js_action_exec'     => $lang['man_js_action_exec'],
        'js_confirm_title'   => $lang['man_js_confirm_title'],
        'js_confirm_action'  => $lang['man_js_confirm_action'],
        'js_confirm_stop'    => $lang['man_js_confirm_stop'],
        'js_confirm_rest'    => $lang['man_js_confirm_rest'],
        'js_act_start'       => $lang['man_js_act_start'],
        'js_act_rest'        => $lang['man_js_act_rest'],
        'js_act_stop'        => $lang['man_js_act_stop'],
        'vnc_err_connect'    => $lang['man_vnc_err_connect'],
        'vnc_err_comm'       => $lang['man_vnc_err_comm'],
        'vnc_err_incomplete' => $lang['man_vnc_err_incomplete'],
        'vnc_err_connection' => $lang['man_vnc_err_connection'],
        'stats_disk_usage'   => $lang['man_stats_disk_usage'],
        'err_invalid_id'     => $lang['man_err_invalid_id'],
        'err_modal_not_found'=> $lang['man_err_modal_not_found'],
        'err_modal_elements' => $lang['man_err_modal_elements'],
        'js_reinstall_confirm'=> $lang['man_js_reinstall_confirm'],
        'js_processing'      => $lang['man_js_processing'],
        'js_reinstalling'    => $lang['man_js_reinstalling'],
        'js_reset_pw_success'=> $lang['man_js_reset_pw_success'],
        'js_reset_pw_new'    => $lang['man_js_reset_pw_new'],
        'opt_extend'         => $lang['man_modal_opt_extend'],
        'opt_upgrade'        => $lang['man_modal_opt_upgrade'],
        'opt_btn_extend'     => $lang['man_modal_opt_extend'],
        'opt_btn_upgrade'    => $lang['man_modal_opt_upgrade'],
        'hist_completed'     => $lang['man_hist_completed'],
        'hist_pending'       => $lang['man_hist_pending'],
        'hist_failed'        => $lang['man_hist_failed'],
        'ip_none'            => $lang['man_ip_none'],
        'ip_buy'             => $lang['man_ip_buy'],
        'ip_assigning'       => $lang['man_ip_assigning'],
        'ip_no_expiry'       => $lang['man_ip_no_expiry'],
        'ip_copy'            => $lang['man_ip_copy'],
        'ip_btn_buy'         => $lang['man_modal_ip_btn'],
        'scripts_starting'   => $lang['man_scripts_starting'],
        'scripts_conn_ended' => $lang['man_scripts_conn_ended'],
        'scripts_badge_ended'=> $lang['man_scripts_badge_ended'],
        'scripts_badge_ok'   => $lang['man_scripts_badge_ok'],
        'scripts_badge_fail' => $lang['man_scripts_badge_fail'],
        'scripts_run_again'  => $lang['man_scripts_run_again'],
        'scripts_back'       => $lang['man_scripts_back'],
    ]); ?>;
</script>

<?php
// ── JS bundle (all from /dashboard/js/manage/) ──────────────────────────────
$jsDir = '/var/www/veneko/dashboard/js/manage/';
$jsFiles = ['charts', 'power', 'vnc', 'upgrade', 'main', 'ip_manager', 'orders', 'abuse_reports'];
foreach ($jsFiles as $f):
    $v = jsv("$f.min.js", $jsDir);
?>
<script src="/dashboard/js/manage/<?php echo $f; ?>.min.js?v=<?php echo $v; ?>"></script>
<?php endforeach; ?>

<a href="../support" title="<?php echo $lang['dash_menu_support']; ?>" style="
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9999;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    box-shadow: 0 4px 18px rgba(0,0,0,0.35);
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
" onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 6px 24px rgba(0,0,0,0.45)'"
   onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 18px rgba(0,0,0,0.35)'">
    <i class="fas fa-headset"></i>
</a>

<?php include '../footer.php'; ?>