// transactions.js — Transactions, Movements & Managed Services page

// ── Global state ───────────────────────────────────────────────────────────────
let currentTxPage  = 1;
let currentMovPage = 1;
let currentSvcPage = 1;
let servicesLoaded = false;
let currenciesData = [];

// ── Tab switching ──────────────────────────────────────────────────────────────
function switchTab(tabName, btnElement) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
}

// ── Recharge modal ─────────────────────────────────────────────────────────────
const rechargeModal = document.getElementById('rechargeModal');

function openRechargeModal() {
    rechargeModal.classList.add('active');
    loadCurrencies();
    document.getElementById('paymentForm').style.display    = 'block';
    document.getElementById('paymentResult').style.display  = 'none';
    document.getElementById('btnCreatePayment').style.display = 'block';
    document.getElementById('rechargeAmount').value         = '';
}

function closeRechargeModal() {
    rechargeModal.classList.remove('active');
    if (countdownInterval) clearInterval(countdownInterval);
}

function setAmount(val) {
    document.getElementById('rechargeAmount').value = val;
}

// ── Currencies ─────────────────────────────────────────────────────────────────
async function loadCurrencies() {
    if (currenciesData.length > 0) return;

    try {
        const res     = await fetch('../api/transactions/currencies');
        const rawData = await res.json();
        let list      = rawData.data || rawData;

        if (list && !Array.isArray(list) && typeof list === 'object') list = Object.values(list);
        if (!Array.isArray(list)) return;

        currenciesData = list;

        const topCoins = ['USDT','BTC','ETH','TRX','LTC','BNB'];
        const coinGrid      = document.getElementById('coinGrid');
        const moreCoinsGrid = document.getElementById('moreCoinsGrid');
        coinGrid.innerHTML = moreCoinsGrid.innerHTML = '';

        topCoins.forEach(sym => {
            const coin = list.find(c => (c.symbol || c.currency) === sym);
            if (coin) coinGrid.appendChild(createCoinBtn(coin, false));
        });
        list.filter(c => !topCoins.includes(c.symbol || c.currency))
            .forEach(c => moreCoinsGrid.appendChild(createCoinBtn(c, true)));

    } catch (e) { console.error('Error loading currencies', e); }
}

const COIN_LOGOS = {
    USDT:'USDT_Logo.png', BTC:'BTC.png',  ETH:'ETH.png',  TRX:'TRX.png',
    LTC:'LTC.png',  BNB:'BNB.png',  USDC:'USDC.png', DOGE:'DODGE.png',
    POL:'POL.png',  SOL:'SOL.png',  SHIB:'SHIB.png', TON:'TON.png',
    XMR:'XMR.png',  DAI:'DAI.png',  BCH:'BCH.png',   NOT:'NOT.png',
    DOGS:'DOGS.png', XRP:'XRP.png'
};

function createCoinBtn(coin, _small) {
    const sym  = coin.symbol || coin.currency;
    const logo = COIN_LOGOS[sym] || 'BTC.png';
    const btn  = document.createElement('div');
    btn.className      = `coin-btn crypto-${sym.toLowerCase()}`;
    btn.dataset.symbol = sym;
    btn.style.cssText  = 'display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;border:2px solid rgba(255,255,255,0.1);padding:8px 4px;border-radius:12px;cursor:pointer;background:rgba(255,255,255,0.02);transition:all .2s;';
    btn.innerHTML      = `<img src="/assets/img/crypto/${logo}" alt="${sym}" style="width:24px;height:24px;object-fit:contain;">
        <span style="font-weight:700;font-size:0.8rem;color:var(--text-light);pointer-events:none;">${sym}</span>`;
    btn.onclick = () => selectCoin(sym, btn);
    return btn;
}

document.getElementById('btnToggleMore').addEventListener('click', function () {
    const cnt  = document.getElementById('moreCoinsContainer');
    const open = cnt.style.display === 'none';
    cnt.style.display = open ? 'block' : 'none';
    this.innerHTML = open
        ? `<i class="fas fa-chevron-up"></i> ${LANG_TX.btn_less}`
        : `<i class="fas fa-chevron-down"></i> ${LANG_TX.btn_more}`;
});

function selectCoin(symbol, btnEl) {
    document.querySelectorAll('#coinGrid .coin-btn, #moreCoinsGrid .coin-btn').forEach(b => {
        b.classList.remove('selected');
        b.style.borderColor = 'rgba(255,255,255,0.1)';
        b.style.background  = 'rgba(255,255,255,0.02)';
    });
    if (btnEl) btnEl.classList.add('selected');
    document.getElementById('selectedCurrency').value = symbol;
    document.getElementById('selectedNetwork').value  = '';
    renderNetworks(symbol, 'networkGrid', 'networkGroup', 'selectedNetwork', '.net-btn');
}

function renderNetworks(symbol, gridId, groupId, hiddenId, btnSelector) {
    const group = document.getElementById(groupId);
    const grid  = document.getElementById(gridId);
    const coin  = currenciesData.find(c => (c.symbol || c.currency) === symbol);

    grid.innerHTML = '';
    if (!coin || !coin.networks) { group.style.display = 'none'; return; }

    group.style.display = 'block';
    const nets = Object.values(coin.networks);
    nets.forEach(net => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = btnSelector.replace('.','');
        let label = net.name || net.network;
        if (net.keys?.length) {
            const extras = net.keys.filter(k =>
                k.toLowerCase() !== (net.name||'').toLowerCase() &&
                k.toLowerCase() !== (net.network||'').toLowerCase());
            if (extras.length) label += ` (${extras.join(', ')})`;
        }
        btn.textContent = label;
        btn.style.cssText = 'border:1px solid var(--border);background:transparent;color:var(--text-light);padding:5px 12px;border-radius:20px;cursor:pointer;font-size:0.85rem;margin-right:5px;margin-bottom:5px;transition:all .2s;';
        btn.onclick = () => {
            grid.querySelectorAll('button').forEach(b => {
                b.style.borderColor = 'var(--border)';
                b.style.color       = 'var(--text-light)';
                b.style.background  = 'transparent';
            });
            btn.style.borderColor = 'var(--primary)';
            btn.style.color       = 'var(--primary)';
            btn.style.background  = 'rgba(0,243,255,0.1)';
            document.getElementById(hiddenId).value = net.network;
        };
        grid.appendChild(btn);
    });
    if (nets.length === 1) grid.firstChild.click();
}

function formatNetworkName(symbol, netKey) {
    const coin = currenciesData.find(c => (c.symbol || c.currency) === symbol);
    if (!coin?.networks) return netKey || '---';
    const found = Object.values(coin.networks).find(n => n.network === netKey || n.keys?.includes(netKey));
    return found ? `${found.name} (${netKey})` : (netKey || '---');
}

// ── Create recharge payment ────────────────────────────────────────────────────
async function createPayment() {
    const amount   = document.getElementById('rechargeAmount').value;
    const currency = document.getElementById('selectedCurrency').value;
    const network  = document.getElementById('selectedNetwork').value;

    if (!amount || !currency || !network) { alert(LANG_TX.err_complete_fields); return; }
    if (currency === 'BTC' && parseFloat(amount) < 10) { alert(LANG_TX.err_min_btc); return; }
    if (parseFloat(amount) < 5) { alert(LANG_TX.err_min_general); return; }

    const btn = document.getElementById('btnCreatePayment');
    btn.disabled  = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_TX.processing}`;

    try {
        const res  = await fetch('../api/transactions/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount, payment_currency: currency, network }),
        });
        const data = await res.json();

        if (data.success) {
            const tx = data.data;
            document.getElementById('paymentForm').style.display    = 'none';
            document.getElementById('btnCreatePayment').style.display = 'none';
            document.getElementById('paymentResult').style.display  = 'block';
            document.getElementById('qrCodeImg').src                = tx.qr_code;
            document.getElementById('payAmount').textContent        = tx.payment_amount;
            document.getElementById('payCurrency').textContent      = tx.payment_currency;
            document.getElementById('payNetwork').textContent       = formatNetworkName(tx.payment_currency, tx.network);
            document.getElementById('payAddress').textContent       = tx.address;
            startCountdown(tx.expired_at);
            loadTransactions();
        } else {
            document.getElementById('rechargeError').textContent = data.message || LANG_TX.err_create;
        }
    } catch (e) {
        document.getElementById('rechargeError').textContent = LANG_TX.err_connection;
    } finally {
        btn.disabled  = false;
        btn.innerHTML = LANG_TX.btn_generate;
    }
}

function copyAddress() {
    navigator.clipboard.writeText(document.getElementById('payAddress').textContent)
        .then(() => alert(LANG_TX.res_copied));
}

// ── Countdown ──────────────────────────────────────────────────────────────────
let countdownInterval;
function startCountdown(expiry) {
    if (countdownInterval) clearInterval(countdownInterval);
    const el = document.getElementById('expireTimer');
    const tick = () => {
        const diff = expiry - Math.floor(Date.now() / 1000);
        if (diff <= 0) {
            el.textContent = LANG_TX.res_expired;
            el.className   = 'text-red';
            clearInterval(countdownInterval);
            return;
        }
        el.className   = '';
        el.textContent = `${Math.floor(diff/60)}:${String(diff%60).padStart(2,'0')}`;
    };
    tick();
    countdownInterval = setInterval(tick, 1000);
}

// ── Pagination renderer ────────────────────────────────────────────────────────
function renderPagination(pagination, containerId, pageFunction) {
    const container = document.getElementById(containerId);
    if (!pagination || pagination.total <= pagination.limit) {
        container.innerHTML = '';
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';
    const { current_page: cur, pages: total, total: totalItems, limit } = pagination;
    const start = (cur - 1) * limit + 1;
    const end   = Math.min(cur * limit, totalItems);

    let startPage = Math.max(1, cur - 2);
    let endPage   = Math.min(total, cur + 2);
    if (startPage <= 2) endPage   = Math.min(5, total);
    if (endPage >= total - 1) startPage = Math.max(1, total - 4);

    let pages = '';
    for (let i = Math.max(1, startPage); i <= Math.min(total, endPage); i++) {
        pages += `<button class="page-btn ${i === cur ? 'active' : ''}" onclick="${pageFunction}(${i})">${i}</button>`;
    }

    container.innerHTML = `
        <div class="pagination-info">Showing ${start} - ${end} of ${totalItems}</div>
        <div class="pagination-controls">
            <button class="page-btn" ${cur === 1 ? 'disabled' : ''} onclick="${pageFunction}(${cur - 1})"><i class="fas fa-chevron-left"></i></button>
            ${pages}
            <button class="page-btn" ${cur === total ? 'disabled' : ''} onclick="${pageFunction}(${cur + 1})"><i class="fas fa-chevron-right"></i></button>
        </div>`;
}

// ── Transactions tab ───────────────────────────────────────────────────────────
function changeTxPage(page) { currentTxPage = page; loadTransactions(); }

async function loadTransactions() {
    const tbody = document.getElementById('transactionsTableBody');
    try {
        const res      = await fetch(`../api/transactions/list?page=${currentTxPage}&limit=10`);
        const response = await res.json();
        const data     = response.data || response;
        const pagination = response.pagination;

        if (!response.success || !data?.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">${LANG_TX.no_recharges}</td></tr>`;
            document.getElementById('transactionsPagination').innerHTML = '';
            return;
        }

        // Auto-expire stale PENDING transactions
        const now = Math.floor(Date.now() / 1000);
        await Promise.all(
            data.filter(tx => tx.status === 'PENDING' && tx.expired_at > 0 && now >= tx.expired_at)
                .map(tx => fetch('../api/transactions/expire', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: tx.id }),
                }).then(r => r.json()).then(r => { if (r.expired) tx.status = 'EXPIRED'; }).catch(() => {}))
        );

        tbody.innerHTML = '';
        data.forEach(tx => {
            const statusMap = {
                COMPLETED: ['status-badge status-running', LANG_TX.status_completed],
                FAILED:    ['status-badge status-stopped', LANG_TX.status_failed],
                EXPIRED:   ['status-expired',              LANG_TX.status_expired],
                PENDING:   ['status-badge status-pending', LANG_TX.status_pending],
            };
            const [cls, label] = statusMap[tx.status] || ['', tx.status];

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${tx.id}</td>
                <td><i class="${getCurrencyIcon(tx.payment_currency)}" style="margin-right:5px;color:var(--primary);"></i>
                    ${tx.payment_currency} <small style="color:var(--text-muted)">(${tx.network || ''})</small></td>
                <td><span class="${cls}">${label}</span></td>
                <td>$${parseFloat(tx.amount).toFixed(2)}</td>
                <td><a href="transaction_detail?id=${tx.id}" class="btn-sm"
                    style="background:var(--primary);color:white;border:none;padding:4px 12px;border-radius:4px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                    <i class="fas fa-eye"></i> ${LANG_TX.view_payment}</a></td>`;
            tbody.appendChild(tr);
        });

        if (pagination) renderPagination(pagination, 'transactionsPagination', 'changeTxPage');

    } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:red;">${LANG_TX.err_connection}</td></tr>`;
    }
}

// ── Movements tab ──────────────────────────────────────────────────────────────
function changeMovPage(page) { currentMovPage = page; loadMovements(); }

async function loadMovements() {
    const tbody = document.getElementById('movementsTableBody');
    try {
        const res      = await fetch(`../api/users/movements?page=${currentMovPage}&limit=10`);
        const response = await res.json();
        const data     = response.data || response;
        const pagination = response.pagination;

        if (!response.success || !data?.length) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">${LANG_TX.no_movements}</td></tr>`;
            document.getElementById('movementsPagination').innerHTML = '';
            return;
        }

        tbody.innerHTML = '';
        data.forEach(mov => {
            const pos = parseFloat(mov.amount) >= 0;
            const tr  = document.createElement('tr');
            tr.innerHTML = `
                <td>#${mov.id}</td>
                <td>${mov.description}</td>
                <td>${new Date(mov.created_at).toLocaleDateString(LANG_TX.date_format)}</td>
                <td class="${pos ? 'text-green' : 'text-red'}">${pos ? '+' : ''}$${parseFloat(mov.amount).toFixed(2)}</td>`;
            tbody.appendChild(tr);
        });

        if (pagination) renderPagination(pagination, 'movementsPagination', 'changeMovPage');

    } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:red;">${LANG_TX.err_connection}</td></tr>`;
    }
}

// ── Managed Services tab ───────────────────────────────────────────────────────
function changeSvcPage(page) { currentSvcPage = page; loadServices(); }

async function loadPendingServicesCount() {
    try {
        const res  = await fetch('../api/orders/pending_services_count');
        const data = await res.json();
        const badge = document.getElementById('svcPendingBadge');
        if (data.count > 0) {
            badge.textContent   = data.count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    } catch (e) { /* ignore */ }
}

async function loadServices() {
    servicesLoaded = true;
    const tbody = document.getElementById('servicesTableBody');
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">${LANG_TX.loading}</td></tr>`;

    try {
        const res      = await fetch(`../api/orders/list?type=managed_service&page=${currentSvcPage}&limit=10`);
        const response = await res.json();
        const data     = response.data || [];
        const pagination = response.pagination;

        if (!response.success || !data.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">
                <i class="fas fa-briefcase" style="font-size:1.5rem;margin-bottom:8px;display:block;color:var(--primary);"></i>
                ${LANG_TX.no_services}</td></tr>`;
            document.getElementById('servicesPagination').innerHTML = '';
            return;
        }

        tbody.innerHTML = '';
        data.forEach(order => {
            const date = new Date(order.created_at).toLocaleDateString(LANG_TX.date_format, {
                day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'
            });
            const statusClass = { COMPLETED:'status-completed', PENDING:'status-pending', FAILED:'status-failed', CANCELLED:'status-expired' }[order.status] || 'status-pending';
            const statusText  = { COMPLETED: LANG_TX.status_completed, PENDING: LANG_TX.status_pending, FAILED: LANG_TX.status_failed }[order.status] || order.status;
            const desc = (order.description || '').replace(/'/g, "\\'");

            const payBtn = order.status === 'PENDING' ? `
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                    <button onclick="payServiceBalance(${order.id}, this)"
                        style="background:var(--primary);color:#000;border:none;padding:4px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem;font-weight:600;">
                        <i class="fas fa-wallet"></i> ${LANG_TX.svc_pay_balance}
                    </button>
                    <button onclick="openSvcCryptoModal(${order.id}, '${desc}', ${order.total_amount})"
                        style="background:transparent;color:var(--primary);border:1px solid var(--primary);padding:4px 12px;border-radius:4px;cursor:pointer;font-size:0.8rem;font-weight:600;">
                        <i class="fas fa-coins"></i> ${LANG_TX.svc_pay_crypto}
                    </button>
                </div>` : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-family:monospace;color:var(--text-muted);">#${order.id}</td>
                <td style="max-width:260px;">
                    <div style="font-weight:600;color:var(--text-light);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <i class="fas fa-briefcase" style="color:var(--primary);margin-right:6px;font-size:0.85em;"></i>
                        ${order.description || '—'}
                    </div>
                    ${payBtn}
                </td>
                <td style="color:var(--text-muted);white-space:nowrap;">${date}</td>
                <td style="font-weight:700;color:var(--text-light);">$${parseFloat(order.total_amount).toFixed(2)}</td>
                <td><span class="${statusClass}">${statusText}</span></td>`;
            tbody.appendChild(tr);
        });

        if (pagination) renderPagination(pagination, 'servicesPagination', 'changeSvcPage');

    } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:red;">${LANG_TX.err_connection}</td></tr>`;
    }
}

async function payServiceBalance(orderId, btn) {
    if (!confirm(LANG_TX.svc_confirm_balance)) return;

    const original = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const res  = await fetch('../api/orders/pay_service', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId }),
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('displayBalance').textContent = '$' + parseFloat(data.new_balance).toFixed(2);
            currentSvcPage = 1;
            servicesLoaded = false;
            loadServices();
            loadPendingServicesCount();
        } else {
            alert(data.message || LANG_TX.svc_pay_failed);
            btn.disabled  = false;
            btn.innerHTML = original;
        }
    } catch (e) {
        alert(LANG_TX.err_connection);
        btn.disabled  = false;
        btn.innerHTML = original;
    }
}

// ── Service crypto payment modal ───────────────────────────────────────────────
let svcOrderId       = null;
let svcCryptoCountdown = null;
let svcCoinsLoaded   = false;

function openSvcCryptoModal(orderId, description, amount) {
    svcOrderId = orderId;
    document.getElementById('svcCryptoModal').classList.add('active');
    document.getElementById('svcCryptoDesc').textContent = description;
    document.getElementById('svcCryptoAmt').textContent  = '$' + parseFloat(amount).toFixed(2);
    document.getElementById('svcCryptoStep1').style.display  = 'block';
    document.getElementById('svcCryptoResult').style.display = 'none';
    document.getElementById('btnSvcCryptoPay').style.display = 'block';
    document.getElementById('svcCryptoError').textContent    = '';
    document.getElementById('svcSelectedCurrency').value     = '';
    document.getElementById('svcSelectedNetwork').value      = '';
    document.getElementById('svcNetworkGroup').style.display = 'none';
    if (!svcCoinsLoaded) buildSvcCoinGrid();
}

function closeSvcCryptoModal() {
    document.getElementById('svcCryptoModal').classList.remove('active');
    if (svcCryptoCountdown) clearInterval(svcCryptoCountdown);
}

function buildSvcCoinGrid() {
    if (!currenciesData.length) { loadCurrencies().then(buildSvcCoinGrid); return; }
    svcCoinsLoaded = true;

    const topCoins = ['USDT','BTC','ETH','TRX','LTC','BNB'];
    const grid     = document.getElementById('svcCoinGrid');
    const moreGrid = document.getElementById('svcMoreCoinsGrid');
    grid.innerHTML = moreGrid.innerHTML = '';

    const mkBtn = (coin) => {
        const sym  = coin.symbol || coin.currency;
        const logo = COIN_LOGOS[sym] || 'BTC.png';
        const btn  = document.createElement('div');
        btn.className      = `coin-btn crypto-${sym.toLowerCase()}`;
        btn.dataset.symbol = sym;
        btn.style.cssText  = 'display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;border:2px solid rgba(255,255,255,0.1);padding:8px 4px;border-radius:12px;cursor:pointer;background:rgba(255,255,255,0.02);transition:all .2s;';
        btn.innerHTML      = `<img src="/assets/img/crypto/${logo}" alt="${sym}" style="width:24px;height:24px;object-fit:contain;">
            <span style="font-weight:700;font-size:0.8rem;color:var(--text-light);">${sym}</span>`;
        btn.onclick = () => {
            document.querySelectorAll('#svcCoinGrid .coin-btn, #svcMoreCoinsGrid .coin-btn').forEach(b => {
                b.classList.remove('selected');
                b.style.borderColor = 'rgba(255,255,255,0.1)';
                b.style.background  = 'rgba(255,255,255,0.02)';
            });
            btn.classList.add('selected');
            document.getElementById('svcSelectedCurrency').value = sym;
            document.getElementById('svcSelectedNetwork').value  = '';
            renderNetworks(sym, 'svcNetworkGrid', 'svcNetworkGroup', 'svcSelectedNetwork', 'svc-net-btn');
        };
        return btn;
    };

    topCoins.forEach(sym => {
        const c = currenciesData.find(c => (c.symbol||c.currency) === sym);
        if (c) grid.appendChild(mkBtn(c));
    });
    currenciesData.filter(c => !topCoins.includes(c.symbol||c.currency))
        .forEach(c => moreGrid.appendChild(mkBtn(c)));

    document.getElementById('svcBtnToggleMore').onclick = function () {
        const cnt  = document.getElementById('svcMoreCoinsContainer');
        const open = cnt.style.display === 'none';
        cnt.style.display = open ? 'block' : 'none';
        this.innerHTML = open
            ? `<i class="fas fa-chevron-up"></i> ${LANG_TX.btn_less}`
            : `<i class="fas fa-chevron-down"></i> ${LANG_TX.btn_more}`;
    };
}

async function submitSvcCryptoPayment() {
    const currency = document.getElementById('svcSelectedCurrency').value;
    const network  = document.getElementById('svcSelectedNetwork').value;
    const errDiv   = document.getElementById('svcCryptoError');
    const btn      = document.getElementById('btnSvcCryptoPay');

    if (!currency || !network) { errDiv.textContent = LANG_TX.err_complete_fields; return; }

    btn.disabled  = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_TX.processing}`;

    try {
        const res  = await fetch('../api/orders/pay_service_crypto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: svcOrderId, payment_currency: currency, network }),
        });
        const data = await res.json();

        if (data.success) {
            const p = data.data;
            document.getElementById('svcCryptoStep1').style.display  = 'none';
            document.getElementById('btnSvcCryptoPay').style.display = 'none';
            document.getElementById('svcCryptoResult').style.display = 'block';
            document.getElementById('svcQrImg').src                  = p.qr_code;
            document.getElementById('svcPayAmt').textContent         = p.payment_amount;
            document.getElementById('svcPayCur').textContent         = p.payment_currency;
            document.getElementById('svcPayNet').textContent         = formatNetworkName(p.payment_currency, p.network);
            document.getElementById('svcPayAddr').textContent        = p.address;
            startSvcCountdown(p.expired_at);
            loadServices();
        } else {
            errDiv.textContent = data.message || LANG_TX.err_create;
        }
    } catch (e) {
        errDiv.textContent = LANG_TX.err_connection;
    } finally {
        btn.disabled  = false;
        btn.innerHTML = LANG_TX.btn_generate;
    }
}

function startSvcCountdown(expiry) {
    if (svcCryptoCountdown) clearInterval(svcCryptoCountdown);
    const el = document.getElementById('svcExpireTimer');
    const tick = () => {
        const diff = expiry - Math.floor(Date.now() / 1000);
        if (diff <= 0) { el.textContent = LANG_TX.res_expired; el.style.color = '#ef4444'; clearInterval(svcCryptoCountdown); return; }
        el.style.color = '';
        el.textContent = `${Math.floor(diff/60)}:${String(diff%60).padStart(2,'0')}`;
    };
    tick();
    svcCryptoCountdown = setInterval(tick, 1000);
}

function copySvcAddress() {
    navigator.clipboard.writeText(document.getElementById('svcPayAddr').textContent)
        .then(() => alert(LANG_TX.res_copied));
}

// ── Payment detail modal (existing transactions) ───────────────────────────────
function viewTransaction(tx) {
    const now       = Math.floor(Date.now() / 1000);
    const isPending = tx.status === 'PENDING';
    const isExpired = isPending && tx.expired_at > 0 && now >= tx.expired_at;

    if (isPending && !isExpired) {
        viewPayment(tx.qr_code, tx.payment_amount, tx.payment_currency, tx.address, tx.expired_at, tx.network);
    } else {
        viewPaymentDetails(tx.id);
    }
}

async function viewPaymentDetails(txId) {
    const detailModal = document.getElementById('payDetailModal');
    const body        = document.getElementById('payDetailBody');
    detailModal.classList.add('active');
    body.innerHTML = `<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i><p style="margin-top:14px;color:var(--text-muted);">${LANG_TX.detail_loading}</p></div>`;

    try {
        const res  = await fetch(`../api/transactions/payment_info?id=${txId}`);
        const resp = await res.json();

        if (!resp.success) { body.innerHTML = `<p style="color:#ef4444;text-align:center;padding:20px;">${resp.message || LANG_TX.detail_error}</p>`; return; }

        const d   = resp.data;
        const loc = resp.local;

        if (d && ['expired','cancelled','canceled'].includes((d.status||'').toLowerCase()) && loc.status === 'PENDING') {
            setTimeout(() => loadTransactions(), 800);
        }

        const statusColors = { paid:'#10b981', expired:'#f59e0b', cancelled:'#f59e0b', pending:'#3b82f6', unpaid:'#3b82f6', failed:'#ef4444' };
        const statusColor  = statusColors[(d?.status||loc.status||'').toLowerCase()] || '#94a3b8';
        const fmt   = ts => ts ? new Date(ts * 1000).toLocaleString() : '–';
        const money = v  => v != null ? `$${parseFloat(v).toFixed(4)}` : '–';
        const bool  = v  => v ? 'Yes' : 'No';

        let txsHtml = '';
        if (d?.txs?.length > 0) {
            txsHtml = `<div style="margin-top:18px;"><div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">${LANG_TX.lbl_txs}</div>`;
            d.txs.forEach((t, i) => {
                const autoConv = t.auto_convert?.processed ? `${money(t.auto_convert.amount)} ${t.auto_convert.currency}` : 'No';
                txsHtml += `<div class="tx-block">
                    <div style="font-size:0.75rem;color:var(--primary);font-weight:700;margin-bottom:8px;">Tx #${i+1}</div>
                    <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_hash}</span><span class="detail-value" style="font-family:monospace;font-size:0.72rem;">${t.tx_hash||'–'}</span></div>
                    <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_network}</span><span class="detail-value">${t.network||'–'} (${t.currency||''})</span></div>
                    <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_received}</span><span class="detail-value" style="color:#10b981;">${money(t.amount)} ${t.currency||''}</span></div>
                    <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_confirms}</span><span class="detail-value">${t.confirmations??'–'}</span></div>
                    <div class="detail-row"><span class="detail-label">Auto Conversion</span><span class="detail-value">${autoConv}</span></div>
                    <div class="detail-row"><span class="detail-label">Address</span><span class="detail-value" style="font-family:monospace;font-size:0.72rem;word-break:break-all;">${t.address||'–'}</span></div>
                    <div class="detail-row"><span class="detail-label">Date</span><span class="detail-value">${fmt(t.date)}</span></div>
                </div>`;
            });
            txsHtml += '</div>';
        }

        body.innerHTML = `<div style="padding:4px 0;">
            <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_track}</span><span class="detail-value" style="font-family:monospace;">${d?.track_id||loc.track_id||'–'}</span></div>
            <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_status}</span><span class="detail-value" style="color:${statusColor};font-weight:700;">${d?.status||loc.status}</span></div>
            <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_amount}</span><span class="detail-value">${money(d?.amount??loc.amount)} ${d?.currency??'USD'}</span></div>
            <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_currency}</span><span class="detail-value">${loc.payment_currency||'–'} / ${loc.network||'–'}</span></div>
            ${d ? `
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${d.description||'–'}</span></div>
            <div class="detail-row"><span class="detail-label">Order ID</span><span class="detail-value" style="font-family:monospace;">${d.order_id||'–'}</span></div>
            <div class="detail-row"><span class="detail-label">Lifetime</span><span class="detail-value">${d.lifetime ? d.lifetime+' min' : '–'}</span></div>
            <div class="detail-row"><span class="detail-label">Mixed Payment</span><span class="detail-value">${bool(d.mixed_payment)}</span></div>` : ''}
            <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_date}</span><span class="detail-value">${fmt(d?.date)||new Date(loc.created_at).toLocaleString()}</span></div>
            <div class="detail-row"><span class="detail-label">${LANG_TX.lbl_expires}</span><span class="detail-value">${fmt(d?.expired_at??loc.expired_at)}</span></div>
            ${txsHtml}
        </div>`;

    } catch (e) {
        console.error(e);
        body.innerHTML = `<p style="color:#ef4444;text-align:center;padding:20px;">${LANG_TX.detail_error}</p>`;
    }
}

function viewPayment(qr, amount, currency, address, expiry, network) {
    document.getElementById('paymentForm').style.display      = 'none';
    document.getElementById('btnCreatePayment').style.display = 'none';
    document.getElementById('paymentResult').style.display    = 'block';
    document.getElementById('qrCodeImg').src                  = qr;
    document.getElementById('payAmount').textContent          = amount;
    document.getElementById('payCurrency').textContent        = currency;
    document.getElementById('payNetwork').textContent         = formatNetworkName(currency, network);
    document.getElementById('payAddress').textContent         = address;
    startCountdown(expiry);
    rechargeModal.classList.add('active');
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function getCurrencyIcon(currency) {
    if (!currency) return 'fas fa-coins';
    return { BTC:'fab fa-bitcoin', ETH:'fab fa-ethereum', TRX:'fas fa-network-wired', USDT:'fas fa-dollar-sign' }[currency] || 'fas fa-coins';
}

// ── Init ───────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await loadCurrencies();
    loadTransactions();
    loadMovements();
    loadPendingServicesCount();

    try {
        const res  = await fetch('../api/users/me');
        const data = await res.json();
        if (data.success) {
            document.getElementById('displayBalance').textContent = '$' + parseFloat(data.data.balance || 0).toFixed(2);
        }
    } catch (e) { console.error(e); }
});
