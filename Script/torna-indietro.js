document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btn-back');
    if (btn) {
        btn.addEventListener('click', function () {
            history.back();
        });
    }
});
