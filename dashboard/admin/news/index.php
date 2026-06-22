<?php
require_once __DIR__ . '/../../../api/config.php';

$pageTitle = SITE_NAME . ' - Gestión de Noticias';
include __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i
                        class="fas fa-bars"></i></button>
                <h1>Gestión de Noticias</h1>
            </div>
            <p>Publica actualizaciones y novedades con formato enriquecido</p>
        </div>
        <div class="table-controls">
            <button class="action-btn" style="background:var(--primary); color:white; padding: 10px 20px; height: auto;"
                onclick="openNewsModal()">
                <i class="fas fa-plus"></i> Nueva Noticia
            </button>
        </div>
    </header>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Vistas</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="newsTableBody">
                <tr>
                    <td colspan="7" style="text-align:center; padding: 20px;">Cargando noticias...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="pagination" class="pagination"></div>
</main>

<?php include __DIR__ . '/editor_modal.php'; ?>

<script>
    let currentPage = 1;

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('newsTitle').addEventListener('input', updatePreviewText);
        document.getElementById('newsCategory').addEventListener('change', updatePreviewText);

        loadNews(1);

        document.getElementById('newsForm').addEventListener('submit', function (e) {
            e.preventDefault();
            saveNews();
        });
    });

    async function loadNews(page) {
        currentPage = page;
        const tbody = document.getElementById('newsTableBody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> Cargando...ticias...</td></tr>';

        try {
            const res = await fetch(`../../../api/admin/news?page=${page}&limit=10`);
            const response = await res.json();

            if (response.success) {
                renderTable(response.data);
                renderPagination(response.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 20px; color:red;">${response.error || 'Error'}</td></tr>`;
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px; color:red;">Error de conexión</td></tr>';
        }
    }

    function renderTable(newsList) {
        const tbody = document.getElementById('newsTableBody');
        if (!newsList || newsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;">No hay noticias.</td></tr>';
            return;
        }

        tbody.innerHTML = newsList.map(item => `
            <tr>
                <td>#${item.id}</td>
                <td style="font-weight:600;">${item.title}</td>
                <td><span style="font-size:0.8rem; background:rgba(255,255,255,0.05); padding:4px 8px; border-radius:4px;">${item.category}</span></td>
                <td>
                    <span class="badge ${item.is_active == 1 ? 'badge-running' : 'badge-stopped'}">
                        ${item.is_active == 1 ? 'Activa' : 'Inactiva'}
                    </span>
                </td>
                <td style="text-align:center;"><i class="fas fa-eye" style="font-size:0.8rem; opacity:0.5; margin-right:5px;"></i> ${item.views || 0}</td>
                <td>${new Date(item.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="action-btn" style="background:#3498db; color:white;" onclick='editNews(${JSON.stringify(item).replace(/'/g, "&#39;")})'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn btn-delete" onclick="deleteNews(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function saveNews() {
        const id = document.getElementById('newsId').value;
        const data = {
            title: document.getElementById('newsTitle').value,
            category: document.getElementById('newsCategory').value,
            content: quill.root.innerHTML, // Obtener HTML de Quill
            is_active: document.getElementById('newsActive').checked ? 1 : 0
        };

        const method = id ? 'PUT' : 'POST';
        const url = id ? `../../../api/admin/news?id=${id}` : '../../../api/admin/news';

        try {
            const res = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const response = await res.json();

            if (response.success) {
                closeNewsModal();
                loadNews(id ? currentPage : 1);
            } else {
                alert(response.error);
            }
        } catch (e) {
            alert('Error al procesar la solicitud');
        }
    }

    async function deleteNews(id) {
        if (!confirm('¿Eliminar esta noticia permanentemente?')) return;
        try {
            const res = await fetch(`../../../api/admin/news?id=${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) loadNews(currentPage);
        } catch (e) {
            alert('Error al eliminar');
        }
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
        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="loadNews(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - 1 && i <= current + 1)) {
                html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadNews(${i})">${i}</button>`;
            }
        }
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="loadNews(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;
        container.innerHTML = html;
    }
</script>

<?php include __DIR__ . '/../../footer.php'; ?>