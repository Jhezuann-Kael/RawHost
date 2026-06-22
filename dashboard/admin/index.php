<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Dashboard';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1>Panel de Administración</h1>
            </div>
            <p>Gestión global del sistema</p>
        </div>
    </header>

    <!-- Dashboard Stats Section -->
    <div id="dashboard-section">
        <div class="stats-grid">
            <div class="stat-card" onclick="window.location.href='transactions'">
                <div class="stat-info">
                    <h3 id="stat-pending-tx">...</h3>
                    <p>Transacciones Pendientes</p>
                </div>
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-card" onclick="window.location.href='tickets'">
                <div class="stat-info">
                    <h3 id="stat-pending-tickets">...</h3>
                    <p>Tickets Pendientes</p>
                </div>
                <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>
            <div class="stat-card" onclick="window.location.href='users'">
                <div class="stat-info">
                    <h3 id="stat-active-clients">...</h3>
                    <p>Clientes Activos</p>
                </div>
                <div class="stat-icon" style="background: rgba(46, 213, 115, 0.1); color: #2ecc71;">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="billing-section">
            <div class="billing-header">
                <h2>Resumen de Facturación</h2>
            </div>
            <div class="billing-grid">
                <div class="billing-item">
                    <h4>Hoy</h4>
                    <div class="amount" id="bill-today">$0.00</div>
                </div>
                <div class="billing-item">
                    <h4>Este Mes</h4>
                    <div class="amount" id="bill-month">$0.00</div>
                </div>
                <div class="billing-item">
                    <h4>Este Año</h4>
                    <div class="amount" id="bill-year">$0.00</div>
                </div>
                <div class="billing-item highlight">
                    <h4>Total Histórico</h4>
                    <div class="amount" id="bill-total">$0.00</div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', loadDashboardStats);

    async function loadDashboardStats() {
        try {
            const res = await fetch('../../api/admin/dashboard_stats');
            const data = await res.json();

            if (data.success) {
                document.getElementById('stat-pending-tx').textContent = data.stats.pending_transactions || '0';
                document.getElementById('stat-pending-tickets').textContent = data.stats.pending_tickets || '0';
                document.getElementById('stat-active-clients').textContent = data.stats.active_clients || '0';

                if (data.billing) {
                    document.getElementById('bill-today').textContent = '$' + parseFloat(data.billing.today || 0).toFixed(2);
                    document.getElementById('bill-month').textContent = '$' + parseFloat(data.billing.month || 0).toFixed(2);
                    document.getElementById('bill-year').textContent = '$' + parseFloat(data.billing.year || 0).toFixed(2);
                    document.getElementById('bill-total').textContent = '$' + parseFloat(data.billing.total || 0).toFixed(2);
                }
            }
        } catch (e) {
            console.error('Error loading dashboard stats:', e);
        }
    }
</script>

<?php include '../footer.php'; ?>