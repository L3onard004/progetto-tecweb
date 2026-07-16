document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.product-form');
    if (!form) return;

    const radios = form.querySelectorAll('input[name="taglia"]');
    const errore = document.getElementById('errore-taglia');
    const field = document.getElementById('field-taglia');

    const hasTaglia = function () {
        return Array.from(radios).some(function (r) { return r.checked; });
    };

    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (errore) errore.hidden = true;
            if (field) field.classList.remove('field-invalid');
        });
    });

    form.addEventListener('submit', function (e) {
        if (!hasTaglia()) {
            e.preventDefault();
            if (errore) errore.hidden = false;
            if (field) field.classList.add('field-invalid');
        }
    });
});
