<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Gastos';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Registro de Gastos</h1>
            </div>
            <p>Historial de pagos al proveedor y otros gastos operacionales</p>
        </div>
        <div class="table-controls">
            <button class="btn" onclick="openModal()" style="background:var(--primary); color:#fff; padding:8px 18px; border-radius:8px; border:none; cursor:pointer; font-weight:600;">
                <i class="fas fa-plus"></i> Nuevo Gasto
            </button>
        </div>
    </header>

    <!-- Totals -->
    <div class="billing-section">
        <div class="billing-header"><h2>Totales</h2></div>
        <div class="billing-grid">
            <div class="billing-item highlight">
                <h4>Total USD</h4>
                <div class="amount" id="total-usd">$0.00</div>
            </div>
            <div class="billing-item">
                <h4>Total EUR</h4>
                <div class="amount" id="total-eur">€0.00</div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="admin-table-container" style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Moneda</th>
                    <th>Monto Crypto/Moneda</th>
                    <th>Monto Fiat</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="expensesTableBody">
                <tr>
                    <td colspan="7" style="text-align:center; padding:20px;">Cargando...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="pagination" class="pagination"></div>

    <!-- New Expense Modal -->
    <div id="expenseModal" class="modal-overlay">
        <div class="modal" style="width:480px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice-dollar" style="margin-right:8px; color:var(--primary);"></i>Nuevo Gasto</h2>
                <button class="btn-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Moneda usada <span style="color:#e74c3c;">*</span></label>
                    <input type="text" id="inp-currency" class="form-control" placeholder="BTC, USDT, ETH, USD…" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Monto en esa moneda <span style="color:#7f8c8d; font-weight:400;">(opcional)</span></label>
                    <input type="number" id="inp-amount-currency" class="form-control" placeholder="0.00000000" step="any" min="0">
                </div>
                <div style="display:grid; grid-template-columns:1fr 120px; gap:12px;">
                    <div class="form-group">
                        <label>Monto Fiat <span style="color:#e74c3c;">*</span></label>
                        <input type="number" id="inp-amount-fiat" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Divisa</label>
                        <select id="inp-fiat-currency" class="form-control">
                            <option value="USD">USD $</option>
                            <option value="EUR">EUR €</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Descripción / Notas <span style="color:#7f8c8d; font-weight:400;">(opcional)</span></label>
                    <textarea id="inp-description" class="form-control" rows="3" placeholder="Ej: Pago proveedor Hetzner — Factura #123…" style="resize:vertical;"></textarea>
                </div>
                <p id="modal-error" style="color:#e74c3c; font-size:0.85em; margin:0; display:none;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                <button class="btn" id="saveBtn" onclick="saveExpense()" style="background:var(--primary); color:#fff;">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal" style="width:400px;">
            <div class="modal-header">
                <h2 style="color:#e74c3c;"><i class="fas fa-triangle-exclamation" style="margin-right:8px;"></i>Eliminar Gasto</h2>
                <button class="btn-close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-muted);">¿Seguro que quieres eliminar este registro? Esta acción no se puede deshacer.</p>
                <input type="hidden" id="deleteId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn" onclick="confirmDelete()"
                    style="background:rgba(255,77,77,0.15); color:#ff4d4d; border:1px solid rgba(255,77,77,0.3);">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

</main>

<style>
.admin-table th,
.admin-table td { padding: 8px 10px; font-size: 0.8rem; }
.admin-table th  { font-size: 0.75rem; }
.col-exp-id  { width: 36px; color: var(--admin-muted); font-size: 0.75rem; white-space: nowrap; }
.col-exp-date .t-date { display: block; }
.col-exp-date .t-time { font-size: 0.72rem; color: #7f8c8d; }
.col-exp-desc { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--admin-muted); font-size: 0.8rem; }
</style>

<script>
    let currentPage = 1;

    document.addEventListener('DOMContentLoaded', () => loadExpenses(1));

    async function loadExpenses(page) {
        currentPage = page;
        const tbody = document.getElementById('expensesTableBody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const res = await fetch(`../../api/admin/expenses?page=${page}&limit=20`);
            const r = await res.json();
            if (r.success) {
                renderTable(r.data);
                renderPagination(r.pagination);
                renderTotals(r.totals);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red; padding:20px;">${r.error || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red; padding:20px;">Error de conexión</td></tr>';
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('expensesTableBody');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:var(--text-muted);">Sin registros aún.</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(e => {
            const amountCrypto = e.amount_currency
                ? `<span style="font-family:monospace;">${parseFloat(e.amount_currency).toLocaleString(undefined, {maximumFractionDigits:8})}</span>`
                : '<span style="color:#bdc3c7;">—</span>';
            const symbol = e.fiat_currency === 'EUR' ? '€' : '$';
            const d = new Date(e.created_at);
            const dateStr = d.toLocaleDateString();
            const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const desc = e.description ? escHtml(e.description) : '—';
            return `
                <tr>
                    <td class="col-exp-id">${e.id}</td>
                    <td><span style="font-weight:700; background:rgba(var(--primary-rgb),0.12); color:var(--primary); padding:2px 8px; border-radius:5px; font-size:0.82em;">${e.currency}</span></td>
                    <td>${amountCrypto}</td>
                    <td style="font-weight:600; white-space:nowrap;">${symbol}${parseFloat(e.amount_fiat).toFixed(2)} <small style="color:#7f8c8d;">${e.fiat_currency}</small></td>
                    <td class="col-exp-desc" title="${escHtml(e.description || '')}">${desc}</td>
                    <td class="col-exp-date">
                        <span class="t-date">${dateStr}</span>
                        <span class="t-time">${timeStr}</span>
                    </td>
                    <td>
                        <button class="action-btn btn-delete" onclick="deleteExpense(${e.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        }).join('');
    }

    function renderTotals(totals) {
        if (!totals) return;
        document.getElementById('total-usd').textContent = '$' + (totals.USD || 0).toFixed(2);
        document.getElementById('total-eur').textContent = '€' + (totals.EUR || 0).toFixed(2);
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total <= pagination.limit) { container.innerHTML = ''; return; }
        const current = pagination.current_page, total = pagination.pages;
        let html = `<button class="page-btn" ${current===1?'disabled':''} onclick="loadExpenses(${current-1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = Math.max(1,current-2); i <= Math.min(total,current+2); i++)
            html += `<button class="page-btn ${i===current?'active':''}" onclick="loadExpenses(${i})">${i}</button>`;
        html += `<button class="page-btn" ${current===total?'disabled':''} onclick="loadExpenses(${current+1})"><i class="fas fa-chevron-right"></i></button>`;
        container.innerHTML = html;
    }

    function openModal() {
        document.getElementById('inp-currency').value = 'USDT';
        document.getElementById('inp-amount-currency').value = '';
        document.getElementById('inp-amount-fiat').value = '';
        document.getElementById('inp-fiat-currency').value = 'USD';
        document.getElementById('inp-description').value = '';
        document.getElementById('modal-error').style.display = 'none';
        document.getElementById('expenseModal').classList.add('active');
        setTimeout(() => document.getElementById('inp-currency').focus(), 100);
    }

    function closeModal() { document.getElementById('expenseModal').classList.remove('active'); }

    async function saveExpense() {
        const currency    = document.getElementById('inp-currency').value.trim();
        const amountCrypto = document.getElementById('inp-amount-currency').value.trim();
        const amountFiat  = document.getElementById('inp-amount-fiat').value.trim();
        const fiatCurrency = document.getElementById('inp-fiat-currency').value;
        const description = document.getElementById('inp-description').value.trim();
        const errEl = document.getElementById('modal-error');

        if (!currency) { showErr('La moneda es obligatoria.'); return; }
        if (!amountFiat || isNaN(amountFiat) || parseFloat(amountFiat) < 0) { showErr('Ingresa un monto fiat válido.'); return; }

        const body = { currency, amount_fiat: parseFloat(amountFiat), fiat_currency: fiatCurrency };
        if (amountCrypto !== '') body.amount_currency = parseFloat(amountCrypto);
        if (description)         body.description = description;

        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

        try {
            const res = await fetch('../../api/admin/expenses', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const r = await res.json();
            if (r.success) {
                closeModal();
                loadExpenses(1);
            } else {
                showErr(r.error || 'Error al guardar.');
            }
        } catch (e) {
            showErr('Error de conexión.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Guardar';
        }
    }

    function showErr(msg) {
        const el = document.getElementById('modal-error');
        el.textContent = msg;
        el.style.display = 'block';
    }

    function deleteExpense(id) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); }

    async function confirmDelete() {
        const id = document.getElementById('deleteId').value;
        try {
            const res = await fetch(`../../api/admin/expenses?id=${id}`, { method: 'DELETE' });
            const r = await res.json();
            if (r.success) { closeDeleteModal(); loadExpenses(currentPage); }
            else alert(r.error || 'Error al eliminar');
        } catch (e) { alert('Error de conexión'); }
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
</script>

<?php include '../footer.php'; ?>
