<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/lang_loader.php';
require_once '../api/config.php';
$pageTitle = $lang['tx_title'] . ' - ' . SITE_NAME;
$extraHead = '<link rel="stylesheet" href="css/transactions.min.css?v=' . filemtime(__DIR__ . '/css/transactions.min.css') . '">';
include 'header.php';
?>

<main class="main-content">
    <div class="header">
        <div style="display:flex; align-items:center;">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu">
                <i class="fas fa-bars"></i>
            </button>
            <h1 style="margin:0;"><?php echo $lang['tx_title']; ?></h1>
        </div>
    </div>

    <div class="balance-card">
        <div style="color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">
            <?php echo $lang['tx_lbl_balance'] ?? 'Available Balance'; ?>
        </div>
        <div class="balance-amount" id="displayBalance">---</div>
        <button class="btn btn-primary" onclick="openRechargeModal()">
            <i class="fas fa-plus-circle"></i> <?php echo $lang['tx_btn_add_funds'] ?? 'Add Funds'; ?>
        </button>
    </div>

    <div class="tabs-nav">
        <button class="tab-btn active" onclick="switchTab('recharges', this)">
            <i class="fas fa-history" style="font-size:0.9em;"></i> <?php echo $lang['tx_tab_transactions'] ?? 'Transactions'; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('movements', this)">
            <i class="fas fa-exchange-alt" style="font-size:0.9em;"></i> <?php echo $lang['tx_tab_movements'] ?? 'Movements'; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('services', this); if (!servicesLoaded) loadServices();">
            <i class="fas fa-briefcase" style="font-size:0.9em;"></i> <?php echo $lang['tx_tab_services'] ?? 'Managed Services'; ?>
            <span id="svcPendingBadge" style="display:none; background:#ef4444; color:#fff; border-radius:10px; font-size:0.7rem; font-weight:700; padding:1px 6px; margin-left:5px; vertical-align:middle;"></span>
        </button>
    </div>

    <!-- Tab: Recharges -->
    <div id="tab-recharges" class="tab-content active">
        <div class="table-responsive">
            <table class="transactions-table" style="margin-bottom:10px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr><td colspan="5" style="text-align:center;"><?php echo $lang['tx_loading']; ?></td></tr>
                </tbody>
            </table>
        </div>
        <div id="transactionsPagination" class="pagination" style="margin-bottom:40px;"></div>
    </div>

    <!-- Tab: Movements -->
    <div id="tab-movements" class="tab-content">
        <div class="table-responsive">
            <table class="transactions-table" style="margin-bottom:10px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="movementsTableBody">
                    <tr><td colspan="4" style="text-align:center;"><?php echo $lang['tx_loading']; ?></td></tr>
                </tbody>
            </table>
        </div>
        <div id="movementsPagination" class="pagination"></div>
    </div>

    <!-- Tab: Managed Services -->
    <div id="tab-services" class="tab-content">
        <div class="table-responsive">
            <table class="transactions-table" style="margin-bottom:10px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $lang['tx_svc_col_desc']   ?? 'Service'; ?></th>
                        <th><?php echo $lang['tx_svc_col_date']   ?? 'Date'; ?></th>
                        <th><?php echo $lang['tx_svc_col_amount'] ?? 'Amount'; ?></th>
                        <th><?php echo $lang['tx_svc_col_status'] ?? 'Status'; ?></th>
                    </tr>
                </thead>
                <tbody id="servicesTableBody">
                    <tr><td colspan="5" style="text-align:center;"><?php echo $lang['tx_loading']; ?></td></tr>
                </tbody>
            </table>
        </div>
        <div id="servicesPagination" class="pagination"></div>
    </div>
</main>

<!-- Modal: Recharge -->
<div class="modal-overlay" id="rechargeModal">
    <div class="modal">
        <div class="modal-header">
            <h2><?php echo $lang['tx_modal_title']; ?></h2>
            <button class="btn-close" onclick="closeRechargeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div style="background:rgba(0,243,255,0.05); border:1px solid rgba(0,243,255,0.2); color:var(--text-light); padding:12px; border-radius:8px; margin-bottom:20px; font-size:0.9rem; display:flex; align-items:start; gap:10px;">
                <i class="fas fa-info-circle" style="color:var(--primary); margin-top:3px;"></i>
                <div>
                    <?php echo $lang['tx_min_amount_note']; ?><br>
                    <span style="font-size:0.85em; opacity:0.8;"><?php echo $lang['tx_min_btc_note']; ?></span>
                </div>
            </div>

            <div id="paymentForm">
                <div class="form-group">
                    <label><?php echo $lang['tx_label_amount']; ?></label>
                    <input type="number" id="rechargeAmount" class="form-control" placeholder="5.00" min="5" step="0.01">
                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <?php foreach ([10, 20, 35, 50] as $amt): ?>
                        <button type="button" class="btn-sm" onclick="setAmount(<?php echo $amt; ?>)"
                            style="border:1px solid var(--border); background:rgba(255,255,255,0.05); color:var(--text-light); cursor:pointer; flex:1; padding:5px; border-radius:4px;">
                            $<?php echo $amt; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['tx_label_crypto']; ?></label>
                    <div id="coinGrid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:10px;"></div>
                    <div id="moreCoinsContainer" style="display:none; margin-top:10px;">
                        <div id="moreCoinsGrid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); gap:10px;"></div>
                    </div>
                    <button type="button" id="btnToggleMore" class="btn-sm"
                        style="background:none; border:none; color:var(--primary); width:100%; margin-top:5px; cursor:pointer;">
                        <i class="fas fa-chevron-down"></i> <?php echo $lang['tx_btn_more_coins']; ?>
                    </button>
                </div>

                <div class="form-group" id="networkGroup" style="display:none; animation:fadeIn 0.3s;">
                    <label><?php echo $lang['tx_label_network']; ?></label>
                    <div id="networkGrid" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                </div>

                <input type="hidden" id="selectedCurrency">
                <input type="hidden" id="selectedNetwork">
                <div id="rechargeError" style="color:#ef4444; margin-top:10px; font-size:0.9rem;"></div>
            </div>

            <div id="paymentResult" style="display:none; text-align:center;">
                <div style="margin-bottom:20px;">
                    <img id="qrCodeImg" src="" alt="QR Code" style="background:white; padding:10px; border-radius:8px; width:200px; height:200px;">
                </div>
                <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px; text-align:left;">
                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['tx_res_send_exact']; ?></div>
                    <div style="font-size:1.2rem; color:var(--primary); font-weight:bold; margin-bottom:10px;">
                        <span id="payAmount">0.000000</span> <span id="payCurrency">TRX</span>
                    </div>
                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['tx_res_network']; ?></div>
                    <div style="font-size:1.1rem; color:var(--text-light); font-weight:bold; margin-bottom:10px;" id="payNetwork">---</div>
                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['tx_res_address']; ?></div>
                    <div style="font-family:monospace; word-break:break-all; color:var(--text-light); margin-bottom:10px;">
                        <span id="payAddress">...</span>
                        <button onclick="copyAddress()" style="background:none; border:none; color:var(--primary); cursor:pointer; margin-left:5px;"><i class="fas fa-copy"></i></button>
                    </div>
                    <div style="color:#f59e0b; font-size:0.8rem;">
                        <i class="fas fa-clock"></i> <?php echo $lang['tx_res_expires']; ?> <span id="expireTimer">Loading...</span>
                    </div>
                </div>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:15px;"><?php echo $lang['tx_res_note']; ?></p>
            </div>
        </div>
        <div class="modal-footer" style="padding-top:20px; display:flex; gap:10px; border-top:1px solid rgba(255,255,255,0.1);">
            <button class="btn" style="flex:1; background:#333; color:white;" onclick="closeRechargeModal()" id="btnCancelRecharge"><?php echo $lang['tx_btn_cancel']; ?></button>
            <button class="btn btn-primary" style="flex:1;" onclick="createPayment()" id="btnCreatePayment"><?php echo $lang['tx_btn_generate']; ?></button>
        </div>
    </div>
</div>

<!-- Modal: Payment Detail -->
<div class="modal-overlay" id="payDetailModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-receipt"></i> Payment Details</h2>
            <button class="btn-close" onclick="document.getElementById('payDetailModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="payDetailBody">
            <div style="text-align:center; padding:30px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i></div>
        </div>
        <div class="modal-footer" style="justify-content:flex-end;">
            <button class="btn" style="background:#333; color:white;" onclick="document.getElementById('payDetailModal').classList.remove('active')"><?php echo $lang['tx_btn_cancel']; ?></button>
        </div>
    </div>
</div>

<!-- Modal: Crypto Pay for Managed Services -->
<div class="modal-overlay" id="svcCryptoModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-coins" style="margin-right:8px; color:var(--primary);"></i>Pay with Crypto</h2>
            <button class="btn-close" onclick="closeSvcCryptoModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="svcCryptoSummary" style="background:rgba(0,243,255,0.05); border:1px solid rgba(0,243,255,0.15); border-radius:8px; padding:12px 16px; margin-bottom:18px; font-size:0.9rem;">
                <div style="color:var(--text-muted); font-size:0.78rem; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px;">Service</div>
                <div id="svcCryptoDesc" style="font-weight:600; color:var(--text-light);"></div>
                <div id="svcCryptoAmt" style="color:var(--primary); font-size:1.2rem; font-weight:700; margin-top:4px;"></div>
            </div>

            <div id="svcCryptoStep1">
                <div class="form-group">
                    <label><?php echo $lang['tx_label_crypto']; ?></label>
                    <div id="svcCoinGrid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:10px;"></div>
                    <div id="svcMoreCoinsContainer" style="display:none; margin-top:10px;">
                        <div id="svcMoreCoinsGrid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); gap:10px;"></div>
                    </div>
                    <button type="button" id="svcBtnToggleMore" style="background:none; border:none; color:var(--primary); width:100%; margin-top:5px; cursor:pointer;">
                        <i class="fas fa-chevron-down"></i> <?php echo $lang['tx_btn_more_coins']; ?>
                    </button>
                </div>
                <div class="form-group" id="svcNetworkGroup" style="display:none;">
                    <label><?php echo $lang['tx_label_network']; ?></label>
                    <div id="svcNetworkGrid" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                </div>
                <input type="hidden" id="svcSelectedCurrency">
                <input type="hidden" id="svcSelectedNetwork">
                <div id="svcCryptoError" style="color:#ef4444; font-size:0.88rem; margin-top:8px;"></div>
            </div>

            <div id="svcCryptoResult" style="display:none; text-align:center;">
                <div style="margin-bottom:16px;">
                    <img id="svcQrImg" src="" alt="QR" style="background:#fff; padding:10px; border-radius:8px; width:190px; height:190px;">
                </div>
                <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px; text-align:left;">
                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['tx_res_send_exact']; ?></div>
                    <div style="font-size:1.2rem; color:var(--primary); font-weight:700; margin-bottom:10px;">
                        <span id="svcPayAmt"></span> <span id="svcPayCur"></span>
                    </div>
                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['tx_res_network']; ?></div>
                    <div style="font-size:1rem; color:var(--text-light); font-weight:600; margin-bottom:10px;" id="svcPayNet"></div>
                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['tx_res_address']; ?></div>
                    <div style="font-family:monospace; word-break:break-all; color:var(--text-light); margin-bottom:10px;">
                        <span id="svcPayAddr"></span>
                        <button onclick="copySvcAddress()" style="background:none; border:none; color:var(--primary); cursor:pointer; margin-left:5px;"><i class="fas fa-copy"></i></button>
                    </div>
                    <div style="color:#f59e0b; font-size:0.8rem;">
                        <i class="fas fa-clock"></i> <?php echo $lang['tx_res_expires']; ?> <span id="svcExpireTimer"></span>
                    </div>
                </div>
                <p style="font-size:0.82rem; color:var(--text-muted); margin-top:12px;"><?php echo $lang['tx_res_note']; ?></p>
            </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.1); padding-top:16px; gap:10px;">
            <button class="btn" style="flex:1; background:#333; color:#fff;" onclick="closeSvcCryptoModal()"><?php echo $lang['tx_btn_cancel']; ?></button>
            <button class="btn btn-primary" id="btnSvcCryptoPay" style="flex:1;" onclick="submitSvcCryptoPayment()"><?php echo $lang['tx_btn_generate']; ?></button>
        </div>
    </div>
</div>

<!-- JS: PHP variables bridge -->
<script>
const LANG_TX = <?php echo json_encode([
    'status_pending'     => $lang['tx_status_pending'],
    'status_completed'   => $lang['tx_status_completed'],
    'status_failed'      => $lang['tx_status_failed'],
    'status_expired'     => $lang['tx_status_expired'],
    'loading'            => $lang['tx_loading'],
    'no_recharges'       => $lang['tx_no_recharges'],
    'no_movements'       => $lang['tx_no_movements'],
    'no_services'        => $lang['tx_no_services'] ?? 'No managed services yet.',
    'err_connection'     => $lang['tx_err_connection'],
    'view_payment'       => $lang['tx_view_btn'],
    'err_complete_fields'=> $lang['tx_err_complete_fields'],
    'err_min_btc'        => $lang['tx_err_min_btc'],
    'err_min_general'    => $lang['tx_err_min_general'],
    'processing'         => $lang['tx_processing'],
    'err_create'         => $lang['tx_err_create'],
    'res_expired'        => $lang['tx_res_expired'],
    'res_copied'         => $lang['tx_res_copied'],
    'date_format'        => $lang['txt_date_format'],
    'btn_more'           => $lang['tx_btn_more_coins'],
    'btn_less'           => $lang['tx_btn_less_coins'],
    'btn_generate'       => $lang['tx_btn_generate'],
    'detail_loading'     => $lang['tx_detail_loading'],
    'detail_error'       => $lang['tx_detail_error'],
    'lbl_track'          => $lang['tx_lbl_track'],
    'lbl_status'         => $lang['tx_lbl_status_oxa'],
    'lbl_amount'         => $lang['tx_lbl_amount_usd'],
    'lbl_currency'       => $lang['tx_lbl_currency'],
    'lbl_expires'        => $lang['tx_lbl_expires'],
    'lbl_date'           => $lang['tx_lbl_date'],
    'lbl_txs'            => $lang['tx_lbl_txs'],
    'lbl_hash'           => $lang['tx_lbl_hash'],
    'lbl_network'        => $lang['tx_lbl_network'],
    'lbl_confirms'       => $lang['tx_lbl_confirms'],
    'lbl_received'       => $lang['tx_lbl_received'],
    'svc_pay_balance'    => $lang['tx_svc_pay_balance']  ?? 'Pay with Balance',
    'svc_pay_crypto'     => $lang['tx_svc_pay_crypto']   ?? 'Pay with Crypto',
    'svc_confirm_balance'=> $lang['tx_svc_confirm']      ?? 'Pay this service from your balance?',
    'svc_pay_failed'     => $lang['tx_svc_pay_failed']   ?? 'Payment failed',
]); ?>;
</script>
<script src="js/transactions.min.js?v=<?php echo filemtime(__DIR__ . '/js/transactions.min.js'); ?>"></script>

<?php include 'footer.php'; ?>
