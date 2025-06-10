document.addEventListener('DOMContentLoaded', function() {
    const carouselContainers = document.querySelectorAll('.ctr-reviews-container.carousel');

    carouselContainers.forEach(container => {
        const carouselInner = container.querySelector('.ctr-carousel-inner');
        if (!carouselInner) return; // Debería existir si el layout es 'carousel'

        const reviews = carouselInner.querySelectorAll('.ctr-review');
        if (reviews.length === 0) return;

        let currentIndex = 0;

        // Función para mostrar la reseña actual
        function showReview(index) {
            reviews.forEach((review, i) => {
                review.style.display = (i === index) ? 'block' : 'none';
            });
        }

        // Crear botones de navegación
        const prevButton = document.createElement('button');
        prevButton.classList.add('ctr-carousel-button', 'ctr-prev');
        prevButton.innerHTML = '&#10094;'; // Flecha izquierda
        container.appendChild(prevButton);

        const nextButton = document.createElement('button');
        nextButton.classList.add('ctr-carousel-button', 'ctr-next');
        nextButton.innerHTML = '&#10095;'; // Flecha derecha
        container.appendChild(nextButton);

        // Event Listeners para los botones
        prevButton.addEventListener('click', () => {
            currentIndex = (currentIndex > 0) ? currentIndex - 1 : reviews.length - 1;
            showReview(currentIndex);
        });

        nextButton.addEventListener('click', () => {
            currentIndex = (currentIndex < reviews.length - 1) ? currentIndex + 1 : 0;
            showReview(currentIndex);
        });

        // Mostrar la primera reseña al inicio
        showReview(currentIndex);

        // Lógica de autoplay
        let autoplayInterval = parseInt(getComputedStyle(container).getPropertyValue('--ctr-autoplay-interval'));
        let autoplayTimer;

        function startAutoplay() {
            if (autoplayInterval > 0) {
                autoplayTimer = setInterval(() => {
                    currentIndex = (currentIndex < reviews.length - 1) ? currentIndex + 1 : 0;
                    showReview(currentIndex);
                }, autoplayInterval);
            }
        }

        function resetAutoplay() {
            clearInterval(autoplayTimer);
            startAutoplay();
        }

        // Reiniciar autoplay en interacción manual
        prevButton.addEventListener('click', resetAutoplay);
        nextButton.addEventListener('click', resetAutoplay);

        // Iniciar autoplay al cargar
        startAutoplay();
    });
}); 