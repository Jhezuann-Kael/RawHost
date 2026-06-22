<!-- VNC Fullscreen Modal -->
<div class="vnc-modal-overlay" id="vncModal">
    <div class="vnc-modal-content">
        <div class="vnc-modal-header">
            <h3 style="margin: 0; color: var(--text-light);">
                <i class="fas fa-desktop"></i> <?php echo $lang['man_modal_vnc_title']; ?>
            </h3>
            <button class="btn-close" onclick="closeVncModal()"
                style="background: transparent; border: none; color: var(--text-light); font-size: 1.5rem; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="vnc-modal-body">
            <div id="vnc-modal-placeholder"
                style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; gap:15px;">
                <i class="fas fa-desktop" style="font-size: 4rem; color: var(--text-muted); opacity: 0.5;"></i>
                <button class="btn btn-outline" id="btn-vnc-modal-connect" onclick="connectVncModal()"
                    style="border-radius: 8px;">
                    <i class="fas fa-plug"></i> <?php echo $lang['man_modal_vnc_connect']; ?>
                </button>
            </div>

            <div id="vnc-modal-details" class="hidden" style="height: 100%; display: flex; flex-direction: column;">
                <!-- Canvas for NoVNC -->
                <div id="vnc-modal-screen"
                    style="flex: 1; background: #000; border: 1px solid #333; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                    <div id="vnc-modal-status-text" style="color: #666;">Ready to Connect</div>
                </div>

                <div
                    style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; flex-wrap: wrap; gap: 10px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                    <!-- Control Bar -->
                    <div style="display: flex; gap: 15px;">
                        <button id="btn-vnc-modal-keyboard" class="btn-icon-tiny" onclick="toggleVncModalKeyboard()"
                            title="Virtual Keyboard">
                            <i class="fas fa-keyboard"></i> <?php echo $lang['man_modal_vnc_keyboard']; ?>
                        </button>
                        <button id="btn-vnc-modal-ctrlaltdel" class="btn-icon-tiny" onclick="sendCtrlAltDelModal()"
                            title="Send Ctrl-Alt-Del">
                            <i class="fas fa-power-off"></i> Ctrl+Alt+Del
                        </button>
                    </div>
                    <div style="color: var(--text-muted);">
                        Password: <span id="vnc-modal-password" style="color: var(--text-light);">...</span>
                        <button class="btn-icon-tiny" id="copy-vnc-modal-pass" title="Copiar"><i
                                class="fas fa-copy"></i></button>
                    </div>
                    <div>
                        <button id="btn-vnc-modal-disconnect" class="btn-icon-tiny" onclick="disconnectVncModal()"
                            title="Disconnect" style="color: #ef4444;">
                            <i class="fas fa-times"></i> <?php echo $lang['man_modal_vnc_disconnect']; ?>
                        </button>
                    </div>
                </div>
            </div>

            <div id="vnc-modal-error" class="hidden" style="color: #ef4444; padding: 20px; text-align: center;"></div>
        </div>
    </div>
</div>
