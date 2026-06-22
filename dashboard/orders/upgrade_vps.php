<?php
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../includes/lang_loader.php';

$server_id = intval($_GET['id'] ?? 0);
if (!$server_id) {
    header('Location: ../vps');
    exit;
}

$pageTitle = ($lang['man_modal_opt_upgrade'] ?? 'Upgrade de Plan') . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="../css/vps.css?v=' . filemtime(__DIR__ . '/../css/vps.css') . '">
    <link rel="stylesheet" href="../css/pay_vps.css?v=' . filemtime(__DIR__ . '/../css/pay_vps.css') . '">
';

include __DIR__ . '/../header.php';
?>

<main class="main-content">
    <div style="max-width:1060px;margin:0 auto;padding:14px 16px 4px;">
        <h1 style="margin:0 0 2px;font-size:1.5rem;"><?php echo $lang['man_modal_opt_upgrade'] ?? 'Upgrade de Plan'; ?></h1>
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
                    <div class="step-dot-label"><?php echo $lang['vps_label_select_plan'] ?? 'Plan'; ?></div>
                </div>
                <div class="step-line" id="sline-1"></div>
                <div class="step-dot" id="sdot-2">
                    <div class="step-dot-circle">2</div>
                    <div class="step-dot-label"><?php echo $lang['vps_buy_step4'] ?? 'Pago'; ?></div>
                </div>
            </div>

            <!-- Step 1: Plan selection -->
            <div class="step-content active" id="step-1">
                <div class="sblock">
                    <div class="slabel"><?php echo $lang['man_modal_opt_upgrade_label'] ?? 'Selecciona el nuevo plan'; ?></div>
                    <div class="plans-grid-step" id="plansGrid">
                        <div style="color:var(--text-muted);font-size:0.82rem;grid-column:1/-1;padding:18px 0;text-align:center;">
                            <i class="fas fa-spinner fa-spin"></i> <?php echo $lang['dash_loading'] ?? 'Cargando...'; ?>
                        </div>
                    </div>
                </div>

                <!-- Upgrade cost estimate -->
                <div class="sblock" id="costBlock" style="display:none;">
                    <div class="slabel"><?php echo $lang['man_info_price'] ?? 'Costo del upgrade'; ?></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;">Plan actual</span>
                        <span style="font-weight:600;font-size:0.82rem;" id="ce-old-plan">—</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;">Plan nuevo</span>
                        <span style="font-weight:600;font-size:0.82rem;" id="ce-new-plan">—</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                        <span style="color:var(--text-muted);font-size:0.82rem;">Tiempo restante</span>
                        <span style="font-weight:600;font-size:0.82rem;" id="ce-remaining">—</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid rgba(255,255,255,0.07);">
                        <span style="font-weight:700;">Total a pagar</span>
                        <span style="font-weight:800;color:var(--primary);font-size:1.1rem;" id="ce-total">$0.00</span>
                    </div>
                    <div style="margin-top:5px;font-size:0.71rem;color:var(--text-muted);text-align:right;">
                        Diferencia: $<span id="ce-hourly-diff">0.0000</span>/h × <span id="ce-hours">0</span> h
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
                        <span class="rev-label">Servidor</span>
                        <span class="rev-value" id="r-server">—</span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label">Plan actual</span>
                        <span class="rev-value" id="r-old-plan">—</span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label">Nuevo plan</span>
                        <span><span class="rev-value" id="r-new-plan">—</span>
                            <span class="rev-edit" onclick="goStep(1)"><i class="fas fa-pencil-alt"></i> <?php echo $lang['buy_change'] ?? 'Cambiar'; ?></span>
                        </span>
                    </div>
                    <div class="review-row">
                        <span class="rev-label">Tiempo restante</span>
                        <span class="rev-value" id="r-remaining">—</span>
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
                        <span class="or-label">Servidor</span>
                        <span class="or-value empty" id="oc-server">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label">Plan nuevo</span>
                        <span class="or-value empty" id="oc-plan">—</span>
                    </div>
                    <div class="order-row">
                        <span class="or-label"><?php echo $lang['buy_order_payment'] ?? 'Pago'; ?></span>
                        <span class="or-value empty" id="oc-pay">—</span>
                    </div>
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
    const UPG_LANG = <?php echo json_encode([
        'err_plan'      => 'Selecciona un plan para continuar',
        'err_pay'       => $lang['buy_err_pay'] ?? 'Selecciona un método de pago',
        'err_coin'      => $lang['buy_err_coin_net'] ?? 'Selecciona criptomoneda y red',
        'err_conn'      => $lang['tx_err_connection'] ?? 'Error de conexión',
        'processing'    => $lang['man_js_processing'] ?? 'Procesando...',
        'pay_balance'   => $lang['buy_pay_balance'] ?? 'Saldo',
        'btn_invoice'   => $lang['vps_btn_invoice'] ?? 'Generar Factura',
        'btn_confirm'   => $lang['man_modal_opt_btn_confirm'] ?? 'Confirmar',
        'no_plans'      => 'Ya tienes el plan más alto disponible.',
        'no_coins'      => $lang['buy_no_coins'] ?? 'Sin criptomonedas disponibles',
        'min_crypto'    => 'El monto mínimo para pago con cripto es $1.00 USD',
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

    let currentStep  = 1;
    let payMethod    = null;
    let selCurrency  = null;
    let coinsData    = [];
    let userPref     = null;
    let currentAmount = 0;

    // Server data populated by loadServerInfo
    let serverName    = '';
    let currentPlan   = '';
    let currentPlanPrice = 0;
    let addonsPrice   = 0;
    let remainingHours = 0;
    let remainingDisplay = '';

    // Selected upgrade plan
    let selectedPlan = null;

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
            if (!selectedPlan) return showErr('errBox1', UPG_LANG.err_plan);
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
        document.getElementById('sline-1').classList.toggle('done', currentStep > 1);
        document.getElementById('btnBack').style.display    = currentStep > 1 ? 'flex' : 'none';
        document.getElementById('btnNext').style.display    = currentStep < 2 ? 'flex' : 'none';
        document.getElementById('btnFinalize').style.display = currentStep === 2 ? 'flex' : 'none';
    }

    // ── Order card ──────────────────────────────────────────────────────────────

    function updateOrderCard() {
        setOC('oc-server', serverName || null);
        setOC('oc-plan',   selectedPlan ? selectedPlan.name : null);
        setOC('oc-pay',    payMethod === 'balance' ? UPG_LANG.pay_balance : (payMethod === 'crypto' ? 'Crypto' : null));
        document.getElementById('oc-total').textContent = '$' + currentAmount.toFixed(2);
    }

    function setOC(id, val) {
        const el = document.getElementById(id);
        if (val) { el.textContent = val; el.classList.remove('empty'); }
        else     { el.textContent = '—'; el.classList.add('empty'); }
    }

    function updateReview() {
        document.getElementById('r-server').textContent    = serverName || '—';
        document.getElementById('r-old-plan').textContent  = currentPlan || '—';
        document.getElementById('r-new-plan').textContent  = selectedPlan?.name || '—';
        document.getElementById('r-remaining').textContent = remainingDisplay || '—';
        document.getElementById('r-total').textContent     = '$' + currentAmount.toFixed(2);
    }

    // ── Server info ─────────────────────────────────────────────────────────────

    async function loadServerInfo() {
        try {
            const r = await fetch(`/api/servers/detail?id=${SERVER_ID}`);
            const d = await r.json();
            if (d.success && d.data) {
                serverName       = d.data.name || '';
                currentPlan      = d.data.plan || '';
                currentPlanPrice = parseFloat(d.data.plan_price || 0);
                addonsPrice      = parseFloat(d.data.addons_price || 0);
                remainingHours   = parseFloat(d.data.remaining_hours || 0);
                remainingDisplay = d.data.remaining_display || '—';

                document.getElementById('server-subtitle').textContent =
                    serverName + (currentPlan ? ' — ' + currentPlan : '');
                updateOrderCard();
                loadPlans();
            } else {
                document.getElementById('server-subtitle').textContent = 'Servidor no encontrado';
            }
        } catch (e) {
            document.getElementById('server-subtitle').textContent = '';
        }
    }

    // ── Plans ───────────────────────────────────────────────────────────────────

    async function loadPlans() {
        try {
            const r = await fetch('/api/plans/list');
            const d = await r.json();
            if (d.success) renderPlans(d.data);
        } catch (e) {
            document.getElementById('plansGrid').innerHTML =
                `<div style="color:#ef4444;grid-column:1/-1;">${UPG_LANG.err_conn}</div>`;
        }
    }

    function calcUpgradeCost(newPlanPrice) {
        const oldHourly = (currentPlanPrice + addonsPrice) / 720;
        const newHourly = (newPlanPrice    + addonsPrice) / 720;
        return Math.max(0, Math.round((newHourly - oldHourly) * remainingHours * 100) / 100);
    }

    function renderPlans(plans) {
        const grid = document.getElementById('plansGrid');

        const upgradable = plans
            .filter(p => parseFloat(p.price) > currentPlanPrice)
            .map(p => ({ ...p, upgradeCost: calcUpgradeCost(parseFloat(p.price)) }))
            .filter(p => remainingHours === 0 || p.upgradeCost >= 1);

        if (!upgradable.length) {
            grid.innerHTML = `<div style="color:var(--text-muted);grid-column:1/-1;padding:32px 0;text-align:center;">
                <i class="fas fa-info-circle" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                ${UPG_LANG.no_plans}
            </div>`;
            return;
        }

        grid.innerHTML = upgradable.map(p => `
            <div class="plan-card" data-pid="${p.id}" onclick="selPlan(${p.id})">
                <div class="plan-name">${p.name}</div>
                <div class="plan-specs">
                    <div><i class="fas fa-microchip"></i> ${p.cpu} vCPU</div>
                    <div><i class="fas fa-memory"></i> ${parseFloat(p.ram).toFixed(0)} GB RAM</div>
                    <div><i class="fas fa-hdd"></i> ${p.disk} GB SSD</div>
                </div>
                <div class="plan-price">$${parseFloat(p.price).toFixed(2)}/mo</div>
                ${remainingHours > 0
                    ? `<div style="font-size:0.78rem;color:var(--primary);margin-top:4px;font-weight:600;">Upgrade ahora: $${p.upgradeCost.toFixed(2)}</div>`
                    : ''}
            </div>`).join('');

        // Store computed costs for quick access
        window._upgradePlans = upgradable;
    }

    function selPlan(id) {
        selectedPlan  = (window._upgradePlans || []).find(p => p.id == id);
        currentAmount = selectedPlan ? selectedPlan.upgradeCost : 0;

        document.querySelectorAll('.plan-card').forEach(c =>
            c.classList.toggle('selected', c.dataset.pid == id));

        updateOrderCard();

        // Update cost estimate block
        if (selectedPlan) {
            const oldHourly  = (currentPlanPrice + addonsPrice) / 720;
            const newHourly  = (parseFloat(selectedPlan.price) + addonsPrice) / 720;
            const hourlyDiff = newHourly - oldHourly;

            document.getElementById('ce-old-plan').textContent     = currentPlan;
            document.getElementById('ce-new-plan').textContent     = selectedPlan.name;
            document.getElementById('ce-remaining').textContent    = remainingDisplay;
            document.getElementById('ce-total').textContent        = '$' + currentAmount.toFixed(2);
            document.getElementById('ce-hourly-diff').textContent  = hourlyDiff.toFixed(4);
            document.getElementById('ce-hours').textContent        = Math.ceil(remainingHours);
            document.getElementById('costBlock').style.display     = 'block';
        }
    }

    // ── Balance ─────────────────────────────────────────────────────────────────

    async function loadBalance() {
        try {
            const r = await fetch('/api/users/balance');
            const d = await r.json();
            if (d.success)
                document.getElementById('balanceDisplay').textContent =
                    '$' + parseFloat(d.balance).toFixed(2) + ' USD';
        } catch (e) {
            document.getElementById('balanceDisplay').textContent = 'N/D';
        }
    }

    // ── Payment ─────────────────────────────────────────────────────────────────

    function selPay(method) {
        payMethod = method;
        document.getElementById('pc-balance').classList.toggle('selected', method === 'balance');
        document.getElementById('pc-crypto').classList.toggle('selected', method === 'crypto');
        document.getElementById('cryptoArea').style.display = method === 'crypto' ? 'block' : 'none';
        document.getElementById('btnFinalizeText').textContent =
            method === 'crypto' ? UPG_LANG.btn_invoice : UPG_LANG.btn_confirm;
        updateOrderCard();
    }

    // ── Coins ────────────────────────────────────────────────────────────────────

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
                    `<div style="color:#ef4444;grid-column:1/-1;font-size:0.75rem;">${UPG_LANG.no_coins}</div>`;
                return;
            }
            coinsData = list;
            renderCoins();
        } catch (e) {
            document.getElementById('coinGrid').innerHTML =
                `<div style="color:#ef4444;grid-column:1/-1;font-size:0.75rem;">${UPG_LANG.err_conn}</div>`;
        }
    }

    function renderCoins() {
        const sorted = [
            ...coinsData.filter(c => TOP_COINS.includes(c.symbol || c.currency)),
            ...coinsData.filter(c => !TOP_COINS.includes(c.symbol || c.currency)),
        ];
        document.getElementById('coinGrid').innerHTML = sorted.map(c => {
            const sym    = c.symbol || c.currency || '';
            const logo   = COIN_LOGOS[sym] || 'BTC.png';
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
        const coin    = coinsData.find(c => c.symbol === sym || c.currency === sym);
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
        if (!payMethod) return showErr('errBox2', UPG_LANG.err_pay);
        if (payMethod === 'crypto') {
            if (!selCurrency || !selCurrency.network) return showErr('errBox2', UPG_LANG.err_coin);
            if (currentAmount > 0 && currentAmount < 1) return showErr('errBox2', UPG_LANG.min_crypto);
        }

        const btn = document.getElementById('btnFinalize');
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${UPG_LANG.processing}`;

        const payload = {
            server_id:      SERVER_ID,
            action:         'upgrade',
            value:          selectedPlan.id,
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
                        action:           'upgrade',
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
            btn.innerHTML = `<i class="fas fa-check-circle"></i> <span id="btnFinalizeText">${payMethod === 'crypto' ? UPG_LANG.btn_invoice : UPG_LANG.btn_confirm}</span>`;
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
