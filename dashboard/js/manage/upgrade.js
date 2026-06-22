let currentMode = "";
let selectedActionPayMethod = null;
let selectedActionCurrency = null;
let actionCurrencies = [];

function openUpgradeFlow() {
    selectedActionPayMethod = null;
    selectedActionCurrency = null;
    actionCurrencies = [];
    window.currentActionAmount = 0;
    document.getElementById('upgradeModal').classList.add('active');
}

function closeUpgradeModal() {
    document.getElementById('upgradeModal').classList.remove('active');
    document.getElementById('processResult').style.display = 'none';
    const cryptoSel = document.getElementById('crypto-currency-selector-action');
    if (cryptoSel) cryptoSel.style.display = 'none';
    const errDiv = document.getElementById('stepActionError');
    if (errDiv) errDiv.style.display = 'none';
    const btn = document.getElementById('btnProcessOrder');
    if (btn) { btn.style.display = 'none'; btn.disabled = false; }
}

function goBackToStep1() {
    // No-op
}

async function goToStep2(mode) {
    currentMode = mode;
    document.getElementById('step2-config').style.display = 'block';

    document.getElementById('selectedDuration').value = "";
    document.getElementById('selectedUpgradePlanId').value = "";
    document.querySelectorAll('.duration-box').forEach(b => b.classList.remove('selected'));
    document.querySelectorAll('.plan-option-card').forEach(c => c.classList.remove('selected'));
    window.currentActionAmount = 0;

    selectedActionPayMethod = null;
    selectedActionCurrency = null;
    actionCurrencies = [];
    const cryptoSel = document.getElementById('crypto-currency-selector-action');
    if (cryptoSel) cryptoSel.style.display = 'none';
    const errDiv = document.getElementById('stepActionError');
    if (errDiv) errDiv.style.display = 'none';

    document.getElementById('priceEstimateContainer').style.display = 'none';

    if (mode === 'renew') {
        document.getElementById('upgradeModalTitle').textContent = LANG_MAN.opt_extend;
        document.getElementById('renewOptions').style.display = 'block';
        document.getElementById('upgradeOptions').style.display = 'none';

        loadUserBalance('action-balance-display');
        selectActionPayMethod('balance');
    } else {
        document.getElementById('upgradeModalTitle').textContent = LANG_MAN.opt_upgrade;
        document.getElementById('renewOptions').style.display = 'none';
        document.getElementById('upgradeOptions').style.display = 'block';

        loadUserBalance('action-balance-display');
        selectActionPayMethod('balance');
        await loadUpgradePlans();
    }
}

function evaluateActionPayRules() {
    const errDiv = document.getElementById('stepActionError');
    const btnProcess = document.getElementById('btnProcessOrder');
    const btnGoToCrypto = document.getElementById('btnGoToCrypto');
    const amount = window.currentActionAmount || 0;

    if (selectedActionPayMethod === 'crypto') {
        if (amount > 0 && amount < 1) {
            errDiv.textContent = 'El monto mínimo requerido para pagos con Cripto es de $1.00 USD. Sube la duración o usa tu saldo.';
            errDiv.style.display = 'block';
            if (btnGoToCrypto) btnGoToCrypto.style.display = 'none';
        } else {
            if (errDiv.dataset.manualError !== 'true') errDiv.style.display = 'none';
            if (btnGoToCrypto) btnGoToCrypto.style.display = 'inline-block';
        }
        if (btnProcess) btnProcess.style.display = 'none';
    } else {
        if (errDiv.dataset.manualError !== 'true') errDiv.style.display = 'none';
        if (btnProcess) btnProcess.style.display = 'inline-block';
        if (btnGoToCrypto) btnGoToCrypto.style.display = 'none';
    }
}

function selectActionPayMethod(method) {
    selectedActionPayMethod = method;
    document.getElementById('pay-option-balance-action').style.borderColor = (method === 'balance') ? 'var(--primary)' : 'rgba(255,255,255,0.1)';
    document.getElementById('pay-option-balance-action').style.background = (method === 'balance') ? 'rgba(var(--primary-rgb), 0.1)' : 'rgba(255,255,255,0.03)';

    document.getElementById('pay-option-crypto-action').style.borderColor = (method === 'crypto') ? 'var(--primary)' : 'rgba(255,255,255,0.1)';
    document.getElementById('pay-option-crypto-action').style.background = (method === 'crypto') ? 'rgba(var(--primary-rgb), 0.1)' : 'rgba(255,255,255,0.03)';

    const btnProcess = document.getElementById('btnProcessOrder');
    const btnGoToCrypto = document.getElementById('btnGoToCrypto');

    if (method === 'crypto') {
        document.getElementById('user-balance-renew-container').style.display = 'none';
        if (btnProcess) btnProcess.style.display = 'none';
        if (btnGoToCrypto) btnGoToCrypto.style.display = 'inline-block';
        if (actionCurrencies.length === 0) {
            loadActionCurrencies();
        }
    } else {
        document.getElementById('user-balance-renew-container').style.display = (currentMode === 'renew') ? 'block' : 'none';
        document.getElementById('btnProcessText').textContent = (currentMode === 'renew') ? LANG_MAN.opt_btn_extend : LANG_MAN.opt_btn_upgrade;
        if (btnProcess) btnProcess.style.display = 'inline-block';
        if (btnGoToCrypto) btnGoToCrypto.style.display = 'none';
    }

    evaluateActionPayRules();
}

function openCryptoSelectModal() {
    const errDiv = document.getElementById('stepActionError');
    if (currentMode === 'renew') {
        const duration = document.getElementById('selectedDuration').value;
        if (!duration) {
            errDiv.textContent = 'Selecciona una duración antes de continuar.';
            errDiv.style.display = 'block';
            return;
        }
        const amount = window.currentActionAmount || 0;
        if (amount > 0 && amount < 1) {
            errDiv.textContent = 'El monto mínimo para pago con cripto es $1.00 USD. Sube la duración.';
            errDiv.style.display = 'block';
            return;
        }
    } else if (currentMode === 'upgrade') {
        const planId = document.getElementById('selectedUpgradePlanId').value;
        if (!planId) {
            errDiv.textContent = 'Selecciona un plan antes de continuar.';
            errDiv.style.display = 'block';
            return;
        }
    }
    errDiv.style.display = 'none';
    document.getElementById('cryptoSelectModal').classList.add('active');
    selectedActionCurrency = null;
    document.getElementById('act-networkGroup').style.display = 'none';
    document.getElementById('cryptoSelectError').style.display = 'none';
    if (actionCurrencies.length > 0) {
        renderActionCurrencies();
    } else {
        loadActionCurrencies();
    }
}

function closeCryptoSelectModal() {
    document.getElementById('cryptoSelectModal').classList.remove('active');
    selectedActionCurrency = null;
}

async function loadActionCurrencies() {
    const grid = document.getElementById('act-coinGrid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    try {
        const res = await fetch('/api/transactions/currencies');
        const data = await res.json();

        let list = data.data || data;
        if (!Array.isArray(list) && typeof list === 'object') list = Object.values(list);

        if (Array.isArray(list) && list.length > 0) {
            actionCurrencies = list;
            renderActionCurrencies();
        } else {
            throw new Error("Invalid currency data format");
        }
    } catch (e) {
        console.error("Error loading currencies:", e);
        grid.innerHTML = '<div style="color: red; grid-column: 1/-1;">Error loading cryptocurrencies</div>';
    }
}

function renderActionCurrencies() {
    const mainGrid = document.getElementById('act-coinGrid');
    const moreGrid = document.getElementById('act-moreCoinsGrid');
    mainGrid.innerHTML = '';
    moreGrid.innerHTML = '';

    const topCoins = ['USDT', 'BTC', 'ETH', 'TRX', 'LTC', 'BNB'];

    const createVpsCoinBtn = (coin) => {
        const sym = coin.symbol || coin.currency;

        const logos = {
            'USDT': 'USDT_Logo.png', 'BTC': 'BTC.png', 'ETH': 'ETH.png',
            'TRX': 'TRX.png', 'LTC': 'LTC.png', 'BNB': 'BNB.png',
            'USDC': 'USDC.png', 'DOGE': 'DODGE.png', 'POL': 'POL.png',
            'SOL': 'SOL.png', 'SHIB': 'SHIB.png', 'TON': 'TON.png',
            'XMR': 'XMR.png', 'DAI': 'DAI.png', 'BCH': 'BCH.png',
            'NOT': 'NOT.png', 'DOGS': 'DOGS.png', 'XRP': 'XRP.png'
        };
        const logoFile = logos[sym] || 'BTC.png';

        const btn = document.createElement('div');
        btn.className = `crypto-card crypto-${sym.toLowerCase()}`;
        btn.dataset.symbol = sym;
        btn.style.cssText = 'display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border: 2px solid rgba(255,255,255,0.1); padding: 8px 4px; border-radius: 12px; cursor: pointer; background: rgba(255,255,255,0.02); transition: all 0.2s;';

        btn.innerHTML = `
            <img src="/assets/img/crypto/${logoFile}" alt="${sym}" style="width: 24px; height: 24px; object-fit: contain;">
            <span style="font-weight: 700; font-size: 0.8rem; color: var(--text-light); pointer-events: none;">${sym}</span>
        `;

        btn.onclick = () => selectActionCurrency(sym, btn);
        return btn;
    };

    topCoins.forEach(sym => {
        const coin = actionCurrencies.find(c => (c.symbol === sym || c.currency === sym));
        if (coin) mainGrid.appendChild(createVpsCoinBtn(coin));
    });

    actionCurrencies.filter(c => !topCoins.includes(c.symbol || c.currency)).forEach(coin => {
        moreGrid.appendChild(createVpsCoinBtn(coin));
    });

    const btnToggle = document.getElementById('act-btnToggleMore');
    btnToggle.onclick = () => {
        const cont = document.getElementById('act-moreCoinsContainer');
        if (cont.style.display === 'none') {
            cont.style.display = 'block';
            btnToggle.innerHTML = '<i class="fas fa-chevron-up"></i> Show less';
        } else {
            cont.style.display = 'none';
            btnToggle.innerHTML = '<i class="fas fa-chevron-down"></i> See more coins';
        }
    };

    const extraCoins = actionCurrencies.filter(c => !topCoins.includes(c.symbol || c.currency));
    if (extraCoins.length === 0) {
        btnToggle.style.display = 'none';
        document.getElementById('act-moreCoinsContainer').style.display = 'none';
    } else {
        btnToggle.style.display = 'block';
    }
}

function selectActionCurrency(symbol, el) {
    document.querySelectorAll('#cryptoSelectModal .crypto-card').forEach(c => {
        c.classList.remove('selected');
        c.style.borderColor = 'rgba(255,255,255,0.1)';
        c.style.background = 'rgba(255,255,255,0.02)';
    });
    el.classList.add('selected');
    el.style.borderColor = 'var(--primary)';
    el.style.background = 'rgba(0,243,255,0.07)';

    const coin = actionCurrencies.find(c => (c.symbol === symbol || c.currency === symbol));
    selectedActionCurrency = { symbol: symbol, network: null };

    const netGroup = document.getElementById('act-networkGroup');
    const netGrid = document.getElementById('act-networkGrid');
    netGrid.innerHTML = '';

    if (coin && coin.networks) {
        netGroup.style.display = 'block';
        const networks = Object.values(coin.networks);

        networks.forEach((net, idx) => {
            const btn = document.createElement('div');
            btn.className = 'network-pill';
            btn.style.cssText = 'padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); font-size: 0.82rem; cursor: pointer; color: var(--text-light); transition: all 0.2s;';

            let acronym = '';
            if (net.keys && Array.isArray(net.keys)) {
                const found = net.keys.find(k => k.length >= 2 && k.length <= 6 && k === k.toUpperCase());
                if (found && found !== net.network) acronym = found;
            }
            btn.textContent = net.name + (acronym ? ` (${acronym})` : '');

            btn.onclick = () => selectActionNetwork(net.network, btn);
            netGrid.appendChild(btn);

            if (idx === 0) {
                selectActionNetwork(net.network, btn);
            }
        });
    } else {
        netGroup.style.display = 'none';
    }
}

function selectActionNetwork(network, el) {
    document.querySelectorAll('#cryptoSelectModal .network-pill').forEach(c => {
        c.classList.remove('selected');
        c.style.background = 'transparent';
        c.style.color = 'var(--text-light)';
        c.style.borderColor = 'rgba(255,255,255,0.2)';
    });
    el.classList.add('selected');
    el.style.background = 'var(--primary)';
    el.style.color = '#fff';
    el.style.borderColor = 'var(--primary)';
    selectedActionCurrency.network = network;
}

function selectDuration(hours, element) {
    document.getElementById('customDurationVal').value = '';
    document.getElementById('customDurationHelper').textContent = '0 Horas';

    document.querySelectorAll('.duration-box').forEach(b => b.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedDuration').value = hours;

    updatePriceEstimate(hours);
}

function updateCustomDuration() {
    document.querySelectorAll('.duration-box').forEach(b => b.classList.remove('selected'));

    const val = parseInt(document.getElementById('customDurationVal').value) || 0;
    const mult = parseInt(document.getElementById('customDurationUnit').value) || 1;
    const totalHours = val * mult;

    const helper = document.getElementById('customDurationHelper');

    if (totalHours > 8760) {
        helper.textContent = 'Máximo 1 año (8760 horas)';
        helper.style.color = '#ef4444';
        document.getElementById('selectedDuration').value = "";
        document.getElementById('priceEstimateContainer').style.display = 'none';
        return;
    }

    helper.style.color = 'var(--text-muted)';
    helper.textContent = totalHours + ' Horas en total';
    document.getElementById('selectedDuration').value = totalHours > 0 ? totalHours : "";

    if (totalHours > 0) {
        updatePriceEstimate(totalHours);
    } else {
        document.getElementById('priceEstimateContainer').style.display = 'none';
    }
}

async function updatePriceEstimate(duration) {
    if (!duration || duration <= 0) {
        document.getElementById('priceEstimateContainer').style.display = 'none';
        return;
    }

    if (!serverId) {
        console.error('Server ID is not defined!');
        document.getElementById('priceEstimateContainer').style.display = 'none';
        return;
    }

    try {
        const url = `/api/orders/calculate_price?server_id=${serverId}&duration=${duration}`;
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            const priceData = data.data;
            window.currentActionAmount = parseFloat(priceData.total_amount) || 0;
            evaluateActionPayRules();

            document.getElementById('priceEstimatePlan').textContent = formatCurrency(priceData.plan_price);
            document.getElementById('priceEstimateTotal').textContent = formatCurrency(priceData.total_amount);
            document.getElementById('priceEstimateDuration').textContent = priceData.duration;
            document.getElementById('priceEstimateHourly').textContent = priceData.hourly_rate.toFixed(4);

            if (priceData.addons_price > 0) {
                document.getElementById('priceEstimateAddonsRow').style.display = 'flex';
                document.getElementById('priceEstimateAddons').textContent = formatCurrency(priceData.addons_price);
            } else {
                document.getElementById('priceEstimateAddonsRow').style.display = 'none';
            }

            document.getElementById('priceEstimateContainer').style.display = 'block';
        } else {
            console.error('Error calculating price:', data.message);
            document.getElementById('priceEstimateContainer').style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching price estimate:', error);
        document.getElementById('priceEstimateContainer').style.display = 'none';
    }
}

async function loadUpgradePlans() {
    const grid = document.getElementById('upgradePlanGrid');
    grid.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 20px;"><i class="fas fa-circle-notch fa-spin"></i> Cargando planes...</div>';

    try {
        const res = await fetch('/api/plans/list');
        const data = await res.json();
        if (data.success) {
            renderUpgradePlans(data.data);
        }
    } catch (e) {
        grid.innerHTML = '<div style="color: red;">Error cargando planes</div>';
    }
}

function renderUpgradePlans(plans) {
    const grid = document.getElementById('upgradePlanGrid');
    grid.innerHTML = '';

    const remainingHours = Math.ceil(window.vpsRemainingHours || 0);
    const addonsPrice    = window.vpsAddonsPrice || 0;
    const oldHourly      = ((currentPlanPrice + addonsPrice) > 0)
        ? (currentPlanPrice + addonsPrice) / 720 : 0;

    const upgradablePlans = plans
        .filter(plan => parseFloat(plan.price || 0) > currentPlanPrice)
        .map(plan => {
            const newPlanPrice = parseFloat(plan.price || 0);
            const newHourly    = ((newPlanPrice + addonsPrice) > 0)
                ? (newPlanPrice + addonsPrice) / 720 : 0;
            const upgradeCost  = Math.max(
                0,
                Math.round(((newHourly - oldHourly) * remainingHours) * 100) / 100
            );
            return { ...plan, upgradeCost };
        })
        .filter(plan => remainingHours === 0 || plan.upgradeCost >= 1);

    if (upgradablePlans.length === 0) {
        grid.innerHTML = `
            <div style="text-align: center; color: var(--text-muted); padding: 40px; grid-column: 1/-1;">
                <i class="fas fa-info-circle" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                <p style="font-size: 1.1rem; margin-bottom: 10px;">Ya tienes el plan más alto disponible</p>
                <p style="font-size: 0.9rem; opacity: 0.7;">No hay planes superiores con un costo de actualización mayor a $1.00 USD.</p>
            </div>
        `;
        return;
    }

    upgradablePlans.forEach(plan => {
        const card = document.createElement('div');
        card.className = 'plan-card';

        card.onclick = () => {
            document.querySelectorAll('#upgradeModal .plan-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('selectedUpgradePlanId').value = plan.id;
            window.currentActionAmount = plan.upgradeCost;
        };

        const cpu  = plan.cpu || plan.vcpu || '?';
        const ram  = plan.ram || '?';
        const disk = plan.disk || '?';

        card.innerHTML = `
            <div class="plan-name">${plan.name}</div>
            <div class="plan-specs">
                <div><i class="fas fa-microchip"></i> ${cpu} vCPU</div>
                <div><i class="fas fa-memory"></i> ${ram} GB RAM</div>
                <div><i class="fas fa-hdd"></i> ${disk} GB</div>
            </div>
            <div class="plan-price">$${parseFloat(plan.price).toFixed(2)}/mo</div>
            ${remainingHours > 0 ? `<div style="font-size:0.78rem; color:var(--primary); margin-top:4px; font-weight:600;">Upgrade ahora: $${plan.upgradeCost.toFixed(2)} USD</div>` : ''}
        `;
        grid.appendChild(card);
    });
}

async function processOrder() {
    let payload = {
        server_id: serverId,
        action: currentMode
    };

    const errDiv = document.getElementById('cryptoSelectError') || document.getElementById('stepActionError');
    errDiv.dataset.manualError = 'false';
    errDiv.style.display = 'none';

    if (currentMode === 'renew') {
        const duration = document.getElementById('selectedDuration').value;
        if (!duration) { showNotification('warning', LANG_MAN.js_warn, 'Selecciona una duración'); return; }
        payload.value = duration;
    } else if (currentMode === 'upgrade') {
        const planId = document.getElementById('selectedUpgradePlanId').value;
        if (!planId) { showNotification('warning', LANG_MAN.js_warn, 'Selecciona un plan'); return; }
        payload.value = planId;
    }

    if (selectedActionPayMethod === 'crypto') {
        if (!selectedActionCurrency || !selectedActionCurrency.symbol) {
            errDiv.textContent = 'Selecciona una criptomoneda para continuar.';
            errDiv.dataset.manualError = 'true';
            errDiv.style.display = 'block';
            return;
        }
        if (!selectedActionCurrency.network) {
            errDiv.textContent = 'Selecciona una red (network) para la criptomoneda.';
            errDiv.dataset.manualError = 'true';
            errDiv.style.display = 'block';
            return;
        }
        const amount = window.currentActionAmount || 0;
        if (amount > 0 && amount < 1) {
            errDiv.textContent = 'El monto mínimo para pagos directos con cripto es $1.00 USD.';
            errDiv.dataset.manualError = 'true';
            errDiv.style.display = 'block';
            return;
        }
        payload.payment_currency = selectedActionCurrency.symbol;
        payload.network = selectedActionCurrency.network;
    }

    const activeBtn = document.getElementById('btnCreateCryptoInvoice') || document.getElementById('btnProcessOrder');
    const originalText = activeBtn.innerHTML;
    activeBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_MAN.js_processing}`;
    activeBtn.disabled = true;

    try {
        if (selectedActionPayMethod === 'crypto') {
            const r1 = await fetch('/api/orders/process', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...payload, payment_method: 'crypto' })
            });
            const d1 = await r1.json();
            if (!d1.success) throw new Error(d1.message || 'Failed to create order');

            const r2 = await fetch('/api/orders/create_action_invoice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id:         d1.data.order_id,
                    action:           currentMode,
                    payment_currency: selectedActionCurrency.symbol,
                    network:          selectedActionCurrency.network,
                })
            });
            const d2 = await r2.json();
            if (!d2.success) throw new Error(d2.message || 'Failed to create invoice');

            document.getElementById('cryptoSelectModal').classList.remove('active');
            document.getElementById('upgradeModal').classList.remove('active');
            window.location.href = '../vps_invoice?id=' + d2.data.local_id;
        } else {
            const res  = await fetch('/api/orders/process', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...payload, payment_method: 'balance' })
            });
            const data = await res.json();

            if (!data.success) throw new Error(data.message || 'Error processing order.');

            document.getElementById('processResult').style.display = 'block';
            document.getElementById('processResult').innerHTML = `<i class="fas fa-check-circle"></i> ${LANG_MAN.js_action_exec}`;
            setTimeout(() => { location.reload(); }, 2000);
        }
    } catch (e) {
        console.error(e);
        errDiv.textContent = e.message || 'Connection error. Try again.';
        errDiv.dataset.manualError = 'true';
        errDiv.style.display = 'block';
        activeBtn.innerHTML = originalText;
        activeBtn.disabled = false;
    }
}
