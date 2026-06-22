<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Verify hCaptcha
    require_once __DIR__ . '/helpers/hcaptcha.php';
    $captchaToken = $input['h-captcha-response'] ?? '';
    if (empty($captchaToken)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa la verificación de seguridad.']);
        exit;
    }
    $captchaResult = verify_hcaptcha($captchaToken, $_SERVER['REMOTE_ADDR'] ?? null);
    if (!$captchaResult['success']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Verificación de seguridad fallida. Inténtalo de nuevo.']);
        exit;
    }

    // Validate input
    if (!isset($input['name']) || empty(trim($input['name']))) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nombre es requerido']);
        exit;
    }

    if (!isset($input['subject']) || empty(trim($input['subject']))) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Asunto es requerido']);
        exit;
    }

    if (!isset($input['contact']) || empty(trim($input['contact']))) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Contacto es requerido']);
        exit;
    }

    $name = htmlspecialchars(trim($input['name']));
    $subject = htmlspecialchars(trim($input['subject']));
    $contact = htmlspecialchars(trim($input['contact']));
    $message = isset($input['message']) ? htmlspecialchars(trim($input['message'])) : '';

    // Build Telegram message
    $telegramMessage = "🔔 <b>Nueva Solicitud de Contacto</b>\n\n";
    $telegramMessage .= "👤 <b>Nombre:</b> " . $name . "\n";
    $telegramMessage .= "📋 <b>Asunto:</b> " . $subject . "\n";
    $telegramMessage .= "📞 <b>Contacto:</b> " . $contact . "\n";

    if (!empty($message)) {
        $telegramMessage .= "\n💬 <b>Mensaje:</b>\n" . $message;
    }

    // Send to Telegram
    $telegramApiUrl = "https://api.telegram.org/bot" . TOKEN_TELEGRAM . "/sendMessage";

    $telegramData = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $telegramMessage,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($telegramApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telegramData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo json_encode([
            'status' => 'success',
            'message' => '¡Gracias! Tu mensaje ha sido enviado correctamente. Te contactaremos pronto.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al enviar el mensaje. Por favor intenta nuevamente.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
