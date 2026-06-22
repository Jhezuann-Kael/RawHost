<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
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

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && empty($_SESSION['is_superuser']))) {
    header("Location: ../index");
    exit;
}

$pageTitle = "Docker Manager - " . SITE_NAME;

// Reuse versioning helper from index.php logic
define('V', '1.0.9');
function jsv(string $file, string $base): string {
    $path = $base . $file;
    $mt = file_exists($path) ? filemtime($path) : time();
    return $mt . '.' . V;
}

$cssFiles = ['base', 'server-info', 'connection', 'modals', 'scripts', 'docker'];
$cssLinks = '';
foreach ($cssFiles as $f) {
    $path = __DIR__ . '/css/' . $f . '.css';
    $v = jsv($f . '.css', __DIR__ . '/css/');
    $cssLinks .= "<link rel=\"stylesheet\" href=\"css/{$f}.min.css?v={$v}\">\n";
}

$extraHead = "
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css\">
<script src=\"https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js\"></script>
{$cssLinks}";

include '../header.php';
?>

<main class="main-content" id="main-content">
    <div class="header">
        <div style="display: flex; align-items: center;">
            <a href="index?id=<?php echo $server_id; ?>" class="toggle-btn" style="text-decoration:none; margin-right:15px;"><i class="fas fa-arrow-left"></i></a>
            <h1 style="margin: 0;">
                <i class="fab fa-docker" style="color:#2496ed;"></i> Docker Manager
            </h1>
        </div>
        <div style="text-align: right;">
            <span class="status-badge active"><i class="fas fa-network-wired"></i> <?php echo $vps['ip_address']; ?></span>
        </div>
    </div>

    <div id="docker-main-view">
        <div class="grid-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Left: Containers List -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 class="card-title"><i class="fas fa-box"></i> Running Containers</h3>
                    <button class="btn btn-outline btn-sm" onclick="loadContainers()" id="btn-refresh-docker">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div id="containers-list">
                    <div style="text-align:center; padding:40px; color:var(--text-muted);">
                        <i class="fas fa-spinner fa-spin" style="font-size:1.5rem; color:var(--primary); margin-bottom:10px;"></i>
                        <p>Fetching container data...</p>
                    </div>
                </div>
            </div>

            <!-- Right: Docker Scripts -->
            <div class="card" id="conn-tab-scripts"> <!-- Added ID to fool scripts_manager.js -->
                <h3 class="card-title"><i class="fas fa-scroll"></i> Docker Scripts</h3>
                <div id="scripts-list-view">
                    <div id="docker-scripts-container">
                        <div style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem; color:var(--primary); margin-bottom:10px;"></i>
                            <p>Loading scripts...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline terminal (shown on Run) -->
    <div id="scripts-terminal-view" style="display:none; flex-direction:column; margin-top:20px;">
        <div id="scripts-term-header" style="display:flex; align-items:center; gap:12px; padding:12px; background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.1);">
            <button class="btn-icon-tiny" onclick="closeInlineTerminal()"><i class="fas fa-arrow-left"></i></button>
            <span id="scripts-term-title" style="flex:1; font-weight:700;"></span>
            <span id="scripts-term-badge" class="status-badge"></span>
            <button class="btn btn-manage" id="scripts-term-run-again" onclick="terminalRunAgain()" style="display:none;">
                <i class="fas fa-redo"></i> Run Again
            </button>
        </div>
        <div id="terminal-xterm"></div>
    </div>
</main>

<?php
$jsDir   = __DIR__ . '/../../dashboard/js/manage/';
$jsFiles = ['scripts_manager', 'docker_manager'];
foreach ($jsFiles as $f):
    $v = jsv($f . '.min.js', $jsDir);
?>
<script src="/dashboard/js/manage/<?php echo $f; ?>.min.js?v=<?php echo $v; ?>"></script>
<?php endforeach; ?>

<script>
    window.serverId = "<?php echo $server_id; ?>";
    window.LANG_MAN = <?php echo json_encode([
        'scripts_starting'   => $lang['man_scripts_starting'],
        'scripts_conn_ended' => $lang['man_scripts_conn_ended'],
        'scripts_badge_ended'=> $lang['man_scripts_badge_ended'],
        'scripts_badge_ok'   => $lang['man_scripts_badge_ok'],
        'scripts_badge_fail' => $lang['man_scripts_badge_fail'],
        'scripts_run_again'  => $lang['man_scripts_run_again'],
        'scripts_back'       => $lang['man_scripts_back'],
    ]); ?>;
</script>

<?php include '../footer.php'; ?>
