<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin VPS';
include 'includes/header.php';
?>

<style>
/* ── Botón gestionar ───────────────────────────────────────────────── */
.btn-manage-vps {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    background: var(--primary);
    color: #fff;
    white-space: nowrap;
    transition: opacity .15s;
}
.btn-manage-vps:hover { opacity: .85; }

/* ── Dropdown ⋮ ────────────────────────────────────────────────────── */
.vps-actions { display: flex; align-items: center; gap: 6px; }

.dropdown-wrap { position: relative; }

.btn-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px; height: 32px;
    border-radius: 7px;
    font-size: 1rem;
    border: 1px solid rgba(255,255,255,.1);
    cursor: pointer;
    background: rgba(255,255,255,.06);
    color: var(--text-light);
    transition: background .15s;
}
.btn-more:hover { background: rgba(255,255,255,.12); }

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    min-width: 180px;
    background: #1e2535;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.4);
    z-index: 999;
    overflow: hidden;
}
.dropdown-menu.open { display: block; }

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 14px;
    font-size: 0.82rem;
    color: var(--text-light);
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    transition: background .12s;
}
.dropdown-item:hover { background: rgba(255,255,255,.06); }
.dropdown-item i { width: 14px; text-align: center; }
.dropdown-item.danger { color: #f87171; }
.dropdown-item.danger:hover { background: rgba(248,113,113,.08); }
.dropdown-divider { height: 1px; background: rgba(255,255,255,.07); margin: 3px 0; }

/* ── Mobile cards ──────────────────────────────────────────────────── */
#vps-cards { display: none; }

@media (max-width: 680px) {
    .admin-table-container { display: none; }
    #vps-cards { display: block; }
}

.vps-card {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.vps-card-info { flex: 1; min-width: 0; }
.vps-card-name {
    font-weight: 700;
    font-size: .92rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.vps-card-ip {
    font-size: .75rem;
    color: #64748b;
    font-family: monospace;
    margin-top: 2px;
}
.vps-card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
    flex-wrap: wrap;
}
.vps-card-owner { font-size: .76rem; color: #94a3b8; }
.vps-card-time  { font-size: .76rem; font-weight: 600; }

.vps-card-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
</style>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display:flex; align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
                <h1>Gestión de VPS</h1>
            </div>
            <p>Monitoreo y administración de servidores virtuales</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nombre, IP, usuario…" onkeyup="debounceSearch()">
            </div>
        </div>
    </header>

    <!-- Desktop: tabla -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Servidor</th>
                    <th>Propietario</th>
                    <th>Tiempo restante</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="vpsTableBody">
                <tr><td colspan="7" style="text-align:center; padding:20px;">Cargando…</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Mobile: cards -->
    <div id="vps-cards"></div>

    <div id="pagination" class="pagination"></div>

    <!-- ── Modals ──────────────────────────────────────────────────── -->

    <div id="deleteModal" class="modal-overlay">
        <div class="modal" style="width:440px;">
            <div class="modal-header">
                <h2 style="color:#ff4d4d;"><i class="fas fa-triangle-exclamation" style="margin-right:8px;"></i>Eliminar VPS</h2>
                <button class="btn-close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-muted); font-size:.9em; margin-bottom:16px;">
                    Esta acción no se puede deshacer. Escribe el username del propietario para confirmar.
                </p>
                <div style="background:rgba(255,77,77,.08); border:1px solid rgba(255,77,77,.25); border-radius:8px; padding:14px; margin-bottom:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-size:.75em; color:var(--text-muted); margin-bottom:2px;">Propietario</div>
                            <div id="deleteVpsOwner" style="font-weight:700; color:#ff4d4d;"></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:.75em; color:var(--text-muted); margin-bottom:2px;">VPS</div>
                            <div id="deleteVpsName" style="font-weight:600; color:var(--text-light); font-size:.9em;"></div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="deleteVpsId">
                <input type="hidden" id="deleteVpsOwnerValue">
                <div class="form-group">
                    <label>Username del propietario</label>
                    <input type="text" id="deleteConfirmInput" class="form-control"
                        placeholder="Escribe el username para confirmar…" oninput="validateDeleteInput()">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeDeleteModal()">Cancelar</button>
                <button id="confirmDeleteBtn" class="btn" onclick="confirmDelete()" disabled
                    style="background:rgba(255,77,77,.15); color:#ff4d4d; border:1px solid rgba(255,77,77,.3); opacity:.5; cursor:not-allowed;">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <div id="renameModal" class="modal-overlay">
        <div class="modal" style="max-width:440px;">
            <div class="modal-header">
                <h2 style="color:var(--primary);"><i class="fas fa-pencil" style="margin-right:8px;"></i>Renombrar VPS</h2>
                <button class="btn-close" onclick="closeRenameModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="renameVpsId">
                <div class="form-group">
                    <label>Nuevo nombre</label>
                    <input type="text" id="renameInput" class="form-control" placeholder="Ej: server-mi-nombre">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeRenameModal()">Cancelar</button>
                <button class="btn btn-manage" onclick="confirmRename()" id="confirmRenameBtn">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <div id="changePasswordModal" class="modal-overlay">
        <div class="modal" style="max-width:440px;">
            <div class="modal-header">
                <h2 style="color:#0ea5e9;"><i class="fas fa-key" style="margin-right:8px;"></i>Cambiar Contraseña</h2>
                <button class="btn-close" onclick="closeChangePasswordModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="changePasswordVpsId">
                <p style="color:var(--text-muted); font-size:.85em; margin-bottom:16px; background:rgba(251,191,36,.08); border:1px solid rgba(251,191,36,.25); border-radius:6px; padding:10px;">
                    <i class="fas fa-info-circle" style="color:#fbbf24; margin-right:5px;"></i>
                    Solo actualiza la contraseña en la base de datos. No se comunica con el servicio externo.
                </p>
                <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="text" id="changePasswordInput" class="form-control" placeholder="Nueva contraseña…">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeChangePasswordModal()">Cancelar</button>
                <button class="btn btn-manage" onclick="confirmChangePassword()" id="confirmChangePasswordBtn">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <div id="reassignModal" class="modal-overlay">
        <div class="modal" style="max-width:440px;">
            <div class="modal-header">
                <h2 style="color:#f59e0b;"><i class="fas fa-user-edit" style="margin-right:8px;"></i>Reasignar VPS</h2>
                <button class="btn-close" onclick="closeReassignModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reassignVpsId">
                <div class="form-group" style="position:relative;">
                    <label>Buscar usuario</label>
                    <input type="text" id="userSearchInput" class="form-control" autocomplete="off"
                        placeholder="Username o email…" onkeyup="debounceUserSearch()">
                    <div id="userSearchResults"
                        style="position:absolute; top:100%; left:0; width:100%; background:#1e2535; border:1px solid rgba(255,255,255,.1); border-top:none; max-height:200px; overflow-y:auto; display:none; z-index:1001; border-radius:0 0 8px 8px; box-shadow:0 4px 12px rgba(0,0,0,.3);">
                    </div>
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label>ID seleccionado</label>
                    <input type="number" id="newUserId" class="form-control" readonly placeholder="—">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeReassignModal()">Cancelar</button>
                <button class="btn btn-manage" onclick="confirmReassign()">
                    <i class="fas fa-check"></i> Reasignar
                </button>
            </div>
        </div>
    </div>

</main>

<script>
let currentPage = 1;
let searchTimer, userSearchTimer;
let openDropdown = null;

document.addEventListener('DOMContentLoaded', () => {
    loadVPS(1);
    document.addEventListener('click', e => {
        if (openDropdown && !openDropdown.contains(e.target)) {
            openDropdown.querySelector('.dropdown-menu').classList.remove('open');
            openDropdown = null;
        }
        const res = document.getElementById('userSearchResults');
        const inp = document.getElementById('userSearchInput');
        if (res && res.style.display === 'block' && e.target !== inp && !res.contains(e.target))
            res.style.display = 'none';
    });
});

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadVPS(1), 500);
}
function debounceUserSearch() {
    clearTimeout(userSearchTimer);
    userSearchTimer = setTimeout(searchUsers, 300);
}

async function searchUsers() {
    const query = document.getElementById('userSearchInput').value;
    const res = document.getElementById('userSearchResults');
    if (query.length < 1) { res.style.display = 'none'; return; }
    try {
        const r = await fetch(`/api/admin/users/list?search=${encodeURIComponent(query)}&limit=5`);
        const data = await r.json();
        if (data.users && data.users.length > 0) {
            res.innerHTML = data.users.map(u => `
                <div onclick="selectUser(${u.id}, '${u.username}')"
                    style="padding:10px 14px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,.05); display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-weight:600; font-size:.85rem;">${u.username}</div>
                        <div style="font-size:.75rem; color:var(--text-muted);">${u.email}</div>
                    </div>
                    <span style="font-size:.75rem; background:rgba(255,255,255,.07); padding:2px 7px; border-radius:4px;">ID ${u.id}</span>
                </div>
            `).join('');
        } else {
            res.innerHTML = '<div style="padding:10px 14px; color:var(--text-muted); font-size:.85rem;">Sin resultados</div>';
        }
        res.style.display = 'block';
    } catch(e) { console.error(e); }
}
function selectUser(id, username) {
    document.getElementById('newUserId').value = id;
    document.getElementById('userSearchInput').value = username;
    document.getElementById('userSearchResults').style.display = 'none';
}

async function loadVPS(page) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    document.getElementById('vpsTableBody').innerHTML =
        '<tr><td colspan="7" style="text-align:center; padding:24px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando…</td></tr>';
    document.getElementById('vps-cards').innerHTML =
        '<div style="text-align:center; padding:24px; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
        const params = new URLSearchParams({ page, limit: 10 });
        if (search) params.append('search', search);
        const r = await fetch(`/api/admin/vps?${params}`);
        const data = await r.json();
        if (data.vps) {
            renderDesktop(data.vps);
            renderMobile(data.vps);
            renderPagination(data.pagination);
        } else {
            const msg = data.error || 'Error al cargar';
            document.getElementById('vpsTableBody').innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:#f87171;">${msg}</td></tr>`;
            document.getElementById('vps-cards').innerHTML = `<div style="text-align:center; padding:20px; color:#f87171;">${msg}</div>`;
        }
    } catch(e) {
        document.getElementById('vpsTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center; color:#f87171; padding:20px;">Error de conexión</td></tr>';
        document.getElementById('vps-cards').innerHTML = '<div style="text-align:center; color:#f87171; padding:20px;">Error de conexión</div>';
    }
}

/* ── Helpers ───────────────────────────────────────────────────────── */
function badgeClass(status) {
    if (status === 'ACTIVE' || status === 'RUNNING') return 'badge-running';
    if (status === 'PROVISIONING') return 'badge-provisioning';
    if (status === 'STOPPED') return 'badge-stopped';
    return 'badge-inactive';
}

function timeLeftHtml(expires_at) {
    if (!expires_at) return '<span style="color:#64748b;">—</span>';
    const diff = new Date(expires_at) - new Date();
    if (diff <= 0) {
        const h = Math.floor(Math.abs(diff) / 3600000);
        const d = Math.floor(h / 24);
        return `<span style="color:#f87171; font-weight:600;">Expirado</span><div style="font-size:.72rem; color:#f87171; opacity:.7;">hace ${d > 0 ? d+'d '+(h%24)+'h' : h+'h'}</div>`;
    }
    const h = Math.floor(diff / 3600000);
    const d = Math.floor(h / 24);
    const color = h < 24 ? '#f87171' : h < 72 ? '#fb923c' : '#4ade80';
    return `<span style="color:${color}; font-weight:600;">${d > 0 ? d+'d '+(h%24)+'h' : h+'h'}</span><div style="font-size:.72rem; color:#64748b;">${new Date(expires_at).toLocaleDateString()}</div>`;
}

function actionsHtml(vps) {
    const safeName  = (vps.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const safeOwner = (vps.owner_username || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return `
        <div class="vps-actions">
            <button class="btn-manage-vps" onclick="manageVPS(${vps.id})">
                <i class="fas fa-cogs"></i> <span>Gestionar</span>
            </button>
            <div class="dropdown-wrap" id="ddwrap-${vps.id}">
                <button class="btn-more" onclick="toggleDropdown(${vps.id})">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu" id="dd-${vps.id}">
                    <button class="dropdown-item" onclick="closeDropdown(${vps.id}); openRenameModal(${vps.id}, '${safeName}')">
                        <i class="fas fa-pencil" style="color:#a78bfa;"></i> Renombrar
                    </button>
                    <button class="dropdown-item" onclick="closeDropdown(${vps.id}); openChangePasswordModal(${vps.id})">
                        <i class="fas fa-key" style="color:#38bdf8;"></i> Cambiar contraseña
                    </button>
                    <button class="dropdown-item" onclick="closeDropdown(${vps.id}); openReassignModal(${vps.id})">
                        <i class="fas fa-user-edit" style="color:#fb923c;"></i> Reasignar
                    </button>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item danger" onclick="closeDropdown(${vps.id}); deleteVPS(${vps.id}, '${safeOwner}', '${safeName}')">
                        <i class="fas fa-trash"></i> Eliminar VPS
                    </button>
                </div>
            </div>
        </div>`;
}

/* ── Desktop table ─────────────────────────────────────────────────── */
function renderDesktop(list) {
    const tbody = document.getElementById('vpsTableBody');
    if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">Sin resultados.</td></tr>';
        return;
    }
    tbody.innerHTML = list.map(vps => `
        <tr>
            <td style="color:#64748b; font-size:.82rem;">#${vps.id}</td>
            <td>
                <div style="font-weight:600; font-size:.88rem;">${vps.name || '—'}</div>
                <div style="font-size:.75rem; color:#64748b; font-family:monospace;">${vps.ip_address || 'Sin IP'}</div>
            </td>
            <td style="font-size:.85rem;">${vps.owner_username || '<span style="color:#f87171;">Sin asignar</span>'}</td>
            <td>${timeLeftHtml(vps.expires_at)}</td>
            <td><span class="badge ${badgeClass(vps.status)}">${vps.status}</span></td>
            <td style="font-size:.8rem; color:#64748b;">${new Date(vps.created_at).toLocaleDateString()}</td>
            <td>${actionsHtml(vps)}</td>
        </tr>
    `).join('');
}

/* ── Mobile cards ──────────────────────────────────────────────────── */
function renderMobile(list) {
    const container = document.getElementById('vps-cards');
    if (!list.length) {
        container.innerHTML = '<div style="text-align:center; padding:24px; color:var(--text-muted);">Sin resultados.</div>';
        return;
    }
    container.innerHTML = list.map(vps => {
        const diff = vps.expires_at ? new Date(vps.expires_at) - new Date() : null;
        let timeColor = '#4ade80', timeLabel = '—';
        if (diff !== null) {
            const h = Math.floor(Math.abs(diff) / 3600000);
            const d = Math.floor(h / 24);
            timeLabel = diff < 0 ? `Expirado` : (d > 0 ? `${d}d ${h%24}h` : `${h}h`);
            timeColor = diff < 0 ? '#f87171' : h < 24 ? '#f87171' : h < 72 ? '#fb923c' : '#4ade80';
        }
        return `
            <div class="vps-card">
                <div class="vps-card-info">
                    <div class="vps-card-name">${vps.name || '—'}</div>
                    <div class="vps-card-ip">${vps.ip_address || 'Sin IP'}</div>
                    <div class="vps-card-meta">
                        <span class="badge ${badgeClass(vps.status)}">${vps.status}</span>
                        <span class="vps-card-time" style="color:${timeColor};">${timeLabel}</span>
                        ${vps.owner_username ? `<span class="vps-card-owner">· ${vps.owner_username}</span>` : ''}
                    </div>
                </div>
                <div class="vps-card-actions">${actionsHtml(vps)}</div>
            </div>`;
    }).join('');
}

/* ── Dropdown ──────────────────────────────────────────────────────── */
function toggleDropdown(id) {
    const wrap = document.getElementById(`ddwrap-${id}`);
    const menu = document.getElementById(`dd-${id}`);
    const isOpen = menu.classList.contains('open');
    if (openDropdown && openDropdown !== wrap)
        openDropdown.querySelector('.dropdown-menu').classList.remove('open');
    menu.classList.toggle('open', !isOpen);
    openDropdown = isOpen ? null : wrap;
}
function closeDropdown(id) {
    document.getElementById(`dd-${id}`)?.classList.remove('open');
    openDropdown = null;
}

/* ── Pagination ────────────────────────────────────────────────────── */
function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    if (!pagination || pagination.total_items <= pagination.limit) { container.innerHTML = ''; return; }
    const current = parseInt(pagination.current_page);
    const total   = parseInt(pagination.total_pages);
    let html = `<button class="page-btn" ${current===1?'disabled':''} onclick="loadVPS(${current-1})"><i class="fas fa-chevron-left"></i></button>`;
    for (let i = Math.max(1, current-2); i <= Math.min(total, current+2); i++)
        html += `<button class="page-btn ${i===current?'active':''}" onclick="loadVPS(${i})">${i}</button>`;
    html += `<button class="page-btn" ${current===total?'disabled':''} onclick="loadVPS(${current+1})"><i class="fas fa-chevron-right"></i></button>`;
    container.innerHTML = html;
}

/* ── Navigate ──────────────────────────────────────────────────────── */
function manageVPS(id) { window.location.href = `/dashboard/manage/?id=${id}`; }

/* ── Delete ────────────────────────────────────────────────────────── */
function deleteVPS(id, ownerUsername, vpsName) {
    document.getElementById('deleteVpsId').value = id;
    document.getElementById('deleteVpsOwnerValue').value = ownerUsername || '';
    document.getElementById('deleteVpsOwner').textContent = ownerUsername || 'Sin asignar';
    document.getElementById('deleteVpsName').textContent  = vpsName || `#${id}`;
    document.getElementById('deleteConfirmInput').value = '';
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true; btn.style.opacity = '.5';
    document.getElementById('deleteModal').classList.add('active');
    setTimeout(() => document.getElementById('deleteConfirmInput').focus(), 100);
}
function validateDeleteInput() {
    const val = document.getElementById('deleteConfirmInput').value.trim();
    const exp = document.getElementById('deleteVpsOwnerValue').value.trim();
    const btn = document.getElementById('confirmDeleteBtn');
    const ok  = val === exp && exp !== '';
    btn.disabled = !ok;
    btn.style.opacity  = ok ? '1' : '.5';
    btn.style.cursor   = ok ? 'pointer' : 'not-allowed';
    btn.style.background = ok ? 'rgba(255,77,77,.3)' : 'rgba(255,77,77,.15)';
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); }
async function confirmDelete() {
    const id = document.getElementById('deleteVpsId').value;
    try {
        const r = await fetch(`/api/admin/vps?id=${id}`, { method: 'DELETE' });
        const data = await r.json();
        if (data.message) { closeDeleteModal(); loadVPS(currentPage); }
        else alert(data.error || 'Error al eliminar');
    } catch(e) { alert('Error de conexión'); }
}

/* ── Rename ────────────────────────────────────────────────────────── */
function openRenameModal(id, currentName) {
    document.getElementById('renameVpsId').value = id;
    document.getElementById('renameInput').value = currentName;
    document.getElementById('renameModal').classList.add('active');
    setTimeout(() => document.getElementById('renameInput').focus(), 100);
}
function closeRenameModal() { document.getElementById('renameModal').classList.remove('active'); }
async function confirmRename() {
    const id   = document.getElementById('renameVpsId').value;
    const name = document.getElementById('renameInput').value.trim();
    if (!name) { alert('El nombre no puede estar vacío'); return; }
    const btn = document.getElementById('confirmRenameBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';
    try {
        const r = await fetch('/api/admin/vps', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'rename', vps_id: id, name })
        });
        const data = await r.json();
        if (data.message) { closeRenameModal(); loadVPS(currentPage); }
        else alert(data.error || 'Error al renombrar');
    } catch(e) { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar'; }
}

/* ── Change Password ───────────────────────────────────────────────── */
function openChangePasswordModal(id) {
    document.getElementById('changePasswordVpsId').value = id;
    document.getElementById('changePasswordInput').value = '';
    document.getElementById('changePasswordModal').classList.add('active');
    setTimeout(() => document.getElementById('changePasswordInput').focus(), 100);
}
function closeChangePasswordModal() { document.getElementById('changePasswordModal').classList.remove('active'); }
async function confirmChangePassword() {
    const id  = document.getElementById('changePasswordVpsId').value;
    const pwd = document.getElementById('changePasswordInput').value;
    if (!pwd) { alert('La contraseña no puede estar vacía'); return; }
    const btn = document.getElementById('confirmChangePasswordBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';
    try {
        const r = await fetch('/api/admin/vps', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'change_password', vps_id: id, password: pwd })
        });
        const data = await r.json();
        if (data.message) { closeChangePasswordModal(); }
        else alert(data.error || 'Error al cambiar la contraseña');
    } catch(e) { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar'; }
}

/* ── Reassign ──────────────────────────────────────────────────────── */
function openReassignModal(id) {
    document.getElementById('reassignVpsId').value   = id;
    document.getElementById('newUserId').value       = '';
    document.getElementById('userSearchInput').value = '';
    document.getElementById('userSearchResults').style.display = 'none';
    document.getElementById('reassignModal').classList.add('active');
    setTimeout(() => document.getElementById('userSearchInput').focus(), 100);
}
function closeReassignModal() { document.getElementById('reassignModal').classList.remove('active'); }
async function confirmReassign() {
    const vpsId  = document.getElementById('reassignVpsId').value;
    const userId = document.getElementById('newUserId').value;
    if (!userId) { alert('Por favor selecciona un usuario válido'); return; }
    try {
        const r = await fetch('/api/admin/vps', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'assign', vps_id: vpsId, user_id: userId })
        });
        const data = await r.json();
        if (data.message) { closeReassignModal(); loadVPS(currentPage); }
        else alert(data.error || 'Error al reasignar');
    } catch(e) { alert('Error de conexión'); }
}
</script>

<?php include '../footer.php'; ?>
