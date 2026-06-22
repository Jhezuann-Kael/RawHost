/* profile.js */
(function () {

    const ALLOWED_REFERRAL_USERS = [19, 20, 22, 23];

    /* ── API ── */
    async function loadProfile() {
        const loading = document.getElementById('profileLoading');
        const content = document.getElementById('profileContent');

        try {
            const res  = await fetch('../api/users/me');
            const data = await res.json();

            if (!data.success) throw new Error(data.message);

            const u = data.data;

            /* Avatar */
            if (u.profile_picture) {
                const img  = document.getElementById('avatarImg');
                const icon = document.getElementById('avatarIcon');
                img.src = u.profile_picture + '?v=' + Date.now();
                img.style.display = 'block';
                if (icon) icon.style.display = 'none';
            }

            setText('pUsername', u.username || LANG_PROF.user_fallback);
            const dnEl = document.getElementById('pDisplayName');
            if (dnEl) {
                dnEl.value = u.display_name || '';
                const u_ = u.username || 'you';
                const suggestions = [
                    u_ + 'Rawhost', 'Real' + u_, u_ + 'NotRam',
                    u_ + '404', u_ + 'Offshore', u_ + 'NoLogs',
                ];
                dnEl.placeholder = 'e.g. ' + suggestions[Math.floor(Math.random() * suggestions.length)];
            }

            const emailEl = document.getElementById('pEmail');
            if (u.email && u.email.trim()) {
                emailEl.textContent = u.email;
            } else {
                emailEl.style.display = 'none';
            }

            setText('pId',      '#' + u.id);
            setText('pBalance', '$' + parseFloat(u.balance || 0).toFixed(2));

            try {
                setText('pJoined', new Date(u.created_at).toLocaleDateString());
            } catch (_) {}

            /* Telegram */
            const tgEl     = document.getElementById('pTelegram');
            const tgWidget = document.getElementById('tg-widget');
            if (u.telegram_id) {
                const tgDisplay = u.tg_username ? `@${u.tg_username}` : u.telegram_id;
                tgEl.innerHTML =
                    `<span style="color:var(--success);display:flex;align-items:center;gap:5px;">
                        <i class="fas fa-check-circle"></i> ${LANG_PROF.connected}
                        <span style="color:var(--text-muted);font-size:.8em;">(${tgDisplay})</span>
                        <button onclick="unlinkTelegram()" style="margin-left:6px;background:none;border:none;color:#ef4444;cursor:pointer;font-size:.78rem;padding:2px 6px;border-radius:4px;border:1px solid rgba(239,68,68,.3);" title="Unlink Telegram">
                            <i class="fas fa-unlink"></i>
                        </button>
                    </span>`;
                tgEl.style.display = '';
                if (tgWidget) tgWidget.style.display = 'none';
            } else {
                tgEl.style.display = 'none';
                if (tgWidget) tgWidget.style.display = '';
            }

            /* Admin */
            if (u.is_superuser == 1 || u.is_superuser === true) {
                show('crownIcon');
                show('adminBadge');
            }

            /* Referral */
            if (ALLOWED_REFERRAL_USERS.includes(parseInt(u.id))) {
                show('referralSection');
                if (u.referral_code) {
                    setText('referralCode', u.referral_code);
                    show('referralCodeWrap');
                    hide('referralGenBtn');
                    if (u.referrals_count !== undefined) {
                        const valid = u.valid_referrals_count || 0;
                        document.getElementById('referralStats').style.display = 'block';
                        document.getElementById('referralCount').innerHTML =
                            `${u.referrals_count} <span style="color:var(--text-muted);font-size:.9em;">${LANG_PROF.total}</span>
                             &nbsp;|&nbsp;
                             <span style="color:#ffd700;">${valid}</span> <span style="color:var(--text-muted);font-size:.9em;">${LANG_PROF.valid}</span>`;
                    }
                } else {
                    hide('referralCodeWrap');
                    show('referralGenBtn');
                }
            }

            content.style.display = '';
        } catch (e) {
            console.error(e);
            content.innerHTML = `<p style="text-align:center;color:#f87171;padding:40px">${LANG_PROF.err_conn_load}</p>`;
            content.style.display = '';
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    /* ── Referral ── */
    window.generateReferralCode = async function () {
        const btn = document.getElementById('referralGenBtn');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_PROF.generating}`;
        try {
            const res  = await fetch('../api/users/generate_referral', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                setText('referralCode', data.code);
                show('referralCodeWrap');
                hide('referralGenBtn');
                alert(LANG_PROF.gen_success);
            } else {
                alert(LANG_PROF.err + ' ' + data.message);
            }
        } catch (e) {
            alert(LANG_PROF.err_conn);
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    };

    window.copyReferralCode = function () {
        const code = document.getElementById('referralCode').textContent;
        navigator.clipboard.writeText(code).then(() => alert(LANG_PROF.copy_success + ' ' + code));
    };

    /* ── Password modal ── */
    window.openPassModal = function () {
        ['currPass','newPass','confPass'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.value = ''; el.type = 'password'; }
        });
        setEyeIcon('currPass', false);
        setEyeIcon('newPass',  false);
        setEyeIcon('confPass', false);
        hideMsg('passError');
        hideMsg('passSuccess');
        document.getElementById('passOverlay').classList.add('open');
        document.getElementById('passModal').classList.add('open');
    };

    window.closePassModal = function () {
        document.getElementById('passModal').classList.remove('open');
        document.getElementById('passOverlay').classList.remove('open');
    };

    window.toggleEye = function (id) {
        const el = document.getElementById(id);
        const isPass = el.type === 'password';
        el.type = isPass ? 'text' : 'password';
        setEyeIcon(id, isPass);
    };

    window.submitPassChange = async function () {
        const curr = val('currPass');
        const n1   = val('newPass');
        const n2   = val('confPass');
        const btn  = document.getElementById('btnSavePass');

        hideMsg('passError');
        hideMsg('passSuccess');

        if (!curr || !n1 || !n2) return;

        if (n1 !== n2) {
            showMsg('passError', LANG_PROF.pass_mismatch);
            return;
        }
        if (n1.length < 6) {
            showMsg('passError', LANG_PROF.pass_len);
            return;
        }

        const orig = btn.textContent;
        btn.disabled = true;
        btn.textContent = '...';

        try {
            const res  = await fetch('../api/users/modify_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: curr, new_password: n1 })
            });
            const data = await res.json();

            if (data.status === 'success') {
                showMsg('passSuccess', LANG_PROF.pass_success);
                setTimeout(closePassModal, 1500);
            } else {
                showMsg('passError', data.message);
            }
        } catch (_) {
            showMsg('passError', 'Error');
        } finally {
            btn.disabled = false;
            btn.textContent = orig;
        }
    };

    /* ── Helpers ── */
    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }
    function show(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'block';
    }
    function hide(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }
    function val(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }
    function showMsg(id, text) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = text;
        el.style.display = 'block';
    }
    function hideMsg(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }
    function setEyeIcon(inputId, visible) {
        const el = document.getElementById('eye_' + inputId);
        if (el) el.className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
    }

    /* ── Payment preference ── */
    async function loadCurrencies() {
        const select = document.getElementById('prefCurrencySelect');
        if (!select) return;

        try {
            const [currRes, prefRes] = await Promise.all([
                fetch('../api/transactions/currencies'),
                fetch('../api/users/preferences'),
            ]);
            const currData = await currRes.json();
            const prefData = await prefRes.json();

            const saved = prefData.success ? (prefData.data.preferred_currency || '') : '';

            // Load auto_renew state
            if (prefData.success) {
                const toggle = document.getElementById('autoRenewToggle');
                if (toggle) toggle.checked = !!prefData.data.auto_renew;
            }

            const raw = currData.data || currData;
            const list = Array.isArray(raw) ? raw : Object.values(raw);

            list.forEach(item => {
                const sym  = item.symbol || item.currency || '';
                const nets = item.networks
                    ? (Array.isArray(item.networks) ? item.networks : Object.values(item.networks))
                    : [];
                nets.forEach(net => {
                    const network = net.network || net;
                    const value   = `${sym}:${network}`;
                    const opt     = document.createElement('option');
                    opt.value       = value;
                    opt.textContent = `${sym} (${network})`;
                    if (value === saved) opt.selected = true;
                    select.appendChild(opt);
                });
            });
        } catch (e) {
            console.error('loadCurrencies', e);
        }
    }

    window.savePreference = async function () {
        const select  = document.getElementById('prefCurrencySelect');
        const msgEl   = document.getElementById('prefMsg');
        const btn     = document.getElementById('btnSavePref');
        const orig    = btn.innerHTML;

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        msgEl.textContent = '';

        try {
            const res  = await fetch('../api/users/preferences', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ preferred_currency: select.value || null }),
            });
            const data = await res.json();

            msgEl.style.color = data.success ? 'var(--success)' : '#f87171';
            msgEl.textContent = data.success
                ? (LANG_PROF.pref_saved ?? 'Saved!')
                : (data.message || 'Error');
        } catch (_) {
            msgEl.style.color = '#f87171';
            msgEl.textContent = LANG_PROF.err_conn;
        } finally {
            btn.disabled  = false;
            btn.innerHTML = orig;
        }
    };

    window.saveAutoRenew = async function (enabled) {
        const msgEl = document.getElementById('autoRenewMsg');
        msgEl.textContent = '';
        try {
            const res  = await fetch('../api/users/preferences', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ auto_renew: enabled }),
            });
            const data = await res.json();
            msgEl.style.color = data.success ? 'var(--success)' : '#f87171';
            msgEl.textContent = data.success ? (enabled ? 'Auto-renovación activada.' : 'Auto-renovación desactivada.') : (data.message || 'Error');
            setTimeout(() => { msgEl.textContent = ''; }, 3000);
        } catch (_) {
            msgEl.style.color = '#f87171';
            msgEl.textContent = 'Error de conexión.';
        }
    };

    /* ── Avatar upload ── */
    window.uploadAvatar = async function (input) {
        if (!input.files || !input.files[0]) return;

        const wrap = document.getElementById('avatarWrap');
        const orig = wrap ? wrap.style.opacity : '';
        if (wrap) wrap.style.opacity = '0.5';

        const fd = new FormData();
        fd.append('avatar', input.files[0]);

        try {
            const res  = await fetch('../api/users/upload_avatar', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                const img  = document.getElementById('avatarImg');
                const icon = document.getElementById('avatarIcon');
                img.src = data.url + '?v=' + Date.now();
                img.style.display = 'block';
                if (icon) icon.style.display = 'none';
            } else {
                alert(data.message || 'Error al subir la imagen');
            }
        } catch (e) {
            alert(LANG_PROF.err_conn);
        } finally {
            if (wrap) wrap.style.opacity = orig || '1';
            input.value = '';
        }
    };

    window.unlinkTelegram = async function () {
        if (!confirm('Unlink Telegram from this account?')) return;
        try {
            const res  = await fetch('../api/auth/telegram_unlink', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                const tgEl     = document.getElementById('pTelegram');
                const tgWidget = document.getElementById('tg-widget');
                if (tgEl)     tgEl.style.display = 'none';
                if (tgWidget) tgWidget.style.display = '';
            }
        } catch (e) {
            console.error(e);
        }
    };

    function handleTgFlash() {
        if (!window.TG_STATUS || !TG_STATUS.status) return;
        const map = {
            linked:       ['success', '<i class="fas fa-check-circle"></i> Telegram vinculado correctamente.'],
            already_used: ['error',   '<i class="fas fa-times-circle"></i> Esa cuenta de Telegram ya está vinculada a otro usuario.'],
            error:        ['error',   '<i class="fas fa-times-circle"></i> ' + (TG_STATUS.msg || 'Error al vincular Telegram.')],
        };
        const [type, html] = map[TG_STATUS.status] || [];
        if (!type) return;
        const n = document.createElement('div');
        n.innerHTML = html;
        Object.assign(n.style, {
            position:'fixed', top:'20px', right:'20px', zIndex:9999,
            background: type === 'success' ? 'rgba(16,185,129,.15)' : 'rgba(239,68,68,.15)',
            border: `1px solid ${type === 'success' ? '#10b981' : '#ef4444'}`,
            color: type === 'success' ? '#10b981' : '#ef4444',
            padding:'12px 18px', borderRadius:'10px', fontSize:'.9rem',
            boxShadow:'0 4px 16px rgba(0,0,0,.3)', display:'flex', alignItems:'center', gap:'8px',
        });
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 5000);
        // Clean URL
        history.replaceState(null, '', location.pathname);
    }

    async function saveDisplayName() {
        const input = document.getElementById('pDisplayName');
        const msgEl = document.getElementById('pDisplayNameMsg');
        const val   = (input.value || '').trim();

        msgEl.style.display = 'none';
        try {
            const res  = await fetch('../api/users/preferences', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ display_name: val || null }),
            });
            const data = await res.json();
            msgEl.style.display  = '';
            msgEl.style.color    = data.success ? '#10b981' : '#ef4444';
            msgEl.textContent    = data.success ? '✓ Saved' : (data.message || 'Error');
            setTimeout(() => { msgEl.style.display = 'none'; }, 2500);
        } catch {
            msgEl.style.display = '';
            msgEl.style.color   = '#ef4444';
            msgEl.textContent   = 'Connection error';
        }
    }
    window.saveDisplayName = saveDisplayName;

    document.addEventListener('DOMContentLoaded', () => { loadProfile(); loadCurrencies(); handleTgFlash(); });
})();
