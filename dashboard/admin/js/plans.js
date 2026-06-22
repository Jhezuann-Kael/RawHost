let plans = [];
let activePlanId = null;
let editingFeeId = null;

document.addEventListener('DOMContentLoaded', loadPlans);

// ── Load all plans ────────────────────────────────────────────────────────────
async function loadPlans() {
    try {
        const res = await fetch('../../api/plans/list');
        const r = await res.json();
        if (r.success) {
            plans = r.data;
            renderPlanList();
        } else {
            document.getElementById('planListBody').innerHTML =
                `<div style="padding:20px; color:#f87171;">${r.message || 'Error al cargar planes'}</div>`;
        }
    } catch (e) {
        document.getElementById('planListBody').innerHTML =
            '<div style="padding:20px; color:#f87171;">Error de conexión</div>';
    }
}

function renderPlanList() {
    const container = document.getElementById('planListBody');
    if (!plans.length) {
        container.innerHTML = '<div style="padding:20px; color:var(--admin-muted);">Sin planes.</div>';
        return;
    }
    container.innerHTML = plans.map(p => `
        <div class="plan-item ${p.id == activePlanId ? 'active' : ''}" onclick="selectPlan(${p.id})" id="plan-item-${p.id}">
            <div>
                <div class="plan-item-name">${esc(p.name)}</div>
                <div class="fee-count">${p.fees.length} fee${p.fees.length !== 1 ? 's' : ''}</div>
            </div>
            <div class="plan-item-price">$${parseFloat(p.price).toFixed(2)} <small style="font-weight:400;color:var(--admin-muted);">${p.currency}</small></div>
        </div>
    `).join('');
}

// ── Select plan → render detail ───────────────────────────────────────────────
function selectPlan(id) {
    activePlanId = id;
    editingFeeId = null;
    document.querySelectorAll('.plan-item').forEach(el => el.classList.remove('active'));
    const item = document.getElementById(`plan-item-${id}`);
    if (item) item.classList.add('active');

    const plan = plans.find(p => p.id == id);
    if (!plan) return;
    renderDetail(plan);

    // On tablet/mobile scroll detail into view
    if (window.innerWidth <= 900) {
        document.getElementById('detailPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function renderDetail(plan) {
    const panel = document.getElementById('detailPanel');
    panel.innerHTML = `
        <div class="detail-header">
            <h2><i class="fas fa-layer-group" style="color:var(--primary); margin-right:10px;"></i>${esc(plan.name)}</h2>
        </div>
        <div class="detail-body">

            <div class="section-block">
                <div class="section-block-title">Especificaciones</div>
                <div class="specs-grid">
                    <div class="spec-item">
                        <div class="spec-val">${plan.cpu} vCPU</div>
                        <div class="spec-label">CPU</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-val">${parseFloat(plan.ram).toFixed(1)} GB</div>
                        <div class="spec-label">RAM</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-val">${plan.disk} GB</div>
                        <div class="spec-label">Disco</div>
                    </div>
                </div>
            </div>

            <div class="section-block">
                <div class="section-block-title">Precio mensual</div>
                <div class="price-row">
                    <div>
                        <label class="form-label">Precio</label>
                        <input type="number" id="inp-price" class="form-control" value="${parseFloat(plan.price).toFixed(2)}" step="0.01" min="0">
                    </div>
                    <div>
                        <label class="form-label">Moneda</label>
                        <input type="text" class="form-control" value="${esc(plan.currency)}" disabled style="opacity:0.6;">
                    </div>
                    <div>
                        <label class="form-label">&nbsp;</label>
                        <button class="btn-primary-sm" onclick="savePrice(${plan.id})" id="btn-save-price">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

            <div class="section-block">
                <div class="section-block-title">Fees</div>
                <div id="fees-list">${renderFeesList(plan.fees)}</div>
                <div id="fee-form-area">${renderAddFeeForm()}</div>
            </div>

        </div>
    `;
}

function renderFeesList(fees) {
    if (!fees || fees.length === 0) {
        return '<div style="color:var(--admin-muted); font-size:0.85rem; margin-bottom:14px;">Sin fees configurados.</div>';
    }
    return `<div class="fees-list">${fees.map(f => renderFeeRow(f)).join('')}</div>`;
}

function renderFeeRow(f) {
    const typeClass  = f.type === 'percentage' ? 'type-percentage' : 'type-fixed';
    const typeLabel  = f.type === 'percentage' ? '%' : 'Fijo';
    const valueDisplay = f.type === 'percentage'
        ? `${parseFloat(f.value).toFixed(2)}%`
        : `$${parseFloat(f.value).toFixed(4)}`;
    const billingClass = f.billing_type === 'setup' ? 'billing-setup' : f.billing_type === 'short_term' ? 'billing-short-term' : 'billing-recurring';
    const billingLabel = f.billing_type === 'setup' ? 'Setup' : f.billing_type === 'short_term' ? 'Corto Plazo' : 'Recurrente';

    return `
        <div class="fee-row" id="fee-row-${f.id}">
            <div class="fee-row-label">${esc(f.name)}</div>
            <div><span class="fee-row-type ${typeClass}">${typeLabel}</span></div>
            <div><span class="fee-row-billing ${billingClass}">${billingLabel}</span></div>
            <div class="fee-row-value">${valueDisplay}</div>
            <div class="fee-row-currency">${esc(f.currency)}</div>
            <div class="fee-actions">
                <button class="btn-icon btn-icon-edit" onclick="editFee(${f.id})" title="Editar">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn-icon btn-icon-delete" onclick="deleteFee(${f.id})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
}

function renderAddFeeForm(fee) {
    const isEdit = !!fee;
    return `
        <div class="add-fee-form">
            <div>
                <label class="form-label">Nombre</label>
                <input type="text" id="fee-name" class="form-control" placeholder="Ej: IVA" value="${fee ? esc(fee.name) : ''}">
            </div>
            <div>
                <label class="form-label">Tipo valor</label>
                <select id="fee-type" class="form-control">
                    <option value="percentage" ${fee && fee.type === 'percentage' ? 'selected' : ''}>Porcentaje</option>
                    <option value="fixed"      ${fee && fee.type === 'fixed'      ? 'selected' : ''}>Precio fijo</option>
                </select>
            </div>
            <div>
                <label class="form-label">Cobro</label>
                <select id="fee-billing-type" class="form-control">
                    <option value="setup"      ${fee && fee.billing_type === 'setup'      ? 'selected' : ''}>Setup (único)</option>
                    <option value="recurring"  ${!fee || fee.billing_type === 'recurring' ? 'selected' : ''}>Recurrente</option>
                    <option value="short_term" ${fee && fee.billing_type === 'short_term' ? 'selected' : ''}>Corto Plazo (&lt;720h)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Valor</label>
                <input type="number" id="fee-value" class="form-control" placeholder="0.00" step="any" min="0" value="${fee ? fee.value : ''}">
            </div>
            <div>
                <label class="form-label">Moneda</label>
                <input type="text" id="fee-currency" class="form-control" placeholder="USD" maxlength="3" value="${fee ? esc(fee.currency) : 'USD'}">
            </div>
            <div style="display:flex; gap:6px;">
                <div>
                    <label class="form-label">&nbsp;</label>
                    <button class="btn-primary-sm" onclick="${isEdit ? `updateFee(${fee.id})` : 'addFee()'}" id="btn-fee-action">
                        <i class="fas ${isEdit ? 'fa-check' : 'fa-plus'}"></i> ${isEdit ? 'Actualizar' : 'Agregar'}
                    </button>
                </div>
                ${isEdit ? `<div><label class="form-label">&nbsp;</label><button class="btn-outline-sm" onclick="cancelEditFee()">Cancelar</button></div>` : ''}
            </div>
        </div>
    `;
}

// ── Price ─────────────────────────────────────────────────────────────────────
async function savePrice(planId) {
    const price = parseFloat(document.getElementById('inp-price').value);
    if (isNaN(price) || price < 0) { toast('Precio inválido', 'error'); return; }

    const btn = document.getElementById('btn-save-price');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const res = await fetch('../../api/admin/plans', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: planId, price })
        });
        const r = await res.json();
        if (r.success) {
            const plan = plans.find(p => p.id == planId);
            if (plan) plan.price = price;
            renderPlanList();
            toast('Precio actualizado', 'success');
        } else {
            toast(r.message || 'Error al guardar', 'error');
        }
    } catch (e) {
        toast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar';
    }
}

// ── Fees ──────────────────────────────────────────────────────────────────────
async function addFee() {
    const plan = plans.find(p => p.id == activePlanId);
    if (!plan) return;

    const name        = document.getElementById('fee-name').value.trim();
    const type        = document.getElementById('fee-type').value;
    const billingType = document.getElementById('fee-billing-type').value;
    const value       = document.getElementById('fee-value').value;
    const currency    = document.getElementById('fee-currency').value.trim().toUpperCase() || 'USD';

    if (!name || !value) { toast('Nombre y valor son obligatorios', 'error'); return; }

    const btn = document.getElementById('btn-fee-action');
    btn.disabled = true;

    try {
        const res = await fetch('../../api/plans/fees', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: activePlanId, name, type, billing_type: billingType, value: parseFloat(value), currency })
        });
        const r = await res.json();
        if (r.success) {
            await refreshPlanFees(plan);
            toast('Fee agregado', 'success');
        } else {
            toast(r.message || 'Error', 'error');
        }
    } catch (e) {
        toast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
    }
}

function editFee(feeId) {
    const plan = plans.find(p => p.id == activePlanId);
    if (!plan) return;
    const fee = plan.fees.find(f => f.id == feeId);
    if (!fee) return;

    editingFeeId = feeId;
    document.getElementById('fee-form-area').innerHTML = renderAddFeeForm(fee);
}

function cancelEditFee() {
    editingFeeId = null;
    document.getElementById('fee-form-area').innerHTML = renderAddFeeForm();
}

async function updateFee(feeId) {
    const name        = document.getElementById('fee-name').value.trim();
    const type        = document.getElementById('fee-type').value;
    const billingType = document.getElementById('fee-billing-type').value;
    const value       = document.getElementById('fee-value').value;
    const currency    = document.getElementById('fee-currency').value.trim().toUpperCase() || 'USD';

    if (!name || !value) { toast('Nombre y valor son obligatorios', 'error'); return; }

    const btn = document.getElementById('btn-fee-action');
    btn.disabled = true;

    try {
        const res = await fetch('../../api/plans/fees', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: feeId, name, type, billing_type: billingType, value: parseFloat(value), currency })
        });
        const r = await res.json();
        if (r.success) {
            const plan = plans.find(p => p.id == activePlanId);
            await refreshPlanFees(plan);
            editingFeeId = null;
            toast('Fee actualizado', 'success');
        } else {
            toast(r.message || 'Error', 'error');
        }
    } catch (e) {
        toast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function deleteFee(feeId) {
    if (!confirm('¿Eliminar este fee?')) return;

    try {
        const res = await fetch('../../api/plans/fees', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: feeId })
        });
        const r = await res.json();
        if (r.success) {
            const plan = plans.find(p => p.id == activePlanId);
            await refreshPlanFees(plan);
            toast('Fee eliminado', 'success');
        } else {
            toast(r.message || 'Error', 'error');
        }
    } catch (e) {
        toast('Error de conexión', 'error');
    }
}

async function refreshPlanFees(plan) {
    try {
        const res = await fetch(`../../api/plans/fees?plan_id=${plan.id}`);
        const r = await res.json();
        if (r.success) {
            plan.fees = r.data;
            document.getElementById('fees-list').innerHTML = renderFeesList(plan.fees);
            document.getElementById('fee-form-area').innerHTML = renderAddFeeForm();
            const feeCount = document.querySelector(`#plan-item-${plan.id} .fee-count`);
            if (feeCount) feeCount.textContent = `${plan.fees.length} fee${plan.fees.length !== 1 ? 's' : ''}`;
        }
    } catch (e) { /* silently ignore */ }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

let toastTimer;
function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.className = `toast toast-${type} show`;
    el.textContent = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
}
