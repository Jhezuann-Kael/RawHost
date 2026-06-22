/* scripts_manager.js — inline xterm script runner */

let _term = null;
let _fitAddon = null;
let _sse = null;
let _currentScriptId = null;
let _currentScriptArgs = {};
let _scriptRunning = false;
let _scriptDone = false; // true once 'done' event received — suppresses onerror on natural close

// ── Script list ──────────────────────────────────────────────────────────────

const _scriptsMeta = {}; // id → full script object (including args)

async function loadScriptsList() {
    const container = document.getElementById('scripts-list-container');
    if (!container) return;

    try {
        const res = await fetch(`/api/servers/scripts?id=${serverId}`);
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<p style="color:var(--text-muted); padding:15px;">${data.message || 'Could not load scripts.'}</p>`;
            return;
        }

        if (!data.scripts || data.scripts.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding:30px; color:var(--text-muted);">
                    <i class="fas fa-box-open" style="font-size:2rem; opacity:0.4; display:block; margin-bottom:10px;"></i>
                    No scripts available for <strong>${data.os || 'this OS'}</strong> yet.
                </div>`;
            return;
        }

        const osBadge = data.os !== 'unknown'
            ? `<span style="font-size:0.78rem; color:var(--text-muted); margin-left:8px;"><i class="fab fa-linux"></i> ${data.os}</span>`
            : '';

        const cards = data.scripts.map(s => { _scriptsMeta[s.id] = s; return buildScriptCard(s); }).join('');
        container.innerHTML = `
            <div style="margin-bottom:14px; font-size:0.85rem; color:var(--text-muted);">
                Available for your VPS ${osBadge}
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:14px;">
                ${cards}
            </div>`;
    } catch (e) {
        container.innerHTML = `<p style="color:#ef4444; padding:15px;">Error loading scripts: ${e.message}</p>`;
    }
}

function buildScriptCard(s) {
    const icon    = s.icon || 'fas fa-scroll';
    const hasArgs = s.args && s.args.length > 0;
    const handler = hasArgs
        ? `openScriptArgsModal(this.dataset.scriptId, this.dataset.scriptName)`
        : `openTerminalAndRun(this.dataset.scriptId, this.dataset.scriptName)`;

    return `
        <div style="
            background:rgba(255,255,255,0.04);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:12px;
            padding:18px;
            display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:1.6rem; color:var(--primary);"><i class="${icon}"></i></span>
                <div>
                    <div style="font-weight:700; font-size:0.97rem;">${escHtml(s.name)}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">
                        <i class="fas fa-clock"></i> ~${escHtml(s.estimated_time || '?')}
                        ${s.requires_root ? '&nbsp;<i class="fas fa-shield-alt" title="Requires root"></i> root' : ''}
                        ${hasArgs ? '&nbsp;<i class="fas fa-sliders-h" title="Configurable"></i>' : ''}
                    </div>
                </div>
            </div>
            <p style="font-size:0.83rem; color:var(--text-muted); margin:0; line-height:1.5;">
                ${escHtml(s.description)}
            </p>
            <button class="btn btn-manage" style="width:100%; margin-top:auto;"
                data-script-id="${escHtml(String(s.id))}"
                data-script-name="${escHtml(s.name)}"
                onclick="${handler}">
                <i class="fas fa-play"></i> ${hasArgs ? 'Configure & Run' : 'Run'}
            </button>
        </div>`;
}

// ── Args modal ───────────────────────────────────────────────────────────────

function openScriptArgsModal(scriptId, scriptName) {
    const meta = _scriptsMeta[scriptId];
    if (!meta || !meta.args || meta.args.length === 0) {
        openTerminalAndRun(scriptId, scriptName);
        return;
    }

    // Build or reuse modal
    let modal = document.getElementById('_scriptArgsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = '_scriptArgsModal';
        modal.style.cssText = `
            position:fixed; inset:0; z-index:9999;
            background:rgba(0,0,0,0.6);
            display:flex; align-items:center; justify-content:center;`;
        document.body.appendChild(modal);
    }

    const fields = meta.args.map(arg => {
        const opts = arg.options.map(o =>
            `<option value="${escHtml(o.value)}"${o.value === arg.default ? ' selected' : ''}>${escHtml(o.label)}</option>`
        ).join('');
        return `
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.82rem; color:var(--text-muted); margin-bottom:6px;">
                    ${escHtml(arg.label)}
                </label>
                <select id="_arg_${escHtml(arg.id)}" style="
                    width:100%; padding:9px 12px; border-radius:8px;
                    background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12);
                    color:var(--text-light); font-size:0.9rem; outline:none; cursor:pointer;">
                    ${opts}
                </select>
            </div>`;
    }).join('');

    modal.innerHTML = `
        <div style="
            background:#13131f; border:1px solid rgba(255,255,255,0.1);
            border-radius:16px; padding:28px; width:100%; max-width:420px;
            box-shadow:0 20px 60px rgba(0,0,0,0.5);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; font-size:1.05rem;">
                    <i class="fas fa-sliders-h" style="color:var(--primary); margin-right:8px;"></i>
                    ${escHtml(scriptName)}
                </h3>
                <button onclick="document.getElementById('_scriptArgsModal').style.display='none'"
                    style="background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer; line-height:1;">
                    &times;
                </button>
            </div>
            ${fields}
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button onclick="document.getElementById('_scriptArgsModal').style.display='none'"
                    style="flex:1; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.12);
                           background:rgba(255,255,255,0.05); color:var(--text-muted); cursor:pointer;">
                    Cancel
                </button>
                <button onclick="_runWithArgs('${escHtml(scriptId)}', '${escHtml(scriptName)}')"
                    style="flex:2; padding:10px; border-radius:8px; border:none;
                           background:var(--primary); color:#fff; font-weight:700; cursor:pointer;">
                    <i class="fas fa-play"></i> Run
                </button>
            </div>
        </div>`;

    modal.style.display = 'flex';
    modal.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
}

function _runWithArgs(scriptId, scriptName) {
    const meta = _scriptsMeta[scriptId];
    const collectedArgs = {};

    if (meta && meta.args) {
        for (const arg of meta.args) {
            const el = document.getElementById(`_arg_${arg.id}`);
            if (el) collectedArgs[arg.id] = el.value;
        }
    }

    document.getElementById('_scriptArgsModal').style.display = 'none';
    _currentScriptArgs = collectedArgs;
    openTerminalAndRun(scriptId, scriptName);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Inline terminal ──────────────────────────────────────────────────────────

function openTerminalAndRun(scriptId, scriptName) {
    _currentScriptId   = scriptId;

    document.getElementById('scripts-term-title').textContent = scriptName;
    _setBadge('', '');
    document.getElementById('scripts-term-run-again').style.display = 'none';

    // Swap views
    document.getElementById('scripts-list-view').style.display = 'none';
    document.getElementById('scripts-terminal-view').style.display = 'flex';

    // Remove padding from finder-body so terminal fills edge-to-edge
    const finderBody = document.getElementById('conn-tab-scripts').closest('.finder-body');
    if (finderBody) finderBody.classList.add('terminal-active');

    _initTerminal();
    _runScript(scriptId, _currentScriptArgs);
}

function closeInlineTerminal() {
    _abortSSE();
    _destroyTerminal();
    _currentScriptArgs = {};

    document.getElementById('scripts-terminal-view').style.display = 'none';
    document.getElementById('scripts-list-view').style.display = '';

    const finderBody = document.getElementById('conn-tab-scripts').closest('.finder-body');
    if (finderBody) finderBody.classList.remove('terminal-active');
}

function terminalRunAgain() {
    if (!_currentScriptId || _scriptRunning) return;
    if (_term) _term.clear();
    document.getElementById('scripts-term-run-again').style.display = 'none';
    _setBadge('', '');
    _runScript(_currentScriptId, _currentScriptArgs);
}

// ── xterm init/destroy ───────────────────────────────────────────────────────

function _initTerminal() {
    _destroyTerminal();

    const el = document.getElementById('terminal-xterm');

    _term = new Terminal({
        cursorBlink: false,
        disableStdin: true,
        scrollback: 5000,
        fontSize: 13,
        fontFamily: '"Fira Code", "Cascadia Code", monospace',
        theme: {
            background: '#0d0d17',
            foreground: '#e2e8f0',
            cursor:     '#10b981',
            green:      '#10b981',
            yellow:     '#f59e0b',
            red:        '#ef4444',
            cyan:       '#06b6d4',
        },
    });

    _fitAddon = new FitAddon.FitAddon();
    _term.loadAddon(_fitAddon);
    _term.open(el);

    // Defer fit until browser has painted the now-visible element
    requestAnimationFrame(() => {
        requestAnimationFrame(() => { if (_fitAddon) _fitAddon.fit(); });
    });

    const ro = new ResizeObserver(() => requestAnimationFrame(() => { if (_fitAddon) _fitAddon.fit(); }));
    ro.observe(el);
    _term._ro = ro;
}

function _destroyTerminal() {
    if (_term) {
        if (_term._ro) _term._ro.disconnect();
        _term.dispose();
        _term = null;
        _fitAddon = null;
    }
}

// ── SSE runner ───────────────────────────────────────────────────────────────

function _abortSSE() {
    if (_sse) { _sse.close(); _sse = null; }
    _scriptRunning = false;
    _scriptDone = false;
}

function _runScript(scriptId, args = {}) {
    _abortSSE();
    _scriptRunning = true;
    _scriptDone = false;

    _write(`\x1b[36m${LANG_MAN.scripts_starting}\x1b[0m\r\n`);

    let url = `/api/servers/run_script?server_id=${encodeURIComponent(serverId)}&script_id=${encodeURIComponent(scriptId)}`;
    for (const [k, v] of Object.entries(args)) {
        url += `&args[${encodeURIComponent(k)}]=${encodeURIComponent(v)}`;
    }
    _sse = new EventSource(url);

    _sse.onmessage = function (e) {
        if (!e.data) return;
        try {
            const p = JSON.parse(e.data);
            if (p.type === 'output') {
                _write(p.msg.replace(/\r?\n/g, '\r\n'));
            } else if (p.type === 'error') {
                _write(`\x1b[31m✖ ${p.msg}\x1b[0m\r\n`);
            } else if (p.type === 'done') {
                _scriptDone = true;
                _sse.close(); _sse = null;
                _scriptRunning = false;
                _onDone(p.ok, p.msg);
            }
        } catch (_) {}
    };

    _sse.onerror = function () {
        if (_scriptDone) return;
        _sse.close(); _sse = null;
        _scriptRunning = false;
        _write(`\r\n\x1b[90m${LANG_MAN.scripts_conn_ended}\x1b[0m\r\n`);
        _setBadge('neutral', LANG_MAN.scripts_badge_ended);
        document.getElementById('scripts-term-run-again').style.display = 'inline-flex';
    };
}

function _write(text) {
    if (_term) _term.write(text);
}

function _onDone(ok, msg) {
    if (ok) {
        _write(`\r\n\x1b[32m✔ ${msg || LANG_MAN.scripts_badge_ok}\x1b[0m\r\n`);
        _setBadge('ok', LANG_MAN.scripts_badge_ok);
    } else {
        _write(`\r\n\x1b[31m✖ ${msg || LANG_MAN.scripts_badge_fail}\x1b[0m\r\n`);
        _setBadge('error', LANG_MAN.scripts_badge_fail);
    }
    document.getElementById('scripts-term-run-again').style.display = 'inline-flex';
}

function _setBadge(type, label) {
    const badge = document.getElementById('scripts-term-badge');
    badge.textContent = label;
    if (type === 'ok') {
        badge.style.cssText = 'background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3);';
    } else if (type === 'error') {
        badge.style.cssText = 'background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);';
    } else if (type === 'neutral') {
        badge.style.cssText = 'background:rgba(148,163,184,0.12); color:#94a3b8; border:1px solid rgba(148,163,184,0.25);';
    } else {
        badge.style.cssText = '';
    }
}

// ── Auto-load ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const interval = setInterval(() => {
        const mc = document.getElementById('main-content');
        if (mc && !mc.classList.contains('hidden')) {
            clearInterval(interval);
            loadScriptsList();
        }
    }, 500);
});
