<!-- Terminal Modal -->
<div class="modal-overlay" id="terminalModal" style="display:none;">
    <div class="modal" style="max-width:780px; width:95vw;">
        <div class="modal-header" style="display:flex; align-items:center; gap:12px;">
            <i class="fas fa-terminal" style="color:var(--primary);"></i>
            <h2 id="terminalModalTitle" style="margin:0; flex:1;">Script Terminal</h2>
            <span id="terminalStatusBadge" class="status-badge" style="font-size:0.78rem;"></span>
            <button class="btn-close" id="terminalCloseBtn" onclick="closeTerminalModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:0; background:#0d0d17; border-radius:0 0 4px 4px;">
            <div id="terminal-xterm" style="padding:10px; height:420px; overflow:hidden;"></div>
        </div>
        <div class="modal-footer" style="justify-content:flex-end;">
            <button class="btn btn-power" id="terminalRunAgainBtn" onclick="terminalRunAgain()" style="display:none;">
                <i class="fas fa-redo"></i> Run Again
            </button>
            <button class="btn btn-manage" id="terminalDoneBtn" onclick="closeTerminalModal()" style="display:none;">
                <i class="fas fa-check"></i> Done
            </button>
        </div>
    </div>
</div>
