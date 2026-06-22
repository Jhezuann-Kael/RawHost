function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    if (!form) return;

    const txtErrorConn    = form.dataset.msgErrorConn    || 'Connection error';
    const txtCaptchaMissing = form.dataset.msgCaptcha    || 'Please complete the security verification';

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const messageEl = document.getElementById('message');
        messageEl.style.display = 'none';

        const captchaResponse = hcaptcha.getResponse();
        if (!captchaResponse) {
            messageEl.textContent = txtCaptchaMissing;
            messageEl.className = 'message error';
            messageEl.style.display = 'block';
            return;
        }

        const data = Object.fromEntries(new FormData(this).entries());
        data['h-captcha-response'] = captchaResponse;

        fetch('api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(data => {
                messageEl.textContent = data.message;
                messageEl.style.display = 'block';
                if (data.success) {
                    messageEl.className = 'message success';
                    setTimeout(() => window.location.href = 'dashboard/index', 1500);
                } else {
                    messageEl.className = 'message error';
                    hcaptcha.reset();
                }
            })
            .catch(() => {
                messageEl.textContent = txtErrorConn;
                messageEl.className = 'message error';
                messageEl.style.display = 'block';
                hcaptcha.reset();
            });
    });
});
