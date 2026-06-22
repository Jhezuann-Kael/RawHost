<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Órdenes';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display:flex; align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
                <h1>Gestión de Órdenes</h1>
            </div>
            <p>Historial de órdenes de VPS, dominios y servicios</p>
        </div>
        <div class="table-controls">
            <button class="btn-import-vps" onclick="openImportModal()">
                <i class="fas fa-file-import"></i> Importar VPS
            </button>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por ID, usuario, VPS..." onkeyup="debounceSearch()">
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Estado</span>
                <div class="filter-btns" id="statusFilterBtns">
                    <button class="filter-btn active" data-value="">Todo</button>
                    <button class="filter-btn" data-value="COMPLETED">Completado</button>
                    <button class="filter-btn" data-value="PENDING">Pendiente</button>
                    <button class="filter-btn" data-value="CANCELLED">Cancelado</button>
                </div>
            </div>
            <div class="filter-group">
                <span class="filter-label">Tipo</span>
                <div class="filter-btns" id="typeFilterBtns">
                    <button class="filter-btn active" data-value="">Todo</button>
                    <button class="filter-btn" data-value="vps">VPS</button>
                    <button class="filter-btn" data-value="renewal">Renovación</button>
                    <button class="filter-btn" data-value="upgrade">Upgrade</button>
                    <button class="filter-btn" data-value="domain">Dominio</button>
                    <button class="filter-btn" data-value="managed_service">Managed</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Stats -->
    <div class="billing-section" id="stats-section">
        <div class="billing-grid">
            <div class="billing-item highlight">
                <h4>Revenue Total</h4>
                <div class="amount" id="stat-revenue">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Total Órdenes</h4>
                <div class="amount" id="stat-total">0</div>
            </div>
            <div class="billing-item">
                <h4>Completadas</h4>
                <div class="amount" style="color:#4ade80;" id="stat-completed">0</div>
            </div>
            <div class="billing-item">
                <h4>Pendientes</h4>
                <div class="amount" style="color:#fbbf24;" id="stat-pending">0</div>
            </div>
            <div class="billing-item">
                <h4>Canceladas</h4>
                <div class="amount" style="color:#f87171;" id="stat-cancelled">0</div>
            </div>
        </div>
    </div>

    <!-- Orders Table (desktop) -->
    <div class="admin-table-container" style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>VPS / Dominio</th>
                    <th>Tipo</th>
                    <th>Monto</th>
                    <th>Duración</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <tr><td colspan="9" style="text-align:center; padding:20px;">Cargando órdenes...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Orders Cards (móvil) -->
    <div id="orders-cards" style="display:none;"></div>

    <div id="pagination" class="pagination"></div>

    <!-- Import VPS Modal -->
    <div id="importVpsModal" class="modal-overlay" onclick="if(event.target===this)closeImportModal()">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header">
                <h2 style="color:var(--primary);"><i class="fas fa-file-import" style="margin-right:8px;"></i>Importar VPS manual</h2>
                <button class="btn-close" onclick="closeImportModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label class="form-label">ID de Orden *</label>
                        <input type="number" id="importOrderId" class="form-control" placeholder="Ej: 109" min="1"
                            oninput="clearImportResult()">
                    </div>
                    <div>
                        <label class="form-label">IP asignada *</label>
                        <input type="text" id="importIp" class="form-control" placeholder="Ej: 102.220.160.168"
                            oninput="clearImportResult()">
                    </div>
                    <div>
                        <label class="form-label">Hostname <small style="color:var(--admin-muted); font-weight:400;">(opcional — usa el de la orden si se deja vacío)</small></label>
                        <input type="text" id="importServerName" class="form-control" placeholder="Ej: mi-servidor">
                    </div>
                    <div id="importResult"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-reexecute" id="btnImportVps" onclick="submitImportVps()">
                    <i class="fas fa-file-import"></i> Importar
                </button>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div id="orderModal" class="modal-overlay" onclick="if(event.target===this)closeOrderModal()">
        <div class="modal" style="max-width:560px;">
            <div class="modal-header">
                <h2 id="orderModalTitle" style="color:var(--primary);"><i class="fas fa-receipt" style="margin-right:8px;"></i>Detalle de Orden</h2>
                <button class="btn-close" onclick="closeOrderModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="orderModalBody"></div>
            <div class="modal-footer" id="orderModalFooter"></div>
        </div>
    </div>

</main>

<style>
.filter-bar {
    display:flex; flex-direction:column; gap:10px; margin-bottom:20px;
    background:var(--admin-card); border:1px solid var(--admin-border);
    border-radius:8px; padding:14px 18px;
}
.filter-group { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.filter-label {
    font-size:0.75em; font-weight:700; text-transform:uppercase;
    letter-spacing:.07em; color:var(--admin-muted); min-width:46px; flex-shrink:0;
}
.filter-btns { display:flex; gap:6px; flex-wrap:wrap; }
.filter-btn {
    background:var(--admin-surface); border:1px solid var(--admin-border);
    color:var(--admin-muted); padding:4px 12px; border-radius:20px;
    font-size:0.8em; font-weight:500; cursor:pointer; transition:all .15s; white-space:nowrap;
}
.filter-btn:hover { border-color:var(--primary); color:var(--admin-text); }
.filter-btn.active { background:var(--primary); border-color:var(--primary); color:#fff; font-weight:600; }

@media (max-width: 680px) {
    .admin-table-container { display: none !important; }
    #orders-cards { display: block !important; margin-bottom: 20px; }
}

.order-detail-grid {
    display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px;
}
.order-detail-item { background:rgba(255,255,255,.04); border-radius:6px; padding:10px 12px; }
.order-detail-label { font-size:0.72em; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:3px; }
.order-detail-value { font-size:0.92em; font-weight:600; color:var(--text-light); word-break:break-all; }

.btn-reexecute {
    background:rgba(16,185,129,.15); color:#34d399;
    border:1px solid rgba(16,185,129,.3); border-radius:6px;
    padding:8px 16px; font-size:0.88em; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:6px;
    transition:all .2s; white-space:nowrap;
}
.btn-reexecute:hover { background:rgba(16,185,129,.25); }
.btn-reexecute:disabled { opacity:.5; cursor:not-allowed; }

.btn-status-complete {
    background:rgba(59,130,246,.15); color:#60a5fa;
    border:1px solid rgba(59,130,246,.3); border-radius:6px;
    padding:8px 16px; font-size:0.88em; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:6px;
    transition:all .2s;
}
.btn-status-complete:hover { background:rgba(59,130,246,.25); }
.btn-status-complete:disabled { opacity:.5; cursor:not-allowed; }

.btn-cancel-order {
    background:rgba(239,68,68,.12); color:#f87171;
    border:1px solid rgba(239,68,68,.25); border-radius:6px;
    padding:8px 16px; font-size:0.88em; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:6px;
    transition:all .2s;
}
.btn-cancel-order:hover { background:rgba(239,68,68,.22); }
.btn-cancel-order:disabled { opacity:.5; cursor:not-allowed; }

.action-result {
    margin-top:10px; padding:10px 14px; border-radius:6px;
    font-size:0.88em; display:flex; align-items:flex-start; gap:8px;
}
.action-result.ok  { background:rgba(39,174,96,.12); color:#2ecc71; border:1px solid rgba(39,174,96,.25); }
.action-result.err { background:rgba(231,76,60,.12); color:#e74c3c; border:1px solid rgba(231,76,60,.25); }

.btn-import-vps {
    background:rgba(139,92,246,.15); color:#a78bfa;
    border:1px solid rgba(139,92,246,.3); border-radius:6px;
    padding:8px 16px; font-size:0.88em; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:6px;
    transition:all .2s; white-space:nowrap;
}
.btn-import-vps:hover { background:rgba(139,92,246,.25); }

.form-label {
    display:block; font-size:0.75em; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em;
    color:var(--admin-muted); margin-bottom:5px;
}

/* ── Tabla compacta ─────────────────────────────────── */
.admin-table th,
.admin-table td { padding: 8px 10px; font-size: 0.8rem; }
.admin-table th  { font-size: 0.75rem; }
.col-ord-id  { width: 36px; color: var(--admin-muted); font-size: 0.75rem; white-space: nowrap; }
.col-ord-user { max-width: 120px; }
.col-ord-user .u-name { font-weight: 600; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 110px; }
.col-ord-target { max-width: 130px; }
.col-ord-target .t-name { font-weight: 600; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 120px; }
.col-ord-date .t-date { display: block; }
.col-ord-date .t-time { font-size: 0.72rem; color: #7f8c8d; }
</style>

<script>
    let currentPage = 1;
    let searchTimer;
    let currentOrderData = null;

    document.addEventListener('DOMContentLoaded', () => {
        initFilterBtns();
        loadOrders(1);
    });

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadOrders(1), 500);
    }

    function getActiveFilter(groupId) {
        const active = document.querySelector(`#${groupId} .filter-btn.active`);
        return active ? active.dataset.value : '';
    }

    function initFilterBtns() {
        document.querySelectorAll('.filter-btns').forEach(group => {
            group.addEventListener('click', e => {
                const btn = e.target.closest('.filter-btn');
                if (!btn) return;
                group.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                loadOrders(1);
            });
        });
    }

    async function loadOrders(page) {
        currentPage = page;
        const tbody  = document.getElementById('ordersTableBody');
        const search = document.getElementById('searchInput').value;
        const status = getActiveFilter('statusFilterBtns');
        const type   = getActiveFilter('typeFilterBtns');

        const loading = '<i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>';
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:20px;">${loading} Cargando...</td></tr>`;
        document.getElementById('orders-cards').innerHTML = `<div style="text-align:center;padding:20px;color:var(--admin-muted);">${loading}</div>`;

        try {
            const params = new URLSearchParams({ page, limit: 15 });
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (type)   params.append('type', type);

            const res  = await fetch(`/api/admin/orders/list?${params}`);
            const data = await res.json();

            if (data.error) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:20px;color:red;">${data.error}</td></tr>`;
                return;
            }

            renderTable(data.orders);
            renderPagination(data.pagination);
            renderStats(data.stats);
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;color:red;">Error de conexión</td></tr>';
        }
    }

    function renderStats(stats) {
        if (!stats) return;
        document.getElementById('stat-revenue').textContent   = '$' + parseFloat(stats.revenue   || 0).toFixed(2);
        document.getElementById('stat-total').textContent     = parseInt(stats.total     || 0).toLocaleString();
        document.getElementById('stat-completed').textContent = parseInt(stats.completed || 0).toLocaleString();
        document.getElementById('stat-pending').textContent   = parseInt(stats.pending   || 0).toLocaleString();
        document.getElementById('stat-cancelled').textContent = parseInt(stats.cancelled || 0).toLocaleString();
    }

    const typeLabels = {
        vps: 'VPS', renewal: 'Renovación', upgrade: 'Upgrade',
        domain: 'Dominio', managed_service: 'Managed'
    };

    function badgeClass(status) {
        if (status === 'COMPLETED') return 'badge-completed';
        if (status === 'PENDING')   return 'badge-pending';
        if (status === 'CANCELLED') return 'badge-failed';
        return 'badge-closed';
    }

    function renderTable(orders) {
        renderDesktop(orders);
        renderMobile(orders);
    }

    function renderDesktop(orders) {
        const tbody = document.getElementById('ordersTableBody');
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No se encontraron órdenes.</td></tr>';
            return;
        }

        tbody.innerHTML = orders.map(o => {
            const target = o.vps_name
                ? `<span class="t-name" title="${escHtml(o.vps_name)}">${escHtml(o.vps_name)}</span><span style="font-size:0.72rem;color:#7f8c8d;">${escHtml(o.vps_ip || '')}</span>`
                : (o.domain_name ? `<span class="t-name" style="color:var(--primary);" title="${escHtml(o.domain_name)}">${escHtml(o.domain_name)}</span>` : '<span style="color:#7f8c8d;">—</span>');
            const duration   = o.duration ? `${o.duration}h` : '—';
            const typeLabel  = typeLabels[o.type] || o.type || '—';
            const badge      = badgeClass(o.status);
            const d = new Date(o.created_at);
            const dateStr = d.toLocaleDateString();
            const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            return `<tr>
                <td class="col-ord-id">${o.id}</td>
                <td class="col-ord-user">
                    <span class="u-name" title="${escHtml(o.username || o.email || '')}">${escHtml(o.username || o.email || 'N/A')}</span>
                    <span style="font-size:0.72rem;color:#7f8c8d;">ID ${o.user_id}</span>
                </td>
                <td class="col-ord-target">${target}</td>
                <td style="white-space:nowrap;">${typeLabel}</td>
                <td class="balance-positive">$${parseFloat(o.total_amount).toFixed(2)}</td>
                <td style="white-space:nowrap;">${duration}</td>
                <td><span class="badge ${badge}">${o.status}</span></td>
                <td class="col-ord-date">
                    <span class="t-date">${dateStr}</span>
                    <span class="t-time">${timeStr}</span>
                </td>
                <td>
                    <button class="action-btn" style="background:var(--primary);color:#fff;"
                        onclick='openOrderModal(${JSON.stringify(o)})' title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    function renderMobile(orders) {
        const container = document.getElementById('orders-cards');
        if (!orders || orders.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--admin-muted);">No se encontraron órdenes.</div>';
            return;
        }

        container.innerHTML = orders.map(o => {
            const badge     = badgeClass(o.status);
            const typeLabel = typeLabels[o.type] || o.type || '—';
            const target    = o.vps_name || o.domain_name || '—';
            const duration  = o.duration ? ` · ${o.duration}h` : '';

            return `
                <div class="admin-card">
                    <div class="admin-card-info">
                        <div class="admin-card-name">#${o.id} · ${escHtml(o.username || o.email || 'N/A')}</div>
                        <div class="admin-card-sub">${escHtml(target)}</div>
                        <div class="admin-card-meta">
                            <span class="badge ${badge}">${o.status}</span>
                            <span style="color:#4ade80;font-weight:600;font-size:0.85em;">$${parseFloat(o.total_amount).toFixed(2)}</span>
                            <span style="font-size:0.8em;color:var(--admin-muted);">${typeLabel}${duration}</span>
                        </div>
                        <div class="admin-card-date">${new Date(o.created_at).toLocaleString()}</div>
                    </div>
                    <div class="admin-card-actions">
                        <button class="action-btn" style="background:var(--primary);color:#fff;"
                            onclick='openOrderModal(${JSON.stringify(o)})' title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>`;
        }).join('');
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total <= pagination.limit) {
            container.innerHTML = ''; return;
        }
        const current = pagination.current_page;
        const total   = pagination.pages;
        let html = `<button class="page-btn" ${current===1?'disabled':''} onclick="loadOrders(${current-1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = Math.max(1,current-2); i <= Math.min(total,current+2); i++) {
            html += `<button class="page-btn ${i===current?'active':''}" onclick="loadOrders(${i})">${i}</button>`;
        }
        html += `<button class="page-btn" ${current===total?'disabled':''} onclick="loadOrders(${current+1})"><i class="fas fa-chevron-right"></i></button>`;
        container.innerHTML = html;
    }

    function openOrderModal(order) {
        currentOrderData = order;

        const typeLabels = {
            vps: 'VPS', renewal: 'Renovación', upgrade: 'Upgrade',
            domain: 'Dominio', managed_service: 'Managed'
        };

        document.getElementById('orderModalTitle').innerHTML =
            `<i class="fas fa-receipt" style="margin-right:8px;"></i>Orden #${order.id}`;

        document.getElementById('orderModalBody').innerHTML = `
            <div class="order-detail-grid">
                <div class="order-detail-item">
                    <div class="order-detail-label">Usuario</div>
                    <div class="order-detail-value">${escHtml(order.username || order.email || 'N/A')} <small style="color:#7f8c8d;">(ID ${order.user_id})</small></div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">Tipo</div>
                    <div class="order-detail-value">${typeLabels[order.type] || order.type || '—'}</div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">VPS / Destino</div>
                    <div class="order-detail-value">${escHtml(order.vps_name || order.domain_name || '—')}</div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">IP</div>
                    <div class="order-detail-value">${escHtml(order.vps_ip || '—')}</div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">Plan</div>
                    <div class="order-detail-value">${escHtml(order.plan_name || '—')}</div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">Duración</div>
                    <div class="order-detail-value">${order.duration ? order.duration + ' horas' : '—'}</div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">Monto</div>
                    <div class="order-detail-value" style="color:#4ade80;">$${parseFloat(order.total_amount).toFixed(2)}</div>
                </div>
                <div class="order-detail-item">
                    <div class="order-detail-label">Estado</div>
                    <div class="order-detail-value">${order.status}</div>
                </div>
                <div class="order-detail-item" style="grid-column:span 2;">
                    <div class="order-detail-label">Fecha</div>
                    <div class="order-detail-value">${new Date(order.created_at).toLocaleString()}</div>
                </div>
                ${order.description ? `<div class="order-detail-item" style="grid-column:span 2;">
                    <div class="order-detail-label">Descripción</div>
                    <div class="order-detail-value">${escHtml(order.description)}</div>
                </div>` : ''}
            </div>
            <div id="modalActionResult"></div>
        `;

        const isVpsOrder  = !!(order.vps_id || order.plan_id);
        const isPending   = order.status === 'PENDING';
        const isCompleted = order.status === 'COMPLETED';
        const needsVpsForm = !order.vps_id; // No VPS created yet → need name + password

        // Build re-execute form (only for new purchases without vps_id)
        const reexecForm = needsVpsForm ? `
            <div style="margin-top:12px; display:flex; flex-direction:column; gap:8px;">
                <div style="font-size:0.75em; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Datos para provisionar</div>
                <input type="text" id="reexecServerName" class="form-control" placeholder="Hostname (ej: server-mi-vps)"
                    value="${order.username ? 'vps-' + escHtml(order.username.toLowerCase().replace(/[^a-z0-9]/g,'').substring(0,8)) : 'vps-server'}">
                <input type="text" id="reexecPassword" class="form-control" placeholder="Contraseña"
                    value="Rawh0st#${Math.random().toString(36).substring(2,10)}">
                <small style="color:var(--text-muted); font-size:0.78em;"><i class="fas fa-info-circle"></i> Se auto-generan si se dejan vacíos.</small>
            </div>` : '';

        document.getElementById('orderModalFooter').innerHTML = `
            <div style="display:flex; flex-direction:column; gap:10px; width:100%;">
                ${isVpsOrder && isPending ? `
                <div>
                    ${reexecForm}
                    <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn-reexecute" id="btnReexecute" onclick="reexecuteOrder(${order.id}, false)">
                            <i class="fas fa-redo"></i> Re-ejecutar (provisionVps)
                        </button>
                    </div>
                </div>` : ''}

                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    ${!isCompleted ? `
                    <button class="btn-status-complete" id="btnMarkComplete" onclick="updateOrderStatus(${order.id},'COMPLETED')">
                        <i class="fas fa-check-circle"></i> Marcar Completada
                    </button>` : '<span style="color:#4ade80; font-size:0.88em;"><i class="fas fa-check-circle"></i> Orden completada</span>'}

                    ${isVpsOrder && isCompleted ? `
                    <button class="btn-reexecute" id="btnSetPendingReexec" onclick="openSetPendingForm(${order.id}, ${JSON.stringify(!!order.vps_id)})">
                        <i class="fas fa-sync-alt"></i> Set PENDING + Re-ejecutar
                    </button>` : ''}

                    ${!isCompleted ? `
                    <button class="btn-cancel-order" id="btnCancel" onclick="updateOrderStatus(${order.id},'CANCELLED')">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>` : ''}
                </div>
            </div>
        `;

        document.getElementById('orderModal').classList.add('active');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.remove('active');
        currentOrderData = null;
    }

    async function reexecuteOrder(orderId, setPending = false) {
        const btnId = setPending ? 'btnSetPendingReexec' : 'btnReexecute';
        const btn   = document.getElementById(btnId);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ejecutando...'; }

        const serverName = document.getElementById('reexecServerName')?.value?.trim() || '';
        const password   = document.getElementById('reexecPassword')?.value?.trim()   || '';

        const payload = { order_id: orderId, set_pending: setPending };
        if (serverName) payload.name_server = serverName;
        if (password)   payload.password    = password;

        try {
            const res  = await fetch('/api/admin/orders/reexecute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (data.success) {
                showModalResult('ok', data.message);
                loadOrders(currentPage);
            } else {
                showModalResult('err', data.error || 'Error al ejecutar');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-redo"></i> Re-ejecutar'; }
            }
        } catch (e) {
            showModalResult('err', 'Error de conexión');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-redo"></i> Re-ejecutar'; }
        }
    }

    function openSetPendingForm(orderId, hasVps) {
        // Replace the footer with form + confirm for set_pending_reexecute
        const formHtml = !hasVps ? `
            <div style="margin-bottom:10px; display:flex; flex-direction:column; gap:8px;">
                <div style="font-size:0.75em; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Datos para provisionar</div>
                <input type="text" id="reexecServerName" class="form-control" placeholder="Hostname (ej: server-mi-vps)">
                <input type="text" id="reexecPassword" class="form-control" placeholder="Contraseña (se auto-genera si vacío)">
            </div>` : `<div id="reexecServerName" style="display:none;"></div><div id="reexecPassword" style="display:none;"></div>`;

        document.getElementById('orderModalFooter').innerHTML = `
            <div style="background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.2); border-radius:8px; padding:12px; margin-bottom:12px; font-size:0.85em; color:#fca5a5;">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                Esto cambiará el estado a <strong>PENDING</strong> y ejecutará <code>provisionVps</code>.
                ${hasVps ? 'El VPS asociado será renovado.' : 'Se creará un nuevo VPS.'}
            </div>
            ${formHtml}
            <div style="display:flex; gap:8px;">
                <button class="btn-reexecute" id="btnSetPendingReexec" onclick="reexecuteOrder(${orderId}, true)">
                    <i class="fas fa-sync-alt"></i> Confirmar: Set PENDING + Ejecutar
                </button>
                <button class="btn-cancel-order" onclick="openOrderModal(currentOrderData)">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
            <div id="modalActionResult"></div>
        `;
    }

    async function updateOrderStatus(orderId, status) {
        const btnId = status === 'COMPLETED' ? 'btnMarkComplete' : 'btnCancel';
        const btn   = document.getElementById(btnId);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

        try {
            const res  = await fetch('/api/admin/orders/update_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status }),
            });
            const data = await res.json();

            if (data.success) {
                showModalResult('ok', data.message);
                loadOrders(currentPage);
            } else {
                showModalResult('err', data.error || 'Error');
                if (btn) { btn.disabled = false; btn.innerHTML = status === 'COMPLETED' ? '<i class="fas fa-check-circle"></i> Marcar Completada' : '<i class="fas fa-times-circle"></i> Cancelar'; }
            }
        } catch (e) {
            showModalResult('err', 'Error de conexión');
            if (btn) btn.disabled = false;
        }
    }

    function showModalResult(type, message) {
        const el = document.getElementById('modalActionResult');
        if (el) el.innerHTML = `<div class="action-result ${type}"><i class="fas fa-${type==='ok'?'check-circle':'exclamation-circle'}"></i> ${escHtml(message)}</div>`;
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeOrderModal(); closeImportModal(); }
    });

    function openImportModal() {
        document.getElementById('importOrderId').value    = '';
        document.getElementById('importIp').value         = '';
        document.getElementById('importServerName').value = '';
        document.getElementById('importResult').innerHTML = '';
        document.getElementById('btnImportVps').disabled  = false;
        document.getElementById('btnImportVps').innerHTML = '<i class="fas fa-file-import"></i> Importar';
        document.getElementById('importVpsModal').classList.add('active');
    }

    function closeImportModal() {
        document.getElementById('importVpsModal').classList.remove('active');
    }

    function clearImportResult() {
        document.getElementById('importResult').innerHTML = '';
    }

    async function submitImportVps() {
        const orderId    = parseInt(document.getElementById('importOrderId').value) || 0;
        const ipAddress  = document.getElementById('importIp').value.trim();
        const serverName = document.getElementById('importServerName').value.trim();

        if (!orderId || !ipAddress) {
            document.getElementById('importResult').innerHTML =
                `<div class="action-result err"><i class="fas fa-exclamation-circle"></i> ID de orden e IP son requeridos.</div>`;
            return;
        }

        const btn = document.getElementById('btnImportVps');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';

        const payload = { order_id: orderId, ip_address: ipAddress };
        if (serverName) payload.name_server = serverName;

        try {
            const res  = await fetch('/api/admin/orders/import_vps', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const resp = await res.json();

            if (resp.success) {
                document.getElementById('importResult').innerHTML =
                    `<div class="action-result ok"><i class="fas fa-check-circle"></i> ${escHtml(resp.message)}</div>`;
                btn.innerHTML = '<i class="fas fa-check"></i> Importado';
                loadOrders(currentPage);
            } else {
                document.getElementById('importResult').innerHTML =
                    `<div class="action-result err"><i class="fas fa-exclamation-circle"></i> ${escHtml(resp.error || 'Error al importar')}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-import"></i> Importar';
            }
        } catch (e) {
            document.getElementById('importResult').innerHTML =
                `<div class="action-result err"><i class="fas fa-exclamation-circle"></i> Error de conexión.</div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-import"></i> Importar';
        }
    }
</script>

<?php include '../footer.php'; ?>
