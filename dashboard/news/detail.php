<?php
require_once __DIR__ . '/../../api/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/lang_loader.php';
$newsId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pageTitle = SITE_NAME;
include __DIR__ . '/../header.php';
?>

<style>
    .detail-container {
        max-width: 900px;
        margin: 20px auto;
        background: var(--bg-card);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    }

    .detail-header {
        padding: 50px 50px 30px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        background: linear-gradient(to bottom, rgba(99, 102, 241, 0.05), transparent);
    }

    .detail-category {
        font-size: 0.8rem;
        background: var(--primary);
        color: white;
        padding: 4px 15px;
        border-radius: 20px;
        text-transform: uppercase;
        font-weight: 800;
        display: inline-block;
        margin-bottom: 20px;
        letter-spacing: 1px;
    }

    .detail-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        line-height: 1.2;
        margin-bottom: 20px;
    }

    .detail-meta {
        display: flex;
        gap: 20px;
        color: var(--text-muted);
        font-size: 0.9rem;
        align-items: center;
    }

    .detail-content {
        padding: 50px;
        color: #e2e8f0;
        line-height: 1.8;
        font-size: 1.1rem;
    }

    .detail-content h1,
    .detail-content h2,
    .detail-content h3 {
        color: white;
        margin: 30px 0 15px;
    }

    .detail-content p {
        margin-bottom: 20px;
    }

    .detail-content img {
        max-width: 100%;
        height: auto;
        border-radius: 12px;
        margin: 20px 0;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .detail-content blockquote {
        border-left: 4px solid var(--primary);
        background: rgba(99, 102, 241, 0.05);
        padding: 20px 30px;
        margin: 30px 0;
        border-radius: 0 12px 12px 0;
        font-style: italic;
    }

    .detail-content ul,
    .detail-content ol {
        padding-left: 25px;
        margin-bottom: 20px;
    }

    .back-nav {
        max-width: 900px;
        margin: 20px auto;
        padding: 0 10px;
    }

    .back-link {
        color: var(--text-muted);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: color 0.2s;
        font-weight: 600;
    }

    .back-link:hover {
        color: var(--primary);
    }

    /* Styles for Quill formatting parity */
    .ql-align-center {
        text-align: center;
    }

    .ql-align-right {
        text-align: right;
    }

    .ql-align-justify {
        text-align: justify;
    }

    @media (max-width: 768px) {

        .detail-header,
        .detail-content {
            padding: 30px 20px;
        }

        .detail-title {
            font-size: 1.8rem;
        }
    }
</style>

<main class="main-content">
    <div class="back-nav">
        <a href="index" class="back-link">
            <i class="fas fa-arrow-left"></i> <?php echo $lang['news_back']; ?>
        </a>
    </div>

    <div id="newsDetail" class="detail-container">
        <div style="padding: 100px; text-align: center;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
        </div>
    </div>
</main>

<script>
    const newsId = <?php echo $newsId; ?>;
    const LANG_DETAIL = {
        published: '<?php echo $lang['news_published']; ?>',
        notFound: '<?php echo $lang['news_err_not_found']; ?>',
        notFoundDesc: '<?php echo $lang['news_err_not_found_desc']; ?>',
        viewOthers: '<?php echo $lang['news_view_others']; ?>',
        error: '<?php echo $lang['dash_err_loading']; ?>',
        dateFormat: '<?php echo $lang['txt_date_format']; ?>'
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (!newsId) {
            window.location.href = 'index';
            return;
        }
        loadDetail();
    });

    async function loadDetail() {
        const container = document.getElementById('newsDetail');
        try {
            const res = await fetch(`../../api/news/detail?id=${newsId}`);
            const data = await res.json();

            if (data.success) {
                renderDetail(data.data);
                document.title = data.data.title + ' - ' + '<?php echo SITE_NAME; ?>';
            } else {
                container.innerHTML = `
                    <div style="padding: 100px; text-align: center;">
                        <i class="fas fa-exclamation-triangle fa-3x" style="color: #ef4444; margin-bottom: 20px;"></i>
                        <h2>${LANG_DETAIL.notFound}</h2>
                        <p style="color: var(--text-muted); margin-top: 10px;">${LANG_DETAIL.notFoundDesc}</p>
                        <a href="index" class="btn" style="margin-top: 30px; display: inline-block; background: var(--primary); color: white; padding: 10px 25px; border-radius: 8px; text-decoration: none;">${LANG_DETAIL.viewOthers}</a>
                    </div>
                `;
            }
        } catch (e) {
            container.innerHTML = `<p style="text-align:center; padding: 50px;">${LANG_DETAIL.error}</p>`;
        }
    }

    function renderDetail(news) {
        const container = document.getElementById('newsDetail');
        const date = new Date(news.created_at).toLocaleDateString(LANG_DETAIL.dateFormat, {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        container.innerHTML = `
            <div class="detail-header">
                <span class="detail-category">${news.category}</span>
                <h1 class="detail-title">${news.title}</h1>
                <div class="detail-meta">
                    <span><i class="far fa-calendar-alt"></i> ${date}</span>
                    <span><i class="far fa-clock"></i> ${LANG_DETAIL.published}</span>
                </div>
            </div>
            <div class="detail-content">
                ${news.content}
            </div>
        `;
    }
</script>

<?php include __DIR__ . '/../footer.php'; ?>