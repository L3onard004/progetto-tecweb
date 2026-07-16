document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('form');
    const errorBox = document.getElementById('form-errors');
    if (!form || !errorBox) return;

    // Messaggi di errore restituiti dal server
    const serverMsgs = {
        campi_vuoti: 'Compila tutti i campi.',
        nome_corto: 'Il nome deve contenere almeno 2 caratteri.',
        cognome_corto: 'Il cognome deve contenere almeno 2 caratteri.',
        username_invalido: 'Username non valido: da 3 a 20 caratteri, solo lettere, numeri, punto (.), trattino (-) o underscore (_).',
        email_invalida: 'Inserisci un indirizzo email valido.',
        password_corta: 'La password deve contenere almeno 8 caratteri.',
        username_duplicato: 'Questo username è già in uso. Scegline un altro.',
        email_duplicata: 'Questa email è già registrata. Prova ad accedere.',
        duplicato: 'Username o email già in uso.',
        generico: 'Si è verificato un errore. Riprova più tardi.'
    };
    const errore = new URLSearchParams(window.location.search).get('errore');
    if (errore) {
        const p = document.createElement('p');
        p.textContent = serverMsgs[errore] || 'Errore sconosciuto.';
        errorBox.appendChild(p);
        errorBox.hidden = false;
        errorBox.focus();
    }

    const fields = {
        nome: form.nome,
        cognome: form.cognome,
        username: form.username,
        email: form.email,
        password: form.password
    };
    if (Object.values(fields).some(f => !f)) return;

    function clearErrors() {
        errorBox.innerHTML = '';
        errorBox.hidden = true;

        Object.values(fields).forEach(field => {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
        });
    }

    function showError(field, message, errors) {
        field.classList.add('error');
        field.setAttribute('aria-invalid', 'true');
        errors.push(message);
    }

    form.addEventListener('submit', function (event) {

        clearErrors();
        const errors = [];

        const nome = fields.nome.value.trim();
        const cognome = fields.cognome.value.trim();
        const username = fields.username.value.trim();
        const email = fields.email.value.trim();
        const password = fields.password.value;

        // Validazioni input

        if (nome.length < 2) {
            showError(fields.nome, 'Il nome deve contenere almeno 2 caratteri.', errors);
        }

        if (cognome.length < 2) {
            showError(fields.cognome, 'Il cognome deve contenere almeno 2 caratteri.', errors);
        }

        if (!/^[a-zA-Z0-9._-]{3,20}$/.test(username)) {
            showError(
                fields.username,
                'Username non valido: 3–20 caratteri, solo lettere, numeri, punto, trattino o underscore.',
                errors
            );
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError(fields.email, 'Inserisci un indirizzo email valido.', errors);
        }

        if (password.length < 8) {
            showError(
                fields.password,
                'La password deve contenere almeno 8 caratteri.',
                errors
            );
        }

        if (errors.length > 0) {
            event.preventDefault();
            const p = document.createElement('p');
            p.textContent = 'Controlla i campi evidenziati e rispetta i vincoli indicati.';
            errorBox.appendChild(p);
            errorBox.hidden = false;
            errorBox.focus();
        }
    });
});
