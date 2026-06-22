/* orders.js — Order History module (pagination + rendering) */

const OrderHistory = (() => {
    let _page       = 1;
    let _totalPages = 1;

    // ── Public API ────────────────────────────────────────────────────────────

    async function load(page = 1) {
        _page = Math.max(1, Math.min(page, _totalPages));
        _setLoading(true);

        try {
            const res = await fetch(
                `/api/orders/list_by_vps?vps_id=${encodeURIComponent(serverId)}&page=${_page}`
            );
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Unknown error');

            _totalPages = data.total_pages;
            _renderRows(data.orders);
            _renderPagination(data.page, data.total_pages, data.total);
        } catch (e) {
            _renderError(e.message);
        } finally {
            _setLoading(false);
        }
    }

    function goTo(page) {
        load(page);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    function _tbody() { return document.getElementById('orders-body'); }
    function _pager() { return document.getElementById('orders-pagination'); }

    function _setLoading(on) {
        const el = document.getElementById('orders-loading-indicator');
        if (el) el.style.display = on ? 'inline-block' : 'none';
    }

    function _statusBadge(status) {
        const map = {
            COMPLETED: { label: LANG_MAN.hist_completed, cls: 'status-active'        },
            PENDING:   { label: LANG_MAN.hist_pending,   cls: 'status-provisioning'  },
            FAILED:    { label: LANG_MAN.hist_failed,     cls: 'status-inactive'      },
        };
        const s = map[status] ?? { label: status, cls: '' };
        return `<span class="status-badge ${s.cls}">${s.label}</span>`;
    }

    function _renderRows(orders) {
        const tbody = _tbody();
        if (!tbody) return;

        if (!orders?.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">
                        <i class="fas fa-inbox" style="opacity:.35; font-size:1.4rem; display:block; margin-bottom:6px;"></i>
                        No orders yet
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = orders.map(o => `
            <tr>
                <td><code>#${o.id}</code></td>
                <td>${o.plan_name || 'N/A'}</td>
                <td>${o.duration} h</td>
                <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                <td>${new Date(o.created_at).toLocaleDateString()}</td>
                <td>${_statusBadge(o.status)}</td>
            </tr>`).join('');
    }

    function _renderError(msg) {
        const tbody = _tbody();
        if (tbody) tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center; padding:20px; color:#ef4444;">
                    <i class="fas fa-exclamation-circle"></i> ${msg}
                </td>
            </tr>`;
        const pager = _pager();
        if (pager) pager.innerHTML = '';
    }

    function _renderPagination(page, totalPages, total) {
        const el = _pager();
        if (!el) return;

        const countLabel = `<span class="orders-count">${total} order${total !== 1 ? 's' : ''}</span>`;

        if (totalPages <= 1) {
            el.innerHTML = countLabel;
            return;
        }

        const prevDisabled = page <= 1            ? 'disabled' : '';
        const nextDisabled = page >= totalPages   ? 'disabled' : '';

        el.innerHTML = `
            ${countLabel}
            <div class="orders-page-controls">
                <button class="orders-page-btn" ${prevDisabled}
                    onclick="OrderHistory.goTo(${page - 1})" aria-label="Previous page">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="orders-page-info">${page} / ${totalPages}</span>
                <button class="orders-page-btn" ${nextDisabled}
                    onclick="OrderHistory.goTo(${page + 1})" aria-label="Next page">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>`;
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => {
        const waitForContent = setInterval(() => {
            const mc = document.getElementById('main-content');
            if (mc && !mc.classList.contains('hidden')) {
                clearInterval(waitForContent);
                load(1);
            }
        }, 400);
    });

    return { load, goTo };
})();
