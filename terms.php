<?php
session_start();
require_once 'includes/lang_loader.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['terms_title']; ?></title>
    <meta name="description" content="<?php echo $lang['terms_meta_desc']; ?>">
    <link rel="canonical" href="https://rawhost.net/terms">

    <!-- OpenGraph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://rawhost.net/terms">
    <meta property="og:title" content="<?php echo $lang['terms_og_title']; ?>">
    <meta property="og:description" content="<?php echo $lang['terms_og_desc']; ?>">
    <meta property="og:image" content="https://rawhost.net/assets/op-image.webp">
    <meta property="og:locale" content="<?php echo ($currentLang === 'es') ? 'es_ES' : 'en_US'; ?>">
    <meta property="og:site_name" content="RawHost">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://rawhost.net/terms">
    <meta name="twitter:title" content="<?php echo $lang['terms_og_title']; ?>">
    <meta name="twitter:description" content="<?php echo $lang['terms_og_desc']; ?>">
    <meta name="twitter:image" content="https://rawhost.net/assets/op-image.webp">

    <!-- Discord / General Embeds -->
    <meta name="theme-color" content="#6366F1">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/favicon.ico'); ?>">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="/assets/css/terms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <a href="index" style="text-decoration:none; color:white; display:flex; align-items:center;">
                        <img src="/assets/logo/logo_standar.svg" alt="RawHost"
                            style="height: 28px; margin-right: 8px; border-radius: 100%; object-fit: contain;">
                        Raw<span>Host</span>
                    </a>
                </div>
                <div class="nav-links">
                    <a href="index#home"><?php echo $lang['nav_home']; ?></a>
                    <a href="index#features"><?php echo $lang['nav_features']; ?></a>
                    <a href="index#pricing"><?php echo $lang['nav_plans']; ?></a>
                    <a href="index#about"><?php echo $lang['nav_about']; ?></a>
                    <a href="index#contact"><?php echo $lang['nav_contact']; ?></a>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <!-- Lang Switcher Desktop -->
                    <div class="lang-switch-desktop" style="display: flex; gap: 8px;">
                        <a href="?lang=es" title="Español"
                            style="opacity: <?php echo $_SESSION['lang'] == 'es' ? '1' : '0.4'; ?>; transition: opacity 0.3s;"><img
                                src="https://flagcdn.com/w20/es.png" alt="ES" style="display:block;"></a>
                        <a href="?lang=en" title="English"
                            style="opacity: <?php echo $_SESSION['lang'] == 'en' ? '1' : '0.4'; ?>; transition: opacity 0.3s;"><img
                                src="https://flagcdn.com/w20/us.png" alt="EN" style="display:block;"></a>
                    </div>

                    <div class="auth-buttons" style="display: flex; gap: 10px; align-items: center;">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <span style="color: white; margin-right: 10px;"><?php echo $lang['auth_greeting']; ?>,
                                <a href="dashboard/index"
                                    style="color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.3s;"
                                    onmouseover="this.style.color='var(--secondary)'"
                                    onmouseout="this.style.color='var(--primary)'"><?php echo htmlspecialchars($_SESSION['username']); ?></a></span>
                            <a href="logout" class="btn btn-outline"
                                style="padding: 8px 15px; font-size: 0.9rem;"><?php echo $lang['btn_logout']; ?></a>
                        <?php else: ?>
                            <a href="login" class="btn btn-outline"
                                style="padding: 8px 15px; font-size: 0.9rem;"><?php echo $lang['btn_login']; ?></a>
                            <a href="register" class="btn btn-primary"
                                style="padding: 8px 15px; font-size: 0.9rem;"><?php echo $lang['btn_register']; ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-header">
        <div class="container">
            <h1><?php echo $lang['terms_h1']; ?></h1>
            <p style="font-size: 1.2rem; color: var(--text-muted);"><?php echo $lang['terms_subtitle']; ?></p>
        </div>
    </div>

    <section class="terms-content">
        <div class="container" style="max-width: 800px;">
            <p class="last-updated"><?php echo $lang['terms_last_updated']; ?></p>

            <div class="terms-section">
                <h3><span class="number">1</span> <?php echo $lang['terms_sec_1_title']; ?></h3>
                <p><?php echo $lang['terms_sec_1_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">2</span> <?php echo $lang['terms_sec_2_title']; ?></h3>
                <p><?php echo $lang['terms_sec_2_content_1']; ?></p>
                <p><?php echo $lang['terms_sec_2_content_2']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">3</span> <?php echo $lang['terms_sec_3_title']; ?></h3>
                <p><?php echo $lang['terms_sec_3_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">4</span> <?php echo $lang['terms_sec_4_title']; ?></h3>
                <p><?php echo $lang['terms_sec_4_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">5</span> <?php echo $lang['terms_sec_5_title']; ?></h3>
                <p><?php echo $lang['terms_sec_5_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">6</span> <?php echo $lang['terms_sec_6_title']; ?></h3>
                <p><?php echo $lang['terms_sec_6_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">7</span> <?php echo $lang['terms_sec_7_title']; ?></h3>
                <p><?php echo $lang['terms_sec_7_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">8</span> <?php echo $lang['terms_sec_8_title']; ?></h3>
                <p><?php echo $lang['terms_sec_8_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">9</span> <?php echo $lang['terms_sec_9_title']; ?></h3>
                <p><?php echo $lang['terms_sec_9_content']; ?></p>
            </div>

            <div class="terms-section">
                <h3><span class="number">10</span> <?php echo $lang['terms_sec_10_title']; ?></h3>
                <p><?php echo $lang['terms_sec_10_content']; ?></p>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <div class="logo">
                        <img src="/assets/logo/logo_standar.svg" alt="RawHost"
                            style="height: 28px; margin-right: 8px; border-radius: 100%; object-fit: contain;">
                        Raw<span>Host</span>
                    </div>
                    <p><?php echo $lang['footer_desc']; ?></p>
                </div>
                <div class="footer-col">
                    <h4><?php echo $lang['footer_prod']; ?></h4>
                    <ul>
                        <li><a href="index"><?php echo $lang['footer_vps']; ?></a></li>
                        <li><a href="index"><?php echo $lang['footer_domains']; ?></a></li>
                        <li><span
                                style="color: var(--text-muted); cursor: not-allowed;"><?php echo $lang['footer_dedicated']; ?></span>
                        </li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4><?php echo $lang['footer_company']; ?></h4>
                    <ul>
                        <li><a href="index#nosotros"><?php echo $lang['nav_about']; ?></a></li>
                        <li><a href="index#contacto"><?php echo $lang['nav_contact']; ?></a></li>
                        <li><a href="terms" style="color:var(--primary);"><?php echo $lang['footer_terms']; ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> RawHost.</p>
            </div>
        </div>
    </footer>

</body>

</html>