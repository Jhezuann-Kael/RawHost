<?php
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/repositories/UserRepository.php';

session_start();
require_once 'includes/lang_loader.php'; // Include generic language loader

$isCompletingRegistration = false;
$foundUser = null;
$code = '';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $repo = new UserRepository();
    $foundUser = $repo->getById($_SESSION['user_id']);

    // If user is logged in and has a username, show complete registration
    if ($foundUser && !empty($foundUser['username']) && $foundUser['username'] !== 'Unknown') {
        $isCompletingRegistration = true;
    } elseif (!isset($_GET['code'])) {
        // If logged in, no username (unlikely but possible), and no code, go to dashboard
        header('Location: dashboard/');
        exit;
    }
} elseif (isset($_GET['code'])) {
    $code = htmlspecialchars($_GET['code']);
    $repo = new UserRepository();
    $foundUser = $repo->findByCode($code);
    if ($foundUser) {
        $isCompletingRegistration = true;
    }
} elseif (isset($_SESSION['user_id']) && !isset($_GET['code'])) {
    // Fallback for logic consistency (implied by above but strictly replacing original block)
    header('Location: dashboard/');
    exit;
}

$referralCode = '';
if (isset($_GET['referral_code'])) {
    $referralCode = htmlspecialchars($_GET['referral_code']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isCompletingRegistration ? $lang['auth_complete_title'] : $lang['auth_register_title']; ?>
    </title>
    <meta name="description" content="<?php echo $lang['auth_register_desc']; ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://rawhost.net/register">

    <!-- OpenGraph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://rawhost.net/register">
    <meta property="og:title" content="<?php echo $lang['auth_register_title']; ?>">
    <meta property="og:description" content="<?php echo $lang['auth_register_desc']; ?>">
    <meta property="og:image" content="https://rawhost.net/assets/op-image.webp">
    <meta property="og:locale" content="<?php echo $_SESSION['lang'] == 'es' ? 'es_ES' : 'en_US'; ?>">
    <meta property="og:site_name" content="RawHost">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://rawhost.net/register">
    <meta name="twitter:title" content="<?php echo $lang['auth_register_title']; ?>">
    <meta name="twitter:description" content="<?php echo $lang['auth_register_desc']; ?>">
    <meta name="twitter:image" content="https://rawhost.net/assets/op-image.webp">

    <!-- Discord / General Embeds -->
    <meta name="theme-color" content="#6366F1">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/favicon.ico'); ?>">

    <link rel="stylesheet" href="style.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/style.css'); ?>">
    <link rel="stylesheet" href="/assets/css/auth-split.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
</head>

<body class="auth-page register-page">

    <div class="lang-switch-auth">
        <a href="?lang=es" title="Español" aria-label="Cambiar a Español"
            class="<?php echo $_SESSION['lang'] == 'es' ? 'active' : ''; ?>"><img src="https://flagcdn.com/w40/es.png"
                alt="Español"></a>
        <a href="?lang=en" title="English" aria-label="Switch to English"
            class="<?php echo $_SESSION['lang'] == 'en' ? 'active' : ''; ?>"><img src="https://flagcdn.com/w40/us.png"
                alt="English"></a>
    </div>

    <canvas id="bg-canvas"></canvas>
    <script src="/assets/js/bg-canvas.js" defer></script>
    <div class="auth-split">

        <!-- Left: pitch -->
        <div class="auth-pitch">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:28px;">
                <img src="/assets/logo/logo_standar.svg" alt="RawHost"
                    style="width:52px; height:52px; border-radius:50%; border:2px solid rgba(99,102,241,0.35); object-fit: contain;">
                <span style="font-size:1.35rem; font-weight:800; color:#fff; letter-spacing:-0.01em;">Raw<span
                        style="color:var(--primary-color);">/Host</span></span>
            </div>
            <p class="auth-pitch-eyebrow">
                <?php echo $currentLang === 'es' ? '🇸🇮 Infraestructura Offshore · Eslovenia' : '🇸🇮 Offshore Infrastructure · Slovenia'; ?>
            </p>
            <h1>
                <?php if ($currentLang === 'es'): ?>
                    Empieza<br>sin dejar<br><em>ningún rastro.</em>
                <?php else: ?>
                    Start<br>without leaving<br><em>any trace.</em>
                <?php endif; ?>
            </h1>
            <p class="auth-pitch-sub">
                <?php echo $currentLang === 'es'
                    ? 'Crea tu cuenta en segundos. Sin email, sin teléfono, sin datos personales. Solo un usuario y contraseña.'
                    : 'Create your account in seconds. No email, no phone, no personal data. Just a username and password.'; ?>
            </p>
            <div class="auth-feats">
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-user-secret"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'Registro 100% anónimo' : '100% anonymous signup'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Sin email ni teléfono. Cero datos personales.' : 'No email or phone required. Zero personal data.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fab fa-bitcoin"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'Paga con cripto' : 'Pay with crypto'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Bitcoin, Monero, USDT y más. Sin bancos, sin rastro.' : 'Bitcoin, Monero, USDT and more. No banks, no trace.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-bolt"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'VPS activo en minutos' : 'VPS live in minutes'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Aprovisionamiento automático tras confirmar el pago.' : 'Auto-provisioned right after payment confirms.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'DMCA Ignored · Bulletproof' : 'DMCA Ignored · Bulletproof'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Infraestructura en Eslovenia. Ignoramos reportes de abuso.' : 'Infrastructure in Slovenia. We ignore abuse reports.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-eye-slash"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? '0% Logs de actividad' : '0% Activity logs'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'No registramos conexiones ni actividad de red.' : 'We do not log connections or network activity.'; ?></span>
                    </div>
                </div>
            </div>
            <p class="auth-pitch-footer">
                <span class="live-dot"></span>
                <?php echo $currentLang === 'es' ? 'Operando desde Eslovenia — fuera de jurisdicción directa de EE.UU.' : 'Operating from Slovenia — outside direct US jurisdiction'; ?>
            </p>
        </div>

        <!-- Right: form -->
        <div class="auth-form-col">
            <div class="auth-card">
                <?php if ($isCompletingRegistration): ?>
                    <h2><?php echo $lang['auth_complete_title']; ?></h2>
                    <div class="user-greeting">
                        <?php echo $lang['auth_greeting']; ?>
                        <?php if ($foundUser['username'] !== 'Unknown' && !empty($foundUser['username'])): ?>
                            <strong><?php echo htmlspecialchars($foundUser['username']); ?></strong>
                        <?php endif; ?>
                    </div>
                    <form id="completeRegisterForm"
                        data-msg-pass-mismatch="<?php echo htmlspecialchars($lang['auth_msg_pass_mismatch']); ?>"
                        data-msg-pass-short="<?php echo htmlspecialchars($lang['auth_msg_pass_short']); ?>"
                        data-msg-error-conn="<?php echo htmlspecialchars($lang['auth_msg_error_conn']); ?>"
                        data-msg-captcha="<?php echo htmlspecialchars($lang['captcha_missing'] ?? 'Please complete the security verification'); ?>">
                        <input type="hidden" name="code" value="<?php echo $code; ?>">

                        <?php if ($foundUser['username'] === 'Unknown' || empty($foundUser['username'])): ?>
                            <div class="form-group">
                                <label for="username"><?php echo $lang['auth_username']; ?></label>
                                <input type="text" id="username" name="username" required autocomplete="username"
                                    placeholder="<?php echo $lang['auth_username_placeholder']; ?>">
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="referral_code"><?php echo $lang['auth_referral']; ?></label>
                            <input type="text" id="referral_code" name="referral_code"
                                placeholder="<?php echo $lang['auth_referral_placeholder']; ?>"
                                value="<?php echo $referralCode; ?>">
                        </div>
                        <div class="form-group">
                            <label for="password"><?php echo $lang['auth_password']; ?></label>
                            <div style="position: relative;">
                                <input type="password" id="password" name="password" required autocomplete="new-password"
                                    placeholder="<?php echo $lang['auth_password_placeholder']; ?>">
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)"
                                    aria-label="<?php echo $lang['auth_toggle_password'] ?? 'Show password'; ?>"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #b0bec5; cursor: pointer; font-size: 1rem;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password"><?php echo $lang['auth_confirm_password']; ?></label>
                            <div style="position: relative;">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    autocomplete="new-password"
                                    placeholder="<?php echo $lang['auth_confirm_password_placeholder']; ?>">
                                <button type="button" class="password-toggle"
                                    onclick="togglePassword('confirm_password', this)"
                                    aria-label="<?php echo $lang['auth_toggle_password'] ?? 'Show password'; ?>"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #b0bec5; cursor: pointer; font-size: 1rem;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>" data-theme="dark" data-lang="<?php echo $currentLang; ?>"></div>
                        <br>
                        <button type="submit"
                            class="btn btn-primary btn-block"><?php echo $lang['auth_btn_complete']; ?></button>
                    </form>
                <?php else: ?>
                    <h2><?php echo $lang['auth_register_title']; ?></h2>

                    <!-- Telegram: quick access at top -->
                    <script async src="https://telegram.org/js/telegram-widget.js?22"></script>
                    <script src="/assets/js/telegram-auth.js" defer></script>
                    <button type="button" id="telegram-btn"
                        data-bot-id="<?php echo explode(':', TOKEN_TELEGRAM)[0]; ?>"
                        onclick="loginWithTelegram()"
                        style="width:100%; background:#24A1DE; color:white; display:flex; align-items:center; justify-content:center; gap:10px; border:none; cursor:pointer; padding:13px; border-radius:8px; font-weight:600; font-size:0.97rem; transition:background 0.2s; margin-bottom:18px;"
                        onmouseover="this.style.background='#1a8fc7'" onmouseout="this.style.background='#24A1DE'">
                        <i class="fab fa-telegram-plane" style="font-size:1.1rem;"></i>
                        <?php echo $lang['auth_telegram']; ?>
                    </button>

                    <!-- Divider -->
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                        <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
                        <span style="font-size:0.78rem; color:#556070; white-space:nowrap;">
                            <?php echo $currentLang === 'es' ? 'o regístrate con contraseña' : 'or sign up with password'; ?>
                        </span>
                        <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
                    </div>

                    <form id="registerForm"
                        data-msg-pass-mismatch="<?php echo htmlspecialchars($lang['auth_msg_pass_mismatch']); ?>"
                        data-msg-pass-short="<?php echo htmlspecialchars($lang['auth_msg_pass_short']); ?>"
                        data-msg-error-conn="<?php echo htmlspecialchars($lang['auth_msg_error_conn']); ?>"
                        data-msg-captcha="<?php echo htmlspecialchars($lang['captcha_missing'] ?? 'Please complete the security verification'); ?>">
                        <div class="form-group">
                            <label for="username"><?php echo $lang['auth_username']; ?></label>
                            <input type="text" id="username" name="username" required autocomplete="username"
                                placeholder="<?php echo $lang['auth_username_placeholder']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="referral_code"><?php echo $lang['auth_referral']; ?></label>
                            <input type="text" id="referral_code" name="referral_code"
                                placeholder="<?php echo $lang['auth_referral_placeholder']; ?>"
                                value="<?php echo $referralCode; ?>">
                        </div>
                        <div class="form-group">
                            <label for="password"><?php echo $lang['auth_password']; ?></label>
                            <div style="position: relative;">
                                <input type="password" id="password" name="password" required autocomplete="new-password"
                                    placeholder="<?php echo $lang['auth_password_placeholder']; ?>">
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)"
                                    aria-label="<?php echo $lang['auth_toggle_password'] ?? 'Show password'; ?>"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #b0bec5; cursor: pointer; font-size: 1rem;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password"><?php echo $lang['auth_confirm_password']; ?></label>
                            <div style="position: relative;">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    autocomplete="new-password"
                                    placeholder="<?php echo $lang['auth_confirm_password_placeholder']; ?>">
                                <button type="button" class="password-toggle"
                                    onclick="togglePassword('confirm_password', this)"
                                    aria-label="<?php echo $lang['auth_toggle_password'] ?? 'Show password'; ?>"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #b0bec5; cursor: pointer; font-size: 1rem;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>" data-theme="dark" data-lang="<?php echo $currentLang; ?>"></div>
                        <br>
                        <button type="submit"
                            class="btn btn-primary btn-block"><?php echo $lang['auth_btn_register']; ?></button>
                    </form>
                <?php endif; ?>

                <div id="message" class="message" role="alert"></div>
                <div class="auth-footer">
                    <?php if ($isCompletingRegistration): ?>
                        <a href="login"><?php echo $lang['auth_cancel_link']; ?></a>
                    <?php else: ?>
                        <?php echo $lang['auth_has_account']; ?> <a href="login"><?php echo $lang['auth_login_link']; ?></a>
                        |
                        <a href="index"><?php echo $lang['auth_home_link']; ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /auth-form-col -->
    </div><!-- /auth-split -->

    <script src="/assets/js/register.js" defer></script>
</body>

</html>