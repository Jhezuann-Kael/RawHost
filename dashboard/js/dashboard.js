// Variables globales para check de nuevo usuario
let userBalance = 0;
let vpsCount = 0;
let domainsCount = 0;
let pendingTransactions = 0;
let welcomeUsername = '';

// Cache de respuestas API: evita llamadas duplicadas entre fases
const _apiCache = {};
function apiFetch(url) {
    if (!_apiCache[url]) _apiCache[url] = fetch(url).then(r => r.json());
    return _apiCache[url];
}

// Initialize Dashboard - Load minimal data first to decide what to show
async function initializeDashboard() {
    try {
        if (window.INIT_DATA) {
            // PHP ya proveyó los contadores — sin llamadas API de conteo
            userBalance         = window.INIT_DATA.balance;
            welcomeUsername     = window.INIT_DATA.username;
            vpsCount            = window.INIT_DATA.vpsCount;
            domainsCount        = window.INIT_DATA.domainCount;
            pendingTransactions = window.INIT_DATA.pendingCount;
        } else {
            await Promise.all([
                loadBalanceOnly(),
                loadVPSCountOnly(),
                loadDomainsCountOnly(),
                loadPendingTransactionsCount()
            ]);
        }

        // Ocultar loading inicial
        document.getElementById('initialLoading').style.display = 'none';

        const isNewUser = window.INIT_DATA
            ? window.INIT_DATA.isNewUser
            : userBalance <= 0 && vpsCount === 0 && domainsCount === 0 && pendingTransactions === 0;

        // Mostrar noticias para TODOS
        document.getElementById('globalNewsSection').style.display = 'block';
        void loadRecentNews();

        if (isNewUser) {
            // Mostrar pantalla de bienvenida
            const greetEl = document.getElementById('welcomeGreeting');
            if (greetEl && welcomeUsername) greetEl.textContent = 'Hola, ' + welcomeUsername + ' 👋';
            document.getElementById('welcomeScreen').style.display = 'block';
        } else {
            // Mostrar dashboard y cargar el resto de datos
            document.getElementById('dashboardContent').style.display = 'block';
            void loadFullDashboard();
        }
    } catch (error) {
        console.error('Error initializing dashboard:', error);
        const loading = document.getElementById('initialLoading');
        if (loading) {
            loading.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 20px;"></i>
                <p style="font-size: 1.1rem; color: #ef4444;">${LANG.dash_err_loading}</p>
            `;
        }
    }
}

// Load only balance (for decision)
async function loadBalanceOnly() {
    try {
        const data = await apiFetch('../api/users/me');
        if (data.success && data.data) {
            userBalance = parseFloat(data.data.balance) || 0;
            welcomeUsername = data.data.username || '';
        }
    } catch (e) {
        console.error('Error loading balance:', e);
    }
}

// Load only VPS count (for decision)
async function loadVPSCountOnly() {
    try {
        const data = await apiFetch('../api/servers/list');
        if (data.success && data.data) {
            vpsCount = data.data.length;
        }
    } catch (e) {
        console.error('Error loading VPS count:', e);
    }
}

// Load only domains count (for decision)
async function loadDomainsCountOnly() {
    try {
        const data = await apiFetch('../api/domains/list');
        if (data.success && data.data) {
            domainsCount = data.data.length;
        }
    } catch (e) {
        console.error('Error loading domains count:', e);
    }
}

// Load pending transactions count (for new-user decision)
async function loadPendingTransactionsCount() {
    try {
        const data = await apiFetch('../api/orders/list');
        if (data.success && data.data) {
            // Count any order that is not completed or failed — e.g. PENDING, CONFIRMING, etc.
            pendingTransactions = data.data.filter(o => {
                const s = (o.status || '').toUpperCase();
                return s !== 'COMPLETED' && s !== 'FAILED' && s !== 'REJECTED';
            }).length;
        }
    } catch (e) {
        console.error('Error loading pending transactions:', e);
    }
}

// Load full dashboard (only called if user has resources)
async function loadFullDashboard() {
    await Promise.all([
        loadBalance(),
        loadVPSList(),
        loadRecentTransactions(),
        loadDomains()
    ]);
}

// Load User Balance
async function loadBalance() {
    try {
        const data = await apiFetch('../api/users/me');

        if (data.success && data.data) {
            const balance = parseFloat(data.data.balance) || 0;
            userBalance = balance; // Guardar en variable global
            const balElem = document.getElementById('totalBalance');
            if (balElem) balElem.textContent = `$${balance.toFixed(2)}`;
        }
    } catch (e) {
        console.error('Error loading balance:', e);
    }
}

// Load VPS List (first 5)
async function loadVPSList() {
    try {
        const data = await apiFetch('../api/servers/list');

        const container = document.getElementById('vpsList');
        if (!container) return;

        if (data.success && data.data && data.data.length > 0) {
            vpsCount = data.data.length; // Guardar en variable global
            const vpsCountElem = document.getElementById('totalVPS');
            if (vpsCountElem) vpsCountElem.textContent = data.data.length;

            // Check for VPS expiring within 24 hours
            const now = Date.now();
            const in24h = now + 24 * 60 * 60 * 1000;
            const expiringSoon = data.data.filter(s => {
                if (!s.expires_at || s.status === 'SUSPENDED' || s.status === 'TERMINATED') return false;
                const exp = new Date(s.expires_at).getTime();
                return exp > now && exp <= in24h;
            });
            if (expiringSoon.length > 0) {
                const banner = document.getElementById('vpsExpiryWarning');
                const text   = document.getElementById('vpsExpiryText');
                if (banner && text) {
                    const names = expiringSoon.map(s => `<strong>${s.name}</strong>`).join(', ');
                    const suffix = expiringSoon.length === 1 ? 'se suspenderá' : 'se suspenderán';
                    text.innerHTML = `Atención: ${names} ${suffix} en menos de 24 horas. Renueva tu plan para evitar la suspensión.`;
                    banner.style.display = 'block';
                }
            }

            // Show only first 5 servers
            const limitedServers = data.data.slice(0, 5);

            container.innerHTML = limitedServers.map(server => {
                const status = server.status || 'unknown';
                let statusClass = 'status-inactive';
                let statusText = LANG.status_inactive;

                if (status === 'ACTIVE') {
                    statusClass = 'status-active';
                    statusText = LANG.status_active;
                }
                else if (status === 'PROVISIONING') {
                    statusClass = 'status-provisioning';
                    statusText = LANG.status_provisioning;
                }
                else if (status === 'INACTIVE') {
                    statusClass = 'status-inactive';
                    statusText = LANG.status_inactive;
                }

                const ramDisplay = server.specs && server.specs.ram ? server.specs.ram : 'N/A';

                return `
                    <div class="vps-mini-card" onclick="window.location.href='manage/?id=${server.id}'">
                        <div class="vps-info">
                            <div class="vps-name">${server.name || 'VPS-' + server.id}</div>
                            <div class="vps-specs">
                                ${server.specs ? server.specs.cpu : 'N/A'} • ${ramDisplay} • 
                                ${server.ip || LANG.status_pending + '...'}
                            </div>
                        </div>
                        <span class="vps-status ${statusClass}">${statusText}</span>
                    </div>
                `;
            }).join('');
        } else {
            const vpsCountElem = document.getElementById('totalVPS');
            if (vpsCountElem) vpsCountElem.textContent = '0';
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-server"></i>
                    <p>${LANG.dash_no_servers}</p>
                    <a href="vps" class="btn btn-manage" style="margin-top: 10px;">
                        <i class="fas fa-plus"></i> ${LANG.dash_btn_create_server}
                    </a>
                </div>
            `;
        }
    } catch (e) {
        console.error('Error loading VPS:', e);
        const container = document.getElementById('vpsList');
        if (container) container.innerHTML = `<div class="empty-state" style="color: #ef4444;">${LANG.dash_err_servers}</div>`;
    }
}

// Load Recent News (first 3)
async function loadRecentNews() {
    const container = document.getElementById('newsListHome');
    if (!container) return;

    try {
        const res = await fetch(`../api/news/list?page=1&limit=3`);
        const data = await res.json();

        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = `<div class="news-mini-list">` + data.data.map(item => {
                const date = new Date(item.created_at).toLocaleDateString(LANG.txt_date_format, {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });

                return `
                    <div class="news-mini-card" onclick="window.location.href='news/detail?id=${item.id}'">
                        <span class="news-mini-badge">${item.category}</span>
                        <div class="news-mini-title">${item.title}</div>
                        <div class="news-mini-date">
                            <i class="far fa-calendar-alt"></i> ${date}
                        </div>
                    </div>
                `;
            }).join('') + '</div>';
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="far fa-newspaper"></i>
                    <p>${LANG.news_no_items}</p>
                </div>
            `;
        }
    } catch (e) {
        console.error('Error loading news:', e);
        container.innerHTML = ''; // Silently fail for news on home
    }
}

// Load recent transactions (real balance movements, not orders)
async function loadRecentTransactions() {
    const container = document.getElementById('transactionsList');
    if (!container) return;
    try {
        const res = await fetch('../api/transactions/list?limit=5&page=1');
        const data = await res.json();

        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = '<ul class="item-list">' + data.data.map(tx => {
                const status = (tx.status || '').toLowerCase();
                let statusClass = 'status-inactive';
                let statusText = status;
                switch (status) {
                    case 'completed': statusClass = 'status-active';       statusText = LANG.status_completed; break;
                    case 'pending':   statusClass = 'status-provisioning'; statusText = LANG.status_pending;   break;
                    case 'failed':    statusClass = 'status-inactive';     statusText = LANG.status_failed;    break;
                    case 'confirming':statusClass = 'status-provisioning'; statusText = LANG.status_pending;   break;
                }
                const dateStr = new Date(tx.created_at).toLocaleDateString(LANG.txt_date_format, {
                    year: 'numeric', month: 'short', day: 'numeric'
                });
                const desc = tx.description || tx.type || 'Transaction';
                return `
                    <li>
                        <div class="item-info">
                            <div class="item-title">${desc}</div>
                            <div class="item-meta">${dateStr} · ${tx.payment_currency || tx.currency || ''}</div>
                        </div>
                        <span class="item-badge ${statusClass}">$${parseFloat(tx.amount || 0).toFixed(2)} · ${statusText}</span>
                    </li>`;
            }).join('') + '</ul>';
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exchange-alt"></i>
                    <p>${LANG.dash_no_orders}</p>
                </div>`;
        }
    } catch (e) {
        container.innerHTML = `<div class="empty-state" style="color:#ef4444;">${LANG.dash_err_orders}</div>`;
    }
}


// Load Domains count
async function loadDomains() {
    try {
        const data = await apiFetch('../api/domains/list');

        if (data.success && data.data) {
            domainsCount = data.data.length; // Guardar en variable global
            const domCountElem = document.getElementById('totalDomains');
            if (domCountElem) domCountElem.textContent = data.data.length;
        }
    } catch (e) {
        console.error('Error loading domains:', e);
    }
}

// Load everything on page load
document.addEventListener('DOMContentLoaded', initializeDashboard);
