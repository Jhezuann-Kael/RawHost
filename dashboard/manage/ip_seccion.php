<!-- IPs Adicionales -->
<div class="card full-width" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 class="card-title"><i class="fas fa-network-wired"></i>
            <?php echo $lang['ip_sec_title']; ?>
        </h3>
        <button class="btn btn-primary" onclick="openBuyIpModal()">
            <i class="fas fa-plus"></i>
            <?php echo $lang['ip_sec_btn_add']; ?>
        </button>
    </div>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>
                        <?php echo $lang['ip_tbl_type']; ?>
                    </th>
                    <th>
                        <?php echo $lang['ip_tbl_ip']; ?>
                    </th>
                    <th>
                        <?php echo $lang['ip_tbl_price']; ?>
                    </th>
                    <th>
                        <?php echo $lang['ip_tbl_status']; ?>
                    </th>
                    <th>
                        <?php echo $lang['ip_tbl_expiry']; ?>
                    </th>
                    <th>
                        <?php echo $lang['ip_tbl_action']; ?>
                    </th>
                </tr>
            </thead>
            <tbody id="addons-body">
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px; color: var(--text-muted);">
                        <?php echo $lang['ip_sec_none']; ?> <a href="#" onclick="openBuyIpModal(); return false;"
                            style="color: var(--primary);">
                            <?php echo $lang['ip_sec_buy_now']; ?>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>