/* docker_manager.js — Docker Manager page logic */

// ── Containers ────────────────────────────────────────────────────────────────

async function loadContainers() {
    const list = document.getElementById('containers-list');
    const btn  = document.getElementById('btn-refresh-docker');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    }

    try {
        const res  = await fetch(`../../api/servers/docker_ps?id=${serverId}`);
        const data = await res.json();

        if (!data.success) {
            list.innerHTML = `
                <div class="dc-state dc-state--error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${data.message}</p>
                    ${data.error ? `<pre class="dc-error-detail">${data.error}</pre>` : ''}
                </div>`;
            return;
        }

        if (data.containers.length === 0) {
            list.innerHTML = `
                <div class="dc-state dc-state--empty">
                    <i class="fas fa-ghost"></i>
                    <p>No running containers found.</p>
                </div>`;
            return;
        }

        list.innerHTML = renderComposeGroups(data.containers);
    } catch (e) {
        list.innerHTML = `<div class="dc-state dc-state--error"><p>Error: ${e.message}</p></div>`;
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
        }
    }
}

function renderComposeGroups(containers) {
    // Group by compose_project; standalone containers use sentinel key
    const groups = new Map();
    containers.forEach(c => {
        const key = c.compose_project || '__standalone__';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(c);
    });

    // Compose groups first (alphabetical), standalone last
    const sorted = [...groups.entries()].sort(([a], [b]) => {
        if (a === '__standalone__') return 1;
        if (b === '__standalone__') return -1;
        return a.localeCompare(b);
    });

    return `<div class="dc-groups">${sorted.map(([project, ctrs]) => renderGroup(project, ctrs)).join('')}</div>`;
}

function renderGroup(project, ctrs) {
    const isStandalone = project === '__standalone__';
    const name  = isStandalone ? 'Standalone' : project;
    const icon  = isStandalone ? 'fa-cube' : 'fa-layer-group';
    const extra = isStandalone ? ' dc-group-icon--standalone' : '';
    const rows  = ctrs.map((c, i) => renderContainerRow(c, i === ctrs.length - 1)).join('');
    const runningCount = ctrs.filter(c => c.status.toLowerCase().startsWith('up')).length;
    const totalCount   = ctrs.length;
    const countLabel   = runningCount === totalCount
        ? `${totalCount}`
        : `${runningCount}/${totalCount}`;
    const allUp = runningCount === totalCount;

    return `
    <div class="dc-group">
        <div class="dc-group-header" onclick="dcGroupToggle(this)">
            <i class="fas fa-chevron-down dc-chevron"></i>
            <i class="fas ${icon} dc-group-icon${extra}"></i>
            <span class="dc-group-name">${name}</span>
            <span class="dc-group-count dc-group-count--${allUp ? 'up' : 'partial'}">${countLabel} running</span>
        </div>
        <div class="dc-group-body">
            ${rows}
        </div>
    </div>`;
}

function renderContainerRow(c, isLast) {
    const isUp       = c.status.toLowerCase().startsWith('up');
    const dotClass   = isUp ? 'dc-dot--up' : 'dc-dot--down';
    const name       = c.compose_service || c.name;
    const image      = c.image ? `<span class="dc-row-image">${c.image}</span>` : '';
    const connector  = isLast ? '└' : '├';

    const ports = c.ports
        ? c.ports.split(',').filter(Boolean).map(p => `<span class="port-badge">${p.trim()}</span>`).join('')
        : '';

    return `
    <div class="dc-row" data-container="${c.name}" onclick="location.href='container?id=${serverId}&container=${c.name}'" title="View logs">
        <span class="dc-connector">${connector}</span>
        <span class="dc-dot ${dotClass}"></span>
        <div class="dc-row-info">
            <span class="dc-row-name">${name}</span>
            <span class="dc-row-meta">${c.status}${c.image ? ' · ' + c.image.split('/').pop() : ''}</span>
        </div>
        <div class="dc-row-ports">${ports}</div>
        <div class="dc-row-actions" onclick="event.stopPropagation()">
            <div id="dca-${c.name}" class="dca-wrap">
                <button class="dca-trigger" onclick="dcaToggle('${c.name}')" title="Remove container">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <div class="dca-confirm" id="dca-confirm-${c.name}" style="display:none;">
                    <span>Remove?</span>
                    <button class="dca-yes dca-yes--container" onclick="dcaRemove('${c.name}', this, false)" title="Remove container only"><i class="fas fa-trash-alt"></i> Container</button>
                    <button class="dca-yes dca-yes--volume"    onclick="dcaRemove('${c.name}', this, true)"  title="Remove container + volumes"><i class="fas fa-trash-alt"></i> +Volume</button>
                    <button class="dca-no"  onclick="dcaToggle('${c.name}')"><i class="fas fa-times"></i></button>
                </div>
            </div>
        </div>
    </div>`;
}

function dcGroupToggle(header) {
    const body    = header.nextElementSibling;
    const chevron = header.querySelector('.dc-chevron');
    const collapsed = body.classList.toggle('dc-group-body--collapsed');
    chevron.classList.toggle('dc-chevron--collapsed', collapsed);
}

// ── Docker Scripts ────────────────────────────────────────────────────────────

async function loadDockerScripts() {
    const container = document.getElementById('docker-scripts-container');
    try {
        const res  = await fetch(`../../api/servers/scripts?id=${serverId}`);
        const data = await res.json();
        if (!data.success) { container.innerHTML = `<p>${data.message}</p>`; return; }

        const dockerScripts = data.scripts.filter(s => {
            const name = s.name.toLowerCase();
            const desc = s.description.toLowerCase();
            const cat  = (s.category ?? '').toLowerCase();
            return name.includes('docker') || desc.includes('docker') ||
                   cat.includes('docker')  || cat.includes('containers');
        });

        if (dockerScripts.length === 0) {
            container.innerHTML = `<p style="color:var(--text-muted); text-align:center; padding:20px;">No docker scripts available for this OS.</p>`;
            return;
        }

        dockerScripts.forEach(s => { _scriptsMeta[s.id] = s; });

        const groups = {};
        dockerScripts.forEach(s => {
            const cat = s.category || 'General';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(s);
        });

        container.innerHTML = Object.entries(groups).map(([cat, scripts]) => `
            <div class="docker-scripts-category">
                <div class="docker-scripts-category-label">${cat}</div>
                <div class="docker-scripts-list">
                    ${scripts.map(s => buildDockerScriptCard(s)).join('')}
                </div>
            </div>`).join('');
    } catch (e) {
        container.innerHTML = `<p>Error loading scripts: ${e.message}</p>`;
    }
}

function buildDockerScriptCard(s) {
    const time     = s.estimated_time ? `<span class="dsc-time">· ${s.estimated_time}</span>` : '';
    const hasArgs  = s.args && s.args.length > 0;
    const safeName = s.name.replace(/'/g, "\\'");
    const handler  = hasArgs
        ? `openScriptArgsModal('${s.id}', '${safeName}')`
        : `openTerminalAndRun('${s.id}', '${safeName}')`;
    const btnIcon  = hasArgs ? 'fa-sliders-h' : 'fa-play';

    return `
        <div class="docker-script-card" onclick="${handler}">
            <span class="dsc-icon"><i class="${s.icon || 'fab fa-docker'}"></i></span>
            <div class="dsc-body">
                <div class="dsc-name">${s.name}</div>
                <div class="dsc-sub">
                    <span class="dsc-desc">${s.description}</span>
                    ${time}
                </div>
            </div>
            <button class="btn btn-manage dsc-btn" onclick="event.stopPropagation(); ${handler}">
                <i class="fas ${btnIcon}"></i>
            </button>
        </div>`;
}

// ── Container actions ─────────────────────────────────────────────────────────

function dcaToggle(name) {
    const confirm = document.getElementById(`dca-confirm-${name}`);
    const trigger = document.querySelector(`#dca-${name} .dca-trigger`);
    const open    = confirm.style.display === 'none';
    confirm.style.display = open ? 'flex' : 'none';
    trigger.style.display = open ? 'none' : '';
}

async function dcaRemove(name, btn, withVolume = false) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const action = withVolume ? 'remove_with_volume' : 'remove';

    try {
        const res  = await fetch('../../api/servers/docker_action', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    `id=${encodeURIComponent(serverId)}&container=${encodeURIComponent(name)}&action=${action}`,
        });
        const data = await res.json();

        if (data.success) {
            const row = document.querySelector(`.dc-row[data-container="${name}"]`);
            if (row) {
                row.style.transition = 'opacity 0.25s';
                row.style.opacity = '0';
                setTimeout(() => {
                    const group = row.closest('.dc-group');
                    row.remove();
                    // Remove group header if no rows remain
                    if (group && group.querySelectorAll('.dc-row').length === 0) {
                        group.style.transition = 'opacity 0.2s';
                        group.style.opacity = '0';
                        setTimeout(() => group.remove(), 200);
                    }
                }, 250);
            }
        } else {
            alert(data.message || 'Failed to remove container');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i>';
        }
    } catch (e) {
        alert('Error: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i>';
    }
}

// ── Terminal overrides ────────────────────────────────────────────────────────

window._initTerminal = function () {
    if (typeof _destroyTerminal === 'function') _destroyTerminal();

    const el = document.getElementById('terminal-xterm');
    if (!el) return;

    _term = new Terminal({
        cols: 80,
        rows: 24,
        cursorBlink: false,
        disableStdin: true,
        scrollback: 5000,
        fontSize: 13,
        fontFamily: 'monospace',
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

    _term.open(el);

    setTimeout(() => {
        if (!_term) return;
        _term.options.fontFamily = '"Fira Code", "Cascadia Code", monospace';
        _fitAddon = new FitAddon.FitAddon();
        _term.loadAddon(_fitAddon);
        if (el.offsetParent) {
            try { _fitAddon.fit(); } catch (_) {}
        }
    }, 400);
};

window.openTerminalAndRun = function (id, name) {
    _currentScriptId = id;

    document.getElementById('docker-main-view').style.display = 'none';
    document.getElementById('scripts-terminal-view').style.display = 'flex';
    document.getElementById('scripts-term-title').textContent = name;
    document.getElementById('scripts-term-run-again').style.display = 'none';
    if (typeof _setBadge === 'function') _setBadge('', '');

    setTimeout(() => {
        _initTerminal();
        setTimeout(() => {
            if (typeof _runScript === 'function') _runScript(id, _currentScriptArgs);
        }, 200);
    }, 200);
};

window.closeInlineTerminal = function () {
    if (typeof _abortSSE === 'function') _abortSSE();
    if (typeof _destroyTerminal === 'function') _destroyTerminal();
    _currentScriptArgs = {};

    document.getElementById('scripts-terminal-view').style.display = 'none';
    document.getElementById('docker-main-view').style.display = '';
};

// ── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadContainers();
    loadDockerScripts();

    window.addEventListener('resize', () => {
        if (typeof _term !== 'undefined' && _term && _term.element && _term.element.offsetParent && _fitAddon) {
            try { _fitAddon.fit(); } catch (_) {}
        }
    });
});
