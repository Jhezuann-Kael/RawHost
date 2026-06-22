<?php
session_start();
require_once 'includes/lang_loader.php';
require_once 'api/config.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['meta_title']; ?></title>
    <meta name="description" content="<?php echo $lang['meta_description']; ?>">

    <link rel="preload" href="/assets/fonts/inter/latin.woff2" as="font" type="font/woff2" crossorigin>
    <meta name="robots" content="index, follow">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://rawhost.net/<?php echo $_SESSION['lang'] === 'es' ? '?lang=es' : ''; ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $lang['meta_title']; ?>">
    <meta property="og:description" content="<?php echo $lang['meta_description']; ?>">
    <meta property="og:image" content="https://rawhost.net/assets/op-image.webp">
    <meta property="og:locale" content="<?php echo $_SESSION['lang'] === 'es' ? 'es_ES' : 'en_US'; ?>">
    <meta property="og:site_name" content="RawHost">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="rawhost.net">
    <meta property="twitter:url" content="https://rawhost.net/<?php echo $_SESSION['lang'] === 'es' ? '?lang=es' : ''; ?>">
    <meta name="twitter:title" content="<?php echo $lang['meta_title']; ?>">
    <meta name="twitter:description" content="<?php echo $lang['meta_description']; ?>">
    <meta name="twitter:image" content="https://rawhost.net/assets/op-image.webp">

    <!-- Discord / General Embeds -->
    <meta name="theme-color" content="#6366F1">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/favicon.ico'); ?>">

    <link rel="stylesheet" href="/assets/css/index.min.css">

    <!-- Inline critical CSS (eliminates render-blocking style.css request) -->
    <style>
        <?php echo file_get_contents(__DIR__ . '/style.min.css'); ?>
    </style>

    <!-- Performance Preloads -->
    <link rel="preload" href="/assets/fonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fa-custom.css?v=<?php echo filemtime('assets/fa-custom.css'); ?>" as="style">
    <link rel="stylesheet" href="/assets/fa-custom.css?v=<?php echo filemtime('assets/fa-custom.css'); ?>">

    <link rel="stylesheet" href="/assets/fonts/webfonts.css">

    <link rel="canonical" href="https://rawhost.net/<?php echo $_SESSION['lang'] === 'es' ? '?lang=es' : ''; ?>">
    <link rel="alternate" hreflang="es" href="https://rawhost.net/?lang=es" />
    <link rel="alternate" hreflang="en" href="https://rawhost.net/" />
    <link rel="alternate" hreflang="x-default" href="https://rawhost.net/" />

    <!-- Google tag (gtag.js) — deferred to not block render -->
    <script async defer src="https://www.googletagmanager.com/gtag/js?id=AW-17899855343"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'AW-17899855343');
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "RawHost",
      "url": "https://rawhost.net/",
      "logo": "https://rawhost.net/assets/icon-site.webp",
      "description": <?php echo json_encode($lang['meta_description']); ?>,
      "sameAs": [
        "https://twitter.com/rawhost",
        "https://t.me/rawhost"
      ]
    }
    </script>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <img src="/assets/logo/logo_standar.svg" alt="RawHost – Bulletproof Offshore VPS" width="60" height="60"
                        style="height: 60px; border-radius: 100%; object-fit: contain;">Raw<span style="margin-left: -8px;">/Host</span>
                </div>
                <div class="nav-links">
                    <a href="#home" class="active"><?php echo $lang['nav_home']; ?></a>
                    <a href="#features"><?php echo $lang['nav_features']; ?></a>
                    <a href="#pricing"><?php echo $lang['nav_plans']; ?></a>
                    <a href="#about"><?php echo $lang['nav_about']; ?></a>
                    <a href="#contact"><?php echo $lang['nav_contact']; ?></a>
                    <a href="https://proxy.rawhost.net" target="_blank"
                        style="color: var(--accent-color); font-weight: 600;">Proxies</a>
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

                    <div class="auth-buttons">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <span style="color: white; margin-right: 10px;">
                                <?php echo $lang['auth_greeting']; ?>,
                                <a href="dashboard"
                                    style="color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.3s;"
                                    onmouseover="this.style.color='var(--secondary)'"
                                    onmouseout="this.style.color='var(--primary)'"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                            </span>
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

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menú">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-content">
                <!-- Lang Switcher Mobile -->
                <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 20px;">
                    <a href="?lang=es" class="<?php echo $_SESSION['lang'] == 'es' ? 'active-lang' : ''; ?>"
                        style="color: white; font-size: 1.2rem; text-decoration: none; padding: 5px; border-bottom: 2px solid <?php echo $_SESSION['lang'] == 'es' ? 'var(--primary)' : 'transparent'; ?>">ES</a>
                    <a href="?lang=en" class="<?php echo $_SESSION['lang'] == 'en' ? 'active-lang' : ''; ?>"
                        style="color: white; font-size: 1.2rem; text-decoration: none; padding: 5px; border-bottom: 2px solid <?php echo $_SESSION['lang'] == 'en' ? 'var(--primary)' : 'transparent'; ?>">EN</a>
                </div>

                <!-- Navigation Links -->
                <div
                    style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px;">
                    <a href="#home" class="mobile-menu-item"><i class="fas fa-home"></i>
                        <?php echo $lang['nav_home']; ?></a>
                    <a href="#features" class="mobile-menu-item"><i class="fas fa-star"></i>
                        <?php echo $lang['nav_features']; ?></a>
                    <a href="#pricing" class="mobile-menu-item"><i class="fas fa-tags"></i>
                        <?php echo $lang['nav_plans']; ?></a>
                    <a href="#about" class="mobile-menu-item"><i class="fas fa-info-circle"></i>
                        <?php echo $lang['nav_about']; ?></a>
                    <a href="#contact" class="mobile-menu-item"><i class="fas fa-envelope"></i>
                        <?php echo $lang['nav_contact']; ?></a>
                    <a href="https://proxy.rawhost.net" target="_blank" class="mobile-menu-item"
                        style="color: var(--accent-color);"><i class="fas fa-network-wired"></i> Proxies</a>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mobile-user-info">
                        <i class="fas fa-user-circle"
                            style="font-size: 2rem; color: var(--primary-color); margin-bottom: 10px;"></i>
                        <span style="color: white; font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </div>
                    <a href="dashboard" class="mobile-menu-item">
                        <i class="fas fa-tachometer-alt"></i> <?php echo $lang['btn_dashboard']; ?>
                    </a>
                    <a href="logout" class="mobile-menu-item">
                        <i class="fas fa-sign-out-alt"></i> <?php echo $lang['btn_logout']; ?>
                    </a>
                <?php else: ?>
                    <a href="login" class="mobile-menu-item">
                        <i class="fas fa-sign-in-alt"></i> <?php echo $lang['btn_login']; ?>
                    </a>
                    <a href="register" class="mobile-menu-item primary">
                        <i class="fas fa-user-plus"></i> <?php echo $lang['btn_register']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                mobileMenu.classList.toggle('active');
                mobileMenuBtn.querySelector('i').classList.toggle('fa-bars');
                mobileMenuBtn.querySelector('i').classList.toggle('fa-times');
            });

            // Close menu when clicking outside
            document.addEventListener('click', function (e) {
                if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    mobileMenu.classList.remove('active');
                    mobileMenuBtn.querySelector('i').classList.add('fa-bars');
                    mobileMenuBtn.querySelector('i').classList.remove('fa-times');
                }
            });

            // Close menu when clicking on a menu item
            const menuItems = mobileMenu.querySelectorAll('.mobile-menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function () {
                    mobileMenu.classList.remove('active');
                    mobileMenuBtn.querySelector('i').classList.add('fa-bars');
                    mobileMenuBtn.querySelector('i').classList.remove('fa-times');
                });
            });
        }
    </script>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero" id="home">
            <div class="container">
                <div class="hero-content">
                    <h1><?php echo $lang['hero_title_1']; ?>
                        <strong><?php echo $lang['hero_title_strong_1']; ?></strong><?php echo $lang['hero_title_2']; ?>
                        <strong><?php echo $lang['hero_title_strong_2']; ?></strong>
                    </h1>
                    <p><?php echo $lang['hero_desc']; ?></p>
                    <div class="hero-buttons">
                        <a href="register" class="btn btn-primary"><?php echo $lang['btn_register']; ?></a>
                        <a href="#pricing" class="btn btn-outline"><?php echo $lang['hero_btn_plans']; ?></a>
                    </div>
                    <p style="margin-top: 14px; font-size: 0.85rem; color: var(--text-muted);">
                        <?php echo $currentLang === 'es' ? '¿Ya tienes cuenta?' : 'Already have an account?'; ?>
                        <a href="login" style="color: var(--primary-color); text-decoration: none; font-weight: 600;"><?php echo $lang['btn_login']; ?></a>
                    </p>
                </div>
            </div>
        </section>

        <!-- Promo Banner -->
        <section class="promo-banner"
            style="background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 50px 0; text-align: center; color: white; position: relative;">
            <div class="container" style="position: relative; z-index: 2;">
                <div
                    style="font-size: 0.9rem; color: #4d9aff; text-transform: uppercase; letter-spacing: 3px; font-weight: 700; margin-bottom: 10px; display: inline-block; border: 1px solid #4d9aff; padding: 4px 12px; border-radius: 4px;">
                    <?php echo $lang['promo_badge']; ?>
                </div>
                <h2 style="font-size: 2.2rem; margin-bottom: 15px; font-weight: 600; letter-spacing: -0.5px;">
                    <?php echo $lang['promo_title']; ?> <span
                        style="color: white; border-bottom: 2px solid var(--accent-color); padding-bottom: 2px;"><?php echo $lang['promo_price']; ?></span>
                </h2>
                <p
                    style="font-size: 1.1rem; margin-bottom: 25px; color: var(--text-muted); max-width: 600px; margin-left: auto; margin-right: auto;">
                    <?php echo $lang['promo_desc']; ?>
                </p>
                <a href="login" class="btn btn-primary"
                    style="padding: 10px 30px; font-size: 1rem;">
                    <?php echo $lang['promo_btn']; ?> <i class="fas fa-chevron-right"
                        style="font-size: 0.8rem; margin-left: 5px;"></i>
                </a>
            </div>
        </section>
        <section class="pricing" id="pricing">
            <div class="container">
                <div class="section-title">
                    <h2><?php echo $lang['pricing_title']; ?></h2>
                    <p><?php echo $lang['pricing_subtitle']; ?></p>
                </div>
                <div id="plans-container">
                    <p style="color: white; text-align: center;">
                        <?php echo $lang['pricing_loading']; ?>
                    </p>
                </div>
                <noscript>
                    <div style="text-align: center; padding: 40px 0; color: #ccc;">
                        <p style="margin-bottom: 20px;"><?php echo $currentLang === 'es' ? 'Activa JavaScript para ver los planes, o' : 'Enable JavaScript to see our plans, or'; ?></p>
                        <a href="register" class="btn btn-primary" style="margin-right: 12px;"><?php echo $lang['btn_register']; ?></a>
                        <a href="login" class="btn btn-outline"><?php echo $lang['btn_login']; ?></a>
                    </div>
                </noscript>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="container">
                <div class="section-title">
                    <h2><?php echo $lang['features_title']; ?></h2>
                    <p><?php echo $lang['features_subtitle']; ?></p>
                </div>
                <?php
                $ctRows = $currentLang === 'es' ? [
                    ['Verificación de identidad', 'Email + teléfono obligatorio',       'Solo username — nunca'],
                    ['Logs de actividad',         'Conexiones y tráfico registrados',   'Cero logs'],
                    ['Cumplimiento DMCA',         'Actúan en 24–48h',                   'Ignorado'],
                    ['Métodos de pago',           'Tarjeta / PayPal (trazable)',         'BTC · XMR · USDT'],
                    ['Jurisdicción',              'EE.UU. / UK — subpoenas directas',   'Eslovenia — sin jurisdicción directa de EE.UU.'],
                    ['Reportes de abuso',         'Revisados y ejecutados',             'Ignorados'],
                    ['Datos con autoridades',     'Bajo solicitud formal',               'No hay datos que compartir'],
                    ['Suspensión por contenido',  'Alta — según ToS del proveedor',      'No — tus reglas'],
                    ['Soporte técnico',           'Tickets genéricos, días de espera',   'Personalizado · 24/7'],
                ] : [
                    ['Identity Verification',          'Email + phone required',               'Username only — never'],
                    ['Activity Logs',                  'Connections & traffic logged',          'Zero logs'],
                    ['DMCA Compliance',                'Acted on within 24–48h',               'Ignored'],
                    ['Payment Methods',                'Credit card / PayPal (traceable)',      'BTC · XMR · USDT'],
                    ['Jurisdiction',                   'US / UK — direct subpoenas',           'Slovenia — no direct US jurisdiction'],
                    ['Abuse Reports',                  'Reviewed and enforced',                'Ignored'],
                    ['Data Shared with Authorities',   'Upon formal request',                  'No data to share'],
                    ['Content Suspension',             'High — depends on provider ToS',       'No — your rules'],
                    ['Technical Support',              'Generic tickets, days to respond',     'Personalized · 24/7'],
                ];
                $ctStdLabel = $currentLang === 'es' ? 'VPS Estándar' : 'Standard VPS';
                ?>
                <div class="ct-wrap">
                    <table class="ct">
                        <thead>
                            <tr>
                                <th class="ct-h-crit"></th>
                                <th class="ct-h-std"><?php echo $ctStdLabel; ?></th>
                                <th class="ct-h-rh">RawHost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ctRows as $row): ?>
                            <tr>
                                <td class="ct-crit"><?php echo $row[0]; ?></td>
                                <td class="ct-bad"><i class="fas fa-times ct-x"></i><?php echo $row[1]; ?></td>
                                <td class="ct-good"><i class="fas fa-check ct-v"></i><?php echo $row[2]; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Network Speed Section -->
        <section class="network-speed"
            style="padding: 100px 0; background: linear-gradient(to bottom, #000000, #0a0a0a); position: relative; overflow: hidden; border-top: 1px solid rgba(255,255,255,0.05);">
            <!-- Background element for visual interest -->
            <div
                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; height: 100%; background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 70%); pointer-events: none;">
            </div>

            <div class="container" style="position: relative; z-index: 2; text-align: center;">
                <div class="section-title">
                    <h2>Blazing Fast Network for Bulletproof VPS</h2>
                    <p>Enterprise-grade connectivity included in <strong>ALL</strong> offshore VPS plans.</p>
                </div>

                <div class="speed-grid"
                    style="display: flex; flex-wrap: wrap; justify-content: center; gap: 40px; margin-top: 50px;">
                    <!-- Download -->
                    <div class="speed-card"
                        style="flex: 1 1 300px; max-width: 500px; background: rgba(255,255,255,0.03); padding: 40px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                        <div class="speed-value speed-value--download">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"
                                style="height: 2.5rem; fill: #00d9b5; vertical-align: middle; margin-right: 15px; opacity: 0.8;">
                                <path
                                    d="M144 480C64.5 480 0 415.5 0 336c0-62.8 40.2-116.2 96.2-135.9c-.1-2.7-.2-5.4-.2-8.1c0-88.4 71.6-160 160-160c59.3 0 111 32.2 138.7 80.2C409.9 102 428.3 96 448 96c53 0 96 43 96 96c0 12.2-2.3 23.8-6.4 34.6C596 238.4 640 290.1 640 352c0 70.7-57.3 128-128 128l-368 0zm79-167l80 80c9.4 9.4 24.6 9.4 33.9 0l80-80c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-39 39L344 184c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 134.1-39-39c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9z" />
                            </svg>1,272.32
                        </div>
                        <div
                            style="font-size: 1.2rem; color: #b3b3b3; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">
                            Mbps Download</div>
                    </div>
                    <!-- Upload -->
                    <div class="speed-card"
                        style="flex: 1 1 300px; max-width: 500px; background: rgba(255,255,255,0.03); padding: 40px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                        <div class="speed-value speed-value--upload">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"
                                style="height: 2.5rem; fill: #6aadff; vertical-align: middle; margin-right: 15px; opacity: 0.8;">
                                <path
                                    d="M144 480C64.5 480 0 415.5 0 336c0-62.8 40.2-116.2 96.2-135.9c-.1-2.7-.2-5.4-.2-8.1c0-88.4 71.6-160 160-160c59.3 0 111 32.2 138.7 80.2C409.9 102 428.3 96 448 96c53 0 96 43 96 96c0 12.2-2.3 23.8-6.4 34.6C596 238.4 640 290.1 640 352c0 70.7-57.3 128-128 128l-368 0zm79-217c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l39-39L296 392c0 13.3 10.7 24 24 24s24-10.7 24-24l0-134.1 39 39c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-80-80c-9.4-9.4-24.6-9.4-33.9 0l-80 80z" />
                            </svg>1,567.00
                        </div>
                        <div
                            style="font-size: 1.2rem; color: #b3b3b3; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">
                            Mbps Upload</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Flexibility Section -->
        <section class="flexibility"
            style="padding: 100px 0; background: linear-gradient(to bottom, var(--background-dark), var(--surface-dark));">
            <div class="container">
                <div class="section-title">
                    <h2><?php echo $lang['flex_title']; ?></h2>
                    <p><?php echo $lang['flex_subtitle']; ?></p>
                </div>
                <div class="flexibility-column">

                    <!-- Item 1: IPs -->
                    <div class="flexibility-row">
                        <div class="flexibility-img-wrapper">
                            <div class="bg-glow"
                                style="background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, rgba(0,0,0,0) 70%);">
                            </div>
                            <!-- Code Example: Network Config -->
                            <div class="code-window">
                                <div class="window-header">
                                    <div class="dot red"></div>
                                    <div class="dot yellow"></div>
                                    <div class="dot green"></div>
                                </div>
                                <div class="code-content">
                                    <span class="comment"># /etc/network/interfaces</span>
                                    auto eth0:1
                                    iface eth0:1 inet static
                                    address <span class="number">192.168.1.105</span>
                                    netmask <span class="number">255.255.255.0</span>
                                    gateway <span class="number">192.168.1.1</span>

                                    <span class="comment"># Additional IP Block</span>
                                    auto eth0:2
                                    iface eth0:2 inet static
                                    address <span class="number">10.0.0.5</span>
                                    netmask <span class="number">255.255.255.0</span>
                                </div>
                            </div>
                        </div>
                        <div class="flexibility-text-wrapper">
                            <h3 style="font-size: 2.5rem; margin-bottom: 20px; color: white;">
                                <?php echo $lang['flex_1_title']; ?>
                            </h3>
                            <p
                                style="font-size: 1.2rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 25px;">
                                <?php echo $lang['flex_1_desc']; ?>
                            </p>
                            <ul
                                style="list-style: none; padding: 0; margin-bottom: 30px; font-size: 1.1rem; color: #ccc;">
                                <li style="margin-bottom: 10px;"><i class="fas fa-check-circle"
                                        style="color: var(--primary-color); margin-right: 10px;"></i>
                                    <?php echo $lang['flex_1_li_1']; ?></li>
                                <li style="margin-bottom: 10px;"><i class="fas fa-check-circle"
                                        style="color: var(--primary-color); margin-right: 10px;"></i>
                                    <?php echo $lang['flex_1_li_2']; ?></li>
                                <li><i class="fas fa-check-circle"
                                        style="color: var(--primary-color); margin-right: 10px;"></i>
                                    <?php echo $lang['flex_1_li_3']; ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Item 2: OS -->
                    <div class="flexibility-row" style="align-items: center; gap: 40px;">
                        <div class="flexibility-text-wrapper" style="padding: 0;">
                            <h3 style="font-size: 1.8rem; margin-bottom: 12px; color: white;">
                                <?php echo $lang['flex_2_title']; ?>
                            </h3>
                            <p
                                style="font-size: 1rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 18px;">
                                <?php echo $lang['flex_2_desc']; ?>
                            </p>
                            <div style="display: flex; gap: 14px; font-size: 1.6rem; color: var(--text-muted);">
                                <i class="fab fa-windows" style="color: #00a4ef;" title="Windows"></i>
                                <i class="fab fa-ubuntu" style="color: #e95420;" title="Ubuntu"></i>
                                <i class="fab fa-linux" title="Linux"></i>
                                <i class="fab fa-centos" title="CentOS"></i>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                                    style="height: 1.1rem; fill: #d70a53;" title="Debian">
                                    <path
                                        d="M380.2 245.6c3-7.6 5.5-14 5.2-24.4l-4.3 9c4.4-13.2 4-27.1 3.6-40.4c-.2-6-.3-11.8 0-17.4l-1.8-.5c-1.5-45.2-40.6-93.1-75.3-109.4c-30-13.8-76.1-16.2-97.3-5.8c1.3-1.1 4.2-2 6.8-2.7l.3-.1c3.3-1 6-1.7 4-2.9c-19.2 1.9-24.9 5.5-31.1 9.4l-.1 0c-4.6 2.9-9.5 6-20.3 8.7c-3.5 3.4 1.7 2 5.8 .9l0 0c4.1-1.1 7.2-1.9-.1 2.4c-3.5 1-6.6 1.3-9.6 1.6l-.1 0c-8.3 .8-15.8 1.6-30.7 17c.8 1.3 3.4-.2 5.3-1.3l.1-.1c2.3-1.4 3.4-2-1.7 4.4c-19.1-2.4-60.3 43.7-69.1 59l4.6 .8c-3.2 8-6.8 14.8-10 20.8c-4.3 8.1-7.9 14.9-8.7 21.3c-.3 5.1-1 11-1.7 17.3l0 0c-.1 1-.2 2-.3 3l-.1 .6c-3 27.3-6.7 60.8 3.9 73l-1.3 13c.6 1.2 1.1 2.3 1.6 3.5c.2 .4 .4 .8 .5 1.1l0 0 0 0 0 0 0 0 0 0 0 0 0 0c1 2.1 2 4.2 3.3 6.2l-3 .2c7 22.1 10.8 22.5 15.1 22.9l0 0c4.4 .4 9.3 .9 18.7 24.2c-2.7-.9-5.5-1.9-9.4-7.2c-.5 4.1 5.8 16.3 13.1 25.8l-3.1 3.6c2.1 3.7 4.8 6.2 7.6 8.8l0 0 0 0c1 .9 2.1 1.9 3.1 2.9c-11.9-6.5 3.2 13.7 11.9 25.2c.8 1.1 1.5 2 2.2 2.9l0 0 0 0 0 0 0 0 0 0c1.4 1.9 2.5 3.4 2.9 4.1l2.4-4.2c-.3 6.1 4.3 13.9 13.1 24.7l7.3-.3c3 6 14 16.7 20.7 17.2l-4.4 5.8c8.1 2.6 10.3 4.3 12.7 6.2c2.6 2.1 5.4 4.3 16.1 8.1l-4.2-7.4c3.5 3 6.2 5.9 8.8 8.7l.1 .1c5.2 5.6 9.9 10.6 19.7 15.3c10.7 3.7 16.6 4.7 22.7 5.8c.3 0 .6 .1 .9 .1c5.4 .8 11.2 1.8 20.8 4.5c-1.1-.1-2.2-.1-3.3-.1h0c-2.3-.1-4.7-.1-7-.1l0 0 0 0 0 0 0 0 0 0 0 0 0 0c-14.4-.2-29.2-.4-42.7-5.2C107.8 480.5 19.5 367.2 26 250.6c-.6-9.9-.3-20.9 0-30.7c.4-13.5 .7-24.8-1.6-28.3l1-3.1c5.3-17.4 11.7-38.2 23.8-62.8l-.1-.2v-.1c.4 .4 3.4 3.4 8.8-5.8c.8-1.8 1.6-3.7 2.4-5.6c.5-1.1 .9-2.2 1.4-3.2c2.5-6.1 5.1-12.3 8.4-17.9l2.6-.6c1.7-10.1 17-23.8 29.8-35.2l1.1-1c5.7-5.1 10.7-9.7 13.6-13.1l.7 4.4c17-15.9 44.6-27.5 65.6-36.4l.5-.2c4.8-2 9.3-3.9 13.3-5.7c-3.4 3.8 2.2 2.7 10 1c4.8-1 10.4-2.1 15.3-2.4l-3.9 2.1c-2.7 1.4-5.4 2.8-8 4.6c8.1-2 11.7-1.4 15.7-.8l.3 0c3.5 .6 7.3 1.2 14.6 .2c-5.6 .8-12.3 3-11.2 3.8c7.9 .9 12.8-.1 17.2-1l.2 0c5.5-1.1 10.3-2 19.3 .9l-1-4.8c7.3 2.6 12.7 4.3 17.5 5.8l.5 .1c10 3 17.6 5.3 34.2 14.1c3.2 .2 5.3-.5 7.4-1.2l.1 0c3.6-1.1 7-2.1 15.2 1.2c.3 .5 .5 1 .7 1.4c.1 .2 .2 .5 .3 .7l0 .1c1 2.6 1.8 4.6 14.6 12.1c1.7-.7-2.7-4.7-6.4-8.2c0 0 0 0-.1-.1c-.2-.1-.3-.3-.5-.4c32.2 17.3 67.3 54.1 78 93.5c-6-11.1-5.2-5.5-4.3 .5c.6 4 1.2 8.1-.2 7.5c4.5 12.1 8.1 24.5 10.4 37.4l-.8-2.9-.1-.3c-3.3-11.9-9.6-34.3-19.9-49.3c-.4 4.3-2.8 3.9-5.2 3.5l-.1 0 0 0c-3.3-.6-6.2-1.1-1.9 12.6c2.6 3.8 3.1 2.4 3.5 1.1l0 0c.5-1.5 .9-2.7 4.7 5.2c.1 4.1 1 8.2 2.1 12.7l0 0 0 0 .1 .6c.1 .3 .1 .5 .2 .8l.1 .6c.6 2.6 1.3 5.4 1.8 8.4c-1.1-.2-2.3-2.2-3.4-4.2c-1.4-2.4-2.8-4.7-3.7-3.2c2.4 11.5 6.5 17.4 8 18.3c-.3 .6-.6 .7-1.1 .7c-.8 0-1.8 .1-1.9 5.3c.7 13.7 3.3 12.5 5.3 11.6l0 0c.6-.3 1.2-.6 1.7-.4c-.6 2.5-1.6 5.1-2.7 7.9c-2.8 7.1-6 15.4-3.4 26.1c-.8-3-2-6-3.1-8.9l-.1-.4c-.2-.5-.4-1-.6-1.5l0 0c-.3-.8-.6-1.6-.9-2.3c-.6 4.4-.3 7.7-.1 10.6c0 .2 0 .5 0 .7c.4 5.3 .7 10-3 19.9c4.3-14.2 3.8-26.9-.2-20.8c1 10.9-3.7 20.4-8 28.9l-.1 .2c-3.6 7.1-6.8 13.5-5.9 19.3l-5.2-7.1c-7.5 10.9-7 13.3-6.5 15.5l0 .1c.5 1.9 1 3.8-3.4 10.8c1.7-2.9 1.3-3.6 1-4.2l0 0c-.4-.8-.7-1.5 1.7-5.1c-1.6 .1-5.5 3.9-10.1 8.5c-3.9 3.9-8.5 8.4-12.8 11.8c-37.5 30.1-82.3 34-125.6 17.8c.2-1-.2-2.1-3.1-4.1c-36.8-28.2-58.5-52.1-50.9-107.5c2.1-1.6 3.6-5.8 5.3-10.8l0 0 0 0 .2-.4 .1-.3 0-.1c2.9-8.4 6.5-18.8 14.3-23.8c7.8-17.3 31.3-33.3 56.4-33.7c25.6-1.4 47.2 13.7 58.1 27.9c-19.8-18.4-52.1-24-79.7-10.4c-28.2 12.7-45 43.8-42.5 74.7c.3-.4 .6-.6 .9-.8l0 0s0 0 0 0c0 0 .1-.1 .1-.1l.1-.1c.6-.5 1.1-.9 1.4-3.3c-.9 60.2 64.8 104.3 112.1 82l.6 1.3c12.7-3.5 15.9-6.5 20.3-10.7l.1-.1 0 0c2.2-2.1 4.7-4.5 8.9-7.3c-.3 .7-1.3 1.7-2.4 2.7c-2.2 2.1-4.6 4.5-1.6 4.6c5-1.3 18.5-13.4 28.5-22.3l0 0 0 0c.6-.5 1.2-1 1.7-1.5c1.5-1.3 2.8-2.5 4-3.6l0 0 .3-.3c1.9-4.2 1.6-5.6 1.3-7l0-.1c-.4-1.6-.8-3.3 2.4-9.6l7.3-3.7c.8-2.1 1.5-4.1 2.2-6c.2-.6 .5-1.2 .7-1.8l-.4-.2zM349.3 34.3l-.2-.1 .2 .1 0 0zM247.8 334.1c-6-3-13.7-8.9-14.8-11.4l-.4 .3c-.3 .6-.5 1.3-.2 2.2c-12.2-5.7-23.4-14.3-32.6-24.9c4.9 7.1 10.1 14.1 17 19.5c-6.9-2.3-15.1-11.8-21.6-19.3l-.1-.1c-4.3-5-7.9-9.1-9.7-9.5c19.8 35.5 80.5 62.3 112.3 49c-14.7 .5-33.4 .3-49.9-5.8zm79.3-119.7l-.1-.2c-.5-1.5-1.1-3.1-1.7-3.4c1.4-5.8 5.4-10.7 4.4 4.6c-1 3.8-1.8 1.5-2.6-1zm-4.2 22.2c-1.3 7.9-5 15.5-10.1 22.5c.2-2-1.2-2.4-2.6-2.8l0 0c-2.9-.8-5.9-1.6 5.6-16.1c-.5 1.9-2.1 4.6-3.7 7.3l0 0 0 0-.3 .4c-3.6 5.9-6.7 11 4 4.3l1-1.8c2.6-4.5 5-8.8 6-13.8h.1zm-55.6 33.9c7.1 .6 14.1 .6 21-1.1c-2.5 2.4-5.2 4.8-8.3 7.2c-11.1-1.7-21.2-6-12.7-6.1zm-92.6 11.6c3.6 7.1 6.4 11.5 9 15.7l.1 .2c2.3 3.7 4.4 7.1 6.8 11.7c-5.1-4.2-8.7-9.5-12.5-15l-.3-.5c-1.4-2.1-2.8-4.2-4.4-6.2l1.2-5.9h.1zm7.5-9.6c1.6 3.3 3.2 6.4 5.7 9.1l2.6 7.7-1.3-2.1c-3.2-5.3-6.3-10.6-8-16.7l.8 1.6 .2 .4zm238.9-41.6c-2.3 17.4-7.7 34.6-16 50.3c7.6-14.9 12.5-30.9 14.8-47.2l1.2-3.1zM35.6 110.6c.4 .8 1.4 .5 2.3 .3c1.9-.5 3.6-.9-.1 7.6c-.5 .3-1 .7-1.5 1l0 0 0 0c-1.4 .9-2.8 1.9-3.9 3c1.9-3.8 3.5-7.4 3.2-11.9zM25.3 152.3c-.7 3.7-1.5 7.9-3.4 13.9c.2-1.9 0-3.5-.2-4.9l0-.1c-.4-3.4-.7-6.3 4.3-12.8c-.3 1.2-.5 2.5-.7 3.8v.1z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flexibility-img-wrapper" style="flex: 0 0 450px; max-width: 450px;">
                            <!-- OS List: compact -->
                            <div class="code-window" style="font-size: 0.82rem;">
                                <div class="window-header">
                                    <div class="dot red"></div>
                                    <div class="dot yellow"></div>
                                    <div class="dot green"></div>
                                    <span
                                        style="color: #b3b3b3; font-size: 11px; margin-left: 8px;">available_images.txt</span>
                                </div>
                                <div class="code-content"
                                    style="font-family: 'Inter', sans-serif; padding: 0px 28px; line-height: 1; overflow: hidden;">

                                    <!-- Grid Header -->
                                    <div
                                        style="display: grid; grid-template-columns: 1fr auto; gap: 10px; border-bottom: 1px solid #333; padding-bottom: 6px; margin-bottom: 8px; color: #b3b3b3; font-size: 0.75rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; opacity: 0.7; line-height: normal;">
                                        <span>Distribution / App</span>
                                        <span>Version</span>
                                    </div>

                                    <div id="osListDisplay" style="color:var(--text-muted);font-size:0.8rem;">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item 3: Renta -->
                    <div class="flexibility-row">
                        <div class="flexibility-img-wrapper">
                            <div class="bg-glow"
                                style="background: radial-gradient(circle, rgba(0, 255, 204, 0.15) 0%, rgba(0,0,0,0) 70%);">
                            </div>
                            <!-- Interactive Slider: Flexible Hiring -->
                            <div class="code-window" style="background: #1e1e1e; padding: 0;">
                                <div class="window-header">
                                    <div class="dot red"></div>
                                    <div class="dot yellow"></div>
                                    <div class="dot green"></div> <span
                                        style="color: #b3b3b3; font-size: 12px; margin-left: 10px;">hourly_calculator.exe</span>
                                </div>
                                <div class="code-content"
                                    style="padding: 25px; white-space: normal; overflow: visible; ">
                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <div style="font-size: 0.9rem; color: #b3b3b3; margin-bottom: 5px;">ESTIMATED
                                            COST
                                        </div>
                                        <div id="price-display"
                                            style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);">
                                            $1.61</div>
                                        <div style="font-size: 0.8rem; color: #999;">based on Start Plan</div>
                                    </div>

                                    <div
                                        style="margin-bottom: 10px; display: flex; justify-content: space-between; color: #fff; font-size: 0.9rem;">
                                        <span>Duration:</span>
                                        <span id="hours-display" style="color: var(--accent-color); font-weight: 600;">1
                                            Week (168h)</span>
                                    </div>

                                    <label for="price-slider" class="sr-only"
                                        style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;">Duration
                                        slider</label>
                                    <input type="range" id="price-slider" min="1" max="700" value="168"
                                        aria-label="Select rental duration in hours"
                                        style="width: 100%; -webkit-appearance: none; height: 6px; background: #333; border-radius: 5px; outline: none; cursor: pointer;">

                                    <div
                                        style="display: flex; justify-content: space-between; margin-top: 5px; color: #999; font-size: 0.7rem;">
                                        <span>1h</span>
                                        <span>1 Month (700h)</span>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const slider = document.getElementById('price-slider');
                                    const priceDisplay = document.getElementById('price-display');
                                    const hoursDisplay = document.getElementById('hours-display');

                                    // User requests $6.91 for 700 hours (1 Month)
                                    // Hourly rate = 6.91 / 700
                                    const hourlyRate = 6.91 / 700;

                                    function updatePrice() {
                                        const hours = parseInt(slider.value);
                                        const price = hours * hourlyRate;

                                        let priceText = '$' + price.toFixed(2);
                                        if (price < 0.01) priceText = '$' + price.toFixed(4);

                                        priceDisplay.innerText = priceText;

                                        let timeText = hours + ' hours';

                                        if (hours >= 700) {
                                            timeText = '1 Month (700h)';
                                        } else if (hours >= 168) {
                                            const weeks = (hours / 168).toFixed(1);
                                            timeText = weeks + ' weeks (' + hours + 'h)';
                                        } else if (hours >= 24) {
                                            const days = (hours / 24).toFixed(1);
                                            timeText = days + ' days (' + hours + 'h)';
                                        }

                                        hoursDisplay.innerText = timeText;
                                    }

                                    slider.addEventListener('input', updatePrice);
                                    // Init
                                    updatePrice();
                                });
                            </script>
                        </div>
                        <div class="flexibility-text-wrapper">
                            <h3 style="font-size: 2.5rem; margin-bottom: 20px; color: white;">
                                <?php echo $lang['flex_3_title']; ?>
                            </h3>
                            <p
                                style="font-size: 1.2rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 25px;">
                                <?php echo $lang['flex_3_desc']; ?>
                            </p>
                            <a href="#pricing" class="btn btn-outline"
                                style="border-color: var(--accent-color); color: var(--accent-color);"><?php echo $lang['flex_3_btn']; ?></a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- About Us Section -->
        <section class="about-us" id="about"
            style="padding: 100px 0; text-align: center; background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('assets/mr_robot_lan.webp') no-repeat center center/cover; border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div class="container" style="max-width: 900px;">
                <div class="section-title">
                    <h2 style="font-size: 2.8rem;"><?php echo $lang['about_title']; ?></h2>
                    <div style="width: 60px; height: 4px; background: var(--primary-color); margin: 20px auto;"></div>
                </div>
                <div class="about-content" style="font-size: 1.2rem; line-height: 1.8; color: #e0e0e0;">
                    <p style="margin-bottom: 30px;"><?php echo $lang['about_p1']; ?></p>
                    <p style="margin-bottom: 30px;"><?php echo $lang['about_p2']; ?></p>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const langData = {
                    loading: "<?php echo $lang['pricing_loading']; ?>",
                    error: "<?php echo $lang['pricing_error']; ?>",
                    btn: "<?php echo $lang['pricing_btn']; ?>",
                    per_month: "<?php echo $lang['pricing_per_month']; ?>"
                };

                fetch('api/plans/list')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const container = document.getElementById('plans-container');

                            const basicNames = ['start', 'boost', 'go'];
                            const basicPlans = data.data.filter(p => basicNames.some(n => p.name.toLowerCase().includes(n)));
                            let premiumPlans = data.data.filter(p => !basicNames.some(n => p.name.toLowerCase().includes(n)));

                            const premiumOrder = ['pro', 'max', 'ultra', 'power', 'titan'];
                            premiumPlans.sort((a, b) => {
                                const ai = premiumOrder.findIndex(n => a.name.toLowerCase().includes(n));
                                const bi = premiumOrder.findIndex(n => b.name.toLowerCase().includes(n));
                                return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
                            });

                            function buildRow(plan) {
                                const price = parseFloat(plan.price).toFixed(2);
                                const isStart = plan.name.toLowerCase().includes('start');
                                const osHtml = isStart
                                    ? '<span class="pt-os"><i class="fab fa-linux"></i> Linux</span>'
                                    : '<span class="pt-os"><i class="fab fa-windows" style="color:#00a4ef;"></i><i class="fab fa-linux" style="margin-left:4px;"></i> Win / Linux</span>';
                                const badge = plan.isFeatured ? '<span class="pt-badge">POPULAR</span>' : '';
                                const btnClass = plan.isFeatured ? 'pt-btn-primary' : 'pt-btn-outline';
                                return `<tr class="${plan.isFeatured ? 'pt-featured' : ''}">
                                    <td><span class="pt-name">${plan.name}</span>${badge}</td>
                                    <td><strong>${plan.cpu}</strong> vCPU</td>
                                    <td><strong>${plan.ram} GB</strong></td>
                                    <td><strong>${plan.disk} GB</strong> NVMe</td>
                                    <td>1000 MB/s</td>
                                    <td>${osHtml}</td>
                                    <td class="pt-price">$${price}<span class="pt-per">${langData.per_month}</span></td>
                                    <td><a href="register" class="pt-btn ${btnClass}">${langData.btn}</a></td>
                                </tr>`;
                            }

                            function buildTable(plans) {
                                return `<div class="plans-table-wrap">
                                    <table class="plans-table">
                                        <thead><tr>
                                            <th>Plan</th>
                                            <th><i class="fas fa-microchip" style="margin-right:5px;"></i>CPU</th>
                                            <th><i class="fas fa-memory" style="margin-right:5px;"></i>RAM</th>
                                            <th><i class="fas fa-hdd" style="margin-right:5px;"></i>Disk</th>
                                            <th><i class="fas fa-tachometer-alt" style="margin-right:5px;"></i>Speed</th>
                                            <th>OS</th>
                                            <th>Price</th>
                                            <th></th>
                                        </tr></thead>
                                        <tbody>${plans.map(buildRow).join('')}</tbody>
                                    </table>
                                </div>`;
                            }

                            let html = '';

                            if (basicPlans.length) {
                                html += `<div class="plans-group-header">
                                    <h3><i class="fab fa-linux" style="margin-right:7px;color:#aaa;"></i>Basic Plans</h3>
                                    <div class="plans-group-sep"></div>
                                    <p>Entry level</p>
                                </div>`;
                                html += buildTable(basicPlans);
                            }

                            if (premiumPlans.length) {
                                html += '<hr class="plans-section-divider">';
                                html += `<div class="plans-group-header">
                                    <h3><i class="fas fa-star" style="margin-right:7px;color:var(--primary-color);"></i>Premium Plans</h3>
                                    <div class="plans-group-sep"></div>
                                    <p>Win / Linux &bull; Full power</p>
                                </div>`;
                                html += buildTable(premiumPlans);
                            }

                            container.innerHTML = html;
                        } else {
                            document.getElementById('plans-container').innerHTML = '<p style="color:red;text-align:center;">' + langData.error + '</p>';
                        }
                    })
                    .catch(() => {
                        document.getElementById('plans-container').innerHTML = '<p style="color:red;text-align:center;">' + langData.error + '</p>';
                    });
            });
        </script>

        <!-- Contact Section -->
        <!-- Lazy-load hCaptcha only when contact form is near viewport -->
        <script>
            (function () {
                var loaded = false;
                var obs = new IntersectionObserver(function (entries) {
                    if (entries[0].isIntersecting && !loaded) {
                        loaded = true;
                        var s = document.createElement('script');
                        s.src = 'https://js.hcaptcha.com/1/api.js';
                        s.async = true;
                        document.head.appendChild(s);
                        obs.disconnect();
                    }
                }, { rootMargin: '400px' });
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('contact');
                    if (el) obs.observe(el);
                });
            })();
        </script>
        <section class="contact-section" id="contact"
            style="padding: 80px 0; background: linear-gradient(to bottom, var(--surface-dark), var(--background-dark));">
            <div class="container" style="max-width: 1100px;">
                <!-- Two-column: info left, form right -->
                <div style="display: grid; grid-template-columns: 1fr 420px; gap: 60px; align-items: start;">

                    <!-- Left: info -->
                    <div style="padding-top: 20px;">
                        <p
                            style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--primary-color); margin-bottom: 12px;">
                            Get in touch</p>
                        <h2
                            style="font-size: 2.2rem; font-weight: 800; color: white; margin-bottom: 16px; line-height: 1.2;">
                            <?php echo $lang['contact_title']; ?>
                        </h2>
                        <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 30px;">
                            <?php echo $lang['contact_subtitle']; ?>
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 18px;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div
                                    style="width: 42px; height: 42px; border-radius: 10px; background: rgba(0,102,255,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fab fa-telegram" style="color: #0088cc; font-size: 1.1rem;"></i>
                                </div>
                                <div>
                                    <div
                                        style="font-size: 0.75rem; color: #777; text-transform: uppercase; letter-spacing: 1px;">
                                        Telegram</div>
                                    <a href="https://t.me/rawhost" target="_blank"
                                        style="color: white; font-weight: 600; font-size: 0.95rem; text-decoration: none;">@rawhost</a>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div
                                    style="width: 42px; height: 42px; border-radius: 10px; background: rgba(0,102,255,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-lock" style="color: var(--primary-color); font-size: 1rem;"></i>
                                </div>
                                <div>
                                    <div
                                        style="font-size: 0.75rem; color: #777; text-transform: uppercase; letter-spacing: 1px;">
                                        Security</div>
                                    <span
                                        style="color: #ccc; font-size: 0.9rem;"><?php echo $lang['contact_secure']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: form card -->
                    <div
                        style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 32px 28px;">
                        <h3
                            style="font-size: 1.1rem; font-weight: 700; color: white; margin: 0 0 22px; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo $lang['contact_btn'] ?? 'Send Message'; ?>
                        </h3>
                        <form id="contactForm">
                            <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 16px;">
                                <div>
                                    <label for="contactName"
                                        style="display:block;margin-bottom:5px;font-size:0.78rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;"><?php echo $lang['contact_name']; ?></label>
                                    <input type="text" id="contactName" name="name" required
                                        style="width:100%;padding:10px 12px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:white;font-size:0.9rem;box-sizing:border-box;"
                                        placeholder="">
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div>
                                        <label for="contactSubject"
                                            style="display:block;margin-bottom:5px;font-size:0.78rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;"><?php echo $lang['contact_subject']; ?></label>
                                        <input type="text" id="contactSubject" name="subject" required
                                            style="width:100%;padding:10px 12px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:white;font-size:0.9rem;box-sizing:border-box;"
                                            placeholder="">
                                    </div>
                                    <div>
                                        <label for="contactInfo"
                                            style="display:block;margin-bottom:5px;font-size:0.78rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;"><?php echo $lang['contact_info']; ?></label>
                                        <input type="text" id="contactInfo" name="contact" required
                                            style="width:100%;padding:10px 12px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:white;font-size:0.9rem;box-sizing:border-box;"
                                            placeholder="">
                                    </div>
                                </div>
                                <div>
                                    <label for="contactMessage"
                                        style="display:block;margin-bottom:5px;font-size:0.78rem;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;"><?php echo $lang['contact_message']; ?></label>
                                    <textarea id="contactMessage" name="message" rows="4"
                                        style="width:100%;padding:10px 12px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:white;font-size:0.9rem;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                        placeholder=""></textarea>
                                </div>
                            </div>
                            <!-- hCaptcha: horizontal scroll on overflow for small cards -->
                            <div style="overflow-x: auto; margin-bottom: 14px; scrollbar-width: thin;">
                                <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"
                                    data-theme="dark"></div>
                            </div>
                            <div id="contactFormMessage" role="alert"
                                style="margin-bottom: 14px; padding: 12px; border-radius: 8px; display: none; font-size: 0.88rem;">
                            </div>
                            <button type="submit" class="btn btn-primary" id="contactSubmitBtn"
                                style="width: 100%; padding: 13px; font-size: 0.95rem; font-weight: 600;">
                                <i class="fas fa-paper-plane"></i> <?php echo $lang['contact_btn']; ?>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </section>
        <!-- Other Projects Section -->
        <section class="other-projects"
            style="padding: 60px 0; text-align: center; background: rgba(0, 0, 0, 0.4); border-top: 1px solid rgba(255,255,255,0.05);">
            <div class="container">
                <h3 style="margin-bottom: 20px; font-size: 1.8rem; color: #fff;">Need High-Quality Proxies?</h3>
                <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 1.1rem;">Check out our specialized
                    proxy service.</p>
                <a href="https://proxy.rawhost.net" target="_blank" class="btn btn-outline"
                    style="border-color: var(--primary-color); padding: 12px 30px; font-size: 1rem;">
                    <i class="fas fa-external-link-alt" style="margin-right: 8px;"></i> Visit proxy.rawhost.net
                </a>
            </div>
        </section>

    </main>

    <script>
        document.getElementById('contactForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = document.getElementById('contactSubmitBtn');
            const messageDiv = document.getElementById('contactFormMessage');
            const originalBtnText = submitBtn.innerHTML;

            // Translations for JS
            const txtSending = "<?php echo $lang['contact_sending']; ?>";
            const txtError = "<?php echo $lang['contact_error']; ?>";

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + txtSending;
            messageDiv.style.display = 'none';
            // Get hCaptcha response
            const captchaResponse = typeof hcaptcha !== 'undefined' ? hcaptcha.getResponse() : '';
            if (!captchaResponse) {
                messageDiv.style.display = 'block'; messageDiv.style.background = 'rgba(255, 71, 87, 0.2)'; messageDiv.style.borderLeft = '4px solid #ff4757'; messageDiv.style.color = '#ff4757';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> <?php echo $lang["captcha_missing"] ?? "Please complete the security verification."; ?>';
                submitBtn.disabled = false; submitBtn.innerHTML = originalBtnText;
                return;
            }
            const formData = { name: document.getElementById('contactName').value, subject: document.getElementById('contactSubject').value, contact: document.getElementById('contactInfo').value, message: document.getElementById('contactMessage').value, 'h-captcha-response': captchaResponse };
            try {
                const response = await fetch('/api/contact', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData) });
                const result = await response.json();
                if (response.ok && result.status === 'success') {
                    messageDiv.style.display = 'block'; messageDiv.style.background = 'rgba(46, 213, 115, 0.2)'; messageDiv.style.borderLeft = '4px solid #2ed573'; messageDiv.style.color = '#2ed573';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message; document.getElementById('contactForm').reset();
                } else {
                    messageDiv.style.display = 'block'; messageDiv.style.background = 'rgba(255, 71, 87, 0.2)'; messageDiv.style.borderLeft = '4px solid #ff4757'; messageDiv.style.color = '#ff4757';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.message || txtError);
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.style.display = 'block'; messageDiv.style.background = 'rgba(255, 71, 87, 0.2)'; messageDiv.style.borderLeft = '4px solid #ff4757'; messageDiv.style.color = '#ff4757';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + txtError;
            } finally { submitBtn.disabled = false; submitBtn.innerHTML = originalBtnText; }
        });
    </script>
    <script>
    (function () {
        const PRIORITY = [
            { match: 'debian',     label: 'Debian',     icon: 'fab fa-debian',  color: '#D70A53', type: 'os' },
            { match: 'ubuntu',     label: 'Ubuntu',     icon: 'fab fa-ubuntu',  color: '#E95420', type: 'os' },
            { match: 'windows',    label: 'Windows',    icon: 'fab fa-windows', color: '#0078D4', type: 'os' },
            { match: 'cloudpanel', label: 'CloudPanel', icon: 'fas fa-cube',    color: '#5B8CFF', type: 'app' },
            { match: 'centos',     label: 'CentOS',     icon: 'fab fa-centos',  color: '#932279', type: 'os' },
            { match: 'alma',       label: 'AlmaLinux',  icon: 'fas fa-leaf',    color: '#10B981', type: 'os' },
            { match: 'rocky',      label: 'Rocky',      icon: 'fas fa-leaf',    color: '#F97316', type: 'os' },
            { match: 'wordpress',  label: 'WordPress',  icon: 'fab fa-linux',   color: '#21759B', type: 'app' },
            { match: 'cpanel',     label: 'cPanel',     icon: 'fas fa-cube',    color: '#FF6C2C', type: 'app' },
            { match: 'nextcloud',  label: 'Nextcloud',  icon: 'fab fa-linux',   color: '#0082C9', type: 'app' },
        ];

        function getVersion(name) {
            const m = name.match(/[\d]+(?:\.[\d]+)?(?:\s+\w+)?/);
            return m ? m[0] : '';
        }

        function renderRow(icon, color, label, version, isLast) {
            return `<div style="display:grid;grid-template-columns:1fr auto;gap:10px;${isLast ? '' : 'margin-bottom:4px;'}align-items:center;line-height:normal;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="${icon}" style="color:${color};font-size:1.1rem;width:20px;text-align:center;"></i>
                    <span style="color:#eee;">${label}</span>
                </div>
                <span class="string" style="color:var(--accent-color);font-weight:500;">${version}</span>
            </div>`;
        }

        fetch('/api/plans/list')
            .then(r => r.json())
            .then(d => {
                if (!d.success || !d.data.length) return;
                const allOS  = new Map();
                const allApp = new Map();
                d.data.forEach(plan => {
                    (plan.available_os_image_versions || []).forEach(o => {
                        const key = o.name.toLowerCase();
                        if (!allOS.has(key)) allOS.set(key, o.name);
                    });
                    (plan.available_applications || []).forEach(a => {
                        const key = a.name.toLowerCase();
                        if (!allApp.has(key)) allApp.set(key, a.name);
                    });
                });

                const rows = [];
                const seen = new Set();
                PRIORITY.forEach(p => {
                    const pool = p.type === 'app' ? allApp : allOS;
                    for (const [key, name] of pool) {
                        if (key.includes(p.match) && !seen.has(key)) {
                            seen.add(key);
                            rows.push({ icon: p.icon, color: p.color, label: p.label, version: getVersion(name) });
                            break;
                        }
                    }
                });

                const MAX = 6;
                const visible = rows.slice(0, MAX);
                const el = document.getElementById('osListDisplay');
                if (!el) return;
                el.innerHTML = visible.map((r, i) => renderRow(r.icon, r.color, r.label, r.version, i === visible.length - 1)).join('');
            })
            .catch(() => {});
    })();
    </script>
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <div class="logo">
                        <img src="/assets/logo/logo_standar.svg" alt="RawHost – Bulletproof Offshore VPS" width="60" height="60"
                            style="height: 60px; border-radius: 100%; object-fit: contain;">Raw<span style="margin-left: -8px;">/Host</span>
                    </div>
                    <p><?php echo $lang['footer_desc']; ?></p>
                    <div class="social-links">
                        <!-- Add social icons here if needed -->
                    </div>
                </div>
                <div class="footer-col">
                    <h3><?php echo $lang['footer_prod']; ?></h3>
                    <ul>
                        <li><a href="#pricing"><?php echo $lang['footer_vps']; ?></a></li>
                        <li><a href="dashboard/domains"><?php echo $lang['footer_domains']; ?></a></li>
                        <li><span
                                style="color: var(--text-muted); cursor: not-allowed;"><?php echo $lang['footer_dedicated']; ?></span>
                        </li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3><?php echo $lang['footer_company']; ?></h3>
                    <ul>
                        <li><a href="#about"><?php echo $lang['nav_about']; ?></a></li>
                        <li><a href="#contact"><?php echo $lang['nav_contact']; ?></a></li>
                        <li><a href="terms"><?php echo $lang['footer_terms']; ?></a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3><?php echo $currentLang === 'es' ? 'Cuenta' : 'Account'; ?></h3>
                    <ul>
                        <li><a href="register"><?php echo $lang['btn_register']; ?></a></li>
                        <li><a href="login"><?php echo $lang['btn_login']; ?></a></li>
                        <li><a href="dashboard"><?php echo $currentLang === 'es' ? 'Panel de control' : 'Dashboard'; ?></a></li>
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