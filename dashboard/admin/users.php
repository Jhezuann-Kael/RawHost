<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle    = SITE_NAME . ' - Admin Usuarios';
$adminPageCss = 'users.css';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display:flex; align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Usuarios</h1>
            </div>
            <p>Gestión de clientes y referidos</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar..." onkeyup="debounceSearch()">
            </div>
        </div>
    </header>

    <!-- Telegram stat -->
    <div id="telegramStatWrap" style="margin-bottom:20px; display:none;">
        <div class="stat-card" style="display:inline-flex; align-items:center; gap:12px; padding:14px 22px; background:var(--admin-card); border:1px solid var(--admin-border); border-radius:10px;">
            <div style="width:38px; height:38px; border-radius:50%; background:rgba(0,136,204,0.15); display:flex; align-items:center; justify-content:center;">
                <i class="fab fa-telegram" style="color:#29b6f6; font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-size:0.75rem; color:var(--admin-muted); text-transform:uppercase; letter-spacing:.05em;">Registrados con Telegram</div>
                <div style="font-size:1.5rem; font-weight:700; color:var(--admin-text);" id="telegramStatCount">—</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('users')" id="tab-users">
            <i class="fas fa-users"></i> Usuarios
        </button>
        <button class="tab-btn" onclick="switchTab('referrals')" id="tab-referrals">
            <i class="fas fa-user-plus"></i> Referidos
        </button>
    </div>

    <!-- Telegram filter (users tab only) -->
    <div id="telegramFilterWrap" style="margin-bottom:18px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <span style="font-size:0.82rem; color:var(--admin-muted); font-weight:600;">Telegram:</span>
        <label class="tg-filter-label">
            <input type="radio" name="telegramFilter" value="all" checked onchange="loadUsers(1)"> Todos
        </label>
        <label class="tg-filter-label">
            <input type="radio" name="telegramFilter" value="with" onchange="loadUsers(1)">
            <i class="fab fa-telegram" style="color:#29b6f6;"></i> Con Telegram
        </label>
        <label class="tg-filter-label">
            <input type="radio" name="telegramFilter" value="without" onchange="loadUsers(1)">
            <i class="fas fa-ban" style="color:var(--admin-muted);"></i> Sin Telegram
        </label>
    </div>

    <!-- Users panel -->
    <div class="tab-panel active" id="panel-users">
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-usuario">Usuario</th>
                        <th class="col-email">Email</th>
                        <th class="col-saldo">Saldo</th>
                        <th class="col-rol">Rol</th>
                        <th class="col-tg"><i class="fab fa-telegram" style="color:#29b6f6;"></i> Telegram</th>
                        <th class="col-fecha">Registrado</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr><td colspan="8" style="text-align:center; padding:20px;">Cargando usuarios...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginationUsers" class="pagination"></div>
    </div>

    <!-- Referrals panel -->
    <div class="tab-panel" id="panel-referrals">
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario registrado</th>
                        <th>Email</th>
                        <th>Referido por</th>
                        <th>Código usado</th>
                        <th>Estado</th>
                        <th>Fecha registro</th>
                    </tr>
                </thead>
                <tbody id="referralsTableBody">
                    <tr><td colspan="7" style="text-align:center; padding:20px;">Cargando referidos...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginationReferrals" class="pagination"></div>
    </div>

    <!-- Change password modal -->
    <div id="passwordModal" class="modal-overlay">
        <div class="modal" style="width:420px;">
            <div class="modal-header">
                <h2 id="modalUserTitle"><i class="fas fa-key" style="margin-right:8px; color:var(--primary);"></i>Cambiar Contraseña</h2>
                <button class="btn-close" onclick="closePasswordModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="passUserId">
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" id="newPassword" class="form-control" placeholder="Mínimo 6 caracteres">
                </div>
                <div id="passError"   style="color:#ff4d4d; font-size:.85rem; display:none;"></div>
                <div id="passSuccess" style="color:var(--success); font-size:.85rem; display:none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closePasswordModal()">Cancelar</button>
                <button class="btn btn-primary" id="btnSavePass" onclick="submitPasswordChange()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</main>

<script>
    let activeTab       = 'users';
    let currentPageU    = 1;
    let currentPageR    = 1;
    let searchTimer;
    let openDropdown    = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadUsers(1);
        document.getElementById('passwordModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closePasswordModal();
        });
        document.addEventListener('click', e => {
            if (openDropdown && !openDropdown.contains(e.target)) {
                openDropdown.querySelector('.dropdown-menu').classList.remove('open');
                openDropdown = null;
            }
        });
    });

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

    function getTelegramFilter() {
        const checked = document.querySelector('input[name="telegramFilter"]:checked');
        return checked ? checked.value : 'all';
    }

    // ── Tabs ──────────────────────────────────────────────────────────────────
    function switchTab(tab) {
        activeTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');

        const ph = { users: 'Buscar por Nombre, Email...', referrals: 'Buscar usuario o referido...' };
        document.getElementById('searchInput').placeholder = ph[tab];

        const isUsers = tab === 'users';
        document.getElementById('telegramFilterWrap').style.display = isUsers ? '' : 'none';
        document.getElementById('telegramStatWrap').style.display   = isUsers ? '' : 'none';

        if (tab === 'users')     loadUsers(1);
        if (tab === 'referrals') loadReferrals(1);
    }

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            if (activeTab === 'users')    loadUsers(1);
            if (activeTab === 'referrals') loadReferrals(1);
        }, 500);
    }

    // ── Users ─────────────────────────────────────────────────────────────────
    async function loadUsers(page) {
        currentPageU = page;
        const tbody  = document.getElementById('usersTableBody');
        const search = document.getElementById('searchInput').value;

        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const params = new URLSearchParams({ page, limit: 10 });
            if (search) params.append('search', search);
            const tgFilter = getTelegramFilter();
            if (tgFilter !== 'all') params.append('telegram_filter', tgFilter);

            const res = await fetch(`../../api/admin/users/list?${params}`);
            const r   = await res.json();

            if (r.users) {
                renderUsersTable(r.users);
                renderPagination(r.pagination, loadUsers, 'paginationUsers');

                // Update telegram stat
                if (typeof r.total_telegram !== 'undefined') {
                    document.getElementById('telegramStatCount').textContent = r.total_telegram;
                    document.getElementById('telegramStatWrap').style.display = '';
                }
            } else {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:20px; color:red;">${r.error || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:red;">Error de conexión</td></tr>';
        }
    }

    function actionsHtmlUser(u) {
        const safeUsername = (u.username || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        return `
            <div class="user-actions">
                <button class="btn-view-user" onclick="viewUser(${u.id})" title="Ver detalle">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="dropdown-wrap" id="ddwrap-${u.id}">
                    <button class="btn-more" onclick="toggleDropdown(${u.id})">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu" id="dd-${u.id}">
                        <button class="dropdown-item" onclick="closeDropdown(${u.id}); impersonateUser(${u.id}, '${safeUsername}')">
                            <i class="fas fa-user-secret" style="color:#f59e0b;"></i> Inspeccionar sesión
                        </button>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item" onclick="closeDropdown(${u.id}); openPasswordModal(${u.id}, '${safeUsername}')">
                            <i class="fas fa-key" style="color:#38bdf8;"></i> Cambiar contraseña
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function renderUsersTable(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!users || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px;">No se encontraron usuarios.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(u => {
            const balanceLow = parseFloat(u.balance) < 5;
            const roleClass  = u.is_superuser == 1 ? 'role-admin' : 'role-user';
            const roleText   = u.is_superuser == 1 ? 'ADMIN' : 'USER';
            const tgLabel    = u.tg_username ? `@${u.tg_username}` : u.telegram_id;
            const tgBadge    = u.telegram_id
                ? `<span style="display:inline-flex;align-items:center;justify-content:center;gap:5px;color:#29b6f6;font-size:0.78rem;width:100%;"><i class="fab fa-telegram"></i><span style="font-family:monospace;color:var(--admin-muted);">${tgLabel}</span></span>`
                : `<span style="color:var(--admin-muted);">—</span>`;
            return `
                <tr>
                    <td class="col-id">${u.id}</td>
                    <td class="col-usuario"><span class="cell-text" style="font-weight:600;" title="${u.username || ''}">${u.username || 'N/A'}</span></td>
                    <td class="col-email"><span class="cell-text" title="${u.email || ''}">${u.email || 'N/A'}</span></td>
                    <td class="col-saldo ${balanceLow ? 'balance-zero' : 'balance-positive'}">$${parseFloat(u.balance).toFixed(2)}</td>
                    <td class="col-rol"><span class="badge ${roleClass}">${roleText}</span></td>
                    <td class="col-tg">${tgBadge}</td>
                    <td class="col-fecha">${new Date(u.created_at).toLocaleDateString()}</td>
                    <td class="col-actions">${actionsHtmlUser(u)}</td>
                </tr>`;
        }).join('');
    }

    // ── Referrals ─────────────────────────────────────────────────────────────
    async function loadReferrals(page) {
        currentPageR = page;
        const tbody  = document.getElementById('referralsTableBody');
        const search = document.getElementById('searchInput').value;

        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const params = new URLSearchParams({ page, limit: 10 });
            if (search) params.append('search', search);

            const res = await fetch(`../../api/admin/referrals_list?${params}`);
            const r   = await res.json();

            if (r.success && r.users) {
                renderReferralsTable(r.users);
                renderPagination(r.pagination, loadReferrals, 'paginationReferrals', true);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:red;">${r.error || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:red;">Error de conexión</td></tr>';
        }
    }

    function renderReferralsTable(users) {
        const tbody = document.getElementById('referralsTableBody');
        if (!users || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">No se encontraron referidos.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(u => `
            <tr>
                <td>#${u.id}</td>
                <td><span style="font-weight:600;">${u.username || 'N/A'}</span></td>
                <td>${u.email || 'N/A'}</td>
                <td><span class="badge role-user" style="background:rgba(0,102,255,0.1); color:#60a5fa;">
                    <i class="fas fa-user-tag"></i> ${u.referrer_username || ('ID: ' + u.referrer_id)}
                </span></td>
                <td style="font-family:monospace; color:var(--success);">${u.referral_code || 'N/A'}</td>
                <td>${u.completed_orders_count > 0
                    ? '<span class="badge" style="background:rgba(46,213,115,0.1); color:#2ecc71;">Válido ($)</span>'
                    : '<span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted);">Registrado</span>'
                }</td>
                <td>${new Date(u.created_at).toLocaleDateString()} ${new Date(u.created_at).toLocaleTimeString()}</td>
            </tr>`).join('');
    }

    // ── Pagination ────────────────────────────────────────────────────────────
    function renderPagination(pagination, loadFn, containerId, useTotal = false) {
        const container = document.getElementById(containerId);
        if (!pagination) { container.innerHTML = ''; return; }

        const total   = parseInt(pagination.total_pages);
        const current = parseInt(pagination.current_page);
        const limit   = parseInt(pagination.limit);
        const count   = parseInt(useTotal ? pagination.total_users : pagination.total_users);

        if (count <= limit) { container.innerHTML = ''; return; }

        let html = `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="${loadFn.name}(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="${loadFn.name}(${i})">${i}</button>`;
        }
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="${loadFn.name}(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;
        container.innerHTML = html;
    }

    // ── Actions ───────────────────────────────────────────────────────────────
    function viewUser(id) { window.location.href = `user_detail?id=${id}`; }

    async function impersonateUser(id, username) {
        if (!confirm(`¿Entrar como "${username}"?\n\nPodrás ver y actuar exactamente como este usuario. Aparecerá una barra amarilla para volver a tu sesión admin.`)) return;
        try {
            const res  = await fetch('../../api/admin/users/impersonate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: id })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Error al iniciar inspección');
            }
        } catch (e) {
            alert('Error de conexión');
        }
    }

    function openPasswordModal(id, username) {
        document.getElementById('passUserId').value = id;
        document.getElementById('modalUserTitle').innerHTML = `<i class="fas fa-key" style="margin-right:8px; color:var(--primary);"></i>Contraseña: ${username}`;
        document.getElementById('newPassword').value = '';
        document.getElementById('passError').style.display   = 'none';
        document.getElementById('passSuccess').style.display = 'none';
        document.getElementById('passwordModal').classList.add('active');
        setTimeout(() => document.getElementById('newPassword').focus(), 100);
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').classList.remove('active');
    }

    async function submitPasswordChange() {
        const userId      = document.getElementById('passUserId').value;
        const newPassword = document.getElementById('newPassword').value;
        const errorDiv    = document.getElementById('passError');
        const successDiv  = document.getElementById('passSuccess');
        const btn         = document.getElementById('btnSavePass');

        if (!newPassword || newPassword.length < 6) {
            errorDiv.textContent    = 'La contraseña debe tener al menos 6 caracteres.';
            errorDiv.style.display  = 'block';
            return;
        }

        errorDiv.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        try {
            const res  = await fetch('../../api/admin/users/modify_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, new_password: newPassword })
            });
            const data = await res.json();

            if (data.status === 'success') {
                successDiv.textContent    = data.message;
                successDiv.style.display  = 'block';
                setTimeout(() => closePasswordModal(), 1500);
            } else {
                errorDiv.textContent   = data.message || 'Error al actualizar contraseña';
                errorDiv.style.display = 'block';
            }
        } catch (e) {
            errorDiv.textContent   = 'Error de conexión con el servidor.';
            errorDiv.style.display = 'block';
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Guardar Cambios';
        }
    }
</script>

<?php include '../footer.php'; ?>
