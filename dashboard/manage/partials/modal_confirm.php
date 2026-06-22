<!-- Confirmation Modal (Replaces confirm() dialogs) -->
<div class="modal-overlay" id="confirmModal" style="display: none;">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h2 id="confirmModalTitle"><?php echo $lang['man_js_confirm_title']; ?></h2>
            <button class="btn-close" onclick="closeConfirmModal(false)"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p id="confirmModalMessage" style="color: var(--text-light); line-height: 1.6;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-power"
                onclick="closeConfirmModal(false)"><?php echo $lang['man_modal_opt_btn_cancel']; ?></button>
            <button class="btn btn-manage" id="confirmModalBtn"
                onclick="closeConfirmModal(true)"><?php echo $lang['man_modal_opt_btn_confirm']; ?></button>
        </div>
    </div>
</div>
