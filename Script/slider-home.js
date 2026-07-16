document.addEventListener("DOMContentLoaded", function () {
    // SLIDER HOMEPAGE
    const slides = document.querySelectorAll(".slide");
    let currentSlide = 0;

    if (slides.length > 0) {
        // esegue questa funzione ogni tot millisecondi
        setInterval(function () {
            // Rimuove la classe attiva dall'immagine corrente
            slides[currentSlide].classList.remove("active");
            
            // Passa alla prossima immagine, e se è l'ultima ricomincia da zero
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Aggiunge la classe attiva alla nuova immagine
            slides[currentSlide].classList.add("active");
        }, 4000); // 4 secondi per ogni immagine
    }
});