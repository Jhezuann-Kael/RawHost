<?php
require_once __DIR__ . '/../../api/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/lang_loader.php';
$pageTitle = SITE_NAME . ' - ' . $lang['news_title'];
include __DIR__ . '/../header.php';
?>

<style>
    /* ... (Estilos permanecen igual) ... */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }

    .news-card {
        background: var(--bg-card);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        cursor: pointer;
    }

    .news-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .news-card-body {
        padding: 25px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .news-category {
        font-size: 0.75rem;
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        font-weight: 700;
        display: inline-block;
        margin-bottom: 12px;
        width: fit-content;
    }

    .news-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: white;
        line-height: 1.4;
    }

    .news-excerpt {
        color: var(--text-muted);
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    .news-date {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .read-more {
        color: var(--primary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: gap 0.2s;
    }

    .news-card:hover .read-more {
        gap: 10px;
    }

    .empty-state {
        text-align: center;
        padding: 100px 20px;
        background: var(--bg-card);
        border-radius: 16px;
        border: 1px dashed rgba(255, 255, 255, 0.1);
    }
</style>

<main class="main-content">
    <header class="header">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1><?php echo $lang['news_title']; ?></h1>
            </div>
        </div>
        <p class="section-title"><?php echo $lang['news_subtitle']; ?></p>
    </header>

    <div id="newsList" class="news-grid">
        <!-- News items will be loaded here -->
        <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
            <p style="margin-top: 15px;"><?php echo $lang['news_loading']; ?></p>
        </div>
    </div>

    <div id="pagination" class="pagination" style="display:none; margin-top: 40px; justify-content: center;"></div>
</main>

<script>
    const LANG_NEWS = {
        readMore: '<?php echo $lang['news_read_more']; ?>',
        noItems: '<?php echo $lang['news_no_items']; ?>',
        checkBack: '<?php echo $lang['news_check_back']; ?>',
        error: '<?php echo $lang['dash_err_loading']; ?>',
        dateFormat: '<?php echo $lang['txt_date_format']; ?>'
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadNews(1);
    });

    async function loadNews(page) {
        const grid = document.getElementById('newsList');
        try {
            const res = await fetch(`../../api/news/list?page=${page}&limit=6`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                renderNews(data.data);
                renderPagination(data.pagination);
            } else {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <i class="fas fa-newspaper fa-3x" style="opacity: 0.2; margin-bottom: 20px;"></i>
                        <h3>${LANG_NEWS.noItems}</h3>
                        <p style="color: var(--text-muted);">${LANG_NEWS.checkBack}</p>
                    </div>
                `;
            }
        } catch (e) {
            grid.innerHTML = `<p style="text-align:center; grid-column:1/-1;">${LANG_NEWS.error}</p>`;
        }
    }

    function renderNews(news) {
        const grid = document.getElementById('newsList');
        grid.innerHTML = news.map(item => {
            const date = new Date(item.created_at).toLocaleDateString(LANG_NEWS.dateFormat, {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            // Strip HTML tags for the excerpt
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = item.content;
            const excerpt = tempDiv.textContent || tempDiv.innerText || "";

            return `
                <div class="news-card" onclick="window.location.href='detail?id=${item.id}'">
                    <div class="news-card-body">
                        <span class="news-category">${item.category}</span>
                        <h3 class="news-title">${item.title}</h3>
                        <p class="news-excerpt">${excerpt}</p>
                        <div class="news-footer">
                            <div class="news-date">
                                <i class="far fa-calendar-alt"></i> ${date}
                            </div>
                            <span class="read-more">${LANG_NEWS.readMore} <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (pagination.total_pages <= 1) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }
        container.style.display = 'flex';

        let html = '';
        const current = parseInt(pagination.current_page);
        const total = parseInt(pagination.total_pages);

        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="loadNews(${current - 1})"><i class="fas fa-chevron-left"></i></button>`;

        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
                html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadNews(${i})">${i}</button>`;
            } else if (i === current - 3 || i === current + 3) {
                html += `<span style="padding: 10px;">...</span>`;
            }
        }

        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="loadNews(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;

        container.innerHTML = html;
    }
</script>

<?php include __DIR__ . '/../footer.php'; ?>