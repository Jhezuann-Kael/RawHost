<?php
$minFile = __DIR__ . '/bundle.min.css';
$lastMod = filemtime($minFile);
$etag    = '"' . dechex($lastMod) . '"';

header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastMod) . ' GMT');
header('Vary: Accept-Encoding');

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH'])     && trim($_SERVER['HTTP_IF_NONE_MATCH'])    === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastMod)
) {
    http_response_code(304);
    exit;
}

readfile($minFile);
