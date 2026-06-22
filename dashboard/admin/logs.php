<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Logs & Analytics';
include 'includes/header.php';

$logDir      = __DIR__ . '/../../logs/';
$selectedFile = $_GET['file'] ?? null;
$activeTab   = isset($_GET['tab']) && $_GET['tab'] === 'bot' ? 'bot' : 'logs';
$error       = null;
$logContent  = '';

if ($selectedFile) {
    $activeTab = 'logs';
    if (basename($selectedFile) !== $selectedFile || !file_exists("$logDir$selectedFile")) {
        $error        = 'Archivo inválido o no encontrado.';
        $selectedFile = null;
    } else {
        $logContent = file_get_contents("$logDir$selectedFile");
    }
}

$files = [];
if (is_dir($logDir)) {
    foreach (scandir($logDir) as $file) {
        if ($file !== '.' && $file !== '..' && !is_dir("$logDir$file")) {
            $files[] = ['name' => $file, 'size' => filesize("$logDir$file"), 'mtime' => filemtime("$logDir$file")];
        }
    }
}
?>

<style>
    .tab-bar {
        display: flex;
        gap: 4px;
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 10px;
        padding: 5px;
        margin-bottom: 24px;
        width: fit-content;
    }

    .tab-btn {
        padding: 8px 20px;
        border: none;
        border-radius: 7px;
        background: transparent;
        color: var(--admin-muted);
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.18s;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .tab-btn:hover { color: var(--admin-text); }
    .tab-btn.active { background: var(--admin-card); color: var(--admin-text); box-shadow: 0 1px 4px rgba(0,0,0,0.3); }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* Bot Analytics styles */
    .stat-card {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .stat-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .stat-info { display: flex; flex-direction: column; }
    .stat-number { font-size: 1.5rem; font-weight: 700; color: var(--admin-text); }
    .stat-label  { font-size: 0.75rem; color: var(--admin-muted); margin-top: 2px; }

    .badge-connected  { background:rgba(16,185,129,0.15); color:#10b981; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
    .badge-registered { background:rgba(59,130,246,0.15);  color:#3b82f6; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
    .badge-new        { background:rgba(156,163,175,0.15); color:#9ca3af; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }

    .ev-badge { display:inline-block; min-width:26px; text-align:center; padding:1px 6px; border-radius:8px; font-size:.78rem; font-weight:600; }
    .ev-0   { color: var(--admin-muted); }
    .ev-pos { background:rgba(99,102,241,0.12); color:#818cf8; }

    /* Log viewer */
    .log-card {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 20px;
    }
</style>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display:flex; align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Logs & Analytics</h1>
            </div>
            <p>Registros del sistema y analítica del bot</p>
        </div>
        <div class="table-controls" id="botSearchBar" style="display:<?php echo $activeTab === 'bot' ? 'flex' : 'none'; ?>">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por Telegram ID o usuario..." onkeyup="debounceSearch()">
            </div>
        </div>
    </header>

    <!-- Tabs -->
    <div class="tab-bar">
        <button class="tab-btn <?php echo $activeTab === 'logs' ? 'active' : ''; ?>" onclick="switchTab('logs')" id="tab-logs">
            <i class="fas fa-file-alt"></i> Logs del Sistema
        </button>
        <button class="tab-btn <?php echo $activeTab === 'bot' ? 'active' : ''; ?>" onclick="switchTab('bot')" id="tab-bot">
            <i class="fas fa-robot"></i> Bot Analytics
        </button>
    </div>

    <!-- Logs panel -->
    <div class="tab-panel <?php echo $activeTab === 'logs' ? 'active' : ''; ?>" id="panel-logs">
        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#f87171; padding:14px 18px; border-radius:8px; margin-bottom:20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($selectedFile): ?>
            <div class="log-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="margin:0; color:var(--primary);"><i class="fas fa-file-alt" style="margin-right:8px;"></i><?php echo htmlspecialchars($selectedFile); ?></h3>
                    <a href="logs" style="background:var(--admin-surface); border:1px solid var(--admin-border); color:var(--admin-text); padding:6px 14px; border-radius:6px; text-decoration:none; font-size:0.85rem;">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div style="background:#0f172a; padding:15px; border-radius:8px; overflow-x:auto;">
                    <?php if (empty($logContent)): ?>
                        <div style="color:var(--admin-muted); font-style:italic;">Archivo vacío.</div>
                    <?php else: ?>
                        <pre style="color:#e2e8f0; font-family:'Consolas','Monaco',monospace; font-size:0.85rem; margin:0; white-space:pre-wrap;"><?php echo htmlspecialchars($logContent); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="log-card">
                <h3 style="margin:0 0 20px; color:var(--admin-text);">Archivos disponibles</h3>
                <?php if (empty($files)): ?>
                    <p style="color:var(--admin-muted);">No se encontraron archivos de log en <?php echo htmlspecialchars($logDir); ?></p>
                <?php else: ?>
                    <div class="admin-table-container" style="margin:0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Tamaño</th>
                                    <th>Última modificación</th>
                                    <th style="text-align:right;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $f): ?>
                                    <tr>
                                        <td><i class="fas fa-file-code" style="color:var(--admin-muted); margin-right:8px;"></i><?php echo htmlspecialchars($f['name']); ?></td>
                                        <td style="color:var(--admin-muted);"><?php echo number_format($f['size'] / 1024, 2); ?> KB</td>
                                        <td style="color:var(--admin-muted);"><?php echo date('Y-m-d H:i:s', $f['mtime']); ?></td>
                                        <td style="text-align:right;">
                                            <a href="logs?file=<?php echo urlencode($f['name']); ?>" class="action-btn btn-assign" style="text-decoration:none;">
                                                Ver <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bot Analytics panel -->
    <div class="tab-panel <?php echo $activeTab === 'bot' ? 'active' : ''; ?>" id="panel-bot">
        <div id="statsRow" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.15); color:#6366f1;"><i class="fas fa-users"></i></div>
                <div class="stat-info"><span class="stat-number" id="s-unique">—</span><span class="stat-label">Usuarios únicos</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15); color:#10b981;"><i class="fas fa-link"></i></div>
                <div class="stat-info"><span class="stat-number" id="s-connected">—</span><span class="stat-label">Conectados</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15); color:#f59e0b;"><i class="fas fa-bolt"></i></div>
                <div class="stat-info"><span class="stat-number" id="s-events">—</span><span class="stat-label">Eventos totales</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.15); color:#3b82f6;"><i class="fas fa-compass"></i></div>
                <div class="stat-info"><span class="stat-number" id="s-nav">—</span><span class="stat-label">Navegaciones</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,68,68,0.15); color:#ef4444;"><i class="fas fa-user-times"></i></div>
                <div class="stat-info"><span class="stat-number" id="s-unlink">—</span><span class="stat-label">Desvinculaciones</span></div>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Telegram ID</th><th>Username</th><th>Estado</th>
                        <th>Total eventos</th><th>Start</th><th>Register</th>
                        <th>Link</th><th>Unlink</th><th>Nav</th>
                        <th>Última actividad</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="botTableBody">
                    <tr><td colspan="11" style="text-align:center; padding:20px;">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginationBot" class="pagination"></div>

        <!-- Event History Modal -->
        <div id="eventsModal" class="modal-overlay">
            <div class="modal" style="width:700px; max-width:95vw;">
                <div class="modal-header">
                    <h2><i class="fas fa-list" style="margin-right:8px; color:var(--primary);"></i>Historial: <span id="modalTgUser"></span></h2>
                    <button class="btn-close" onclick="closeEventsModal()"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body" style="padding:0;">
                    <table class="admin-table" style="margin:0; border-radius:0;">
                        <thead>
                            <tr><th>Evento</th><th>Estado</th><th>Sección</th><th>Fecha</th></tr>
                        </thead>
                        <tbody id="eventsBody">
                            <tr><td colspan="4" style="text-align:center; padding:20px;">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer" style="justify-content:center;">
                    <div id="modalPagination" class="pagination" style="margin:0;"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    let botLoaded    = false;
    let currentPageB = 1;
    let searchTimer;
    let modalTgId    = '';
    let modalPage    = 1;

    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($activeTab === 'bot'): ?>
        loadBotStats(); loadBotTable(1); botLoaded = true;
        <?php endif; ?>

        document.getElementById('eventsModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeEventsModal();
        });
    });

    // ── Tabs ──────────────────────────────────────────────────────────────────
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
        document.getElementById('botSearchBar').style.display = tab === 'bot' ? 'flex' : 'none';

        if (tab === 'bot' && !botLoaded) {
            loadBotStats();
            loadBotTable(1);
            botLoaded = true;
        }
    }

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadBotTable(1), 500);
    }

    // ── Bot Analytics ─────────────────────────────────────────────────────────
    async function loadBotStats() {
        try {
            const res  = await fetch('../../api/admin/bot_analytics?action=stats');
            const data = await res.json();
            if (!data.success) return;
            const s = data.stats;
            document.getElementById('s-unique').textContent    = s.unique_users    ?? 0;
            document.getElementById('s-connected').textContent = s.connected_users ?? 0;
            document.getElementById('s-events').textContent    = s.total_events    ?? 0;
            document.getElementById('s-nav').textContent       = s.ev_nav          ?? 0;
            document.getElementById('s-unlink').textContent    = s.ev_unlink       ?? 0;
        } catch (e) { console.error(e); }
    }

    async function loadBotTable(page) {
        currentPageB = page;
        const tbody  = document.getElementById('botTableBody');
        const search = document.getElementById('searchInput').value;

        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const params = new URLSearchParams({ page, limit: 20 });
            if (search) params.append('search', search);

            const res  = await fetch(`../../api/admin/bot_analytics?${params}`);
            const data = await res.json();

            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:20px;color:red;">${data.message || 'Error'}</td></tr>`;
                return;
            }
            renderBotTable(data.data);
            renderBotPagination(data, 'paginationBot', loadBotTable);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:red;">Error de conexión</td></tr>';
        }
    }

    function renderBotTable(rows) {
        const tbody = document.getElementById('botTableBody');
        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;">Sin datos.</td></tr>';
            return;
        }

        const stateMap = { connected: 'badge-connected', registered: 'badge-registered', new: 'badge-new' };
        const ev = n => parseInt(n) > 0
            ? `<span class="ev-badge ev-pos">${n}</span>`
            : `<span class="ev-badge ev-0">0</span>`;

        tbody.innerHTML = rows.map(r => {
            const stateClass = stateMap[r.user_state] || 'badge-new';
            const stateLabel = r.user_state === 'connected' ? 'Conectado' : r.user_state === 'registered' ? 'Registrado' : 'Nuevo';
            const username   = r.tg_username ? '@' + r.tg_username : '—';
            const lastSeen   = r.last_seen ? new Date(r.last_seen).toLocaleString() : '—';
            return `<tr>
                <td style="font-family:monospace; font-size:.85rem;">${r.tg_id}</td>
                <td>${username}</td>
                <td><span class="${stateClass}">${stateLabel}</span></td>
                <td><strong>${r.total_events}</strong></td>
                <td>${ev(r.ev_start)}</td>
                <td>${ev(r.ev_register)}</td>
                <td>${ev(r.ev_link)}</td>
                <td>${ev(r.ev_unlink)}</td>
                <td>${ev(r.ev_nav)}</td>
                <td style="font-size:.82rem; color:var(--admin-muted);">${lastSeen}</td>
                <td>
                    <button class="action-btn btn-assign" onclick="openEvents('${r.tg_id}', '${username}')" title="Ver historial">
                        <i class="fas fa-list"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    function renderBotPagination(data, containerId, loadFn) {
        const container = document.getElementById(containerId);
        if (!data || data.pages <= 1) { container.innerHTML = ''; return; }
        const current = data.current_page, total = data.pages;
        let html = `<button class="page-btn" ${current===1?'disabled':''} onclick="${loadFn.name}(${current-1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = Math.max(1,current-2); i <= Math.min(total,current+2); i++)
            html += `<button class="page-btn ${i===current?'active':''}" onclick="${loadFn.name}(${i})">${i}</button>`;
        html += `<button class="page-btn" ${current===total?'disabled':''} onclick="${loadFn.name}(${current+1})"><i class="fas fa-chevron-right"></i></button>`;
        container.innerHTML = html;
    }

    async function openEvents(tgId, username) {
        modalTgId = tgId;
        modalPage = 1;
        document.getElementById('modalTgUser').textContent = username + ' (' + tgId + ')';
        document.getElementById('eventsModal').classList.add('active');
        loadEvents(1);
    }

    async function loadEvents(page) {
        modalPage = page;
        const tbody = document.getElementById('eventsBody');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i></td></tr>';

        try {
            const params = new URLSearchParams({ action: 'events', tg_id: modalTgId, page, limit: 30 });
            const res    = await fetch(`../../api/admin/bot_analytics?${params}`);
            const data   = await res.json();

            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;">Sin eventos.</td></tr>';
                document.getElementById('modalPagination').innerHTML = '';
                return;
            }

            const eventLabels = { start: 'Start', register: 'Register', link: 'Link', unlink: 'Unlink', nav: 'Nav' };
            const stateMap    = { connected: 'badge-connected', registered: 'badge-registered', new: 'badge-new' };

            tbody.innerHTML = data.data.map(e => {
                const stateClass = stateMap[e.user_state] || 'badge-new';
                return `<tr>
                    <td><span class="ev-badge ev-pos">${eventLabels[e.event_type] || e.event_type}</span></td>
                    <td><span class="${stateClass}">${e.user_state || '—'}</span></td>
                    <td style="font-size:.82rem; color:var(--admin-muted);">${e.section || '—'}</td>
                    <td style="font-size:.82rem; color:var(--admin-muted);">${new Date(e.created_at).toLocaleString()}</td>
                </tr>`;
            }).join('');

            renderBotPagination(data, 'modalPagination', loadEvents);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:red;">Error</td></tr>';
        }
    }

    function closeEventsModal() {
        document.getElementById('eventsModal').classList.remove('active');
    }
</script>

<?php include '../footer.php'; ?>
