<?php
$adminCss = __DIR__ . '/../css/admin.css';
$v = file_exists($adminCss) ? filemtime($adminCss) : time();
echo '<link rel="stylesheet" href="/dashboard/admin/css/admin.css?v=' . $v . '">';

if (!empty($adminPageCss)) {
    $pageCssFile = __DIR__ . '/../css/' . $adminPageCss;
    $pv = file_exists($pageCssFile) ? filemtime($pageCssFile) : time();
    echo '<link rel="stylesheet" href="/dashboard/admin/css/' . htmlspecialchars($adminPageCss) . '?v=' . $pv . '">';
}
