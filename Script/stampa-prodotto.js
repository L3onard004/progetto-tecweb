//Apertura di tutti i details del singolo prodotto

window.addEventListener('beforeprint', function () {
    document.querySelectorAll('.product-accordion details').forEach(function (d) {
        d._wasOpen = d.open;
        d.open = true;
    });
});

window.addEventListener('afterprint', function () {
    document.querySelectorAll('.product-accordion details').forEach(function (d) {
        d.open = d._wasOpen;
        delete d._wasOpen;
    });
});
