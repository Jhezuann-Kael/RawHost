let currentPage = 1;
let searchTimer;

document.addEventListener('DOMContentLoaded', () => loadServices(1));

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadServices(1), 450);
}

async function loadServices(page) {
    currentPage = page;
    const tbody  = document.getElementById('servicesTableBody');
    const search = document.getElementById('searchInput').value.trim();

    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i></td></tr>';

    try {
        const params = new URLSearchParams({ page, limit: 15 });
        if (search) params.append('search', search);

        const res  = await fetch(`../../api/admin/managed_services?${params}`);
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Load error');

        renderTable(data.data);
        renderPagination(data.pagination);

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:#ef4444;">${e.message}</td></tr>`;
    }
}

function renderTable(rows) {
    const tbody = document.getElementById('servicesTableBody');

    if (!rows.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="fas fa-briefcase" style="font-size:1.8rem;display:block;margin-bottom:10px;opacity:.3;"></i>
                    No managed services found
                </td>
            </tr>`;
        return;
    }

    const statusBadge = s => {
        const map = {
            COMPLETED: 'badge-completed',
            PENDING:   'badge-pending',
            FAILED:    'badge-failed',
            CANCELLED: 'badge-closed',
        };
        return `<span class="badge ${map[s] || ''}">${s}</span>`;
    };

    tbody.innerHTML = rows.map(o => `
        <tr>
            <td><code style="color:var(--text-muted);">#${o.id}</code></td>
            <td>
                <div style="font-weight:600;">${escHtml(o.username || '—')}</div>
                <div style="font-size:0.78rem;color:var(--text-muted);">${escHtml(o.email || '')}</div>
            </td>
            <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(o.description || '—')}</td>
            <td style="font-weight:700;">$${parseFloat(o.total_amount).toFixed(2)}</td>
            <td style="white-space:nowrap;color:var(--text-muted);">${new Date(o.created_at).toLocaleString()}</td>
            <td>${statusBadge(o.status)}</td>
            <td>
                <button class="action-btn btn-delete" onclick="deleteService(${o.id}, '${escAttr(o.description || '')}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`).join('');
}

function renderPagination(p) {
    const el = document.getElementById('pagination');
    if (!p || p.pages <= 1) { el.innerHTML = ''; return; }

    const prev = `<button class="page-btn" ${p.current_page <= 1 ? 'disabled' : ''} onclick="loadServices(${p.current_page - 1})"><i class="fas fa-chevron-left"></i></button>`;
    const next = `<button class="page-btn" ${p.current_page >= p.pages ? 'disabled' : ''} onclick="loadServices(${p.current_page + 1})"><i class="fas fa-chevron-right"></i></button>`;

    let pages = '';
    const s = Math.max(1, p.current_page - 2);
    const e = Math.min(p.pages, p.current_page + 2);
    for (let i = s; i <= e; i++) {
        pages += `<button class="page-btn ${i === p.current_page ? 'active' : ''}" onclick="loadServices(${i})">${i}</button>`;
    }

    el.innerHTML = `<span class="orders-count" style="margin-right:10px;">${p.total} order${p.total !== 1 ? 's' : ''}</span>${prev}${pages}${next}`;
}

// ── Create Modal ───────────────────────────────────────────────────────────

let chargeNow = true;

function setChargeMode(charge) {
    chargeNow = charge;
    document.getElementById('btnCreateLabel').textContent = charge ? 'Charge & Create' : 'Create Pending Order';
    document.getElementById('optChargeNow').style.background    = charge ? 'rgba(0,243,255,0.05)' : '';
    document.getElementById('optLeavePending').style.background  = charge ? '' : 'rgba(0,243,255,0.05)';
}

function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
    document.getElementById('userSearch').value      = '';
    document.getElementById('selectedUserId').value  = '';
    document.getElementById('svcDescription').value  = '';
    document.getElementById('svcAmount').value       = '';
    document.getElementById('userDropdown').style.display = 'none';
    document.getElementById('selectedUserInfo').style.display = 'none';
    document.getElementById('createError').style.display = 'none';
    document.querySelector('input[name="chargeMode"][value="1"]').checked = true;
    setChargeMode(true);
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
}

let userSearchTimer;
async function searchUsers(q) {
    clearTimeout(userSearchTimer);
    document.getElementById('selectedUserId').value = '';
    document.getElementById('selectedUserInfo').style.display = 'none';

    if (q.length < 2) { document.getElementById('userDropdown').style.display = 'none'; return; }

    userSearchTimer = setTimeout(async () => {
        try {
            const res  = await fetch(`../../api/admin/users/list?search=${encodeURIComponent(q)}&limit=8`);
            const data = await res.json();
            renderUserDropdown(data.users || []);
        } catch (e) { /* ignore */ }
    }, 300);
}

function renderUserDropdown(users) {
    const dd = document.getElementById('userDropdown');
    if (!users.length) { dd.style.display = 'none'; return; }

    dd.innerHTML = users.map(u => `
        <div onclick="selectUser(${u.id}, '${escAttr(u.username)}', '${escAttr(u.email)}', ${parseFloat(u.balance || 0).toFixed(2)})"
             style="padding:10px 14px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;"
             onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background=''">
            <div>
                <div style="font-weight:600;">${escHtml(u.username)}</div>
                <div style="font-size:0.78rem;color:var(--text-muted);">${escHtml(u.email)}</div>
            </div>
            <div style="color:var(--primary);font-weight:700;">$${parseFloat(u.balance || 0).toFixed(2)}</div>
        </div>`).join('');

    dd.style.display = 'block';
}

function selectUser(id, username, email, balance) {
    document.getElementById('selectedUserId').value           = id;
    document.getElementById('userSearch').value               = username;
    document.getElementById('userDropdown').style.display     = 'none';
    document.getElementById('selectedUserLabel').textContent  = `${username} — ${email}`;
    document.getElementById('selectedUserBalance').textContent = `Balance: $${parseFloat(balance).toFixed(2)}`;
    document.getElementById('selectedUserInfo').style.display = 'block';
}

document.addEventListener('click', e => {
    if (!e.target.closest('#userSearch') && !e.target.closest('#userDropdown')) {
        document.getElementById('userDropdown').style.display = 'none';
    }
});

async function submitCreate() {
    const userId      = document.getElementById('selectedUserId').value;
    const description = document.getElementById('svcDescription').value.trim();
    const amount      = parseFloat(document.getElementById('svcAmount').value);
    const errDiv      = document.getElementById('createError');
    const btn         = document.getElementById('btnCreate');

    errDiv.style.display = 'none';

    if (!userId) { showErr('Please select a user.'); return; }
    if (!description) { showErr('Description is required.'); return; }
    if (!amount || amount <= 0) { showErr('Enter a valid amount.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

    try {
        const res  = await fetch('../../api/admin/managed_services', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: parseInt(userId), description, amount, charge_now: chargeNow }),
        });
        const data = await res.json();

        if (data.success) {
            closeCreateModal();
            loadServices(currentPage);
        } else {
            showErr(data.message || 'Unknown error');
        }
    } catch (e) {
        showErr('Connection error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-check"></i> <span id="btnCreateLabel">${chargeNow ? 'Charge & Create' : 'Create Pending Order'}</span>`;
    }
}

function showErr(msg) {
    const el = document.getElementById('createError');
    el.textContent = msg;
    el.style.display = 'block';
}

async function deleteService(id, description) {
    if (!confirm(`Delete managed service #${id}?\n"${description}"\n\nThis will also remove all associated transactions. This cannot be undone.`)) return;

    try {
        const res  = await fetch(`../../api/admin/managed_services?id=${id}`, { method: 'DELETE' });
        const data = await res.json();

        if (data.success) {
            loadServices(currentPage);
        } else {
            alert(data.message || 'Delete failed');
        }
    } catch (e) {
        alert('Connection error');
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s).replace(/'/g,"\\'");
}
