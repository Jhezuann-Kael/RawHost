const serverId = window.serverId || "";
let currentPlanName = "";
let currentPlanPrice = 0;
window.vpsRemainingHours = 0;
window.vpsAddonsPrice = 0;
let _statsFailCount = 0;
const _STATS_MAX_FAILS = 3;

function formatCurrency(amount) {
    return '$' + parseFloat(amount || 0).toFixed(2);
}

async function loadUserBalance(elementId) {
    try {
        const res = await fetch('/api/users/me');
        const data = await res.json();
        if (data.success && data.data) {
            const balanceEl = document.getElementById(elementId);
            if (balanceEl) {
                balanceEl.textContent = formatCurrency(data.data.balance);
            }
        }
    } catch (e) {
        console.error('Error loading user balance:', e);
    }
}

async function fetchServerDetails() {
    try {
        const response = await fetch(`/api/servers/detail?id=${serverId}`);
        const result = await response.json();

        if (!result.success) {
            showNotification('error', LANG_MAN.js_err, result.message);
            setTimeout(() => { window.location.href = 'index'; }, 2000);
            return;
        }

        const vps = result.data;
        currentPlanName = vps.plan;

        document.title = `${LANG_MAN.title} - ${vps.name}`;

        document.getElementById('vps-name').textContent = vps.name;
        const statusElement = document.getElementById('vps-status');

        let statusText = LANG_MAN.stat_inactive;
        if (vps.status === 'ACTIVE') statusText = LANG_MAN.stat_active;
        else if (vps.status === 'PROVISIONING') statusText = LANG_MAN.stat_prov;
        else if (vps.status === 'INACTIVE') statusText = LANG_MAN.stat_inactive;

        statusElement.textContent = statusText;
        const statusClass = 'status-' + (vps.status || 'provisioning').toLowerCase();
        statusElement.className = 'status-badge ' + statusClass;

        document.getElementById('vps-plan').textContent = vps.plan;
        const planPrice = parseFloat(vps.plan_price || 0);
        document.getElementById('vps-price').textContent = planPrice.toFixed(2);
        currentPlanPrice = planPrice;
        window.vpsRemainingHours = parseFloat(vps.remaining_hours || 0);
        window.vpsAddonsPrice = parseFloat(vps.addons_price || 0);
        document.getElementById('vps-expiry').textContent = vps.expires_at ? new Date(vps.expires_at).toLocaleDateString() : 'N/A';

        const totalHours = vps.duration || 100;
        const remaining = vps.remaining_hours || 0;

        let percentage = (remaining / totalHours) * 100;
        if (percentage > 100) percentage = 100;

        const fill = document.getElementById('vps-time-fill');

        fill.style.width = percentage + '%';

        const timeRemainingEl = document.getElementById('time-remaining');
        if (timeRemainingEl) {
            let displayText = vps.remaining_display || '0 horas';
            timeRemainingEl.textContent = displayText;
        } else {
            console.error("Element 'time-remaining' not found!");
        }

        if (percentage > 50) {
            fill.style.backgroundColor = '#10b981';
        } else if (percentage > 10) {
            fill.style.backgroundColor = '#f59e0b';
        } else {
            fill.style.backgroundColor = '#ef4444';
        }

        document.getElementById('vps-ip').textContent = vps.ip;
        const sshIpHint = document.getElementById('ssh-ip-hint');
        if (sshIpHint) sshIpHint.textContent = vps.ip;

        const appLoginRow = document.getElementById('app-login-row');
        if (appLoginRow && vps.app_login_link) {
            document.getElementById('app-login-url').textContent = vps.app_login_link;
            setupCopy('copy-app-login', vps.app_login_link);
            appLoginRow.style.display = '';
        }
        document.getElementById('vps-user').textContent = vps.user;
        document.getElementById('vps-password-raw').value = vps.password;

        setupCopy('copy-ip', vps.ip);
        setupCopy('copy-user', vps.user);
        setupCopy('copy-password', vps.password);

        document.getElementById('toggle-password').onclick = function () {
            const display = document.getElementById('vps-password-display');
            const icon = this.querySelector('i');
            const raw = document.getElementById('vps-password-raw').value;

            if (display.textContent.includes('•')) {
                display.textContent = raw;
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                display.textContent = '••••••••••';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        };

        document.getElementById('spec-cpu').textContent = vps.specs.cpu;
        document.getElementById('spec-ram').textContent = vps.specs.ram;
        document.getElementById('spec-disk').textContent = vps.specs.disk;
        document.getElementById('spec-os').textContent = vps.os;

        document.getElementById('loading').classList.add('hidden');
        document.getElementById('main-content').classList.remove('hidden');

        updateResourceMeters();

    } catch (error) {
        console.error('Error fetching details:', error);
        showNotification('error', LANG_MAN.js_err, 'Na se pudieron cargar los detalles.');
    }
}

async function updateResourceMeters() {
    const safeFetch = async (url) => {
        const r = await fetch(url, { credentials: 'include' });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
    };

    const handleError = (err) => {
        _statsFailCount++;
        console.warn(`Stats fetch error (${_statsFailCount}/${_STATS_MAX_FAILS}):`, err.message);
        if (_statsFailCount >= _STATS_MAX_FAILS) _stopStatsPolling();
    };

    safeFetch(`/api/servers/usage?id=${serverId}&type=cpu`)
        .then(cpuResult => {
            if (cpuResult.success && cpuResult.data.items?.length > 0) {
                const latest = cpuResult.data.items[cpuResult.data.items.length - 1];
                const cpuUsage = Math.min(Math.max(latest.load_average || 0, 0), 100);
                document.getElementById('cpu-usage-value').textContent = cpuUsage.toFixed(1) + '%';
                document.getElementById('cpu-usage-bar').style.width = cpuUsage + '%';
            }
            _statsFailCount = 0;
        })
        .catch(err => {
            handleError(err);
            document.getElementById('cpu-usage-value').textContent = 'N/A';
        });

    safeFetch(`/api/servers/usage?id=${serverId}&type=memory`)
        .then(memoryResult => {
            if (memoryResult.success && memoryResult.data.items?.length > 0) {
                const latest = memoryResult.data.items[memoryResult.data.items.length - 1];
                const ramUsedMB = Math.round(latest.memory || 0);
                const specRamText = document.getElementById('spec-ram').textContent;
                const ramTotalGB = parseFloat(specRamText) || 1;
                const ramTotalMB = Math.round(ramTotalGB * 1024);
                const ramPercentage = Math.min((ramUsedMB / ramTotalMB) * 100, 100);
                document.getElementById('ram-usage-value').textContent = `${ramUsedMB} MB / ${ramTotalMB} MB`;
                document.getElementById('ram-usage-bar').style.width = ramPercentage + '%';
            }
        })
        .catch(err => {
            handleError(err);
            document.getElementById('ram-usage-value').textContent = 'N/A';
        });

    safeFetch(`/api/servers/usage?id=${serverId}&type=network`)
        .then(networkResult => {
            if (networkResult.success && networkResult.data.items?.length > 0) {
                const latest = networkResult.data.items[networkResult.data.items.length - 1];
                const activity = (latest.derivative?.read_kb || 0) + (latest.derivative?.write_kb || 0);
                const display = activity > 1024 ? (activity / 1024).toFixed(2) + ' MB/s' : activity.toFixed(2) + ' KB/s';
                const pct = Math.min((activity / (100 * 1024)) * 100, 100);
                document.getElementById('network-usage-value').textContent = display;
                document.getElementById('network-usage-bar').style.width = pct + '%';
            } else {
                document.getElementById('network-usage-value').textContent = '0 KB/s';
                document.getElementById('network-usage-bar').style.width = '0%';
            }
        })
        .catch(err => {
            handleError(err);
            document.getElementById('network-usage-value').textContent = 'N/A';
        });

    const diskUsage = safeFetch(`/api/servers/usage?id=${serverId}&type=disks`);
    safeFetch(`/api/servers/disk?id=${serverId}`)
        .then(diskDetailResult => {
            if (diskDetailResult?.success && diskDetailResult.data) {
                const d = diskDetailResult.data;
                const usedGB = parseFloat(d.actual_size ?? 0) / 1073741824;
                const totalGB = parseFloat(document.getElementById('spec-disk').textContent) || 20;
                const pct = Math.min((usedGB / totalGB) * 100, 100);
                document.getElementById('disk-usage-value').textContent = `${usedGB.toFixed(1)} GB / ${totalGB} GB`;
                document.getElementById('disk-usage-bar').style.width = pct + '%';
            } else {
                return diskUsage.then(diskResult => {
                    if (diskResult?.success && diskResult.data?.items?.length > 0) {
                        const latest = diskResult.data.items[diskResult.data.items.length - 1];
                        const dev = latest.derivative || {};
                        const totalKB = parseFloat(dev.read_kb ?? dev.read ?? 0) + parseFloat(dev.write_kb ?? dev.write ?? 0);
                        const display = totalKB > 1024 ? (totalKB / 1024).toFixed(2) + ' MB/s' : totalKB.toFixed(2) + ' KB/s';
                        document.getElementById('disk-usage-value').textContent = display;
                        document.getElementById('disk-usage-bar').style.width = Math.min((totalKB / (500 * 1024)) * 100, 100) + '%';
                    } else {
                        document.getElementById('disk-usage-value').textContent = '— GB';
                        document.getElementById('disk-usage-bar').style.width = '0%';
                    }
                });
            }
        })
        .catch(() => {
            diskUsage.then(diskResult => {
                if (diskResult?.success && diskResult.data?.items?.length > 0) {
                    const latest = diskResult.data.items[diskResult.data.items.length - 1];
                    const dev = latest.derivative || {};
                    const totalKB = parseFloat(dev.read_kb ?? dev.read ?? 0) + parseFloat(dev.write_kb ?? dev.write ?? 0);
                    const display = totalKB > 1024 ? (totalKB / 1024).toFixed(2) + ' MB/s' : totalKB.toFixed(2) + ' KB/s';
                    document.getElementById('disk-usage-value').textContent = display;
                    document.getElementById('disk-usage-bar').style.width = Math.min((totalKB / (500 * 1024)) * 100, 100) + '%';
                } else {
                    document.getElementById('disk-usage-value').textContent = '— GB';
                    document.getElementById('disk-usage-bar').style.width = '0%';
                }
            }).catch(err => {
                handleError(err);
                document.getElementById('disk-usage-value').textContent = 'N/A';
            });
        });
}

function switchResourceTab(tab) {
    const resourceTabs = document.querySelectorAll('[onclick^="switchResourceTab"]');
    resourceTabs.forEach(t => t.classList.remove('active'));
    event.target.closest('button')?.classList.add('active');

    document.querySelectorAll('.resource-tab-content').forEach(content => {
        content.classList.remove('active');
    });

    const targetContent = document.getElementById(`resource-tab-${tab}`);
    if (targetContent) {
        targetContent.classList.add('active');

        if (tab === 'graphs') {
            const activeSubTab = document.querySelector('.stats-tab[data-tab].active')?.dataset.tab || 'network';
            setTimeout(() => loadServerStats(activeSubTab), 100);
        }
    }
}

function setupCopy(elementId, text) {
    const el = document.getElementById(elementId);
    if (el) {
        el.onclick = () => {
            navigator.clipboard.writeText(text).then(() => { });
        };
    }
}

if (serverId && serverId != '0') {
    fetchServerDetails().then(() => {
        setTimeout(() => {
            loadServerStats('network');
            startStatsRefresh();
        }, 500);
    });
} else {
    showNotification('error', LANG_MAN.js_err, LANG_MAN.err_invalid_id);
    setTimeout(() => {
        window.location.href = 'index';
    }, 2000);
}

function showNotification(type, title, message, duration = 5000) {
    const container = document.getElementById('notificationContainer');

    const toast = document.createElement('div');
    toast.className = `notification-toast ${type}`;

    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    else if (type === 'error') icon = 'fa-exclamation-circle';
    else if (type === 'warning') icon = 'fa-exclamation-triangle';

    toast.innerHTML = `
        <div class="notification-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    if (duration > 0) {
        setTimeout(() => {
            closeNotification(toast.querySelector('.notification-close'));
        }, duration);
    }
}

function closeNotification(button) {
    const toast = button.closest('.notification-toast');
    if (toast) {
        toast.classList.add('hiding');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

let confirmResolve = null;

function showConfirm(title, message, confirmText = 'Confirmar', dangerAction = false) {
    return new Promise((resolve) => {
        confirmResolve = resolve;

        const modal = document.getElementById('confirmModal');
        if (!modal) {
            console.error(LANG_MAN.err_modal_not_found);
            resolve(false);
            return;
        }

        const titleEl = document.getElementById('confirmModalTitle');
        const messageEl = document.getElementById('confirmModalMessage');
        const confirmBtn = document.getElementById('confirmModalBtn');

        if (!titleEl || !messageEl || !confirmBtn) {
            console.error(LANG_MAN.err_modal_elements);
            resolve(false);
            return;
        }

        titleEl.textContent = title;
        messageEl.textContent = message;
        confirmBtn.textContent = confirmText;
        confirmBtn.className = dangerAction ? 'btn btn-power' : 'btn btn-manage';

        modal.style.zIndex = '10000';

        modal.classList.add('active');
        modal.style.display = 'flex';
    });
}

function closeConfirmModal(confirmed) {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('active');
    modal.style.display = 'none';

    if (confirmResolve) {
        confirmResolve(confirmed);
        confirmResolve = null;
    }
}
