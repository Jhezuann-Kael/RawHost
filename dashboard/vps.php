<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../api/config.php';
require_once '../includes/lang_loader.php';
$pageTitle = $lang['vps_title'] . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="css/vps.min.css?v=' . filemtime(__DIR__ . '/css/vps.min.css') . '">
';
include 'header.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="header">
        <div class="header-flex">
            <div class="header-left">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 style="margin: 0;"><?php echo $lang['vps_header']; ?></h1>
            </div>
            <div class="header-right">
                <!-- Grid / List view toggle -->
                <div class="view-toggles">
                    <button class="view-toggle-btn active" id="btnViewGrid" title="Card view">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-toggle-btn" id="btnViewList" title="List view">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                <a href="orders/pay_vps" class="btn btn-manage btn-create-pulse" style="max-width: 180px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-plus"></i><?php echo $lang['vps_btn_create']; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Empty hero (shown when user has no servers) -->
    <div id="vpsEmptyHero" style="display:none;">
        <div class="page-empty-hero">
            <div class="page-empty-glow"></div>
            <div class="page-empty-icon-wrap">
                <i class="fas fa-server"></i>
            </div>
            <h2 class="page-empty-title"><?php echo $lang['vps_empty_title']; ?></h2>
            <p class="page-empty-sub"><?php echo $lang['vps_empty_text']; ?></p>
            <a href="orders/pay_vps" class="btn btn-manage page-empty-cta">
                <i class="fas fa-plus"></i> <?php echo $lang['vps_btn_create']; ?>
            </a>
            <div class="page-empty-features">
                <div class="page-feat-chip"><i class="fas fa-bolt"></i> <?php echo $lang['vps_chip_deploy']; ?></div>
                <div class="page-feat-chip"><i class="fas fa-shield-alt"></i> <?php echo $lang['vps_chip_ddos']; ?></div>
                <div class="page-feat-chip"><i class="fas fa-headset"></i> <?php echo $lang['vps_chip_support']; ?></div>
            </div>
        </div>
    </div>

    <!-- Active servers section (shown when user has servers) -->
    <div id="activeServersSection" style="display:none;">
        <div class="section-header-row">
            <div class="section-title"><?php echo $lang['vps_sec_active']; ?></div>
            <div id="statusChipBar" class="status-chip-bar"></div>
        </div>
        <div class="servers-list-header" id="serversListHeader">
            <div><?php echo $lang['vps_list_name'] ?? 'Server'; ?></div>
            <div>IP / OS</div>
            <div>Specs</div>
            <div>Expira</div>
            <div><?php echo $lang['vps_list_actions'] ?? 'Actions'; ?></div>
        </div>
        <div class="servers-grid" id="serversGrid"></div>
    </div>

    <div id="ordersSectionWrap" style="display:none;">
        <div class="section-title" style="margin-top: 30px;"><?php echo $lang['vps_sect_orders']; ?></div>
        <div id="vpsOrdersWrap">
            <div style="text-align: center; padding: 30px;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i>
            </div>
        </div>
    </div>
</main>

<!-- Create Server Modal -->
<div class="modal-overlay" id="createServerModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle"><?php echo $lang['vps_modal_title']; ?></h2>
            <button class="btn-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <!-- Step 1: Payment Method + Duration & Plan -->
            <div id="step1" style="display: block;">

                <!-- Payment Method Toggle -->
                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.88rem; color: var(--text-muted); display: block; margin-bottom: 10px;">
                        <?php echo $lang['vps_pay_method_label']; ?>
                    </label>
                    <div class="payment-method-grid">
                        <div id="pay-option-balance" class="payment-option-card" onclick="selectPayMethod('balance')">
                            <div class="icon-large"><i class="fas fa-wallet"></i></div>
                            <div class="title"><?php echo $lang['vps_pay_balance_title']; ?></div>
                            <div class="subtitle">
                                <?php echo $lang['vps_pay_balance_avail']; ?> <span
                                    id="user-balance-display"><?php echo $lang['vps_pay_balance_loading']; ?></span>
                            </div>
                        </div>
                        <div id="pay-option-crypto" class="payment-option-card" onclick="selectPayMethod('crypto')">
                            <div class="icon-large">₿</div>
                            <div class="title"><?php echo $lang['vps_pay_crypto_title']; ?></div>
                            <div class="subtitle"><?php echo $lang['vps_pay_crypto_sub']; ?></div>
                        </div>
                    </div>
                    <!-- Plan filter notice -->
                    <div id="plan-filter-notice" class="notice-info" style="display:none;">
                        <i class="fas fa-info-circle"></i> <?php echo $lang['vps_plan_filter_notice']; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['vps_label_duration']; ?></label>
                    <div class="duration-grid">
                        <div class="duration-card" data-hours="720" onclick="selectDuration(720, this)">
                            <div class="duration-value"><?php echo $lang['vps_js_month_1']; ?></div>
                        </div>
                        <div class="duration-card" data-hours="1440" onclick="selectDuration(1440, this)">
                            <div class="duration-value"><?php echo $lang['vps_js_month_2']; ?></div>
                        </div>
                        <div class="duration-card" data-hours="2160" onclick="selectDuration(2160, this)">
                            <div class="duration-value"><?php echo $lang['vps_js_month_3']; ?></div>
                        </div>
                    </div>

                    <label
                        style="font-size: 0.9rem; color: var(--text-muted); margin-top: 15px; display: block;"><?php echo $lang['vps_label_period_short']; ?></label>
                    <div class="duration-grid">
                        <div class="duration-card" data-hours="24" onclick="selectDuration(24, this)">
                            <div class="duration-value"><?php echo $lang['vps_js_hour_24']; ?></div>
                        </div>
                        <div class="duration-card" data-hours="168" onclick="selectDuration(168, this)">
                            <div class="duration-value"><?php echo $lang['vps_js_day_7']; ?></div>
                        </div>
                        <div class="duration-card" data-hours="360" onclick="selectDuration(360, this)">
                            <div class="duration-value"><?php echo $lang['vps_js_day_15']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['vps_label_select_plan']; ?></label>
                    <div id="plansGrid" class="plans-grid">
                        <!-- Plans loaded via JS -->
                    </div>
                </div>

                <input type="hidden" id="selectedDuration" value="">
                <input type="hidden" id="selectedPlanId" value="">
            </div>

            <!-- Step 2: Config & OS -->
            <div id="step2" style="display: none;">
                <div class="form-group">
                    <label><?php echo $lang['vps_label_host']; ?></label>
                    <div class="input-with-button">
                        <input type="text" id="hostname" class="form-control"
                            placeholder="<?php echo $lang['vps_label_host_ph']; ?>">
                        <button type="button" class="btn btn-outline" onclick="generateHostname()"
                            title="<?php echo $lang['vps_btn_gen_host'] ?? 'Generate'; ?>">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <small
                        style="color: var(--text-muted); font-size: 0.8rem;"><?php echo $lang['vps_help_host']; ?></small>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['vps_label_pass']; ?></label>
                    <div class="input-with-button">
                        <input type="text" id="password" class="form-control"
                            placeholder="<?php echo $lang['vps_label_pass_ph']; ?>">
                        <button type="button" class="btn btn-outline" onclick="generatePassword()"
                            title="<?php echo $lang['vps_btn_gen_pass'] ?? 'Generate'; ?>">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <small
                        style="color: var(--text-muted); font-size: 0.8rem;"><?php echo $lang['vps_help_pass']; ?></small>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['vps_label_os']; ?></label>
                    <div id="osGrid" class="os-grid">
                        <!-- OS options loaded via JS -->
                    </div>
                </div>

                <div id="step2Error" style="color: #ef4444; margin-top: 15px; display: none;"></div>

                <input type="hidden" id="selectedOS" value="">
            </div>

            <!-- Step 3: Summary (balance: final confirm; crypto: review before invoice) -->
            <div id="step3" style="display: none;">
                <div class="summary-card">
                    <h3 style="margin-bottom: 20px; color: var(--primary);"><?php echo $lang['vps_summary_title']; ?>
                    </h3>
                    <div class="summary-row">
                        <span><?php echo $lang['vps_summary_plan'] ?? 'Plan'; ?></span>
                        <strong id="summary-plan">-</strong>
                    </div>
                    <div class="summary-row">
                        <span><?php echo $lang['vps_summary_duration'] ?? 'Duration'; ?></span>
                        <strong id="summary-duration">-</strong>
                    </div>
                    <div class="summary-row">
                        <span><?php echo $lang['vps_summary_os'] ?? 'OS'; ?></span>
                        <strong id="summary-os">-</strong>
                    </div>
                    <div class="summary-row">
                        <span><?php echo $lang['vps_summary_hostname'] ?? 'Hostname'; ?></span>
                        <strong id="summary-hostname">-</strong>
                    </div>
                    <!-- Crypto-only rows (hidden for balance) -->
                    <div id="summary-crypto-section" style="display: none;">
                        <div class="summary-row" style="margin-top: 10px;">
                            <span><?php echo $lang['vps_summary_crypto'] ?? 'Crypto'; ?></span>
                            <strong id="summary-coin" style="color: var(--primary);">-</strong>
                        </div>
                        <div class="summary-row">
                            <span><?php echo $lang['vps_summary_network'] ?? 'Network'; ?></span>
                            <strong id="summary-network">-</strong>
                        </div>
                    </div>
                    <div class="summary-row"
                        style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: 15px;">
                        <span style="font-size: 1.2rem;"><?php echo $lang['vps_summary_total'] ?? 'Total'; ?></span>
                        <strong id="summary-total" style="font-size: 1.5rem; color: var(--primary);">$0.00</strong>
                    </div>
                </div>
            </div>

            <!-- Step 4: Crypto Currency Selector -->
            <div id="step4" style="display: none;">
                <p style="color: var(--text-muted); margin-bottom: 14px; font-size: 0.88rem;">
                    <?php echo $lang['vps_crypto_select_prompt'] ?? 'Select the cryptocurrency you want to pay with:'; ?>
                </p>

                <!-- Crypto selector -->
                <div id="crypto-currency-selector">
                    <label style="font-size: 0.88rem; display: block; margin-bottom: 10px; color: var(--text-muted);">
                        <?php echo $lang['vps_crypto_select_lbl'] ?? 'Select Cryptocurrency:'; ?>
                    </label>

                    <div id="vps-coinGrid" class="currency-main-grid">
                        <!-- injected by JS -->
                    </div>

                    <div id="vps-moreCoinsContainer" style="display:none; margin-top: 8px;">
                        <div id="vps-moreCoinsGrid" class="currency-more-grid">
                            <!-- injected by JS -->
                        </div>
                    </div>
                    <button type="button" id="vps-btnToggleMore" class="btn-toggle-text">
                        <i class="fas fa-chevron-down"></i>
                        <?php echo $lang['vps_crypto_more_coins'] ?? 'See more coins'; ?>
                    </button>

                    <div id="vps-networkGroup" style="display:none; margin-top: 14px; animation: fadeIn 0.3s;">
                        <label
                            style="font-size: 0.88rem; display: block; margin-bottom: 8px; color: var(--text-muted);">
                            <?php echo $lang['vps_crypto_network_lbl'] ?? 'Network:'; ?>
                        </label>
                        <div id="vps-networkGrid" class="network-selector-grid"></div>
                    </div>

                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px;">
                        <?php echo $lang['vps_crypto_fee_notice'] ?? 'The network fee is paid by you.'; ?>
                    </p>
                </div>

                <div id="step4Error" style="color: #ef4444; margin-top: 12px; font-size: 0.88rem; display: none;"></div>
            </div>

            <div id="createError" style="color: #ef4444; margin-top: 15px; display: none;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-power" onclick="closeModal()"><?php echo $lang['vps_btn_cancel']; ?></button>
            <button class="btn btn-power" id="btnBack" onclick="previousStep()"
                style="display: none;"><?php echo $lang['vps_btn_back']; ?></button>
            <button class="btn btn-manage" id="btnNext"
                onclick="nextStep()"><?php echo $lang['vps_btn_next']; ?></button>
            <button class="btn btn-manage" id="btnCreate" onclick="createServer()"
                style="display: none;"><?php echo $lang['vps_btn_submit']; ?></button>
        </div>
    </div>
</div>
<!-- Floating Telegram Button -->
<a href="https://t.me/raw_host_bot" target="_blank" rel="noopener" class="fab-btn" title="Ordenar VPS vía Telegram Bot">
    <i class="fab fa-telegram-plane"></i>
</a>

<script>
    const LANG_VPS = <?php echo json_encode([
        'month_1' => $lang['vps_js_month_1'],
        'month_2' => $lang['vps_js_month_2'],
        'month_3' => $lang['vps_js_month_3'],
        'hour_24' => $lang['vps_js_hour_24'],
        'day_7' => $lang['vps_js_day_7'],
        'day_15' => $lang['vps_js_day_15'],
        'err_select' => $lang['vps_js_err_select'],
        'err_fields' => $lang['vps_js_err_fields'],
        'err_host' => $lang['vps_js_err_host'],
        'err_pass' => $lang['vps_js_err_pass'],
        'creating' => $lang['vps_js_creating'],
        'success' => $lang['vps_js_success'],
        'err_create' => $lang['vps_js_err_create'],
        'err_load' => $lang['vps_err_load'],
        'empty_title' => $lang['vps_empty_title'],
        'empty_text' => $lang['vps_empty_text'],
        'btn_manage' => $lang['vps_btn_manage'],
        'btn_submit' => $lang['vps_btn_submit'],
        'btn_invoice' => $lang['vps_btn_invoice'],
        'btn_create' => $lang['vps_btn_create'],
        'modal_title' => $lang['vps_modal_title'],
        'modal_title_config' => $lang['vps_modal_title'],
        'modal_title_confirm' => $lang['vps_summary_title'],
        'modal_title_crypto' => $lang['vps_modal_title_crypto'],
        'modal_title_order' => $lang['vps_modal_title_order'],
        'modal_title_invoice' => $lang['vps_modal_title_invoice'],
        'err_pay_method' => $lang['vps_err_pay_method'],
        'err_conn' => $lang['vps_err_conn'],
        'addr_copied' => $lang['vps_addr_copied'],
        'label_hours' => $lang['vps_label_hours'],
        'status_suspended'   => $lang['status_suspended'],
        'status_active'      => $lang['status_active'],
        'status_inactive'    => $lang['status_inactive'],
        'status_provisioning'=> $lang['status_provisioning'],
        'sect_orders'        => $lang['vps_sect_orders'],
        'no_orders'          => $lang['vps_no_orders'],
        'tbl_id'             => $lang['vps_orders_tbl_id'],
        'tbl_plan'           => $lang['vps_orders_tbl_plan'],
        'tbl_dur'            => $lang['vps_orders_tbl_dur'],
        'tbl_amount'         => $lang['vps_orders_tbl_amount'],
        'tbl_status'         => $lang['vps_orders_tbl_status'],
        'tbl_date'           => $lang['vps_orders_tbl_date'],
        'hours'              => $lang['vps_orders_hours'],
        'action_start'   => $lang['vps_action_start'],
        'action_restart' => $lang['vps_action_restart'],
        'action_stop'    => $lang['vps_action_stop'],
        'action_console' => $lang['vps_action_console'],
        'action_err_conn'=> $lang['vps_action_err_conn'],
    ]); ?>;
</script>
<script src="js/vps.min.js?v=<?php echo filemtime(__DIR__ . '/js/vps.min.js'); ?>"></script>

<?php include 'footer.php'; ?>