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
    const completeForm = document.getElementById('completeRegisterForm');
    const registerForm = document.getElementById('registerForm');
    const activeForm   = completeForm || registerForm;
    if (!activeForm) return;

    const txtPassMismatch   = activeForm.dataset.msgPassMismatch  || 'Passwords do not match';
    const txtPassShort      = activeForm.dataset.msgPassShort     || 'Password too short';
    const txtErrorConn      = activeForm.dataset.msgErrorConn     || 'Connection error';
    const txtCaptchaMissing = activeForm.dataset.msgCaptcha       || 'Please complete the security verification';

    activeForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const messageEl = document.getElementById('message');
        messageEl.style.display = 'none';

        const data = Object.fromEntries(new FormData(this).entries());

        if (registerForm && data.password.length < 6) {
            messageEl.textContent = txtPassShort;
            messageEl.className = 'message error';
            messageEl.style.display = 'block';
            return;
        }

        if (data.password !== data.confirm_password) {
            messageEl.textContent = txtPassMismatch;
            messageEl.className = 'message error';
            messageEl.style.display = 'block';
            return;
        }

        const captchaResponse = hcaptcha.getResponse();
        if (!captchaResponse) {
            messageEl.textContent = txtCaptchaMissing;
            messageEl.className = 'message error';
            messageEl.style.display = 'block';
            return;
        }
        data['h-captcha-response'] = captchaResponse;

        const endpoint = completeForm ? 'api/auth/complete_registration' : 'api/auth/register';
        const redirect  = completeForm ? 'login' : 'login';

        fetch(endpoint, {
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
                    setTimeout(() => window.location.href = redirect, 2000);
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
