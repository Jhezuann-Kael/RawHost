<?php
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../includes/lang_loader.php';

$server_id = intval($_GET['id'] ?? 0);
if (!$server_id) {
    header('Location: ../vps');
    exit;
}

$pageTitle = ($lang['man_modal_opt_extend'] ?? 'Extender Duración') . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="../css/vps.css?v=' . filemtime(__DIR__ . '/../css/vps.css') . '">
    <link rel="stylesheet" href="../css/pay_vps.css?v=' . filemtime(__DIR__ . '/../css/pay_vps.css') . '">
';

include __DIR__ . '/../header.php';
?>

<main class="main-content">
    <div style="max-width:1060px;margin:0 auto;padding:14px 16px 4px;">
        <h1 style="margin:0 0 2px;font-size:1.5rem;"><?php echo $lang['man_modal_opt_extend'] ?? 'Extender Duración'; ?></h1>
        <p style="color:var(--text-muted);margin:0;font-size:0.85rem;" id="server-subtitle">
            <i class="fas fa-spinner fa-spin"></i> <?php echo $lang['dash_loading'] ?? 'Cargando...'; ?>
        </p>
    </div>

    <div class="buy-layout">

        <!-- ══ LEFT: steps ══ -->
        <div>
            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step-dot active" id="sdot-1">
                    <div class="step-dot-circle">1</div>
                    <div class="step-dot-label"><?php echo $lang['vps_label_duration'] ?? 'Duración'; ?></div>
                </div>
                <div class="step-line" id="sline-1"></div>
                <div class="step-dot" id="sdot-2">
                    <div class="step-dot-circle">2</div>
                    <div class="step-dot-label"><?php echo $lang['vps_buy_step4'] ?? 'Pago'; ?></div>
                </div>
            </div>

            <!-- Step 1: Duration -->
            <div class="step-content active" id="step-1">
                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_label_duration'] ?? 'Duración'; ?></div>
                    <div class="dur-wrap">
                        <div class="dur-btn" data-h="24" onclick="selDur(24,this)"><?php echo $lang['vps_js_hour_24'] ?? '24 Horas'; ?></div>
                        <div class="dur-btn" data-h="168" onclick="selDur(168,this)">7 <?php echo $lang['man_modal_opt_days'] ?? 'Días'; ?></div>
                        <div class="dur-btn" data-h="720" onclick="selDur(720,this)"><?php echo $lang['vps_js_month_1'] ?? '1 Mes'; ?></div>
                        <div class="dur-btn" data-h="1440" onclick="selDur(1440,this)"><?php echo $lang['vps_js_month_2'] ?? '2 Meses'; ?></div>
                        <div class="dur-btn" data-h="2160" onclick="selDur(2160,this)"><?php echo $lang['vps_js_month_3'] ?? '3 Meses'; ?></div>
                        <div class="dur-custom-btn" id="customToggle" onclick="toggleCustomDur()" title="<?php echo $lang['buy_custom_title'] ?? 'Personalizado'; ?>">
                            <i class="fas fa-sliders-h" style="font-size:0.8rem;"></i>
                        </div>
                    </div>
                    <div class="dur-custom-area" id="customArea">
                        <input type="number" id="customHours" class="form-control" min="1" step="1" placeholder="<?php echo $lang['vps_label_hours'] ?? 'Horas'; ?>">
                        <span><?php echo $lang['vps_label_hours'] ?? 'Horas'; ?> <small style="opacity:.5;"><?php echo $lang['buy_custom_min'] ?? '(mín. 1h)'; ?></small></span>
                        <button class="btn btn-outline" style="padding:5px 12px;font-size:0.78rem;" onclick="applyCustomDur()">OK</button>
                    </div>
                </div>

                <!-- Price estimate -->
                <div class="sblock" id="priceBlock" style="display:none;">
                    <div class="slabel"><?php echo $lang['man_info_price'] ?? 'Precio'; ?></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;"><?php echo $lang['man_modal_opt_duration'] ?? 'Duración'; ?></span>
                        <span style="font-weight:600;font-size:0.82rem;" id="pe-duration">—</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;"><?php echo $lang['man_info_price'] ?? 'Precio plan'; ?></span>
                        <span style="font-weight:600;" id="pe-plan">—</span>
                    </div>
                    <div id="pe-addons-wrap"></div>
                    <div id="pe-fee-row" style="display:none;justify-content:space-between;align-items:center;margin-bottom:7px;padding:6px 8px;background:rgba(243,156,18,0.08);border-radius:6px;border:1px solid rgba(243,156,18,0.2);">
                        <span style="color:#f39c12;font-size:0.82rem;" id="pe-fee-label">Cargo corto plazo</span>
                        <span style="font-weight:600;color:#f39c12;" id="pe-fee">—</span>
                    </div>
                    <div id="pe-recurring-row" style="display:none;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;" id="pe-recurring-label">—</span>
                        <span style="font-weight:600;" id="pe-recurring">—</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid rgba(255,255,255,0.07);">
                        <span style="font-weight:700;">Total</span>
                        <span style="font-weight:800;color:var(--primary);font-size:1.1rem;" id="pe-total">$0.00</span>
                    </div>
                    <div style="margin-top:5px;font-size:0.71rem;color:var(--text-muted);text-align:right;">
                        <span id="pe-hours">0</span> h × $<span id="pe-hourly">0.0000</span>/h
                    </div>
                </div>

                <div class="err-box" id="errBox1"></div>
            </div>

            <!-- Step 2: Payment + Review -->
            <div class="step-content" id="step-2">
                <div class="sblock">
                    <div class="slabel"><?php echo $lang['vps_buy_step4'] ?? 'Método de pago'; ?></div>
                    <div class="pay-row">
                        <div class="pay-card" id="pc-balance" onclick="selPay('balance')">
                            <div class="pay-icon"><i class="fas fa-wallet"></i></div>
                            <div class="pay-title"><?php echo $lang['vps_pay_balance_title'] ?? 'Saldo de cuenta'; ?></div>
                            <div class="pay-sub" id="balanceDisplay">...</div>
                        </div>
                        <div class="pay-card" id="pc-crypto" onclick="selPay('crypto')">
                            <div class="pay-icon">₿</div>
                            <div class="pay-title"><?php echo $lang['vps_pay_crypto_title'] ?? 'Pagar con cripto'; ?></div>
                            <div class="pay-sub"><?php echo $lang['vps_pay_crypto_sub'] ?? 'Factura directa'; ?></div>
                        </div>
                    </div>

                    <div id="cryptoArea" style="display:none;margin-top:14px;animation:fadeIn 0.2s;">
                        <div class="slabel" style="margin-bottom:8px;"><?php echo $lang['buy_coin_label'] ?? 'Criptomoneda'; ?></div>
                        <div class="coin-grid-sm" id="coinGrid">
                            <div style="color:var(--text-muted);font-size:0.75rem;grid-column:1/-1;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div id="netArea" style="display:none;">
                            <div class="slabel" style="margin:10px 0 6px;"><?php echo $lang['buy_net_label'] ?? 'Red'; ?></div>
                            <div class="net-row" id="netGrid"></div>
                        </div>
                    </div>
                </div>

                <div class="sblock">
                    <div class="slabel"><?php echo $lang['buy_summary_title'] ?? 'Resumen'; ?></div>
                    <div class="review-row">
                        <span class="rev-label"><?php echo $lang['man_info_plan'] ?? 'Plan'; ?></span>
                        <span class="rev-value" id="r-plan">—</span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label"><?php echo $lang['buy_order_duration'] ?? 'Duración'; ?></span>
                        <span><span class="rev-value" id="r-dur">—</span>
                            <span class="rev-edit" onclick="goStep(1)"><i class="fas fa-pencil-alt"></i> <?php echo $lang['buy_change'] ?? 'Cambiar'; ?></span>
                        </span>
                    </div>
                    <div class="review-row" id="r-fee-row" style="display:none;">
                        <span class="rev-label" id="r-fee-label"></span>
                        <span class="rev-value" id="r-fee-value" style="color:#f39c12;"></span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label"><?php echo $lang['buy_order_total'] ?? 'Total'; ?></span>
                        <span class="rev-value" id="r-total" style="color:var(--primary);">—</span>
                    </div>
                </div>

                <div class="err-box" id="errBox2"></div>
            </div>
        </div>

        <!-- ══ RIGHT: sticky order summary ══ -->
        <div>
            <div class="order-card">
                <div class="order-card-head"><?php echo $lang['buy_order_title'] ?? 'Tu pedido'; ?></div>
                <div class="order-card-body">
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['man_info_plan'] ?? 'Servidor'; ?></span>
                        <span class="or-value empty" id="oc-server">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['buy_order_duration'] ?? 'Duración'; ?></span>
                        <span class="or-value empty" id="oc-dur">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['buy_order_payment'] ?? 'Pago'; ?></span>
                        <span class="or-value empty" id="oc-pay">—</span>
                    </div>
                </div>
                <div class="order-fee-row" id="oc-fee-row" style="display:none;">
                    <span class="or-label" id="oc-fee-label"></span>
                    <span class="or-value" id="oc-fee-value" style="color:#f39c12;font-weight:600;"></span>
                </div>
                <div class="order-card-total">
                    <span class="total-label"><?php echo $lang['buy_order_total'] ?? 'Total'; ?></span>
                    <span class="total-price" id="oc-total">$0.00</span>
                </div>
                <div class="order-card-btns">
                    <button class="btn btn-manage btn-step-next" id="btnNext" onclick="goNext()">
                        <?php echo $lang['buy_btn_continue'] ?? 'Continuar'; ?> <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn btn-manage btn-step-next" id="btnFinalize" style="display:none;" onclick="finalizeOrder()">
                        <i class="fas fa-check-circle"></i> <span id="btnFinalizeText"><?php echo $lang['man_modal_opt_btn_confirm'] ?? 'Confirmar'; ?></span>
                    </button>
                    <button class="btn-step-back" id="btnBack" style="display:none;" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> <?php echo $lang['vps_btn_back'] ?? 'Atrás'; ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    const RENEW_LANG = <?php echo json_encode([
        'err_dur'       => $lang['man_modal_opt_duration'] ?? 'Selecciona una duración',
        'err_pay'       => $lang['buy_err_pay'] ?? 'Selecciona un método de pago',
        'err_coin'      => $lang['buy_err_coin_net'] ?? 'Selecciona criptomoneda y red',
        'err_conn'      => $lang['tx_err_connection'] ?? 'Error de conexión',
        'processing'    => $lang['man_js_processing'] ?? 'Procesando...',
        'pay_balance'   => $lang['buy_pay_balance'] ?? 'Saldo',
        'btn_invoice'   => $lang['vps_btn_invoice'] ?? 'Generar Factura',
        'btn_confirm'   => $lang['man_modal_opt_btn_confirm'] ?? 'Confirmar',
        'dur_month'     => $lang['buy_dur_month'] ?? 'mes',
        'dur_months'    => $lang['buy_dur_months'] ?? 'meses',
        'no_coins'      => $lang['buy_no_coins'] ?? 'Sin criptomonedas disponibles',
        'min_crypto'    => 'El monto mínimo para pago con cripto es $1.00 USD',
        'err_dur_select'=> 'Selecciona una duración antes de continuar',
    ]); ?>;

    const SERVER_ID = <?php echo $server_id; ?>;

    const COIN_LOGOS = {
        USDT:'USDT_Logo.png', BTC:'BTC.png', ETH:'ETH.png', TRX:'TRX.png',
        LTC:'LTC.png', BNB:'BNB.png', USDC:'USDC.png', DOGE:'DODGE.png',
        POL:'POL.png', SOL:'SOL.png', SHIB:'SHIB.png', TON:'TON.png',
        XMR:'XMR.png', DAI:'DAI.png', BCH:'BCH.png', NOT:'NOT.png',
        DOGS:'DOGS.png', XRP:'XRP.png'
    };
    const TOP_COINS = ['USDT','BTC','ETH','TRX','LTC','BNB'];

    let currentStep = 1;
    let selectedDur = null;
    let payMethod = null;
    let selCurrency = null;
    let coinsData = [];
    let userPref = null;
    let currentAmount = 0;
    let serverName = '';
    let planName = '';

    document.addEventListener('DOMContentLoaded', () => {
        loadServerInfo();
        loadBalance();
        loadCoins();
        loadUserPref();
    });

    // ── Navigation ──────────────────────────────────────────────────────────────

    function goStep(n) {
        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep = n;
        document.getElementById(`step-${currentStep}`).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        updateIndicator();
        updateOrderCard();
        if (n === 2) updateReview();
    }

    function goNext() {
        hideErr();
        if (currentStep === 1) {
            if (!selectedDur) return showErr('errBox1', RENEW_LANG.err_dur_select);
            goStep(2);
        }
    }

    function goBack() {
        hideErr();
        goStep(currentStep - 1);
    }

    function updateIndicator() {
        for (let i = 1; i <= 2; i++) {
            const dot = document.getElementById(`sdot-${i}`);
            dot.classList.remove('active', 'done');
            if (i < currentStep) dot.classList.add('done');
            if (i === currentStep) dot.classList.add('active');
        }
        if (document.getElementById('sline-1'))
            document.getElementById('sline-1').classList.toggle('done', currentStep > 1);

        document.getElementById('btnBack').style.display = currentStep > 1 ? 'flex' : 'none';
        document.getElementById('btnNext').style.display = currentStep < 2 ? 'flex' : 'none';
        document.getElementById('btnFinalize').style.display = currentStep === 2 ? 'flex' : 'none';
    }

    // ── Order card ──────────────────────────────────────────────────────────────

    function updateOrderCard() {
        setOC('oc-server', serverName || null);
        setOC('oc-dur', selectedDur ? durLabel(selectedDur) : null);
        setOC('oc-pay', payMethod === 'balance' ? RENEW_LANG.pay_balance : (payMethod === 'crypto' ? 'Crypto' : null));
        document.getElementById('oc-total').textContent = '$' + currentAmount.toFixed(2);

        const feeLabel = document.getElementById('pe-fee-label')?.textContent || '';
        const feeVal   = document.getElementById('pe-fee')?.textContent || '';
        const feeRow   = document.getElementById('oc-fee-row');
        const feeVisible = document.getElementById('pe-fee-row')?.style.display !== 'none' && feeLabel;
        if (feeVisible) {
            document.getElementById('oc-fee-label').textContent = feeLabel;
            document.getElementById('oc-fee-value').textContent = feeVal;
            feeRow.style.display = 'flex';
        } else {
            feeRow.style.display = 'none';
        }
    }

    function setOC(id, val) {
        const el = document.getElementById(id);
        if (val) { el.textContent = val; el.classList.remove('empty'); }
        else { el.textContent = '—'; el.classList.add('empty'); }
    }

    function durLabel(h) {
        if (h % 720 === 0) {
            const m = h / 720;
            return `${m} ${m > 1 ? RENEW_LANG.dur_months : RENEW_LANG.dur_month}`;
        }
        if (h % 24 === 0) return `${h/24} días`;
        return `${h} h`;
    }

    function updateReview() {
        document.getElementById('r-plan').textContent = planName || '—';
        document.getElementById('r-dur').textContent = selectedDur ? durLabel(selectedDur) : '—';
        document.getElementById('r-total').textContent = '$' + currentAmount.toFixed(2);

        const feeLabel   = document.getElementById('pe-fee-label')?.textContent || '';
        const feeVal     = document.getElementById('pe-fee')?.textContent || '';
        const rFeeRow    = document.getElementById('r-fee-row');
        const feeVisible = document.getElementById('pe-fee-row')?.style.display !== 'none' && feeLabel;
        if (feeVisible) {
            document.getElementById('r-fee-label').textContent = feeLabel;
            document.getElementById('r-fee-value').textContent = feeVal;
            rFeeRow.style.display = 'flex';
        } else {
            rFeeRow.style.display = 'none';
        }
    }

    // ── Server info ─────────────────────────────────────────────────────────────

    async function loadServerInfo() {
        try {
            const r = await fetch(`/api/servers/detail?id=${SERVER_ID}`);
            const d = await r.json();
            if (d.success && d.data) {
                serverName = d.data.name || '';
                planName   = d.data.plan || '';
                document.getElementById('server-subtitle').textContent =
                    serverName + (planName ? ' — ' + planName : '');
                updateOrderCard();
            } else {
                document.getElementById('server-subtitle').textContent = 'Servidor no encontrado';
            }
        } catch (e) {
            document.getElementById('server-subtitle').textContent = '';
        }
    }

    // ── Duration ─────────────────────────────────────────────────────────────────

    function selDur(h, el) {
        selectedDur = h;
        document.querySelectorAll('.dur-btn').forEach(b => b.classList.remove('active'));
        if (el) el.classList.add('active');
        document.getElementById('customArea').classList.remove('open');
        document.getElementById('customToggle').classList.remove('on');
        updateOrderCard();
        fetchPriceEstimate(h);
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
        updateOrderCard();
        fetchPriceEstimate(val);
    }

    async function fetchPriceEstimate(hours) {
        const block = document.getElementById('priceBlock');
        block.style.display = 'none';
        if (!hours || hours < 1) return;

        try {
            const r = await fetch(`/api/orders/calculate_price?server_id=${SERVER_ID}&duration=${hours}`);
            const d = await r.json();
            if (!d.success) return;

            const p = d.data;
            currentAmount = parseFloat(p.total_amount) || 0;

            document.getElementById('pe-duration').textContent = durLabel(hours);
            document.getElementById('pe-plan').textContent = '$' + parseFloat(p.plan_price).toFixed(2);
            document.getElementById('pe-total').textContent = '$' + currentAmount.toFixed(2);
            document.getElementById('pe-hours').textContent = p.duration;
            document.getElementById('pe-hourly').textContent = parseFloat(p.hourly_rate).toFixed(4);

            const addonsWrap = document.getElementById('pe-addons-wrap');
            const breakdown  = p.addons_breakdown || [];
            if (breakdown.length > 0) {
                const TYPE_LABEL = { IPV4: 'IP adicional', IPV6: 'IPv6 adicional', STORAGE: 'Almacenamiento' };
                addonsWrap.innerHTML = breakdown.map(a => {
                    const label = TYPE_LABEL[a.type] || a.type;
                    return `<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;">${a.count}× ${label} <span style="opacity:.5;">($${a.unit_price.toFixed(2)}/u)</span></span>
                        <span style="font-weight:600;">$${a.total.toFixed(2)}</span>
                    </div>`;
                }).join('');
            } else {
                addonsWrap.innerHTML = '';
            }

            const feeRow  = document.getElementById('pe-fee-row');
            const feePct  = parseFloat(p.short_term_fee_pct || 0);
            const feeAmt  = parseFloat(p.short_term_fee_amt || 0);
            if (feePct > 0) {
                document.getElementById('pe-fee-label').textContent = `Cargo corto plazo (+${feePct}%)`;
                document.getElementById('pe-fee').textContent = `+$${feeAmt.toFixed(2)}`;
                feeRow.style.display = 'flex';
            } else {
                feeRow.style.display = 'none';
            }

            const recurringRow = document.getElementById('pe-recurring-row');
            const recurringPct = parseFloat(p.recurring_fee_pct || 0);
            const recurringAmt = parseFloat(p.recurring_fee_amt || 0);
            if (recurringAmt > 0) {
                document.getElementById('pe-recurring-label').textContent = recurringPct > 0 ? `Impuestos/cargos (+${recurringPct}%)` : 'Cargos recurrentes';
                document.getElementById('pe-recurring').textContent = `+$${recurringAmt.toFixed(2)}`;
                recurringRow.style.display = 'flex';
            } else {
                recurringRow.style.display = 'none';
            }

            block.style.display = 'block';
            updateOrderCard();
            if (currentStep === 2) updateReview();
        } catch (e) {
            block.style.display = 'none';
        }
    }

    // ── Balance ──────────────────────────────────────────────────────────────────

    async function loadBalance() {
        try {
            const r = await fetch('/api/users/balance');
            const d = await r.json();
            if (d.success) {
                document.getElementById('balanceDisplay').textContent =
                    '$' + parseFloat(d.balance).toFixed(2) + ' USD';
            }
        } catch (e) {
            document.getElementById('balanceDisplay').textContent = 'N/D';
        }
    }

    // ── Payment ──────────────────────────────────────────────────────────────────

    function selPay(method) {
        payMethod = method;
        document.getElementById('pc-balance').classList.toggle('selected', method === 'balance');
        document.getElementById('pc-crypto').classList.toggle('selected', method === 'crypto');
        document.getElementById('cryptoArea').style.display = method === 'crypto' ? 'block' : 'none';

        const txt = document.getElementById('btnFinalizeText');
        txt.textContent = method === 'crypto' ? RENEW_LANG.btn_invoice : RENEW_LANG.btn_confirm;
        updateOrderCard();
    }

    // ── Coins ─────────────────────────────────────────────────────────────────────

    async function loadUserPref() {
        try {
            const r = await fetch('/api/users/preferences');
            const d = await r.json();
            if (d.success && d.data.preferred_currency) {
                const [sym, net] = d.data.preferred_currency.split(':', 2);
                if (sym && net) { userPref = { symbol: sym, network: net }; renderCoins(); }
            }
        } catch (_) {}
    }

    async function loadCoins() {
        try {
            const r = await fetch('/api/transactions/currencies');
            const raw = await r.json();
            let list = raw.data || raw;
            if (list && !Array.isArray(list)) list = Object.values(list);
            if (!Array.isArray(list) || !list.length) {
                document.getElementById('coinGrid').innerHTML =
                    `<div style="color:#ef4444;grid-column:1/-1;font-size:0.75rem;">${RENEW_LANG.no_coins}</div>`;
                return;
            }
            coinsData = list;
            renderCoins();
        } catch (e) {
            document.getElementById('coinGrid').innerHTML =
                `<div style="color:#ef4444;grid-column:1/-1;font-size:0.75rem;">${RENEW_LANG.err_conn}</div>`;
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

    // ── Finalize ─────────────────────────────────────────────────────────────────

    async function finalizeOrder() {
        hideErr();
        if (!payMethod) return showErr('errBox2', RENEW_LANG.err_pay);
        if (payMethod === 'crypto') {
            if (!selCurrency || !selCurrency.network) return showErr('errBox2', RENEW_LANG.err_coin);
            if (currentAmount > 0 && currentAmount < 1) return showErr('errBox2', RENEW_LANG.min_crypto);
        }

        const btn = document.getElementById('btnFinalize');
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${RENEW_LANG.processing}`;

        const payload = {
            server_id:      SERVER_ID,
            action:         'renew',
            value:          selectedDur,
            payment_method: payMethod,
        };

        try {
            if (payMethod === 'crypto') {
                payload.payment_currency = selCurrency.symbol;
                payload.network          = selCurrency.network;
            }

            const r1 = await fetch('/api/orders/process', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d1 = await r1.json();
            if (!d1.success) throw new Error(d1.message);

            if (payMethod === 'balance') {
                window.location.href = `../manage/index?id=${SERVER_ID}`;
            } else {
                const r2 = await fetch('/api/orders/create_action_invoice', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id:         d1.data.order_id,
                        action:           'renew',
                        payment_currency: selCurrency.symbol,
                        network:          selCurrency.network,
                    }),
                });
                const d2 = await r2.json();
                if (!d2.success) throw new Error(d2.message);
                window.location.href = `../vps_invoice?id=${d2.data.local_id}`;
            }
        } catch (e) {
            showErr('errBox2', e.message);
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-check-circle"></i> <span id="btnFinalizeText">${payMethod === 'crypto' ? RENEW_LANG.btn_invoice : RENEW_LANG.btn_confirm}</span>`;
        }
    }

    function showErr(boxId, msg) {
        const e = document.getElementById(boxId);
        e.textContent = msg; e.style.display = 'block';
        e.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function hideErr() {
        ['errBox1','errBox2'].forEach(id => {
            const e = document.getElementById(id);
            if (e) e.style.display = 'none';
        });
    }
</script>

<?php include __DIR__ . '/../footer.php'; ?>
