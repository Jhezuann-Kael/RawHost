<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/lang_loader.php';
require_once '../api/config.php';
require_once '../repositories/TransactionRepository.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$txId = intval($_GET['id'] ?? 0);

if ($txId <= 0) {
    header('Location: vps.php');
    exit;
}

$transRepo = new TransactionRepository();
$invoice = $transRepo->getByIdAndUser($txId, $userId);

if (!$invoice || !in_array($invoice['type'], ['vps_purchase', 'vps_renew', 'vps_upgrade'])) {
    header('Location: vps.php');
    exit;
}

$meta = json_decode($invoice['order_metadata'], true) ?: [];
$planName = $meta['plan_name'] ?? 'VPS Plan';
$duration = $meta['duration'] ?? 720;
$durationText = $duration . ' ' . ($lang['vps_label_hours'] ?? 'hours');
$redirectUrl = ($invoice['type'] === 'vps_purchase') ? 'vps.php' : 'manage/index.php?id=' . ($meta['server_id'] ?? '');

$pageTitle = $lang['vps_inv_title'] . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="css/transaction_detail.min.css">
    <style>
        .invoice-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 24px;
            margin: 40px auto;
            max-width: 500px;
            text-align: center;
        }
        .invoice-summary {
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            text-align: left;
            font-size: 0.85rem;
            border: 1px solid rgba(255,255,255,0.05);
        }
    </style>
';
include 'header.php';
?>

<main class="main-content">
    <div class="td-page-header" style="max-width: 500px; margin: 0 auto 20px;">
        <a href="vps" class="td-back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo $lang['td_back_link']; ?>
        </a>
        <h1 class="td-page-title" style="justify-content:center; width:100%; border:none; padding:0; margin: 10px 0 0;">
            <i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>
            <?php echo $lang['vps_inv_title']; ?>
        </h1>
    </div>

    <!-- Active Invoice View -->
    <div id="invoiceView" class="invoice-card" <?php echo ($invoice['status'] === 'COMPLETED') ? 'style="display:none;"' : ''; ?>>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
            <?php echo $lang['vps_inv_scan_qr']; ?>
        </p>

        <!-- Summary -->
        <div class="invoice-summary">
            <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);"><?php echo $lang['vps_inv_plan']; ?>:</span> <strong><?php echo htmlspecialchars($planName); ?></strong></div>
            <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);"><?php echo $lang['vps_inv_duration']; ?>:</span> <strong><?php echo htmlspecialchars($durationText); ?></strong></div>
            <div style="margin-bottom: 4px;"><span style="color: var(--text-muted);">Hostname:</span> <strong><?php echo htmlspecialchars($meta['name_server'] ?? '-'); ?></strong></div>
            <div><span style="color: var(--text-muted);"><?php echo $lang['vps_inv_total_usd']; ?>:</span> <strong style="color: var(--primary);">$<?php echo number_format($invoice['amount'], 2); ?></strong></div>
        </div>

        <img src="<?php echo htmlspecialchars($invoice['qr_code']); ?>" alt="QR Pago"
            style="width: 180px; height: 180px; border-radius: 10px; margin: 0 auto 16px; display: block; background: #fff; padding: 6px;">
        
        <div style="margin-bottom: 10px;">
            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $lang['vps_inv_network']; ?></span><br>
            <strong style="font-size: 1rem;"><?php echo htmlspecialchars($invoice['payment_currency'] . ' / ' . $invoice['network']); ?></strong>
        </div>
        
        <div style="margin-bottom: 14px;">
            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $lang['vps_inv_amount']; ?></span><br>
            <strong style="font-size: 1.4rem; color: #10b981;"><?php echo htmlspecialchars($invoice['payment_amount']); ?> <?php echo htmlspecialchars($invoice['payment_currency']); ?></strong>
        </div>
        
        <div style="margin-bottom: 14px;">
            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $lang['vps_inv_address']; ?></span><br>
            <div style="display: flex; align-items: center; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 4px;">
                <code id="invoiceAddress"
                    style="font-size: 0.78rem; word-break: break-all; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 8px; cursor: pointer;"
                    onclick="copyAddress()"><?php echo htmlspecialchars($invoice['address']); ?></code>
                <button onclick="copyAddress()"
                    style="background: none; border: 1px solid rgba(255,255,255,0.2); color: var(--text-light); border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 0.8rem;">📋 <?php echo $lang['vps_inv_copy']; ?></button>
            </div>
            
            <?php if (!empty($invoice['memo'])): ?>
            <div style="margin-top: 8px;">
                <span style="font-size: 0.78rem; color: #f59e0b;"><?php echo $lang['vps_inv_memo_warn']; ?></span><br>
                <code style="font-size: 0.78rem; background: rgba(245,158,11,0.15); padding: 4px 10px; border-radius: 6px;"><?php echo htmlspecialchars($invoice['memo']); ?></code>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">
            <?php echo $lang['vps_inv_expires']; ?> <span id="timerValue">--:--</span>
        </div>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 15px;">
            <?php echo $lang['vps_inv_footer_msg']; ?>
        </p>
    </div>

    <!-- Payment Confirmed View -->
    <div id="successView" class="invoice-card" <?php echo ($invoice['status'] === 'COMPLETED') ? '' : 'style="display:none;"'; ?>>
        <div style="padding: 10px;">
            <div style="font-size: 3.5rem; margin-bottom: 16px; animation: popIn 0.5s ease;">✅</div>
            <h3 style="color: #10b981; margin-bottom: 10px; font-size: 1.3rem;"><?php echo $lang['vps_inv_success_title']; ?></h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                <?php echo $lang['vps_inv_success_desc']; ?>
            </p>
            <div style="background: rgba(16,185,129,0.07); border: 1px solid rgba(16,185,129,0.25); border-radius: 12px; padding: 16px; text-align: left; font-size: 0.85rem; margin-bottom: 16px;">
                <div style="margin-bottom:8px;"><span style="color:var(--text-muted);"><?php echo $lang['vps_inv_plan']; ?>:</span> <strong><?php echo htmlspecialchars($planName); ?></strong></div>
                <div style="margin-bottom:8px;"><span style="color:var(--text-muted);"><?php echo $lang['vps_inv_duration']; ?>:</span> <strong><?php echo htmlspecialchars($durationText); ?></strong></div>
                <div style="margin-bottom:8px;"><span style="color:var(--text-muted);">Hostname:</span> <strong><?php echo htmlspecialchars($meta['name_server'] ?? '-'); ?></strong></div>
                <div style="margin-bottom:8px;"><span style="color:var(--text-muted);"><?php echo $lang['vps_inv_amount_paid']; ?>:</span> <strong style="color:#10b981;">$<?php echo number_format($invoice['amount'], 2); ?> USD</strong></div>
                <div><span style="color:var(--text-muted);"><?php echo $lang['vps_inv_track_id']; ?>:</span> <code style="font-size:0.78rem;"><?php echo htmlspecialchars($invoice['track_id']); ?></code></div>
            </div>
            <p style="color: var(--text-muted); font-size: 0.8rem;">
                <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> <?php echo $lang['vps_inv_redirecting']; ?> <span id="cdValue">5</span>s...
            </p>
        </div>
    </div>

</main>

<script>
    const TX_TRACK_ID = '<?php echo $invoice['track_id']; ?>';
    const JS_EXPIRES_AT = <?php echo intval($invoice['expired_at']) * 1000; ?>;
    const MSG_COPIED = '<?php echo $lang['td_copied'] ?? "Copied"; ?>';
    let statusInterval = null;
    let timerInterval = null;
    let IS_COMPLETED = <?php echo ($invoice['status'] === 'COMPLETED') ? 'true' : 'false'; ?>;

    function copyAddress() {
        const addr = document.getElementById('invoiceAddress').textContent;
        navigator.clipboard.writeText(addr).then(() => {
            alert(MSG_COPIED);
        }).catch(() => {
            prompt('Copia:', addr);
        });
    }

    function updateTimer() {
        if (IS_COMPLETED) return;
        const now = Date.now();
        const diff = Math.max(0, Math.floor((JS_EXPIRES_AT - now) / 1000));
        const m = String(Math.floor(diff / 60)).padStart(2, '0');
        const s = String(diff % 60).padStart(2, '0');
        const tv = document.getElementById('timerValue');
        if (tv) tv.textContent = `${m}:${s}`;
        
        if (diff <= 0) {
            clearInterval(timerInterval);
            clearInterval(statusInterval);
        }
    }

    function showCompletion() {
        IS_COMPLETED = true;
        clearInterval(timerInterval);
        clearInterval(statusInterval);
        
        document.getElementById('invoiceView').style.display = 'none';
        document.getElementById('successView').style.display = 'block';
        
        let secs = 5;
        const cdInt = setInterval(() => {
            secs--;
            document.getElementById('cdValue').textContent = secs;
            if (secs <= 0) {
                clearInterval(cdInt);
                window.location.href = '<?php echo htmlspecialchars($redirectUrl); ?>';
            }
        }, 1000);
    }

    if (!IS_COMPLETED) {
        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);

        statusInterval = setInterval(async () => {
            try {
                const res = await fetch(`../api/orders/invoice_status?track_id=${TX_TRACK_ID}`);
                const data = await res.json();
                if (data.success && data.status === 'COMPLETED') {
                    showCompletion();
                } else if (data.success && ['FAILED', 'EXPIRED'].includes(data.status)) {
                    clearInterval(statusInterval);
                    clearInterval(timerInterval);
                }
            } catch (e) {
                // Ignore network errors in polling
            }
        }, 8000);
    } else {
        showCompletion();
    }
</script>

<?php include 'footer.php'; ?>
