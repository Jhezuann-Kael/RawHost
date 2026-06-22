<?php
// includes/lang_loader.php

function getBrowserLanguage()
{
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        return ($lang === 'es') ? 'es' : 'en'; // Return ES if explicitly ES, else EN
    }
    return 'en'; // Default to EN
}

// 1. Check GET parameter (user manual switch)
if (isset($_GET['lang'])) {
    $langCode = $_GET['lang'];
    if ($langCode === 'en' || $langCode === 'es') {
        $_SESSION['lang'] = $langCode;
        setcookie('lang', $langCode, time() + (86400 * 30), "/"); // 30 days
    }
}

// 2. Check Cookie/Session or Detect
if (isset($_SESSION['lang'])) {
    $currentLang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $currentLang = $_COOKIE['lang'];
    $_SESSION['lang'] = $currentLang;
} else {
    $currentLang = getBrowserLanguage();
    $_SESSION['lang'] = $currentLang;
}

// Safety check
if ($currentLang !== 'en' && $currentLang !== 'es') {
    $currentLang = 'es';
}

// Load Language File
$langFile = __DIR__ . '/../languages/' . $currentLang . '.php';
if (file_exists($langFile)) {
    $lang = require($langFile);
} else {
    // Fallback
    $lang = require(__DIR__ . '/../languages/es.php');
}
?>