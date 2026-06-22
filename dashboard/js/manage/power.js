async function openReinstallModal() {
    document.getElementById('reinstallModal').classList.add('active');
    document.getElementById('selectedOsId').value = '';
    document.getElementById('selectedAppId').value = '';
    document.getElementById('reinstallType').value = 'os';
    reinstallTabSwitch('os');
    await loadReinstallOptions();
}

function closeReinstallModal() {
    document.getElementById('reinstallModal').classList.remove('active');
}

function reinstallTabSwitch(tab) {
    document.getElementById('reinstallType').value = tab;
    document.getElementById('reinstall-tab-os').classList.toggle('active', tab === 'os');
    document.getElementById('reinstall-tab-app').classList.toggle('active', tab === 'app');
    document.getElementById('reinstall-os-pane').style.display = tab === 'os' ? 'block' : 'none';
    document.getElementById('reinstall-app-pane').style.display = tab === 'app' ? 'block' : 'none';
}

function getOsIcon(name) {
    const l = name.toLowerCase();
    if (l.includes('windows')) return { icon: 'fab fa-windows', cls: 'os-windows' };
    if (l.includes('ubuntu'))  return { icon: 'fab fa-ubuntu',  cls: 'os-ubuntu'  };
    if (l.includes('debian'))  return { icon: 'fab fa-linux',   cls: 'os-debian'  };
    if (l.includes('centos'))  return { icon: 'fab fa-centos',  cls: 'os-centos'  };
    if (l.includes('suse') || l.includes('opensuse')) return { icon: 'fab fa-suse', cls: 'os-suse' };
    return { icon: 'fas fa-server', cls: 'os-linux' };
}

function buildReinstallCard(item, type) {
    const card = document.createElement('div');
    card.className = 'os-option-card';
    const { icon, cls } = getOsIcon(item.name);
    card.classList.add(cls);
    card.innerHTML = `<div class="os-icon"><i class="${icon}"></i></div><div class="os-name">${item.name}</div>`;
    card.onclick = () => {
        document.querySelectorAll('#reinstallModal .os-option-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        if (type === 'os') {
            document.getElementById('selectedOsId').value = item.id;
            document.getElementById('selectedAppId').value = '';
        } else {
            document.getElementById('selectedAppId').value = item.id;
            document.getElementById('selectedOsId').value = '';
        }
    };
    return card;
}

async function loadReinstallOptions() {
    const osGrid  = document.getElementById('osGrid');
    const appGrid = document.getElementById('appGrid');
    try {
        const res  = await fetch('/api/plans/list');
        const data = await res.json();
        if (!data.success) throw new Error();

        const plan = data.data.find(p => p.name === currentPlanName) || data.data[0];

        // OS list
        const osList = plan?.available_os_image_versions || [];
        osGrid.innerHTML = '';
        if (osList.length) {
            osList.forEach(os => osGrid.appendChild(buildReinstallCard(os, 'os')));
        } else {
            osGrid.innerHTML = '<div style="color:var(--text-muted);padding:20px;grid-column:1/-1;">No OS available.</div>';
        }

        // App list
        const appList = plan?.available_applications || [];
        appGrid.innerHTML = '';
        if (appList.length) {
            appList.forEach(app => appGrid.appendChild(buildReinstallCard(app, 'app')));
        } else {
            appGrid.innerHTML = '<div style="color:var(--text-muted);padding:20px;grid-column:1/-1;">No apps available.</div>';
        }
    } catch (e) {
        osGrid.innerHTML  = '<div style="color:#ef4444;padding:20px;grid-column:1/-1;">Error loading options.</div>';
        appGrid.innerHTML = '';
    }
}

async function confirmReinstall() {
    const type  = document.getElementById('reinstallType').value;
    const osId  = document.getElementById('selectedOsId').value;
    const appId = document.getElementById('selectedAppId').value;

    if (type === 'os' && !osId)   { showNotification('warning', LANG_MAN.js_warn, 'Select an OS image.'); return; }
    if (type === 'app' && !appId) { showNotification('warning', LANG_MAN.js_warn, 'Select an application.'); return; }

    const confirmed = await showConfirm(LANG_MAN.js_confirm_title, LANG_MAN.js_reinstall_confirm, LANG_MAN.js_reinstalling, true);
    if (!confirmed) return;

    const btn = document.getElementById('btnConfirmReinstall');
    const originalContent = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_MAN.js_reinstalling}`;
    btn.disabled = true;

    const body = { vps_id: serverId };
    if (type === 'app') body.application_id = parseInt(appId);
    else                body.os_id = parseInt(osId);

    try {
        const response = await fetch('/api/servers/reinstall', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await response.json();

        if (data.success) {
            showNotification('success', LANG_MAN.js_success, LANG_MAN.js_action_exec);
            closeReinstallModal();
            setTimeout(() => fetchServerDetails(), 2000);
        } else {
            showNotification('error', LANG_MAN.js_err, data.message || 'Error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    } catch (error) {
        showNotification('error', 'Error', 'Connection Error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

async function performServerAction(action) {
    let confirmMsg = "";
    let btnSelector = "";

    if (action === 'stop') {
        confirmMsg = LANG_MAN.js_confirm_stop;
        btnSelector = "button[title^='Forz'], button[title^='Stop']";
    } else if (action === 'restart') {
        confirmMsg = LANG_MAN.js_confirm_rest;
        btnSelector = "button[title^='Rein'], button[title^='Restart']";
    } else if (action === 'start') {
        confirmMsg = LANG_MAN.js_confirm_action;
        btnSelector = "button[title^='Inic'], button[title^='Start']";
    }

    if (confirmMsg) {
        const confirmed = await showConfirm(
            LANG_MAN.js_confirm_title,
            confirmMsg,
            'Yes',
            action === 'stop'
        );

        if (!confirmed) return;
    }

    const btn = document.querySelector(btnSelector);
    if (!btn) {
    }
    const btnStable = document.querySelector(`button[onclick="performServerAction('${action}')"]`);
    if (!btnStable) {
        showNotification('error', LANG_MAN.js_err, 'Button not found');
        return;
    }
    const originalContent = btnStable.innerHTML;

    btnStable.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
    btnStable.disabled = true;

    try {
        const response = await fetch('/api/servers/action', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                vps_id: serverId,
                action: action
            })
        });

        const result = await response.json();

        if (result.success) {
            const statusBadge = document.getElementById('vps-status');

            let actionText = LANG_MAN.js_processing;
            if (action === 'start') actionText = LANG_MAN.js_act_start;
            else if (action === 'restart') actionText = LANG_MAN.js_act_rest;
            else if (action === 'stop') actionText = LANG_MAN.js_act_stop;

            statusBadge.textContent = actionText;

            showNotification('success', LANG_MAN.js_success, LANG_MAN.js_action_exec);
            setTimeout(() => fetchServerDetails(), 3000);

        } else {
            showNotification('error', LANG_MAN.js_err, result.message || 'Error');
        }

    } catch (error) {
        console.error(error);
        showNotification('error', 'Error', 'Connection Error');
    } finally {
        if (btnStable) {
            btnStable.innerHTML = originalContent;
            btnStable.disabled = false;
        }
    }
}

async function resetPassword() {
    const confirmed = await showConfirm(
        LANG_MAN.js_confirm_title,
        'Reset Password?',
        'Yes',
        true
    );

    if (!confirmed) {
        return;
    }

    const btn = document.getElementById('btn-reset-password');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reset...';
    btn.disabled = true;

    try {
        const response = await fetch('/api/servers/reset_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vps_id: serverId })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('success', LANG_MAN.js_reset_pw_success, `${LANG_MAN.js_reset_pw_new} ${data.new_password}`, 10000);
            document.getElementById('vps-password-raw').value = data.new_password;
            document.getElementById('vps-password-display').textContent = '••••••••••';
            setupCopy('copy-password', data.new_password);
        } else {
            showNotification('error', LANG_MAN.js_err, data.message || 'Error');
        }
    } catch (e) {
        console.error(e);
        showNotification('error', 'Error de Comunicación', 'No se pudo conectar con el servidor.');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}
