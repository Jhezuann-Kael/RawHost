<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

require_once '../api/config.php';
require_once '../includes/lang_loader.php';
require_once '../repositories/VpsRepository.php';

$server_id = intval($_GET['id'] ?? 0);
if (!$server_id) { http_response_code(400); exit('Invalid ID'); }

$vpsRepo = new VpsRepository();
$vps = $vpsRepo->getById($server_id);

if (!$vps || ($vps['user_id'] != $_SESSION['user_id'] && empty($_SESSION['is_superuser']))) {
    http_response_code(403);
    exit(htmlspecialchars($lang['vps_vnc_forbidden']));
}

$vpsName = htmlspecialchars($vps['name'] ?? 'VPS #' . $server_id);
?><!DOCTYPE html>
<html lang="<?php echo $lang['txt_date_format'] === 'es-ES' ? 'es' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['vps_vnc_title']; ?> — <?php echo $vpsName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="module">
        import RFB from 'https://cdn.jsdelivr.net/npm/@novnc/novnc@1.4.0/core/rfb.js';
        window.RFB = RFB;
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; background: #0a0e1a; color: #e2e8f0; font-family: system-ui, sans-serif; overflow: hidden; }

        .vnc-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .vnc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: #111827;
            border-bottom: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
            gap: 10px;
        }
        .vnc-header-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: .9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .vnc-header-title i { color: #a78bfa; }
        .vnc-server-name { color: #94a3b8; font-size: .8rem; margin-left: 4px; }

        .vnc-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .vnc-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 11px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
            font-size: .8rem;
            cursor: pointer;
            transition: background .15s;
            white-space: nowrap;
        }
        .vnc-btn:hover { background: rgba(255,255,255,.12); }
        .vnc-btn:disabled { opacity: .4; cursor: not-allowed; }
        .vnc-btn.primary { background: rgba(99,102,241,.2); border-color: rgba(99,102,241,.4); color: #a5b4fc; }
        .vnc-btn.primary:hover { background: rgba(99,102,241,.3); }
        .vnc-btn.danger { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.3); color: #fca5a5; }
        .vnc-btn.danger:hover { background: rgba(239,68,68,.22); }

        .vnc-pass-display {
            font-size: .78rem;
            color: #64748b;
            display: none;
            gap: 5px;
            align-items: center;
        }
        .vnc-pass-display.visible { display: flex; }
        .vnc-pass-display span { color: #94a3b8; font-family: monospace; }
        .vnc-copy-btn {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 2px 4px;
            font-size: .75rem;
        }
        .vnc-copy-btn:hover { color: #94a3b8; }

        .vnc-status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #374151;
            flex-shrink: 0;
            transition: background .3s;
        }
        .vnc-status-dot.connecting { background: #f59e0b; animation: pulse 1s infinite; }
        .vnc-status-dot.connected  { background: #10b981; }
        .vnc-status-dot.error      { background: #ef4444; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        .vnc-body {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: #000;
        }

        #vnc-screen {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #vnc-screen canvas { display: block; }

        .vnc-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            background: #000;
            z-index: 10;
        }
        .vnc-overlay.hidden { display: none; }
        .vnc-overlay-icon { font-size: 3.5rem; color: #374151; }
        .vnc-overlay-msg { color: #64748b; font-size: .9rem; }
        .vnc-overlay-err { color: #f87171; font-size: .85rem; text-align: center; max-width: 360px; }
    </style>
</head>
<body>

<div class="vnc-shell">
    <!-- Header bar -->
    <div class="vnc-header">
        <div class="vnc-header-title">
            <span class="vnc-status-dot" id="statusDot"></span>
            <i class="fas fa-desktop"></i>
            <?php echo $lang['vps_vnc_title']; ?>
            <span class="vnc-server-name">— <?php echo $vpsName; ?></span>
        </div>

        <div class="vnc-controls">
            <div class="vnc-pass-display" id="passDisplay">
                <?php echo $lang['vps_vnc_password']; ?>:
                <span id="passText">…</span>
                <button class="vnc-copy-btn" id="copyPassBtn" title="Copy"><i class="fas fa-copy"></i></button>
            </div>
            <button class="vnc-btn" id="btnCtrlAltDel" onclick="sendCtrlAltDel()" disabled title="<?php echo $lang['vps_vnc_ctrl_alt_del']; ?>">
                <i class="fas fa-power-off"></i> <?php echo $lang['vps_vnc_ctrl_alt_del']; ?>
            </button>
            <button class="vnc-btn danger" id="btnDisconnect" onclick="disconnect()" disabled>
                <i class="fas fa-times"></i> <?php echo $lang['vps_vnc_disconnect']; ?>
            </button>
        </div>
    </div>

    <!-- VNC canvas area -->
    <div class="vnc-body">
        <div id="vnc-screen"></div>

        <!-- Placeholder shown before connect / on error -->
        <div class="vnc-overlay" id="overlay">
            <i class="fas fa-desktop vnc-overlay-icon"></i>
            <div class="vnc-overlay-msg" id="overlayMsg"></div>
            <div class="vnc-overlay-err" id="overlayErr" style="display:none;"></div>
            <button class="vnc-btn primary" id="btnConnect" onclick="connect()">
                <i class="fas fa-plug"></i> <?php echo $lang['vps_vnc_connect']; ?>
            </button>
        </div>
    </div>
</div>

<script>
const SERVER_ID = <?php echo $server_id; ?>;
const LANG = <?php echo json_encode([
    'connecting'  => $lang['vps_vnc_connecting'],
    'conn_closed' => $lang['vps_vnc_conn_closed'],
    'conn_lost'   => $lang['vps_vnc_conn_lost'],
    'err_creds'   => $lang['vps_vnc_err_creds'],
    'err_auth'    => $lang['vps_vnc_err_auth'],
    'err_conn'    => $lang['vps_vnc_err_conn'],
    'connect'     => $lang['vps_vnc_connect'],
]); ?>;

let rfb = null;

const dot        = document.getElementById('statusDot');
const overlay    = document.getElementById('overlay');
const overlayMsg = document.getElementById('overlayMsg');
const overlayErr = document.getElementById('overlayErr');
const btnConnect    = document.getElementById('btnConnect');
const btnDisconnect = document.getElementById('btnDisconnect');
const btnCtrlAltDel = document.getElementById('btnCtrlAltDel');
const passDisplay   = document.getElementById('passDisplay');
const passText      = document.getElementById('passText');
const copyPassBtn   = document.getElementById('copyPassBtn');

function setDot(state) {
    dot.className = 'vnc-status-dot' + (state ? ' ' + state : '');
}

function showOverlay(msg, err) {
    overlayMsg.textContent = msg || '';
    overlayErr.textContent = err || '';
    overlayErr.style.display = err ? 'block' : 'none';
    overlay.classList.remove('hidden');
    btnConnect.style.display = '';
    btnDisconnect.disabled = true;
    btnCtrlAltDel.disabled = true;
    passDisplay.classList.remove('visible');
}

function hideOverlay() {
    overlay.classList.add('hidden');
    btnDisconnect.disabled = false;
    btnCtrlAltDel.disabled = false;
}

async function connect() {
    btnConnect.disabled = true;
    btnConnect.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG.connecting}`;
    overlayMsg.textContent = LANG.connecting;
    overlayErr.style.display = 'none';
    setDot('connecting');

    try {
        const r   = await fetch(`/api/servers/vnc?id=${SERVER_ID}`);
        const data = await r.json();

        if (!data.success || !data.vnc_url || !data.vnc_password) {
            showOverlay('', data.message || LANG.err_creds);
            setDot('error');
            return;
        }

        passText.textContent = data.vnc_password;
        passDisplay.classList.add('visible');
        copyPassBtn.onclick = () => navigator.clipboard.writeText(data.vnc_password);

        let wsUrl = data.vnc_url;
        if (!wsUrl.startsWith('ws')) {
            wsUrl = 'wss://hypervisor.kordindustries.io:443/vnc?url=' + encodeURIComponent(wsUrl);
        }

        const screen = document.getElementById('vnc-screen');
        rfb = new RFB(screen, wsUrl, {
            credentials: { password: data.vnc_password, username: 'root' }
        });
        rfb.scaleViewport = true;
        rfb.resizeSession = true;

        rfb.addEventListener('connect', () => {
            hideOverlay();
            setDot('connected');
        });

        rfb.addEventListener('disconnect', (e) => {
            const msg = e.detail.clean ? LANG.conn_closed : LANG.conn_lost;
            showOverlay(msg);
            setDot('');
            rfb = null;
        });

        rfb.addEventListener('credentialsfailed', () => {
            showOverlay('', LANG.err_auth);
            setDot('error');
            rfb = null;
        });

    } catch (err) {
        showOverlay('', LANG.err_conn + ': ' + err.message);
        setDot('error');
    } finally {
        btnConnect.disabled = false;
        btnConnect.innerHTML = `<i class="fas fa-plug"></i> ${LANG.connect}`;
    }
}

function disconnect() {
    if (rfb) { rfb.disconnect(); rfb = null; }
}

function sendCtrlAltDel() {
    if (rfb) rfb.sendCtrlAltDel();
}

// Auto-connect when noVNC is ready
window.addEventListener('load', () => {
    if (window.RFB) {
        connect();
    } else {
        setTimeout(connect, 800);
    }
});
</script>
</body>
</html>
