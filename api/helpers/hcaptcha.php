<?php
/**
 * hCaptcha Server-Side Verification Helper
 * Docs: https://docs.hcaptcha.com/#verify-the-user-response-server-side
 */

require_once __DIR__ . '/../config.php';

/**
 * Verify hCaptcha token against the siteverify API
 * @param string $token The h-captcha-response token from the client
 * @param string|null $remoteip Optional client IP for improved accuracy
 * @return array ['success' => bool, 'error_codes' => array]
 */
function verify_hcaptcha($token, $remoteip = null)
{
    if (empty($token)) {
        return ['success' => false, 'error_codes' => ['missing-input-response']];
    }

    $payload = [
        'secret' => HCAPTCHA_SECRET_KEY,
        'response' => $token,
        'sitekey' => HCAPTCHA_SITE_KEY
    ];

    if ($remoteip) {
        $payload['remoteip'] = $remoteip;
    }

    $ch = curl_init('https://api.hcaptcha.com/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        error_log("hCaptcha verification request failed: HTTP $httpCode");
        return ['success' => false, 'error_codes' => ['connection-error']];
    }

    $data = json_decode($response, true);

    return [
        'success' => isset($data['success']) ? $data['success'] : false,
        'error_codes' => isset($data['error-codes']) ? $data['error-codes'] : []
    ];
}
