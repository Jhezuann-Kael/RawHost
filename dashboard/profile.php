<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

require_once '../api/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/lang_loader.php';
require_once '../repositories/UserRepository.php';
$_profileUser = (new UserRepository())->getById((int)$_SESSION['user_id']);
$_gotifyToken = $_profileUser['gotify_token'] ?? null;

$pageTitle = $lang['prof_title'] . ' - ' . SITE_NAME;
$extraHead = '<link rel="stylesheet" href="css/profile.min.css?v=' . filemtime('css/profile.min.css') . '">';
include 'header.php';

$currentUrl  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
             . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
$lang_active = $_SESSION['lang'] ?? 'en';
?>

<main class="main-content">

    <div class="header">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;">
                <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 style="margin:0;"><?php echo $lang['prof_title']; ?></h1>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <a href="../api/lang/switch?lang=es&back=<?php echo urlencode($currentUrl); ?>" title="Español"
                   style="opacity:<?php echo $lang_active === 'es' ? '1' : '0.4'; ?>;transition:opacity .3s;">
                    <img src="https://flagcdn.com/w20/es.png" alt="ES" style="display:block;width:24px;border-radius:2px;">
                </a>
                <a href="../api/lang/switch?lang=en&back=<?php echo urlencode($currentUrl); ?>" title="English"
                   style="opacity:<?php echo $lang_active === 'en' ? '1' : '0.4'; ?>;transition:opacity .3s;">
                    <img src="https://flagcdn.com/w20/us.png" alt="EN" style="display:block;width:24px;border-radius:2px;">
                </a>
            </div>
        </div>
    </div>

    <div class="profile-wrapper">

        <div id="profileLoading" class="profile-loading">
            <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary);"></i>
        </div>

        <div id="profileContent" style="display:none;">

            <!-- ── Identity header ── -->
            <div class="profile-identity">
                <div class="profile-avatar" id="avatarWrap" onclick="document.getElementById('avatarInput').click()" title="<?php echo $lang['prof_title']; ?>">
                    <i class="fas fa-user" id="avatarIcon"></i>
                    <img id="avatarImg" src="" alt="Avatar" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <div class="avatar-overlay"><i class="fas fa-camera"></i></div>
                    <i class="fas fa-crown crown-icon" id="crownIcon"></i>
                </div>
                <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="uploadAvatar(this)">
                <div class="profile-identity-info">
                    <h2 id="pUsername">—</h2>
                    <p id="pEmail"></p>
                    <span id="adminBadge" class="admin-badge"><?php echo $lang['prof_badge_admin']; ?></span>
                </div>
            </div>

            <!-- ── Settings layout ── -->
            <div class="settings-layout">

                <!-- Sidebar nav -->
                <nav class="settings-nav">
                    <div class="settings-nav-item active" onclick="showSection('general')">
                        <i class="fas fa-user-circle"></i> <?php echo $lang['settings_general']; ?>
                    </div>
                    <div class="settings-nav-divider"></div>
                    <div class="settings-nav-item" onclick="showSection('security')">
                        <i class="fas fa-shield-alt"></i> <?php echo $lang['settings_security']; ?>
                    </div>
                    <div class="settings-nav-divider"></div>
                    <div class="settings-nav-item" onclick="showSection('billing')">
                        <i class="fas fa-wallet"></i> <?php echo $lang['settings_billing']; ?>
                    </div>
                    <div class="settings-nav-divider"></div>
                    <div class="settings-nav-item" onclick="showSection('notifications')">
                        <i class="fas fa-bell"></i> <?php echo $lang['settings_notifications']; ?>
                    </div>
                    <div class="settings-nav-divider"></div>
                    <div class="settings-nav-item" onclick="showSection('api')">
                        <i class="fas fa-key"></i> <?php echo $lang['settings_api']; ?>
                    </div>
                </nav>

                <!-- Content area -->
                <div>

                    <!-- ══ General ══ -->
                    <div id="section-general" class="settings-section active">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_sect_details']; ?></h3>
                                <p><?php echo $lang['dash_desc']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['auth_username']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <span class="settings-row-value"><?php echo htmlspecialchars($_profileUser['username'] ?? ''); ?></span>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_lbl_id']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <span class="settings-row-value" id="pId">—</span>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_lbl_joined']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <span class="settings-row-value" id="pJoined">—</span>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_lbl_status']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <span style="font-size:.8rem;font-weight:600;color:var(--success);"><?php echo $lang['prof_status_active']; ?></span>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label">Display name</div>
                                        <div class="settings-row-desc" style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Shown in support chat. Optional.</div>
                                    </div>
                                    <div class="settings-row-control" style="display:flex;gap:8px;align-items:center;">
                                        <input type="text" id="pDisplayName" maxlength="60" placeholder="Loading..."
                                            style="padding:7px 10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:white;font-size:0.85rem;width:170px;outline:none;">
                                        <button class="btn btn-manage" style="padding:7px 14px;font-size:0.82rem;white-space:nowrap;" onclick="saveDisplayName()">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                        <span id="pDisplayNameMsg" style="font-size:0.78rem;display:none;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Telegram -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3>Telegram</h3>
                                <p><?php echo $lang['dash_tg_prompt_desc']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row" id="tg-li">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_lbl_telegram']; ?></div>
                                        <div class="settings-row-desc" id="pTelegram" style="display:none;"></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <div id="tg-widget" style="display:none; line-height:1;">
                                            <script async src="https://telegram.org/js/telegram-widget.js?22"
                                                data-telegram-login="<?php echo BOT_USERNAME; ?>"
                                                data-size="small"
                                                data-lang="<?php echo $lang_active; ?>"
                                                data-auth-url="/api/auth/telegram_link"
                                                data-request-access="write">
                                            </script>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Referral -->
                        <div class="settings-card" id="referralSection" style="display:none;">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_ref_title']; ?></h3>
                                <p><?php echo $lang['prof_ref_desc']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_ref_stats_total']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <span class="settings-row-value" id="referralCount">0</span>
                                    </div>
                                </div>
                                <div class="settings-row" style="flex-direction:column;align-items:flex-start;gap:10px;">
                                    <div id="referralCodeWrap" class="referral-code-wrap" onclick="copyReferralCode()" title="<?php echo $lang['prof_btn_copy_tooltip']; ?>" style="margin:0;max-width:100%;">
                                        <div id="referralCode" class="referral-code-box">—</div>
                                        <i class="fas fa-copy referral-copy-icon"></i>
                                    </div>
                                    <button id="referralGenBtn" class="btn btn-manage" onclick="generateReferralCode()" style="font-size:.82rem;padding:6px 14px;">
                                        <i class="fas fa-magic"></i> <?php echo $lang['prof_btn_generate']; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ══ Security ══ -->
                    <div id="section-security" class="settings-section">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_modal_pass_title']; ?></h3>
                                <p><?php echo $lang['prof_js_pass_len']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_btn_change_pass']; ?></div>
                                        <div class="settings-row-desc"><?php echo $lang['auth_password_placeholder']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <button class="btn btn-outline" onclick="openPassModal()" style="font-size:.82rem;padding:6px 14px;">
                                            <i class="fas fa-key"></i> <?php echo $lang['prof_btn_change_pass']; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ══ Billing ══ -->
                    <div id="section-billing" class="settings-section">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_balance_title']; ?></h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['dash_stat_balance_label']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <div class="settings-balance-chip">
                                            <span class="amount" id="pBalance">$0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_auto_renew_title']; ?></h3>
                                <p><?php echo $lang['prof_auto_renew_desc']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_auto_renew_title']; ?></div>
                                        <div class="settings-row-desc" id="autoRenewMsg"></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="autoRenewToggle" onchange="saveAutoRenew(this.checked)">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_pref_title']; ?></h3>
                                <p><?php echo $lang['prof_pref_desc']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <select id="prefCurrencySelect" style="display:none;" aria-hidden="true">
                                    <option value=""></option>
                                </select>
                                <div style="padding:4px 22px 14px;">
                                    <div id="currencyCardsWrap" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                                        <i class="fas fa-spinner fa-spin" style="color:var(--primary);font-size:.85rem;"></i>
                                    </div>
                                    <div id="prefMsg" style="font-size:.8rem;min-height:16px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ══ Notifications ══ -->
                    <div id="section-notifications" class="settings-section">

                        <?php if ($_gotifyToken): ?>
                        <!-- Active -->
                        <div class="settings-card">
                            <div class="settings-card-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                                <div>
                                    <h3><?php echo $lang['prof_push_title']; ?></h3>
                                    <p><?php echo $lang['prof_push_active_desc']; ?></p>
                                </div>
                                <span class="notif-badge active"><?php echo $lang['prof_push_badge_active']; ?></span>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_push_lbl_token']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <div class="settings-token-row">
                                            <code id="gotifyTokenDisplay"><?php echo htmlspecialchars($_gotifyToken); ?></code>
                                            <button class="btn btn-outline" id="btnCopyToken" onclick="gotifyCopyToken()" style="padding:4px 8px;font-size:.75rem;" title="<?php echo $lang['prof_push_copy_token']; ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_push_lbl_server']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <div class="settings-token-row">
                                            <code style="color:#a5b4fc;"><?php echo GOTIFY_URL; ?></code>
                                            <button class="btn btn-outline" onclick="navigator.clipboard.writeText('<?php echo GOTIFY_URL; ?>');this.innerHTML='<i class=\'fas fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i>',1500)" style="padding:4px 8px;font-size:.75rem;" title="<?php echo $lang['prof_push_copy_url']; ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_push_btn_test']; ?></div>
                                        <div class="settings-row-desc" id="gotifyMsg"></div>
                                    </div>
                                    <div class="settings-row-control" style="display:flex;gap:8px;">
                                        <button class="btn btn-manage" id="btnGotifyTest" onclick="gotifyTest()" style="font-size:.8rem;padding:6px 12px;">
                                            <i class="fas fa-paper-plane"></i> <?php echo $lang['prof_push_btn_test']; ?>
                                        </button>
                                        <button class="btn btn-outline" id="btnGotifyRegen" onclick="gotifySetup(true)" style="font-size:.8rem;padding:6px 12px;" title="<?php echo $lang['prof_push_regen_title']; ?>">
                                            <i class="fas fa-redo"></i> <?php echo $lang['prof_push_btn_regen']; ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_push_download']; ?></div>
                                        <div class="settings-row-desc"><?php echo $lang['prof_push_apk_direct']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <a href="https://play.google.com/store/apps/details?id=com.github.gotify" target="_blank" rel="noopener" class="btn btn-manage" style="font-size:.8rem;padding:6px 12px;text-decoration:none;">
                                            <i class="fab fa-google-play"></i> <?php echo $lang['prof_push_playstore']; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_push_prefs_title']; ?></h3>
                                <p><?php echo $lang['prof_push_freq_note']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_push_notify_expiry']; ?></div>
                                        <div class="settings-row-desc"><?php echo $lang['prof_push_notify_expiry_desc']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="toggleNotifyExpiry">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="settings-row">
                                    <div class="settings-row-info">
                                        <div class="settings-row-label"><?php echo $lang['prof_push_notify_metrics']; ?></div>
                                        <div class="settings-row-desc"><?php echo $lang['prof_push_notify_metrics_desc']; ?></div>
                                    </div>
                                    <div class="settings-row-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="toggleNotifyMetrics" onchange="gotifyToggleThresholds(this.checked)">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div id="gotifyThresholds" style="padding:14px 22px;border-top:1px solid rgba(255,255,255,0.04);display:flex;flex-direction:column;gap:12px;">
                                    <div style="font-size:.75rem;color:var(--text-muted);font-weight:600;"><?php echo $lang['prof_push_thresholds']; ?></div>
                                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                                        <?php foreach ([
                                            ['id'=>'threshCpu',  'lbl'=>$lang['prof_push_cpu_lbl'],  'def'=>90],
                                            ['id'=>'threshRam',  'lbl'=>$lang['prof_push_ram_lbl'],  'def'=>90],
                                            ['id'=>'threshDisk', 'lbl'=>$lang['prof_push_disk_lbl'], 'def'=>85],
                                        ] as $t): ?>
                                        <div>
                                            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:4px;text-align:center;"><?php echo $t['lbl']; ?></div>
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <input type="range" id="<?php echo $t['id']; ?>Range" min="50" max="99" value="<?php echo $t['def']; ?>" style="flex:1;accent-color:var(--primary);" oninput="document.getElementById('<?php echo $t['id']; ?>Val').textContent=this.value+'%'">
                                                <span id="<?php echo $t['id']; ?>Val" style="font-size:.75rem;font-family:monospace;color:#e2e8f0;min-width:34px;text-align:right;"><?php echo $t['def']; ?>%</span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <button class="btn btn-manage" id="btnSavePrefs" onclick="gotifySavePrefs()" style="font-size:.8rem;padding:6px 14px;">
                                            <i class="fas fa-save"></i> <?php echo $lang['prof_push_btn_save_prefs']; ?>
                                        </button>
                                        <span id="prefsMsg" style="font-size:.8rem;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php else: ?>
                        <!-- Inactive -->
                        <div class="settings-card">
                            <div class="settings-card-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                                <div>
                                    <h3><?php echo $lang['prof_push_title']; ?></h3>
                                    <p><?php echo $lang['prof_push_inactive_desc']; ?></p>
                                </div>
                                <span class="notif-badge inactive"><?php echo $lang['prof_push_badge_inactive']; ?></span>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row" style="flex-direction:column;align-items:flex-start;gap:12px;">
                                    <ol style="margin:0;padding-left:18px;font-size:.82rem;color:var(--text-muted);line-height:2;">
                                        <li>
                                            <?php echo $lang['prof_push_step_1']; ?>
                                            <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                                <a href="https://play.google.com/store/apps/details?id=com.github.gotify" target="_blank" rel="noopener"
                                                   style="display:inline-flex;align-items:center;gap:7px;background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.25);color:#34d399;padding:6px 13px;border-radius:8px;font-size:.78rem;font-weight:600;text-decoration:none;transition:background .2s;"
                                                   onmouseover="this.style.background='rgba(52,211,153,0.18)'" onmouseout="this.style.background='rgba(52,211,153,0.1)'">
                                                    <i class="fab fa-google-play"></i> <?php echo $lang['prof_push_playstore']; ?>
                                                </a>
                                                <a href="https://github.com/gotify/android/releases/latest" target="_blank" rel="noopener"
                                                   style="font-size:.75rem;color:var(--text-muted);text-decoration:none;">
                                                    <i class="fas fa-download" style="font-size:.7rem;margin-right:3px;"></i><?php echo $lang['prof_push_apk_direct']; ?>
                                                </a>
                                            </div>
                                        </li>
                                        <li><?php echo $lang['prof_push_step_2']; ?> <code style="color:#a5b4fc;"><?php echo GOTIFY_URL; ?></code></li>
                                        <li><?php echo $lang['prof_push_step_3']; ?></li>
                                    </ol>
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        <button class="btn btn-manage" id="btnGotifySetup" onclick="gotifySetup(false)" style="font-size:.85rem;">
                                            <i class="fas fa-bell"></i> <?php echo $lang['prof_push_btn_activate']; ?>
                                        </button>
                                        <span id="gotifyMsg" style="font-size:.8rem;color:var(--text-muted);"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ══ API ══ -->
                    <div id="section-api" class="settings-section">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><?php echo $lang['prof_apikeys_title']; ?></h3>
                                <p><?php echo $lang['prof_apikeys_desc']; ?></p>
                            </div>
                            <div class="settings-card-body">
                                <div class="settings-row">
                                    <div style="display:flex;gap:8px;width:100%;">
                                        <input type="text" id="newKeyName" class="form-input" placeholder="<?php echo $lang['prof_apikeys_placeholder']; ?>" style="flex:1;padding:8px 10px;font-size:.88rem;">
                                        <button class="btn btn-manage" onclick="createApiKey()" style="white-space:nowrap;padding:8px 14px;">
                                            <i class="fas fa-plus"></i> <?php echo $lang['prof_apikeys_btn_create']; ?>
                                        </button>
                                    </div>
                                </div>
                                <div id="apiKeysList" style="padding:0 22px 6px;display:flex;flex-direction:column;gap:6px;"></div>
                                <div id="apiKeysMsg" style="padding:0 22px 10px;font-size:.8rem;min-height:16px;"></div>
                                <div id="apiKeyReveal" style="display:none;margin:0 22px 16px;background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.3);border-radius:8px;padding:12px;">
                                    <p style="margin:0 0 6px;font-size:.8rem;color:#38bdf8;font-weight:600;">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $lang['prof_apikeys_reveal']; ?>
                                    </p>
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        <code id="apiKeyRevealText" style="flex:1;font-size:.78rem;word-break:break-all;color:#e2e8f0;background:rgba(0,0,0,0.3);padding:8px;border-radius:6px;"></code>
                                        <button class="btn btn-outline" onclick="copyNewKey()" style="padding:6px 12px;font-size:.8rem;flex-shrink:0;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /content area -->
            </div><!-- /settings-layout -->

        </div><!-- /profileContent -->
    </div><!-- /profile-wrapper -->
</main>

<!-- Password modal -->
<div id="passOverlay" class="pass-modal-overlay" onclick="closePassModal()"></div>
<div id="passModal" class="pass-modal">
    <div class="pass-modal-card">
        <div class="pass-modal-header">
            <h3><?php echo $lang['prof_modal_pass_title']; ?></h3>
            <span class="pass-modal-close" onclick="closePassModal()">&times;</span>
        </div>
        <div class="form-group">
            <label><?php echo $lang['prof_lbl_curr_pass']; ?></label>
            <div class="input-wrap">
                <input type="password" id="currPass" class="form-input">
                <span class="eye-btn" onclick="toggleEye('currPass')"><i id="eye_currPass" class="fas fa-eye"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label><?php echo $lang['prof_lbl_new_pass']; ?></label>
            <div class="input-wrap">
                <input type="password" id="newPass" class="form-input">
                <span class="eye-btn" onclick="toggleEye('newPass')"><i id="eye_newPass" class="fas fa-eye"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label><?php echo $lang['prof_lbl_conf_pass']; ?></label>
            <div class="input-wrap">
                <input type="password" id="confPass" class="form-input">
                <span class="eye-btn" onclick="toggleEye('confPass')"><i id="eye_confPass" class="fas fa-eye"></i></span>
            </div>
        </div>
        <div id="passError"   class="modal-msg error"></div>
        <div id="passSuccess" class="modal-msg success"></div>
        <div class="pass-modal-footer">
            <button class="btn btn-outline" onclick="closePassModal()"><?php echo $lang['prof_btn_cancel']; ?></button>
            <button class="btn btn-manage" id="btnSavePass" onclick="submitPassChange()"><?php echo $lang['prof_btn_change_pass']; ?></button>
        </div>
    </div>
</div>

<style>
.toggle-switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:#374151; border-radius:26px; transition:.3s; }
.toggle-slider:before { content:''; position:absolute; height:20px; width:20px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
.toggle-switch input:checked + .toggle-slider { background:var(--primary); }
.toggle-switch input:checked + .toggle-slider:before { transform:translateX(20px); }
</style>

<script>
function showSection(name) {
    document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('section-' + name).classList.add('active');
    event.currentTarget.classList.add('active');
    history.replaceState(null, '', '#' + name);
}

// Restore section from URL hash
(function () {
    const hash = location.hash.replace('#', '');
    if (hash && document.getElementById('section-' + hash)) {
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById('section-' + hash).classList.add('active');
        const nav = [...document.querySelectorAll('.settings-nav-item')].find(n => n.getAttribute('onclick')?.includes("'"+hash+"'"));
        if (nav) nav.classList.add('active');
    }
})();

/* ── Gotify ── */
async function gotifySetup(regen) {
    const btn = document.getElementById(regen ? 'btnGotifyRegen' : 'btnGotifySetup');
    const msg = document.getElementById('gotifyMsg');
    if (regen && !confirm(LANG_PROF.push_regen_confirm)) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    msg.textContent = '';
    try {
        const r = await fetch('../api/users/gotify_setup', { method: 'POST' });
        const d = await r.json();
        if (d.success) { location.reload(); }
        else { msg.style.color = '#ef4444'; msg.textContent = d.message || 'Error'; }
    } catch (e) {
        msg.style.color = '#ef4444'; msg.textContent = LANG_PROF.err_conn;
    } finally { btn.disabled = false; }
}

async function gotifyTest() {
    const btn = document.getElementById('btnGotifyTest');
    const msg = document.getElementById('gotifyMsg');
    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${LANG_PROF.push_sending}`;
    msg.textContent = '';
    try {
        const r = await fetch('../api/users/gotify_test', { method: 'POST' });
        const d = await r.json();
        msg.style.color = d.success ? '#34d399' : '#ef4444';
        msg.textContent = d.success ? LANG_PROF.notif_sent : (d.no_token ? LANG_PROF.notif_no_token : LANG_PROF.notif_fail);
    } catch (e) {
        msg.style.color = '#ef4444'; msg.textContent = LANG_PROF.err_conn;
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-paper-plane"></i> ${LANG_PROF.push_btn_test}`;
    }
}

function gotifyCopyToken() {
    const token = document.getElementById('gotifyTokenDisplay').textContent.trim();
    navigator.clipboard.writeText(token);
    const btn = document.getElementById('btnCopyToken');
    btn.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500);
}

function gotifyToggleThresholds(on) {
    const el = document.getElementById('gotifyThresholds');
    if (el) el.style.opacity = on ? '1' : '0.4';
}

async function gotifyLoadPrefs() {
    try {
        const r = await fetch('../api/users/notify_prefs');
        const d = await r.json();
        if (!d.success) return;
        const p = d.data;
        document.getElementById('toggleNotifyExpiry').checked  = p.notify_expiry;
        document.getElementById('toggleNotifyMetrics').checked = p.notify_metrics;
        const cpu  = document.getElementById('threshCpuRange');
        const ram  = document.getElementById('threshRamRange');
        const disk = document.getElementById('threshDiskRange');
        if (cpu)  { cpu.value  = p.alert_cpu_threshold;  document.getElementById('threshCpuVal').textContent  = p.alert_cpu_threshold  + '%'; }
        if (ram)  { ram.value  = p.alert_ram_threshold;  document.getElementById('threshRamVal').textContent  = p.alert_ram_threshold  + '%'; }
        if (disk) { disk.value = p.alert_disk_threshold; document.getElementById('threshDiskVal').textContent = p.alert_disk_threshold + '%'; }
        gotifyToggleThresholds(p.notify_metrics);
    } catch (_) {}
}

async function gotifySavePrefs() {
    const btn = document.getElementById('btnSavePrefs');
    const msg = document.getElementById('prefsMsg');
    btn.disabled = true;
    msg.textContent = '';
    try {
        const r = await fetch('../api/users/notify_prefs', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                notify_expiry:        document.getElementById('toggleNotifyExpiry').checked,
                notify_metrics:       document.getElementById('toggleNotifyMetrics').checked,
                alert_cpu_threshold:  parseInt(document.getElementById('threshCpuRange').value),
                alert_ram_threshold:  parseInt(document.getElementById('threshRamRange').value),
                alert_disk_threshold: parseInt(document.getElementById('threshDiskRange').value),
            }),
        });
        const d = await r.json();
        msg.style.color = d.success ? '#34d399' : '#ef4444';
        msg.textContent = d.success ? LANG_PROF.push_prefs_saved : LANG_PROF.push_prefs_err;
    } catch (_) {
        msg.style.color = '#ef4444'; msg.textContent = LANG_PROF.err_conn;
    } finally {
        btn.disabled = false;
        setTimeout(() => msg.textContent = '', 3000);
    }
}
</script>

<script>
const TG_STATUS = <?php
    $tg  = $_GET['tg']  ?? '';
    $msg = $_GET['msg'] ?? '';
    echo json_encode(['status' => $tg, 'msg' => htmlspecialchars($msg)]);
?>;
const LANG_PROF = <?php echo json_encode([
    'total'                  => $lang['prof_ref_stats_total'],
    'valid'                  => $lang['prof_ref_stats_valid'],
    'connected'              => $lang['prof_status_connected'],
    'not_connected'          => $lang['prof_status_not_connected'],
    'gen_success'            => $lang['prof_js_gen_success'],
    'err'                    => $lang['prof_js_err'],
    'err_conn'               => $lang['prof_js_err_conn'],
    'copy_success'           => $lang['prof_js_copy_success'],
    'generating'             => $lang['tx_processing'],
    'err_conn_load'          => $lang['tx_err_connection'],
    'user_fallback'          => $lang['auth_username'],
    'pass_mismatch'          => $lang['prof_js_pass_mismatch'],
    'pass_len'               => $lang['prof_js_pass_len'],
    'pass_success'           => $lang['prof_js_pass_success'],
    'pref_saved'             => $lang['prof_pref_saved'] ?? 'Saved!',
    'apikeys_empty'          => $lang['prof_apikeys_empty'],
    'apikeys_btn_delete'     => $lang['prof_apikeys_btn_delete'],
    'apikeys_confirm_delete' => $lang['prof_apikeys_confirm_delete'],
    'apikeys_err_conn'       => $lang['prof_apikeys_err_conn'],
    'push_regen_confirm'     => $lang['prof_push_regen_confirm'],
    'push_sending'           => $lang['prof_push_sending'],
    'push_btn_test'          => $lang['prof_push_btn_test'],
    'push_prefs_saved'       => $lang['prof_push_prefs_saved'],
    'push_prefs_err'         => $lang['prof_push_prefs_err'],
    'notif_sent'             => $lang['notif_sent'],
    'notif_no_token'         => $lang['notif_no_token'],
    'notif_fail'             => $lang['notif_fail'],
]); ?>;
</script>
<script src="js/profile.min.js?v=<?php echo filemtime('js/profile.min.js'); ?>"></script>
<style>
/* ── Currency coin grid (mirrors pay_vps.php) ── */
.pref-coin-grid { display:flex; flex-wrap:wrap; gap:5px; }
.pref-coin-btn { width:68px; flex-shrink:0; }
.pref-coin-btn {
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px;
    border:2px solid rgba(255,255,255,0.1); padding:8px 4px; border-radius:12px; cursor:pointer;
    background:rgba(255,255,255,0.02); transition:all 0.2s; position:relative;
}
.pref-coin-btn:hover { background:rgba(255,255,255,0.05); }
.pref-coin-btn.selected { border-color:var(--primary); background:rgba(99,102,241,0.12); }
.pref-coin-btn.pref-highlight { border-color:var(--primary) !important; box-shadow:0 0 0 2px rgba(99,102,241,0.25); }
.pref-coin-btn .pref-star { color:var(--primary); font-size:0.65rem; line-height:1; position:absolute; top:4px; right:6px; }
.pref-net-btn {
    border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.03);
    color:var(--text-light); padding:6px 14px; border-radius:20px; cursor:pointer;
    font-size:0.8rem; font-weight:500; transition:all 0.2s; display:flex; align-items:center; gap:6px;
}
.pref-net-btn:hover { background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.2); }
.pref-net-btn.selected-net { border-color:var(--primary) !important; color:var(--primary) !important; background:rgba(0,243,255,0.1) !important; }
.pref-net-btn .net-acronym { background:rgba(255,255,255,0.1); color:var(--text-muted); padding:1px 6px; border-radius:4px; font-size:0.65rem; font-weight:700; }
.pref-net-btn.selected-net .net-acronym { background:rgba(0,243,255,0.2); color:var(--primary); }
</style>
<script>
(function () {
    const COIN_LOGOS = {
        USDT:'USDT_Logo.png', BTC:'BTC.png', ETH:'ETH.png', TRX:'TRX.png',
        LTC:'LTC.png', BNB:'BNB.png', USDC:'USDC.png', DOGE:'DODGE.png',
        POL:'POL.png', SOL:'SOL.png', SHIB:'SHIB.png', TON:'TON.png',
        XMR:'XMR.png', DAI:'DAI.png', BCH:'BCH.png', NOT:'NOT.png',
        DOGS:'DOGS.png', XRP:'XRP.png'
    };
    const TOP_COINS = ['USDT','BTC','ETH','TRX','LTC','BNB'];

    let _coinsData   = [];
    let _prefSym     = '';
    let _prefNet     = '';
    let _selSym      = null;
    let _selNet      = null;

    async function savePref(value) {
        const msg = document.getElementById('prefMsg');
        msg.textContent = '';
        try {
            const res  = await fetch('../api/users/preferences', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ preferred_currency: value || null }),
            });
            const data = await res.json();
            msg.style.color = data.success ? 'var(--success)' : '#f87171';
            msg.textContent = data.success ? (LANG_PROF.pref_saved ?? 'Saved!') : (data.message || 'Error');
            setTimeout(() => msg.textContent = '', 2500);
        } catch (_) {
            msg.style.color = '#f87171';
            msg.textContent = LANG_PROF.err_conn;
        }
    }

    function renderPrefNets(sym) {
        _selSym = sym;
        const coin   = _coinsData.find(c => (c.symbol || c.currency) === sym);
        const netWrap = document.getElementById('prefNetArea');
        const netGrid = document.getElementById('prefNetGrid');
        netGrid.innerHTML = '';
        const nets = coin?.networks
            ? (Array.isArray(coin.networks) ? coin.networks : Object.values(coin.networks))
            : [];
        if (!nets.length) {
            netWrap.style.display = 'none';
            _selNet = sym;
            savePref(`${sym}:${sym}`);
            return;
        }
        netWrap.style.display = 'block';
        let prefBtn = null;
        nets.forEach(net => {
            const btn   = document.createElement('button');
            btn.type    = 'button';
            btn.className = 'pref-net-btn';
            const netName = (net.name || net.network || '').replace(/\s*\(.*\)\s*/, '').trim();
            let acronym = '';
            if (net.keys?.length) {
                const f = net.keys.find(k => k.length >= 3 && k.length <= 6 && k === k.toUpperCase());
                if (f) acronym = f;
            }
            if (!acronym && (net.name || net.network || '').includes('(')) {
                const m = (net.name || net.network).match(/\(([^)]+)\)/);
                if (m) acronym = m[1];
            }
            const isPref = _prefNet === net.network && _prefSym === sym;
            if (isPref) { btn.classList.add('pref-highlight'); prefBtn = btn; }
            btn.innerHTML = `${isPref ? '<span class="pref-star">★</span>' : ''}<span>${netName}</span>${acronym ? `<span class="net-acronym">${acronym}</span>` : ''}`;
            btn.onclick = () => {
                document.querySelectorAll('.pref-net-btn').forEach(b => b.classList.remove('selected-net'));
                btn.classList.add('selected-net');
                _selNet = net.network;
                savePref(`${sym}:${net.network}`);
            };
            netGrid.appendChild(btn);
            if (isPref) prefBtn = btn;
        });
        if (prefBtn) prefBtn.click();
        else if (nets.length === 1) netGrid.firstChild?.click();
    }

    function renderPrefCoins() {
        const wrap = document.getElementById('currencyCardsWrap');
        if (!wrap) return;
        const sorted = [
            ..._coinsData.filter(c => TOP_COINS.includes(c.symbol || c.currency)),
            ..._coinsData.filter(c => !TOP_COINS.includes(c.symbol || c.currency)),
        ];
        wrap.innerHTML = `<div class="pref-coin-grid">${sorted.map(c => {
            const sym  = c.symbol || c.currency || '';
            const logo = COIN_LOGOS[sym] || 'BTC.png';
            const isPref = _prefSym === sym;
            return `<div class="pref-coin-btn${isPref ? ' pref-highlight' : ''}" data-sym="${sym}" onclick="window._prefSelCoin('${sym}')">
                ${isPref ? '<span class="pref-star">★</span>' : ''}
                <img src="/assets/img/crypto/${logo}" alt="${sym}" style="width:20px;height:20px;object-fit:contain;" loading="lazy">
                <span style="font-weight:700;font-size:0.72rem;color:var(--text-light);">${sym}</span>
            </div>`;
        }).join('')}</div>
        <div id="prefNetArea" style="display:none;margin-top:10px;">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:6px;"><?php echo $lang['buy_net_label'] ?? 'Network'; ?></div>
            <div id="prefNetGrid" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
        </div>`;

        if (_prefSym) {
            const el = wrap.querySelector(`[data-sym="${_prefSym}"]`);
            if (el) el.classList.add('selected');
            renderPrefNets(_prefSym);
        }
    }

    window._prefSelCoin = function (sym) {
        document.querySelectorAll('#currencyCardsWrap .pref-coin-btn').forEach(b =>
            b.classList.toggle('selected', b.dataset.sym === sym));
        renderPrefNets(sym);
    };

    async function loadCurrencyCards() {
        const wrap = document.getElementById('currencyCardsWrap');
        if (!wrap) return;
        try {
            const [currRes, prefRes] = await Promise.all([
                fetch('../api/transactions/currencies'),
                fetch('../api/users/preferences'),
            ]);
            const currData = await currRes.json();
            const prefData = await prefRes.json();

            const saved       = prefData.success ? (prefData.data.preferred_currency || '') : '';
            const lastPayment = prefData.success ? (prefData.data.last_payment_currency || '') : '';
            const effective   = saved || lastPayment;
            if (effective && effective.includes(':')) {
                [_prefSym, _prefNet] = effective.split(':', 2);
            }

            const raw  = currData.data || currData;
            const list = Array.isArray(raw) ? raw : Object.values(raw);
            _coinsData = list;

            if (!list.length) {
                wrap.innerHTML = `<p style="font-size:.82rem;color:var(--text-muted);"><?php echo $lang['prof_pref_none']; ?></p>`;
                return;
            }
            renderPrefCoins();
        } catch (e) {
            const w = document.getElementById('currencyCardsWrap');
            if (w) w.innerHTML = `<p style="font-size:.82rem;color:#ef4444;">${LANG_PROF.err_conn}</p>`;
        }
    }

    // Hook into showSection to catch navigation clicks
    const _origShow = window.showSection;
    window.showSection = function (name) {
        if (_origShow) _origShow.apply(this, arguments);
        if (name === 'billing') loadCurrencyCards();
    };

    // Also fire when profileContent becomes visible (handles #billing hash or default)
    const content = document.getElementById('profileContent');
    if (content) {
        const obs = new MutationObserver(() => {
            if (content.style.display !== 'none') {
                obs.disconnect();
                if (document.getElementById('section-billing')?.classList.contains('active')) loadCurrencyCards();
            }
        });
        obs.observe(content, { attributes: true, attributeFilter: ['style'] });
    }
})();
</script>

<script>
(function () {
    let _newKey = null;

    async function loadApiKeys() {
        const list = document.getElementById('apiKeysList');
        try {
            const r = await fetch('../../api/users/apikeys');
            const d = await r.json();
            if (!d.success || !d.data.length) {
                list.innerHTML = `<p style="font-size:.82rem;color:var(--text-muted);margin:4px 0;">${LANG_PROF.apikeys_empty}</p>`;
                return;
            }
            list.innerHTML = d.data.map(k => `
                <div style="display:flex;align-items:center;gap:8px;background:rgba(0,0,0,0.2);border-radius:8px;padding:8px 10px;">
                    <i class="fas fa-key" style="color:var(--primary);font-size:.8rem;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.83rem;font-weight:600;">${escHtml(k.name)}</div>
                        <div style="font-size:.73rem;color:var(--text-muted);font-family:monospace;">${escHtml(k.apikey_masked)}</div>
                    </div>
                    <span style="font-size:.7rem;color:var(--text-muted);flex-shrink:0;">${k.created_at.slice(0,10)}</span>
                    <button onclick="deleteApiKey(${k.id})" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px 6px;font-size:.82rem;" title="${LANG_PROF.apikeys_btn_delete}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
        } catch (e) {
            list.innerHTML = `<p style="font-size:.82rem;color:#ef4444;margin:4px 0;">${LANG_PROF.apikeys_err_conn}</p>`;
        }
    }

    window.createApiKey = async function () {
        const name = document.getElementById('newKeyName').value.trim() || 'My Key';
        const msg  = document.getElementById('apiKeysMsg');
        msg.textContent = '';
        document.getElementById('apiKeyReveal').style.display = 'none';
        try {
            const r = await fetch('../../api/users/apikeys', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name }),
            });
            const d = await r.json();
            if (!d.success) { msg.style.color = '#ef4444'; msg.textContent = d.message || 'Error'; return; }
            _newKey = d.apikey;
            document.getElementById('newKeyName').value = '';
            document.getElementById('apiKeyRevealText').textContent = d.apikey;
            document.getElementById('apiKeyReveal').style.display = 'block';
            loadApiKeys();
        } catch (e) {
            msg.style.color = '#ef4444'; msg.textContent = LANG_PROF.apikeys_err_conn;
        }
    };

    window.deleteApiKey = async function (id) {
        if (!confirm(LANG_PROF.apikeys_confirm_delete)) return;
        await fetch('../../api/users/apikeys', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        loadApiKeys();
    };

    window.copyNewKey = function () {
        if (_newKey) navigator.clipboard.writeText(_newKey);
    };

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    const observer = new MutationObserver(() => {
        if (document.getElementById('profileContent').style.display !== 'none') {
            loadApiKeys();
            if (document.getElementById('toggleNotifyExpiry')) gotifyLoadPrefs();
            observer.disconnect();
        }
    });
    observer.observe(document.getElementById('profileContent'), { attributes: true, attributeFilter: ['style'] });
})();
</script>
<?php include 'footer.php'; ?>
