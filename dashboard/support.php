<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../api/config.php';
require_once '../includes/lang_loader.php';
$pageTitle = $lang['supp_title'] . ' - ' . SITE_NAME;
$extraHead = <<<HTML
<style>
    /* ── Live badge ── */
    .support-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.72rem;
        font-weight: 500;
        color: #4ade80;
        background: rgba(34,197,94,0.08);
        border: 1px solid rgba(34,197,94,0.2);
        padding: 4px 10px;
        border-radius: 20px;
        margin-top: 10px;
        user-select: none;
    }

    .support-live-dot {
        width: 7px;
        height: 7px;
        background: #4ade80;
        border-radius: 50%;
        flex-shrink: 0;
        animation: live-pulse 2.2s ease-in-out infinite;
    }

    @keyframes live-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(74,222,128,0.5); }
        50%       { box-shadow: 0 0 0 5px rgba(74,222,128,0); }
    }

    /* ── Tickets list ── */
    .tickets-list {
        margin-top: 16px;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 10px;
        overflow: hidden;
    }

    .tickets-list-header {
        display: grid;
        grid-template-columns: 1fr 130px 96px 100px 28px;
        padding: 9px 16px 9px 20px;
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .ticket-row {
        display: grid;
        grid-template-columns: 1fr 130px 96px 100px 28px;
        padding: 12px 16px 12px 0;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        border-left: 3px solid transparent;
        cursor: pointer;
        transition: background 0.12s, border-left-color 0.15s;
        align-items: center;
        text-decoration: none;
        color: inherit;
        position: relative;
    }

    .ticket-row:last-child { border-bottom: none; }

    /* Status-tinted left border on hover */
    .ticket-row[data-status="OPEN"]:hover        { background: rgba(74,222,128,0.03);  border-left-color: #4ade80; }
    .ticket-row[data-status="IN_PROGRESS"]:hover { background: rgba(96,165,250,0.03);  border-left-color: #60a5fa; }
    .ticket-row[data-status="ANSWERED"]:hover    { background: rgba(251,191,36,0.03);  border-left-color: #fbbf24; }
    .ticket-row[data-status="CLOSED"]:hover      { background: rgba(255,255,255,0.025); border-left-color: rgba(255,255,255,0.18); }

    /* Unread rows */
    .ticket-row.t-has-unread { background: rgba(255,255,255,0.015); }
    .ticket-row.t-has-unread .t-subject-text { color: #fff; font-weight: 600; }

    .t-subject-cell {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        padding-left: 17px;
    }

    .t-prio-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .t-subject-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .t-unread {
        flex-shrink: 0;
        background: #ef4444;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        border-radius: 10px;
        padding: 1px 5px;
        line-height: 1.4;
    }

    .t-cat {
        font-size: 0.78rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .t-status {
        display: flex;
        align-items: center;
    }

    .t-badge {
        font-size: 0.68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 3px 8px;
        border-radius: 4px;
        white-space: nowrap;
    }

    .t-badge-OPEN        { background: rgba(34,197,94,0.12);   color: #4ade80; }
    .t-badge-IN_PROGRESS { background: rgba(59,130,246,0.12);  color: #60a5fa; }
    .t-badge-ANSWERED    { background: rgba(234,179,8,0.12);   color: #fbbf24; }
    .t-badge-CLOSED      { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.35); }

    .t-date {
        font-size: 0.75rem;
        color: var(--text-muted);
        white-space: nowrap;
    }

    /* Arrow hint on hover */
    .t-arrow {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.15);
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity 0.12s, transform 0.12s;
    }

    .ticket-row:hover .t-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    /* ── Header entry buttons ── */
    .support-entry-btns {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-report {
        background: var(--primary);
        color: #fff;
        border: 1px solid transparent;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background 0.12s;
        text-decoration: none;
    }

    .btn-report:hover { background: #0052cc; }

    .btn-idea {
        background: rgba(251,191,36,0.08);
        color: #fbbf24;
        border: 1px solid rgba(251,191,36,0.22);
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background 0.12s, border-color 0.12s;
        text-decoration: none;
    }

    .btn-idea:hover {
        background: rgba(251,191,36,0.13);
        border-color: rgba(251,191,36,0.4);
    }

    /* ── Priority radio cards ── */
    .prio-cards {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .prio-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.12s, border-color 0.12s;
        user-select: none;
    }

    .prio-card:hover {
        background: rgba(255,255,255,0.035);
    }

    .prio-card input[type="radio"] { display: none; }

    .prio-card.prio-selected {
        border-color: rgba(255,255,255,0.18);
        background: rgba(255,255,255,0.04);
    }

    .prio-card-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .prio-card-body { flex: 1; min-width: 0; }

    .prio-card-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #e2e8f0;
    }

    .prio-card-desc {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.38);
        margin-top: 1px;
    }

    .prio-card-check {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.5);
        opacity: 0;
        transition: opacity 0.1s;
    }

    .prio-card.prio-selected .prio-card-check { opacity: 1; }

    /* ── Responsive ── */
    @media (max-width: 640px) {
        .tickets-list-header { display: none; }
        .ticket-row {
            grid-template-columns: 1fr auto;
            grid-template-rows: auto auto;
            gap: 3px 8px;
            padding: 12px 14px 12px 0;
        }
        .t-subject-cell { grid-column: 1; grid-row: 1; }
        .t-cat          { display: none; }
        .t-status       { grid-column: 2; grid-row: 1; align-self: start; }
        .t-date         { grid-column: 2; grid-row: 2; text-align: right; }
        .t-arrow        { display: none; }
    }
</style>
HTML;
include 'header.php';
?>

<main class="main-content">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i class="fas fa-bars"></i></button>
                <h1 style="margin: 0;"><?php echo $lang['supp_header']; ?></h1>
            </div>
            <div class="support-entry-btns">
                <button class="btn-idea" onclick="openSupportModal('idea')">
                    <i class="fas fa-lightbulb"></i> <?php echo $lang['supp_btn_idea']; ?>
                </button>
                <button class="btn-report" onclick="openSupportModal('problem')">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $lang['supp_btn_report']; ?>
                </button>
            </div>
        </div>
        <div style="color: var(--text-muted); margin-top: 5px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <?php echo $lang['supp_sub_header']; ?>
            <span class="support-live-badge">
                <span class="support-live-dot"></span>
                <?php echo $lang['supp_live_badge']; ?>
            </span>
        </div>
    </div>

    <!-- Active Tickets Section -->
    <div class="section-title"><i class="fas fa-ticket-alt"
            style="margin-right:8px; color:var(--primary);"></i><?php echo $lang['supp_sec_tickets']; ?></div>

    <div id="ticketsContainer">
        <!-- Loaded via JS -->
    </div>
</main>

<!-- Create Ticket Modal -->
<div class="modal-overlay" id="createTicketModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i id="modalTitleIcon" class="fas fa-headset"
                    style="margin-right:10px; color:var(--primary);"></i><span id="modalTitleText"><?php echo $lang['supp_modal_title']; ?></span></h2>
            <button class="btn-close" onclick="closeSupportModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="createTicketForm" onsubmit="createTicket(event)">
                <div class="form-group">
                    <label><?php echo $lang['supp_label_subject']; ?> <span
                            style="color:var(--text-muted); font-size:0.8rem;"><?php echo $lang['supp_help_subject']; ?></span></label>
                    <input type="text" id="subject" class="form-control"
                        placeholder="<?php echo $lang['supp_label_subject_ph']; ?>" required>
                </div>

                <div id="catPrioGrid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group" id="catFormGroup">
                        <label><?php echo $lang['supp_label_category']; ?></label>
                        <select id="category" class="form-control">
                            <option value="TECHNICAL"><?php echo $lang['supp_cat_tech']; ?></option>
                            <option value="BILLING"><?php echo $lang['supp_cat_bill']; ?></option>
                            <option value="RECOMMENDATIONS"><?php echo $lang['supp_cat_rec']; ?></option>
                            <option value="SERVICE_REQUEST"><?php echo $lang['supp_cat_service']; ?></option>
                            <option value="OTHER"><?php echo $lang['supp_cat_other']; ?></option>
                        </select>
                    </div>

                    <div class="form-group" id="prioFormGroup">
                        <label><?php echo $lang['supp_label_priority']; ?></label>
                        <div id="prioIdeaInfo" style="display:none;"></div>
                        <div class="prio-cards" id="priorityCards">
                            <label class="prio-card" data-value="LOW" onclick="selectPrio(this)">
                                <input type="radio" name="priority" value="LOW">
                                <span class="prio-card-dot" style="background:#4ade80;"></span>
                                <div class="prio-card-body">
                                    <div class="prio-card-title"><?php echo $lang['supp_prio_low']; ?></div>
                                    <div class="prio-card-desc"><?php echo $lang['supp_prio_low_desc']; ?></div>
                                </div>
                                <i class="fas fa-check prio-card-check"></i>
                            </label>
                            <label class="prio-card prio-selected" data-value="MEDIUM" onclick="selectPrio(this)">
                                <input type="radio" name="priority" value="MEDIUM" checked>
                                <span class="prio-card-dot" style="background:#f59e0b;"></span>
                                <div class="prio-card-body">
                                    <div class="prio-card-title"><?php echo $lang['supp_prio_med']; ?></div>
                                    <div class="prio-card-desc"><?php echo $lang['supp_prio_med_desc']; ?></div>
                                </div>
                                <i class="fas fa-check prio-card-check"></i>
                            </label>
                            <label class="prio-card" data-value="HIGH" onclick="selectPrio(this)">
                                <input type="radio" name="priority" value="HIGH">
                                <span class="prio-card-dot" style="background:#ef4444;"></span>
                                <div class="prio-card-body">
                                    <div class="prio-card-title"><?php echo $lang['supp_prio_high']; ?></div>
                                    <div class="prio-card-desc"><?php echo $lang['supp_prio_high_desc']; ?></div>
                                </div>
                                <i class="fas fa-check prio-card-check"></i>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label id="msgLabel"><?php echo $lang['supp_label_msg']; ?></label>
                    <textarea id="message" class="form-control" rows="6"
                        placeholder="<?php echo $lang['supp_label_msg_ph']; ?>" required
                        style="resize: vertical;"></textarea>
                </div>

                <div id="createTicketError"
                    style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px; margin-top: 15px; display: none; font-size: 0.9rem;">
                </div>

                <div class="modal-footer" style="padding-top: 15px;">
                    <button type="button" class="btn btn-power"
                        onclick="closeSupportModal()"><?php echo $lang['supp_btn_cancel']; ?></button>
                    <button type="submit" class="btn btn-manage" id="btnSubmitTicket" style="min-width: 150px;">
                        <i class="fas fa-paper-plane"></i> <?php echo $lang['supp_btn_submit']; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const LANG_SUPP = {
        cat_tech: '<?php echo $lang['supp_cat_tech']; ?>',
        cat_bill: '<?php echo $lang['supp_cat_bill']; ?>',
        cat_rec: '<?php echo $lang['supp_cat_rec']; ?>',
        cat_service: '<?php echo $lang['supp_cat_service']; ?>',
        cat_other: '<?php echo $lang['supp_cat_other']; ?>',
        empty_title: '<?php echo $lang['supp_empty_title']; ?>',
        empty_text: '<?php echo $lang['supp_empty_text']; ?>',
        chip_fast: '<?php echo $lang['supp_chip_fast']; ?>',
        chip_dedicated: '<?php echo $lang['supp_chip_dedicated']; ?>',
        chip_247: '<?php echo $lang['supp_chip_247']; ?>',
        btn_create: '<?php echo $lang['supp_btn_create']; ?>',
        btn_processing: '<?php echo $lang['supp_btn_processing']; ?>',
        btn_submit: '<?php echo $lang['supp_btn_submit']; ?>',
        btn_retry: '<?php echo $lang['supp_btn_retry']; ?>',
        err_load: '<?php echo $lang['supp_err_load']; ?>',
        err_create: '<?php echo $lang['supp_err_create']; ?>',
        err_unknown: '<?php echo $lang['supp_err_unknown']; ?>',
        err_invalid_res: '<?php echo $lang['supp_err_invalid_res']; ?>',
        err_prefix: '<?php echo $lang['supp_err_prefix']; ?>',
        status_open: '<?php echo $lang['status_open']; ?>',
        status_answered: '<?php echo $lang['status_answered']; ?>',
        status_closed: '<?php echo $lang['status_closed']; ?>',
        status_in_progress: '<?php echo $lang['status_in_progress']; ?>',
        col_subject: '<?php echo $lang['supp_col_subject']; ?>',
        col_category: '<?php echo $lang['supp_col_category']; ?>',
        col_status: '<?php echo $lang['supp_col_status']; ?>',
        col_updated: '<?php echo $lang['supp_col_updated']; ?>',
        modal_title_problem: '<?php echo $lang['supp_modal_title_problem']; ?>',
        modal_title_idea: '<?php echo $lang['supp_modal_title_idea']; ?>',
        subject_ph: '<?php echo addslashes($lang['supp_label_subject_ph']); ?>',
        subject_ph_idea: '<?php echo addslashes($lang['supp_label_subject_ph_idea']); ?>',
        msg_label: '<?php echo addslashes($lang['supp_label_msg']); ?>',
        msg_label_idea: '<?php echo addslashes($lang['supp_label_msg_idea']); ?>',
        msg_ph: '<?php echo addslashes($lang['supp_label_msg_ph']); ?>',
        msg_ph_idea: '<?php echo addslashes($lang['supp_label_msg_ph_idea']); ?>'
    };

    // Category Translation
    function translateCategory(category) {
        const translations = {
            'TECHNICAL': LANG_SUPP.cat_tech,
            'BILLING': LANG_SUPP.cat_bill,
            'RECOMMENDATIONS': LANG_SUPP.cat_rec,
            'SERVICE_REQUEST': LANG_SUPP.cat_service,
            'OTHER': LANG_SUPP.cat_other
        };
        return translations[category] || category;
    }

    // Status Translation
    function translateStatus(status) {
        const translations = {
            'OPEN':        LANG_SUPP.status_open,
            'IN_PROGRESS': LANG_SUPP.status_in_progress,
            'ANSWERED':    LANG_SUPP.status_answered,
            'CLOSED':      LANG_SUPP.status_closed
        };
        return translations[status] || status;
    }

    // Modal Logic
    const modal = document.getElementById('createTicketModal');

    function openSupportModal(type) {
        document.getElementById('createTicketError').style.display = 'none';
        document.getElementById('subject').value = '';
        document.getElementById('message').value = '';

        const titleEl      = document.getElementById('modalTitleText');
        const iconEl       = document.getElementById('modalTitleIcon');
        const catPrioGrid = document.getElementById('catPrioGrid');
        const prioGroup   = document.getElementById('prioFormGroup');
        const prioCards   = document.getElementById('priorityCards');
        const catGroup    = document.getElementById('catFormGroup');
        const catSelect   = document.getElementById('category');
        const subjectEl   = document.getElementById('subject');
        const messageEl   = document.getElementById('message');
        const msgLabel    = document.getElementById('msgLabel');

        if (type === 'idea') {
            titleEl.textContent        = LANG_SUPP.modal_title_idea;
            iconEl.className           = 'fas fa-lightbulb';
            iconEl.style.color         = '#fbbf24';
            catPrioGrid.style.display  = 'none';
            document.querySelector('input[name="priority"][value="LOW"]').checked = true;
            catSelect.value            = 'RECOMMENDATIONS';
            subjectEl.placeholder      = LANG_SUPP.subject_ph_idea;
            msgLabel.textContent       = LANG_SUPP.msg_label_idea;
            messageEl.placeholder      = LANG_SUPP.msg_ph_idea;
        } else {
            titleEl.textContent        = LANG_SUPP.modal_title_problem;
            iconEl.className           = 'fas fa-headset';
            iconEl.style.color         = 'var(--primary)';
            catPrioGrid.style.display  = 'grid';
            prioGroup.style.display    = '';
            prioCards.style.display    = '';
            catGroup.style.display     = '';
            document.querySelector('input[name="priority"][value="MEDIUM"]').checked = true;
            document.querySelectorAll('.prio-card').forEach(c => c.classList.remove('prio-selected'));
            document.querySelector('.prio-card[data-value="MEDIUM"]').classList.add('prio-selected');
            catSelect.value            = 'TECHNICAL';
            subjectEl.placeholder      = LANG_SUPP.subject_ph;
            msgLabel.textContent       = LANG_SUPP.msg_label;
            messageEl.placeholder      = LANG_SUPP.msg_ph;
        }

        modal.classList.add('active');
    }

    function selectPrio(card) {
        document.querySelectorAll('.prio-card').forEach(c => c.classList.remove('prio-selected'));
        card.classList.add('prio-selected');
        card.querySelector('input[type="radio"]').checked = true;
    }

    function closeSupportModal() {
        modal.classList.remove('active');
        document.getElementById('createTicketForm').reset();
        document.getElementById('createTicketError').style.display = 'none';
    }

    // Relative time helper
    function relativeTime(dateStr) {
        const diff = (Date.now() - new Date(dateStr)) / 1000;
        if (diff < 60)    return '< 1 min';
        if (diff < 3600)  return Math.floor(diff / 60) + ' min';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 172800) return new Intl.DateTimeFormat(undefined, { weekday: 'short' }).format(new Date(dateStr));
        return new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' }).format(new Date(dateStr));
    }

    // Priority dot colour
    const PRIO_COLOR = { HIGH: '#ef4444', MEDIUM: '#f59e0b', LOW: 'rgba(255,255,255,0.18)' };

    // Load Tickets
    async function loadTickets() {
        const container = document.getElementById('ticketsContainer');
        container.innerHTML = '<div style="padding:40px;text-align:center;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i></div>';

        try {
            const res  = await fetch('../api/support/list');
            const data = await res.json();

            if (data.status === 'success') {
                if (data.tickets.length === 0) {
                    container.innerHTML = `
                        <div class="page-empty-hero">
                            <div class="page-empty-glow"></div>
                            <div class="page-empty-icon-wrap">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h2 class="page-empty-title">${LANG_SUPP.empty_title}</h2>
                            <p class="page-empty-sub">${LANG_SUPP.empty_text}</p>
                            <button class="btn btn-manage page-empty-cta"
                                onclick="openSupportModal('problem')">
                                <i class="fas fa-plus-circle"></i> ${LANG_SUPP.btn_create}
                            </button>
                            <div class="page-empty-features">
                                <div class="page-feat-chip"><i class="fas fa-bolt"></i> ${LANG_SUPP.chip_fast}</div>
                                <div class="page-feat-chip"><i class="fas fa-shield-alt"></i> ${LANG_SUPP.chip_dedicated}</div>
                                <div class="page-feat-chip"><i class="fas fa-clock"></i> ${LANG_SUPP.chip_247}</div>
                            </div>
                        </div>`;
                    return;
                }

                const list = document.createElement('div');
                list.className = 'tickets-list';

                list.innerHTML = `
                    <div class="tickets-list-header">
                        <div style="padding-left:17px;">${LANG_SUPP.col_subject ?? 'Subject'}</div>
                        <div>${LANG_SUPP.col_category ?? 'Category'}</div>
                        <div>${LANG_SUPP.col_status ?? 'Status'}</div>
                        <div>${LANG_SUPP.col_updated ?? 'Updated'}</div>
                        <div></div>
                    </div>`;

                data.tickets.forEach(ticket => {
                    const unread = parseInt(ticket.unread_count) || 0;
                    const unreadBadge = unread > 0 ? `<span class="t-unread">${unread}</span>` : '';
                    const prioDot    = `<span class="t-prio-dot" style="background:${PRIO_COLOR[ticket.priority] || PRIO_COLOR.LOW};"></span>`;

                    const row = document.createElement('a');
                    row.className = 'ticket-row';
                    if (unread > 0) row.classList.add('t-has-unread');
                    row.dataset.status = ticket.status;
                    row.href = `view_ticket?id=${ticket.id}`;
                    row.innerHTML = `
                        <div class="t-subject-cell">
                            ${prioDot}
                            <span class="t-subject-text" title="${ticket.subject}">${ticket.subject}</span>
                            ${unreadBadge}
                        </div>
                        <div class="t-cat">${translateCategory(ticket.category)}</div>
                        <div class="t-status"><span class="t-badge t-badge-${ticket.status}">${translateStatus(ticket.status)}</span></div>
                        <div class="t-date">${relativeTime(ticket.updated_at)}</div>
                        <div class="t-arrow"><i class="fas fa-arrow-right"></i></div>`;
                    list.appendChild(row);
                });

                container.innerHTML = '';
                container.appendChild(list);
            } else {
                throw new Error(data.message || LANG_SUPP.err_unknown);
            }
        } catch (e) {
            console.error(e);
            container.innerHTML = `
                <div style="padding:40px;text-align:center;color:#ef4444;background:rgba(239,68,68,0.08);border-radius:10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:1.8rem;margin-bottom:10px;"></i>
                    <p>${LANG_SUPP.err_load}</p>
                    <button class="btn btn-manage" onclick="loadTickets()" style="margin-top:10px;">${LANG_SUPP.btn_retry}</button>
                </div>`;
        }
    }

    // Create Ticket
    async function createTicket(e) {
        e.preventDefault();

        const subject = document.getElementById('subject').value;
        const category = document.getElementById('category').value;
        const priority = document.querySelector('input[name="priority"]:checked')?.value || 'MEDIUM';
        const message = document.getElementById('message').value;
        const btn = document.getElementById('btnSubmitTicket');
        const errorDiv = document.getElementById('createTicketError');

        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_SUPP.btn_processing}`;

        try {
            const res = await fetch('../api/support/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ subject, category, priority, message })
            });

            // Check if response is JSON
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error("Raw response:", text);
                throw new Error(LANG_SUPP.err_invalid_res);
            }

            if (data.status === 'success') {
                closeSupportModal();
                loadTickets();
                // Show success toast or animation?
            } else {
                errorDiv.textContent = data.message || LANG_SUPP.err_create;
                errorDiv.style.display = 'block';
            }
        } catch (e) {
            errorDiv.textContent = LANG_SUPP.err_prefix + e.message;
            errorDiv.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-paper-plane"></i> ${LANG_SUPP.btn_submit}`;
        }
    }

    document.addEventListener('DOMContentLoaded', loadTickets);
</script>

<?php include 'footer.php'; ?>