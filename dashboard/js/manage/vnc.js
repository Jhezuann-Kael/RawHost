let rfbModal = null;

function openVncModal() {
    document.getElementById('vncModal').classList.add('active');
}

function closeVncModal() {
    if (rfbModal) {
        rfbModal.disconnect();
        rfbModal = null;
    }
    document.getElementById('vncModal').classList.remove('active');
    document.getElementById('vnc-modal-details').classList.add('hidden');
    document.getElementById('vnc-modal-placeholder').style.display = 'flex';
}

async function connectVncModal() {
    const btn = document.getElementById('btn-vnc-modal-connect');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando...';

    try {
        const response = await fetch(`/api/servers/vnc?id=${serverId}`);
        const result = await response.json();

        if (!result.success) {
            document.getElementById('vnc-modal-error').textContent = result.message || 'Error al obtener credenciales VNC';
            document.getElementById('vnc-modal-error').classList.remove('hidden');
            return;
        }

        const vncUrl = result.vnc_url;
        const vncPassword = result.vnc_password;

        if (!vncUrl || !vncPassword) {
            document.getElementById('vnc-modal-error').textContent = LANG_MAN.vnc_err_incomplete;
            document.getElementById('vnc-modal-error').classList.remove('hidden');
            return;
        }

        document.getElementById('vnc-modal-password').textContent = vncPassword;

        setupCopy('copy-vnc-modal-pass', vncPassword);

        document.getElementById('vnc-modal-placeholder').style.display = 'none';
        document.getElementById('vnc-modal-details').classList.remove('hidden');
        document.getElementById('vnc-modal-status-text').classList.remove('hidden');
        document.getElementById('vnc-modal-status-text').textContent = 'Conectando...';

        let wsUrl = vncUrl;
        if (vncUrl && !vncUrl.startsWith('ws') && !vncUrl.startsWith('http')) {
            wsUrl = 'wss://hypervisor.kordindustries.io:443/vnc?url=' + encodeURIComponent(vncUrl);
        }

        const screen = document.getElementById('vnc-modal-screen');
        rfbModal = new RFB(screen, wsUrl, {
            credentials: {
                password: vncPassword,
                username: 'root'
            }
        });

        rfbModal.addEventListener('connect', () => {
            document.getElementById('vnc-modal-status-text').classList.add('hidden');
            document.getElementById('vnc-modal-error').classList.add('hidden');
        });

        rfbModal.addEventListener('disconnect', (e) => {
            const reason = e.detail.clean ? 'Conexión cerrada' : 'Conexión perdida';
            document.getElementById('vnc-modal-status-text').textContent = reason;
            document.getElementById('vnc-modal-status-text').classList.remove('hidden');
        });

        rfbModal.addEventListener('credentialsfailed', (e) => {
            document.getElementById('vnc-modal-error').textContent = 'Error de autenticación VNC. Verifica la contraseña.';
            document.getElementById('vnc-modal-error').classList.remove('hidden');
            document.getElementById('vnc-modal-status-text').classList.add('hidden');
        });

        rfbModal.addEventListener('securityfailure', (e) => {
            document.getElementById('vnc-modal-error').textContent = 'Error de seguridad VNC: ' + e.detail.reason;
            document.getElementById('vnc-modal-error').classList.remove('hidden');
            document.getElementById('vnc-modal-status-text').classList.add('hidden');
        });

        rfbModal.scaleViewport = true;
        rfbModal.resizeSession = true;

    } catch (error) {
        console.error('VNC Error:', error);
        document.getElementById('vnc-modal-error').textContent = LANG_MAN.vnc_err_connection + error.message;
        document.getElementById('vnc-modal-error').classList.remove('hidden');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

function disconnectVncModal() {
    if (rfbModal) {
        rfbModal.disconnect();
        rfbModal = null;
    }
    document.getElementById('vnc-modal-details').classList.add('hidden');
    document.getElementById('vnc-modal-placeholder').style.display = 'flex';
    document.getElementById('vnc-modal-status-text').textContent = 'Ready';
    document.getElementById('vnc-modal-status-text').classList.remove('hidden');
}

function sendCtrlAltDelModal() {
    if (rfbModal) {
        rfbModal.sendCtrlAltDel();
    }
}

function toggleVncModalKeyboard() {
    if (rfbModal) {
        rfbModal.showKeyboard = !rfbModal.showKeyboard;
        const btn = document.getElementById('btn-vnc-modal-keyboard');
        if (rfbModal.showKeyboard) {
            btn.style.color = 'var(--primary)';
            btn.title = 'Hide Virtual Keyboard';
        } else {
            btn.style.color = '';
            btn.title = 'Virtual Keyboard';
        }
    }
}
