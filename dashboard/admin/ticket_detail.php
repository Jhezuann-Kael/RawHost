<?php
require_once __DIR__ . '/../../api/config.php';
$pageTitle = SITE_NAME . ' - Admin Ticket Detail';
include 'includes/header.php';

// Get and valid ID
$ticketId = $_GET['id'] ?? null;
// Simple redirect if no ID but frontend should handle it
if (!$ticketId) {
    echo "<script>window.location.href='tickets';</script>";
    exit;
}
?>

<style>
    /* Chat Layout - Reuse similar styles but adjusted for Admin context */
    .chat-layout {
        display: grid;
        grid-template-columns: 3fr 1fr;
        gap: 20px;
        height: calc(100vh - 140px);
        min-height: 0;
    }

    .chat-main {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        min-height: 0;
    }

    .chat-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        flex-shrink: 0;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        background: #f8fafc;
        min-height: 0;
    }

    .chat-input-area {
        padding: 12px 16px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .chat-layout {
            grid-template-columns: 1fr;
            height: auto;
            min-height: 0;
        }

        /* On mobile the main content fills available viewport height */
        .chat-main {
            height: calc(100svh - 160px);
        }

        .info-sidebar {
            order: 2;
        }

        .chat-input-area {
            padding: 10px 12px;
        }

        /* Prevent the textarea from expanding the layout */
        #replyMessage {
            max-height: 120px;
            overflow-y: auto !important;
        }
    }

    /* Messages */
    .message-bubble {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 12px;
        line-height: 1.5;
        position: relative;
        font-size: 0.95rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    /* Admin Logic: 'user' class is ME (the admin), 'support' class is the CUSTOMER (other side) */
    .message-bubble.me {
        align-self: flex-end;
        background: var(--primary);
        /* Admin color */
        color: #fff;
        border-bottom-right-radius: 2px;
    }

    .message-bubble.other {
        align-self: flex-start;
        background: #e2e8f0;
        color: #1e293b;
        border-bottom-left-radius: 2px;
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
        border: 2px solid #cbd5e1;
    }

    /* Sidebar Info */
    .info-sidebar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        height: fit-content;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .info-item {
        margin-bottom: 20px;
    }

    .info-label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
    }

    .upload-btn {
        cursor: pointer;
        color: #64748b;
    }

    /* Override dark-theme form-control for this light panel */
    .chat-input-area .form-control {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        border-radius: 12px;
    }

    .chat-input-area .form-control:focus {
        border-color: var(--primary);
        outline: none;
        background: #fff;
    }

    .chat-input-area .form-control::placeholder {
        color: #94a3b8;
    }

    /* Message actions (admin only on own messages) */
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
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 6px;
        color: rgba(255, 255, 255, 0.85);
        cursor: pointer;
        font-size: 0.7rem;
        padding: 3px 8px;
        transition: background 0.2s, color 0.2s;
    }

    .msg-action-btn:hover {
        background: rgba(255, 255, 255, 0.35);
        color: #fff;
    }

    .msg-action-btn.delete:hover {
        background: rgba(239, 68, 68, 0.4);
        color: #fff;
    }

    .msg-edit-area {
        margin-top: 8px;
    }

    .msg-edit-area textarea {
        width: 100%;
        background: rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        color: #fff;
        font-size: 0.9rem;
        padding: 8px;
        resize: none;
        box-sizing: border-box;
    }

    .msg-edit-area textarea:focus {
        outline: none;
        border-color: rgba(255, 255, 255, 0.6);
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
        color: rgba(255, 255, 255, 0.6);
    }

    .message-bubble.other .edited-label {
        color: rgba(0, 0, 0, 0.4);
    }
</style>

<main class="main-content">
    <div class="header" style="margin-bottom: 15px;">
        <div style="display: flex; align-items: center;">
            <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar menu"><i
                    class="fas fa-bars"></i></button>
            <button class="back-btn" onclick="window.location.href='tickets'"
                style="background:rgba(255,255,255,0.1); border:none; color:var(--text-light); border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; margin-right:15px; cursor:pointer;">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div>
                <h1 style="margin: 0;">Ticket <span id="ticketIdDisplay"
                        style="font-family:'JetBrains Mono'; opacity:0.7;">#
                        <?php echo $ticketId; ?>
                    </span></h1>
                <div id="ticketSubject" style="font-size: 1rem; color: var(--text-muted);">Cargando details...</div>
            </div>
        </div>
    </div>

    <div class="chat-layout">
        <div class="chat-main">
            <div class="chat-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:10px; height:10px; border-radius:50%; background:#22c55e;" id="statusIndicator">
                    </div>
                    <span id="ticketStatusText" style="font-weight:600;">LOADING</span>
                    <span id="newMsgIndicator" style="display:none; font-size:0.75rem; background:#22c55e; color:#fff; border-radius:20px; padding:2px 8px;">
                        Nuevo mensaje
                    </span>
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
                    <input type="hidden" id="ticketId" value="<?php echo $ticketId; ?>">

                    <div style="display:flex; gap:10px; align-items:flex-end;">
                        <div style="flex:1;">
                            <textarea id="replyMessage" class="form-control" rows="1"
                                placeholder="Responder como Admin..."
                                style="resize:none; padding:12px; border-radius:20px; transition: height 0.2s;"
                                oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                        </div>

                        <!-- Simple file upload for now -->
                        <div style="position:relative;">
                            <label for="replyImage" class="upload-btn" title="Adjuntar Imagen" style="padding:10px;">
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

        <!-- Sidebar -->
        <div class="info-sidebar">
            <h3 style="margin-bottom:20px; color:var(--primary);">Detalles del Ticket</h3>

            <div class="info-item">
                <div class="info-label">Usuario</div>
                <div class="info-value" id="ticketUser">-</div>
                <div style="font-size:0.8rem; color:var(--text-muted);" id="ticketUserEmail">-</div>
            </div>

            <div class="info-item">
                <div class="info-label">Categoría</div>
                <div class="info-value" id="ticketCategory">-</div>
            </div>

            <div class="info-item">
                <div class="info-label">Prioridad</div>
                <div class="info-value" id="ticketPriority">-</div>
            </div>

            <div class="info-item"
                style="border-top:1px solid rgba(255,255,255,0.1); padding-top:15px; margin-top:30px;">
                <div class="info-label" style="margin-bottom:10px;">Admin Actions</div>
                <button class="btn btn-power" style="width:100%; font-size:0.8rem; background:#e74c3c;"
                    onclick="closeTicketAdmin()">
                    Cerrar Ticket
                </button>
            </div>
        </div>
    </div>
</main>

<script>
    const ticketId = <?php echo $ticketId; ?>;
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;

    let pollInterval = null;
    let lastMessageCount = 0;
    let ticketIsClosed = false;

    function startPolling() {
        if (pollInterval) return;
        pollInterval = setInterval(pollNewMessages, 15000);
    }

    function stopPolling() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    }

    async function pollNewMessages() {
        if (ticketIsClosed) { stopPolling(); return; }
        try {
            const res = await fetch(`../../api/support/get?id=${ticketId}`);
            const data = await res.json();
            if (data.status === 'success' && data.messages.length !== lastMessageCount) {
                lastMessageCount = data.messages.length;
                renderMessages(data.messages);
                showNewMessageIndicator();
            }
        } catch (_) {}
    }

    function showNewMessageIndicator() {
        const indicator = document.getElementById('newMsgIndicator');
        if (indicator) { indicator.style.display = 'inline'; setTimeout(() => indicator.style.display = 'none', 3000); }
    }

    function showFileName(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files && input.files[0]) {
            display.textContent = 'Adjunto: ' + input.files[0].name;
        } else {
            display.textContent = '';
        }
    }

    async function loadTicket() {
        try {
            // Reusing the User API for 'get' might fail if it restricts to OWN tickets only.
            // Ideally we need an admin endpoint or the user endpoint needs to allow admins.
            // Assuming we must create a quick admin endpoint or reuse if policy allows.
            // Let's TRY generic get first, if it fails we might need to patch api/support/get.
            // Actually, let's use the new api/admin/tickets.php if it supports detail? No it supports list.
            // Let's create a quick function here or assume 'api/support/get.php' allows superuser.
            // Looking at standard behavior, usually superuser check is inside.

            // To be safe, I'll fetch from `../../api/support/get?id=${ticketId}` 
            // If that fails (403), we need to fix the API.

            const res = await fetch(`../../api/support/get?id=${ticketId}`);
            const data = await res.json();

            if (data.status === 'success') {
                const ticket = data.ticket;
                const messages = data.messages;

                document.getElementById('ticketSubject').textContent = ticket.subject;
                document.getElementById('ticketCategory').textContent = ticket.category;
                document.getElementById('ticketPriority').textContent = ticket.priority;
                document.getElementById('ticketUser').textContent = ticket.username || 'User ID ' + ticket.user_id;
                document.getElementById('ticketUserEmail').textContent = ticket.email || '';

                const statusEl = document.getElementById('ticketStatusText');
                const bubbleEl = document.getElementById('statusIndicator');
                statusEl.textContent = ticket.status;

                if (ticket.status === 'OPEN') {
                    bubbleEl.style.background = '#22c55e'; statusEl.style.color = '#22c55e';
                    ticketIsClosed = false;
                    startPolling();
                } else if (ticket.status === 'ANSWERED') {
                    bubbleEl.style.background = '#eab308'; statusEl.style.color = '#eab308';
                    ticketIsClosed = false;
                    startPolling();
                } else {
                    bubbleEl.style.background = '#ef4444';
                    statusEl.style.color = '#ef4444';
                    document.querySelector('.chat-input-area').style.display = 'none';
                    ticketIsClosed = true;
                    stopPolling();
                }

                renderMessages(messages);

            } else {
                document.getElementById('chatContainer').innerHTML = `<p style="text-align:center; color:red;">${data.message}</p>`;
            }
        } catch (e) {
            console.error(e);
            document.getElementById('chatContainer').innerHTML = '<p style="text-align:center; color:red;">Error loading ticket.</p>';
        }
    }

    let currentTicketStatus = 'OPEN';

    function renderMessages(messages) {
        const chat = document.getElementById('chatContainer');
        chat.innerHTML = '';

        messages.forEach(msg => {
            const isAdmin = msg.is_superuser == 1;
            const isMe    = msg.user_id == currentUserId;

            // Wrapper aligns the avatar + bubble together
            const row = document.createElement('div');
            row.style.cssText = `display:flex; align-items:flex-end; gap:8px; ${isAdmin ? 'flex-direction:row-reverse;' : ''}`;

            // Admin avatar
            let avatarEl = '';
            if (isAdmin) {
                const src = msg.profile_picture
                    ? `../../${msg.profile_picture}?v=1`
                    : null;
                avatarEl = src
                    ? `<img src="${src}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid rgba(0,102,255,0.4);" alt="${msg.username}">`
                    : `<div style="width:32px;height:32px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.8rem;color:#fff;"><i class="fas fa-user-shield"></i></div>`;
            }

            const msgDiv = document.createElement('div');
            msgDiv.className = `message-bubble ${isMe ? 'me' : 'other'}`;
            msgDiv.dataset.msgId = msg.id;

            let senderLabel = '';
            if (isMe) senderLabel = '<div style="font-size:0.7em; opacity:0.8; margin-bottom:2px;">Tú (Soporte)</div>';
            else senderLabel = `<div style="font-size:0.7em; opacity:0.8; margin-bottom:2px; font-weight:bold;">${msg.username || 'Usuario'}</div>`;

            let imageHtml = '';
            if (msg.image_path) {
                imageHtml = `<img src="../${msg.image_path}" class="message-img" onclick="window.open(this.src)">`;
            }

            const msgText = msg.message.replace(/\n/g, "<br>");
            const editedLabel = msg.edited_at ? `<span class="edited-label">(editado)</span>` : '';

            let actionsHtml = '';
            if (isMe) {
                actionsHtml = `
                    <div class="message-actions">
                        <button class="msg-action-btn" onclick="startEditMessage(${msg.id})" title="Editar">
                            <i class="fas fa-pencil-alt"></i> Editar
                        </button>
                        <button class="msg-action-btn delete" onclick="deleteMessage(${msg.id})" title="Eliminar">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>`;
            }

            msgDiv.innerHTML = `
                ${senderLabel}
                <span class="msg-text">${msgText}</span>${editedLabel}
                ${imageHtml}
                <div class="message-time">${new Date(msg.created_at).toLocaleString([], { hour: '2-digit', minute: '2-digit' })}</div>
                ${actionsHtml}
            `;

            if (avatarEl) {
                const avatarWrapper = document.createElement('div');
                avatarWrapper.innerHTML = avatarEl;
                row.appendChild(avatarWrapper.firstElementChild);
            }
            row.appendChild(msgDiv);
            chat.appendChild(row);
        });

        lastMessageCount = messages.length;
        chat.scrollTop = chat.scrollHeight;
    }

    async function sendReply(e) {
        e.preventDefault();
        const message = document.getElementById('replyMessage').value.trim();
        const imageFile = document.getElementById('replyImage').files[0];
        const errEl = document.getElementById('replyError');
        const btn = document.getElementById('btnReply');

        if (!message && !imageFile) {
            errEl.textContent = 'Escribe un mensaje o adjunta una imagen.';
            return;
        }
        errEl.textContent = '';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('ticket_id', ticketId);
        formData.append('message', message);
        if (imageFile) formData.append('image', imageFile);

        try {
            const res = await fetch('../../api/admin/ticket_reply', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                document.getElementById('replyForm').reset();
                document.getElementById('fileNameDisplay').textContent = '';
                loadTicket();
            } else {
                alert(data.message || 'Error al enviar');
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        } finally {
            btn.disabled = false;
        }
    }

    async function closeTicketAdmin() {
        if (!confirm('¿Cerrar este ticket?')) return;

        try {
            const res = await fetch('../../api/support/close', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: ticketId })
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert('Ticket cerrado');
                loadTicket();
            } else {
                alert(data.message);
            }
        } catch (e) { console.error(e); }
    }

    // ---- Edit / Delete admin message functions ----

    function startEditMessage(msgId) {
        const bubble = document.querySelector(`.message-bubble[data-msg-id="${msgId}"]`);
        if (!bubble || bubble.querySelector('.msg-edit-area')) return;

        const textSpan = bubble.querySelector('.msg-text');
        const currentText = textSpan.innerText;

        const editArea = document.createElement('div');
        editArea.className = 'msg-edit-area';
        editArea.innerHTML = `
            <textarea rows="3">${currentText}</textarea>
            <div class="msg-edit-actions">
                <button class="msg-action-btn" onclick="cancelEdit(${msgId})">Cancelar</button>
                <button class="msg-action-btn" style="background:rgba(255,255,255,0.3);" onclick="saveEdit(${msgId})">Guardar</button>
            </div>`;

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
        bubble.querySelector('.msg-edit-area')?.remove();
        const textSpan = bubble.querySelector('.msg-text');
        if (textSpan) textSpan.style.display = '';
    }

    async function saveEdit(msgId) {
        const bubble = document.querySelector(`.message-bubble[data-msg-id="${msgId}"]`);
        if (!bubble) return;
        const editArea = bubble.querySelector('.msg-edit-area');
        const newText = editArea.querySelector('textarea').value.trim();
        if (!newText) return;

        const saveBtn = editArea.querySelectorAll('.msg-action-btn')[1];
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const res = await fetch('../../api/support/edit_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: msgId, message: newText })
            });
            const data = await res.json();

            if (data.status === 'success') {
                loadTicket();
            } else {
                alert(data.message || 'Error al editar el mensaje.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Guardar';
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Guardar';
        }
    }

    async function deleteMessage(msgId) {
        if (!confirm('¿Eliminar este mensaje?')) return;

        try {
            const res = await fetch('../../api/support/delete_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: msgId })
            });
            const data = await res.json();

            if (data.status === 'success') {
                loadTicket();
            } else {
                alert(data.message || 'Error al eliminar el mensaje.');
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }

    document.addEventListener('DOMContentLoaded', loadTicket);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopPolling();
        else if (!ticketIsClosed) startPolling();
    });
</script>

<?php include '../footer.php'; ?>