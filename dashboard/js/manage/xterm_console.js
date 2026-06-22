/* xterm_console.js — interactive SSH console tab */

let _cTerm         = null;
let _cFit          = null;
let _cSessionId    = null;
let _cSSE          = null;
let _cOffset       = 0;
let _cConnected    = false;
let _cConnecting   = false;
let _cInputBuf     = [];
let _cInputSending = false;

// ── Terminal init/destroy ─────────────────────────────────────────────────────

function _cInitTerm() {
    if (_cTerm) return;

    const el = document.getElementById('console-xterm');

    _cTerm = new Terminal({
        cursorBlink : true,
        disableStdin: false,
        scrollback  : 8000,
        fontSize    : 13,
        fontFamily  : '"Fira Code", "Cascadia Code", monospace',
        theme: {
            background: '#0d0d17',
            foreground: '#e2e8f0',
            cursor    : '#10b981',
            green     : '#10b981',
            yellow    : '#f59e0b',
            red       : '#ef4444',
            cyan      : '#06b6d4',
        },
    });

    _cFit = new FitAddon.FitAddon();
    _cTerm.loadAddon(_cFit);
    _cTerm.open(el);

    requestAnimationFrame(() => requestAnimationFrame(() => { if (_cFit) _cFit.fit(); }));

    // Wrap in rAF to avoid "ResizeObserver loop completed with undelivered notifications"
    const ro = new ResizeObserver(() => requestAnimationFrame(() => { if (_cFit) _cFit.fit(); }));
    ro.observe(el);
    _cTerm._ro = ro;

    _cTerm.onData(data => {
        if (!_cConnected || !_cSessionId) return;
        _cQueueInput(data);
    });
}

function _cDestroyTerm() {
    if (_cTerm) {
        if (_cTerm._ro) _cTerm._ro.disconnect();
        _cTerm.dispose();
        _cTerm = null;
        _cFit  = null;
    }
}

// ── Input batching ────────────────────────────────────────────────────────────

function _cQueueInput(data) {
    _cInputBuf.push(data);
    if (!_cInputSending) _cFlushInput();
}

async function _cFlushInput() {
    if (_cInputSending || _cInputBuf.length === 0) return;
    _cInputSending = true;

    const chunk = _cInputBuf.splice(0).join('');
    try {
        await fetch('/api/servers/ssh_input', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : `session_id=${encodeURIComponent(_cSessionId)}&data=${encodeURIComponent(chunk)}`,
        });
    } catch (_) {}

    _cInputSending = false;
    if (_cInputBuf.length > 0) _cFlushInput();
}

// ── SSE output stream ─────────────────────────────────────────────────────────

function _cStartOutput() {
    if (_cSSE) { _cSSE.close(); _cSSE = null; }

    const url = `/api/servers/ssh_output?session_id=${encodeURIComponent(_cSessionId)}&offset=${_cOffset}`;
    _cSSE = new EventSource(url);

    _cSSE.onmessage = function (e) {
        if (!e.data) return;
        try {
            const p = JSON.parse(e.data);
            if (p.type === 'output') {
                _cOffset = p.offset;
                const bytes = Uint8Array.from(atob(p.data), c => c.charCodeAt(0));
                if (_cTerm) _cTerm.write(bytes);
            } else if (p.type === 'reconnect') {
                _cOffset = p.offset;
                _cSSE.close(); _cSSE = null;
                setTimeout(_cStartOutput, 80);
            } else if (p.type === 'closed') {
                _cSSE.close(); _cSSE = null;
                _cOnDisconnected();
            } else if (p.type === 'error') {
                if (_cTerm) _cTerm.write(`\r\n\x1b[31m✖ ${p.msg}\x1b[0m\r\n`);
                _cSSE.close(); _cSSE = null;
                _cOnDisconnected();
            }
        } catch (_) {}
    };

    _cSSE.onerror = function () {
        if (_cConnected) {
            _cSSE.close(); _cSSE = null;
            setTimeout(_cStartOutput, 1500);
        }
    };
}

// ── Connect / disconnect ──────────────────────────────────────────────────────

async function consoleConnect() {
    if (_cConnecting || _cConnected) return;
    _cConnecting = true;

    // Show terminal, hide placeholder
    document.getElementById('console-placeholder').style.display = 'none';
    document.getElementById('console-xterm').style.display = '';

    _cInitTerm();
    _cTerm.write('\x1b[36mConnecting to server...\x1b[0m\r\n');

    try {
        const res  = await fetch('/api/servers/ssh_connect', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : `server_id=${encodeURIComponent(serverId)}`,
        });
        const data = await res.json();

        if (!data.success) {
            _cTerm.write(`\r\n\x1b[31m✖ ${data.message || 'Connection failed'}\x1b[0m\r\n`);
            _cConnecting = false;
            _cOnDisconnected();
            return;
        }

        _cSessionId  = data.session_id;
        _cOffset     = 0;
        _cConnected  = true;
        _cConnecting = false;
        document.getElementById('console-disconnect-btn').style.display = '';
        _cStartOutput();

    } catch (err) {
        if (_cTerm) _cTerm.write(`\r\n\x1b[31m✖ Network error: ${err.message}\x1b[0m\r\n`);
        _cConnecting = false;
        _cOnDisconnected();
    }
}

async function consoleDisconnect() {
    if (!_cSessionId) return;

    _cConnected = false;
    if (_cSSE) { _cSSE.close(); _cSSE = null; }

    await fetch('/api/servers/ssh_close', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : `session_id=${encodeURIComponent(_cSessionId)}`,
    }).catch(() => {});

    _cSessionId = null;
    _cOffset    = 0;
    if (_cTerm) _cTerm.write('\r\n\x1b[90m[Disconnected]\x1b[0m\r\n');
    _cOnDisconnected();
}

function _cOnDisconnected() {
    _cConnected = false;
    _cConnecting = false;
    document.getElementById('console-disconnect-btn').style.display = 'none';

    // Return to placeholder, destroy terminal
    _cDestroyTerm();
    document.getElementById('console-xterm').style.display = 'none';
    document.getElementById('console-placeholder').style.display = '';
}

// ── Tab activation (called by the tab button) ─────────────────────────────────

function onConsoleTabActivated() {
    if (_cFit) requestAnimationFrame(() => _cFit.fit());
}

// ── Public: send a command line to the active session ─────────────────────────

function consoleSendCommand(cmd) {
    if (!_cConnected || !_cSessionId) return;
    _cQueueInput(cmd + '\n');
}

// Clean up on page leave
window.addEventListener('beforeunload', () => {
    if (_cSessionId) {
        navigator.sendBeacon(
            '/api/servers/ssh_close',
            new URLSearchParams({ session_id: _cSessionId })
        );
    }
});
