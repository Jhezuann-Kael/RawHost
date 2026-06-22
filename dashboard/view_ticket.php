<?php
$pageTitle = $lang['ticket_page_title'];
$extraHead = '
<style>
    /* Chat Layout */
    .chat-layout {
        display: grid;
        grid-template-columns: 3fr 1fr;
        gap: 20px;
        height: calc(100vh - 140px); /* Fill screen minus header */
    }

    /* Main Chat Area */
    .chat-main {
        display: flex;
        flex-direction: column;
        background: rgba(30, 41, 59, 0.5); /* Semi-transparent */
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }
    
    .chat-header {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(0,0,0,0.2);
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        background: url("data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.02\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'3\' cy=\'3\' r=\'3\'/%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'3\'/%3E%3C/g%3E%3C/svg%3E");
    }

    .chat-input-area {
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(0,0,0,0.2);
    }
    
    /* Messages */
    .message-bubble {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 12px;
        line-height: 1.5;
        position: relative;
        font-size: 0.95rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .message-bubble.user {
        align-self: flex-end;
        background: rgba(99,102,241,0.18);
        border: 1px solid rgba(99,102,241,0.28);
        color: var(--text-light);
        border-radius: 12px 4px 12px 12px;
    }

    .message-bubble.support {
        align-self: flex-start;
        background: rgba(16,185,129,0.1);
        border: 1px solid rgba(16,185,129,0.22);
        color: var(--text-light);
        border-radius: 4px 12px 12px 12px;
    }
    
    .message-time {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 4px;
        text-align: right;
    }
    
    .message-img {
        max-width: 100%;
        border-radius: 8px;
        margin-top: 8px;
        cursor: pointer;
        border: 2px solid rgba(255,255,255,0.1);
        transition: transform 0.2s;
    }
    .message-img:hover {
        transform: scale(1.02);
    }

    /* Message actions */
    .message-actions {
        display: none;
        gap: 4px;
        margin-top: 6px;
        justify-content: flex-end;
    }

    .message-bubble:hover .message-actions {
        display: flex;
    }

    .msg-action-btn {
        background: rgba(255,255,255,0.12);
        border: none;
        border-radius: 6px;
        color: rgba(255,255,255,0.7);
        cursor: pointer;
        font-size: 0.7rem;
        padding: 3px 8px;
        transition: background 0.2s, color 0.2s;
    }

    .msg-action-btn:hover {
        background: rgba(255,255,255,0.22);
        color: #fff;
    }

    .msg-action-btn.delete:hover {
        background: rgba(239,68,68,0.3);
        color: #ef4444;
    }

    .msg-edit-area {
        margin-top: 8px;
    }

    .msg-edit-area textarea {
        width: 100%;
        background: rgba(0,0,0,0.25);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 8px;
        color: #fff;
        font-size: 0.9rem;
        padding: 8px;
        resize: none;
        box-sizing: border-box;
    }

    .msg-edit-area textarea:focus {
        outline: none;
        border-color: var(--primary);
    }

    .msg-edit-actions {
        display: flex;
        gap: 6px;
        margin-top: 6px;
        justify-content: flex-end;
    }

    .edited-label {
        font-size: 0.7rem;
        font-style: italic;
        margin-left: 5px;
        color: rgba(255,255,255,0.5);
    }

    .message-bubble.support .edited-label {
        color: rgba(255,255,255,0.45);
    }

    .message-bubble.user .edited-label {
        color: rgba(255,255,255,0.6);
    }

    /* Sidebar Info */
    .info-sidebar {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 20px;
        height: fit-content;
    }
    
    .info-item {
        margin-bottom: 20px;
    }
    
    .info-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-light);
    }

    /* File Input Styling */
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    
    .upload-btn {
        background: rgba(255,255,255,0.1);
        color: var(--text-light);
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
        transition: background 0.3s;
    }
    
    .upload-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    @media (max-width: 900px) {
        .chat-layout {
            grid-template-columns: 1fr;
            height: auto;
        }
        .chat-main {
            height: 600px;
        }
    }
</style>
';
include 'header.php';
?>

<main class="main-content">
    <div class="header" style="margin-bottom: 15px;">
        <div style="display: flex; align-items: center;">
            <button class="toggle-btn" onclick="window.location.href='support'"
                style="background:rgba(255,255,255,0.1); border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; margin-right:15px;">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div>
                <h1 style="margin: 0;"><?php echo $lang['ticket_header']; ?> <span id="ticketIdDisplay"
                        style="font-family:'JetBrains Mono'; opacity:0.7;">#...</span></h1>
                <div id="ticketSubject" style="font-size: 1rem; color: var(--text-muted);">
                    <?php echo $lang['ticket_load_subject']; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="chat-layout">
        <!-- Main Chat -->
        <div class="chat-main">
            <div class="chat-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:10px; height:10px; border-radius:50%; background:#22c55e;" id="statusIndicator">
                    </div>
                    <span id="ticketStatusText"
                        style="font-weight:600;"><?php echo $lang['ticket_status_load']; ?></span>
                </div>
                <button class="btn btn-sm" style="background:transparent; color:var(--text-muted);"
                    onclick="loadTicket()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <div class="chat-messages" id="chatContainer">
                <div style="text-align: center; margin-top: 50px; color: var(--text-muted);">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i>
                </div>
            </div>

            <div class="chat-input-area">
                <form id="replyForm" onsubmit="sendReply(event)">
                    <input type="hidden" id="ticketId" value="<?php echo $_GET['id'] ?? ''; ?>">

                    <div style="display:flex; gap:10px; align-items:flex-end;">
                        <div style="flex:1;">
                            <textarea id="replyMessage" class="form-control" rows="1"
                                placeholder="<?php echo $lang['ticket_ph_reply']; ?>" required
                                style="resize:none; padding:12px; border-radius:20px; transition: height 0.2s;"
                                oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                        </div>

                        <div class="file-input-wrapper">
                            <label for="replyImage" class="upload-btn"
                                title="<?php echo $lang['ticket_btn_attach']; ?>">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="replyImage" accept="image/*" style="display:none;"
                                onchange="showFileName(this)">
                        </div>

                        <button type="submit" class="btn btn-manage" id="btnReply"
                            style="border-radius:50%; width:45px; height:45px; padding:0; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div id="fileNameDisplay"
                        style="font-size:0.8rem; color:var(--primary); margin-top:5px; margin-left:10px;"></div>
                    <div id="replyError" style="color: #ef4444; font-size:0.8rem; margin-top:5px; display: block;">
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="info-sidebar">
            <h3 style="margin-bottom:20px; color:var(--primary);"><?php echo $lang['ticket_det_title']; ?></h3>

            <div class="info-item">
                <div class="info-label"><?php echo $lang['ticket_lbl_cat']; ?></div>
                <div class="info-value" id="ticketCategory">-</div>
            </div>

            <div class="info-item">
                <div class="info-label"><?php echo $lang['ticket_lbl_prio']; ?></div>
                <div class="info-value" id="ticketPriority">-</div>
            </div>

            <div class="info-item">
                <div class="info-label"><?php echo $lang['ticket_lbl_date']; ?></div>
                <div class="info-value" id="ticketDate">-</div>
            </div>

            <div class="info-item"
                style="border-top:1px solid rgba(255,255,255,0.1); padding-top:15px; margin-top:30px;">
                <div class="info-label" style="margin-bottom:10px;"><?php echo $lang['ticket_lbl_act']; ?></div>
                <!-- Future: Close Ticket Button -->
                <button class="btn btn-power" style="width:100%; font-size:0.8rem;" onclick="openCloseModal()"
                    id="btnCloseTicketSidebar">
                    <?php echo $lang['ticket_btn_close']; ?>
                </button>
            </div>
        </div>
    </div>
</main>


<!-- Close Ticket Modal -->
<div class="modal-overlay" id="closeTicketModal">
    <div class="modal" style="text-align:center;">
        <div class="modal-header">
            <h2><?php echo $lang['ticket_close_title']; ?></h2>
            <button class="btn-close"
                onclick="document.getElementById('closeTicketModal').classList.remove('active')"><i
                    class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom:20px; color:var(--text-muted);"><?php echo $lang['ticket_close_text']; ?></p>

            <div style="display:flex; gap:15px; justify-content:center; margin-bottom:25px;">
                <button class="rating-btn" onclick="selectRating('VERY_GOOD', this)">
                    <i class="fas fa-smile-beam" style="font-size:2rem; color:#22c55e;"></i>
                    <div style="margin-top:5px; font-size:0.8rem;"><?php echo $lang['ticket_rate_vg']; ?></div>
                </button>
                <button class="rating-btn" onclick="selectRating('GOOD', this)">
                    <i class="fas fa-smile" style="font-size:2rem; color:#eab308;"></i>
                    <div style="margin-top:5px; font-size:0.8rem;"><?php echo $lang['ticket_rate_g']; ?></div>
                </button>
                <button class="rating-btn" onclick="selectRating('NOT_GOOD', this)">
                    <i class="fas fa-frown" style="font-size:2rem; color:#ef4444;"></i>
                    <div style="margin-top:5px; font-size:0.8rem;"><?php echo $lang['ticket_rate_ng']; ?></div>
                </button>
            </div>

            <input type="hidden" id="selectedRating">

            <button class="btn btn-manage" onclick="confirmCloseTicket()" id="btnCloseConfirm" disabled
                style="width:100%;">
                <?php echo $lang['ticket_btn_conf_close']; ?>
            </button>
        </div>
    </div>

    <style>
        .rating-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            width: 100px;
            transition: all 0.2s;
            color: #fff;
        }

        .rating-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .rating-btn.active {
            background: rgba(37, 99, 235, 0.1);
            border-color: var(--primary);
        }
    </style>

    <script>
        const LANG_TICKET = {
            alert_closed: '<?php echo $lang['ticket_alert_closed']; ?>',
            err_prefix: '<?php echo $lang['supp_err_prefix']; ?>',
            err_conn: '<?php echo $lang['ticket_err_conn']; ?>',
            err_chat_conn: '<?php echo $lang['ticket_err_chat_conn']; ?>',
            att_prefix: '<?php echo $lang['ticket_att_prefix']; ?>',
            err_send: '<?php echo $lang['ticket_err_send']; ?>',
            sender_supp: '<?php echo $lang['ticket_sender_supp']; ?>',
            sender_root: '<?php echo $lang['ticket_sender_root']; ?>',
            btn_conf_close: '<?php echo $lang['ticket_btn_conf_close']; ?>',
            status_open: '<?php echo $lang['status_open']; ?>',
            status_answered: '<?php echo $lang['status_answered']; ?>',
            status_closed: '<?php echo $lang['status_closed']; ?>',
            btn_edit: '<?php echo $lang['ticket_btn_edit']; ?>',
            btn_delete: '<?php echo $lang['ticket_btn_delete']; ?>',
            edited_label: '<?php echo $lang['ticket_edited_label']; ?>',
            edit_placeholder: '<?php echo $lang['ticket_edit_placeholder']; ?>',
            btn_save: '<?php echo $lang['ticket_btn_save']; ?>',
            btn_cancel: '<?php echo $lang['ticket_btn_cancel']; ?>',
            confirm_delete: '<?php echo $lang['ticket_confirm_delete']; ?>',
            err_edit: '<?php echo $lang['ticket_err_edit']; ?>',
            err_delete: '<?php echo $lang['ticket_err_delete']; ?>',
            cat_tech: '<?php echo $lang['supp_cat_tech']; ?>',
            cat_bill: '<?php echo $lang['supp_cat_bill']; ?>',
            cat_rec:  '<?php echo $lang['supp_cat_rec']; ?>',
            cat_svc:  '<?php echo $lang['supp_cat_service']; ?>',
            cat_other:'<?php echo $lang['supp_cat_other']; ?>',
        };

        const ticketId = document.getElementById('ticketId').value;
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;
        const isSuperuser = <?php echo json_encode((bool)($_SESSION['is_superuser'] ?? false)); ?>;

        if (!ticketId) {
            window.location.href = 'support';
        }

        const _catMap = {
            TECHNICAL: LANG_TICKET.cat_tech, BILLING: LANG_TICKET.cat_bill,
            RECOMMENDATIONS: LANG_TICKET.cat_rec, SERVICE_REQUEST: LANG_TICKET.cat_svc,
            OTHER: LANG_TICKET.cat_other,
        };

        function showFileName(input) {
            const display = document.getElementById('fileNameDisplay');
            if (input.files && input.files[0]) {
                display.textContent = LANG_TICKET.att_prefix + input.files[0].name;
            } else {
                display.textContent = '';
            }
        }

        // Close Ticket Functions
        function openCloseModal() {
            document.getElementById('closeTicketModal').classList.add('active');
        }

        function selectRating(rating, btn) {
            document.getElementById('selectedRating').value = rating;

            // Update UI
            document.querySelectorAll('.rating-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.getElementById('btnCloseConfirm').disabled = false;
        }

        async function confirmCloseTicket() {
            const rating = document.getElementById('selectedRating').value;
            const btn = document.getElementById('btnCloseConfirm');

            if (!rating) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            try {
                const res = await fetch('../api/support/close', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ticket_id: ticketId, rating: rating })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    alert(LANG_TICKET.alert_closed);
                    window.location.reload();
                } else {
                    alert(LANG_TICKET.err_prefix + data.message);
                    btn.disabled = false;
                    btn.innerHTML = LANG_TICKET.btn_conf_close;
                }
            } catch (e) {
                console.error(e);
                alert(LANG_TICKET.err_conn);
                btn.disabled = false;
                btn.innerHTML = LANG_TICKET.btn_conf_close;
            }
        }

        async function loadTicket() {
            try {
                const res = await fetch(`../api/support/get?id=${ticketId}`);

                // Handle potentially non-JSON errors (PHP Fatal Errors)
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("Raw:", text);
                    document.getElementById('chatContainer').innerHTML = `<div style="text-align:center; padding:20px; color:#ef4444;">${LANG_TICKET.err_chat_conn}</div>`;
                    return;
                }

                if (data.status === 'success') {
                    const ticket = data.ticket;
                    const messages = data.messages;

                    // Update Info
                    document.getElementById('ticketIdDisplay').textContent = '#' + ticket.id;
                    document.getElementById('ticketSubject').textContent = ticket.subject;
                    document.getElementById('ticketCategory').textContent = _catMap[ticket.category] || ticket.category;
                    document.getElementById('ticketPriority').textContent = ticket.priority;
                    document.getElementById('ticketDate').textContent = new Date(ticket.created_at).toLocaleDateString();

                    // Status
                    function translateStatus(status) {
                        const translations = {
                            'OPEN': LANG_TICKET.status_open,
                            'ANSWERED': LANG_TICKET.status_answered,
                            'CLOSED': LANG_TICKET.status_closed
                        };
                        return translations[status] || status;
                    }

                    const statusEl = document.getElementById('ticketStatusText');
                    const bubbleEl = document.getElementById('statusIndicator');
                    const closeBtn = document.getElementById('btnCloseTicketSidebar');
                    const replyArea = document.querySelector('.chat-input-area');
                    statusEl.textContent = translateStatus(ticket.status);
                    if (ticket.status === 'OPEN') { bubbleEl.style.background = '#22c55e'; statusEl.style.color = '#22c55e'; }
                    else if (ticket.status === 'ANSWERED') { bubbleEl.style.background = '#eab308'; statusEl.style.color = '#eab308'; }
                    else {
                        bubbleEl.style.background = '#ef4444';
                        statusEl.style.color = '#ef4444';
                        // Disable inputs if closed
                        if (replyArea) replyArea.style.display = 'none';
                        if (closeBtn) closeBtn.style.display = 'none';
                    }

                    // Chat
                    const chat = document.getElementById('chatContainer');
                    chat.innerHTML = '';

                    let lastDate = null;

                    const isTicketClosed = ticket.status === 'CLOSED';

                    messages.forEach(msg => {
                        const isMe = msg.user_id == currentUserId;

                        const msgDiv = document.createElement('div');
                        msgDiv.className = `message-bubble ${isMe ? 'user' : 'support'}`;
                        msgDiv.dataset.msgId = msg.id;

                        let imageHtml = '';
                        if (msg.image_path) {
                            imageHtml = `<img src="${msg.image_path}" class="message-img" onclick="window.open(this.src)">`;
                        }

                        // Sender Name Logic
                        let senderName = '';
                        if (!isMe) {
                            const adminName = msg.display_name || msg.username || LANG_TICKET.sender_supp;
                            senderName = `<div style="font-size:0.72rem;font-weight:700;color:#10b981;margin-bottom:4px;"><i class="fas fa-headset"></i> Support — ${adminName}</div>`;
                        }

                        const editedLabel = msg.edited_at ? `<span class="edited-label">${LANG_TICKET.edited_label}</span>` : '';

                        // Convert line breaks
                        const msgText = msg.message.replace(/\n/g, "<br>");

                        // Action buttons: owner can edit/delete; admin can delete; hidden if ticket closed
                        let actionsHtml = '';
                        if (!isTicketClosed && isMe) {
                            actionsHtml = `
                                <div class="message-actions">
                                    <button class="msg-action-btn" onclick="startEditMessage(${msg.id}, this)" title="${LANG_TICKET.btn_edit}">
                                        <i class="fas fa-pencil-alt"></i> ${LANG_TICKET.btn_edit}
                                    </button>
                                    <button class="msg-action-btn delete" onclick="deleteMessage(${msg.id}, this)" title="${LANG_TICKET.btn_delete}">
                                        <i class="fas fa-trash"></i> ${LANG_TICKET.btn_delete}
                                    </button>
                                </div>`;
                        } else if (isSuperuser && isTicketClosed) {
                            actionsHtml = `
                                <div class="message-actions">
                                    <button class="msg-action-btn delete" onclick="deleteMessage(${msg.id}, this)" title="${LANG_TICKET.btn_delete}">
                                        <i class="fas fa-trash"></i> ${LANG_TICKET.btn_delete}
                                    </button>
                                </div>`;
                        }

                        msgDiv.innerHTML = `
                            ${senderName}
                            <span class="msg-text">${msgText}</span>${editedLabel}
                            ${imageHtml}
                            <div class="message-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                            ${actionsHtml}
                        `;
                        chat.appendChild(msgDiv);
                    });

                    // Scroll to bottom
                    chat.scrollTop = chat.scrollHeight;

                } else {
                    alert(data.message);
                    window.location.href = 'support';
                }
            } catch (e) {
                console.error(e);
                document.getElementById('chatContainer').innerHTML = `<div style="text-align:center; color:#ef4444;">Error de red. Intenta recargar.</div>`;
            }
        }

        async function sendReply(e) {
            e.preventDefault();
            const message = document.getElementById('replyMessage').value;
            const imageFile = document.getElementById('replyImage').files[0];
            const btn = document.getElementById('btnReply');
            const errorDiv = document.getElementById('replyError');
            const icon = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            errorDiv.textContent = '';

            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            formData.append('message', message);
            if (imageFile) {
                formData.append('image', imageFile);
            }

            try {
                const res = await fetch('../api/support/reply', {
                    method: 'POST',
                    body: formData
                });
                const text = await res.text();
                let data;
                try { data = JSON.parse(text); } catch (ex) { throw new Error("Server error"); }

                if (data.status === 'success') {
                    document.getElementById('replyForm').reset();
                    document.getElementById('fileNameDisplay').textContent = '';
                    document.getElementById('replyMessage').style.height = 'auto'; // reset height
                    loadTicket(); // Reload chat
                } else {
                    errorDiv.textContent = data.message || LANG_TICKET.err_send;
                }
            } catch (e) {
                console.error(e);
                errorDiv.textContent = LANG_TICKET.err_conn;
            } finally {
                btn.disabled = false;
                btn.innerHTML = icon;
            }
        }

        // ---- Edit / Delete message functions ----

        function startEditMessage(msgId, btn) {
            const bubble = document.querySelector(`.message-bubble[data-msg-id="${msgId}"]`);
            if (!bubble) return;

            const textSpan = bubble.querySelector('.msg-text');
            const currentText = textSpan.innerText;

            // Prevent double edit area
            if (bubble.querySelector('.msg-edit-area')) return;

            const editArea = document.createElement('div');
            editArea.className = 'msg-edit-area';
            editArea.innerHTML = `
                <textarea rows="3">${currentText}</textarea>
                <div class="msg-edit-actions">
                    <button class="msg-action-btn" onclick="cancelEdit(${msgId})">${LANG_TICKET.btn_cancel}</button>
                    <button class="msg-action-btn btn-manage" style="background:var(--primary);" onclick="saveEdit(${msgId})">${LANG_TICKET.btn_save}</button>
                </div>`;

            // Hide original text and actions
            textSpan.style.display = 'none';
            const actions = bubble.querySelector('.message-actions');
            if (actions) actions.style.display = 'none';

            bubble.appendChild(editArea);

            const ta = editArea.querySelector('textarea');
            ta.focus();
            ta.style.height = ta.scrollHeight + 'px';
        }

        function cancelEdit(msgId) {
            const bubble = document.querySelector(`.message-bubble[data-msg-id="${msgId}"]`);
            if (!bubble) return;
            const editArea = bubble.querySelector('.msg-edit-area');
            if (editArea) editArea.remove();
            const textSpan = bubble.querySelector('.msg-text');
            if (textSpan) textSpan.style.display = '';
        }

        async function saveEdit(msgId) {
            const bubble = document.querySelector(`.message-bubble[data-msg-id="${msgId}"]`);
            if (!bubble) return;
            const editArea = bubble.querySelector('.msg-edit-area');
            const newText = editArea.querySelector('textarea').value.trim();
            if (!newText) return;

            const saveBtn = editArea.querySelector('.btn-manage');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res = await fetch('../api/support/edit_message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message_id: msgId, message: newText })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    loadTicket();
                } else {
                    alert(data.message || LANG_TICKET.err_edit);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = LANG_TICKET.btn_save;
                }
            } catch (e) {
                console.error(e);
                alert(LANG_TICKET.err_edit);
                saveBtn.disabled = false;
                saveBtn.innerHTML = LANG_TICKET.btn_save;
            }
        }

        async function deleteMessage(msgId, btn) {
            if (!confirm(LANG_TICKET.confirm_delete)) return;

            btn.disabled = true;

            try {
                const res = await fetch('../api/support/delete_message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message_id: msgId })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    loadTicket();
                } else {
                    alert(data.message || LANG_TICKET.err_delete);
                    btn.disabled = false;
                }
            } catch (e) {
                console.error(e);
                alert(LANG_TICKET.err_delete);
                btn.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', loadTicket);
    </script>

    <?php include 'footer.php'; ?>