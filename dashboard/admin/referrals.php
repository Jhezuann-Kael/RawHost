<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Referidos';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Gestión de Referidos</h1>
            </div>
            <p>Usuarios registrados mediante código de referido</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar usuario o referido..."
                    onkeyup="debounceSearch()">
            </div>
        </div>
    </header>

    <!-- Users Table -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario Registrado</th>
                    <th>Email</th>
                    <th>Referido Por</th>
                    <th>Código Usado</th>
                    <th>Estado</th>
                    <th>Fecha Registro</th>
                </tr>
            </thead>
            <tbody id="referralsTableBody">
                <tr>
                    <td colspan="7" style="text-align:center; padding: 20px;">Cargando referidos...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="pagination"></div>

</main>

<script>
    let currentPage = 1;
    let searchTimer;

    document.addEventListener('DOMContentLoaded', () => {
        loadReferrals(1);
    });

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadReferrals(1);
        }, 500);
    }

    async function loadReferrals(page) {
        currentPage = page;
        const tbody = document.getElementById('referralsTableBody');
        const search = document.getElementById('searchInput').value;

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const params = new URLSearchParams({
                page: page,
                limit: 10
            });
            if (search) params.append('search', search);

            const res = await fetch(`../../api/admin/referrals_list?${params.toString()}`);
            const response = await res.json();

            if (response.success && response.users) {
                renderTable(response.users);
                renderPagination(response.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px; color:red;">${response.error || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px; color:red;">Error de conexión</td></tr>';
        }
    }

    function renderTable(users) {
        const tbody = document.getElementById('referralsTableBody');
        if (!users || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px;">No se encontraron referidos.</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => {
            return `
                <tr>
                    <td>#${user.id}</td>
                    <td>
                        <div style="font-weight:600;">${user.username || 'N/A'}</div>
                    </td>
                    <td>${user.email || 'N/A'}</td>
                    <td>
                        <span class="badge role-user" style="background: rgba(99, 102, 241, 0.1); color: #a5b4fc;">
                            <i class="fas fa-user-tag"></i> ${user.referrer_username || ('ID: ' + user.referrer_id)}
                        </span>
                    </td>
                    <td style="font-family: monospace; color: var(--success);">${user.referral_code || 'N/A'}</td>
                    <td>
                        ${user.completed_orders_count > 0
                    ? '<span class="badge" style="background: rgba(46, 213, 115, 0.1); color: #2ecc71;">Válido ($)</span>'
                    : '<span class="badge" style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted);">Registrado</span>'
                }
                    </td>
                    <td>${new Date(user.created_at).toLocaleDateString()} ${new Date(user.created_at).toLocaleTimeString()}</td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total_users <= pagination.limit) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        const current = parseInt(pagination.current_page);
        const total = parseInt(pagination.total_pages);

        // Prev
        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="loadReferrals(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;

        // Pages
        let start = Math.max(1, current - 2);
        let end = Math.min(total, current + 2);

        for (let i = start; i <= end; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadReferrals(${i})">${i}</button>`;
        }

        // Next
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="loadReferrals(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;

        container.innerHTML = html;
    }
</script>

<?php include '../footer.php'; ?>