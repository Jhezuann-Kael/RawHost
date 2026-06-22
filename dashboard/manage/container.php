<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit;
}

require_once '../../api/config.php';
require_once '../../includes/lang_loader.php';
require_once '../../repositories/VpsRepository.php';

$server_id     = $_GET['id'] ?? 0;
$containerName = htmlspecialchars($_GET['container'] ?? '');

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && empty($_SESSION['is_superuser']))) {
    header("Location: ../index");
    exit;
}

$pageTitle = $containerName . " — " . SITE_NAME;

define('V', '1.0.9');
function jsv(string $file, string $base): string {
    $path = $base . $file;
    $mt = file_exists($path) ? filemtime($path) : time();
    return $mt . '.' . V;
}

$cssFiles = ['base', 'server-info', 'connection', 'modals', 'docker'];
$cssLinks = '';
foreach ($cssFiles as $f) {
    $v = jsv($f . '.min.css', __DIR__ . '/css/');
    $cssLinks .= "<link rel=\"stylesheet\" href=\"css/{$f}.min.css?v={$v}\">\n";
}

$extraHead = "
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css\">
<script src=\"https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js\"></script>
{$cssLinks}
<style>
    .ct-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    @media (max-width: 800px) { .ct-grid { grid-template-columns: 1fr; } }

    /* Connection info */
    .ct-connect-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .ct-connect-row:last-child { border-bottom: none; }
    .ct-connect-icon { width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.85rem; flex-shrink: 0; }
    .ct-connect-label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .ct-connect-val { font-size: 0.9rem; font-weight: 600; color: var(--text-light); display: flex; align-items: center; gap: 6px; }
    .ct-copy-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.75rem; padding: 2px 4px; border-radius: 4px; transition: color 0.15s; }
    .ct-copy-btn:hover { color: var(--primary); }

    /* Env vars */
    .ct-env-search { width: 100%; padding: 7px 10px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-light); font-size: 0.83rem; outline: none; margin-bottom: 10px; }
    .ct-env-search:focus { border-color: var(--primary); }
    .ct-env-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .ct-env-table tr:not(:last-child) td { border-bottom: 1px solid rgba(255,255,255,0.04); }
    .ct-env-table td { padding: 5px 4px; vertical-align: top; }
    .ct-env-key { color: #7dd3fc; font-family: monospace; white-space: nowrap; width: 1%; padding-right: 12px; }
    .ct-env-val { color: var(--text-muted); font-family: monospace; word-break: break-all; }

    /* ── Terminal wrapper ── */
    .ct-term-wrap { background: #0d0d17; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; }
    .ct-term-bar { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.07); flex-shrink: 0; }
    .ct-term-bar-title { flex: 1; font-size: 0.8rem; color: var(--text-muted); }

    /* Layout toggle */
    .ct-layout-btns { display: flex; gap: 2px; background: rgba(255,255,255,0.05); border-radius: 6px; padding: 2px; }
    .ct-layout-btn { background: none; border: none; color: var(--text-muted); padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.78rem; transition: all 0.15s; }
    .ct-layout-btn.active { background: rgba(99,102,241,0.2); color: var(--primary); }
    .ct-layout-btn:hover:not(.active) { color: var(--text-light); }

    /* ── Panels ── */
    .ct-panels { display: grid; grid-template-columns: 1fr 1fr; height: 460px; }
    .ct-panel { display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
    #ct-panel-logs { border-right: 1px solid rgba(255,255,255,0.07); }
    #logs-xterm { flex: 1; min-height: 0; padding: 6px; }

    /* Below layout */
    .ct-panels.layout-below { grid-template-columns: 1fr; height: auto; }
    .ct-panels.layout-below #ct-panel-logs { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.07); }
    .ct-panels.layout-below #logs-xterm { height: 380px; flex: none; }
    .ct-panels.layout-below .ct-cmd-history { max-height: 180px; flex: none; }

    /* ── Console panel ── */
    .ct-panel-header { padding: 8px 12px; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid rgba(255,255,255,0.05); flex-shrink: 0; display: flex; align-items: center; gap: 6px; }
    .ct-cmd-history { flex: 1; overflow-y: auto; min-height: 0; padding: 8px 12px; font-family: monospace; font-size: 0.8rem; }

    /* Command input */
    .ct-cmd-bar { display: flex; align-items: center; gap: 8px; padding: 9px 12px; background: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.07); flex-shrink: 0; }
    .ct-cmd-prompt { font-family: monospace; font-size: 0.85rem; color: #10b981; white-space: nowrap; }
    .ct-cmd-input { flex: 1; background: none; border: none; outline: none; color: var(--text-light); font-family: monospace; font-size: 0.85rem; }
    .ct-cmd-send { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: var(--primary); padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; transition: background 0.15s; white-space: nowrap; }
    .ct-cmd-send:hover { background: rgba(99,102,241,0.28); }
    .ct-cmd-send:disabled { opacity: 0.45; cursor: not-allowed; }
    .ct-cmd-stop { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; transition: background 0.15s; white-space: nowrap; }
    .ct-cmd-stop:hover { background: rgba(239,68,68,0.28); }
</style>";

include '../header.php';
?>

<main class="main-content" id="main-content">
    <div class="header">
        <div style="display:flex; align-items:center;">
            <a href="docker?id=<?php echo $server_id; ?>" class="toggle-btn" style="text-decoration:none; margin-right:15px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 style="margin:0;">
                <i class="fab fa-docker" style="color:#2496ed;"></i>
                <span style="color:var(--primary);"><?php echo $containerName; ?></span>
            </h1>
        </div>
        <div style="text-align:right; display:flex; align-items:center; gap:8px;">
            <span id="ct-status-badge" class="status-badge">loading...</span>
            <span class="status-badge active"><i class="fas fa-network-wired"></i> <?php echo $vps['ip_address']; ?></span>
        </div>
    </div>

    <!-- Info grid -->
    <div class="ct-grid" id="ct-info-grid">
        <!-- Connection info -->
        <div class="card">
            <h3 class="card-title"><i class="fas fa-plug"></i> Connection Info</h3>
            <div id="ct-connect-body">
                <div style="text-align:center; padding:30px; color:var(--text-muted);">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
        </div>

        <!-- Environment variables -->
        <div class="card">
            <h3 class="card-title"><i class="fas fa-list-ul"></i> Environment Variables</h3>
            <input class="ct-env-search" id="ct-env-search" placeholder="Filter variables..." oninput="filterEnv(this.value)">
            <div id="ct-env-body">
                <div style="text-align:center; padding:30px; color:var(--text-muted);">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs + command -->
    <div class="ct-term-wrap">
        <div class="ct-term-bar">
            <i class="fas fa-terminal" style="color:var(--primary);"></i>
            <span class="ct-term-bar-title"><i class="fab fa-docker"></i> <?php echo $containerName; ?></span>
            <span id="log-status" class="status-badge active">CONNECTED</span>
            <button class="btn btn-outline btn-sm" onclick="restartStream()">
                <i class="fas fa-sync-alt"></i> Restart
            </button>
            <div class="ct-layout-btns">
                <button class="ct-layout-btn active" id="btn-layout-split" onclick="setLayout('split')" title="Split view">
                    <i class="fas fa-columns"></i>
                </button>
                <button class="ct-layout-btn" id="btn-layout-below" onclick="setLayout('below')" title="Console below">
                    <i class="fas fa-grip-lines"></i>
                </button>
            </div>
        </div>

        <div class="ct-panels" id="ct-panels">
            <!-- Logs panel -->
            <div class="ct-panel" id="ct-panel-logs">
                <div id="logs-xterm"></div>
            </div>

            <!-- Console panel -->
            <div class="ct-panel" id="ct-panel-cmd">
                <div class="ct-panel-header">
                    <i class="fas fa-terminal" style="color:var(--primary);"></i> Console
                </div>
                <div id="ct-cmd-history" class="ct-cmd-history"></div>
                <div class="ct-cmd-bar">
                    <span class="ct-cmd-prompt">$&nbsp;</span>
                    <input class="ct-cmd-input" id="ct-cmd-input" placeholder="docker exec command..." autocomplete="off" spellcheck="false">
                    <button class="ct-cmd-send" id="ct-cmd-send" onclick="sendCommand()">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                    <button class="ct-cmd-stop" id="ct-cmd-stop" onclick="stopCommand()" style="display:none;">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const serverId     = "<?php echo (int)$server_id; ?>";
const containerName = "<?php echo addslashes($containerName); ?>";
const serverIp     = "<?php echo addslashes($vps['ip_address']); ?>";

let term, fitAddon, sse;
let _envData = {};

// ── Inspect ───────────────────────────────────────────────────────────────────

async function loadInspect() {
    try {
        const res  = await fetch(`../../api/servers/docker_inspect?id=${serverId}&container=${encodeURIComponent(containerName)}`);
        const data = await res.json();

        if (!data.success) {
            document.getElementById('ct-connect-body').innerHTML = `<p style="color:#ef4444">${data.message}</p>`;
            document.getElementById('ct-env-body').innerHTML = '';
            return;
        }

        // Status badge
        const badge = document.getElementById('ct-status-badge');
        const up = data.status === 'running';
        badge.textContent = data.status.toUpperCase();
        badge.className = 'status-badge' + (up ? ' active' : '');

        renderConnect(data);
        renderEnv(data.env);
    } catch (e) {
        document.getElementById('ct-connect-body').innerHTML = `<p style="color:#ef4444">Error: ${e.message}</p>`;
    }
}

function renderConnect(data) {
    const ip = data.server_ip;
    const rows = [];

    // Ports
    if (data.ports && data.ports.length > 0) {
        data.ports.forEach(p => {
            const hostPort = p.host;
            const addr = `${ip}:${hostPort}`;
            rows.push(connectRow('fas fa-ethernet', p.container.replace('/tcp','').replace('/udp',''), addr, true));
        });
    } else {
        rows.push(connectRow('fas fa-ethernet', 'Ports', 'No ports exposed', false));
    }

    // Image
    rows.push(connectRow('fab fa-docker', 'Image', data.image, false));

    // Started
    if (data.started) {
        const d = new Date(data.started);
        rows.push(connectRow('fas fa-clock', 'Started', d.toLocaleString(), false));
    }

    // Game-server specific highlights from env
    const highlights = {
        'SERVER_TYPE':  { icon: 'fas fa-cubes', label: 'Server Type' },
        'VERSION':      { icon: 'fas fa-tag',   label: 'Version' },
        'MC_VERSION':   { icon: 'fas fa-tag',   label: 'MC Version' },
        'MEMORY':       { icon: 'fas fa-memory', label: 'Memory' },
        'ONLINE_MODE':  { icon: 'fas fa-shield-alt', label: 'Online Mode' },
        'EULA':         { icon: 'fas fa-file-contract', label: 'EULA' },
    };
    Object.entries(highlights).forEach(([key, meta]) => {
        if (data.env[key] !== undefined) {
            rows.push(connectRow(meta.icon, meta.label, data.env[key], false));
        }
    });

    document.getElementById('ct-connect-body').innerHTML = rows.join('');
}

function connectRow(icon, label, value, copyable) {
    const copyBtn = copyable
        ? `<button class="ct-copy-btn" onclick="copyText('${escAttr(value)}')" title="Copy"><i class="fas fa-copy"></i></button>`
        : '';
    return `
    <div class="ct-connect-row">
        <div class="ct-connect-icon"><i class="${icon}"></i></div>
        <div style="flex:1; min-width:0;">
            <div class="ct-connect-label">${escHtml(label)}</div>
            <div class="ct-connect-val">${escHtml(value)}${copyBtn}</div>
        </div>
    </div>`;
}

function renderEnv(env) {
    _envData = env;
    document.getElementById('ct-env-body').innerHTML = buildEnvTable(Object.entries(env));
}

function buildEnvTable(entries) {
    if (!entries.length) return '<p style="color:var(--text-muted); font-size:0.85rem;">No environment variables.</p>';
    const rows = entries.map(([k, v]) =>
        `<tr class="ct-env-row">
            <td class="ct-env-key">${escHtml(k)}</td>
            <td class="ct-env-val">${escHtml(v)}</td>
        </tr>`
    ).join('');
    return `<div style="max-height:260px; overflow-y:auto;"><table class="ct-env-table"><tbody>${rows}</tbody></table></div>`;
}

function filterEnv(q) {
    q = q.toLowerCase();
    const entries = Object.entries(_envData).filter(([k, v]) =>
        k.toLowerCase().includes(q) || v.toLowerCase().includes(q)
    );
    document.getElementById('ct-env-body').innerHTML = buildEnvTable(entries);
}

// ── Logs ──────────────────────────────────────────────────────────────────────

function initLogs() {
    const el = document.getElementById('logs-xterm');
    term = new Terminal({
        cursorBlink: false,
        disableStdin: true,
        scrollback: 10000,
        fontSize: 13,
        fontFamily: '"Fira Code", "Cascadia Code", monospace',
        theme: { background: '#0d0d17', foreground: '#e2e8f0' },
    });
    fitAddon = new FitAddon.FitAddon();
    term.loadAddon(fitAddon);
    term.open(el);
    setTimeout(() => fitAddon.fit(), 120);
    window.addEventListener('resize', () => fitAddon.fit());
    startLogStream();
}

function startLogStream() {
    if (sse) sse.close();
    sse = new EventSource(`../../api/servers/docker_logs?id=${serverId}&container=${encodeURIComponent(containerName)}`);
    setLogStatus('CONNECTED', true);

    sse.onmessage = e => {
        const d = JSON.parse(e.data);
        if (d.type === 'output') {
            term.write(d.msg.replace(/\r?\n/g, '\r\n'));
        } else if (d.type === 'error') {
            term.write(`\x1b[31m[ERROR] ${d.msg}\x1b[0m\r\n`);
            setLogStatus('ERROR', false);
        }
    };

    sse.onerror = () => {
        setLogStatus('DISCONNECTED', false);
        sse.close();
        sse = null;
    };
}

function restartStream() {
    if (term) term.clear();
    startLogStream();
}

function setLogStatus(text, active) {
    const el = document.getElementById('log-status');
    el.textContent = text;
    el.className = 'status-badge' + (active ? ' active' : '');
}

// ── Command exec (streaming) ──────────────────────────────────────────────────

let _cmdSse = null;

document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && document.activeElement === document.getElementById('ct-cmd-input')) {
        sendCommand();
    }
});

function sendCommand() {
    const input   = document.getElementById('ct-cmd-input');
    const sendBtn = document.getElementById('ct-cmd-send');
    const stopBtn = document.getElementById('ct-cmd-stop');
    const cmd     = input.value.trim();
    if (!cmd || _cmdSse) return;

    input.disabled = true;
    sendBtn.style.display = 'none';
    stopBtn.style.display = '';

    appendCmdHistory(`<span style="color:#10b981">$ ${escHtml(cmd)}</span>`);
    input.value = '';

    // Output accumulates in a single pre-like element
    const outEl = document.createElement('div');
    outEl.style.cssText = 'color:var(--text-muted); white-space:pre-wrap; font-family:monospace; font-size:0.8rem; padding:2px 0 6px 0;';
    document.getElementById('ct-cmd-history').appendChild(outEl);
    document.getElementById('ct-cmd-history').style.display = '';

    const url = `../../api/servers/docker_exec_stream?id=${encodeURIComponent(serverId)}&container=${encodeURIComponent(containerName)}&command=${encodeURIComponent(cmd)}`;
    _cmdSse = new EventSource(url);

    _cmdSse.onmessage = e => {
        const d = JSON.parse(e.data);
        if (d.type === 'output') {
            outEl.textContent += d.msg;
            outEl.scrollIntoView({ block: 'nearest' });
        } else if (d.type === 'error') {
            outEl.style.color = '#ef4444';
            outEl.textContent += d.msg;
        } else if (d.type === 'done') {
            _cmdDone();
        }
    };

    _cmdSse.onerror = () => {
        if (_cmdSse) { outEl.textContent += '\n[stream closed]'; }
        _cmdDone();
    };
}

function stopCommand() {
    if (_cmdSse) { _cmdSse.close(); _cmdSse = null; }
    _cmdDone();
}

function _cmdDone() {
    if (_cmdSse) { _cmdSse.close(); _cmdSse = null; }
    const input   = document.getElementById('ct-cmd-input');
    const sendBtn = document.getElementById('ct-cmd-send');
    const stopBtn = document.getElementById('ct-cmd-stop');
    input.disabled    = false;
    sendBtn.style.display = '';
    stopBtn.style.display = 'none';
    input.focus();
}

function appendCmdHistory(html) {
    const box = document.getElementById('ct-cmd-history');
    const line = document.createElement('div');
    line.style.cssText = 'padding: 2px 0; border-bottom: 1px solid rgba(255,255,255,0.04);';
    line.innerHTML = html;
    box.appendChild(line);
    box.scrollTop = box.scrollHeight;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s).replace(/'/g, "\\'");
}
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        // brief visual feedback handled by browser
    });
}

// ── Layout toggle ─────────────────────────────────────────────────────────────

function setLayout(mode) {
    const panels = document.getElementById('ct-panels');
    panels.classList.toggle('layout-below', mode === 'below');
    document.getElementById('btn-layout-split').classList.toggle('active', mode === 'split');
    document.getElementById('btn-layout-below').classList.toggle('active', mode === 'below');
    localStorage.setItem('ct-layout', mode);
    // Re-fit xterm after layout shift
    setTimeout(() => { if (fitAddon) fitAddon.fit(); }, 50);
}

// ── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('ct-layout') || 'split';
    if (saved !== 'split') setLayout(saved);
    loadInspect();
    initLogs();
    document.getElementById('ct-cmd-input').focus();
});
</script>

<?php include '../footer.php'; ?>
