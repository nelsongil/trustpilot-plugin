/**
 * Custom Trustpilot Reviews — carrusel (vanilla JS, sin jQuery).
 * Inicializa cualquier .ctr-carousel-container[data-ctr-carousel] en la página.
 */
(function () {
    'use strict';

    function initCarousel(container) {
        var slidesWrap = container.querySelector('.ctr-carousel-slides');
        if (!slidesWrap) return;

        var slides = slidesWrap.querySelectorAll('.ctr-review-slide');
        if (slides.length <= 1) return;

        var current = 0;
        var prevBtn = container.querySelector('.ctr-prev');
        var nextBtn = container.querySelector('.ctr-next');

        function goTo(index) {
            current = (index + slides.length) % slides.length;
            slidesWrap.style.transform = 'translateX(-' + (current * 100) + '%)';
            slides.forEach(function (s, i) {
                s.setAttribute('aria-hidden', i === current ? 'false' : 'true');
            });
        }

        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });
        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });

        // Navegación con teclado cuando el contenedor tiene foco
        container.tabIndex = 0;
        container.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { goTo(current + 1); e.preventDefault(); }
            if (e.key === 'ArrowLeft')  { goTo(current - 1); e.preventDefault(); }
        });

        goTo(0);
    }

    function init() {
        var containers = document.querySelectorAll('.ctr-carousel-container[data-ctr-carousel]');
        containers.forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
