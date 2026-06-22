<!-- Upgrade/Renew Modal -->
<div class="modal-overlay" id="upgradeModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="upgradeModalTitle"><?php echo $lang['man_modal_opt_title']; ?></h2>
            <button class="btn-close" onclick="closeUpgradeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">

            <!-- Step 2: Config -->
            <div id="step2-config" style="display:block;">
                <!-- Payment Method Toggle -->
                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.88rem; color: var(--text-muted); display: block; margin-bottom: 10px;"><?php echo $lang['vps_pay_method_label'] ?? 'Payment Method'; ?></label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div id="pay-option-balance-action" onclick="selectActionPayMethod('balance')"
                            style="border: 2px solid var(--primary); border-radius: 12px; padding: 14px 10px; text-align: center; cursor: pointer; transition: all 0.25s; background: rgba(var(--primary-rgb), 0.1);">
                            <div style="font-size: 1.4rem; margin-bottom: 6px;"><i class="fas fa-wallet"></i></div>
                            <div style="font-weight: 700; font-size: 0.9rem; margin-bottom: 2px;"><?php echo $lang['vps_pay_balance_title'] ?? 'Balance'; ?></div>
                        </div>
                        <div id="pay-option-crypto-action" onclick="selectActionPayMethod('crypto')"
                            style="border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 14px 10px; text-align: center; cursor: pointer; transition: all 0.25s; background: rgba(255,255,255,0.03);">
                            <div style="font-size: 1.4rem; margin-bottom: 6px;"><i class="fab fa-bitcoin" style="color:#f7931a;"></i></div>
                            <div style="font-weight: 700; font-size: 0.9rem; margin-bottom: 2px;"><?php echo $lang['vps_pay_crypto_title'] ?? 'Crypto'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Renew Options -->
                <div id="renewOptions" style="display:none;">
                    <!-- User Balance Display (only for balance) -->
                    <div id="user-balance-renew-container"
                        style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted);"><?php echo $lang['man_modal_opt_balance']; ?></span>
                            <span style="font-weight: 600;" id="user-balance-renew">$0.00</span>
                        </div>
                    </div>

                    <label class="form-group-label"
                        style="display:block; margin-bottom:10px; color:var(--text-muted);"><?php echo $lang['man_modal_opt_duration']; ?></label>
                    <div class="grid-selection" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="os-option-card duration-box" onclick="selectDuration(24, this)">24h</div>
                        <div class="os-option-card duration-box" onclick="selectDuration(168, this)">7
                            <?php echo $lang['man_modal_opt_days']; ?>
                        </div>
                        <div class="os-option-card duration-box" onclick="selectDuration(720, this)">30
                            <?php echo $lang['man_modal_opt_days']; ?>
                        </div>
                    </div>
                    <div style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                        <label
                            style="display:block; margin-bottom:5px; font-size:0.9rem;"><?php echo $lang['man_modal_opt_custom']; ?></label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" id="customDurationVal" class="form-control" placeholder="Cant."
                                style="flex:1; background-color: #1a1a24; color: #ffffff; border: 1px solid rgba(255,255,255,0.2);" oninput="updateCustomDuration()">
                            <select id="customDurationUnit" class="form-control" style="flex:1; background-color: #1a1a24; color: #ffffff; border: 1px solid rgba(255,255,255,0.2);"
                                onchange="updateCustomDuration()">
                                <option value="1">Horas</option>
                                <option value="24"><?php echo $lang['man_modal_opt_days']; ?></option>
                                <option value="720">Meses</option>
                            </select>
                        </div>
                        <p id="customDurationHelper" style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">
                            <?php echo $lang['man_modal_opt_helper']; ?>
                        </p>
                    </div>

                    <!-- Price Estimation Display -->
                    <div id="priceEstimateContainer"
                        style="margin-top: 20px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; display: none;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span
                                style="color: var(--text-muted); font-size: 0.9rem;"><?php echo $lang['man_info_price']; ?></span>
                            <span id="priceEstimatePlan" style="font-weight: 600;">$0.00</span>
                        </div>
                        <div id="priceEstimateAddonsRow"
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; display: none;">
                            <span style="color: var(--text-muted); font-size: 0.9rem;">Addons:</span>
                            <span id="priceEstimateAddons" style="font-weight: 600;">$0.00</span>
                        </div>
                        <hr style="border: 0; border-top: 1px solid rgba(16, 185, 129, 0.2); margin: 10px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-light); font-weight: 700; font-size: 1.05rem;">Total:</span>
                            <span id="priceEstimateTotal"
                                style="font-weight: 700; color: var(--primary); font-size: 1.2rem;">$0.00</span>
                        </div>
                        <div style="margin-top: 8px; font-size: 0.75rem; color: var(--text-muted); text-align: right;">
                            <span id="priceEstimateDuration">0</span> h x $<span
                                id="priceEstimateHourly">0.0000</span>/h
                        </div>
                    </div>

                    <input type="hidden" id="selectedDuration">

                </div>

                <!-- Upgrade Options -->
                <div id="upgradeOptions" style="display:none;">
                    <p style="color:var(--text-muted); margin-bottom:10px;"><?php echo $lang['man_modal_opt_upgrade_label'] ?? 'Select the new plan:'; ?></p>
                    <div id="upgradePlanGrid" class="plans-grid" style="max-height: 450px; overflow-y: auto;">
                        <!-- Plans loaded here -->
                    </div>
                    <input type="hidden" id="selectedUpgradePlanId">
                </div>

                <div id="stepActionError" style="color: #ef4444; margin-top: 15px; display: none;"></div>

                <div id="processResult"
                    style="margin-top: 20px; padding: 10px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: var(--success); display: none; text-align: center;">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-power"
                onclick="closeUpgradeModal()"><?php echo $lang['man_modal_opt_btn_cancel']; ?></button>
            <!-- For balance: process directly. For crypto: go to crypto selector step -->
            <button class="btn btn-manage" id="btnProcessOrder" onclick="processOrder()" style="display:none;"><span
                    id="btnProcessText"><?php echo $lang['man_modal_opt_btn_confirm']; ?></span></button>
            <button class="btn btn-manage" id="btnGoToCrypto" onclick="openCryptoSelectModal()" style="display:none;">
                <i class="fas fa-coins"></i> <?php echo $lang['vps_btn_next'] ?? 'Next'; ?> &rarr;
            </button>
        </div>
    </div>
</div>

<!-- Crypto Selection Modal (Step 2 for crypto payment) -->
<div class="modal-overlay" id="cryptoSelectModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-coins" style="color:var(--primary);"></i> <?php echo $lang['vps_modal_title_crypto'] ?? 'Select Cryptocurrency'; ?></h2>
            <button class="btn-close" onclick="closeCryptoSelectModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-muted); margin-bottom: 14px; font-size: 0.88rem;"><?php echo $lang['vps_crypto_select_prompt'] ?? 'Select the cryptocurrency you want to pay with:'; ?></p>

            <label style="font-size: 0.88rem; display: block; margin-bottom: 10px; color: var(--text-muted);">
                <?php echo $lang['vps_crypto_select_lbl'] ?? 'Select Cryptocurrency:'; ?>
            </label>

            <!-- Top coins grid -->
            <div id="act-coinGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 6px;"></div>

            <!-- More coins expandable -->
            <div id="act-moreCoinsContainer" style="display:none; margin-top: 8px;">
                <div id="act-moreCoinsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(76px, 1fr)); gap: 8px;"></div>
            </div>
            <button type="button" id="act-btnToggleMore"
                style="background:none; border:none; color: var(--primary); width:100%; margin-top:4px; cursor:pointer; font-size: 0.82rem;">
                <i class="fas fa-chevron-down"></i> <?php echo $lang['vps_crypto_more_coins'] ?? 'See more coins'; ?>
            </button>

            <!-- Network pills -->
            <div id="act-networkGroup" style="display:none; margin-top: 14px; animation: fadeIn 0.3s;">
                <label style="font-size: 0.88rem; display: block; margin-bottom: 8px; color: var(--text-muted);"><?php echo $lang['vps_crypto_network_lbl'] ?? 'Network:'; ?></label>
                <div id="act-networkGrid" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
            </div>

            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 12px;">
                <i class="fas fa-info-circle"></i> <?php echo $lang['vps_crypto_fee_notice'] ?? 'Network fee is paid by you. Exact crypto amount shown on invoice.'; ?>
            </p>

            <div id="cryptoSelectError" style="color: #ef4444; margin-top: 12px; font-size: 0.88rem; display: none;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-power" onclick="closeCryptoSelectModal()">
                <i class="fas fa-arrow-left"></i> <?php echo $lang['vps_btn_back'] ?? 'Back'; ?>
            </button>
            <button class="btn btn-manage" id="btnCreateCryptoInvoice" onclick="processOrder()">
                <i class="fas fa-file-invoice"></i> <?php echo $lang['man_modal_opt_btn_confirm'] ?? 'Create Invoice'; ?>
            </button>
        </div>
    </div>
</div>
