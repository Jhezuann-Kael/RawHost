<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Transacciones';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Gestión de Transacciones</h1>
            </div>
            <p>Historial global de pagos y recargas</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por ID, User ID..." onkeyup="debounceSearch()">
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Estado</span>
                <div class="filter-btns" id="statusFilterBtns">
                    <button class="filter-btn active" data-value="">Todo</button>
                    <button class="filter-btn" data-value="COMPLETED">Completado</button>
                    <button class="filter-btn" data-value="PENDING">Pendiente</button>
                    <button class="filter-btn" data-value="REFUND">Reembolso</button>
                    <button class="filter-btn" data-value="FAILED">Fallido</button>
                    <button class="filter-btn" data-value="EXPIRED">Expirado</button>
                </div>
            </div>
            <div class="filter-group">
                <span class="filter-label">Tipo</span>
                <div class="filter-btns" id="typeFilterBtns">
                    <button class="filter-btn active" data-value="">Todo</button>
                    <button class="filter-btn" data-value="recharge">Recarga</button>
                    <button class="filter-btn" data-value="vps_purchase">Compra VPS</button>
                    <button class="filter-btn" data-value="vps_renew">Renovación VPS</button>
                    <button class="filter-btn" data-value="vps_upgrade">Upgrade VPS</button>
                    <button class="filter-btn" data-value="domain_purchase">Dominio</button>
                    <button class="filter-btn" data-value="managed_service">Managed</button>
                </div>
            </div>
        </div>
    </header>

    <div class="billing-section" id="financial-stats">
        <div class="billing-header">
            <h2>Resumen Financiero <small style="font-size:0.7em;color:#7f8c8d;">(excluye superusers)</small></h2>
        </div>
        <div class="billing-grid">
            <div class="billing-item highlight">
                <h4>Total Recaudado</h4>
                <div class="amount" id="stat-total">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Mes Actual</h4>
                <div class="amount" id="stat-month">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Hoy</h4>
                <div class="amount" id="stat-today">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Pendiente</h4>
                <div class="amount" id="stat-pending">$0.00</div>
            </div>
            <div class="billing-item" style="border-top: 1px solid rgba(231,76,60,.25);">
                <h4>Total Reembolsado</h4>
                <div class="amount" id="stat-refunded" style="color:#e74c3c;">$0.00</div>
            </div>
            <div class="billing-item highlight" style="border-top: 1px solid rgba(255,255,255,.08);">
                <h4>Neto (sin reembolsos)</h4>
                <div class="amount" id="stat-net">$0.00</div>
            </div>
        </div>
    </div>

    <!-- Engagement Stats -->
    <div class="billing-section" id="engagement-stats">
        <div class="billing-header">
            <h2>Actividad de Usuarios</h2>
        </div>
        <div class="billing-grid">
            <div class="billing-item">
                <h4>Crearon una orden</h4>
                <div class="amount" id="eng-ordered" style="color:var(--admin-text);">—</div>
            </div>
            <div class="billing-item highlight">
                <h4>Pagaron una orden</h4>
                <div class="amount" id="eng-paid">—</div>
            </div>
            <div class="billing-item">
                <h4>Renovaron su VPS</h4>
                <div class="amount" id="eng-renewed" style="color:#60a5fa;">—</div>
            </div>
        </div>
    </div>

    <!-- Provider Payment Calculator -->
    <div class="billing-section" id="provider-calculator">
        <div class="billing-header" style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <h2>Calculadora de Pago al Proveedor</h2>
            <div style="display:flex; align-items:center; gap:10px; margin-left:auto;">
                <label style="font-weight:600; font-size:0.9em;">Tipo de ganancia:</label>
                <select id="profitType" class="page-btn" onchange="recalcProvider()">
                    <option value="monthly">Por mes (720h)</option>
                    <option value="fixed">Fijo por transacción ($)</option>
                    <option value="percent">Porcentual (%)</option>
                </select>
                <input type="number" id="profitValue" class="page-btn" value="1" min="0" step="0.01"
                    style="width:90px; text-align:center;" oninput="recalcProvider()"
                    placeholder="1.00">
                <span id="profitUnit" style="font-weight:600; color:var(--primary);">$ / 720h</span>
            </div>
        </div>
        <div class="billing-grid">
            <div class="billing-item highlight">
                <h4>A Pagar al Proveedor</h4>
                <div class="amount" id="calc-provider" style="color:#e74c3c;">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Ganancia Neta</h4>
                <div class="amount" id="calc-profit" style="color:#27ae60;">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Transacciones Completadas</h4>
                <div class="amount" id="calc-count" style="font-size:1.8em;">0</div>
            </div>
            <div class="billing-item">
                <h4>Ganancia por Transacción</h4>
                <div class="amount" id="calc-per-tx" style="font-size:1.4em;">$0.00</div>
            </div>
        </div>
    </div>

    <!-- Transactions Table (desktop) -->
    <div class="admin-table-container" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th>Monto</th>
                    <th>Ganancia</th>
                    <th>Moneda/Red</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="transactionsTableBody">
                <tr>
                    <td colspan="9" style="text-align:center; padding: 20px;">Cargando transacciones...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Transactions Cards (móvil) -->
    <div id="tx-cards" style="display:none;"></div>

    <!-- Pagination -->
    <div id="pagination" class="pagination"></div>

</main>

<!-- Transaction Detail Modal -->
<div id="txDetailModal" class="tx-modal-overlay" onclick="if(event.target===this)closeTxDetail()">
    <div class="tx-modal">
        <div class="tx-modal-header">
            <h3><i class="fas fa-receipt"></i> Detalle de Transacción</h3>
            <button class="tx-modal-close" onclick="closeTxDetail()"><i class="fas fa-times"></i></button>
        </div>
        <div class="tx-modal-body" id="txDetailContent"></div>
        <div class="tx-modal-footer" id="txDetailActions"></div>
    </div>
</div>

<style>
/* ── Tabla compacta ────────────────────────────────────────── */
.admin-table th,
.admin-table td { padding: 8px 10px; font-size: 0.8rem; }
.admin-table th  { font-size: 0.75rem; }

.col-tx-id  { width: 36px; color: var(--admin-muted); font-size: 0.75rem; white-space: nowrap; }
.col-user   { max-width: 130px; }
.col-user .u-name { font-weight: 600; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 120px; }
.col-type   { white-space: nowrap; }
.col-date   { white-space: nowrap; }
.col-date .t-date { display: block; }
.col-date .t-time { font-size: 0.72rem; color: #7f8c8d; }

/* ── Filter bar ──────────────────────────────────────────── */
.filter-bar {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
    background: var(--admin-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 14px 18px;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-label {
    font-size: 0.75em;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--admin-muted);
    min-width: 46px;
    flex-shrink: 0;
}

.filter-btns {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.filter-btn {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    color: var(--admin-muted);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: 500;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}

.filter-btn:hover {
    border-color: var(--primary);
    color: var(--admin-text);
}

.filter-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    font-weight: 600;
}

@media (max-width: 768px) {
    .filter-group { flex-direction: column; align-items: flex-start; }
    .filter-label { min-width: auto; }
}

@media (max-width: 680px) {
    .admin-table-container { display: none !important; }
    #tx-cards { display: block !important; margin-bottom: 20px; }
}

.btn-detail {
    background: var(--primary, #3498db);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 0.85em;
    transition: opacity .2s;
}
.btn-detail:hover { opacity: .8; }

.tx-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.tx-modal-overlay.active { display: flex; }

.tx-modal {
    background: var(--bg-card, #1e2530);
    border-radius: 12px;
    width: 100%;
    max-width: 620px;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
    animation: txSlideIn .2s ease;
}
@keyframes txSlideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
}

.tx-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.tx-modal-header h3 {
    margin: 0;
    font-size: 1.05em;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--primary, #3498db);
}
.tx-modal-close {
    background: none;
    border: none;
    color: #7f8c8d;
    font-size: 1.1em;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: background .2s, color .2s;
}
.tx-modal-close:hover { background: rgba(255,255,255,.07); color: #fff; }

.tx-modal-body {
    padding: 20px 22px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.tx-detail-section {
    background: rgba(255,255,255,.04);
    border-radius: 8px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.tx-detail-section h4 {
    margin: 0 0 6px;
    font-size: 0.75em;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--primary, #3498db);
    font-weight: 700;
}

.tx-detail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.88em;
    line-height: 1.4;
}
.tx-label {
    flex-shrink: 0;
    width: 130px;
    color: #95a5a6;
    font-weight: 500;
}
.tx-detail-row span, .tx-detail-row code {
    flex: 1;
    word-break: break-all;
    color: #ecf0f1;
}
code.tx-mono {
    font-family: 'Courier New', monospace;
    font-size: 0.85em;
    background: rgba(255,255,255,.06);
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
}

/* ── Actions Footer ── */
.tx-modal-footer {
    padding: 16px 22px;
    border-top: 1px solid rgba(255,255,255,.08);
}

.tx-action-panel {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tx-action-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.tx-action-label {
    font-size: 0.78em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #95a5a6;
}

.tx-hash-input {
    flex: 1;
    min-width: 0;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 6px;
    padding: 7px 12px;
    color: #ecf0f1;
    font-family: 'Courier New', monospace;
    font-size: 0.85em;
    outline: none;
    transition: border-color .2s;
}
.tx-hash-input:focus { border-color: var(--primary, #3498db); }

.btn-action {
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 0.88em;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: opacity .2s, filter .2s;
}
.btn-action:disabled { opacity: .55; cursor: not-allowed; }
.btn-action:not(:disabled):hover { filter: brightness(1.12); }

.btn-complete-tx    { background: #27ae60; color: #fff; }
.btn-complete-order { background: #2980b9; color: #fff; width: fit-content; }
.btn-save-refund    { background: #e67e22; color: #fff; }

.tx-action-done {
    font-size: 0.88em;
    color: #27ae60;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tx-action-result {
    font-size: 0.88em;
    border-radius: 6px;
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.tx-result-ok  { background: rgba(39,174,96,.15);  color: #2ecc71; border: 1px solid rgba(39,174,96,.3); }
.tx-result-err { background: rgba(231,76,60,.15);  color: #e74c3c; border: 1px solid rgba(231,76,60,.3); }
.tx-action-result i { margin-right: 4px; }
.tx-action-result ul { color: #ecf0f1; font-size: 0.95em; }

.tx-credit-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85em;
    color: #bdc3c7;
    cursor: pointer;
    user-select: none;
    margin-top: 4px;
}
.tx-credit-toggle input[type="checkbox"] { accent-color: #27ae60; width: 15px; height: 15px; cursor: pointer; }

@media (max-width: 768px) {
    /* Provider calculator header: stack controls below title */
    #provider-calculator .billing-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    #provider-calculator .billing-header > div {
        margin-left: 0 !important;
        flex-wrap: wrap;
        width: 100%;
    }
    #provider-calculator .billing-header > div label {
        width: 100%;
    }
    #provider-calculator #profitType,
    #provider-calculator #profitValue {
        flex: 1;
        min-width: 0;
    }

    /* Modal: stack label + value vertically */
    .tx-detail-row {
        flex-direction: column;
        gap: 3px;
    }
    .tx-label {
        width: auto;
        font-size: 0.78em;
    }

    /* Modal action buttons: full width on small screens */
    .tx-action-group > div[style*="display:flex"] {
        flex-direction: column;
    }
    .btn-action {
        width: 100%;
        justify-content: center;
    }
    .tx-hash-input {
        width: 100% !important;
    }
    .btn-complete-order {
        width: 100% !important;
    }
}
</style>

<script>
    let currentPage = 1;
    let searchTimer;
    let globalStats = null;

    document.addEventListener('DOMContentLoaded', () => {
        initFilterBtns();
        loadTransactions(1);
        loadEngagementStats();
        document.getElementById('profitType').addEventListener('change', updateProfitUnit);
    });

    async function loadEngagementStats() {
        try {
            const res  = await fetch('/api/admin/orders?page=1&limit=1');
            const data = await res.json();
            if (data.engagement) {
                document.getElementById('eng-ordered').textContent  = parseInt(data.engagement.users_with_order || 0).toLocaleString();
                document.getElementById('eng-paid').textContent     = parseInt(data.engagement.users_paid       || 0).toLocaleString();
                document.getElementById('eng-renewed').textContent  = parseInt(data.engagement.users_renewed    || 0).toLocaleString();
            }
        } catch(e) { /* silencioso */ }
    }

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadTransactions(1), 500);
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
                loadTransactions(1);
            });
        });
    }

    async function loadTransactions(page) {
        currentPage = page;
        const tbody = document.getElementById('transactionsTableBody');
        const search = document.getElementById('searchInput').value;
        const status = getActiveFilter('statusFilterBtns');

        const loading = '<i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>';
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px;">${loading} Cargando...</td></tr>`;
        document.getElementById('tx-cards').innerHTML = `<div style="text-align:center;padding:20px;color:var(--admin-muted);">${loading}</div>`;

        try {
            const type   = getActiveFilter('typeFilterBtns');
            const params = new URLSearchParams({ page, limit: 10 });
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (type)   params.append('type', type);

            const res = await fetch(`/api/admin/transactions?${params.toString()}`);
            const response = await res.json();

            if (response.success) {
                renderTable(response.data);
                renderPagination(response.pagination);
                if (response.stats) {
                    renderStats(response.stats);
                    globalStats = response.stats;
                    recalcProvider();
                }
            } else {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px; color:red;">${response.message || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 20px; color:red;">Error de conexión</td></tr>';
        }
    }

    function calcProfit(amount, duration, planPrice) {
        const type = document.getElementById('profitType').value;
        const val = parseFloat(document.getElementById('profitValue').value) || 0;
        if (type === 'fixed')   return val;
        if (type === 'percent') return amount * (val / 100);
        // monthly: flat val per 720h
        //   vps_purchase/renew: duration is known, use it directly
        //   recharge (no duration): infer hours from amount using start plan price
        const startPrice = (globalStats && globalStats.start_plan_price) ? globalStats.start_plan_price : 6.91;
        const hours = duration > 0 ? duration : (amount / startPrice) * 720;
        return (hours / 720) * val;
    }

    const TX_TYPE_LABELS = {
        recharge:        'Recarga',
        vps_purchase:    'Compra VPS',
        vps_renew:       'Renovación',
        vps_upgrade:     'Upgrade',
        domain_purchase: 'Dominio',
        managed_service: 'Managed',
    };
    function txTypeLabel(type) { return TX_TYPE_LABELS[type] || (type || 'Recarga'); }

    function txBadgeClass(status) {
        if (status === 'COMPLETED') return 'badge-completed';
        if (status === 'PENDING')   return 'badge-pending';
        if (status === 'FAILED' || status === 'EXPIRED') return 'badge-failed';
        if (status === 'REFUND')    return 'badge-refund';
        return 'badge-closed';
    }

    function renderTable(data) {
        renderDesktop(data);
        renderMobile(data);
    }

    function renderDesktop(data) {
        const tbody = document.getElementById('transactionsTableBody');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 20px;">No se encontraron transacciones.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(tx => {
            const statusBadge = txBadgeClass(tx.status);
            const refundBadge = tx.amount_refund > 0
                ? `<span class="badge" style="background:rgba(231,76,60,.15);color:#e74c3c;border:1px solid rgba(231,76,60,.3);margin-left:4px;font-size:0.75em;">-$${parseFloat(tx.amount_refund).toFixed(2)}</span>`
                : '';

            const amount    = parseFloat(tx.amount);
            const duration  = parseInt(tx.duration) || 0;
            const planPrice = parseFloat(tx.plan_price) || 0;
            const profit    = tx.status === 'COMPLETED' ? calcProfit(amount, duration, planPrice) : null;
            const profitCell = profit !== null
                ? `<span style="color:#27ae60; font-weight:600;">+$${profit.toFixed(2)}</span>`
                : `<span style="color:#bdc3c7;">-</span>`;

            const d = new Date(tx.created_at);
            const dateStr = d.toLocaleDateString();
            const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            return `
                <tr data-duration="${duration}" data-plan-price="${planPrice}">
                    <td class="col-tx-id">${tx.id}</td>
                    <td class="col-user" title="${tx.email || ''}">
                        <span class="u-name">${tx.username || tx.email || 'N/A'}</span>
                        <span style="font-size:0.72rem; color:#7f8c8d;">ID: ${tx.user_id}</span>
                    </td>
                    <td class="col-type">${txTypeLabel(tx.type)}</td>
                    <td class="balance-positive">$${amount.toFixed(2)}${refundBadge}</td>
                    <td>${profitCell}</td>
                    <td style="white-space:nowrap;">
                        ${tx.payment_currency || '-'}${tx.network ? `<small style="color:#7f8c8d;"> ${tx.network}</small>` : ''}
                    </td>
                    <td class="col-date">
                        <span class="t-date">${dateStr}</span>
                        <span class="t-time">${timeStr}</span>
                    </td>
                    <td><span class="badge ${statusBadge}">${tx.status}</span></td>
                    <td>
                        <button class="btn-detail" onclick='showTxDetail(${JSON.stringify(tx)})'>
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderMobile(data) {
        const container = document.getElementById('tx-cards');
        if (!data || data.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--admin-muted);">No se encontraron transacciones.</div>';
            return;
        }

        container.innerHTML = data.map(tx => {
            const badge     = txBadgeClass(tx.status);
            const amount    = parseFloat(tx.amount);
            const refundBadge = tx.amount_refund > 0
                ? ` <span style="font-size:0.75em;color:#e74c3c;">-$${parseFloat(tx.amount_refund).toFixed(2)}</span>`
                : '';
            const typeLabel = tx.type || 'RECHARGE';

            return `
                <div class="admin-card">
                    <div class="admin-card-info">
                        <div class="admin-card-name">#${tx.id} · ${escHtml(tx.username || tx.email || 'N/A')}</div>
                        <div class="admin-card-sub">${escHtml(typeLabel)}${tx.payment_currency ? ' · ' + escHtml(tx.payment_currency) : ''}</div>
                        <div class="admin-card-meta">
                            <span class="badge ${badge}">${tx.status}</span>
                            <span style="color:#4ade80;font-weight:600;font-size:0.85em;">$${amount.toFixed(2)}${refundBadge}</span>
                        </div>
                        <div class="admin-card-date">${new Date(tx.created_at).toLocaleString()}</div>
                    </div>
                    <div class="admin-card-actions">
                        <button class="btn-detail" onclick='showTxDetail(${JSON.stringify(tx)})'>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>`;
        }).join('');
    }

    function recalcProvider() {
        updateProfitUnit();
        if (!globalStats) return;

        const type = document.getElementById('profitType').value;
        const val = parseFloat(document.getElementById('profitValue').value) || 0;
        const total = parseFloat(globalStats.total_revenue || 0);
        const count = parseInt(globalStats.completed_count || 0);
        const totalHours = parseInt(globalStats.total_hours || 0);

        let totalProfit;
        if (type === 'fixed') {
            totalProfit = val * count;
        } else if (type === 'percent') {
            totalProfit = total * (val / 100);
        } else {
            // monthly: $val por cada 720h compradas (suma real de horas)
            totalProfit = (totalHours / 720) * val;
        }

        const providerPayment = Math.max(0, total - totalProfit);
        const profitPerTx = count > 0 ? totalProfit / count : 0;

        document.getElementById('calc-provider').textContent = '$' + providerPayment.toFixed(2);
        document.getElementById('calc-profit').textContent = '$' + totalProfit.toFixed(2);
        document.getElementById('calc-count').textContent = count.toLocaleString();
        document.getElementById('calc-per-tx').textContent = '$' + profitPerTx.toFixed(2);

        // Re-render profit column without reloading
        document.querySelectorAll('#transactionsTableBody tr[data-duration]').forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 9) return;
            const statusBadge = cells[7].querySelector('.badge');
            if (!statusBadge || statusBadge.textContent !== 'COMPLETED') {
                cells[4].innerHTML = '<span style="color:#bdc3c7;">-</span>';
                return;
            }
            const amount = parseFloat(cells[3].textContent.replace('$', '')) || 0;
            const duration = parseInt(row.dataset.duration) || 0;
            const planPrice = parseFloat(row.dataset.planPrice) || 0;
            const profit = calcProfit(amount, duration, planPrice);
            cells[4].innerHTML = `<span style="color:#27ae60; font-weight:600;">+$${profit.toFixed(2)}</span>`;
        });
    }

    function updateProfitUnit() {
        const type = document.getElementById('profitType').value;
        const units = { monthly: '$ / 720h', fixed: '$ / tx', percent: '%' };
        const steps = { monthly: '0.01', fixed: '0.01', percent: '0.01' };
        document.getElementById('profitUnit').textContent = units[type];
        document.getElementById('profitValue').step = steps[type];
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total <= pagination.limit) {
            container.innerHTML = '';
            return;
        }

        const current = pagination.current_page;
        const total = pagination.pages;
        let html = '';

        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="loadTransactions(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;

        const start = Math.max(1, current - 2);
        const end = Math.min(total, current + 2);
        for (let i = start; i <= end; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadTransactions(${i})">${i}</button>`;
        }

        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="loadTransactions(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;
        container.innerHTML = html;
    }

    function showTxDetail(tx) {
        const meta = (() => {
            try { return tx.order_metadata ? JSON.parse(tx.order_metadata) : null; }
            catch(e) { return null; }
        })();

        const field = (label, value, mono = false) => {
            if (value === null || value === undefined || value === '') return '';
            const val = mono
                ? `<code class="tx-mono">${escHtml(String(value))}</code>`
                : `<span>${escHtml(String(value))}</span>`;
            return `<div class="tx-detail-row"><span class="tx-label">${label}</span>${val}</div>`;
        };

        const metaHtml = meta ? Object.entries(meta).map(([k, v]) =>
            field(k, typeof v === 'object' ? JSON.stringify(v) : v, false)
        ).join('') : '';

        document.getElementById('txDetailContent').innerHTML = `
            <div class="tx-detail-section">
                <h4>Identificación</h4>
                ${field('ID', '#' + tx.id)}
                ${field('Track ID', tx.track_id, true)}
                ${field('Order ID', tx.order_id, true)}
                ${field('TX Hash', tx.tx_hash, true)}
            </div>
            <div class="tx-detail-section">
                <h4>Usuario</h4>
                ${field('Username', tx.username || tx.email)}
                ${field('User ID', tx.user_id)}
                ${field('Email', tx.email)}
            </div>
            <div class="tx-detail-section">
                <h4>Pago</h4>
                ${field('Monto USD', tx.amount ? '$' + parseFloat(tx.amount).toFixed(2) : null)}
                ${field('Monto Crypto', tx.payment_amount ? tx.payment_amount + ' ' + (tx.payment_currency || '') : null)}
                ${field('Moneda / Red', [tx.payment_currency, tx.network].filter(Boolean).join(' / '))}
                ${field('Dirección', tx.address, true)}
                ${field('Memo', tx.memo, true)}
            </div>
            <div class="tx-detail-section">
                <h4>Estado y Fechas</h4>
                ${field('Tipo', tx.type)}
                ${field('Estado', tx.status)}
                ${field('Descripción', tx.description)}
                ${field('Creado', tx.created_at ? new Date(tx.created_at).toLocaleString() : null)}
                ${field('Actualizado', tx.updated_at ? new Date(tx.updated_at).toLocaleString() : null)}
                ${field('Expira', tx.expired_at ? new Date(tx.expired_at).toLocaleString() : null)}
                ${tx.amount_refund > 0 ? field('Reembolsado', '$' + parseFloat(tx.amount_refund).toFixed(2)) : ''}
            </div>
            ${metaHtml ? `<div class="tx-detail-section"><h4>Order Metadata</h4>${metaHtml}</div>` : ''}
        `;
        // Action panel — only for non-completed transactions
        const canComplete = tx.status !== 'COMPLETED';
        const hasOrder    = !!tx.order_id;

        const refundSection = `
            <div class="tx-action-group" style="margin-top:10px;">
                <label class="tx-action-label">Monto Reembolsado (USD)</label>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="number" id="refundAmountInput" class="tx-hash-input" min="0" step="0.01"
                        placeholder="0.00" value="${escHtml(tx.amount_refund > 0 ? parseFloat(tx.amount_refund).toFixed(2) : '')}"
                        style="width:120px; font-family:inherit;">
                    <button class="btn-action btn-save-refund" onclick="saveRefund(${tx.id})">
                        <i class="fas fa-undo-alt"></i> Guardar Reembolso
                    </button>
                </div>
            </div>`;

        document.getElementById('txDetailActions').innerHTML = canComplete ? `
            <div class="tx-action-panel">
                <div class="tx-action-group" id="completeTxGroup">
                    <label class="tx-action-label">TX Hash (opcional)</label>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <input type="text" id="txHashInput" class="tx-hash-input" placeholder="0x... o vacío" value="${escHtml(tx.tx_hash || '')}">
                        <button class="btn-action btn-complete-tx" onclick="confirmCompleteTx(${tx.id})">
                            <i class="fas fa-check-circle"></i> Marcar como Pagada
                        </button>
                    </div>
                    ${tx.type === 'recharge' ? `
                    <label class="tx-credit-toggle">
                        <input type="checkbox" id="creditBalanceCheck" checked>
                        <span>Acreditar saldo al usuario <small style="color:#e67e22;">(desmarca si ya lo hiciste manualmente)</small></span>
                    </label>` : ''}
                </div>
                ${hasOrder ? `
                <div class="tx-action-group">
                    <button class="btn-action btn-complete-order" onclick="confirmCompleteOrder(${tx.order_id}, ${tx.id})">
                        <i class="fas fa-clipboard-check"></i> Marcar Orden #${escHtml(String(tx.order_id))} como Completada
                    </button>
                </div>` : ''}
                ${refundSection}
            </div>
        ` : `
            <div class="tx-action-panel">
                <div class="tx-action-done"><i class="fas fa-check-circle"></i> Transacción ya completada</div>
                ${refundSection}
            </div>`;

        document.getElementById('txDetailModal').classList.add('active');
    }

    async function confirmCompleteTx(txId) {
        const txHash       = document.getElementById('txHashInput').value.trim();
        const creditCheck  = document.getElementById('creditBalanceCheck');
        const creditBalance = creditCheck ? creditCheck.checked : true;
        const btn = document.querySelector('.btn-complete-tx');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        try {
            const res = await fetch('/api/admin/update_transaction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'complete_transaction', transaction_id: txId, tx_hash: txHash, credit_balance: creditBalance }),
            });
            const data = await res.json();

            if (data.success) {
                showTxActionResult('success', data.message, data.side_effects || []);
                loadTransactions(currentPage);
            } else {
                showTxActionResult('error', data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Marcar como Pagada';
            }
        } catch(e) {
            showTxActionResult('error', 'Error de conexión');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Marcar como Pagada';
        }
    }

    async function confirmCompleteOrder(orderId, txId) {
        const btn = document.querySelector('.btn-complete-order');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        try {
            const res = await fetch('/api/admin/update_transaction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'complete_order', order_id: orderId }),
            });
            const data = await res.json();

            if (data.success) {
                showTxActionResult('success', data.message);
                loadTransactions(currentPage);
            } else {
                showTxActionResult('error', data.message);
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-clipboard-check"></i> Marcar Orden #${orderId} como Completada`;
            }
        } catch(e) {
            showTxActionResult('error', 'Error de conexión');
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-clipboard-check"></i> Marcar Orden #${orderId} como Completada`;
        }
    }

    function showTxActionResult(type, message, effects = []) {
        const panel = document.getElementById('txDetailActions');
        const effectsHtml = effects.length
            ? '<ul style="margin:6px 0 0; padding-left:18px;">' + effects.map(e => `<li>${escHtml(e)}</li>`).join('') + '</ul>'
            : '';
        panel.innerHTML = `
            <div class="tx-action-result ${type === 'success' ? 'tx-result-ok' : 'tx-result-err'}">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${escHtml(message)}${effectsHtml}
            </div>`;
    }

    async function saveRefund(txId) {
        const input = document.getElementById('refundAmountInput');
        const amount = parseFloat(input.value) || 0;
        const btn = document.querySelector('.btn-save-refund');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        try {
            const res = await fetch('/api/admin/update_transaction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_refund', transaction_id: txId, amount_refund: amount }),
            });
            const data = await res.json();

            if (data.success) {
                showTxActionResult('success', data.message);
                loadTransactions(currentPage);
            } else {
                showTxActionResult('error', data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-undo-alt"></i> Guardar Reembolso';
            }
        } catch(e) {
            showTxActionResult('error', 'Error de conexión');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo-alt"></i> Guardar Reembolso';
        }
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function closeTxDetail() {
        document.getElementById('txDetailModal').classList.remove('active');
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeTxDetail();
    });

    function renderStats(stats) {
        if (!stats) return;
        const total    = parseFloat(stats.total_revenue  || 0);
        const refunded = parseFloat(stats.total_refunded || 0);
        document.getElementById('stat-total').textContent    = '$' + total.toFixed(2);
        document.getElementById('stat-month').textContent    = '$' + parseFloat(stats.month_revenue   || 0).toFixed(2);
        document.getElementById('stat-today').textContent    = '$' + parseFloat(stats.today_revenue   || 0).toFixed(2);
        document.getElementById('stat-pending').textContent  = '$' + parseFloat(stats.pending_revenue || 0).toFixed(2);
        document.getElementById('stat-refunded').textContent = '-$' + refunded.toFixed(2);
        document.getElementById('stat-net').textContent      = '$' + Math.max(0, total - refunded).toFixed(2);
    }

</script>

<?php include '../footer.php'; ?>