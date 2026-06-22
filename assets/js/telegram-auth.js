function loginWithTelegram() {
    const btn = document.getElementById('telegram-btn');
    const bot_id = btn ? btn.dataset.botId : null;
    if (!bot_id) return;
    window.Telegram.Login.auth(
        { bot_id: bot_id, request_access: 'write' },
        (data) => {
            if (data) {
                const params = new URLSearchParams(data).toString();
                window.location.href = 'api/auth/telegram_callback.php?' + params;
            }
        }
    );
}
