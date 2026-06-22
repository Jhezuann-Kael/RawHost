<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Default to English unless the user explicitly set a preference
$currentLang = $_GET['lang'] ?? $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (!in_array($currentLang, ['en', 'es'])) $currentLang = 'en';
$lang = require __DIR__ . '/languages/' . $currentLang . '.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['404_title']; ?></title>
    <meta name="robots" content="noindex, follow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/404.css">
</head>

<body>
    <div class="particles">
        <?php for ($i = 0; $i < 30; $i++): ?>
            <div class="particle"
                style="left:<?php echo rand(0,100); ?>%;animation-delay:<?php echo rand(0,15); ?>s;animation-duration:<?php echo rand(10,20); ?>s;">
            </div>
        <?php endfor; ?>
    </div>

    <div class="container">
        <div class="error-code">404</div>
        <i class="fas fa-ghost error-icon"></i>
        <h1><?php echo $lang['404_heading']; ?></h1>
        <p><?php echo $lang['404_message']; ?></p>

        <div class="buttons">
            <?php if ($isLoggedIn): ?>
                <a href="/dashboard" class="btn btn-primary">
                    <i class="fas fa-home"></i> <?php echo $lang['404_btn_dashboard']; ?>
                </a>
            <?php else: ?>
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> <?php echo $lang['404_btn_home']; ?>
                </a>
                <a href="/login" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> <?php echo $lang['404_btn_login']; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
