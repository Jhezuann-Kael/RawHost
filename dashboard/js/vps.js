/**
 * VPS Management Dashboard Logic
 */

let currentStep = 1;
let selectedDurationHours = 0;
let selectedPlanData = null;
let selectedOSData = null;
let plansData = [];
let selectedPayMethod = null;    // 'balance' | 'crypto'
let selectedCurrency = null;     // { symbol, network }
let invoiceTimerInterval = null;
let userBalance = 0;             // cached user balance
let vpsCurrenciesData = [];      // Shared currencies cache

// ========== Generadores Automáticos ==========
function generateHostname() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let randomPart = '';
    for (let i = 0; i < 7; i++) {
        randomPart += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const hostname = 'server-' + randomPart;
    const input = document.getElementById('hostname');
    if (input) input.value = hostname;
    return hostname;
}

function generatePassword() {
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const symbols = '!@#$%&*';
    const allChars = lowercase + uppercase + numbers + symbols;

    // Asegurar al menos uno de cada tipo
    let password = '';
    password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
    password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
    password += numbers.charAt(Math.floor(Math.random() * numbers.length));
    password += symbols.charAt(Math.floor(Math.random() * symbols.length));

    // Rellenar hasta 12 caracteres
    for (let i = password.length; i < 12; i++) {
        password += allChars.charAt(Math.floor(Math.random() * allChars.length));
    }

    // Mezclar los caracteres
    password = password.split('').sort(() => Math.random() - 0.5).join('');

    const input = document.getElementById('password');
    if (input) input.value = password;
    return password;
}

function autoGenerateCredentials() {
    // Solo generar si los campos están vacíos
    const hostInput = document.getElementById('hostname');
    const passInput = document.getElementById('password');
    if (hostInput && !hostInput.value) {
        generateHostname();
    }
    if (passInput && !passInput.value) {
        generatePassword();
    }
}

// Modal Control
function openModal() {
    document.getElementById('createServerModal').classList.add('active');
    loadPlans();
    loadUserBalance(); // pre-load balance for display in step1

    // Select 30 days by default
    const defaultDurationCard = document.querySelector('.duration-card[data-hours="720"]');
    if (defaultDurationCard) {
        selectDuration(720, defaultDurationCard);
    }
}

function closeModal() {
    document.getElementById('createServerModal').classList.remove('active');
    resetModal();
}

function resetModal() {
    currentStep = 1;
    showStep(1);
    selectedDurationHours = 0;
    selectedPlanData = null;
    selectedOSData = null;
    selectedPayMethod = null;
    selectedCurrency = null;
    if (invoiceTimerInterval) { clearInterval(invoiceTimerInterval); invoiceTimerInterval = null; }
    
    // Hide errors
    ['createError', 'step2Error', 'step4Error', 'plan-filter-notice'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // Reset UI selections
    document.querySelectorAll('.duration-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.os-card').forEach(c => c.classList.remove('selected'));
    
    // Reset pay method UI
    ['pay-option-balance', 'pay-option-crypto'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.style.borderColor = 'rgba(255,255,255,0.1)'; el.style.background = 'rgba(255,255,255,0.03)'; }
    });
}

function showStep(step) {
    const steps = ['step1', 'step2', 'step3', 'step4'];
    steps.forEach((id, index) => {
        const el = document.getElementById(id);
        if (el) el.style.display = (index + 1 === step) ? 'block' : 'none';
    });

    const isCrypto = selectedPayMethod === 'crypto';

    // Back button: visible on steps 2, 3, 4
    const btnBack = document.getElementById('btnBack');
    if (btnBack) btnBack.style.display = (step > 1 && step < 5) ? 'inline-block' : 'none';

    const btnNext = document.getElementById('btnNext');
    const btnCreate = document.getElementById('btnCreate');

    if (step === 1 || step === 2) {
        // Steps 1 & 2: only Next
        if (btnNext) btnNext.style.display = 'inline-block';
        if (btnCreate) btnCreate.style.display = 'none';
    } else if (step === 4) {
        // Step 4 (currency selector): Next goes to step 3 (summary review)
        if (btnNext) btnNext.style.display = 'inline-block';
        if (btnCreate) btnCreate.style.display = 'none';
    } else if (step === 3) {
        // Step 3 (summary): always show Create — for balance it creates server, for crypto it generates invoice
        if (btnNext) btnNext.style.display = 'none';
        if (btnCreate) {
            btnCreate.style.display = 'inline-block';
            btnCreate.textContent = isCrypto ? (LANG_VPS.btn_invoice || 'Generar Factura') : (LANG_VPS.btn_submit || 'Crear Servidor');
        }
        // Show/hide the crypto info section in summary
        const cryptoSec = document.getElementById('summary-crypto-section');
        if (cryptoSec) cryptoSec.style.display = isCrypto ? 'block' : 'none';
    }

    // Modal titles
    const modalTitle = document.getElementById('modalTitle');
    if (!modalTitle) return;

    if (step === 1) {
        modalTitle.textContent = LANG_VPS.modal_title;
    } else if (step === 2) {
        modalTitle.textContent = LANG_VPS.modal_title_config;
    } else if (step === 4) {
        modalTitle.textContent = LANG_VPS.modal_title_crypto;
        const err4 = document.getElementById('step4Error');
        if (err4) err4.style.display = 'none';
        loadVpsCurrencies();
    } else if (step === 3) {
        modalTitle.textContent = isCrypto ? LANG_VPS.modal_title_order : LANG_VPS.modal_title_confirm;
        updateSummary();
    }
}

function nextStep() {
    if (currentStep === 1) {
        if (!selectedPayMethod) {
            alert(LANG_VPS.err_pay_method);
            return;
        }
        if (!selectedDurationHours || !selectedPlanData) {
            alert(LANG_VPS.err_select);
            return;
        }
        loadOSOptions();
        autoGenerateCredentials();
        currentStep = 2;

    } else if (currentStep === 2) {
        const hostname = document.getElementById('hostname').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('step2Error');
        if (errorDiv) errorDiv.style.display = 'none';

        if (!selectedOSData || !hostname || !password) {
            if (errorDiv) { errorDiv.textContent = LANG_VPS.err_fields; errorDiv.style.display = 'block'; }
            return;
        }

        const hostnameRegex = /^[a-zA-Z0-9-]{7,}$/;
        if (!hostnameRegex.test(hostname)) {
            if (errorDiv) { errorDiv.textContent = LANG_VPS.err_host; errorDiv.style.display = 'block'; }
            return;
        }

        if (password.length < 6) {
            if (errorDiv) { errorDiv.textContent = LANG_VPS.err_pass; errorDiv.style.display = 'block'; }
            return;
        }

        currentStep = (selectedPayMethod === 'crypto') ? 4 : 3;

    } else if (currentStep === 4) {
        if (!selectedCurrency || !selectedCurrency.network) {
            const errDiv = document.getElementById('step4Error');
            if (errDiv) { errDiv.textContent = 'Por favor selecciona una criptomoneda y su red.'; errDiv.style.display = 'block'; }
            return;
        }
        currentStep = 3;
    }
    showStep(currentStep);
}

function previousStep() {
    if (currentStep === 2) {
        currentStep = 1;
    } else if (currentStep === 3) {
        currentStep = (selectedPayMethod === 'crypto') ? 4 : 2;
    } else if (currentStep === 4) {
        currentStep = 2;
    }
    if (currentStep >= 1) showStep(currentStep);
}

// Selection Logic
function selectDuration(hours, element) {
    document.querySelectorAll('.duration-card').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
    selectedDurationHours = hours;
    const input = document.getElementById('selectedDuration');
    if (input) input.value = hours;
    updatePrices();
}

function selectPlan(planId, element) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
    selectedPlanData = plansData.find(p => p.id == planId);
    const input = document.getElementById('selectedPlanId');
    if (input) input.value = planId;
}

function selectOS(osId, osName, element) {
    document.querySelectorAll('.os-card').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
    selectedOSData = { id: osId, name: osName };
    const input = document.getElementById('selectedOS');
    if (input) input.value = osName;
}

// Data Loading
async function loadPlans() {
    try {
        const res = await fetch('../api/plans/list');
        const data = await res.json();

        if (data.success) {
            plansData = data.data;
            const grid = document.getElementById('plansGrid');
            if (!grid) return;
            grid.innerHTML = '';

            data.data.forEach(plan => {
                const card = document.createElement('div');
                card.className = 'plan-card';
                card.dataset.planId = plan.id;
                card.onclick = () => selectPlan(plan.id, card);

                const ramGB = parseFloat(plan.ram).toFixed(0);

                card.innerHTML = `
                    <div class="plan-name">${plan.name}</div>
                    <div class="plan-specs">
                        <div><i class="fas fa-microchip"></i> ${plan.cpu} vCPU</div>
                        <div><i class="fas fa-memory"></i> ${ramGB} GB RAM</div>
                        <div><i class="fas fa-hdd"></i> ${plan.disk} GB</div>
                    </div>
                    <div class="plan-price" id="plan-price-${plan.id}">$0.00</div>
                `;
                grid.appendChild(card);
            });

            if (selectedDurationHours > 0) updatePrices();
        }
    } catch (e) {
        console.error('Error loading plans:', e);
    }
}

function updatePrices() {
    if (!selectedDurationHours || plansData.length === 0) return;

    plansData.forEach(plan => {
        const priceEl = document.getElementById(`plan-price-${plan.id}`);
        if (priceEl && plan.price) {
            const monthlyPrice = parseFloat(plan.price);
            const hourlyPrice = monthlyPrice / 720;
            const price = (hourlyPrice * selectedDurationHours).toFixed(2);
            priceEl.textContent = `$${price}`;
        }
    });

    applyPlanFilter();
}

function applyPlanFilter() {
    if (!selectedDurationHours || plansData.length === 0) return;

    let shouldFilter = false;
    if (selectedPayMethod === 'crypto') {
        shouldFilter = true;
    } else if (selectedPayMethod === 'balance' && userBalance < 1.00) {
        shouldFilter = true;
    }

    let anyHidden = false;
    plansData.forEach(plan => {
        const card = document.querySelector(`.plan-card[data-plan-id="${plan.id}"]`);
        if (!card) return;
        const monthlyPrice = parseFloat(plan.price);
        const hourlyPrice = monthlyPrice / 720;
        const price = hourlyPrice * selectedDurationHours;

        if (shouldFilter && price < 1.00) {
            card.style.display = 'none';
            anyHidden = true;
            if (selectedPlanData && selectedPlanData.id == plan.id) {
                selectedPlanData = null;
                card.classList.remove('selected');
            }
        } else {
            card.style.display = '';
        }
    });

    const notice = document.getElementById('plan-filter-notice');
    if (notice) notice.style.display = anyHidden ? 'block' : 'none';
}

async function loadOSOptions() {
    if (!selectedPlanData) return;

    const grid = document.getElementById('osGrid');
    if (!grid) return;
    grid.innerHTML = '';

    if (selectedPlanData.available_os_image_versions) {
        const sortedOS = [...selectedPlanData.available_os_image_versions].sort((a, b) => {
            const aName = a.name.toLowerCase();
            const bName = b.name.toLowerCase();

            const order = ['windows', 'ubuntu', 'debian', 'centos', 'fedora'];
            for (const key of order) {
                if (aName.includes(key) && !bName.includes(key)) return -1;
                if (!aName.includes(key) && bName.includes(key)) return 1;
                if (aName.includes(key) && bName.includes(key)) return 0;
            }
            return 0;
        });

        sortedOS.forEach(os => {
            const card = document.createElement('div');
            card.className = 'os-card';
            card.onclick = () => selectOS(os.id, os.name, card);

            let icon = '<i class="fab fa-linux"></i>';
            let osClass = 'os-linux';
            const lowerName = os.name.toLowerCase();

            const osMap = [
                { key: 'windows', icon: 'fab fa-windows', class: 'os-windows' },
                { key: 'ubuntu', icon: 'fab fa-ubuntu', class: 'os-ubuntu' },
                { key: 'centos', icon: 'fab fa-centos', class: 'os-centos' },
                { key: 'debian', icon: 'fab fa-linux', class: 'os-debian' },
                { key: 'fedora', icon: 'fab fa-fedora', class: 'os-fedora' },
                { key: 'suse', icon: 'fab fa-suse', class: 'os-suse' },
                { key: 'redhat', icon: 'fab fa-redhat', class: 'os-redhat' },
                { key: 'rocky', icon: 'fab fa-redhat', class: 'os-redhat' },
                { key: 'alma', icon: 'fab fa-redhat', class: 'os-redhat' }
            ];

            const found = osMap.find(m => lowerName.includes(m.key));
            if (found) { icon = `<i class="${found.icon}"></i>`; osClass = found.class; }

            card.classList.add(osClass);
            card.innerHTML = `
                <div class="os-icon">${icon}</div>
                <div class="os-name">${os.name}</div>
            `;
            grid.appendChild(card);
        });
    }
}

function updateSummary() {
    const fields = {
        'summary-plan': selectedPlanData ? selectedPlanData.name : '-',
        'summary-duration': selectedDurationHours ? `${selectedDurationHours} ${LANG_VPS.label_hours}` : '-',
        'summary-hostname': document.getElementById('hostname').value || '-'
    };

    for (const [id, val] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    const rawOsName = selectedOSData ? selectedOSData.name : '-';
    const osDisplay = rawOsName.length > 0 ? rawOsName.charAt(0).toUpperCase() + rawOsName.slice(1) : rawOsName;
    const osEl = document.getElementById('summary-os');
    if (osEl) osEl.textContent = osDisplay;

    const totalEl = document.getElementById('summary-total');
    if (totalEl && selectedPlanData && selectedPlanData.price && selectedDurationHours) {
        const hourlyPrice = parseFloat(selectedPlanData.price) / 720;
        const total = (hourlyPrice * selectedDurationHours).toFixed(2);
        totalEl.textContent = `$${total}`;
    }

    if (selectedPayMethod === 'crypto' && selectedCurrency) {
        const coinEl = document.getElementById('summary-coin');
        const netEl = document.getElementById('summary-network');
        if (coinEl) coinEl.textContent = selectedCurrency.symbol || '-';
        if (netEl) netEl.textContent = selectedCurrency.network || '-';
    }
}

// Payment Method Logic
function selectPayMethod(method) {
    selectedPayMethod = method;
    ['pay-option-balance', 'pay-option-crypto'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.style.borderColor = 'rgba(255,255,255,0.1)'; el.style.background = 'rgba(255,255,255,0.03)'; }
    });
    const activeEl = document.getElementById(method === 'balance' ? 'pay-option-balance' : 'pay-option-crypto');
    if (activeEl) {
        activeEl.style.borderColor = 'var(--primary)';
        activeEl.style.background = 'rgba(0,243,255,0.07)';
    }
    applyPlanFilter();
    if (currentStep === 3) showStep(3);
}

async function loadUserBalance() {
    try {
        const res = await fetch('../api/users/balance');
        const data = await res.json();
        const el = document.getElementById('user-balance-display');
        if (data.success) {
            userBalance = parseFloat(data.balance);
            if (el) el.textContent = `$${userBalance.toFixed(2)} USD`;
        } else if (el) el.textContent = 'N/D';
        applyPlanFilter();
    } catch (e) {
        const el = document.getElementById('user-balance-display');
        if (el) el.textContent = 'N/D';
    }
}

async function loadVpsCurrencies() {
    const coinGrid = document.getElementById('vps-coinGrid');
    const moreGrid = document.getElementById('vps-moreCoinsGrid');
    if (!coinGrid || vpsCurrenciesData.length > 0) return;

    coinGrid.innerHTML = '<div style="color:var(--text-muted);font-size:0.82rem;grid-column:1/-1;"><i class="fas fa-spinner fa-spin"></i> Cargando monedas...</div>';

    try {
        const res = await fetch('../api/transactions/currencies');
        const rawData = await res.json();
        let list = rawData.data || rawData;
        if (list && !Array.isArray(list)) list = Object.values(list);

        if (!Array.isArray(list) || list.length === 0) {
            coinGrid.innerHTML = '<div style="color:#ef4444;font-size:0.82rem;grid-column:1/-1;">No hay monedas disponibles.</div>';
            return;
        }

        vpsCurrenciesData = list;
        coinGrid.innerHTML = '';
        if (moreGrid) moreGrid.innerHTML = '';

        const topCoins = ['USDT', 'BTC', 'ETH', 'TRX', 'LTC', 'BNB'];
        const logos = {
            'USDT': 'USDT_Logo.png', 'BTC': 'BTC.png', 'ETH': 'ETH.png',
            'TRX': 'TRX.png', 'LTC': 'LTC.png', 'BNB': 'BNB.png',
            'USDC': 'USDC.png', 'DOGE': 'DODGE.png', 'POL': 'POL.png',
            'SOL': 'SOL.png', 'SHIB': 'SHIB.png', 'TON': 'TON.png',
            'XMR': 'XMR.png', 'DAI': 'DAI.png', 'BCH': 'BCH.png',
            'NOT': 'NOT.png', 'DOGS': 'DOGS.png', 'XRP': 'XRP.png'
        };

        const createBtn = (coin) => {
            const sym = coin.symbol || coin.currency;
            const btn = document.createElement('div');
            btn.className = `vps-coin-btn crypto-${sym.toLowerCase()}`;
            btn.innerHTML = `
                <img src="/assets/img/crypto/${logos[sym] || 'BTC.png'}" alt="${sym}" style="width: 24px; height: 24px; object-fit: contain;">
                <span style="font-weight: 700; font-size: 0.8rem; color: var(--text-light); pointer-events: none;">${sym}</span>
            `;
            btn.onclick = () => selectVpsCoin(sym, btn);
            return btn;
        };

        topCoins.forEach(sym => {
            const coin = list.find(c => (c.symbol === sym || c.currency === sym));
            if (coin) coinGrid.appendChild(createBtn(coin));
        });

        list.filter(c => !topCoins.includes(c.symbol || c.currency)).forEach(coin => {
            if (moreGrid) moreGrid.appendChild(createBtn(coin));
        });

    } catch (e) {
        console.error('Error fetching currencies:', e);
        coinGrid.innerHTML = '<div style="color:#ef4444;font-size:0.82rem;grid-column:1/-1;">Error de conexión.</div>';
    }
}

function selectVpsCoin(symbol, btnElement) {
    document.querySelectorAll('.vps-coin-btn').forEach(b => {
        b.classList.remove('selected');
    });
    if (btnElement) btnElement.classList.add('selected');
    selectedCurrency = { symbol, network: null };
    renderVpsNetworks(symbol);
}

function renderVpsNetworks(symbol) {
    const netGroup = document.getElementById('vps-networkGroup');
    const netGrid = document.getElementById('vps-networkGrid');
    const coin = vpsCurrenciesData.find(c => (c.symbol === symbol || c.currency === symbol));
    if (!netGroup || !netGrid) return;
    netGrid.innerHTML = '';

    if (coin && coin.networks) {
        netGroup.style.display = 'block';
        const networks = Object.values(coin.networks);
        networks.forEach(net => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'vps-net-btn';

            // Extract acronym if available in keys
            let acronym = '';
            if (net.keys && Array.isArray(net.keys)) {
                // Find potential acronym (usually short uppercase string)
                const found = net.keys.find(k => k.length >= 3 && k.length <= 6 && k === k.toUpperCase());
                if (found) acronym = found;
            }

            // Fallback: if acronym not found but network name contains one in parens or is short
            if (!acronym && (net.name || net.network).includes('(')) {
                const match = (net.name || net.network).match(/\(([^)]+)\)/);
                if (match) acronym = match[1];
            }

            const cleanName = (net.name || net.network).replace(/\s*\(.*\)\s*/, '').trim();

            btn.innerHTML = `
                <span>${cleanName}</span>
                ${acronym ? `<span class="net-acronym">${acronym}</span>` : ''}
            `;

            btn.onclick = () => {
                document.querySelectorAll('.vps-net-btn').forEach(b => b.classList.remove('selected-net'));
                btn.classList.add('selected-net');
                selectedCurrency = { symbol, network: net.network };
            };
            netGrid.appendChild(btn);
        });
        if (networks.length === 1) netGrid.firstChild.click();
    } else {
        netGroup.style.display = 'none';
        if (coin) selectedCurrency = { symbol, network: symbol };
    }
}

// Server Lifecycle
async function createServer() {
    const hostname = document.getElementById('hostname').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('createError');
    if (errorDiv) errorDiv.style.display = 'none';

    const btn = document.getElementById('btnCreate');
    if (!btn) return;
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_VPS.creating}`;

    const baseBody = {
        plan_id:     selectedPlanData.id,
        duration:    selectedDurationHours,
        os_image_id: selectedOSData.id,
        name_server: hostname,
        password:    password,
    };

    try {
        if (selectedPayMethod === 'balance') {
            const res  = await fetch('../api/orders/create', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ ...baseBody, payment_method: 'balance' }),
            });
            const data = await res.json();
            if (data.success) {
                closeModal();
                loadServers();
                loadVpsOrders();
                alert(LANG_VPS.success);
            } else if (errorDiv) {
                errorDiv.textContent = data.message || LANG_VPS.err_create;
                errorDiv.style.display = 'block';
            }
        } else {
            // Step 1: create PENDING order
            const r1   = await fetch('../api/orders/create', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ ...baseBody, payment_method: 'crypto' }),
            });
            const d1 = await r1.json();
            if (!d1.success) {
                if (errorDiv) { errorDiv.textContent = d1.message || LANG_VPS.err_create; errorDiv.style.display = 'block'; }
                return;
            }

            // Step 2: create invoice linked to order
            const r2   = await fetch('../api/orders/create_invoice', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    order_id:         d1.data.order_id,
                    name_server:      hostname,
                    password:         password,
                    payment_currency: selectedCurrency.symbol,
                    network:          selectedCurrency.network,
                }),
            });
            const d2 = await r2.json();
            if (d2.success) {
                window.location.href = 'vps_invoice?id=' + d2.data.local_id;
            } else if (errorDiv) {
                errorDiv.textContent = d2.message || LANG_VPS.err_create;
                errorDiv.style.display = 'block';
            }
        }
    } catch (e) {
        if (errorDiv) { errorDiv.textContent = LANG_VPS.err_conn; errorDiv.style.display = 'block'; }
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// VPS Orders
let allVpsOrders = [];
let vpsOrdersPage = 1;
const VPS_ORDERS_PER_PAGE = 10;

async function loadVpsOrders() {
    const wrap = document.getElementById('vpsOrdersWrap');
    if (!wrap) return;
    try {
        const res  = await fetch('../api/orders/list');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Error');
        allVpsOrders = (data.data || []).filter(o => o.type === 'vps');
        vpsOrdersPage = 1;
        renderVpsOrders();
    } catch (e) {
        wrap.innerHTML = `<p style="color:#ef4444; padding:20px;">${e.message}</p>`;
    }
}

function renderVpsOrders() {
    const wrap = document.getElementById('vpsOrdersWrap');
    if (!wrap) return;

    if (!allVpsOrders.length) {
        wrap.innerHTML = `
            <div style="text-align:center; padding:40px; color:var(--text-muted); background:rgba(255,255,255,0.02); border-radius:12px; border:1px dashed rgba(255,255,255,0.1);">
                <i class="fas fa-receipt" style="font-size:2.5rem; opacity:0.3; margin-bottom:15px; display:block;"></i>
                <p>${LANG_VPS.no_orders}</p>
            </div>`;
        return;
    }

    const totalPages = Math.ceil(allVpsOrders.length / VPS_ORDERS_PER_PAGE);
    const start = (vpsOrdersPage - 1) * VPS_ORDERS_PER_PAGE;
    const pageOrders = allVpsOrders.slice(start, start + VPS_ORDERS_PER_PAGE);

    const statusColor = { COMPLETED: '#10b981', PENDING: '#f59e0b', FAILED: '#ef4444', CANCELLED: '#6b7280' };

    const rows = pageOrders.map(o => {
        const st    = (o.status || '').toUpperCase();
        const color = statusColor[st] || '#9ca3af';
        const plan  = o.plan_name || '—';
        const dur   = o.duration ? `${o.duration}${LANG_VPS.hours}` : '—';
        return `
        <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
            <td style="padding:10px 14px; color:var(--text-muted); font-size:0.85rem;">#${o.id}</td>
            <td style="padding:10px 14px; font-weight:600;">${plan}</td>
            <td style="padding:10px 14px; color:var(--text-muted);">${dur}</td>
            <td style="padding:10px 14px; color:var(--primary); font-weight:600;">${o.formatted_amount}</td>
            <td style="padding:10px 14px;">
                <span style="color:${color}; font-weight:600; font-size:0.85rem;">${o.status_label || o.status}</span>
            </td>
            <td style="padding:10px 14px; color:var(--text-muted); font-size:0.85rem;">${o.formatted_date}</td>
        </tr>`;
    }).join('');

    const pagination = totalPages > 1 ? `
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; padding:12px 14px; border-top:1px solid rgba(255,255,255,0.05);">
            <button onclick="setVpsOrdersPage(${vpsOrdersPage - 1})" ${vpsOrdersPage <= 1 ? 'disabled' : ''}
                style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:var(--text-light); border-radius:6px; padding:5px 12px; cursor:pointer; font-size:0.85rem;">
                &lsaquo;
            </button>
            <span style="font-size:0.85rem; color:var(--text-muted);">${vpsOrdersPage} / ${totalPages}</span>
            <button onclick="setVpsOrdersPage(${vpsOrdersPage + 1})" ${vpsOrdersPage >= totalPages ? 'disabled' : ''}
                style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:var(--text-light); border-radius:6px; padding:5px 12px; cursor:pointer; font-size:0.85rem;">
                &rsaquo;
            </button>
        </div>` : '';

    wrap.innerHTML = `
        <div style="overflow-x:auto; background:rgba(255,255,255,0.02); border-radius:12px; border:1px solid rgba(255,255,255,0.07);">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                <thead>
                    <tr style="color:var(--text-muted); border-bottom:1px solid rgba(255,255,255,0.07); text-align:left; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">
                        <th style="padding:12px 14px;">${LANG_VPS.tbl_id}</th>
                        <th style="padding:12px 14px;">${LANG_VPS.tbl_plan}</th>
                        <th style="padding:12px 14px;">${LANG_VPS.tbl_dur}</th>
                        <th style="padding:12px 14px;">${LANG_VPS.tbl_amount}</th>
                        <th style="padding:12px 14px;">${LANG_VPS.tbl_status}</th>
                        <th style="padding:12px 14px;">${LANG_VPS.tbl_date}</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            ${pagination}
        </div>`;
}

function setVpsOrdersPage(page) {
    const totalPages = Math.ceil(allVpsOrders.length / VPS_ORDERS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    vpsOrdersPage = page;
    renderVpsOrders();
}

async function loadServers() {
    const grid              = document.getElementById('serversGrid');
    const emptyHero         = document.getElementById('vpsEmptyHero');
    const activeSection     = document.getElementById('activeServersSection');
    const ordersSection     = document.getElementById('ordersSectionWrap');
    if (!grid) return;

    // Show spinner inside the active section while loading
    grid.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i></div>';

    try {
        const res  = await fetch('../api/servers/list');
        const data = await res.json();

        if (data.success && data.data.length > 0) {
            // ── Has servers ──────────────────────────────────────────
            if (emptyHero)     emptyHero.style.display     = 'none';
            if (activeSection) activeSection.style.display = 'block';
            if (ordersSection) ordersSection.style.display = 'block';

            grid.innerHTML = '';

            const STATUS_COLOR = {
                'ACTIVE':       { dot: '#10b981', text: LANG_VPS.status_active       || 'Active' },
                'PROVISIONING': { dot: '#3b82f6', text: LANG_VPS.status_provisioning || 'Provisioning' },
                'INACTIVE':     { dot: '#f59e0b', text: LANG_VPS.status_inactive     || 'Inactive' },
                'SUSPENDED':    { dot: '#ef4444', text: LANG_VPS.status_suspended    || 'Suspended' },
            };

            data.data.forEach(server => {
                const card = document.createElement('div');
                card.className = 'server-card';

                const status = (server.status || 'UNKNOWN').toUpperCase();
                card.dataset.status = status;
                const sc = STATUS_COLOR[status] || { dot: '#6b7280', text: status };

                const timeLeft   = formatTimeLeft(server.expires_at);
                const isExpiring = server.expires_at && (new Date(server.expires_at) - Date.now()) < 3 * 86400 * 1000;
                const isExpired  = server.expires_at && (new Date(server.expires_at) - Date.now()) <= 0;
                const planLabel  = server.plan_name || '—';
                const specs      = server.specs || {};
                const cpu        = specs.cpu  && specs.cpu  !== 'N/A' ? specs.cpu  : null;
                const ram        = specs.ram  && specs.ram  !== 'N/A' ? specs.ram  : null;
                const disk       = specs.disk && specs.disk !== 'N/A' ? specs.disk : null;

                // Expiry progress bar (% of time consumed)
                let expiryPct = 0;
                if (server.created_at && server.expires_at) {
                    const total   = new Date(server.expires_at) - new Date(server.created_at);
                    const elapsed = Date.now() - new Date(server.created_at);
                    expiryPct = Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));
                }
                const barColor   = isExpired ? '#ef4444' : isExpiring ? '#f59e0b' : '#10b981';
                const timeColor  = isExpired ? '#ef4444' : isExpiring ? '#f59e0b' : 'var(--text-muted)';

                const specPills = [
                    cpu  ? `<span class="sc-spec-pill"><i class="fas fa-microchip"></i>${cpu} vCPU</span>` : '',
                    ram  ? `<span class="sc-spec-pill"><i class="fas fa-memory"></i>${ram}</span>` : '',
                    disk ? `<span class="sc-spec-pill"><i class="fas fa-hdd"></i>${disk} GB</span>` : '',
                ].filter(Boolean).join('');

                card.innerHTML = `
                    <div class="sc-head">
                        <div class="sc-name-row">
                            <span class="sc-dot" style="background:${sc.dot};"></span>
                            <span class="sc-name">${server.name || 'VPS #' + server.id}</span>
                        </div>
                        <span class="sc-status-pill sc-status-${status.toLowerCase()} sc-pill-head">${sc.text}</span>
                    </div>

                    <div class="sc-net">
                        <div class="sc-ip">${server.ip || '—'}</div>
                        <div class="sc-os">${server.os || 'N/A'}</div>
                    </div>

                    <div class="sc-specs-wrap">
                        ${specPills ? `<div class="sc-specs">${specPills}</div>` : `<div class="sc-plan-label">${planLabel}</div>`}
                    </div>

                    <div class="sc-footer">
                        <div class="sc-expiry">
                            <div class="sc-expiry-bar"><div class="sc-expiry-fill" style="width:${expiryPct}%; background:${barColor};"></div></div>
                            <span class="sc-expiry-text" style="color:${timeColor};">${timeLeft}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <a class="sc-manage-btn" href="manage/?id=${server.id}">
                                ${LANG_VPS.btn_manage} <i class="fas fa-arrow-right"></i>
                            </a>
                            <button class="sc-more-btn" id="sc-btn-${server.id}" onclick="toggleServerDropdown(${server.id}, event)" title="Acciones rápidas">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>`;
                grid.appendChild(card);
            });

            buildStatusChipBar(data.data);
        } else {
            // ── No servers ───────────────────────────────────────────
            if (activeSection) activeSection.style.display = 'none';
            if (ordersSection) ordersSection.style.display = 'none';
            if (emptyHero)     emptyHero.style.display     = 'block';
        }
    } catch (e) {
        console.error('Error loading servers:', e);
        if (activeSection) activeSection.style.display = 'block';
        if (emptyHero)     emptyHero.style.display     = 'none';
        grid.innerHTML = `<div class="error-msg">${LANG_VPS.err_load}</div>`;
    }
}

// ── Time helpers ─────────────────────────────────────────────
function formatRelativeTime(dateStr) {
    if (!dateStr) return '—';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)   return diff + 's';
    if (diff < 3600) return Math.floor(diff / 60) + 'm';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    const d = Math.floor(diff / 86400);
    const h = Math.floor((diff % 86400) / 3600);
    return h > 0 ? `${d}d ${h}h` : `${d}d`;
}

function formatTimeLeft(dateStr) {
    if (!dateStr) return '—';
    const diff = Math.floor((new Date(dateStr).getTime() - Date.now()) / 1000);
    if (diff <= 0) return 'Expired';
    if (diff < 3600) return Math.floor(diff / 60) + 'm';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    const d = Math.floor(diff / 86400);
    const h = Math.floor((diff % 86400) / 3600);
    return h > 0 ? `${d}d ${h}h` : `${d}d`;
}

// ── Status chip bar ──────────────────────────────────────────
const STATUS_CHIP_DEFS = [
    { status: 'ACTIVE',       label: LANG_VPS.status_active       || 'Active',       cls: 'chip-active' },
    { status: 'PROVISIONING', label: LANG_VPS.status_provisioning || 'Provisioning', cls: 'chip-provisioning' },
    { status: 'INACTIVE',     label: LANG_VPS.status_inactive     || 'Inactive',     cls: 'chip-inactive' },
    { status: 'SUSPENDED',    label: LANG_VPS.status_suspended    || 'Suspended',    cls: 'chip-suspended' },
];

let activeStatusFilter = 'ALL';

function buildStatusChipBar(servers) {
    const bar = document.getElementById('statusChipBar');
    if (!bar) return;

    const counts = {};
    servers.forEach(s => { const st = (s.status || 'UNKNOWN').toUpperCase(); counts[st] = (counts[st] || 0) + 1; });

    bar.innerHTML = '';

    const allChip = document.createElement('button');
    allChip.type = 'button';
    allChip.className = 'status-chip chip-all' + (activeStatusFilter === 'ALL' ? ' active' : '');
    allChip.dataset.status = 'ALL';
    allChip.innerHTML = `<span class="chip-count">${servers.length}</span> All`;
    allChip.onclick = () => filterServersByStatus('ALL', allChip);
    bar.appendChild(allChip);

    STATUS_CHIP_DEFS.forEach(def => {
        const count = counts[def.status] || 0;
        if (!count) return;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = `status-chip ${def.cls}` + (activeStatusFilter === def.status ? ' active' : '');
        chip.dataset.status = def.status;
        chip.innerHTML = `<span class="chip-count">${count}</span> ${def.label}`;
        chip.onclick = () => filterServersByStatus(def.status, chip);
        bar.appendChild(chip);
    });
}

function filterServersByStatus(status, chipEl) {
    activeStatusFilter = status;
    document.querySelectorAll('.status-chip').forEach(c => c.classList.remove('active'));
    if (chipEl) chipEl.classList.add('active');
    document.querySelectorAll('#serversGrid .server-card').forEach(card => {
        card.style.display = (status === 'ALL' || card.dataset.status === status) ? '' : 'none';
    });
}

// View Management
function setView(mode) {
    const grid = document.getElementById('serversGrid');
    const header = document.getElementById('serversListHeader');
    const btnGrid = document.getElementById('btnViewGrid');
    const btnList = document.getElementById('btnViewList');
    if (!grid) return;
    
    if (mode === 'list') {
        grid.classList.add('servers-list');
        if (header) header.style.display = 'grid';
        if (btnGrid) btnGrid.classList.remove('active');
        if (btnList) btnList.classList.add('active');
    } else {
        grid.classList.remove('servers-list');
        if (header) header.style.display = 'none';
        if (btnGrid) btnGrid.classList.add('active');
        if (btnList) btnList.classList.remove('active');
    }
    localStorage.setItem('vps_view_mode', mode);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    loadServers();
    loadVpsOrders();
    const savedView = localStorage.getItem('vps_view_mode') || 'grid';
    setView(savedView);

    // View Toggle Listeners
    const btnViewGrid = document.getElementById('btnViewGrid');
    const btnViewList = document.getElementById('btnViewList');
    if (btnViewGrid) btnViewGrid.onclick = () => setView('grid');
    if (btnViewList) btnViewList.onclick = () => setView('list');
    
    const btnToggleMore = document.getElementById('vps-btnToggleMore');
    if (btnToggleMore) {
        btnToggleMore.onclick = function() {
            const container = document.getElementById('vps-moreCoinsContainer');
            const isHidden = container.style.display === 'none';
            container.style.display = isHidden ? 'block' : 'none';
            this.innerHTML = isHidden ? '<i class="fas fa-chevron-up"></i> Ver menos' : '<i class="fas fa-chevron-down"></i> Ver más monedas';
        };
    }

    const btnOpenModal = document.getElementById('btnOpenModal');
    if (btnOpenModal) btnOpenModal.addEventListener('click', openModal);

    document.addEventListener('click', e => {
        if (_openDropdownMenu && !_openDropdownMenu.contains(e.target)) {
            closeAllServerDropdowns();
        }
    });
});

// ── Server card quick-action dropdown (fixed positioning to avoid clip) ──
let _openDropdownMenu = null;

const SC_ACTIONS = [
    { action: 'start',   label: () => LANG_VPS.action_start,   icon: 'fa-play',      color: '#10b981' },
    { action: 'restart', label: () => LANG_VPS.action_restart, icon: 'fa-sync-alt',  color: '#3b82f6' },
    { action: 'stop',    label: () => LANG_VPS.action_stop,    icon: 'fa-power-off', color: '#ef4444' },
    { action: 'vnc',     label: () => LANG_VPS.action_console, icon: 'fa-desktop',   color: '#a78bfa' },
];

function _buildDropdownMenu(id) {
    const existing = document.getElementById(`sc-dd-${id}`);
    if (existing) return existing;

    const menu = document.createElement('div');
    menu.id = `sc-dd-${id}`;
    menu.className = 'sc-dropdown-menu';
    menu.innerHTML = SC_ACTIONS.map(a => `
        <button class="sc-dropdown-item" onclick="closeAllServerDropdowns(); ${a.action === 'vnc' ? `openVpsVnc(${id})` : `quickAction(${id}, '${a.action}')`}">
            <i class="fas ${a.icon}" style="color:${a.color};"></i> ${a.label()}
        </button>`).join('');
    document.body.appendChild(menu);
    return menu;
}

function toggleServerDropdown(id, e) {
    e.stopPropagation();
    const btn  = document.getElementById(`sc-btn-${id}`);
    const menu = _buildDropdownMenu(id);
    const isOpen = menu.classList.contains('open');

    closeAllServerDropdowns();
    if (isOpen) return;

    const rect = btn.getBoundingClientRect();
    menu.style.top   = (rect.bottom + window.scrollY + 6) + 'px';
    menu.style.right = (window.innerWidth - rect.right) + 'px';
    menu.classList.add('open');
    _openDropdownMenu = menu;
}

function closeAllServerDropdowns() {
    if (_openDropdownMenu) {
        _openDropdownMenu.classList.remove('open');
        _openDropdownMenu = null;
    }
}

// ── VNC popup window ─────────────────────────────────────────
function openVpsVnc(id) {
    const w = 1200, h = 750;
    const left = Math.round((screen.width  - w) / 2);
    const top  = Math.round((screen.height - h) / 2);
    window.open(
        `/dashboard/vnc_popup?id=${id}`,
        `vnc_${id}`,
        `width=${w},height=${h},left=${left},top=${top},resizable=yes,scrollbars=no,toolbar=no,menubar=no,location=no`
    );
}

// ── Quick power actions ───────────────────────────────────────
async function quickAction(id, action) {
    const btn = document.getElementById(`sc-btn-${id}`);
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const r = await fetch('/api/servers/action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vps_id: id, action })
        });
        const data = await r.json();
        if (data.success) {
            if (data.new_status) {
                const card = btn.closest('.server-card');
                if (card) {
                    const STATUS_COLOR = {
                        'ACTIVE':       { dot: '#10b981' },
                        'PROVISIONING': { dot: '#3b82f6' },
                        'INACTIVE':     { dot: '#f59e0b' },
                        'SUSPENDED':    { dot: '#ef4444' },
                    };
                    const dotEl = card.querySelector('.sc-dot');
                    if (dotEl) dotEl.style.background = (STATUS_COLOR[data.new_status] || {}).dot || '#6b7280';
                    card.dataset.status = data.new_status;
                }
            }
        } else {
            alert(data.message || 'Error al ejecutar acción');
        }
    } catch (err) {
        alert(LANG_VPS.action_err_conn);
    } finally {
        btn.innerHTML = origHtml;
        btn.disabled = false;
    }
}
