document.addEventListener("DOMContentLoaded", function() {
    const skipLinks = document.querySelectorAll('.navicationHelp');
    skipLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.focus();
            }
        });
    });
});