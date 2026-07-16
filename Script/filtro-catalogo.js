document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const target = params.get('target');

    const filtriGrid = document.querySelector('.filtri-grid');
    const tornaCatalogo = document.querySelector('.torna-catalogo');

    if (!target) {
        if (filtriGrid) filtriGrid.style.display = 'grid';
        if (tornaCatalogo) tornaCatalogo.hidden = true;
        return;
    }

    const categorie = ['donna', 'uomo', 'bambino', 'bambina'];

    categorie.forEach(function (categoria) {
        if (categoria !== target) {
            document.querySelectorAll('.' + categoria).forEach(function (el) {
                el.style.display = 'none';
            });
        }
    });

    if (filtriGrid) filtriGrid.style.display = 'none';
    if (tornaCatalogo) tornaCatalogo.hidden = false;
});
