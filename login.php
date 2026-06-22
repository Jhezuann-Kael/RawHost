<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/repositories/UserRepository.php';

session_start();
require_once 'includes/lang_loader.php'; // Include generic language loader

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/index');
    exit;
}


if (isset($_GET['code'])) {
    $code = htmlspecialchars($_GET['code']); // Basic sanitization
    $repo = new UserRepository();

    $user = $repo->findByCode($code);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_superuser'] = (bool) $user['is_superuser']; // Ensure boolean

        $repo->clearCode($user['id']);

        header("Location: dashboard/");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['auth_login_title']; ?></title>
    <meta name="description" content="<?php echo $lang['auth_login_desc']; ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://rawhost.net/login">

    <!-- OpenGraph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://rawhost.net/login">
    <meta property="og:title" content="<?php echo $lang['auth_login_title']; ?>">
    <meta property="og:description" content="<?php echo $lang['auth_login_desc']; ?>">
    <meta property="og:image" content="https://rawhost.net/assets/op-image.webp">
    <meta property="og:locale" content="<?php echo $_SESSION['lang'] == 'es' ? 'es_ES' : 'en_US'; ?>">
    <meta property="og:site_name" content="RawHost">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://rawhost.net/login">
    <meta name="twitter:title" content="<?php echo $lang['auth_login_title']; ?>">
    <meta name="twitter:description" content="<?php echo $lang['auth_login_desc']; ?>">
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

<body class="auth-page login-page">

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
                    Sin KYC.<br>Sin logs.<br><em>Total libertad.</em>
                <?php else: ?>
                    No KYC.<br>Zero logs.<br><em>Total freedom.</em>
                <?php endif; ?>
            </h1>
            <p class="auth-pitch-sub">
                <?php echo $currentLang === 'es'
                    ? 'Accede a infraestructura VPS Bulletproof sin revelar tu identidad. Pagos con cripto, servidores activos en minutos.'
                    : 'Access Bulletproof VPS infrastructure without revealing your identity. Crypto payments, servers live in minutes.'; ?>
            </p>
            <div class="auth-feats">
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-user-secret"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'Sin KYC, sin verificación' : 'No KYC, no verification'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'No pedimos datos personales. Nunca.' : 'We never ask for personal data. Ever.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-eye-slash"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? '0% Logs de actividad' : '0% Activity logs'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'No registramos conexiones ni actividad de red.' : 'We do not log connections or network activity.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fab fa-bitcoin"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'Pagos 100% en Cripto' : '100% Crypto payments'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Bitcoin, Monero, USDT y más. Sin bancos, sin rastro.' : 'Bitcoin, Monero, USDT and more. No banks, no trace.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'DMCA Ignored · Bulletproof' : 'DMCA Ignored · Bulletproof'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Ignoramos reportes de abuso. Tu contenido, tus reglas.' : 'We ignore abuse reports. Your content, your rules.'; ?></span>
                    </div>
                </div>
                <div class="auth-feat">
                    <div class="auth-feat-icon"><i class="fas fa-bolt"></i></div>
                    <div class="auth-feat-text">
                        <strong><?php echo $currentLang === 'es' ? 'VPS activo en minutos' : 'VPS live in minutes'; ?></strong>
                        <span><?php echo $currentLang === 'es' ? 'Aprovisamiento automático tras confirmación de pago.' : 'Auto-provisioned right after payment confirms.'; ?></span>
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
                <h2><?php echo $lang['auth_login_title']; ?></h2>

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
                        <?php echo $currentLang === 'es' ? 'o continúa con contraseña' : 'or continue with password'; ?>
                    </span>
                    <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
                </div>

                <form id="loginForm"
                    data-msg-error-conn="<?php echo htmlspecialchars($lang['auth_msg_error_conn']); ?>"
                    data-msg-captcha="<?php echo htmlspecialchars($lang['captcha_missing'] ?? 'Please complete the security verification'); ?>">
                    <div class="form-group">
                        <label for="username"><?php echo $lang['auth_username']; ?></label>
                        <input type="text" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo $lang['auth_password']; ?></label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" required
                                autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)"
                                aria-label="<?php echo $lang['auth_toggle_password'] ?? 'Show password'; ?>"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:center;">
                        <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>" data-theme="dark" data-lang="<?php echo $currentLang; ?>"></div>
                    </div>
                    <br>
                    <button type="submit"
                        class="btn btn-primary btn-block"><?php echo $lang['auth_btn_login']; ?></button>
                </form>
                <div id="message" class="message" role="alert"></div>
                <div class="auth-footer">
                    <?php echo $lang['auth_no_account']; ?> <a
                        href="register"><?php echo $lang['auth_register_link']; ?></a> | <a
                        href="index"><?php echo $lang['auth_home_link']; ?></a>
                </div>
            </div>
        </div><!-- /auth-form-col -->
    </div><!-- /auth-split -->

    <script src="/assets/js/login.js" defer></script>
</body>

</html>