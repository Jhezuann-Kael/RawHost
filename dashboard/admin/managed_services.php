<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Managed Services';
include 'includes/header.php';
?>

<main class="main-content">
    <header class="top-bar">
        <div class="welcome-text">
            <div style="display:flex; align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
                <div>
                    <h1>Managed Services</h1>
                    <p>Bill professional services directly to user balances</p>
                </div>
            </div>
        </div>
        <div class="table-controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by user, email or description…" onkeyup="debounceSearch()">
            </div>
            <button class="btn btn-manage" onclick="openCreateModal()" style="white-space:nowrap;">
                <i class="fas fa-plus-circle"></i> New Service
            </button>
        </div>
    </header>

    <div class="admin-table-container" style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="servicesTableBody">
                <tr><td colspan="7" style="text-align:center; padding:24px;"><i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i></td></tr>
            </tbody>
        </table>
    </div>

    <div id="pagination" class="pagination"></div>
</main>

<!-- Create Service Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h2><i class="fas fa-briefcase" style="margin-right:10px; color:var(--primary);"></i>New Managed Service</h2>
            <button class="btn-close" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <!-- User search -->
            <div class="form-group">
                <label>User</label>
                <div style="position:relative;">
                    <input type="text" id="userSearch" class="form-control" placeholder="Search username or email…" autocomplete="off" oninput="searchUsers(this.value)">
                    <div id="userDropdown" style="display:none; position:absolute; z-index:999; width:100%; background:var(--bg-card); border:1px solid rgba(255,255,255,0.1); border-radius:8px; margin-top:4px; max-height:200px; overflow-y:auto;"></div>
                </div>
                <input type="hidden" id="selectedUserId">
                <div id="selectedUserInfo" style="display:none; margin-top:8px; background:rgba(0,243,255,0.05); border:1px solid rgba(0,243,255,0.15); border-radius:8px; padding:10px 14px; font-size:0.88rem;">
                    <span id="selectedUserLabel"></span>
                    <span style="float:right; color:var(--primary); font-weight:700;" id="selectedUserBalance"></span>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text" id="svcDescription" class="form-control" placeholder="e.g. Nginx + SSL installation" maxlength="255">
            </div>

            <div class="form-group">
                <label>Amount (USD)</label>
                <input type="number" id="svcAmount" class="form-control" placeholder="0.00" min="0.01" step="0.01">
            </div>

            <!-- Payment mode toggle -->
            <div style="margin-top:16px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; overflow:hidden;">
                <label id="optChargeNow" onclick="setChargeMode(true)"
                    style="display:flex; align-items:center; gap:12px; padding:12px 16px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,0.06); transition:background .2s;">
                    <input type="radio" name="chargeMode" value="1" checked style="accent-color:var(--primary);">
                    <div>
                        <div style="font-weight:600; font-size:0.9rem;">Charge balance now</div>
                        <div style="font-size:0.78rem; color:var(--text-muted);">Deduct from user balance immediately. Order marked as Completed.</div>
                    </div>
                </label>
                <label id="optLeavePending" onclick="setChargeMode(false)"
                    style="display:flex; align-items:center; gap:12px; padding:12px 16px; cursor:pointer; transition:background .2s;">
                    <input type="radio" name="chargeMode" value="0" style="accent-color:var(--primary);">
                    <div>
                        <div style="font-weight:600; font-size:0.9rem;">Leave as Pending</div>
                        <div style="font-size:0.78rem; color:var(--text-muted);">User pays later from their dashboard with balance or crypto.</div>
                    </div>
                </label>
            </div>

            <div id="createError" style="display:none; color:#ef4444; background:rgba(239,68,68,0.1); padding:10px; border-radius:8px; font-size:0.88rem; margin-top:12px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn" style="background:#333; color:#fff;" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn-manage" id="btnCreate" onclick="submitCreate()">
                <i class="fas fa-check"></i> <span id="btnCreateLabel">Charge & Create</span>
            </button>
        </div>
    </div>
</div>

<script src="js/managed_services.js?v=<?php echo filemtime(__DIR__ . '/js/managed_services.js'); ?>"></script>

<?php include '../footer.php'; ?>
