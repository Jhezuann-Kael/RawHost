<!-- Buy IP Modal -->
<div class="modal-overlay" id="buyIpModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h2><i class="fas fa-network-wired"></i> <?php echo $lang['man_modal_ip_title']; ?></h2>
            <button class="btn-close" onclick="closeBuyIpModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="color: var(--text-muted);"><?php echo $lang['man_modal_ip_type']; ?></span>
                    <span style="font-weight: 600;">IPv4</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="color: var(--text-muted);"><?php echo $lang['man_modal_ip_price']; ?></span>
                    <span style="font-weight: 700; color: var(--primary); font-size: 1.1rem;">$6.99/mes</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--text-muted);">Tu saldo:</span>
                    <span style="font-weight: 600;" id="user-balance-ip">$0.00</span>
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 10px;">
                <i class="fas fa-clock" style="color: #eab308; margin-right: 5px;"></i>
                <?php echo $lang['man_modal_ip_warn1']; ?>
            </p>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                <i class="fas fa-info-circle" style="color: var(--primary); margin-right: 5px;"></i>
                <?php echo $lang['man_modal_ip_warn2']; ?>
            </p>

            <div id="ip-purchase-result" style="margin-top: 15px; padding: 10px; border-radius: 8px; display: none;">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-power"
                onclick="closeBuyIpModal()"><?php echo $lang['man_modal_opt_btn_cancel']; ?></button>
            <button class="btn btn-manage" id="btnBuyIp" onclick="confirmBuyIp()">
                <i class="fas fa-shopping-cart"></i> <?php echo $lang['man_modal_ip_btn']; ?>
            </button>
        </div>
    </div>
</div>
