<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/lang_loader.php';
require_once '../api/config.php';

// Get transaction ID from URL
$txId = intval($_GET['id'] ?? 0);
if ($txId <= 0) {
    header('Location: transactions.php');
    exit;
}

$pageTitle = $lang['td_page_title'] . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="css/transaction_detail.min.css">
';
include 'header.php';
?>

<main class="main-content">
    <!-- Page header -->
    <div class="td-page-header">
        <a href="transactions" class="td-back-btn">
            <i class="fas fa-arrow-left"></i>
            <?php echo $lang['td_back_link']; ?>
        </a>
        <h1 class="td-page-title">
            <i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>
            <?php echo $lang['td_header']; ?>
        </h1>
        <span class="td-track-badge" id="headerTrackId">#<?php echo $txId; ?></span>
    </div>

    <!-- Loading state -->
    <div id="tdLoading" class="td-loading">
        <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>
        <span><?php echo $lang['td_loading']; ?></span>
    </div>

    <!-- Main content (hidden until loaded) -->
    <div id="tdContent" style="display:none;">

        <!-- Row 1: Unified transaction card -->
        <div class="td-card" id="cardUnified" style="margin-bottom:20px;">
            <div class="td-card-header">
                <i class="fas fa-file-invoice"></i>
                <?php echo $lang['td_card_title']; ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;">
                <div class="td-card-body" id="invoiceBody" style="border-right:1px solid rgba(255,255,255,0.06);"></div>
                <div class="td-card-body" id="oxaBody"></div>
            </div>
        </div>

        <!-- Row 2: QR + Txs -->
        <div class="td-grid">

            <!-- QR / Address block -->
            <div class="td-card" id="cardQr">
                <div class="td-card-header">
                    <i class="fas fa-qrcode"></i>
                    <?php echo $lang['td_payment_address']; ?>
                </div>
                <div class="td-qr-wrap" id="qrBody">
                    <!-- filled by JS -->
                </div>
            </div>

            <!-- TXs block -->
            <div class="td-card" id="cardTxs" style="display:none;">
                <div class="td-card-header">
                    <i class="fas fa-link"></i>
                    <?php echo $lang['td_txs_onchain']; ?>
                </div>
                <div class="td-txs-section" id="txsBody">
                    <!-- filled by JS -->
                </div>
            </div>

        </div>

    </div><!-- /#tdContent -->

    <!-- Error state -->
    <div id="tdError" class="td-error" style="display:none;">
        <i class="fas fa-exclamation-circle" style="font-size:2rem;margin-bottom:10px;"></i>
        <p id="tdErrorMsg">Could not load transaction.</p>
        <a href="transactions" class="td-back-btn" style="margin-top:12px;">
            <i class="fas fa-arrow-left"></i> <?php echo $lang['td_back_link']; ?>
        </a>
    </div>

</main>

<script>
    const TX_ID = <?php echo $txId; ?>;

    // ── i18n strings for JS ──────────────────────────────────────────────────
    const LANG_TD = <?php echo json_encode([
        'copy' => $lang['td_copy'],
        'copied' => $lang['td_copied'],
        'no_addr' => $lang['td_no_address'],
        'no_track' => $lang['td_no_track'],
        'expires_in' => $lang['td_expires_in'],
        'txs_title' => $lang['td_txs_onchain'],
        'status_completed' => $lang['status_completed'],
        'status_pending' => $lang['status_pending'],
        'status_expired' => $lang['tx_status_expired'],
        'status_failed' => $lang['status_failed'],
        // Row labels - invoice panel
        'lbl_id' => $lang['td_lbl_id'],
        'lbl_currency' => $lang['td_lbl_currency'],
        'lbl_amount' => $lang['td_lbl_amount'],
        'lbl_crypto_amt' => $lang['td_lbl_crypto_amount'],
        'lbl_created' => $lang['td_lbl_created'],
        'lbl_expires' => $lang['td_lbl_expires'],
        // Row labels - OxaPay panel
        'lbl_track_id' => $lang['td_lbl_track_id'],
        'lbl_status' => $lang['td_lbl_status'],
        'lbl_lifetime' => $lang['td_lbl_lifetime'],
        'lbl_mixed' => $lang['td_lbl_mixed'],
        'lbl_date_exp' => $lang['td_lbl_date_expires'],
        'lbl_description' => $lang['td_lbl_description'],
        'lbl_order_id' => $lang['td_lbl_order_id'],
        // Row labels - Txs block
        'lbl_hash' => $lang['td_lbl_hash'],
        'lbl_network' => $lang['td_lbl_network'],
        'lbl_received' => $lang['td_lbl_received'],
        'lbl_confirms' => $lang['td_lbl_confirms'],
        'lbl_auto_conv' => $lang['td_lbl_auto_conv'],
        'lbl_address' => $lang['td_lbl_address'],
        'lbl_tx_date' => $lang['td_lbl_tx_date'],
        'lbl_tx_num' => $lang['td_lbl_tx_num'],
        // Boolean
        'yes' => $lang['td_yes'],
        'no' => $lang['td_no'],
        // Error messages
        'err_connect' => $lang['td_err_connect'],
        'err_not_found' => $lang['td_err_not_found'],
        'err_forbidden' => $lang['td_err_forbidden'],
        'err_track' => $lang['td_err_track'],
        'err_timeout' => $lang['td_err_timeout'],
        'err_gateway' => $lang['td_err_gateway'],
        'err_generic' => $lang['td_err_generic'],
        'err_default' => $lang['td_err_default'],
    ]); ?>;


    // ── helpers ──────────────────────────────────────────────
    const fmt = ts => ts ? new Date(ts * 1000).toLocaleString() : '–';
    const money = v => v !== undefined && v !== null ? `$${parseFloat(v).toFixed(4)}` : '–';
    const tf = b => b ? LANG_TD.yes : LANG_TD.no;

    function row(label, value, cls = '') {
        return `<div class="td-row">
        <span class="td-label">${label}</span>
        <span class="td-value ${cls}">${value}</span>
    </div>`;
    }

    function statusBadge(s) {
        if (!s) return '–';
        const sl = s.toLowerCase();
        // Normalize both local DB statuses and OxaPay API statuses to the 4 web classes
        const cls =
            (sl === 'completed' || sl === 'paid') ? 'status-completed' :
                (sl === 'pending' || sl === 'unpaid' || sl === 'waiting') ? 'status-pending' :
                    (sl === 'expired' || sl === 'cancelled') ? 'status-expired' :
        /* failed / refunded / etc. */                                   'status-failed';

        const label = {
            completed: LANG_TD.status_completed, paid: LANG_TD.status_completed,
            pending: LANG_TD.status_pending, unpaid: LANG_TD.status_pending, waiting: LANG_TD.status_pending,
            expired: LANG_TD.status_expired, cancelled: LANG_TD.status_expired,
            failed: LANG_TD.status_failed
        }[sl] || s;

        return `<span class="${cls}">${label}</span>`;
    }

    // ── main loader ───────────────────────────────────────────
    async function loadDetail() {
        try {
            const res = await fetch(`../api/transactions/payment_info?id=${TX_ID}`);
            const resp = await res.json();

            if (!resp.success) {
                showError(friendlyError(resp.message));
                return;
            }

            const loc = resp.local;   // DB row
            const d = resp.data;    // OxaPay live data (may be null if no track_id)

            // Update header badge
            const trackId = d?.track_id || loc.track_id || '–';
            document.getElementById('headerTrackId').textContent = trackId;

            // ── Left panel: local invoice ──────────────────────
            let invHtml = '';
            invHtml += row(LANG_TD.lbl_id, `#${loc.id}`, 'mono');
            invHtml += row(LANG_TD.lbl_currency, `${loc.payment_currency || '–'} / ${loc.network || '–'}`);
            invHtml += row(LANG_TD.lbl_amount, `$${parseFloat(loc.amount).toFixed(2)} USD`, 'green bold');
            if (loc.payment_amount) {
                invHtml += row(LANG_TD.lbl_crypto_amt, `${loc.payment_amount} ${loc.payment_currency}`);
            }
            invHtml += row(LANG_TD.lbl_created, fmt(loc.created_at ? Math.floor(new Date(loc.created_at).getTime() / 1000) : null));
            invHtml += row(LANG_TD.lbl_expires, fmt(loc.expired_at));
            document.getElementById('invoiceBody').innerHTML = invHtml;

            // ── Right panel: OxaPay live data ─────────────────
            let oxaHtml = '';
            if (d) {
                oxaHtml += row(LANG_TD.lbl_track_id, `<span class="mono">${d.track_id}</span>`);
                oxaHtml += row(LANG_TD.lbl_status, statusBadge(d.status));
                oxaHtml += row(LANG_TD.lbl_lifetime, d.lifetime ? d.lifetime + ' min' : '–');
                oxaHtml += row(LANG_TD.lbl_mixed, tf(d.mixed_payment));
                if (d.date || d.expired_at) {
                    const dateStr = d.date ? fmt(d.date) : '–';
                    const expiresStr = d.expired_at ? fmt(d.expired_at) : '–';
                    oxaHtml += row(LANG_TD.lbl_date_exp, `${dateStr} / ${expiresStr}`);
                }
                if (d.description) oxaHtml += row(LANG_TD.lbl_description, d.description);
                if (d.order_id) oxaHtml += row(LANG_TD.lbl_order_id, `<span class="mono">${d.order_id}</span>`);
            } else {
                oxaHtml = `<div class="td-loading" style="padding:30px;">
                <i class="fas fa-info-circle" style="color:var(--text-muted);font-size:1.5rem;"></i>
                <span style="color:var(--text-muted);font-size:0.85rem;">${LANG_TD.no_track}</span>
            </div>`;
            }
            document.getElementById('oxaBody').innerHTML = oxaHtml;

            // ── QR / Address block ────────────────────────────
            let qrHtml = '';
            const qr = loc.qr_code;
            const address = loc.address;
            const memo = loc.memo;

            if (qr) {
                qrHtml += `<img class="td-qr-img" src="${qr}" alt="Payment QR">`;
            }
            if (address) {
                qrHtml += `<div class="td-address-block">
                <span class="td-address-text" id="payAddress">${address}</span>
                <button class="td-copy-btn" onclick="copyText('${address}')">
                    <i class="fas fa-copy"></i> ${LANG_TD.copy}
                </button>
            </div>`;
            }
            if (memo) {
                qrHtml += `<div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:8px 12px;font-size:0.8rem;color:#f59e0b;width:100%;text-align:center;">
                <i class="fas fa-tag"></i> MEMO/TAG: <strong>${memo}</strong>
            </div>`;
            }
            if (!qr && !address) {
                qrHtml = `<div class="td-loading" style="padding:30px;">
                <i class="fas fa-qrcode" style="color:var(--text-muted);font-size:1.5rem;"></i>
                <span style="color:var(--text-muted);font-size:0.85rem;">${LANG_TD.no_addr}</span>
            </div>`;
            }

            // Countdown timer for PENDING
            if (loc.status === 'PENDING' && loc.expired_at) {
                qrHtml += `<div class="td-timer">
                <i class="fas fa-clock"></i>
                ${LANG_TD.expires_in} <span id="detailTimer">--:--</span>
            </div>`;
            }
            document.getElementById('qrBody').innerHTML = qrHtml;

            // Start timer if pending
            if (loc.status === 'PENDING' && loc.expired_at) {
                startTimer(loc.expired_at);
            }

            // ── TXs block ─────────────────────────────────────
            if (d?.txs?.length > 0) {
                let txsHtml = `<div class="td-txs-title">${LANG_TD.txs_title}</div>`;
                d.txs.forEach((t, i) => {
                    const autoConv = t.auto_convert?.processed
                        ? `${money(t.auto_convert.amount)} ${t.auto_convert.currency}` : LANG_TD.no;
                    txsHtml += `<div class="td-tx-block">
                    <div class="td-tx-number">${LANG_TD.lbl_tx_num} #${i + 1}</div>
                    ${row(LANG_TD.lbl_hash, `<span class="mono">${t.tx_hash || '–'}</span>`)}
                    ${row(LANG_TD.lbl_network, `${t.network || '–'} (${t.currency || ''})`)}
                    ${row(LANG_TD.lbl_received, `${money(t.amount)} ${t.currency || ''}`, 'green')}
                    ${row(LANG_TD.lbl_confirms, t.confirmations ?? '–')}
                    ${row(LANG_TD.lbl_auto_conv, autoConv)}
                    ${row(LANG_TD.lbl_address, `<span class="mono">${t.address || '–'}</span>`)}
                    ${row(LANG_TD.lbl_tx_date, fmt(t.date))}
                </div>`;
                });
                document.getElementById('txsBody').innerHTML = txsHtml;
                document.getElementById('cardTxs').style.display = 'block';
            }

            // Show content
            document.getElementById('tdLoading').style.display = 'none';
            document.getElementById('tdContent').style.display = 'block';

        } catch (e) {
            console.error(e);
            showError(LANG_TD.err_connect);
        }
    }

    // Map raw/technical error messages to user-friendly copy
    function friendlyError(msg) {
        if (!msg) return LANG_TD.err_default;
        const m = msg.toLowerCase();
        if (m.includes('not found') || m.includes('could not be found'))
            return LANG_TD.err_not_found;
        if (m.includes('unauthorized') || m.includes('forbidden'))
            return LANG_TD.err_forbidden;
        if (m.includes('track') || m.includes('track_id'))
            return LANG_TD.err_track;
        if (m.includes('timeout') || m.includes('timed out'))
            return LANG_TD.err_timeout;
        if (m.includes('oxapay') || m.includes('gateway'))
            return LANG_TD.err_gateway;
        return LANG_TD.err_generic;
    }

    function showError(msg) {
        document.getElementById('tdLoading').style.display = 'none';
        document.getElementById('tdErrorMsg').textContent = msg;
        document.getElementById('tdError').style.display = 'flex';
        document.getElementById('tdError').style.flexDirection = 'column';
        document.getElementById('tdError').style.alignItems = 'center';
    }

    let _timerInterval = null;
    function startTimer(expiredAt) {
        function tick() {
            const diff = Math.max(0, Math.floor((expiredAt - Date.now() / 1000)));
            const m = String(Math.floor(diff / 60)).padStart(2, '0');
            const s = String(diff % 60).padStart(2, '0');
            const el = document.getElementById('detailTimer');
            if (el) el.textContent = `${m}:${s}`;
            if (diff <= 0) clearInterval(_timerInterval);
        }
        tick();
        _timerInterval = setInterval(tick, 1000);
    }

    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('.td-copy-btn');
            if (btn) {
                btn.innerHTML = `<i class="fas fa-check"></i> ${LANG_TD.copied}`;
                setTimeout(() => btn.innerHTML = `<i class="fas fa-copy"></i> ${LANG_TD.copy}`, 2000);
            }
        }).catch(e => console.error(e));
    }

    document.addEventListener('DOMContentLoaded', loadDetail);
</script>

<?php include 'footer.php'; ?>