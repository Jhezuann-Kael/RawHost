/**
 * IP Manager Script
 * Handles IP purchasing and management separately from main.js
 */

// ========== IP ADDONS MANAGEMENT ==========
async function loadAddons() {
    try {
        const res = await fetch(`/api/addons/list?vps_id=${serverId}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        if (data.success && data.addons) {
            renderAddons(data.addons);
        }
    } catch (e) {
        console.error('Error loading addons:', e);
    }
}

function renderAddons(addons) {
    const tbody = document.getElementById('addons-body');
    if (!tbody) return; // Guard clause if element doesn't exist

    if (!addons || addons.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center; padding: 20px; color: var(--text-muted);">
                    ${LANG_MAN.ip_none} <a href="#" onclick="openBuyIpModal(); return false;" style="color: var(--primary);">${LANG_MAN.ip_buy}</a>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = addons.map(addon => {
        let statusBadge = 'status-inactive';
        let statusText = addon.status;

        if (addon.status === 'ACTIVE') {
            statusBadge = 'status-active';
            statusText = LANG_MAN.stat_active;
        } else if (addon.status === 'PENDING') {
            statusBadge = 'status-provisioning';
            statusText = LANG_MAN.stat_prov;
        }

        const expiryDate = addon.expires_at ? new Date(addon.expires_at).toLocaleDateString() : LANG_MAN.ip_no_expiry;

        return `
            <tr>
                <td><span class="badge badge-primary">${addon.type || 'IPV4'}</span></td>
                <td><code>${addon.value || LANG_MAN.ip_assigning}</code></td>
                <td>$${parseFloat(addon.price).toFixed(2)}/mes</td>
                <td><span class="badge ${statusBadge}">${statusText}</span></td>
                <td>${expiryDate}</td>
                <td>
                    ${addon.value ? `<button class="btn-icon-tiny" onclick="copyToClipboard('${addon.value}')" title="${LANG_MAN.ip_copy}"><i class="fas fa-copy"></i></button>` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

async function openBuyIpModal() {
    const modal = document.getElementById('buyIpModal');
    if (!modal) return;

    // Show modal immediately
    modal.classList.add('active');

    // Reset result div and button state
    const resultDiv = document.getElementById('ip-purchase-result');
    if (resultDiv) resultDiv.style.display = 'none';

    const btn = document.getElementById('btnBuyIp');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-shopping-cart"></i> ${LANG_MAN.ip_btn_buy}`;
    }

    // Load user balance using shared helper from main.js
    if (typeof loadUserBalance === 'function') {
        loadUserBalance('user-balance-ip');
    }
}

function closeBuyIpModal() {
    const modal = document.getElementById('buyIpModal');
    if (modal) modal.classList.remove('active'); // Use active class for CSS transitions

    const resultDiv = document.getElementById('ip-purchase-result');
    if (resultDiv) resultDiv.style.display = 'none';
}

// Generic tab switcher used by Finder windows
function switchFinderTab(group, tab, btnEl) {
    const prefix = `${group}-tab`;
    const win = btnEl?.closest('.finder-window');
    if (win) {
        win.querySelectorAll('.finder-tab-content').forEach(el => el.classList.remove('active'));
        win.querySelectorAll('.stats-tab').forEach(b => b.classList.remove('active'));
    }
    const target = document.getElementById(`${prefix}-${tab}`);
    if (target) target.classList.add('active');
    if (btnEl) btnEl.classList.add('active');

    if (group === 'ip' && tab === 'add' && typeof loadUserBalance === 'function') {
        loadUserBalance('user-balance-ip');
    }
}

async function confirmBuyIp(inline = false) {
    const btn = document.getElementById(inline ? 'btnBuyIpInline' : 'btnBuyIp');
    const resultDiv = document.getElementById('ip-purchase-result');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_MAN.js_processing}`;
    }

    try {
        const res = await fetch('/api/orders/create_addon_order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                vps_id: serverId
            })
        });

        const data = await res.json();

        if (data.success) {
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.style.background = 'rgba(16, 185, 129, 0.1)';
                resultDiv.style.color = '#10b981';
                resultDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            }

            setTimeout(() => {
                closeBuyIpModal();
                loadAddons();
                // Switch back to list tab after inline purchase
                const listBtn = document.querySelector('.finder-window#ips-section .stats-tab');
                if (listBtn) switchFinderTab('ip', 'list', listBtn);
                loadUserBalance('user-balance-ip');
                if (typeof showNotification === 'function') showNotification('success', '¡Éxito!', data.message);
            }, 2000);
        } else {
            throw new Error(data.message || LANG_MAN.js_err);
        }
    } catch (e) {
        if (resultDiv) {
            resultDiv.style.display = 'block';
            resultDiv.style.background = 'rgba(239, 68, 68, 0.1)';
            resultDiv.style.color = '#ef4444';
            resultDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + e.message;
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-shopping-cart"></i> ${LANG_MAN.ip_btn_buy}`;
        }
    }
}

// Initialize when DOM is ready
window.addEventListener('DOMContentLoaded', () => {
    if (serverId) loadAddons();
});
