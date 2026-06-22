<?php
$pageTitle = 'Detalles de Usuario - Admin';
include 'includes/header.php';
?>
<?php include 'includes/user_detail_styles.php'; ?>

<?php
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$userId) {
    header('Location: /dashboard/admin/users');
    exit;
}
?>

<main class="main-content">
    <div class="detail-header">
        <h1>Detalles del Usuario</h1>
        <div class="header-actions">
            <button class="balance-btn" onclick="openEditUserModal()" style="background:#3498db;">
                <i class="fas fa-edit"></i> Editar Usuario
            </button>
            <button class="balance-btn" onclick="openBalanceModal()">
                <i class="fas fa-wallet"></i> Modificar Saldo
            </button>
            <button class="balance-btn" id="btnToggleSupportBlock" onclick="toggleSupportBlock()" style="background:#e67e22;">
                <i class="fas fa-ban"></i> <span id="btnSupportBlockLabel">Bloquear Soporte</span>
            </button>
            <button class="balance-btn" onclick="confirmDeleteUser()" style="background:#e74c3c;">
                <i class="fas fa-trash"></i> Eliminar Usuario
            </button>
            <a href="/dashboard/admin/users" class="back-btn">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- User Information -->
    <div class="detail-card">
        <h2 class="section-title">Información del Usuario</h2>
        <div class="user-info-section">
            <div class="info-item">
                <div class="info-label">ID</div>
                <div class="info-value" id="infoId">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Nombre de Usuario</div>
                <div class="info-value" id="infoUsername">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value" id="infoEmail">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Telegram</div>
                <div class="info-value" id="infoTelegram">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Código de Referido</div>
                <div class="info-value" id="infoReferral">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Saldo Actual</div>
                <div class="info-value" id="infoBalance" style="color:var(--primary);">$0.00</div>
            </div>
            <div class="info-item">
                <div class="info-label">Rol</div>
                <div class="info-value" id="infoRole">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha de Registro</div>
                <div class="info-value" id="infoCreatedAt">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Moneda Preferida</div>
                <div class="info-value" id="infoPrefCurrency">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Acceso a Soporte</div>
                <div class="info-value" id="infoSupportBlocked">-</div>
            </div>
        </div>
    </div>

    <!-- VPS -->
    <div class="detail-card">
        <h2 class="section-title"><i class="fas fa-server"></i> Servidores VPS</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nombre</th><th>IP</th><th>Estado</th>
                    <th>OS</th><th>Creado</th><th>Acción</th>
                </tr>
            </thead>
            <tbody id="vpsTableBody">
                <tr><td colspan="7" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Movements + Transactions tabbed -->
    <div class="detail-card" style="padding:0;">
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('movements', this)">
                <i class="fas fa-exchange-alt"></i> Movimientos de Saldo
            </button>
            <button class="tab-btn" onclick="switchTab('transactions', this)">
                <i class="fas fa-credit-card"></i> Transacciones
            </button>
        </div>

        <div id="tab-movements" class="tab-panel" style="padding:20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Tipo</th><th>Monto</th>
                        <th>Descripción</th><th>Fecha</th><th>Acción</th>
                    </tr>
                </thead>
                <tbody id="movementsTableBody">
                    <tr><td colspan="6" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="tab-transactions" class="tab-panel" style="padding:20px; display:none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Track ID</th><th>Monto</th><th>Cripto</th>
                        <th>Estado</th><th>Fecha</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr><td colspan="6" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tickets -->
    <div class="detail-card">
        <h2 class="section-title"><i class="fas fa-ticket-alt"></i> Tickets de Soporte</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Asunto</th><th>Categoría</th><th>Estado</th>
                    <th>Prioridad</th><th>Fecha</th><th>Acción</th>
                </tr>
            </thead>
            <tbody id="ticketsTableBody">
                <tr><td colspan="7" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</main>

<script>
const userId = <?php echo $userId; ?>;

window.addEventListener('DOMContentLoaded', () => {
    loadUserInfo();
    loadVPS();
    loadMovements();
    loadTransactions();
    loadTickets();
});

async function loadUserInfo() {
    try {
        const res  = await fetch(`../../api/admin/user_detail?id=${userId}`);
        const data = await res.json();
        if (data.success && data.user) {
            const u = data.user;
            document.getElementById('infoId').textContent        = u.id;
            document.getElementById('infoUsername').textContent  = u.username;
            document.getElementById('infoEmail').textContent     = u.email;
            const tgDisplay = u.tg_username ? `@${u.tg_username} (${u.telegram_id})` : (u.telegram_id || 'N/A');
            document.getElementById('infoTelegram').textContent  = tgDisplay;
            document.getElementById('infoReferral').textContent  = u.referral_code || 'N/A';
            document.getElementById('infoBalance').textContent   = `$${parseFloat(u.balance || 0).toFixed(2)}`;
            document.getElementById('infoRole').textContent      = u.is_superuser == 1 ? 'Administrador' : 'Usuario';
            document.getElementById('infoCreatedAt').textContent = new Date(u.created_at).toLocaleString('es-ES');
            const pref = u.preferred_currency;
            document.getElementById('infoPrefCurrency').textContent = pref
                ? pref.replace(':', ' — ')
                : 'No configurada';

            const blocked = u.support_blocked == 1;
            const infoEl  = document.getElementById('infoSupportBlocked');
            infoEl.innerHTML = blocked
                ? '<span class="badge badge-failed">Bloqueado</span>'
                : '<span class="badge badge-active">Activo</span>';
            document.getElementById('btnSupportBlockLabel').textContent = blocked ? 'Desbloquear Soporte' : 'Bloquear Soporte';
            document.getElementById('btnToggleSupportBlock').style.background = blocked ? '#27ae60' : '#e67e22';
        }
    } catch (e) { console.error('loadUserInfo:', e); }
}

async function loadVPS() {
    const tbody = document.getElementById('vpsTableBody');
    try {
        const res  = await fetch(`../../api/admin/user_vps?user_id=${userId}`);
        const data = await res.json();
        if (data.success && data.vps && data.vps.length > 0) {
            tbody.innerHTML = data.vps.map(v => `
                <tr>
                    <td>${v.id}</td>
                    <td>${v.name || 'N/A'}</td>
                    <td>${v.ip_address || 'Pendiente'}</td>
                    <td><span class="badge badge-${v.status.toLowerCase()}">${v.status}</span></td>
                    <td>${v.os_name || 'N/A'}</td>
                    <td>${new Date(v.created_at).toLocaleDateString('es-ES')}</td>
                    <td>
                        <button class="action-btn" style="background:var(--primary);color:white;"
                                onclick="window.location.href='../manage/?id=${v.id}'">
                            <i class="fas fa-cog"></i> Ver
                        </button>
                    </td>
                </tr>`).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No tiene servidores VPS</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state" style="color:#ff4757;">Error al cargar VPS</td></tr>';
    }
}

async function loadMovements() {
    const tbody = document.getElementById('movementsTableBody');
    try {
        const res  = await fetch(`../../api/admin/user_movements?user_id=${userId}&limit=5`);
        const data = await res.json();
        if (data.success && data.movements && data.movements.length > 0) {
            tbody.innerHTML = data.movements.map(m => `
                <tr>
                    <td>${m.id}</td>
                    <td><span class="badge badge-${m.type.toLowerCase()}">${m.type === 'IN' ? 'Ingreso' : 'Egreso'}</span></td>
                    <td style="color:${m.type === 'IN' ? '#2ed573' : '#ff4757'};">
                        ${m.type === 'IN' ? '+' : '-'}$${Math.abs(parseFloat(m.amount)).toFixed(2)}
                    </td>
                    <td>${m.description || 'N/A'}</td>
                    <td>${new Date(m.created_at).toLocaleString('es-ES')}</td>
                    <td>
                        <button class="action-btn" style="background:#f39c12;color:white;"
                            data-mov-id="${m.id}"
                            data-mov-amount="${Math.abs(parseFloat(m.amount))}"
                            data-mov-desc="${(m.description || '').replace(/"/g, '&quot;')}"
                            onclick="openEditMovementModal(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>`).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No tiene movimientos registrados</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="color:#ff4757;">Error al cargar movimientos</td></tr>';
    }
}

async function loadTransactions() {
    const tbody = document.getElementById('transactionsTableBody');
    try {
        const res  = await fetch(`../../api/admin/user_transactions?user_id=${userId}&limit=5`);
        const data = await res.json();
        if (data.success && data.transactions && data.transactions.length > 0) {
            tbody.innerHTML = data.transactions.map(t => {
                const statusMap = {
                    'COMPLETED': 'badge-active',
                    'PENDING':   'badge-provisioning',
                    'EXPIRED':   'badge-expired',
                    'FAILED':    'badge-failed',
                };
                const badgeClass = statusMap[t.status] || 'badge-inactive';
                const crypto = t.payment_amount
                    ? `${parseFloat(t.payment_amount).toFixed(6)} ${t.payment_currency || ''}`
                    : 'N/A';
                return `
                <tr>
                    <td>#${t.id}</td>
                    <td style="font-family:monospace; font-size:0.8em; color:var(--admin-muted);">${t.track_id || 'N/A'}</td>
                    <td style="color:#4ade80; font-weight:600;">$${parseFloat(t.amount).toFixed(2)}</td>
                    <td style="font-size:0.85em;">${crypto}</td>
                    <td><span class="badge ${badgeClass}">${t.status}</span></td>
                    <td>${new Date(t.created_at).toLocaleDateString('es-ES')}</td>
                </tr>`;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No tiene transacciones registradas</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="color:#f87171;">Error al cargar transacciones</td></tr>';
    }
}

async function loadTickets() {
    const tbody = document.getElementById('ticketsTableBody');
    try {
        const res  = await fetch(`../../api/admin/user_tickets?user_id=${userId}`);
        const data = await res.json();
        if (data.success && data.tickets && data.tickets.length > 0) {
            tbody.innerHTML = data.tickets.map(t => `
                <tr>
                    <td>${t.id}</td>
                    <td>${t.subject}</td>
                    <td>${t.category}</td>
                    <td><span class="badge badge-${t.status.toLowerCase()}">${t.status}</span></td>
                    <td>${t.priority}</td>
                    <td>${new Date(t.created_at).toLocaleDateString('es-ES')}</td>
                    <td>
                        <button class="action-btn" style="background:#3498db;color:white;"
                                onclick="window.location.href='ticket_detail?id=${t.id}'">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    </td>
                </tr>`).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No tiene tickets de soporte</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state" style="color:#ff4757;">Error al cargar tickets</td></tr>';
    }
}
</script>

<!-- Delete User Modal -->
<div class="modal-overlay" id="deleteUserModal">
    <div class="modal" style="width:440px;">
        <div class="modal-header">
            <h2 style="color:#e74c3c;"><i class="fas fa-triangle-exclamation" style="margin-right:8px;"></i>Eliminar Usuario</h2>
            <button class="btn-close" onclick="closeDeleteUserModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:#7f8c8d; font-size:0.9rem; margin-bottom:16px;">
                Esta acción eliminará al usuario de forma permanente y no se puede deshacer.
                Para confirmar, escribe su nombre de usuario.
            </p>
            <div class="delete-info-box">
                <div>
                    <div class="label">Usuario</div>
                    <div class="value" id="deleteUserNameDisplay"></div>
                </div>
                <div style="text-align:right;">
                    <div class="label">ID</div>
                    <div class="value-secondary" id="deleteUserIdDisplay"></div>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Escribe el username para confirmar</label>
                <input type="text" id="deleteUserConfirmInput" class="form-control"
                    placeholder="Username del usuario..." oninput="validateDeleteUserInput()">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDeleteUserModal()">Cancelar</button>
            <button id="confirmDeleteUserBtn" class="btn btn-danger" onclick="submitDeleteUser()" disabled>
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<!-- Balance Modal -->
<div class="modal-overlay" id="balanceModal">
    <div class="modal" style="width:500px; max-height:90vh;">
        <div class="modal-header">
            <h2>Modificar Saldo</h2>
            <button class="btn-close" onclick="closeBalanceModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:#7f8c8d; margin-bottom:20px; font-size:0.9rem;"><span id="balanceUserInfo"></span></p>
            <div class="form-group">
                <label>Tipo de Operación</label>
                <select id="balanceOperation" class="form-control">
                    <option value="add">Agregar Saldo</option>
                    <option value="subtract">Quitar Saldo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Monto (USD)</label>
                <input type="number" step="0.01" min="0.01" id="balanceAmount" class="form-control" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Descripción (Opcional)</label>
                <input type="text" id="balanceDescription" class="form-control" placeholder="Motivo del ajuste">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeBalanceModal()">Cancelar</button>
            <button id="btnConfirmBalance" class="btn btn-primary" onclick="submitBalanceChange()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal" style="width:500px; max-height:90vh;">
        <div class="modal-header">
            <h2>Editar Usuario</h2>
            <button class="btn-close" onclick="closeEditUserModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <input type="text" id="editUsername" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="editEmail" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Telegram ID</label>
                <input type="text" id="editTelegramId" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>Código de Referido</label>
                <input type="text" id="editReferralCode" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="editIsSuperuser" style="width:auto;margin-right:8px;">
                    Administrador
                </label>
            </div>
            <div class="form-group">
                <label>Moneda Preferida (Cripto)</label>
                <select id="editPrefCurrency" class="form-control">
                    <option value="">— Sin preferencia —</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeEditUserModal()">Cancelar</button>
            <button id="btnSaveUser" class="btn btn-primary" onclick="submitEditUser()">Guardar Cambios</button>
        </div>
    </div>
</div>

<!-- Edit Movement Modal -->
<div class="modal-overlay" id="editMovementModal">
    <div class="modal" style="width:460px; max-height:90vh;">
        <div class="modal-header">
            <h2>Editar Movimiento</h2>
            <button class="btn-close" onclick="closeEditMovementModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editMovId">
            <div class="form-group">
                <label>Monto (USD)</label>
                <input type="number" step="0.01" min="0.01" id="editMovAmount" class="form-control" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <input type="text" id="editMovDescription" class="form-control" placeholder="Descripción del movimiento">
            </div>
            <div id="editMovError" style="color:#e74c3c;font-size:0.85rem;display:none;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeEditMovementModal()">Cancelar</button>
            <button id="btnSaveMov" class="btn btn-primary" onclick="submitEditMovement()">Guardar</button>
        </div>
    </div>
</div>

<script>
// ── Support Block Toggle ─────────────────────────────────────────────────────
async function toggleSupportBlock() {
    const btn   = document.getElementById('btnToggleSupportBlock');
    const label = document.getElementById('btnSupportBlockLabel');
    const prev  = label.textContent;
    btn.disabled = true; label.textContent = 'Procesando...';
    try {
        const res  = await fetch('../../api/admin/users/toggle_support_block', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            loadUserInfo();
        } else {
            alert(data.message || 'Error al cambiar estado de soporte');
            label.textContent = prev;
        }
    } catch (e) {
        alert('Error de conexión');
        label.textContent = prev;
    } finally { btn.disabled = false; }
}

// ── Tabs ─────────────────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}

// ── Balance Modal ────────────────────────────────────────────────────────────
async function openBalanceModal() {
    try {
        const res  = await fetch(`../../api/admin/user_detail?id=${userId}`);
        const data = await res.json();
        if (data.success && data.user) {
            document.getElementById('balanceUserInfo').textContent =
                `Usuario: ${data.user.username} | Saldo actual: $${parseFloat(data.user.balance).toFixed(2)}`;
            document.getElementById('balanceAmount').value      = '';
            document.getElementById('balanceDescription').value = '';
            document.getElementById('balanceOperation').value   = 'add';
            document.getElementById('balanceModal').classList.add('active');
        }
    } catch (e) { alert('Error al obtener datos del usuario'); }
}

function closeBalanceModal() { document.getElementById('balanceModal').classList.remove('active'); }
document.getElementById('balanceModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeBalanceModal(); });

async function submitBalanceChange() {
    const operation   = document.getElementById('balanceOperation').value;
    const amount      = parseFloat(document.getElementById('balanceAmount').value);
    const description = document.getElementById('balanceDescription').value;
    const btn         = document.getElementById('btnConfirmBalance');

    if (!amount || amount <= 0) { alert('Ingresa un monto válido'); return; }

    btn.disabled = true; btn.textContent = 'Procesando...';
    try {
        const res = await fetch('../../api/admin/balance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                type: operation === 'add' ? 'IN' : 'OUT',
                amount,
                description: description || `Ajuste de saldo por administrador`
            })
        });
        const result = await res.json();
        if (res.ok && result.success) {
            closeBalanceModal();
            loadUserInfo();
            loadMovements();
        } else {
            alert(result.message || 'Error al modificar saldo');
        }
    } catch (e) { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.textContent = 'Confirmar'; }
}

// ── Edit User Modal ──────────────────────────────────────────────────────────
async function openEditUserModal() {
    try {
        const [userRes, currRes] = await Promise.all([
            fetch(`../../api/admin/users/get_user?id=${userId}`),
            fetch('../../api/transactions/currencies'),
        ]);
        const data    = await userRes.json();
        const currData = await currRes.json();

        if (data.status === 'success' && data.user) {
            const u      = data.user;
            const saved  = u.preferred_currency || '';

            document.getElementById('editUsername').value       = u.username || '';
            document.getElementById('editEmail').value         = u.email || '';
            document.getElementById('editTelegramId').value    = u.telegram_id || '';
            document.getElementById('editReferralCode').value  = u.referral_code || '';
            document.getElementById('editIsSuperuser').checked = u.is_superuser == 1;

            const select = document.getElementById('editPrefCurrency');
            select.innerHTML = '<option value="">— Sin preferencia —</option>';
            const raw  = currData.data || currData;
            const list = Array.isArray(raw) ? raw : Object.values(raw);
            list.forEach(item => {
                const sym  = item.symbol || '';
                const nets = item.networks
                    ? (Array.isArray(item.networks) ? item.networks : Object.values(item.networks))
                    : [];
                nets.forEach(net => {
                    const network = net.network || net;
                    const value   = `${sym}:${network}`;
                    const opt     = new Option(`${sym} (${network})`, value);
                    if (value === saved) opt.selected = true;
                    select.add(opt);
                });
            });

            document.getElementById('editUserModal').classList.add('active');
        } else {
            alert(data.message || 'Error al cargar datos');
        }
    } catch (e) { alert('Error al obtener datos del usuario'); }
}

function closeEditUserModal() { document.getElementById('editUserModal').classList.remove('active'); }
document.getElementById('editUserModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeEditUserModal(); });

async function submitEditUser() {
    const btn = document.getElementById('btnSaveUser');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const res = await fetch('../../api/admin/users/edit_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id:            userId,
                username:           document.getElementById('editUsername').value,
                email:              document.getElementById('editEmail').value,
                telegram_id:        document.getElementById('editTelegramId').value || null,
                referral_code:      document.getElementById('editReferralCode').value || null,
                is_superuser:       document.getElementById('editIsSuperuser').checked,
                preferred_currency: document.getElementById('editPrefCurrency').value || null,
            })
        });
        const result = await res.json();
        if (res.ok && result.status === 'success') {
            closeEditUserModal();
            loadUserInfo();
        } else {
            alert(result.message || 'Error al actualizar usuario');
        }
    } catch (e) { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar Cambios'; }
}

// ── Delete User ──────────────────────────────────────────────────────────────
function confirmDeleteUser() {
    const username = document.getElementById('infoUsername').textContent.trim();
    document.getElementById('deleteUserNameDisplay').textContent = username;
    document.getElementById('deleteUserIdDisplay').textContent   = `#${userId}`;
    document.getElementById('deleteUserConfirmInput').value      = '';
    document.getElementById('confirmDeleteUserBtn').disabled     = true;
    document.getElementById('deleteUserModal').classList.add('active');
    setTimeout(() => document.getElementById('deleteUserConfirmInput').focus(), 100);
}

function validateDeleteUserInput() {
    const input    = document.getElementById('deleteUserConfirmInput').value.trim();
    const expected = document.getElementById('deleteUserNameDisplay').textContent.trim();
    const btn      = document.getElementById('confirmDeleteUserBtn');
    btn.disabled   = input !== expected || expected === '';
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('deleteUserModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeDeleteUserModal();
    });
});

async function submitDeleteUser() {
    try {
        const res = await fetch('../../api/admin/users/delete_user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const result = await res.json();
        if (res.ok && result.status === 'success') {
            window.location.href = '/dashboard/admin/users';
        } else {
            alert(result.message || 'Error al eliminar usuario');
        }
    } catch (e) { alert('Error de conexión'); }
}

// ── Edit Movement Modal ──────────────────────────────────────────────────────
function openEditMovementModal(btn) {
    document.getElementById('editMovId').value          = btn.dataset.movId;
    document.getElementById('editMovAmount').value      = btn.dataset.movAmount;
    document.getElementById('editMovDescription').value = btn.dataset.movDesc;
    document.getElementById('editMovError').style.display = 'none';
    document.getElementById('editMovementModal').classList.add('active');
}

function closeEditMovementModal() { document.getElementById('editMovementModal').classList.remove('active'); }
document.getElementById('editMovementModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeEditMovementModal(); });

async function submitEditMovement() {
    const id          = document.getElementById('editMovId').value;
    const amount      = parseFloat(document.getElementById('editMovAmount').value);
    const description = document.getElementById('editMovDescription').value.trim();
    const errorDiv    = document.getElementById('editMovError');
    const btn         = document.getElementById('btnSaveMov');

    if (!amount || amount <= 0) {
        errorDiv.textContent = 'El monto debe ser mayor a 0';
        errorDiv.style.display = 'block';
        return;
    }

    errorDiv.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const res = await fetch('../../api/admin/users/modify_movement', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ movement_id: parseInt(id), amount, description })
        });
        const data = await res.json();
        if (data.success) {
            closeEditMovementModal();
            loadMovements();
        } else {
            errorDiv.textContent = data.message || 'Error al guardar';
            errorDiv.style.display = 'block';
        }
    } catch (e) {
        errorDiv.textContent = 'Error de conexión';
        errorDiv.style.display = 'block';
    } finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}
</script>

<?php include '../footer.php'; ?>
