document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cart-qty-form').forEach(form => {
        const input = form.querySelector('.qty-input');
        if (!input) return;

        form.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                const delta = parseInt(btn.dataset.delta, 10);
                let value = parseInt(input.value, 10) || 1;

                value += delta;
                if (value < 1) value = 1;

                input.value = value;
                form.submit();
            });
        });
    });
});

