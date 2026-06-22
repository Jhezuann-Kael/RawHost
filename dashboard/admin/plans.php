<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Planes';
include 'includes/header.php';
?>
<?php include 'includes/plans_styles.php'; ?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display:flex; align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Planes</h1>
            </div>
            <p>Gestión de planes, precios y fees</p>
        </div>
    </header>

    <div class="plans-grid">
        <!-- Lista de planes -->
        <div class="plan-list">
            <div class="plan-list-header">Planes disponibles</div>
            <div id="planListBody">
                <div style="padding:20px; text-align:center; color:var(--admin-muted);">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
        </div>

        <!-- Detalle del plan seleccionado -->
        <div class="detail-panel" id="detailPanel">
            <div class="detail-panel-empty">
                <i class="fas fa-layer-group"></i>
                <span>Selecciona un plan para ver su detalle</span>
            </div>
        </div>
    </div>
</main>

<div class="toast" id="toast"></div>

<script src="js/plans.js?v=<?php echo filemtime(__DIR__ . '/js/plans.js'); ?>"></script>

<?php include '../footer.php'; ?>
