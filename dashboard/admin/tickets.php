<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Tickets';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i
                        class="fas fa-bars"></i></button>
                <h1>Gestión de Tickets</h1>
            </div>
            <p>Atención al cliente y soporte técnico</p>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por ID, Asunto, Usuario..."
                    onkeyup="debounceSearch()">
            </div>
            <select id="statusFilter" class="page-btn" style="min-width:150px;" onchange="loadTickets(1)">
                <option value="">Todos los Estados</option>
                <option value="OPEN">Abiertos</option>
                <option value="ANSWERED">Respondidos</option>
                <option value="CLOSED">Cerrados</option>
            </select>
            <button id="btnDeleteAll" class="page-btn btn-delete" style="display:none; white-space:nowrap;" onclick="deleteAllVisible()">
                <i class="fas fa-trash"></i> Eliminar visibles (<span id="deleteAllCount">0</span>)
            </button>
        </div>
    </header>

    <!-- Satisfaction widget -->
    <div id="satisfactionWidget" style="display:none; margin-bottom:16px; background:var(--bg-card); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:14px 20px; display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
        <div>
            <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);margin-bottom:6px;">Satisfacción promedio</div>
            <div id="satisfactionFaces" style="display:flex;gap:6px;align-items:center;"></div>
        </div>
        <div id="satisfactionScore" style="font-size:2rem;font-weight:700;color:#fff;line-height:1;"></div>
        <div id="satisfactionBars" style="flex:1;min-width:160px;display:flex;flex-direction:column;gap:5px;"></div>
        <div style="font-size:0.75rem;color:var(--text-muted);" id="satisfactionTotal"></div>
    </div>

    <!-- Tickets Table (desktop) -->
    <div class="admin-table-container tickets-desktop">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Asunto</th>
                    <th>Usuario</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th title="Valoración del usuario al cerrar">Val.</th>
                    <th>Última Act.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="ticketsTableBody">
                <tr>
                    <td colspan="8" style="text-align:center; padding: 20px;">Cargando tickets...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tickets Cards (mobile) -->
    <div id="ticketsCardBody" class="tickets-mobile" style="display:none;"></div>

    <!-- Pagination -->
    <div id="pagination" class="pagination"></div>

</main>

<style>
    @media (max-width: 768px) {
        .tickets-desktop { display: none !important; }
        .tickets-mobile  { display: block !important; }

        .ticket-card {
            background: var(--admin-card, #fff);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,.06);
        }

        .ticket-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .ticket-card-id {
            font-size: 0.75rem;
            color: #94a3b8;
            font-family: 'JetBrains Mono', monospace;
        }

        .ticket-card-subject {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }

        .ticket-card-meta {
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 10px;
        }

        .ticket-card-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .ticket-card-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 4px;
        }

        .ticket-card-actions .action-btn {
            flex: 1;
            justify-content: center;
            padding: 8px;
            font-size: 0.85rem;
        }
    }

    @media (min-width: 769px) {
        .tickets-mobile { display: none !important; }
    }
</style>

<script>
    let currentPage = 1;
    let searchTimer;

    document.addEventListener('DOMContentLoaded', () => {
        loadTickets(1);
    });

    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadTickets(1);
        }, 500);
    }

    async function loadTickets(page) {
        currentPage = page;
        const tbody = document.getElementById('ticketsTableBody');
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;

        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...</td></tr>';

        try {
            const params = new URLSearchParams({
                page: page,
                limit: 10
            });
            if (search) params.append('search', search);
            if (status) params.append('status', status);

            const res = await fetch(`../../api/admin/tickets?${params.toString()}`);
            const response = await res.json();

            if (response.success) {
                renderSatisfaction(response.rating_stats);
                renderTable(response.data);
                renderPagination(response.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px; color:red;">${response.message || 'Error al cargar'}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px; color:red;">Error de conexión</td></tr>';
        }
    }

    const FACES = [
        { icon: 'fa-sad-cry',  color: '#ef4444', min: 0    },
        { icon: 'fa-frown',    color: '#f97316', min: 1.5  },
        { icon: 'fa-meh',      color: '#f59e0b', min: 2.5  },
        { icon: 'fa-smile',    color: '#84cc16', min: 3.5  },
        { icon: 'fa-laugh',    color: '#22c55e', min: 4.5  },
    ];

    function activeFaceIndex(score) {
        for (let i = FACES.length - 1; i >= 0; i--) {
            if (score >= FACES[i].min) return i;
        }
        return 0;
    }

    function renderSatisfaction(stats) {
        const widget = document.getElementById('satisfactionWidget');
        if (!stats || stats.total_rated === 0) { widget.style.display = 'none'; return; }

        widget.style.display = 'flex';

        const score = stats.avg_score;
        const active = activeFaceIndex(score);

        // Faces row
        document.getElementById('satisfactionFaces').innerHTML = FACES.map((f, i) => {
            const isActive = i === active;
            return `<i class="fas ${f.icon}" style="font-size:${isActive ? '1.9rem' : '1.1rem'};color:${isActive ? f.color : 'rgba(255,255,255,0.15)'};transition:all .2s;" title="${score.toFixed(1)}"></i>`;
        }).join('');

        // Numeric score
        document.getElementById('satisfactionScore').textContent = score.toFixed(1);
        document.getElementById('satisfactionScore').style.color = FACES[active].color;

        // Distribution bars
        const rows = [
            { label: 'Muy bueno', key: 'VERY_GOOD', color: '#22c55e' },
            { label: 'Bueno',     key: 'GOOD',      color: '#f59e0b' },
            { label: 'No bueno',  key: 'NOT_GOOD',  color: '#ef4444' },
        ];
        document.getElementById('satisfactionBars').innerHTML = rows.map(r => {
            const cnt = stats[r.key] || 0;
            const pct = stats.total_rated > 0 ? Math.round(cnt / stats.total_rated * 100) : 0;
            return `
                <div style="display:flex;align-items:center;gap:8px;font-size:0.73rem;">
                    <span style="width:68px;color:var(--text-muted);text-align:right;">${r.label}</span>
                    <div style="flex:1;height:5px;background:rgba(255,255,255,0.07);border-radius:3px;overflow:hidden;">
                        <div style="width:${pct}%;height:100%;background:${r.color};border-radius:3px;"></div>
                    </div>
                    <span style="width:28px;color:var(--text-muted);">${cnt}</span>
                </div>`;
        }).join('');

        document.getElementById('satisfactionTotal').textContent = `${stats.total_rated} valoraciones`;
    }

    const RATING_MAP = {
        VERY_GOOD: { icon: 'fa-smile',   color: '#22c55e', label: 'Muy bueno' },
        GOOD:      { icon: 'fa-meh',     color: '#f59e0b', label: 'Bueno' },
        NOT_GOOD:  { icon: 'fa-frown',   color: '#ef4444', label: 'No bueno' },
    };

    function ratingBadge(rating) {
        if (!rating) return '<span style="color:rgba(255,255,255,0.2);">—</span>';
        const r = RATING_MAP[rating];
        if (!r) return '<span style="color:rgba(255,255,255,0.2);">—</span>';
        return `<i class="fas ${r.icon}" style="font-size:1.1rem;color:${r.color};" title="${r.label}"></i>`;
    }

    const PRIO_LABEL = {
        HIGH:   'Bloqueo total',
        MEDIUM: 'Problema menor',
        LOW:    'Consulta'
    };

    function getBadgeClasses(ticket) {
        let statusBadge = 'badge-closed';
        if (ticket.status === 'OPEN') statusBadge = 'badge-open';
        else if (ticket.status === 'ANSWERED') statusBadge = 'badge-answered';

        let priorityBadge = 'badge-medium';
        if (ticket.priority === 'HIGH') priorityBadge = 'badge-high';
        else if (ticket.priority === 'LOW') priorityBadge = 'badge-low';

        return { statusBadge, priorityBadge };
    }

    function renderTable(tickets) {
        const tbody = document.getElementById('ticketsTableBody');
        const cardBody = document.getElementById('ticketsCardBody');

        // Update bulk-delete button
        visibleTicketIds = (tickets || []).map(t => t.id);
        const btnDel = document.getElementById('btnDeleteAll');
        const search = document.getElementById('searchInput').value.trim();
        if (visibleTicketIds.length > 0 && search) {
            document.getElementById('deleteAllCount').textContent = visibleTicketIds.length;
            btnDel.style.display = '';
        } else {
            btnDel.style.display = 'none';
        }

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 20px;">No se encontraron tickets.</td></tr>';
            cardBody.innerHTML = '<p style="text-align:center; padding:20px; color:#94a3b8;">No se encontraron tickets.</p>';
            return;
        }

        // Desktop table rows
        tbody.innerHTML = tickets.map(ticket => {
            const { statusBadge, priorityBadge } = getBadgeClasses(ticket);
            const safeSubject = ticket.subject.replace(/'/g, "\\'");
            const ratingHtml = ratingBadge(ticket.rating);
            return `
                <tr>
                    <td>#${ticket.id}</td>
                    <td>
                        <span style="font-weight:600;">${ticket.subject}</span>
                        <div style="font-size:0.8em; color:#7f8c8d;">${ticket.category || 'General'}</div>
                    </td>
                    <td>
                        <span style="font-weight:500;">${ticket.username || 'Usuario'}</span>
                        <div style="font-size:0.8em; color:#7f8c8d;">${ticket.email || ''}</div>
                    </td>
                    <td><span class="badge ${priorityBadge}">${PRIO_LABEL[ticket.priority] || ticket.priority}</span></td>
                    <td><span class="badge ${statusBadge}">${ticket.status}</span></td>
                    <td>${ratingHtml}</td>
                    <td>${new Date(ticket.updated_at).toLocaleString()}</td>
                    <td style="display:flex; gap:6px;">
                        <button class="action-btn" style="background:var(--primary); color:white;" onclick="viewTicket(${ticket.id})" title="Ver / Responder">
                            <i class="fas fa-reply"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteTicket(${ticket.id}, '${safeSubject}')" title="Eliminar ticket">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Mobile cards
        cardBody.innerHTML = tickets.map(ticket => {
            const { statusBadge, priorityBadge } = getBadgeClasses(ticket);
            const safeSubject = ticket.subject.replace(/'/g, "\\'");
            return `
                <div class="ticket-card">
                    <div class="ticket-card-top">
                        <div>
                            <div class="ticket-card-id">#${ticket.id}</div>
                            <div class="ticket-card-subject">${ticket.subject}</div>
                        </div>
                    </div>
                    <div class="ticket-card-meta">
                        ${ticket.username || 'Usuario'}${ticket.email ? ' · ' + ticket.email : ''}<br>
                        ${ticket.category || 'General'} · ${new Date(ticket.updated_at).toLocaleString([], {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'})}
                    </div>
                    <div class="ticket-card-badges">
                        <span class="badge ${statusBadge}">${ticket.status}</span>
                        <span class="badge ${priorityBadge}">${PRIO_LABEL[ticket.priority] || ticket.priority}</span>
                        ${ticket.rating ? ratingBadge(ticket.rating) : ''}
                    </div>
                    <div class="ticket-card-actions">
                        <button class="action-btn" style="background:var(--primary); color:white; display:flex;" onclick="viewTicket(${ticket.id})">
                            <i class="fas fa-reply"></i>&nbsp;Responder
                        </button>
                        <button class="action-btn btn-delete" style="display:flex;" onclick="deleteTicket(${ticket.id}, '${safeSubject}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total <= pagination.limit) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        const current = parseInt(pagination.current_page);
        const total = parseInt(pagination.pages);

        // Prev
        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="loadTickets(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;

        // Pages
        let start = Math.max(1, current - 2);
        let end = Math.min(total, current + 2);

        for (let i = start; i <= end; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadTickets(${i})">${i}</button>`;
        }

        // Next
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="loadTickets(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;

        container.innerHTML = html;
    }

    // Track IDs currently visible for bulk delete
    let visibleTicketIds = [];

    function viewTicket(id) {
        window.location.href = `ticket_detail?id=${id}`;
    }

    async function deleteAllVisible() {
        const search = document.getElementById('searchInput').value.trim();
        const ids = [...visibleTicketIds];
        if (!ids.length) return;
        if (!confirm(`¿Eliminar los ${ids.length} tickets${search ? ' de "' + search + '"' : ''}? Esta acción no se puede deshacer.`)) return;

        const btnDel = document.getElementById('btnDeleteAll');
        btnDel.disabled = true;
        btnDel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

        let errors = 0;
        for (const id of ids) {
            try {
                const res = await fetch('../../api/admin/delete_ticket', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ticket_id: id })
                });
                const data = await res.json();
                if (data.status !== 'success') errors++;
            } catch { errors++; }
        }

        btnDel.disabled = false;
        btnDel.innerHTML = '<i class="fas fa-trash"></i> Eliminar visibles (<span id="deleteAllCount">0</span>)';
        if (errors) alert(`${errors} ticket(s) no se pudieron eliminar.`);
        loadTickets(1);
    }

    async function deleteTicket(id, subject) {
        if (!confirm(`¿Eliminar el ticket #${id} "${subject}" y todos sus mensajes? Esta acción no se puede deshacer.`)) return;

        try {
            const res = await fetch('../../api/admin/delete_ticket', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: id })
            });
            const data = await res.json();

            if (data.status === 'success') {
                loadTickets(currentPage);
            } else {
                alert(data.message || 'Error al eliminar el ticket.');
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }

</script>

<?php include '../footer.php'; ?>