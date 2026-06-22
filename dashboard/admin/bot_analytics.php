<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Bot Analytics';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Bot Analytics</h1>
            </div>
            <p>Uso del bot de Telegram por usuario</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por Telegram ID o usuario..."
                    onkeyup="debounceSearch()">
            </div>
        </div>
    </header>

    <!-- Summary Stats -->
    <div id="statsRow" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="stat-card" id="stat-unique">
            <div class="stat-icon" style="background:rgba(99,102,241,0.15); color:#6366f1;"><i class="fas fa-users"></i></div>
            <div class="stat-info"><span class="stat-number" id="s-unique">—</span><span class="stat-label">Usuarios únicos</span></div>
        </div>
        <div class="stat-card" id="stat-connected">
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

    <!-- Users Table -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Telegram ID</th>
                    <th>Username</th>
                    <th>Estado</th>
                    <th>Total Eventos</th>
                    <th>Start</th>
                    <th>Register</th>
                    <th>Link</th>
                    <th>Unlink</th>
                    <th>Nav</th>
                    <th>Última actividad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr><td colspan="11" style="text-align:center; padding:20px;">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="pagination" class="pagination"></div>

    <!-- Event History Modal -->
    <div id="eventsModal" class="modal-overlay">
        <div class="modal" style="width:700px; max-width:95vw;">
            <div class="modal-header">
                <h2><i class="fas fa-list" style="margin-right:8px; color:var(--primary);"></i>Historial: <span id="modalTgUser"></span></h2>
                <button class="btn-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" style="padding:0;">
                <table class="admin-table" style="margin:0; border-radius:0;">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Estado</th>
                            <th>Sección</th>
                            <th>Fecha</th>
                        </tr>
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
</main>

<style>
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .stat-info { display: flex; flex-direction: column; }
    .stat-number { font-size: 1.5rem; font-weight: 700; color: var(--text); }
    .stat-label  { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }

    .badge-connected { background: rgba(16,185,129,0.15); color:#10b981; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
    .badge-registered { background: rgba(59,130,246,0.15); color:#3b82f6; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }
    .badge-new { background: rgba(156,163,175,0.15); color:#9ca3af; padding:2px 8px; border-radius:12px; font-size:.75rem; font-weight:600; }

    .ev-badge { display:inline-block; min-width:26px; text-align:center; padding:1px 6px; border-radius:8px; font-size:.78rem; font-weight:600; }
    .ev-0 { color: var(--text-muted); }
    .ev-pos { background:rgba(99,102,241,0.12); color:#818cf8; }
</style>

<script>
    let currentPage = 1;
    let searchTimer;
    let modalTgId = '';
    let modalPage = 1;

    document.addEventListener('DOMContentLoaded', () => {
        loadStats();
        loadTable(1);
    });

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadTable(1), 500);
    }

    async function loadStats() {
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

    async function loadTable(page) {
        currentPage = page;
        const tbody  = document.getElementById('tableBody');
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

            renderTable(data.data);
            renderPagination(data, loadTable);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:red;">Error de conexión</td></tr>';
        }
    }

    function renderTable(rows) {
        const tbody = document.getElementById('tableBody');
        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;">Sin datos.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(r => {
            const stateMap = { connected: 'badge-connected', registered: 'badge-registered', new: 'badge-new' };
            const stateClass = stateMap[r.user_state] || 'badge-new';
            const stateLabel = r.user_state === 'connected' ? 'Conectado' : r.user_state === 'registered' ? 'Registrado' : 'Nuevo';
            const username = r.tg_username ? '@' + r.tg_username : '—';
            const lastSeen = r.last_seen ? new Date(r.last_seen).toLocaleString('es-VE') : '—';

            const ev = (n) => parseInt(n) > 0
                ? `<span class="ev-badge ev-pos">${n}</span>`
                : `<span class="ev-badge ev-0">0</span>`;

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
                <td style="font-size:.82rem; color:var(--text-muted);">${lastSeen}</td>
                <td>
                    <button class="action-btn btn-assign" onclick="openEvents('${r.tg_id}', '${username}')" title="Ver historial">
                        <i class="fas fa-list"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    function renderPagination(data, loadFn, containerId = 'pagination') {
        const container = document.getElementById(containerId);
        if (!data || data.pages <= 1) { container.innerHTML = ''; return; }

        const current = data.current_page;
        const total   = data.pages;
        let html = '';

        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="${loadFn.name}(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;

        const start = Math.max(1, current - 2);
        const end   = Math.min(total, current + 2);
        for (let i = start; i <= end; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="${loadFn.name}(${i})">${i}</button>`;
        }

        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="${loadFn.name}(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;
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
            const res  = await fetch(`../../api/admin/bot_analytics?${params}`);
            const data = await res.json();

            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;">Sin eventos.</td></tr>';
                document.getElementById('modalPagination').innerHTML = '';
                return;
            }

            const eventLabels = { start: 'Start', register: 'Register', link: 'Link', unlink: 'Unlink', nav: 'Nav' };
            const stateMap    = { connected: 'badge-connected', registered: 'badge-registered', new: 'badge-new' };

            tbody.innerHTML = data.data.map(e => {
                const date = new Date(e.created_at).toLocaleString('es-VE');
                const stateClass = stateMap[e.user_state] || 'badge-new';
                return `<tr>
                    <td><span class="ev-badge ev-pos">${eventLabels[e.event_type] || e.event_type}</span></td>
                    <td><span class="${stateClass}">${e.user_state || '—'}</span></td>
                    <td style="font-size:.82rem; color:var(--text-muted);">${e.section || '—'}</td>
                    <td style="font-size:.82rem; color:var(--text-muted);">${date}</td>
                </tr>`;
            }).join('');

            renderPagination(data, loadEvents, 'modalPagination');
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:red;">Error</td></tr>';
        }
    }

    function closeModal() {
        document.getElementById('eventsModal').classList.remove('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('eventsModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeModal();
        });
    });
</script>

<?php include '../footer.php'; ?>
