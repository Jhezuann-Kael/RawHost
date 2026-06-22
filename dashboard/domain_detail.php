<?php
require_once '../api/config.php';
require_once '../models/User.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$domainId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (empty($domainId)) {
    header('Location: domains.php');
    exit;
}

require_once '../includes/lang_loader.php';

$pageTitle = $lang['dom_det_title'] . ' - ' . SITE_NAME;
$extraHead = '
    <style>
        .toggle-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            margin-right: 15px;
            padding: 5px;
            transition: color 0.3s;
        }
        .toggle-btn:hover {
            color: var(--primary);
        }
        .detail-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        .detail-value {
            color: var(--text-light);
            font-weight: 600;
        }
        .nameserver-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .nameserver-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 6px;
            font-family: monospace;
        }
    </style>
';
include 'header.php';
?>

<main class="main-content">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1 style="margin: 0;"><?php echo $lang['dom_det_title']; ?></h1>
            </div>
            <button onclick="window.location.href='domains.php'" class="btn btn-outline" style="max-width: 200px;">
                <i class="fas fa-arrow-left"></i> <?php echo $lang['dom_det_btn_back']; ?>
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" style="text-align: center; padding: 60px;">
        <i class="fas fa-spinner fa-spin fa-3x" style="color:var(--primary);"></i>
        <p style="margin-top: 20px;"><?php echo $lang['dom_det_loading']; ?></p>
    </div>

    <!-- Error State -->
    <div id="errorState" style="display: none; text-align: center; padding: 60px;">
        <i class="fas fa-exclamation-triangle fa-3x" style="color: #ef4444;"></i>
        <p id="errorMessage" style="margin-top: 20px; color: #ef4444;"></p>
        <button onclick="window.location.href='domains.php'" class="btn btn-primary" style="margin-top: 20px;">
            <?php echo $lang['dom_det_btn_back']; ?>
        </button>
    </div>

    <!-- Domain Details -->
    <div id="domainDetails" style="display: none;">
        <!-- Domain Info Card -->
        <div class="detail-card">
            <h2 style="margin-top: 0; margin-bottom: 20px;"><i class="fas fa-globe"></i>
                <?php echo $lang['dom_det_header_info']; ?></h2>
            <div class="detail-row">
                <span class="detail-label"><?php echo $lang['dom_det_lbl_name']; ?></span>
                <span class="detail-value" id="domainName">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php echo $lang['dom_det_lbl_status']; ?></span>
                <span id="domainStatus"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php echo $lang['dom_det_lbl_date_reg']; ?></span>
                <span class="detail-value" id="createdDate">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php echo $lang['dom_det_lbl_date_exp']; ?></span>
                <span class="detail-value" id="expirationDate">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php echo $lang['dom_det_lbl_term']; ?></span>
                <span class="detail-value"><span id="registrationTerm">-</span>
                    <?php echo $lang['dom_card_years']; ?></span>
            </div>
        </div>

        <!-- Nameservers Card -->
        <div class="detail-card">
            <h2 style="margin-top: 0; margin-bottom: 20px;"><i class="fas fa-network-wired"></i>
                <?php echo $lang['dom_det_header_ns']; ?></h2>

            <div id="nameserverDisplay">
                <ul class="nameserver-list" id="nameserverList">
                    <li style="text-align: center; color: var(--text-muted);"><?php echo $lang['dom_det_ns_empty']; ?>
                    </li>
                </ul>
            </div>

            <button onclick="openChangeNameserversModal()" class="btn btn-manage"
                style="width: 100%; margin-top: 15px;">
                <i class="fas fa-edit"></i> <?php echo $lang['dom_det_btn_ns_change']; ?>
            </button>
        </div>

        <!-- Contacts Card (if available) -->
        <div class="detail-card" id="contactsCard" style="display: none;">
            <h2 style="margin-top: 0; margin-bottom: 20px;"><i class="fas fa-address-card"></i>
                <?php echo $lang['dom_det_header_contact']; ?>
            </h2>
            <div id="contactsContent"></div>
        </div>
    </div>
</main>

<!-- Change Nameservers Modal -->
<div class="modal-overlay" id="changeNameserversModal">
    <div class="modal" style="width: 500px; height: auto;">
        <div class="modal-header">
            <h2><?php echo $lang['dom_det_modal_title']; ?></h2>
            <button class="btn-close" onclick="closeNameserversModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="nsError" class="alert alert-danger"
                style="display:none; margin-bottom:15px; color: #ff6b6b; background: rgba(255, 107, 107, 0.1); padding: 10px; border-radius: 4px;">
            </div>
            <div id="nsSuccess" class="alert alert-success"
                style="display:none; margin-bottom:15px; color: #51cf66; background: rgba(81, 207, 102, 0.1); padding: 10px; border-radius: 4px;">
            </div>

            <form id="nameserverForm" onsubmit="handleChangeNameservers(event)">
                <div class="form-group">
                    <label><?php echo $lang['dom_det_ns1_label']; ?></label>
                    <input type="text" id="dns1" class="form-control" placeholder="ns1.ejemplo.com" required>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['dom_det_ns2_label']; ?></label>
                    <input type="text" id="dns2" class="form-control" placeholder="ns2.ejemplo.com" required>
                </div>

                <button type="submit" class="btn btn-manage" style="width: 100%; margin-top: 20px;">
                    <i class="fas fa-save"></i> <?php echo $lang['dom_det_btn_save']; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const domainId = <?php echo $domainId; ?>;
    const LANG_DOM_DET = <?php echo json_encode([
        'loading' => $lang['dom_det_loading'],
        'err_load' => $lang['dom_det_err_load'],
        'err_conn' => $lang['dom_det_err_conn'],
        'ns_empty' => $lang['dom_det_ns_empty'],
        'ns_req' => $lang['dom_det_ns_req'],
        'saving' => $lang['dom_det_btn_saving'],
        'ns_success' => $lang['dom_det_ns_success'],
        'ns_err' => $lang['dom_det_ns_err'],
        'status_active' => $lang['prof_status_active'],
        'status_pending' => $lang['status_pending'] ?? 'Pending',
        'status_expired' => $lang['tx_res_expired'] ?? 'Expired',
        'err_conn_prefix' => $lang['prof_js_err_conn'] ?? 'Error de conexión',
    ]); ?>;
    let currentDomain = null;

    async function loadDomainDetails() {
        const loadingState = document.getElementById('loadingState');
        const errorState = document.getElementById('errorState');
        const detailsSection = document.getElementById('domainDetails');

        try {
            const res = await fetch(`../api/domains/get?id=${domainId}`);
            const data = await res.json();

            if (data.success) {
                currentDomain = data.data;
                displayDomainDetails(currentDomain);

                loadingState.style.display = 'none';
                detailsSection.style.display = 'block';
            } else {
                showError(data.message || LANG_DOM_DET.err_load);
            }
        } catch (err) {
            console.error('Error loading domain:', err);
            showError(LANG_DOM_DET.err_conn);
        }
    }

    function showError(message) {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
        document.getElementById('errorMessage').textContent = message;
    }

    function displayDomainDetails(domain) {
        // Basic info
        document.getElementById('domainName').textContent = domain.domain_name;

        // Status badge
        const statusEl = document.getElementById('domainStatus');
        const status = (domain.status || 'UNKNOWN').toUpperCase();
        let statusClass = 'status-inactive';
        let statusText = status;

        if (status === 'ACTIVE' || status === 'SUCCESS') {
            statusClass = 'status-active';
            statusText = LANG_DOM_DET.status_active;
        }
        else if (status === 'PENDING') {
            statusClass = 'status-provisioning';
            statusText = LANG_DOM_DET.status_pending;
        }
        else if (status === 'EXPIRED') {
            statusClass = 'status-expired';
            statusText = LANG_DOM_DET.status_expired;
        }

        statusEl.innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;

        // Dates
        document.getElementById('createdDate').textContent = domain.created_at
            ? new Date(domain.created_at).toLocaleDateString()
            : 'N/A';
        document.getElementById('expirationDate').textContent = domain.expiration_date
            ? new Date(domain.expiration_date).toLocaleDateString()
            : 'N/A';
        document.getElementById('registrationTerm').textContent = domain.registration_term || 1;

        // Nameservers
        const nameserverList = document.getElementById('nameserverList');
        if (domain.nameservers && domain.nameservers.length > 0) {
            nameserverList.innerHTML = domain.nameservers.map(ns =>
                `<li class="nameserver-item"><i class="fas fa-server"></i> ${ns}</li>`
            ).join('');
        } else {
            nameserverList.innerHTML = `<li style="text-align: center; color: var(--text-muted);">${LANG_DOM_DET.ns_empty}</li>`;
        }

        // Contacts (if available)
        if (domain.contacts) {
            const contactsCard = document.getElementById('contactsCard');
            const contactsContent = document.getElementById('contactsContent');

            let contactsHtml = '';
            for (const [key, value] of Object.entries(domain.contacts)) {
                contactsHtml += `
                    <div class="detail-row">
                        <span class="detail-label">${key}</span>
                        <span class="detail-value">${value}</span>
                    </div>
                `;
            }

            contactsContent.innerHTML = contactsHtml;
            contactsCard.style.display = 'block';
        }
    }

    function openChangeNameserversModal() {
        const modal = document.getElementById('changeNameserversModal');
        modal.classList.add('active');

        // Pre-fill with current nameservers if available
        if (currentDomain && currentDomain.nameservers && currentDomain.nameservers.length >= 2) {
            document.getElementById('dns1').value = currentDomain.nameservers[0] || '';
            document.getElementById('dns2').value = currentDomain.nameservers[1] || '';
        }

        // Clear messages
        document.getElementById('nsError').style.display = 'none';
        document.getElementById('nsSuccess').style.display = 'none';
    }

    function closeNameserversModal() {
        const modal = document.getElementById('changeNameserversModal');
        modal.classList.remove('active');
    }

    async function handleChangeNameservers(e) {
        e.preventDefault();

        const btn = e.target.querySelector('button[type="submit"]');
        const errorDiv = document.getElementById('nsError');
        const successDiv = document.getElementById('nsSuccess');

        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        const dns1 = document.getElementById('dns1').value.trim();
        const dns2 = document.getElementById('dns2').value.trim();

        if (!dns1 || !dns2) {
            errorDiv.textContent = LANG_DOM_DET.ns_req;
            errorDiv.style.display = 'block';
            return;
        }

        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_DOM_DET.saving}`;

        try {
            const payload = {
                domain_id: domainId,
                dns1: dns1,
                dns2: dns2
            };

            const res = await fetch('../api/domains/change_nameservers', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (data.status === 'success') {
                successDiv.textContent = LANG_DOM_DET.ns_success;
                successDiv.style.display = 'block';

                // Reload domain details
                setTimeout(() => {
                    closeNameserversModal();
                    loadDomainDetails();
                }, 1500);
            } else {
                errorDiv.textContent = data.message || LANG_DOM_DET.ns_err;
                errorDiv.style.display = 'block';
            }
        } catch (err) {
            console.error('Error changing nameservers:', err);
            errorDiv.textContent = LANG_DOM_DET.err_conn_prefix + ': ' + err.message;
            errorDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // Load details on page load
    document.addEventListener('DOMContentLoaded', loadDomainDetails);
</script>

<?php include 'footer.php'; ?>