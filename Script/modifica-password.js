document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.form-auth');
    if (!form) return;

    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const errorDiv = document.getElementById('modpw-errors');
    if (!newPassword || !confirmPassword || !errorDiv) return;

    form.addEventListener('submit', function (e) {
        if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            errorDiv.textContent = 'Le nuove password non coincidono.';
            errorDiv.hidden = false;
            errorDiv.focus();
        }
    });

    confirmPassword.addEventListener('input', function () {
        if (newPassword.value === confirmPassword.value) {
            errorDiv.hidden = true;
            errorDiv.textContent = '';
        }
    });
});
