<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Dominios';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Gestión de Dominios</h1>
            </div>
            <p>Monitoreo y administración de dominios registrados</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nombre, propietario..."
                    onkeyup="debounceSearch()">
            </div>
        </div>
    </header>

    <!-- Domains Table -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dominio</th>
                    <th>Propietario</th>
                    <th>TLD</th>
                    <th>Estado</th>
                    <th>Expira</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="domainsTableBody">
                <tr>
                    <td colspan="8" style="text-align:center; padding: 20px;">Cargando dominios...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="pagination"></div>

    <!-- Reassign Modal -->
    <div id="reassignModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:25px; border-radius:8px; width:400px; max-width:90%;">
            <h3 style="margin-top:0;">Reasignar Dominio</h3>
            <p style="color:#7f8c8d; font-size:0.9em; margin-bottom:20px;">Busca y selecciona el nuevo usuario
                propietario.</p>

            <input type="hidden" id="reassignDomainId">

            <div style="position:relative; margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:600;">Buscar Usuario:</label>
                <input type="text" id="userSearchInput" class="search-box" autocomplete="off"
                    style="width:100%; box-sizing:border-box; padding:10px; background:white; border:1px solid #ddd; border-radius:4px;"
                    placeholder="Username o Email..." onkeyup="debounceUserSearch()">
                <div id="userSearchResults"
                    style="position:absolute; top:100%; left:0; width:100%; background:white; border:1px solid #ddd; border-top:none; max-height:200px; overflow-y:auto; display:none; z-index:1001; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px; font-weight:600;">ID Seleccionado:</label>
                <input type="number" id="newUserId" class="search-box" readonly
                    style="width:100%; box-sizing:border-box; padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px;"
                    placeholder="ID">
            </div>

            <div style="text-align:right;">
                <button onclick="closeReassignModal()"
                    style="padding:8px 15px; border:none; background:#e74c3c; color:white; border-radius:4px; cursor:pointer; margin-right:10px;">Cancelar</button>
                <button onclick="confirmReassign()"
                    style="padding:8px 15px; border:none; background:#2ecc71; color:white; border-radius:4px; cursor:pointer;">Guardar</button>
            </div>
        </div>
    </div>

</main>

<style>
/* ── Tabla compacta ─────────────────────────────────── */
.admin-table th,
.admin-table td { padding: 8px 10px; font-size: 0.8rem; }
.admin-table th  { font-size: 0.75rem; }
.col-dom-id   { width: 36px; color: var(--admin-muted); font-size: 0.75rem; white-space: nowrap; }
.col-dom-name .d-name { font-weight: 600; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px; }

/* ── Dropdown ⋮ ─────────────────────────────────────── */
.dom-actions  { display: flex; align-items: center; gap: 6px; }
.dropdown-wrap { position: relative; }
.btn-more {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 7px; font-size: 1rem;
    border: 1px solid rgba(255,255,255,.1); cursor: pointer;
    background: rgba(255,255,255,.06); color: var(--admin-text);
    transition: background .15s;
}
.btn-more:hover { background: rgba(255,255,255,.12); }
.dropdown-menu {
    display: none; position: absolute; right: 0; top: calc(100% + 4px);
    min-width: 170px; background: #1e2535;
    border: 1px solid rgba(255,255,255,.1); border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.4); z-index: 999; overflow: hidden;
}
.dropdown-menu.open { display: block; }
.dropdown-item {
    display: flex; align-items: center; gap: 9px; padding: 9px 14px;
    font-size: 0.82rem; color: var(--admin-text); cursor: pointer;
    border: none; background: none; width: 100%; text-align: left; transition: background .12s;
}
.dropdown-item:hover { background: rgba(255,255,255,.06); }
.dropdown-item i { width: 14px; text-align: center; }
.dropdown-item.danger { color: #f87171; }
.dropdown-item.danger:hover { background: rgba(248,113,113,.08); }
.dropdown-divider { height: 1px; background: rgba(255,255,255,.07); margin: 3px 0; }
</style>

<script>
    let currentPage = 1;
    let searchTimer;
    let userSearchTimer;
    let openDropdown = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadDomains(1);

        document.addEventListener('click', function (e) {
            // Close dropdown
            if (openDropdown && !openDropdown.contains(e.target)) {
                openDropdown.querySelector('.dropdown-menu').classList.remove('open');
                openDropdown = null;
            }
            // Close user search results
            const container = document.getElementById('userSearchResults');
            const input = document.getElementById('userSearchInput');
            if (container && container.style.display === 'block' && e.target !== input && !container.contains(e.target)) {
                container.style.display = 'none';
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

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadDomains(1);
        }, 500);
    }

    function debounceUserSearch() {
        clearTimeout(userSearchTimer);
        userSearchTimer = setTimeout(searchUsers, 300);
    }

    async function searchUsers() {
        const query = document.getElementById('userSearchInput').value;
        const resultsContainer = document.getElementById('userSearchResults');

        if (query.length < 1) {
            resultsContainer.style.display = 'none';
            return;
        }

        try {
            const res = await fetch(`../../api/admin/users/list?search=${encodeURIComponent(query)}&limit=5`);
            const data = await res.json();

            if (data.users && data.users.length > 0) {
                resultsContainer.innerHTML = data.users.map(u => `
                    <div onclick="selectUser(${u.id}, '${u.username}')" 
                        style="padding:10px; cursor:pointer; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:bold;">${u.username}</div>
                            <div style="font-size:0.8em; color:#7f8c8d;">${u.email}</div>
                        </div>
                        <span style="font-size:0.8em; background:#eee; padding:2px 6px; border-radius:4px;">ID: ${u.id}</span>
                    </div>
                `).join('');

                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = '<div style="padding:10px; color:#888;">No se encontraron usuarios</div>';
                resultsContainer.style.display = 'block';
            }
        } catch (e) {
            console.error(e);
        }
    }

    function selectUser(id, username) {
        document.getElementById('newUserId').value = id;
        document.getElementById('userSearchInput').value = username;
        document.getElementById('userSearchResults').style.display = 'none';
    }

    async function loadDomains(page) {
        currentPage = page;
        const tbody = document.getElementById('domainsTableBody');
        const search = document.getElementById('searchInput').value;

        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const params = new URLSearchParams({
                page: page,
                limit: 10
            });
            if (search) params.append('search', search);

            const res = await fetch(`../../api/admin/domains?${params.toString()}`);
            const response = await res.json();

            if (response.domains) {
                renderTable(response.domains);
                renderPagination(response.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 20px; color:red;">${response.error || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px; color:red;">Error de conexión</td></tr>';
        }
    }

    function renderTable(domainsList) {
        const tbody = document.getElementById('domainsTableBody');
        if (!domainsList || domainsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;">No se encontraron dominios.</td></tr>';
            return;
        }

        tbody.innerHTML = domainsList.map(domain => {
            let statusBadge = 'badge-inactive';
            if (domain.status === 'ACTIVE') statusBadge = 'badge-running';
            else if (domain.status === 'PENDING') statusBadge = 'badge-provisioning';
            else if (domain.status === 'EXPIRED' || domain.is_expired) statusBadge = 'badge-stopped';

            const expiryDate = domain.expiration_date ? new Date(domain.expiration_date).toLocaleDateString() : 'N/A';
            const createdDate = domain.created_at ? new Date(domain.created_at).toLocaleDateString() : 'N/A';

            let expiryInfo = expiryDate;
            if (domain.days_until_expiry !== null) {
                if (domain.is_expired) {
                    expiryInfo += ` <span style="color:#e74c3c; font-size:0.8em;">(Expirado)</span>`;
                } else if (domain.days_until_expiry < 30) {
                    expiryInfo += ` <span style="color:#f39c12; font-size:0.8em;">(${domain.days_until_expiry}d)</span>`;
                }
            }

            const safeName = (domain.domain_name || '').replace(/'/g, "\\'");
            return `
                <tr>
                    <td class="col-dom-id">${domain.id}</td>
                    <td class="col-dom-name">
                        <span class="d-name" title="${domain.domain_name}">${domain.domain_name}</span>
                        ${domain.nameserver_count > 0 ? `<span style="font-size:0.72rem; color:#7f8c8d;"><i class="fas fa-server"></i> ${domain.nameserver_count} NS</span>` : ''}
                    </td>
                    <td style="white-space:nowrap;">
                        ${domain.owner_username
                            ? `<span style="font-weight:500;">${domain.owner_username}</span>`
                            : '<span style="color:#e74c3c;">Sin asignar</span>'}
                    </td>
                    <td><span style="font-family:monospace; font-size:0.8rem;">.${domain.tld.toUpperCase()}</span></td>
                    <td><span class="badge ${statusBadge}">${domain.status}</span></td>
                    <td style="white-space:nowrap;">${expiryInfo}</td>
                    <td style="white-space:nowrap;">${createdDate}</td>
                    <td>
                        <div class="dom-actions">
                            <button class="action-btn" style="background:var(--primary);color:#fff;width:30px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;"
                                onclick="viewDomain(${domain.id})" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="dropdown-wrap" id="ddwrap-${domain.id}">
                                <button class="btn-more" onclick="toggleDropdown(${domain.id})">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu" id="dd-${domain.id}">
                                    <button class="dropdown-item" onclick="closeDropdown(${domain.id}); openReassignModal(${domain.id})">
                                        <i class="fas fa-user-edit" style="color:#fb923c;"></i> Reasignar
                                    </button>
                                    <div class="dropdown-divider"></div>
                                    <button class="dropdown-item danger" onclick="closeDropdown(${domain.id}); deleteDomain(${domain.id})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total_items <= pagination.limit) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        const current = parseInt(pagination.current_page);
        const total = parseInt(pagination.total_pages);

        // Prev
        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="loadDomains(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;

        // Pages
        let start = Math.max(1, current - 2);
        let end = Math.min(total, current + 2);

        for (let i = start; i <= end; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadDomains(${i})">${i}</button>`;
        }

        // Next
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="loadDomains(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;

        container.innerHTML = html;
    }

    function viewDomain(id) {
        window.location.href = `../../dashboard/domain_detail?id=${id}`;
    }

    async function deleteDomain(id) {
        if (!confirm('¿Estás seguro de que deseas eliminar este dominio? Esta acción no se puede deshacer.')) return;

        try {
            const res = await fetch(`../../api/admin/domains?id=${id}`, { method: 'DELETE' });
            const data = await res.json();

            if (data.message) {
                loadDomains(currentPage);
            } else {
                alert(data.error || 'Error al eliminar');
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }

    // Reassign Logic
    function openReassignModal(id) {
        document.getElementById('reassignDomainId').value = id;
        document.getElementById('newUserId').value = '';
        document.getElementById('userSearchInput').value = '';
        document.getElementById('userSearchResults').style.display = 'none';
        document.getElementById('reassignModal').style.display = 'flex';
    }

    function closeReassignModal() {
        document.getElementById('reassignModal').style.display = 'none';
    }

    async function confirmReassign() {
        const domainId = document.getElementById('reassignDomainId').value;
        const userId = document.getElementById('newUserId').value;

        if (!userId) {
            alert('Por favor selecciona un usuario válido');
            return;
        }

        try {
            const res = await fetch('../../api/admin/domains', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'assign',
                    domain_id: domainId,
                    user_id: userId
                })
            });
            const data = await res.json();

            if (data.message) {
                alert('Dominio reasignado correctamente');
                closeReassignModal();
                loadDomains(currentPage);
            } else {
                alert(data.error || 'Error al reasignar');
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión al reasignar');
        }
    }

</script>

<?php include '../footer.php'; ?>