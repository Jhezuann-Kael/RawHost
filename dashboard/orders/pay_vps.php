<?php
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../includes/lang_loader.php';

$pageTitle = ($lang['vps_buy_title'] ?? 'Comprar VPS') . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="../css/vps.css?v=' . filemtime(__DIR__ . '/../css/vps.css') . '">
    <link rel="stylesheet" href="../css/pay_vps.css?v=' . filemtime(__DIR__ . '/../css/pay_vps.css') . '">
    <style>
        .ip-addon-row { display:flex; gap:8px; flex-wrap:wrap; }
        .ip-addon-btn {
            padding:8px 16px; border-radius:8px; cursor:pointer; font-size:0.82rem; font-weight:600;
            border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.04);
            color:var(--text-muted); transition:all .15s;
        }
        .ip-addon-btn:hover { border-color:var(--primary); color:var(--text-light); }
        .ip-addon-btn.active { border-color:var(--primary); background:rgba(139,92,246,0.15); color:var(--primary); }
    </style>
';

include __DIR__ . '/../header.php';
?>

<main class="main-content">
    <div style="max-width:1060px;margin:0 auto;padding:14px 16px 4px;">
        <h1 style="margin:0 0 2px;font-size:1.5rem;"><?php echo $lang['vps_buy_header'] ?? 'Configura tu Servidor'; ?>
        </h1>
        <p style="color:var(--text-muted);margin:0;font-size:0.85rem;">
            <?php echo $lang['vps_buy_subtitle'] ?? 'Personaliza tu VPS según tus necesidades.'; ?>
        </p>
    </div>

    <div class="buy-layout">

        <!-- ══ LEFT: steps ══ -->
        <div>
            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step-dot active" id="sdot-1">
                    <div class="step-dot-circle">1</div>
                    <div class="step-dot-label">Plan</div>
                </div>
                <div class="step-line" id="sline-1"></div>
                <div class="step-dot" id="sdot-2">
                    <div class="step-dot-circle">2</div>
                    <div class="step-dot-label"><?php echo $lang['buy_step_configure']; ?></div>
                </div>
                <div class="step-line" id="sline-2"></div>
                <div class="step-dot" id="sdot-3">
                    <div class="step-dot-circle">3</div>
                    <div class="step-dot-label"><?php echo $lang['buy_step_pay']; ?></div>
                </div>
            </div>

            <!-- Step 1: Plan -->
            <div class="step-content active" id="step-1">
                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_label_select_plan']; ?></div>
                    <div class="plans-grid-step" id="plansGrid">
                        <div
                            style="color:var(--text-muted);font-size:0.82rem;grid-column:1/-1;padding:18px 0;text-align:center;">
                            <i class="fas fa-spinner fa-spin"></i> <?php echo $lang['dash_loading']; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Configure -->
            <div class="step-content" id="step-2">

                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_label_duration']; ?></div>
                    <div class="dur-wrap">
                        <div class="dur-btn active" data-h="720" onclick="selDur(720,this)">
                            <?php echo $lang['vps_js_month_1']; ?>
                        </div>
                        <div class="dur-btn" data-h="1440" onclick="selDur(1440,this)">
                            <?php echo $lang['vps_js_month_2']; ?>
                        </div>
                        <div class="dur-btn" data-h="2160" onclick="selDur(2160,this)">
                            <?php echo $lang['vps_js_month_3']; ?>
                        </div>
                        <div class="dur-btn" data-h="24" onclick="selDur(24,this)">
                            <?php echo $lang['vps_js_hour_24']; ?>
                        </div>
                        <div class="dur-custom-btn" id="customToggle" onclick="toggleCustomDur()" title="<?php echo $lang['buy_custom_title']; ?>">
                            <i class="fas fa-sliders-h" style="font-size:0.8rem;"></i>
                        </div>
                    </div>
                    <div class="dur-custom-area" id="customArea">
                        <input type="number" id="customHours" class="form-control" min="1" step="1" placeholder="<?php echo $lang['vps_label_hours']; ?>">
                        <span><?php echo $lang['vps_label_hours']; ?> <small style="opacity:.5;"><?php echo $lang['buy_custom_min']; ?></small></span>
                        <button class="btn btn-outline" style="padding:5px 12px;font-size:0.78rem;"
                            onclick="applyCustomDur()">OK</button>
                    </div>
                </div>

                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_buy_step2'] ?? 'SO / Aplicación'; ?></div>
                    <div class="os-featured-grid" id="osFeaturedGrid"></div>
                    <div class="os-more-section" id="osMoreSection" style="display:none;">
                        <div id="osMoreGrid"></div>
                    </div>
                    <button class="os-more-toggle" id="osMoreBtn" onclick="toggleMoreOS()" style="display:none;">
                        <i class="fas fa-chevron-down" id="osMoreIcon"></i>
                        <span id="osMoreLabel"></span>
                    </button>
                </div>

                <!-- Addons: Extra IPs -->
                <div class="sblock">
                    <div class="slabel"><?php echo $lang['buy_ip_label']; ?> <span style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">$<?php echo number_format(IPV4_ADDON_PRICE, 2); ?><?php echo $lang['buy_ip_price_note']; ?></span></div>
                    <div class="ip-addon-row" id="ipAddonRow">
                        <div class="ip-addon-btn active" data-ips="0" onclick="selIPs(0,this)"><?php echo $lang['buy_ip_none']; ?></div>
                        <div class="ip-addon-btn" data-ips="1" onclick="selIPs(1,this)">+1 IP</div>
                        <div class="ip-addon-btn" data-ips="2" onclick="selIPs(2,this)">+2 IPs</div>
                        <div class="ip-addon-btn" data-ips="3" onclick="selIPs(3,this)">+3 IPs</div>
                        <div class="ip-addon-btn" data-ips="4" onclick="selIPs(4,this)">+4 IPs</div>
                    </div>
                    <p id="ipAddonNote" style="display:none;font-size:0.75rem;color:#f59e0b;margin:6px 0 0;">
                        <i class="fas fa-info-circle"></i> <span id="ipAddonNoteText"></span>
                    </p>
                </div>

                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_buy_step3'] ?? 'Servidor'; ?></div>
                    <div class="cfg-row">
                        <div class="form-group">
                            <label><?php echo $lang['vps_label_host']; ?></label>
                            <div class="input-with-button">
                                <input type="text" id="hostname" class="form-control"
                                    placeholder="<?php echo $lang['vps_label_host_ph']; ?>">
                                <button type="button" class="btn btn-outline" onclick="generateHostname()"><i
                                        class="fas fa-sync-alt"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang['vps_label_pass']; ?></label>
                            <div class="input-with-button">
                                <input type="text" id="password" class="form-control"
                                    placeholder="<?php echo $lang['vps_label_pass_ph']; ?>">
                                <button type="button" class="btn btn-outline" onclick="generatePassword()"><i
                                        class="fas fa-sync-alt"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Step 3: Payment + Review -->
            <div class="step-content" id="step-3">

                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_buy_step4'] ?? 'Método de pago'; ?></div>
                    <div class="pay-row">
                        <div class="pay-card" id="pc-balance" onclick="selPay('balance')">
                            <div class="pay-icon"><i class="fas fa-wallet"></i></div>
                            <div class="pay-title"><?php echo $lang['vps_pay_balance_title']; ?></div>
                            <div class="pay-sub" id="balanceDisplay">...</div>
                        </div>
                        <div class="pay-card" id="pc-crypto" onclick="selPay('crypto')">
                            <div class="pay-icon">₿</div>
                            <div class="pay-title"><?php echo $lang['vps_pay_crypto_title']; ?></div>
                            <div class="pay-sub"><?php echo $lang['vps_pay_crypto_sub']; ?></div>
                        </div>
                    </div>
                    <div id="cryptoArea" style="display:none;margin-top:14px;animation:fadeIn 0.2s;">
                        <div class="slabel" style="margin-bottom:8px;"><?php echo $lang['buy_coin_label']; ?></div>
                        <div class="coin-grid-sm" id="coinGrid">
                            <div style="color:var(--text-muted);font-size:0.75rem;grid-column:1/-1;"><i
                                    class="fas fa-spinner fa-spin"></i></div>
                        </div>
                        <div id="netArea" style="display:none;">
                            <div class="slabel" style="margin:10px 0 6px;"><?php echo $lang['buy_net_label']; ?></div>
                            <div class="net-row" id="netGrid"></div>
                        </div>
                    </div>
                </div>

                <div class="sblock">
                    <div class="slabel"><?php echo $lang['buy_summary_title']; ?></div>
                    <div class="review-row">
                        <span class="rev-label">Plan</span>
                        <span><span class="rev-value" id="r-plan">—</span><span class="rev-edit" onclick="goStep(1)"><i class="fas fa-pencil-alt"></i> <?php echo $lang['buy_change']; ?></span></span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label"><?php echo $lang['buy_order_os']; ?></span>
                        <span><span class="rev-value" id="r-os">—</span><span class="rev-edit" onclick="goStep(2)"><i class="fas fa-pencil-alt"></i> <?php echo $lang['buy_change']; ?></span></span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label"><?php echo $lang['buy_order_duration']; ?></span>
                        <span><span class="rev-value" id="r-dur">—</span><span class="rev-edit" onclick="goStep(2)"><i class="fas fa-pencil-alt"></i> <?php echo $lang['buy_change']; ?></span></span>
                    </div>
                    <div class="review-row" id="r-fee-row" style="display:none;">
                        <span class="rev-label" id="r-fee-label"></span>
                        <span class="rev-value" id="r-fee-value" style="color:#f39c12;"></span>
                    </div>
                    <div class="review-row" id="r-setup-row" style="display:none;">
                        <span class="rev-label" id="r-setup-label"></span>
                        <span class="rev-value" id="r-setup-value" style="color:#a78bfa;"></span>
                    </div>
                    <div class="review-row" id="r-recurring-row" style="display:none;">
                        <span class="rev-label" id="r-recurring-label"></span>
                        <span class="rev-value" id="r-recurring-value"></span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label">Hostname</span>
                        <span><span class="rev-value" id="r-host">—</span><span class="rev-edit" onclick="goStep(2)"><i class="fas fa-pencil-alt"></i> <?php echo $lang['buy_change']; ?></span></span>
                    </div>
                    <div class="review-row" id="r-ip-row" style="display:none;">
                        <span class="rev-label" id="r-ip-label"></span>
                        <span class="rev-value" id="r-ip-value" style="color:#38bdf8;"></span>
                    </div>
                </div>

                <div id="ipCryptoNote" style="display:none;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:10px 14px;font-size:0.8rem;color:#f59e0b;margin-bottom:14px;">
                    <i class="fas fa-info-circle"></i> <?php echo $lang['buy_ip_note_crypto']; ?>
                </div>

                <div class="err-box" id="errBox"></div>

            </div>
        </div>

        <!-- ══ RIGHT: sticky order summary ══ -->
        <div>
            <div class="order-card">
                <div class="order-card-head"><?php echo $lang['buy_order_title']; ?></div>
                <div class="order-card-body">
                    <div class="order-row">
                        <span class="or-label">Plan</span>
                        <span class="or-value empty" id="oc-plan">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['buy_order_duration']; ?></span>
                        <span class="or-value empty" id="oc-dur">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['buy_order_os']; ?></span>
                        <span class="or-value empty" id="oc-os">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['buy_order_payment']; ?></span>
                        <span class="or-value empty" id="oc-pay">—</span>
                    </div>
                </div>
                <div class="order-fee-row" id="oc-ip-row" style="display:none;">
                    <span class="or-label" id="oc-ip-label"></span>
                    <span class="or-value" id="oc-ip-value" style="color:#38bdf8;font-weight:600;"></span>
                </div>
                <div class="order-fee-row" id="oc-fee-row" style="display:none;">
                    <span class="or-label" id="oc-fee-label"></span>
                    <span class="or-value" id="oc-fee-value" style="color:#f39c12;font-weight:600;"></span>
                </div>
                <div class="order-fee-row" id="oc-setup-row" style="display:none;border-top-color:rgba(139,92,246,0.25);background:rgba(139,92,246,0.05);">
                    <span class="or-label" id="oc-setup-label"></span>
                    <span class="or-value" id="oc-setup-value" style="color:#a78bfa;font-weight:600;"></span>
                </div>
                <div class="order-fee-row" id="oc-recurring-row" style="display:none;">
                    <span class="or-label" id="oc-recurring-label"></span>
                    <span class="or-value" id="oc-recurring-value" style="font-weight:600;"></span>
                </div>
                <div class="order-card-total">
                    <span class="total-label"><?php echo $lang['buy_order_total']; ?></span>
                    <span class="total-price" id="oc-total">$0.00</span>
                </div>
                <div class="order-card-btns">
                    <button class="btn btn-manage btn-step-next" id="btnNext" onclick="goNext()">
                        <?php echo $lang['buy_btn_continue']; ?> <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn btn-manage btn-step-next" id="btnFinalize" style="display:none;"
                        onclick="finalizeOrder()">
                        <i class="fas fa-check-circle"></i> <?php echo $lang['vps_btn_submit'] ?? 'Finalizar'; ?>
                    </button>
                    <button class="btn-step-back" id="btnBack" style="display:none;" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> <?php echo $lang['vps_btn_back']; ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    const IP_PRICE = <?php echo IPV4_ADDON_PRICE; ?>;

    const LANG = <?php echo json_encode([
        'ip_note_balance'  => $lang['buy_ip_note_balance'],
        'err_fields'       => $lang['vps_js_err_fields'],
        'err_host'         => $lang['vps_js_err_host'],
        'err_pass'         => $lang['vps_js_err_pass'],
        'btn_submit'       => $lang['vps_btn_submit'],
        'btn_invoice'      => $lang['vps_btn_invoice'],
        'err_plan'         => $lang['buy_err_plan'],
        'err_os'           => $lang['buy_err_os'],
        'err_pay'          => $lang['buy_err_pay'],
        'err_coin'         => $lang['buy_err_coin_net'],
        'no_os'            => $lang['buy_no_os'],
        'no_apps'          => $lang['buy_no_apps'],
        'err_plans'        => $lang['buy_err_plans'],
        'no_coins'         => $lang['buy_no_coins'],
        'processing'       => $lang['man_js_processing'],
        'err_conn'         => $lang['tx_err_connection'],
        'pay_balance'      => $lang['buy_pay_balance'],
        'dur_month'        => $lang['buy_dur_month'],
        'dur_months'       => $lang['buy_dur_months'],
        'os_more'          => $lang['vps_label_os_more']        ?? 'Ver todas las opciones',
        'os_less'          => $lang['vps_label_os_less']         ?? 'Ver menos',
        'os_section'       => $lang['vps_label_os_section']      ?? 'Sistema Operativo',
        'apps_section'     => $lang['vps_label_apps_section']    ?? 'Aplicaciones',
        'feat_debian'      => $lang['vps_feat_debian_desc']      ?? 'Estable y ligero.',
        'feat_ubuntu'      => $lang['vps_feat_ubuntu_desc']      ?? 'Popular y versátil.',
        'feat_windows'     => $lang['vps_feat_windows_desc']     ?? 'Windows Server.',
        'feat_cloudpanel'  => $lang['vps_feat_cloudpanel_desc']  ?? 'Panel web moderno.',
    ]); ?>;

    const COIN_LOGOS = {
        USDT: 'USDT_Logo.png', BTC: 'BTC.png', ETH: 'ETH.png', TRX: 'TRX.png',
        LTC: 'LTC.png', BNB: 'BNB.png', USDC: 'USDC.png', DOGE: 'DODGE.png',
        POL: 'POL.png', SOL: 'SOL.png', SHIB: 'SHIB.png', TON: 'TON.png',
        XMR: 'XMR.png', DAI: 'DAI.png', BCH: 'BCH.png', NOT: 'NOT.png',
        DOGS: 'DOGS.png', XRP: 'XRP.png'
    };
    const TOP_COINS = ['USDT', 'BTC', 'ETH', 'TRX', 'LTC', 'BNB'];

    function getStMultiplier(plan, hours) {
        if (!plan || hours >= 720) return 1.0;
        const fee = (plan.fees || []).find(f => f.billing_type === 'short_term' && f.type === 'percentage');
        return fee ? 1.0 + (parseFloat(fee.value) / 100.0) : 1.0;
    }

    function getStFeePercent(plan, hours) {
        if (!plan || hours >= 720) return 0;
        const fee = (plan.fees || []).find(f => f.billing_type === 'short_term' && f.type === 'percentage');
        return fee ? parseFloat(fee.value) : 0;
    }

    function getSetupFeesTotal(plan) {
        if (!plan) return 0;
        return (plan.fees || [])
            .filter(f => f.billing_type === 'setup' && f.type === 'fixed')
            .reduce((sum, f) => sum + parseFloat(f.value), 0);
    }

    function getSetupFeesList(plan) {
        if (!plan) return [];
        return (plan.fees || []).filter(f => f.billing_type === 'setup' && f.type === 'fixed');
    }

    function getRecurringFees(plan) {
        if (!plan) return { pct: 0, fixed: 0 };
        let pct = 0, fixed = 0;
        (plan.fees || []).forEach(f => {
            if (f.billing_type !== 'recurring') return;
            if (f.type === 'percentage') pct += parseFloat(f.value);
            else if (f.type === 'fixed') fixed += parseFloat(f.value);
        });
        return { pct, fixed };
    }

    let currentStep = 1;
    let plansData = [];
    let selectedPlan = null;
    let selectedDur = 720;
    let selectedOS = null;
    let selectedApp = null;
    let selectedIPs = 0;
    let payMethod = null;
    let selCurrency = null;
    let userBalance = 0;
    let coinsData = [];
    let userPref = null;

    document.addEventListener('DOMContentLoaded', () => {
        generateHostname();
        generatePassword();
        loadPlans();
        loadBalance();
        loadCoins();
        loadUserPref();
        updateOrderCard();
    });

    // ── Navigation ────────────────────────────────────────────────────────────────

    function goStep(n) {
        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep = n;
        document.getElementById(`step-${currentStep}`).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        updateIndicator();
        updateOrderCard();
        if (n === 3) updateReview();
    }

    function goNext() {
        hideErr();
        if (currentStep === 1) {
            if (!selectedPlan) return showErr(LANG.err_plan);
            loadOSOptions();
            goStep(2);
        } else if (currentStep === 2) {
            if (!selectedOS && !selectedApp) return showErr(LANG.err_os);
            const host = document.getElementById('hostname').value.trim();
            const pass = document.getElementById('password').value.trim();
            if (host.length < 7) return showErr(LANG.err_host || LANG.err_fields);
            if (pass.length < 6) return showErr(LANG.err_pass || LANG.err_fields);
            goStep(3);
        }
    }

    function goBack() {
        hideErr();
        goStep(currentStep - 1);
    }

    function updateIndicator() {
        for (let i = 1; i <= 3; i++) {
            const dot = document.getElementById(`sdot-${i}`);
            dot.classList.remove('active', 'done');
            if (i < currentStep) dot.classList.add('done');
            if (i === currentStep) dot.classList.add('active');
            if (i < 3) document.getElementById(`sline-${i}`).classList.toggle('done', i < currentStep);
        }
        document.getElementById('btnBack').style.display = currentStep > 1 ? 'flex' : 'none';
        document.getElementById('btnNext').style.display = currentStep < 3 ? 'flex' : 'none';
        document.getElementById('btnFinalize').style.display = currentStep === 3 ? 'flex' : 'none';
    }

    // ── Order card ────────────────────────────────────────────────────────────────

    function updateOrderCard() {
        setOC('oc-plan', selectedPlan ? selectedPlan.name : null);
        setOC('oc-os', selectedOS ? selectedOS.name : (selectedApp ? selectedApp.name : null));
        const h = selectedDur;
        const durLabel = h % 720 === 0 ? `${h/720} ${h/720 > 1 ? LANG.dur_months : LANG.dur_month}` : `${h} h`;
        setOC('oc-dur', durLabel);
        setOC('oc-pay', payMethod === 'balance' ? LANG.pay_balance : (payMethod === 'crypto' ? 'Crypto' : null));

        if (selectedPlan) {
            const mult        = getStMultiplier(selectedPlan, selectedDur);
            const basePrice   = parseFloat(selectedPlan.price) / 720 * selectedDur;
            const stPrice     = basePrice * mult;
            const setupTotal  = getSetupFeesTotal(selectedPlan);
            const rec         = getRecurringFees(selectedPlan);
            const recAmt      = stPrice * (rec.pct / 100) + rec.fixed;
            const totalPrice  = stPrice + setupTotal + recAmt;
            const feePct      = getStFeePercent(selectedPlan, selectedDur);

            const feeRow = document.getElementById('oc-fee-row');
            if (feePct > 0) {
                document.getElementById('oc-fee-label').textContent = `Cargo corto plazo (+${feePct}%)`;
                document.getElementById('oc-fee-value').textContent = `+$${(stPrice - basePrice).toFixed(2)}`;
                feeRow.style.display = 'flex';
            } else {
                feeRow.style.display = 'none';
            }

            const setupRow = document.getElementById('oc-setup-row');
            if (setupTotal > 0) {
                const list = getSetupFeesList(selectedPlan);
                const label = list.length === 1 ? list[0].name : 'Tarifas de activación';
                document.getElementById('oc-setup-label').textContent = label;
                document.getElementById('oc-setup-value').textContent = `+$${setupTotal.toFixed(2)}`;
                setupRow.style.display = 'flex';
            } else {
                setupRow.style.display = 'none';
            }

            const recurringRow = document.getElementById('oc-recurring-row');
            if (recAmt > 0) {
                document.getElementById('oc-recurring-label').textContent = rec.pct > 0 ? `Impuestos/cargos (+${rec.pct}%)` : 'Cargos recurrentes';
                document.getElementById('oc-recurring-value').textContent = `+$${recAmt.toFixed(2)}`;
                recurringRow.style.display = 'flex';
            } else {
                recurringRow.style.display = 'none';
            }

            const ipTotal   = selectedIPs * IP_PRICE;
            const ipRow     = document.getElementById('oc-ip-row');
            if (ipTotal > 0) {
                document.getElementById('oc-ip-label').textContent = `+${selectedIPs} IP${selectedIPs > 1 ? 's' : ''} adicional${selectedIPs > 1 ? 'es' : ''}`;
                document.getElementById('oc-ip-value').textContent = `+$${ipTotal.toFixed(2)}`;
                ipRow.style.display = 'flex';
            } else {
                ipRow.style.display = 'none';
            }

            document.getElementById('oc-total').textContent = '$' + (totalPrice + ipTotal).toFixed(2);
        }
    }

    function setOC(id, val) {
        const el = document.getElementById(id);
        if (val) { el.textContent = val; el.classList.remove('empty'); }
        else { el.textContent = '—'; el.classList.add('empty'); }
    }

    function updateReview() {
        const h = selectedDur;
        const dur = h % 720 === 0 ? `${h/720} ${h/720 > 1 ? LANG.dur_months : LANG.dur_month}` : `${h} h`;
        document.getElementById('r-plan').textContent = selectedPlan?.name || '—';
        document.getElementById('r-os').textContent = selectedOS?.name || selectedApp?.name || '—';
        document.getElementById('r-dur').textContent = dur;
        document.getElementById('r-host').textContent = document.getElementById('hostname').value || '—';

        const feeRow = document.getElementById('r-fee-row');
        const feePct = selectedPlan ? getStFeePercent(selectedPlan, h) : 0;
        if (feePct > 0) {
            const basePrice  = parseFloat(selectedPlan.price) / 720 * h;
            const surcharge  = basePrice * (feePct / 100);
            document.getElementById('r-fee-label').textContent = `Cargo corto plazo (+${feePct}%)`;
            document.getElementById('r-fee-value').textContent = `+$${surcharge.toFixed(2)}`;
            feeRow.style.display = 'flex';
        } else {
            feeRow.style.display = 'none';
        }

        const setupRow = document.getElementById('r-setup-row');
        const setupTotal = selectedPlan ? getSetupFeesTotal(selectedPlan) : 0;
        if (setupTotal > 0) {
            const list = getSetupFeesList(selectedPlan);
            const label = list.length === 1 ? list[0].name : 'Tarifas de activación';
            document.getElementById('r-setup-label').textContent = label;
            document.getElementById('r-setup-value').textContent = `+$${setupTotal.toFixed(2)}`;
            setupRow.style.display = 'flex';
        } else {
            setupRow.style.display = 'none';
        }

        const recurringRow = document.getElementById('r-recurring-row');
        const rec = selectedPlan ? getRecurringFees(selectedPlan) : { pct: 0, fixed: 0 };
        const baseForRec = selectedPlan ? parseFloat(selectedPlan.price) / 720 * h * getStMultiplier(selectedPlan, h) : 0;
        const recAmt = baseForRec * (rec.pct / 100) + rec.fixed;
        if (recAmt > 0) {
            document.getElementById('r-recurring-label').textContent = rec.pct > 0 ? `Impuestos/cargos (+${rec.pct}%)` : 'Cargos recurrentes';
            document.getElementById('r-recurring-value').textContent = `+$${recAmt.toFixed(2)}`;
            recurringRow.style.display = 'flex';
        } else {
            recurringRow.style.display = 'none';
        }

        // IPs
        const rIpRow = document.getElementById('r-ip-row');
        if (rIpRow) {
            if (selectedIPs > 0) {
                document.getElementById('r-ip-label').textContent = `+${selectedIPs} IP${selectedIPs > 1 ? 's' : ''} adicional${selectedIPs > 1 ? 'es' : ''}`;
                document.getElementById('r-ip-value').textContent = `+$${(selectedIPs * IP_PRICE).toFixed(2)}`;
                rIpRow.style.display = 'flex';
            } else {
                rIpRow.style.display = 'none';
            }
        }
    }

    // ── Plans ─────────────────────────────────────────────────────────────────────

    async function loadPlans() {
        try {
            const r = await fetch('../../api/plans/list');
            const d = await r.json();
            if (d.success) { plansData = d.data; renderPlans(); }
        } catch (e) {
            document.getElementById('plansGrid').innerHTML =
                `<div style="color:#ef4444;grid-column:1/-1;">${LANG.err_plans}</div>`;
        }
    }

    function renderPlans() {
        document.getElementById('plansGrid').innerHTML = plansData.map(p => {
            const setupTotal = getSetupFeesTotal(p);
            const setupHtml = setupTotal > 0
                ? `<div style="font-size:0.68rem;color:var(--text-muted);margin-top:2px;">+ $${setupTotal.toFixed(2)} setup</div>`
                : '';
            return `
        <div class="plan-card ${selectedPlan?.id == p.id ? 'selected' : ''}" data-pid="${p.id}" onclick="selPlan(${p.id})">
            <div class="plan-name">${p.name}</div>
            <div class="plan-specs">
                <div><i class="fas fa-microchip"></i> ${p.cpu} vCPU</div>
                <div><i class="fas fa-memory"></i> ${parseFloat(p.ram).toFixed(0)} GB RAM</div>
                <div><i class="fas fa-hdd"></i> ${p.disk} GB SSD</div>
            </div>
            <div class="plan-price" id="pp-${p.id}">$${(parseFloat(p.price) * getStMultiplier(p, selectedDur) / 720 * selectedDur).toFixed(2)}</div>
            ${setupHtml}
        </div>`;
        }).join('');
    }

    function selPlan(id) {
        selectedPlan = plansData.find(p => p.id == id);
        document.querySelectorAll('.plan-card').forEach(c => c.classList.toggle('selected', c.dataset.pid == id));
        updateOrderCard();
        loadOSOptions();
        // Auto-advance
        goStep(2);
    }

    function updatePrices() {
        plansData.forEach(p => {
            const el = document.getElementById(`pp-${p.id}`);
            if (el) el.textContent = '$' + (parseFloat(p.price) * getStMultiplier(p, selectedDur) / 720 * selectedDur).toFixed(2);
        });
        updateOrderCard();
    }

    // ── Extra IPs ─────────────────────────────────────────────────────────────────

    function selIPs(n, el) {
        selectedIPs = n;
        document.querySelectorAll('.ip-addon-btn').forEach(b => b.classList.remove('active'));
        if (el) el.classList.add('active');
        const note     = document.getElementById('ipAddonNote');
        const noteText = document.getElementById('ipAddonNoteText');
        if (n > 0) {
            noteText.textContent = LANG.ip_note_balance.replace('__TOTAL__', (n * IP_PRICE).toFixed(2));
            note.style.display = 'block';
        } else {
            note.style.display = 'none';
        }
        updateOrderCard();
    }

    // ── Duration ──────────────────────────────────────────────────────────────────

    function selDur(h, el) {
        selectedDur = h;
        document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('active'));
        if (el) el.classList.add('active');
        document.getElementById('customArea').classList.remove('open');
        document.getElementById('customToggle').classList.remove('on');
        updatePrices();
    }

    function toggleCustomDur() {
        const open = document.getElementById('customArea').classList.toggle('open');
        document.getElementById('customToggle').classList.toggle('on', open);
        if (open) {
            document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('customHours').focus();
        }
    }

    function applyCustomDur() {
        const val = parseInt(document.getElementById('customHours').value, 10);
        const inp = document.getElementById('customHours');
        if (!val || val < 1) { inp.style.borderColor = '#ef4444'; return; }
        inp.style.borderColor = '';
        selectedDur = val;
        document.getElementById('customToggle').style.borderColor = 'var(--primary)';
        document.getElementById('customToggle').style.color = 'var(--primary)';
        updatePrices();
    }

    // ── OS ────────────────────────────────────────────────────────────────────────

    const OS_FEATURED = [
        { matches: ['debian'],                  label: 'Debian',     descKey: 'feat_debian',     type: 'os' },
        { matches: ['ubuntu'],                  label: 'Ubuntu',     descKey: 'feat_ubuntu',     type: 'os' },
        { matches: ['windows 2025', 'windows'], label: 'Windows',    descKey: 'feat_windows',    type: 'os' },
        { matches: ['cloudpanel'],              label: 'CloudPanel', descKey: 'feat_cloudpanel', type: 'app' },
    ];
    let _osMoreOpen = false;

    function sortItems(list) {
        const getVer = n => { const m = n.match(/\d+(?:\.\d+)?/g); return m ? parseFloat(m[m.length - 1]) : 0; };
        const baseName = n => n.replace(/[\d.]+/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
        return [...list].sort((a, b) => {
            const ba = baseName(a.name), bb = baseName(b.name);
            if (ba !== bb) return ba.localeCompare(bb);
            return getVer(b.name) - getVer(a.name);
        });
    }

    function loadOSOptions() {
        if (!selectedPlan) return;
        const osList  = sortItems((selectedPlan.available_os_image_versions || []).map(o => ({ ...o, _type: 'os' })));
        const appList = sortItems((selectedPlan.available_applications || []).map(a => ({ ...a, _type: 'app' })));
        const all = [...osList, ...appList];

        const featured = [], more = [];
        const usedIds = new Set();

        OS_FEATURED.forEach(f => {
            const pool = f.type === 'app' ? appList : osList;
            const terms = f.matches || [f.match];
            let match = null;
            for (const term of terms) {
                match = pool.find(item => item.name.toLowerCase().includes(term) && !usedIds.has(item.id));
                if (match) break;
            }
            if (match) { usedIds.add(match.id); featured.push({ item: match, meta: f }); }
        });
        all.forEach(item => { if (!usedIds.has(item.id)) more.push(item); });

        // Render featured cards
        document.getElementById('osFeaturedGrid').innerHTML = featured.map(({ item, meta }) => {
            const { icon, color } = getOSIcon(item.name);
            const sel = (item._type === 'os' && selectedOS?.id == item.id) || (item._type === 'app' && selectedApp?.id == item.id);
            return `<div class="os-feat-card ${sel ? 'selected' : ''}" onclick="selItem('${item._type}',${item.id},'${escA(item.name)}')">
                <div class="os-feat-icon"><i class="${icon}" style="color:${color};"></i></div>
                <div class="os-feat-info">
                    <div class="os-feat-name">${meta.label}</div>
                    <div class="os-feat-desc">${LANG[meta.descKey] || ''}</div>
                </div>
            </div>`;
        }).join('');

        // Render expandable chips with OS/App dividers
        if (more.length) {
            const moreOS   = more.filter(item => item._type === 'os');
            const moreApps = more.filter(item => item._type === 'app');
            let html = '';
            if (moreOS.length) {
                html += `<div class="os-section-label">${LANG.os_section}</div>
                <div class="os-grid-compact">${moreOS.map(item => osChip(item, 'os')).join('')}</div>`;
            }
            if (moreApps.length) {
                html += `<div class="os-section-label">${LANG.apps_section}</div>
                <div class="os-grid-compact">${moreApps.map(item => osChip(item, 'app')).join('')}</div>`;
            }
            document.getElementById('osMoreGrid').innerHTML = html;
            document.getElementById('osMoreBtn').style.display = 'flex';
            document.getElementById('osMoreLabel').textContent = LANG.os_more;
        } else {
            document.getElementById('osMoreBtn').style.display = 'none';
            document.getElementById('osMoreSection').style.display = 'none';
        }
    }

    function toggleMoreOS() {
        _osMoreOpen = !_osMoreOpen;
        document.getElementById('osMoreSection').style.display = _osMoreOpen ? 'block' : 'none';
        document.getElementById('osMoreIcon').className = `fas fa-chevron-${_osMoreOpen ? 'up' : 'down'}`;
        document.getElementById('osMoreLabel').textContent = _osMoreOpen ? LANG.os_less : LANG.os_more;
    }

    function osChip(item, type) {
        const sel = (type === 'os' && selectedOS?.id == item.id) || (type === 'app' && selectedApp?.id == item.id);
        const { icon, color } = getOSIcon(item.name);
        return `<div class="os-chip ${sel ? 'selected' : ''}" onclick="selItem('${type}',${item.id},'${escA(item.name)}')">
            <i class="${icon}" style="color:${color};"></i>${item.name}
        </div>`;
    }

    function selItem(type, id, name) {
        if (type === 'os') { selectedOS = { id, name }; selectedApp = null; }
        else { selectedApp = { id, name }; selectedOS = null; }
        loadOSOptions();
        updateOrderCard();
    }

    function getOSIcon(n) {
        const l = n.toLowerCase();
        // Applications first (before OS, to avoid e.g. "ubuntu" matching "CloudPanel on Ubuntu")
        if (l.includes('cloudpanel'))  return { icon: 'fas fa-cloud',           color: '#5B8CFF' };
        if (l.includes('cloudron'))    return { icon: 'fas fa-cube',             color: '#00BCD4' };
        if (l.includes('wordpress'))   return { icon: 'fab fa-wordpress',        color: '#21759B' };
        if (l.includes('cpanel'))      return { icon: 'fas fa-server',           color: '#FF6C2C' };
        if (l.includes('plesk'))       return { icon: 'fas fa-desktop',          color: '#52BBE6' };
        if (l.includes('gitlab'))      return { icon: 'fab fa-gitlab',           color: '#FC6D26' };
        if (l.includes('jenkins'))     return { icon: 'fab fa-jenkins',          color: '#D33833' };
        if (l.includes('nextcloud'))   return { icon: 'fas fa-cloud-upload-alt', color: '#0082C9' };
        if (l.includes('jitsi'))       return { icon: 'fas fa-video',            color: '#97D7A7' };
        if (l.includes('openvpn'))     return { icon: 'fas fa-shield-alt',       color: '#EA7E20' };
        if (l.includes('nginx'))       return { icon: 'fas fa-stream',           color: '#009639' };
        if (l.includes('lamp'))        return { icon: 'fas fa-layer-group',      color: '#F5A623' };
        if (l.includes('mariadb'))     return { icon: 'fas fa-database',         color: '#C0765A' };
        // OS
        if (l.includes('windows')) return { icon: 'fab fa-windows', color: '#0078D4' };
        if (l.includes('ubuntu'))  return { icon: 'fab fa-ubuntu',  color: '#E95420' };
        if (l.includes('debian'))  return { icon: 'fab fa-linux',   color: '#D70A53' };
        if (l.includes('centos'))  return { icon: 'fab fa-centos',  color: '#932279' };
        if (l.includes('fedora'))  return { icon: 'fab fa-fedora',  color: '#3C6EB4' };
        if (l.includes('rocky'))   return { icon: 'fab fa-redhat',  color: '#10B981' };
        if (l.includes('alma'))    return { icon: 'fab fa-redhat',  color: '#F97316' };
        if (l.includes('redhat') || l.includes('rhel')) return { icon: 'fab fa-redhat', color: '#EE0000' };
        if (l.includes('suse') || l.includes('opensuse')) return { icon: 'fab fa-suse', color: '#73BA25' };
        return { icon: 'fab fa-linux', color: '#9CA3AF' };
    }

    // ── Balance ───────────────────────────────────────────────────────────────────

    async function loadBalance() {
        try {
            const r = await fetch('../../api/users/balance');
            const d = await r.json();
            if (d.success) {
                userBalance = parseFloat(d.balance);
                document.getElementById('balanceDisplay').textContent = '$' + userBalance.toFixed(2) + ' USD';
            }
        } catch (e) { document.getElementById('balanceDisplay').textContent = 'N/D'; }
    }

    // ── Payment ───────────────────────────────────────────────────────────────────

    function selPay(method) {
        payMethod = method;
        document.getElementById('pc-balance').classList.toggle('selected', method === 'balance');
        document.getElementById('pc-crypto').classList.toggle('selected', method === 'crypto');
        document.getElementById('cryptoArea').style.display = method === 'crypto' ? 'block' : 'none';
        const cryptoNote = document.getElementById('ipCryptoNote');
        if (cryptoNote) cryptoNote.style.display = (method === 'crypto' && selectedIPs > 0) ? 'block' : 'none';
        const btn = document.getElementById('btnFinalize');
        btn.innerHTML = '<i class="fas fa-check-circle"></i> ' + (method === 'crypto' ? LANG.btn_invoice : LANG.btn_submit);
        updateOrderCard();
    }

    // ── Coins ─────────────────────────────────────────────────────────────────────

    async function loadUserPref() {
        try {
            const r = await fetch('../../api/users/preferences');
            const d = await r.json();
            if (d.success && d.data.preferred_currency) {
                const [sym, net] = d.data.preferred_currency.split(':', 2);
                if (sym && net) { userPref = { symbol: sym, network: net }; renderCoins(); }
            }
        } catch (_) {}
    }

    async function loadCoins() {
        try {
            const r = await fetch('../../api/transactions/currencies');
            const raw = await r.json();
            let list = raw.data || raw;
            if (list && !Array.isArray(list)) list = Object.values(list);
            if (!Array.isArray(list) || !list.length) {
                document.getElementById('coinGrid').innerHTML =
                    `<div style="color:#ef4444;grid-column:1/-1;font-size:0.75rem;">${LANG.no_coins}</div>`;
                return;
            }
            coinsData = list;
            renderCoins();
        } catch (e) {
            document.getElementById('coinGrid').innerHTML =
                `<div style="color:#ef4444;grid-column:1/-1;font-size:0.75rem;">${LANG.err_conn}</div>`;
        }
    }

    function renderCoins() {
        const sorted = [
            ...coinsData.filter(c => TOP_COINS.includes(c.symbol || c.currency)),
            ...coinsData.filter(c => !TOP_COINS.includes(c.symbol || c.currency)),
        ];
        document.getElementById('coinGrid').innerHTML = sorted.map(c => {
            const sym  = c.symbol || c.currency || '';
            const logo = COIN_LOGOS[sym] || 'BTC.png';
            const isPref = userPref && userPref.symbol === sym;
            return `<div class="vps-coin-btn crypto-${sym.toLowerCase()}${isPref ? ' pref-highlight' : ''}" data-sym="${sym}" onclick="selCoin('${sym}')" title="${isPref ? '★ Preferred' : sym}">
            ${isPref ? '<span class="pref-star">★</span>' : ''}
            <img src="/assets/img/crypto/${logo}" alt="${sym}" style="width:20px;height:20px;object-fit:contain;">
            <span style="font-weight:700;font-size:0.72rem;color:var(--text-light);">${sym}</span>
        </div>`;
        }).join('');
    }

    function selCoin(sym) {
        selCurrency = { symbol: sym, network: null };
        document.querySelectorAll('.vps-coin-btn').forEach(b =>
            b.classList.toggle('selected', b.dataset.sym === sym));
        renderNetworks(sym);
    }

    function renderNetworks(sym) {
        const coin = coinsData.find(c => c.symbol === sym || c.currency === sym);
        const netArea = document.getElementById('netArea');
        const netGrid = document.getElementById('netGrid');
        netGrid.innerHTML = '';
        const nets = coin?.networks
            ? (Array.isArray(coin.networks) ? coin.networks : Object.values(coin.networks))
            : [];
        if (!nets.length) { netArea.style.display = 'none'; selCurrency.network = sym; return; }
        netArea.style.display = 'block';
        let prefBtn = null;
        nets.forEach(net => {
            const btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'vps-net-btn';
            const cleanName = (net.name || net.network || '').replace(/\s*\(.*\)\s*/, '').trim();
            let acronym = '';
            if (net.keys?.length) {
                const f = net.keys.find(k => k.length >= 3 && k.length <= 6 && k === k.toUpperCase());
                if (f) acronym = f;
            }
            if (!acronym && (net.name || net.network || '').includes('(')) {
                const m = (net.name || net.network).match(/\(([^)]+)\)/);
                if (m) acronym = m[1];
            }
            const isPref = userPref && userPref.symbol === sym && userPref.network === net.network;
            if (isPref) btn.classList.add('pref-highlight');
            btn.innerHTML = `${isPref ? '<span class="pref-star">★</span> ' : ''}<span>${cleanName}</span>${acronym ? `<span class="net-acronym">${acronym}</span>` : ''}`;
            btn.onclick = () => {
                document.querySelectorAll('.vps-net-btn').forEach(b => b.classList.remove('selected-net'));
                btn.classList.add('selected-net');
                selCurrency.network = net.network;
            };
            netGrid.appendChild(btn);
            if (isPref) prefBtn = btn;
        });
        if (prefBtn) prefBtn.click();
        else if (nets.length === 1) netGrid.firstChild?.click();
    }

    // ── Finalize ──────────────────────────────────────────────────────────────────

    async function finalizeOrder() {
        hideErr();
        if (!payMethod) return showErr(LANG.err_pay);
        if (payMethod === 'crypto' && (!selCurrency || !selCurrency.network))
            return showErr(LANG.err_coin);

        const btn = document.getElementById('btnFinalize');
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG.processing}`;

        const body = {
            plan_id: selectedPlan.id,
            duration: selectedDur,
            os_image_id: selectedOS?.id || null,
            application_id: selectedApp?.id || null,
            name_server: document.getElementById('hostname').value.trim(),
            password: document.getElementById('password').value.trim(),
            payment_method: payMethod,
        };

        try {
            const r1 = await fetch('../../api/orders/create', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body),
            });
            const d1 = await r1.json();
            if (!d1.success) throw new Error(d1.message);

            if (payMethod === 'balance') {
                if (selectedIPs > 0 && d1.data.vps_id) {
                    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Añadiendo ${selectedIPs} IP${selectedIPs > 1 ? 's' : ''}...`;
                    for (let i = 0; i < selectedIPs; i++) {
                        try {
                            await fetch('../../api/orders/create_addon_order', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ vps_id: d1.data.vps_id }),
                            });
                        } catch (_) {}
                    }
                }
                window.location.href = '../vps';
            } else {
                const r2 = await fetch('../../api/orders/create_invoice', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: d1.data.order_id,
                        name_server: body.name_server,
                        password: body.password,
                        payment_currency: selCurrency.symbol,
                        network: selCurrency.network,
                    }),
                });
                const d2 = await r2.json();
                if (d2.success) {
                    window.location.href = '../vps_invoice.php?id=' + d2.data.local_id;
                } else throw new Error(d2.message);
            }
        } catch (e) {
            showErr(e.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> ' + (payMethod === 'crypto' ? LANG.btn_invoice : LANG.btn_submit);
        }
    }

    function showErr(msg) {
        const e = document.getElementById('errBox');
        e.textContent = msg; e.style.display = 'block';
        e.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function hideErr() { document.getElementById('errBox').style.display = 'none'; }

    function generateHostname() {
        const c = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let r = 'server-';
        for (let i = 0; i < 7; i++) r += c[~~(Math.random() * c.length)];
        document.getElementById('hostname').value = r;
    }

    function generatePassword() {
        const lc = 'abcdefghijklmnopqrstuvwxyz', uc = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            nb = '0123456789', sy = '!@#$%&*', all = lc + uc + nb + sy;
        let p = lc[~~(Math.random() * lc.length)] + uc[~~(Math.random() * uc.length)]
            + nb[~~(Math.random() * nb.length)] + sy[~~(Math.random() * sy.length)];
        for (let i = 4; i < 12; i++) p += all[~~(Math.random() * all.length)];
        document.getElementById('password').value = p.split('').sort(() => Math.random() - .5).join('');
    }
    function escA(s) { return String(s).replace(/'/g, "\\'").replace(/"/g, '&quot;'); }
</script>

<?php include __DIR__ . '/../footer.php'; ?>