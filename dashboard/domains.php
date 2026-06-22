<?php
require_once '../api/config.php';
require_once '../models/User.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/lang_loader.php';

$pageTitle = $lang['dom_title'] . ' - ' . SITE_NAME;
// Sidebar toggle style is already in style.css, but index.php had some specific inline style for headers.
// We can reuse what index.php considers 'extraHead' if needed, or just relying on style.css
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
    </style>
';
include 'header.php';
?>

<main class="main-content">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i
                        class="fas fa-bars"></i></button>
                <h1 style="margin: 0;">
                    <?php echo $lang['dom_header']; ?>
                </h1>
            </div>
            <button id="btnOpenDomainModal" class="btn btn-manage" style="max-width: 200px;">
                <i class="fas fa-plus"></i>
                <?php echo $lang['dom_btn_register']; ?>
            </button>
        </div>
    </div>

    <!-- Empty hero (shown when user has no domains) -->
    <div id="domEmptyHero" style="display:none;">
        <div class="page-empty-hero">
            <div class="page-empty-glow"></div>
            <div class="page-empty-icon-wrap">
                <i class="fas fa-globe"></i>
            </div>
            <h2 class="page-empty-title"><?php echo $lang['dom_no_domains']; ?></h2>
            <p class="page-empty-sub"><?php echo $lang['dom_no_domains_sub']; ?></p>
            <button class="btn btn-manage page-empty-cta" onclick="document.getElementById('btnOpenDomainModal').click()">
                <i class="fas fa-plus"></i> <?php echo $lang['dom_btn_register']; ?>
            </button>
            <div class="page-empty-features">
                <div class="page-feat-chip"><i class="fas fa-shield-alt"></i> <?php echo $lang['dom_chip_whois']; ?></div>
                <div class="page-feat-chip"><i class="fas fa-globe"></i> <?php echo $lang['dom_chip_extensions']; ?></div>
                <div class="page-feat-chip"><i class="fas fa-bolt"></i> <?php echo $lang['dom_chip_instant']; ?></div>
                <div class="page-feat-chip"><i class="fas fa-sync"></i> <?php echo $lang['dom_chip_autorenew']; ?></div>
            </div>
        </div>
    </div>

    <!-- Active domains section (shown when user has domains) -->
    <div id="activeDomainsSection" style="display:none;">
        <div style="margin-bottom: 20px;">
            <input type="text" id="domainSearch" class="form-control"
                placeholder="<?php echo $lang['dom_search_ph']; ?>"
                oninput="filterDomains(this.value)"
                style="max-width: 380px;">
        </div>
        <div class="section-title"><?php echo $lang['dom_sect_registered']; ?></div>
        <div class="servers-grid" id="domainsGrid">
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i>
            </div>
        </div>
    </div>

    <!-- Orders section -->
    <div id="domOrdersSectionWrap" style="display:none;">
        <div class="section-title" style="margin-top: 30px;"><?php echo $lang['dom_sect_orders']; ?></div>
        <div id="domainOrdersWrap">
            <div style="text-align: center; padding: 30px;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i>
            </div>
        </div>
    </div>
</main>

<!-- Create Domain Modal -->
<div class="modal-overlay" id="createDomainModal">
    <div class="modal" style="width: 500px; height: auto;">
        <div class="modal-header">
            <h2>
                <?php echo $lang['dom_modal_title']; ?>
            </h2>
            <button class="btn-close" onclick="closeDomainModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="domainError" class="alert alert-danger"
                style="display:none; margin-bottom:15px; color: #ff6b6b; background: rgba(255, 107, 107, 0.1); padding: 10px; border-radius: 4px;">
            </div>
            <div id="domainSuccess" class="alert alert-success"
                style="display:none; margin-bottom:15px; color: #51cf66; background: rgba(81, 207, 102, 0.1); padding: 10px; border-radius: 4px;">
            </div>

            <!-- Step 1: Check Availability -->
            <div id="checkStep">
                <div class="form-group">
                    <label>
                        <?php echo $lang['dom_step1_label']; ?>
                    </label>
                    <input type="text" id="checkName" class="form-control"
                        placeholder="<?php echo $lang['dom_step1_placeholder']; ?>" required>
                    <small style="color:var(--text-muted); display:block; margin-top:5px;">
                        <?php echo $lang['dom_step1_help']; ?>
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <?php echo $lang['dom_step1_ext_label']; ?>
                    </label>
                    <div style="margin-bottom: 10px;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="selectAllSuffixes(true)">
                            <?php echo $lang['dom_btn_all']; ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="selectAllSuffixes(false)">
                            <?php echo $lang['dom_btn_none']; ?>
                        </button>
                    </div>
                    <div id="suffixList"
                        style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 150px; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 6px;">
                        <!-- JS injected suffixes -->
                    </div>
                </div>

                <button type="button" onclick="handleCheckDomain()" id="btnCheck" class="btn btn-manage"
                    style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-search"></i>
                    <?php echo $lang['dom_btn_check']; ?>
                </button>
            </div>

            <!-- Step 2: Results & Config -->
            <div id="buyStep" style="display: none; animation: fadeIn 0.3s ease-in-out;">

                <!-- Results List -->
                <div id="resultsArea">
                    <h3 style="margin-top:0; margin-bottom: 15px;">
                        <?php echo $lang['dom_res_title']; ?>
                    </h3>
                    <div id="domainResultsList"
                        style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto;">
                        <!-- JS injected results -->
                    </div>
                    <button type="button" onclick="resetCheck()" class="btn btn-outline"
                        style="width:100%; margin-top:15px;">
                        <i class="fas fa-search"></i>
                        <?php echo $lang['dom_btn_new_search']; ?>
                    </button>
                </div>

                <!-- Configuration Form (Initially Hidden) -->
                <div id="configArea" style="display:none;">
                    <button type="button" onclick="backToResults()"
                        style="background:none; border:none; color:var(--text-muted); cursor:pointer; margin-bottom:10px;">
                        <i class="fas fa-arrow-left"></i>
                        <?php echo $lang['dom_btn_back']; ?>
                    </button>

                    <div
                        style="margin-bottom: 20px; padding: 10px; background: rgba(81, 207, 102, 0.1); border: 1px solid rgba(81, 207, 102, 0.3); border-radius: 8px; color: #eefdf2;">
                        <i class="fas fa-check-circle" style="color: #51cf66;"></i>
                        <?php echo $lang['dom_config_title']; ?> <strong><span
                                id="selectedConfigDomain"></span></strong>
                    </div>

                    <form id="domainForm" onsubmit="handleBuyDomain(event)">
                        <div class="form-group">
                            <label>
                                <?php echo $lang['dom_config_duration']; ?>
                            </label>
                            <select id="domainYears" class="form-control">
                                <option value="1">1
                                    <?php echo $lang['dom_config_year']; ?>
                                </option>
                                <option value="2">2
                                    <?php echo $lang['dom_config_years']; ?>
                                </option>
                                <option value="3">3
                                    <?php echo $lang['dom_config_years']; ?>
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <?php echo $lang['dom_config_pass']; ?>
                            </label>
                            <input type="text" id="domainPassword" class="form-control"
                                placeholder="<?php echo $lang['dom_config_pass_placeholder']; ?>">
                        </div>

                        <!-- Payment Method -->
                        <div class="form-group" style="margin-top: 15px;">
                            <label>Método de pago</label>
                            <div style="display: flex; gap: 10px; margin-top: 8px;">
                                <label style="flex:1; cursor:pointer;">
                                    <input type="radio" name="domainPayMethod" value="balance" checked
                                        onchange="toggleDomainPayMethod(this.value)" style="margin-right:6px;">
                                    <i class="fas fa-wallet"></i> Balance
                                </label>
                                <label style="flex:1; cursor:pointer;">
                                    <input type="radio" name="domainPayMethod" value="crypto"
                                        onchange="toggleDomainPayMethod(this.value)" style="margin-right:6px;">
                                    <i class="fas fa-coins"></i> Crypto
                                </label>
                            </div>
                        </div>

                        <!-- Crypto options (hidden by default) -->
                        <div id="domainCryptoOptions" style="display:none;">
                            <div class="form-group">
                                <label style="font-size:0.88rem; color:var(--text-muted); display:block; margin-bottom:8px;">Moneda</label>
                                <div id="dom-coinGrid" class="currency-main-grid"></div>
                                <div id="dom-moreCoinsContainer" style="display:none; margin-top:8px;">
                                    <div id="dom-moreCoinsGrid" class="currency-more-grid"></div>
                                </div>
                                <button type="button" id="dom-btnToggleMore" class="btn-toggle-text">
                                    <i class="fas fa-chevron-down"></i> <?php echo $lang['vps_crypto_more_coins'] ?? 'See more coins'; ?>
                                </button>
                            </div>
                            <div id="dom-networkGroup" style="display:none; margin-top:10px;">
                                <label style="font-size:0.88rem; color:var(--text-muted); display:block; margin-bottom:8px;">Red</label>
                                <div id="dom-networkGrid" class="network-selector-grid"></div>
                            </div>
                        </div>

                        <div id="domainPriceDisplay"
                            style="margin: 20px 0; text-align: right; font-size: 1.1rem; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <?php echo $lang['dom_config_total']; ?> <span style="color:var(--text-muted)">
                                <?php echo $lang['dom_config_calculating']; ?>
                            </span>
                        </div>

                        <button type="submit" class="btn btn-manage" style="width: 100%; margin-top: 20px;">
                            <i class="fas fa-shopping-cart"></i>
                            <?php echo $lang['dom_btn_complete']; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crypto Payment Modal -->
<div class="modal-overlay" id="domainPaymentModal">
    <div class="modal" style="width: 480px; height: auto;">
        <div class="modal-header">
            <h2><i class="fas fa-coins"></i> Pago Crypto</h2>
            <button class="btn-close" onclick="closeDomainPaymentModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="domainPaymentBody" style="text-align:center;">
        </div>
    </div>
</div>

<script>
    const LANG_DOM = <?php echo json_encode([
        'total' => $lang['dom_config_total'],
        'avail' => $lang['dom_status_avail'],
        'unavail' => $lang['dom_status_unavail'],
        'select' => $lang['dom_btn_select'],
        'avail_simple' => $lang['dom_msg_avail_simple'],
        'no_res' => $lang['dom_msg_no_res'],
        'alert_name_req' => $lang['dom_alert_name_req'],
        'alert_ext_req' => $lang['dom_alert_ext_req'],
        'verifying' => $lang['dom_btn_verify'],
        'process' => $lang['dom_btn_process'],
        'success_title' => $lang['dom_success_title'],
        'success_msg' => $lang['dom_success_msg'],
        'local_id' => $lang['dom_local_id'],
        'card_reg' => $lang['dom_card_reg'],
        'card_exp' => $lang['dom_card_exp'],
        'card_year' => $lang['dom_config_year'], // reuse singular
        'card_years' => $lang['dom_config_years'] || 'Years',
        'card_id' => $lang['dom_card_id'],
        'btn_manage' => $lang['dom_btn_manage'],
        'status_active' => $lang['prof_status_active'], // Reuse profile active
        'status_pending' => $lang['status_pending'] ?? 'Pending',
        'status_expired' => $lang['tx_res_expired'] ?? 'Expired',
        'err_loading'       => $lang['dom_err_loading'],
        'no_domains'        => $lang['dom_no_domains'],
        'no_domains_sub'    => $lang['dom_no_domains_sub'],
        'no_orders'         => $lang['dom_no_orders'],
        'tbl_id'            => $lang['dom_orders_tbl_id'],
        'tbl_domain'        => $lang['dom_orders_tbl_domain'],
        'tbl_tld'           => $lang['dom_orders_tbl_tld'],
        'tbl_period'        => $lang['dom_orders_tbl_period'],
        'tbl_amount'        => $lang['dom_orders_tbl_amount'],
        'tbl_status'        => $lang['dom_orders_tbl_status'],
        'tbl_date'          => $lang['dom_orders_tbl_date'],
        'years'             => $lang['dom_orders_years'],
    ]); ?>;

    // ── Domain Crypto Selector ────────────────────────────────
    let domCurrenciesData = [];
    let domSelectedCurrency = null; // { symbol, network }

    function toggleDomainPayMethod(val) {
        const wrap = document.getElementById('domainCryptoOptions');
        wrap.style.display = val === 'crypto' ? 'block' : 'none';
        if (val === 'crypto' && domCurrenciesData.length === 0) loadDomCurrencies();
    }

    async function loadDomCurrencies() {
        const grid = document.getElementById('dom-coinGrid');
        grid.innerHTML = '<div style="grid-column:1/-1;color:var(--text-muted);font-size:0.82rem;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
        try {
            const res  = await fetch('../api/transactions/currencies');
            const raw  = await res.json();
            let list   = raw.data || raw;
            if (list && typeof list === 'object' && !Array.isArray(list)) list = Object.values(list);
            domCurrenciesData = list;
            renderDomCurrencies();
        } catch (e) {
            grid.innerHTML = '<div style="color:#ef4444;font-size:0.82rem;grid-column:1/-1;">Error cargando monedas.</div>';
        }
    }

    function renderDomCurrencies() {
        const mainGrid = document.getElementById('dom-coinGrid');
        const moreGrid = document.getElementById('dom-moreCoinsGrid');
        mainGrid.innerHTML = '';
        moreGrid.innerHTML = '';

        const topCoins = ['USDT', 'BTC', 'ETH', 'TRX', 'LTC', 'BNB'];
        const logos = {
            'USDT':'USDT_Logo.png','BTC':'BTC.png','ETH':'ETH.png','TRX':'TRX.png',
            'LTC':'LTC.png','BNB':'BNB.png','USDC':'USDC.png','DOGE':'DODGE.png',
            'SOL':'SOL.png','TON':'TON.png','XMR':'XMR.png','XRP':'XRP.png',
        };

        domCurrenciesData.forEach(coin => {
            const sym   = coin.symbol || coin.currency;
            const isTop = topCoins.includes(sym);
            const card  = document.createElement('div');
            card.className = 'crypto-card';
            card.dataset.symbol = sym;

            const logoFile = logos[sym] || null;
            const logoHtml = logoFile
                ? `<img src="../dashboard/assets/crypto_logos/${logoFile}" alt="${sym}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none'">`
                : `<div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;">${sym.slice(0,3)}</div>`;

            card.innerHTML = `${logoHtml}<div style="font-weight:700;font-size:0.82rem;margin-top:4px;">${sym}</div>`;
            card.style.cssText = 'border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:8px 4px;text-align:center;cursor:pointer;transition:all 0.2s;background:rgba(255,255,255,0.02);display:flex;flex-direction:column;align-items:center;gap:2px;';
            card.onclick = () => selectDomCoin(sym, card);
            (isTop ? mainGrid : moreGrid).appendChild(card);
        });

        const btnMore = document.getElementById('dom-btnToggleMore');
        if (moreGrid.children.length === 0) {
            btnMore.style.display = 'none';
        } else {
            btnMore.onclick = () => {
                const c = document.getElementById('dom-moreCoinsContainer');
                const open = c.style.display !== 'none';
                c.style.display = open ? 'none' : 'block';
                btnMore.innerHTML = `<i class="fas fa-chevron-${open ? 'down' : 'up'}"></i> ${open ? '<?php echo addslashes($lang['vps_crypto_more_coins'] ?? 'See more coins'); ?>' : '<?php echo addslashes($lang['vps_crypto_less_coins'] ?? 'See less'); ?>'}`;
            };
        }

        // Auto-select USDT
        const usdtCard = mainGrid.querySelector('[data-symbol="USDT"]');
        if (usdtCard) usdtCard.click();
    }

    function selectDomCoin(symbol, el) {
        document.querySelectorAll('#dom-coinGrid .crypto-card, #dom-moreCoinsGrid .crypto-card').forEach(c => {
            c.style.borderColor = 'rgba(255,255,255,0.1)';
            c.style.background  = 'rgba(255,255,255,0.02)';
        });
        el.style.borderColor = 'var(--primary)';
        el.style.background  = 'rgba(0,243,255,0.07)';

        domSelectedCurrency = { symbol, network: null };
        renderDomNetworks(symbol);
    }

    function renderDomNetworks(symbol) {
        const netGroup = document.getElementById('dom-networkGroup');
        const netGrid  = document.getElementById('dom-networkGrid');
        const coin     = domCurrenciesData.find(c => (c.symbol === symbol || c.currency === symbol));
        netGrid.innerHTML = '';

        if (coin && coin.networks) {
            netGroup.style.display = 'block';
            const networks = Object.values(coin.networks);
            networks.forEach((net, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'vps-net-btn';

                let acronym = '';
                if (net.keys && Array.isArray(net.keys)) {
                    const found = net.keys.find(k => k.length >= 2 && k.length <= 6 && k === k.toUpperCase());
                    if (found && found !== net.network) acronym = found;
                }
                btn.innerHTML = `<span>${net.name}</span>${acronym ? `<span class="net-acronym">${acronym}</span>` : ''}`;
                btn.onclick = () => {
                    document.querySelectorAll('#dom-networkGrid .vps-net-btn').forEach(b => b.classList.remove('selected-net'));
                    btn.classList.add('selected-net');
                    domSelectedCurrency = { symbol, network: net.network };
                };
                netGrid.appendChild(btn);
                if (idx === 0) btn.click();
            });
        } else {
            netGroup.style.display = 'none';
            if (coin) domSelectedCurrency = { symbol, network: symbol };
        }
    }

    function closeDomainPaymentModal() {
        document.getElementById('domainPaymentModal').classList.remove('active');
    }

    function showDomainPaymentModal(data) {
        const body = document.getElementById('domainPaymentBody');
        const expiry = data.expired_at ? new Date(data.expired_at).toLocaleString() : 'N/A';
        body.innerHTML = `
            <p style="color:var(--text-muted); margin-bottom:15px;">Dominio: <strong style="color:#eee">${data.domain}</strong></p>
            ${data.qr_code ? `<img src="${data.qr_code}" alt="QR" style="width:160px; height:160px; border-radius:8px; margin-bottom:15px;">` : ''}
            <div style="background:rgba(0,0,0,0.3); border-radius:8px; padding:12px; margin-bottom:12px; word-break:break-all; font-size:0.85rem;">
                <div style="color:var(--text-muted); margin-bottom:4px;">Dirección (${data.network})</div>
                <strong>${data.address}</strong>
            </div>
            ${data.memo ? `<div style="background:rgba(0,0,0,0.2); border-radius:8px; padding:10px; margin-bottom:12px; font-size:0.85rem;"><span style="color:var(--text-muted);">Memo/Tag:</span> <strong>${data.memo}</strong></div>` : ''}
            <div style="display:flex; justify-content:space-between; padding:10px; background:rgba(16,185,129,0.1); border-radius:8px; margin-bottom:12px;">
                <span style="color:var(--text-muted);">Monto</span>
                <strong style="color:#10b981;">${data.pay_amount} ${data.pay_currency}</strong>
            </div>
            <div style="display:flex; justify-content:space-between; padding:10px; background:rgba(0,0,0,0.2); border-radius:8px; margin-bottom:12px;">
                <span style="color:var(--text-muted);">USD</span>
                <strong>$${parseFloat(data.amount_usd).toFixed(2)}</strong>
            </div>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:8px;">
                <i class="fas fa-clock"></i> Expira: ${expiry}
            </div>
            <p style="margin-top:15px; font-size:0.85rem; color:var(--text-muted);">
                El dominio se registrará automáticamente al confirmar el pago.
            </p>
        `;
        document.getElementById('domainPaymentModal').classList.add('active');
    }

    // Modal Logic
    const btnOpenDomainModal = document.getElementById('btnOpenDomainModal');
    const domainModal = document.getElementById('createDomainModal');
    let currentCheckedDomain = null; // {name, suffix} used for final order

    let domainPrices = { register: {}, renew: {}, default: 24 };

    async function loadDomainPrices() {
        try {
            const r = await fetch('../api/domains/prices.php');
            const j = await r.json();
            if (j.success) domainPrices = j.data;
        } catch (e) {}
    }

    function calculateDomainPrice(suffix, years) {
        const s = suffix.replace(/^\./, '');
        const base  = domainPrices.register[s] ?? domainPrices.default;
        const renew = domainPrices.renew[s]    ?? domainPrices.default;
        let total = base;
        if (years > 1) total += (years - 1) * renew;
        return total;
    }

    // Update Price Display
    function updatePriceDisplay() {
        if (!currentCheckedDomain) return;
        const years = parseInt(document.getElementById('domainYears').value || 1);
        const price = calculateDomainPrice(currentCheckedDomain.suffix, years);
        const displayEl = document.getElementById('domainPriceDisplay');
        if (displayEl) {
            displayEl.innerHTML = `${LANG_DOM.total} <span style="color:var(--primary); font-weight:800; font-size:1.2rem;">$${price.toFixed(2)}</span>`;
        }
    }

    // User provided default suffixes
    const defaultSuffixes = [
        "info", "io", "com", "org", "net", "xyz", "biz", "me", "fi",
        "to", "cm", "hk", "ac", "sh", "la", "cx", "vc", "so", "app",
        "pw", "cc", "at", "online", "store"
    ];

    if (btnOpenDomainModal) {
        btnOpenDomainModal.addEventListener('click', () => {
            domainModal.classList.add('active');
            renderSuffixes(); // Render checkboxes
            resetCheck(); // Always start at step 1
        });
    }

    // Listen for year changes
    const yearSelect = document.getElementById('domainYears');
    if (yearSelect) {
        yearSelect.addEventListener('change', updatePriceDisplay);
    }

    function closeDomainModal() {
        if (domainModal) domainModal.classList.remove('active');
    }

    // Render chips/checkboxes
    function renderSuffixes() {
        const container = document.getElementById('suffixList');
        if (!container) return; // safety
        if (container.children.length > 0) return; // already rendered

        defaultSuffixes.forEach(s => {
            const wrapper = document.createElement('label');
            wrapper.style.cursor = 'pointer';
            wrapper.style.display = 'inline-flex';
            wrapper.style.alignItems = 'center';
            wrapper.style.background = 'rgba(255,255,255,0.05)';
            wrapper.style.padding = '5px 10px';
            wrapper.style.borderRadius = '15px';
            wrapper.style.border = '1px solid rgba(255,255,255,0.1)';
            wrapper.style.fontSize = '0.9rem';
            wrapper.style.userSelect = 'none';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = s;
            checkbox.checked = true; // Default all checked? Or maybe simplified list? User said "personas puedan escoger".
            // Let's check common ones by default to avoid huge request? Or all? User list is ~20.
            // Let's check all by default as requested in "add these defaults in search".
            checkbox.style.marginRight = '5px';

            wrapper.appendChild(checkbox);
            wrapper.appendChild(document.createTextNode('.' + s)); // Add dot for display
            container.appendChild(wrapper);

            // Toggle visual style on change
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    wrapper.style.background = 'rgba(16, 185, 129, 0.2)';
                    wrapper.style.borderColor = '#10b981';
                } else {
                    wrapper.style.background = 'rgba(255,255,255,0.05)';
                    wrapper.style.borderColor = 'rgba(255,255,255,0.1)';
                }
            });
            // Initial trigger
            checkbox.dispatchEvent(new Event('change'));
        });
    }

    function selectAllSuffixes(enable) {
        const checkboxes = document.querySelectorAll('#suffixList input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.checked = enable;
            cb.dispatchEvent(new Event('change'));
        });
    }

    function resetCheck() {
        document.getElementById('checkStep').style.display = 'block';
        document.getElementById('buyStep').style.display = 'none';

        // Reset sub-areas of buyStep
        document.getElementById('resultsArea').style.display = 'block';
        document.getElementById('configArea').style.display = 'none';

        document.getElementById('domainError').style.display = 'none';
        document.getElementById('domainSuccess').style.display = 'none';

        document.getElementById('checkName').value = '';
        document.getElementById('domainResultsList').innerHTML = '';
        currentCheckedDomain = null;
    }

    function backToResults() {
        document.getElementById('configArea').style.display = 'none';
        document.getElementById('resultsArea').style.display = 'block';
    }

    function configureDomain(name, suffix) {
        currentCheckedDomain = { name, suffix };
        document.getElementById('selectedConfigDomain').textContent = name + (suffix.startsWith('.') ? suffix : '.' + suffix);
        document.getElementById('resultsArea').style.display = 'none';
        document.getElementById('configArea').style.display = 'block';

        // Reset years to 1
        const ySelect = document.getElementById('domainYears');
        if (ySelect) ySelect.value = "1";

        // Reset payment method to balance
        const balanceRadio = document.querySelector('input[name="domainPayMethod"][value="balance"]');
        if (balanceRadio) { balanceRadio.checked = true; toggleDomainPayMethod('balance'); }

        // Initial Price update
        updatePriceDisplay();
    }

    async function handleCheckDomain() {
        const btn = document.getElementById('btnCheck');
        const errorDiv = document.getElementById('domainError');
        const successDiv = document.getElementById('domainSuccess');
        const resultsList = document.getElementById('domainResultsList');

        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        let name = document.getElementById('checkName').value.trim();
        if (!name) {
            errorDiv.textContent = LANG_DOM.alert_name_req;
            errorDiv.style.display = 'block';
            return;
        }

        if (name.includes('.')) {
            name = name.split('.')[0];
        }

        // Gather checked suffixes
        const checkedBoxes = document.querySelectorAll('#suffixList input[type="checkbox"]:checked');
        const selectedSuffixes = Array.from(checkedBoxes).map(cb => cb.value);

        if (selectedSuffixes.length === 0) {
            errorDiv.textContent = LANG_DOM.alert_ext_req;
            errorDiv.style.display = 'block';
            return;
        }

        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_DOM.verifying}`;

        try {
            const payload = {
                query: name,
                suffixes: selectedSuffixes // Send array
            };

            const res = await fetch('../api/domains/check_availability', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            console.log('API Response:', data);

            if (data.success) {
                // Parsing logic as before
                // Parsing logic adapted to user response structure
                let results = [];
                const raw = data.data;

                // Structure: { success: true, data: [...results...], meta: {...} }
                if (raw && raw.data && Array.isArray(raw.data)) {
                    results = raw.data;
                }
                // Fallback: maybe data.data IS the array directly (older API version?)
                else if (Array.isArray(raw)) {
                    results = raw;
                }
                // Fallback for map-like structure if needed, but avoid keys like 'success' or 'meta'
                else if (typeof raw === 'object' && raw !== null) {
                    Object.keys(raw).forEach(key => {
                        if (key !== 'success' && key !== 'meta' && typeof raw[key] === 'object') {
                            results.push({ tld: key, ...raw[key] });
                        }
                    });
                }

                resultsList.innerHTML = '';
                let foundAny = false;

                results.forEach(r => {
                    let fullDomain = '';
                    let tld = r.tld || '';
                    if (r.domain) {
                        fullDomain = r.domain;
                        const parts = fullDomain.split('.');
                        if (parts.length > 1) tld = '.' + parts[parts.length - 1];
                    } else if (tld) {
                        fullDomain = name + (tld.startsWith('.') ? tld : '.' + tld);
                    } else {
                        return;
                    }

                    let isAvailable = false;
                    if (r.status === 'available' || r.available === true || r.result === 'available') isAvailable = true;
                    if (r.status === 'unavailable' || r.available === false) isAvailable = false;

                    const itemDiv = document.createElement('div');
                    itemDiv.style.background = 'rgba(255,255,255,0.05)';
                    itemDiv.style.padding = '10px';
                    itemDiv.style.borderRadius = '6px';
                    itemDiv.style.display = 'flex';
                    itemDiv.style.justifyContent = 'space-between';
                    itemDiv.style.alignItems = 'center';
                    itemDiv.style.border = '1px solid rgba(255,255,255,0.1)';

                    if (isAvailable) {
                        foundAny = true;
                        let cleanSuffix = tld.replace(/^\./, '');
                        // Show estimated price
                        const price = calculateDomainPrice(cleanSuffix, 1);

                        itemDiv.innerHTML = `
                            <div>
                                <strong style="font-size:1.1rem;">${fullDomain}</strong>
                                <span style="color:#10b981; font-size:0.8rem; margin-left:10px;"><i class="fas fa-check"></i> ${LANG_DOM.avail}</span>
                                <div style="font-size:0.85rem; color:var(--text-muted); margin-top:4px;">$${price.toFixed(2)} / ${LANG_DOM.card_year}</div>
                            </div>
                            <button class="btn btn-primary" style="padding: 5px 15px; font-size: 0.9rem;" onclick="configureDomain('${name}', '${cleanSuffix}')">
                                <i class="fas fa-shopping-cart"></i> ${LANG_DOM.select}
                            </button>
                        `;
                        itemDiv.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                        itemDiv.style.background = 'rgba(16, 185, 129, 0.05)';
                    } else {
                        itemDiv.innerHTML = `
                             <div>
                                <strong style="color:var(--text-muted);">${fullDomain}</strong>
                                <span style="color:#ef4444; font-size:0.8rem; margin-left:10px;"><i class="fas fa-times"></i> ${LANG_DOM.unavail}</span>
                            </div>
                        `;
                        itemDiv.style.opacity = '0.7';
                    }

                    resultsList.appendChild(itemDiv);
                });

                if (results.length === 0) {
                    if (data.data && (data.data.status === 'available' || data.data.available)) {
                        resultsList.innerHTML = `<p>${LANG_DOM.avail_simple}</p>`;
                    } else {
                        resultsList.innerHTML = `<p style="text-align:center; color:var(--text-muted);">${LANG_DOM.no_res}</p>`;
                    }
                }

                document.getElementById('checkStep').style.display = 'none';
                document.getElementById('buyStep').style.display = 'block';

            } else {
                errorDiv.textContent = data.error || 'Error en la verificación.';
                errorDiv.style.display = 'block';
            }

        } catch (err) {
            console.error(err);
            errorDiv.textContent = 'Error de conexión: ' + err.message;
            errorDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    async function handleBuyDomain(e) {
        e.preventDefault();

        if (!currentCheckedDomain) return;

        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_DOM.process}`;

        const years    = parseInt(document.getElementById('domainYears').value);
        const password = document.getElementById('domainPassword').value.trim() || null;
        const payMethod = document.querySelector('input[name="domainPayMethod"]:checked')?.value || 'balance';

        const basePayload = {
            domain_name:   currentCheckedDomain.name,
            domain_suffix: currentCheckedDomain.suffix,
            vyear:         years,
            password:      password,
        };

        try {
            if (payMethod === 'balance') {
                const res  = await fetch('../api/orders/create_domain', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(basePayload)
                });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('configArea').innerHTML = `
                        <div class="alert alert-success" style="text-align:center; padding:20px;">
                            <i class="fas fa-check-circle" style="font-size:3rem; margin-bottom:15px;"></i><br>
                            <h3>${LANG_DOM.success_title}</h3>
                            <p>${LANG_DOM.success_msg}</p>
                            <p style="font-size:0.9rem; margin-top:10px;">${LANG_DOM.local_id} ${data.local_id}</p>
                        </div>
                    `;
                    setTimeout(() => { closeDomainModal(); loadDomains(); }, 2000);
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }

            } else {
                if (!domSelectedCurrency || !domSelectedCurrency.network) {
                    alert('Por favor selecciona una criptomoneda y su red.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    return;
                }
                const currency = domSelectedCurrency.symbol;
                const network  = domSelectedCurrency.network;

                const res  = await fetch('../api/orders/create_domain_invoice', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...basePayload, payment_currency: currency, network })
                });
                const data = await res.json();

                if (data.success) {
                    closeDomainModal();
                    showDomainPaymentModal(data.data);
                } else {
                    alert('Error: ' + (data.message || data.error || 'Error desconocido'));
                }
            }

        } catch (err) {
            alert('Error de conexión: ' + err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    }

    // Load Domains
    async function loadDomains() {
        const grid           = document.getElementById('domainsGrid');
        const emptyHero      = document.getElementById('domEmptyHero');
        const activeSection  = document.getElementById('activeDomainsSection');
        const ordersSection  = document.getElementById('domOrdersSectionWrap');

        try {
            const res = await fetch('../api/domains/list');
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                if (emptyHero)     emptyHero.style.display    = 'none';
                if (activeSection) activeSection.style.display = 'block';
                if (ordersSection) ordersSection.style.display = 'block';

                grid.innerHTML = '';
                data.data.forEach(domain => {
                    console.log('Domain data:', domain); // Debug log

                    const card = document.createElement('div');
                    card.className = 'server-card';

                    let statusClass = 'status-inactive';
                    let statusText = (domain.status || 'unknown').toUpperCase();

                    if (statusText === 'ACTIVE' || statusText === 'SUCCESS') {
                        statusClass = 'status-active';
                        statusText = LANG_DOM.status_active;
                    }
                    else if (statusText === 'PENDING') {
                        statusClass = 'status-provisioning';
                        statusText = LANG_DOM.status_pending;
                    }
                    else if (statusText === 'EXPIRED') {
                        statusClass = 'status-expired';
                        statusText = LANG_DOM.status_expired;
                    }

                    const expiryDate = domain.expiration_date ? new Date(domain.expiration_date).toLocaleDateString() : 'N/A';
                    const createdDate = domain.created_at ? new Date(domain.created_at).toLocaleDateString() : 'N/A';
                    let term = domain.registration_term || 1;
                    const yearLabel = term == 1 ? LANG_DOM.card_year : LANG_DOM.card_years;

                    card.innerHTML = `
                        <div class="server-header">
                            <h3 style="word-break: break-all;">${domain.domain_name}</h3>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                        
                        <div class="server-specs">
                            <div class="spec">
                                <i class="fas fa-calendar-check"></i>
                                <span>${LANG_DOM.card_reg} ${createdDate}</span>
                            </div>
                            <div class="spec" title="Expiración">
                                <i class="fas fa-hourglass-end"></i>
                                <span>${LANG_DOM.card_exp} ${expiryDate}</span>
                            </div>
                            <div class="spec">
                                <i class="fas fa-sync"></i>
                                <span>${term} ${yearLabel}</span>
                            </div>
                        </div>

                        <div class="server-info">
                            <div><i class="fas fa-shield-alt"></i> ${LANG_DOM.card_id} ${domain.product_id || domain.id}</div>
                        </div>

                        <div class="server-actions">
                            <button class="btn btn-manage" style="width:100%" onclick="window.location.href='domain_detail/?id=${domain.id}'">
                                <i class="fas fa-cog"></i> ${LANG_DOM.btn_manage}
                            </button>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            } else {
                if (activeSection) activeSection.style.display = 'none';
                if (ordersSection) ordersSection.style.display = 'none';
                if (emptyHero)     emptyHero.style.display     = 'block';
            }

        } catch (e) {
            console.error('Error loading domains:', e);
            if (activeSection) activeSection.style.display = 'block';
            if (emptyHero)     emptyHero.style.display     = 'none';
            grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #ef4444;">${LANG_DOM.err_loading}</div>`;
        }
    }

    // ── Domain Orders ─────────────────────────────────────────
    let allDomainOrders = [];
    let domainOrdersPage = 1;
    const DOMAIN_ORDERS_PER_PAGE = 10;

    async function loadDomainOrders() {
        const wrap = document.getElementById('domainOrdersWrap');
        try {
            const res  = await fetch('../api/orders/list');
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Error');

            allDomainOrders = (data.data || []).filter(o => o.type === 'domain');
            domainOrdersPage = 1;
            renderDomainOrders(allDomainOrders);
        } catch (e) {
            wrap.innerHTML = `<p style="color:#ef4444; padding:20px;">${e.message}</p>`;
        }
    }

    function renderDomainOrders(orders) {
        const wrap = document.getElementById('domainOrdersWrap');

        if (!orders.length) {
            wrap.innerHTML = `
                <div style="text-align:center; padding:40px; color:var(--text-muted); background:rgba(255,255,255,0.02); border-radius:12px; border:1px dashed rgba(255,255,255,0.1);">
                    <i class="fas fa-receipt" style="font-size:2.5rem; opacity:0.3; margin-bottom:15px; display:block;"></i>
                    <p>${LANG_DOM.no_orders}</p>
                </div>`;
            return;
        }

        const totalPages = Math.ceil(orders.length / DOMAIN_ORDERS_PER_PAGE);
        const start = (domainOrdersPage - 1) * DOMAIN_ORDERS_PER_PAGE;
        const pageOrders = orders.slice(start, start + DOMAIN_ORDERS_PER_PAGE);

        const statusColor = { COMPLETED: '#10b981', PENDING: '#f59e0b', FAILED: '#ef4444', CANCELLED: '#6b7280' };

        const rows = pageOrders.map(o => {
            const st = (o.status || '').toUpperCase();
            const color = statusColor[st] || '#9ca3af';
            const domainDisplay = o.domain_name || o.item_name || '—';
            const tld = o.product_domain ? '.' + o.product_domain : '—';
            return `
            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:10px 14px; color:var(--text-muted); font-size:0.85rem;">#${o.id}</td>
                <td style="padding:10px 14px; font-weight:600;">${domainDisplay}</td>
                <td style="padding:10px 14px; color:var(--text-muted);">${tld}</td>
                <td style="padding:10px 14px;">${o.domain_year || 1} ${LANG_DOM.years}</td>
                <td style="padding:10px 14px; color:var(--primary); font-weight:600;">${o.formatted_amount}</td>
                <td style="padding:10px 14px;">
                    <span style="color:${color}; font-weight:600; font-size:0.85rem;">${o.status_label || o.status}</span>
                </td>
                <td style="padding:10px 14px; color:var(--text-muted); font-size:0.85rem;">${o.formatted_date}</td>
            </tr>`;
        }).join('');

        const pagination = totalPages > 1 ? `
            <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; padding:12px 14px; border-top:1px solid rgba(255,255,255,0.05);">
                <button onclick="setDomainOrdersPage(${domainOrdersPage - 1}, currentFilteredOrders)" ${domainOrdersPage <= 1 ? 'disabled' : ''}
                    style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:var(--text-light); border-radius:6px; padding:5px 12px; cursor:pointer; font-size:0.85rem;">
                    &lsaquo;
                </button>
                <span style="font-size:0.85rem; color:var(--text-muted);">${domainOrdersPage} / ${totalPages}</span>
                <button onclick="setDomainOrdersPage(${domainOrdersPage + 1}, currentFilteredOrders)" ${domainOrdersPage >= totalPages ? 'disabled' : ''}
                    style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:var(--text-light); border-radius:6px; padding:5px 12px; cursor:pointer; font-size:0.85rem;">
                    &rsaquo;
                </button>
            </div>` : '';

        wrap.innerHTML = `
            <div style="overflow-x:auto; background:rgba(255,255,255,0.02); border-radius:12px; border:1px solid rgba(255,255,255,0.07);">
                <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                    <thead>
                        <tr style="color:var(--text-muted); border-bottom:1px solid rgba(255,255,255,0.07); text-align:left; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_id}</th>
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_domain}</th>
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_tld}</th>
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_period}</th>
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_amount}</th>
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_status}</th>
                            <th style="padding:12px 14px;">${LANG_DOM.tbl_date}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
                ${pagination}
            </div>`;

        // Store current filtered set so pagination buttons can reference it
        currentFilteredOrders = orders;
    }

    let currentFilteredOrders = [];

    function setDomainOrdersPage(page, orders) {
        const totalPages = Math.ceil(orders.length / DOMAIN_ORDERS_PER_PAGE);
        if (page < 1 || page > totalPages) return;
        domainOrdersPage = page;
        renderDomainOrders(orders);
    }

    // ── Filter both sections by domain name ──────────────────
    let _filterTimer = null;

    function filterDomains(query) {
        clearTimeout(_filterTimer);
        _filterTimer = setTimeout(() => {
            const q = query.toLowerCase().trim();

            const grid = document.getElementById('domainsGrid');
            Array.from(grid.children).forEach(card => {
                const name = card.querySelector('h3')?.textContent?.toLowerCase() || '';
                card.style.display = (!q || name.includes(q)) ? '' : 'none';
            });

            const filtered = !q ? allDomainOrders : allDomainOrders.filter(o =>
                (o.domain_name || o.item_name || '').toLowerCase().includes(q)
            );
            domainOrdersPage = 1;
            renderDomainOrders(filtered);
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDomainPrices();
        loadDomains();
        loadDomainOrders();
    });
</script>

<?php include 'footer.php'; ?>