<!-- Reinstall OS Modal -->
<div class="modal-overlay" id="reinstallModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-compact-disc"></i> <?php echo $lang['man_modal_reinstall_title']; ?></h2>
            <button class="btn-close" onclick="closeReinstallModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="color: #ef4444; margin-bottom: 20px; font-size: 0.9rem; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.2);">
                <i class="fas fa-exclamation-triangle"></i>
                <strong><?php echo $lang['man_modal_reinstall_warn']; ?></strong>
            </p>

            <div style="display:flex; gap:8px; margin-bottom:14px;">
                <button class="stats-tab active" id="reinstall-tab-os" onclick="reinstallTabSwitch('os')">
                    <i class="fas fa-compact-disc"></i> <?php echo $lang['vps_label_os'] ?? 'OS'; ?>
                </button>
                <button class="stats-tab" id="reinstall-tab-app" onclick="reinstallTabSwitch('app')">
                    <i class="fas fa-th-large"></i> <?php echo $lang['vps_label_apps'] ?? 'Apps'; ?>
                </button>
            </div>

            <div id="reinstall-os-pane">
                <div id="osGrid" class="grid-selection">
                    <div style="text-align: center; color: var(--text-muted); padding: 20px; grid-column: 1/-1;">
                        <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> <?php echo $lang['man_loading']; ?>
                    </div>
                </div>
            </div>

            <div id="reinstall-app-pane" style="display:none;">
                <div id="appGrid" class="grid-selection">
                    <div style="text-align: center; color: var(--text-muted); padding: 20px; grid-column: 1/-1;">
                        <i class="fas fa-spinner fa-spin" style="color:var(--primary);"></i> <?php echo $lang['man_loading']; ?>
                    </div>
                </div>
            </div>

            <input type="hidden" id="selectedOsId">
            <input type="hidden" id="selectedAppId">
            <input type="hidden" id="reinstallType" value="os">
        </div>
        <div class="modal-footer">
            <button class="btn btn-power" onclick="closeReinstallModal()" style="max-width: 100px;"><?php echo $lang['man_modal_opt_btn_cancel']; ?></button>
            <button class="btn btn-manage" id="btnConfirmReinstall" onclick="confirmReinstall()" style="max-width: 250px;">
                <?php echo $lang['man_modal_reinstall_btn_confirm']; ?>
            </button>
        </div>
    </div>
</div>
