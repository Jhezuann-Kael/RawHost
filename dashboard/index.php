<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/lang_loader.php';
require_once '../api/config.php';
require_once '../repositories/UserRepository.php';
require_once '../repositories/VpsRepository.php';
require_once '../repositories/DomainRepository.php';
require_once '../repositories/TicketRepository.php';
require_once '../repositories/OrderRepository.php';

$userId = (int) $_SESSION['user_id'];

$_userRepo   = new UserRepository();
$_vpsRepo    = new VpsRepository();
$_domainRepo = new DomainRepository();
$_ticketRepo = new TicketRepository();
$_orderRepo  = new OrderRepository();

$_user        = $_userRepo->getById($userId);
$_balance     = (float) ($_user['balance'] ?? 0);
$_username    = $_user['username'] ?? '';
$_vpsCount    = count($_vpsRepo->getByUserId($userId));
$_domainCount = count($_domainRepo->getByUserId($userId));
$_ticketCount = count($_ticketRepo->getByUserId($userId));

$_allOrders    = $_orderRepo->getByUser($userId);
$_pendingCount = count(array_filter($_allOrders, function ($o) {
    $s = strtoupper($o['status'] ?? '');
    return $s !== 'COMPLETED' && $s !== 'FAILED' && $s !== 'REJECTED';
}));

$_isNewUser      = $_balance <= 0 && $_vpsCount === 0 && $_domainCount === 0 && $_pendingCount === 0;
$_hasTelegram    = !empty($_user['telegram_id']);
$_showTgPrompt   = $_isNewUser && !$_hasTelegram;

$pageTitle = $lang['dash_general_title'] . ' - ' . SITE_NAME;
$extraHead = '
    <link rel="stylesheet" href="css/dashboard.min.css?v=' . filemtime(__DIR__ . '/css/dashboard.min.css') . '">
';
include 'header.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="header">
        <div style="display: flex; align-items: center;">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i
                    class="fas fa-bars"></i></button>
            <h1 style="margin: 0;"><?php echo $lang['dash_general_title']; ?></h1>
        </div>
    </div>

    <!-- Initial Loading Screen -->
    <div id="initialLoading" class="empty-state" style="padding: 80px 20px;">
        <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;"></i>
        <p style="font-size: 1.1rem;"><?php echo $lang['dash_loading']; ?></p>
    </div>

    <!-- Global News Section (Visible for both new and active users) -->
    <div id="globalNewsSection" class="content-section" style="display: none; margin-bottom: 20px;">
        <h2>
            <span><i class="fas fa-newspaper"></i> <?php echo $lang['news_title']; ?></span>
            <a href="news"><?php echo $lang['dash_view_all']; ?> <i class="fas fa-arrow-right"></i></a>
        </h2>
        <div id="newsListHome">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>
                <p><?php echo $lang['news_loading']; ?></p>
            </div>
        </div>
    </div>

    <!-- Welcome Screen for New Users -->
    <div id="welcomeScreen" class="welcome-screen" style="display: none;">

        <?php if ($_showTgPrompt): ?>
        <div id="tgPromptBanner" style="display:flex;align-items:center;gap:14px;background:rgba(0,136,204,0.12);border:1px solid rgba(0,136,204,0.35);border-radius:12px;padding:14px 18px;margin-bottom:22px;">
            <i class="fab fa-telegram" style="font-size:1.6rem;color:#2aabee;flex-shrink:0;"></i>
            <div style="flex:1;">
                <div style="font-weight:600;font-size:.95rem;margin-bottom:2px;"><?php echo $lang['dash_tg_prompt_title'] ?? 'Conecta tu Telegram'; ?></div>
                <div style="font-size:.82rem;color:var(--text-muted);"><?php echo $lang['dash_tg_prompt_desc'] ?? 'Recibe alertas de expiración y notificaciones directamente en Telegram.'; ?></div>
            </div>
            <div style="flex-shrink:0;line-height:1;">
                <script async src="https://telegram.org/js/telegram-widget.js?22"
                    data-telegram-login="<?php echo BOT_USERNAME; ?>"
                    data-size="medium"
                    data-lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>"
                    data-auth-url="/api/auth/telegram_link"
                    data-request-access="write">
                </script>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="welcome-hero">
            <p class="welcome-greeting" id="welcomeGreeting"><?php echo $lang['dash_welcome_title']; ?></p>
            <h1 class="welcome-title"><?php echo $lang['dash_welcome_sub']; ?></h1>
        </div>

        <!-- Action cards -->
        <div class="action-cards">
            <a href="orders/pay_vps" class="action-card">
                <div class="action-card-icon-wrap">
                    <i class="fas fa-server"></i>
                </div>
                <div class="action-card-body">
                    <div class="action-card-title"><?php echo $lang['dash_act_buy_vps']; ?></div>
                    <div class="action-card-desc"><?php echo $lang['dash_act_buy_vps_desc']; ?></div>
                </div>
                <div class="action-card-cta"><?php echo $lang['dash_act_vps_cta']; ?> <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="domains" class="action-card">
                <div class="action-card-icon-wrap">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="action-card-body">
                    <div class="action-card-title"><?php echo $lang['dash_act_buy_domain']; ?></div>
                    <div class="action-card-desc"><?php echo $lang['dash_act_buy_domain_desc']; ?></div>
                </div>
                <div class="action-card-cta"><?php echo $lang['dash_act_domain_cta']; ?> <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="transactions" class="action-card">
                <div class="action-card-icon-wrap">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="action-card-body">
                    <div class="action-card-title"><?php echo $lang['dash_act_recharge']; ?></div>
                    <div class="action-card-desc"><?php echo $lang['dash_act_recharge_desc']; ?></div>
                </div>
                <div class="action-card-cta"><?php echo $lang['dash_act_recharge_cta']; ?> <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>

        <!-- Support banner -->
        <a href="support" class="action-card-support">
            <div class="action-card-support-icon">
                <i class="fas fa-headset"></i>
            </div>
            <div class="action-card-support-body">
                <div class="action-card-title"><?php echo $lang['dash_act_support']; ?></div>
                <div class="action-card-desc"><?php echo $lang['dash_act_support_desc']; ?></div>
            </div>
            <div class="action-card-support-cta">
                <?php echo $lang['dash_act_support_cta']; ?> <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <p class="welcome-note"><i class="fas fa-info-circle"></i> <?php echo $lang['dash_welcome_note']; ?></p>
    </div>

    <!-- VPS Expiry Warning Banner -->
    <div id="vpsExpiryWarning" style="display:none; background:#854d0e; border:1px solid #ca8a04; border-radius:10px; padding:12px 18px; margin-bottom:18px; display:none;">
        <div style="display:flex; align-items:flex-start; gap:12px;">
            <i class="fas fa-exclamation-triangle" style="color:#fbbf24; font-size:1.1rem; margin-top:2px; flex-shrink:0;"></i>
            <div id="vpsExpiryText" style="color:#fef08a; font-size:.9rem; line-height:1.5;"></div>
        </div>
    </div>

    <!-- Dashboard Content (Hidden initially) -->
    <div id="dashboardContent" class="dashboard-content" style="display: none;">

        <!-- Stats Overview -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon icon-balance">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $lang['dash_stat_balance']; ?></h3>
                    <div class="value" id="totalBalance">$<?php echo number_format($_balance, 2); ?></div>
                    <div class="label"><?php echo $lang['dash_stat_balance_label']; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-vps">
                    <i class="fas fa-server"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $lang['dash_stat_vps']; ?></h3>
                    <div class="value" id="totalVPS"><?php echo $_vpsCount; ?></div>
                    <div class="label"><?php echo $lang['dash_stat_vps_label']; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-domains">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $lang['dash_stat_domains']; ?></h3>
                    <div class="value" id="totalDomains"><?php echo $_domainCount; ?></div>
                    <div class="label"><?php echo $lang['dash_stat_domains_label']; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-tickets">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $lang['dash_stat_tickets']; ?></h3>
                    <div class="value" id="totalTickets"><?php echo $_ticketCount; ?></div>
                    <div class="label"><?php echo $lang['dash_stat_tickets_label']; ?></div>
                </div>
            </div>
        </div>

        <!-- VPS List Section -->
        <div class="content-section">
            <h2>
                <span><i class="fas fa-server"></i> <?php echo $lang['dash_sec_servers']; ?></span>
                <a href="vps"><?php echo $lang['dash_view_all']; ?> <i class="fas fa-arrow-right"></i></a>
            </h2>
            <div id="vpsList">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>
                    <p><?php echo $lang['dash_loading_servers']; ?></p>
                </div>
            </div>
        </div>

        <!-- Two Column Grid: Transactions + Quick Ticket -->
        <div class="two-column-grid">
            <!-- Transactions Section -->
            <div class="content-section" style="margin-bottom: 0;">
                <h2>
                    <span><i class="fas fa-exchange-alt"></i> <?php echo $lang['dash_menu_transactions']; ?></span>
                    <a href="transactions"><?php echo $lang['dash_view_all']; ?> <i class="fas fa-arrow-right"></i></a>
                </h2>
                <div id="transactionsList">
                    <div class="empty-state">
                        <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>
                        <p><?php echo $lang['dash_loading_orders']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Ticket Section (dynamic) -->
            <div id="ticketSection" style="margin-bottom:0; display:flex; flex-direction:column; gap:10px;">
                <div class="content-section" style="margin-bottom:0;">
                    <div style="text-align:center; padding:20px; color:var(--text-muted);">
                        <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Close dashboardContent -->

    <!-- Global News Section (Visible for both new and active users) -->
</main>

<script>
    // Inject Translations
    const LANG = <?php echo json_encode([
        'status_active' => $lang['status_active'],
        'status_provisioning' => $lang['status_provisioning'],
        'status_inactive' => $lang['status_inactive'],
        'status_pending' => $lang['status_pending'],
        'status_completed' => $lang['status_completed'],
        'status_failed' => $lang['status_failed'],
        'status_open' => $lang['status_open'],
        'status_in_progress' => $lang['status_in_progress'],
        'status_answered' => $lang['status_answered'],
        'status_closed' => $lang['status_closed'],
        'txt_order' => $lang['txt_order'],
        'txt_ticket' => $lang['txt_ticket'],
        'txt_date_format' => $lang['txt_date_format'],
        'dash_loading_servers' => $lang['dash_loading_servers'],
        'dash_err_servers' => $lang['dash_err_servers'],
        'dash_no_servers' => $lang['dash_no_servers'],
        'dash_btn_create_server' => $lang['dash_btn_create_server'],
        'dash_loading_orders' => $lang['dash_loading_orders'],
        'dash_err_orders' => $lang['dash_err_orders'],
        'dash_no_orders' => $lang['dash_no_orders'],
        'dash_loading_tickets' => $lang['dash_loading_tickets'],
        'dash_err_tickets' => $lang['dash_err_tickets'],
        'dash_no_tickets' => $lang['dash_no_tickets'],
        'dash_btn_create_ticket' => $lang['dash_btn_create_ticket'],
        'dash_err_loading' => $lang['dash_err_loading'],
        'news_loading' => $lang['news_loading'],
        'news_no_items' => $lang['news_no_items'],
    ]); ?>;
</script>
<script>
window.INIT_DATA = <?php echo json_encode([
    'balance'      => $_balance,
    'username'     => $_username,
    'vpsCount'     => $_vpsCount,
    'domainCount'  => $_domainCount,
    'ticketCount'  => $_ticketCount,
    'pendingCount' => $_pendingCount,
    'isNewUser'    => $_isNewUser,
]); ?>;
</script>
<script src="js/dashboard.min.js?v=<?php echo filemtime(__DIR__ . '/js/dashboard.min.js'); ?>"></script>
<script>
(function () {
    const _orig = window.loadFullDashboard;
    window.loadFullDashboard = async function () {
        await _orig();
        if (typeof loadTicketSection === 'function') loadTicketSection();
    };
})();
</script>
<script>
// ── Ticket section ────────────────────────────────────────────────────────────
const _qtLang = {
    isEs:        <?php echo json_encode($currentLang === 'es'); ?>,
    catTech:     <?php echo json_encode($lang['supp_cat_tech']); ?>,
    catBill:     <?php echo json_encode($lang['supp_cat_bill']); ?>,
    catOther:    <?php echo json_encode($lang['supp_cat_other']); ?>,
    viewAll:     <?php echo json_encode($lang['dash_view_all']); ?>,
    secTickets:  <?php echo json_encode($lang['dash_sec_tickets']); ?>,
};

const _inputStyle  = 'width:100%;padding:9px 12px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:white;font-size:0.88rem;box-sizing:border-box;';
const _statusColor = { OPEN:'#6366f1', ANSWERED:'#10b981', IN_PROGRESS:'#f59e0b', CLOSED:'#64748b' };

function _qtEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function _newTicketTabHtml() {
    const ph = _qtLang.isEs;
    return `
        <form id="quickTicketForm" style="display:flex;flex-direction:column;gap:9px;">
            <input type="text" id="qtSubject" name="subject" required
                placeholder="${ph ? 'Asunto...' : 'Subject...'}" style="${_inputStyle}">
            <select id="qtCategory" name="category" style="${_inputStyle} cursor:pointer;">
                <option value="TECHNICAL">${_qtEsc(_qtLang.catTech)}</option>
                <option value="BILLING">${_qtEsc(_qtLang.catBill)}</option>
                <option value="OTHER" selected>${_qtEsc(_qtLang.catOther)}</option>
            </select>
            <textarea id="qtMessage" name="message" rows="4" required
                placeholder="${ph ? 'Describe tu problema...' : 'Describe your issue...'}"
                style="${_inputStyle} resize:vertical;font-family:inherit;"></textarea>
            <div id="quickTicketMsg" role="alert" style="display:none;padding:9px 12px;border-radius:8px;font-size:0.83rem;"></div>
            <button type="submit" id="qtBtn" class="btn btn-manage" style="align-self:flex-start;">
                <i class="fas fa-paper-plane"></i> ${ph ? 'Enviar ticket' : 'Send ticket'}
            </button>
        </form>`;
}

function _chatBubble(msg, isAdmin) {
    const align  = isAdmin ? 'flex-start' : 'flex-end';
    const bg     = isAdmin
        ? 'rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.22)'
        : 'rgba(99,102,241,0.18);border:1px solid rgba(99,102,241,0.28)';
    const radius = isAdmin ? '4px 12px 12px 12px' : '12px 4px 12px 12px';
    const adminName = msg.display_name || msg.username || '';
    const who    = isAdmin
        ? `<div style="font-size:0.68rem;color:#10b981;font-weight:700;margin-bottom:3px;"><i class="fas fa-headset"></i> Support${adminName ? ' — ' + _qtEsc(adminName) : ''}</div>`
        : '';
    const imgHtml = msg.image_path
        ? `<img src="/${_qtEsc(msg.image_path)}" style="max-width:100%;border-radius:6px;margin-top:6px;" loading="lazy">`
        : '';
    const time = msg.created_at ? new Date(msg.created_at).toLocaleString([], {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}) : '';
    return `
    <div style="display:flex;justify-content:${align};">
        <div style="max-width:85%;background:${bg};border-radius:${radius};padding:8px 11px;font-size:0.83rem;line-height:1.45;">
            ${who}
            <div style="color:var(--text-light);">${_qtEsc(msg.message)}${imgHtml}</div>
            <div style="font-size:0.68rem;color:var(--text-muted);margin-top:4px;text-align:right;">${time}</div>
        </div>
    </div>`;
}

function _renderTabPanel(ticket, messages) {
    const color   = _statusColor[ticket.status] || '#64748b';
    const bubbles = messages.map(m => _chatBubble(m, m.is_superuser == 1)).join('');
    const label   = _qtEsc(ticket.subject.length > 28 ? ticket.subject.slice(0,28)+'…' : ticket.subject);
    const ph      = _qtLang.isEs;
    return `
    <div class="content-section" style="margin-bottom:0;">
        <!-- Tab bar -->
        <div style="display:flex;align-items:center;gap:0;margin:-15px -15px 14px;border-bottom:1px solid rgba(255,255,255,0.08);">
            <button id="qt-tab-chat-btn" onclick="qtTab('chat')"
                style="flex:1;padding:10px 14px;background:rgba(99,102,241,0.12);border:none;border-bottom:2px solid var(--primary);color:var(--text-light);font-size:0.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:background 0.15s;">
                <i class="fas fa-comments" style="color:var(--primary);"></i>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${label}</span>
                <span style="background:${color}22;color:${color};border:1px solid ${color}44;border-radius:20px;padding:0 7px;font-size:0.65rem;font-weight:700;white-space:nowrap;">${ticket.status}</span>
            </button>
            <button id="qt-tab-new-btn" onclick="qtTab('new')"
                style="flex:1;padding:10px 14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--text-muted);font-size:0.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all 0.15s;">
                <i class="fas fa-plus-circle"></i> ${ph ? 'Nuevo ticket' : 'New ticket'}
            </button>
        </div>

        <!-- Chat tab -->
        <div id="qt-tab-chat">
            <div id="qt-messages" style="overflow-y:auto;display:flex;flex-direction:column;gap:7px;max-height:230px;padding:4px 0 10px;">
                ${bubbles || `<div style="text-align:center;color:var(--text-muted);font-size:0.82rem;padding:20px 0;">${ph ? 'Sin mensajes aún.' : 'No messages yet.'}</div>`}
            </div>
            <div style="margin-top:8px;display:flex;flex-direction:column;gap:7px;border-top:1px solid rgba(255,255,255,0.06);padding-top:10px;">
                <textarea id="qt-reply-text" rows="2"
                    placeholder="${ph ? 'Escribe tu respuesta...' : 'Write your reply...'}"
                    style="${_inputStyle} resize:none;font-family:inherit;"></textarea>
                <div id="qt-reply-msg" style="display:none;font-size:0.8rem;"></div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button id="qt-reply-btn" data-ticket-id="${ticket.id}" onclick="sendQuickReply(this.dataset.ticketId)" class="btn btn-manage">
                        <i class="fas fa-reply"></i> ${ph ? 'Responder' : 'Reply'}
                    </button>
                    <a href="support/view?id=${ticket.id}" style="font-size:0.78rem;color:var(--primary);">
                        ${_qtEsc(_qtLang.viewAll)} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- New ticket tab -->
        <div id="qt-tab-new" style="display:none;">
            ${_newTicketTabHtml()}
        </div>
    </div>`;
}

function qtTab(name) {
    const chatPane = document.getElementById('qt-tab-chat');
    const newPane  = document.getElementById('qt-tab-new');
    const chatBtn  = document.getElementById('qt-tab-chat-btn');
    const newBtn   = document.getElementById('qt-tab-new-btn');
    const active   = 'rgba(99,102,241,0.12)';
    const inactBg  = 'none';
    const actBorder= '2px solid var(--primary)';
    const inactBorder = '2px solid transparent';
    if (name === 'chat') {
        chatPane.style.display = 'flex'; chatPane.style.flexDirection = 'column';
        newPane.style.display  = 'none';
        chatBtn.style.background    = active;    chatBtn.style.borderBottom = actBorder;  chatBtn.style.color = 'var(--text-light)';
        newBtn.style.background     = inactBg;   newBtn.style.borderBottom  = inactBorder; newBtn.style.color = 'var(--text-muted)';
    } else {
        chatPane.style.display = 'none';
        newPane.style.display  = 'block';
        chatBtn.style.background    = inactBg;   chatBtn.style.borderBottom = inactBorder; chatBtn.style.color = 'var(--text-muted)';
        newBtn.style.background     = active;    newBtn.style.borderBottom  = actBorder;  newBtn.style.color = 'var(--text-light)';
    }
}

async function loadTicketSection() {
    const section = document.getElementById('ticketSection');
    if (!section) return;

    // Reset to single column flex
    section.style.flexDirection = 'column';
    section.style.alignItems    = '';
    section.style.gap           = '';

    try {
        const res  = await fetch('../api/support/list');
        const data = await res.json();
        if (data.status !== 'success') throw new Error(data.message);

        const active = (data.tickets || []).find(t => t.status !== 'CLOSED');

        if (!active) {
            // No active ticket — just create form
            section.innerHTML = `
            <div class="content-section" style="margin-bottom:0;">
                <h2>
                    <span><i class="fas fa-headset"></i> ${_qtEsc(_qtLang.secTickets)}</span>
                    <a href="support">${_qtEsc(_qtLang.viewAll)} <i class="fas fa-arrow-right"></i></a>
                </h2>
                ${_newTicketTabHtml()}
            </div>`;
            _attachCreateForm();
            return;
        }

        // Fetch messages for the active ticket
        const res2 = await fetch(`../api/support/get?id=${active.id}`);
        const data2 = await res2.json();
        const msgs  = data2.status === 'success' ? (data2.messages || []) : [];

        section.innerHTML = _renderTabPanel(active, msgs);

        // Scroll chat to bottom
        const msgBox = document.getElementById('qt-messages');
        if (msgBox) msgBox.scrollTop = msgBox.scrollHeight;

        _attachCreateForm();
    } catch (e) {
        section.innerHTML = `<div class="content-section" style="margin-bottom:0;color:#ef4444;font-size:0.85rem;"><i class="fas fa-exclamation-circle"></i> ${_qtEsc(e.message)}</div>`;
    }
}

async function sendQuickReply(ticketId) {
    ticketId = parseInt(ticketId, 10);
    const btn  = document.getElementById('qt-reply-btn');
    const text = document.getElementById('qt-reply-text');
    const msg  = document.getElementById('qt-reply-msg');
    const body = text.value.trim();
    if (!body) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    msg.style.display = 'none';

    try {
        const fd = new FormData();
        fd.append('ticket_id', ticketId);
        fd.append('message', body);
        const res  = await fetch('../api/support/reply', { method:'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') {
            text.value = '';
            // Append bubble optimistically
            const msgBox = document.getElementById('qt-messages');
            if (msgBox) {
                const div = document.createElement('div');
                div.innerHTML = _chatBubble({ message: body, created_at: new Date().toISOString(), is_admin: 0 }, false);
                msgBox.appendChild(div.firstElementChild);
                msgBox.scrollTop = msgBox.scrollHeight;
            }
        } else {
            msg.style.display = 'block';
            msg.style.color   = '#ef4444';
            msg.textContent   = data.message || 'Error';
        }
    } catch (e) {
        msg.style.display = 'block';
        msg.style.color   = '#ef4444';
        msg.textContent   = _qtLang.isEs ? 'Error de conexión.' : 'Connection error.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-reply"></i> ${_qtLang.isEs ? 'Responder' : 'Reply'}`;
    }
}

function _attachCreateForm() {
    const form = document.getElementById('quickTicketForm');
    if (!form) return;
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn  = document.getElementById('qtBtn');
        const msg  = document.getElementById('quickTicketMsg');
        const subject  = document.getElementById('qtSubject').value.trim();
        const category = document.getElementById('qtCategory').value;
        const message  = document.getElementById('qtMessage').value.trim();
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        msg.style.display = 'none';
        try {
            const res  = await fetch('../api/support/create', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ subject, message, category, priority: 'LOW' }),
            });
            const data = await res.json();
            msg.style.display = 'block';
            if (data.status === 'success') {
                msg.style.background  = 'rgba(46,213,115,0.15)';
                msg.style.borderLeft  = '3px solid #2ed573';
                msg.style.color       = '#2ed573';
                msg.innerHTML = `<i class="fas fa-check-circle"></i> ${_qtLang.isEs ? 'Ticket creado.' : 'Ticket created.'} <a href="support/view?id=${data.ticket_id}" style="color:#2ed573;font-weight:600;">${_qtLang.isEs ? 'Ver' : 'View'} →</a>`;
                form.reset();
                // Reload section to show the new ticket chat
                setTimeout(loadTicketSection, 800);
            } else {
                msg.style.background = 'rgba(239,68,68,0.15)';
                msg.style.borderLeft = '3px solid #ef4444';
                msg.style.color      = '#ef4444';
                msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Error');
            }
        } catch (err) {
            msg.style.display    = 'block';
            msg.style.background = 'rgba(239,68,68,0.15)';
            msg.style.borderLeft = '3px solid #ef4444';
            msg.style.color      = '#ef4444';
            msg.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${_qtLang.isEs ? 'Error de conexión.' : 'Connection error.'}`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-paper-plane"></i> ${_qtLang.isEs ? 'Enviar ticket' : 'Send ticket'}`;
        }
    });
}

// Called from dashboard.js after content is shown
window._loadTicketSection = loadTicketSection;
</script>
<?php include 'footer.php'; ?>