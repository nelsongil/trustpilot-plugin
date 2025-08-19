/**
 * Trustpilot Reviews - Enhanced JavaScript
 * Version 1.8 - Enhanced functionality and performance
 */

(function($) {
    'use strict';
    
    // Main plugin object
    const CTRReviews = {
        
        // Configuration
        config: {
            autoPlay: true,
            autoPlaySpeed: 5000,
            touchEnabled: true,
            lazyLoading: true,
            smoothScrolling: true
        },
        
        // Initialize plugin
        init: function() {
            this.bindEvents();
            this.initCarousels();
            this.initLazyLoading();
            this.initSmoothScrolling();
            this.initTouchSupport();
            this.initAccessibility();
        },
        
        // Bind event handlers
        bindEvents: function() {
            $(document).on('click', '.ctr-prev', this.previousSlide);
            $(document).on('click', '.ctr-next', this.nextSlide);
            $(document).on('click', '.ctr-review-card, .ctr-review-item, .ctr-review-slide', this.handleReviewClick);
            $(document).on('mouseenter', '.ctr-carousel-container', this.pauseAutoPlay);
            $(document).on('mouseleave', '.ctr-carousel-container', this.resumeAutoPlay);
            
            // Touch events for mobile
            if (this.config.touchEnabled) {
                this.initTouchEvents();
            }
            
            // Keyboard navigation
            $(document).on('keydown', this.handleKeyboardNavigation);
        },
        
        // Initialize carousels
        initCarousels: function() {
            $('.ctr-carousel-container').each(function() {
                const $container = $(this);
                const $slides = $container.find('.ctr-review-slide');
                const $nav = $container.find('.ctr-carousel-nav');
                
                if ($slides.length > 1) {
                    // Add navigation if not present
                    if ($nav.length === 0) {
                        $container.append(CTRReviews.createCarouselNavigation($slides.length));
                    }
                    
                    // Initialize carousel functionality
                    CTRReviews.setupCarousel($container);
                }
            });
        },
        
        // Create carousel navigation
        createCarouselNavigation: function(slideCount) {
            let nav = '<div class="ctr-carousel-nav">';
            nav += '<button type="button" class="ctr-prev" aria-label="Reseña anterior">‹</button>';
            
            // Add slide indicators
            for (let i = 0; i < slideCount; i++) {
                nav += '<button type="button" class="ctr-indicator" data-slide="' + i + '" aria-label="Ir a reseña ' + (i + 1) + '">' + (i + 1) + '</button>';
            }
            
            nav += '<button type="button" class="ctr-next" aria-label="Siguiente reseña">›</button>';
            nav += '</div>';
            
            return nav;
        },
        
        // Setup individual carousel
        setupCarousel: function($container) {
            const $slides = $container.find('.ctr-review-slide');
            const $indicators = $container.find('.ctr-indicator');
            let currentSlide = 0;
            let autoPlayInterval;
            
            // Show first slide
            $slides.hide().first().show();
            $indicators.first().addClass('active');
            
            // Auto-play functionality
            if (this.config.autoPlay) {
                this.startAutoPlay($container);
            }
            
            // Store carousel data
            $container.data('carousel', {
                currentSlide: currentSlide,
                totalSlides: $slides.length,
                autoPlayInterval: autoPlayInterval
            });
        },
        
        // Start auto-play
        startAutoPlay: function($container) {
            const carousel = $container.data('carousel');
            if (carousel.autoPlayInterval) {
                clearInterval(carousel.autoPlayInterval);
            }
            
            carousel.autoPlayInterval = setInterval(() => {
                this.nextSlide.call($container.find('.ctr-next')[0]);
            }, this.config.autoPlaySpeed);
            
            $container.data('carousel', carousel);
        },
        
        // Pause auto-play
        pauseAutoPlay: function() {
            const $container = $(this);
            const carousel = $container.data('carousel');
            if (carousel && carousel.autoPlayInterval) {
                clearInterval(carousel.autoPlayInterval);
            }
        },
        
        // Resume auto-play
        resumeAutoPlay: function() {
            const $container = $(this);
            if (this.config.autoPlay) {
                this.startAutoPlay($container);
            }
        },
        
        // Go to previous slide
        previousSlide: function(e) {
            e.preventDefault();
            const $container = $(this).closest('.ctr-carousel-container');
            const carousel = $container.data('carousel');
            const $slides = $container.find('.ctr-review-slide');
            const $indicators = $container.find('.ctr-indicator');
            
            let newSlide = carousel.currentSlide - 1;
            if (newSlide < 0) {
                newSlide = $slides.length - 1;
            }
            
            this.goToSlide($container, newSlide);
        },
        
        // Go to next slide
        nextSlide: function(e) {
            e.preventDefault();
            const $container = $(this).closest('.ctr-carousel-container');
            const carousel = $container.data('carousel');
            const $slides = $container.find('.ctr-review-slide');
            const $indicators = $container.find('.ctr-indicator');
            
            let newSlide = carousel.currentSlide + 1;
            if (newSlide >= $slides.length) {
                newSlide = 0;
            }
            
            this.goToSlide($container, newSlide);
        },
        
        // Go to specific slide
        goToSlide: function($container, slideIndex) {
            const $slides = $container.find('.ctr-review-slide');
            const $indicators = $container.find('.ctr-indicator');
            const carousel = $container.data('carousel');
            
            // Hide current slide
            $slides.eq(carousel.currentSlide).fadeOut(300);
            $indicators.removeClass('active');
            
            // Show new slide
            $slides.eq(slideIndex).fadeIn(300);
            $indicators.eq(slideIndex).addClass('active');
            
            // Update carousel data
            carousel.currentSlide = slideIndex;
            $container.data('carousel', carousel);
            
            // Restart auto-play
            if (this.config.autoPlay) {
                this.startAutoPlay($container);
            }
        },
        
        // Initialize lazy loading
        initLazyLoading: function() {
            if (!this.config.lazyLoading || !('IntersectionObserver' in window)) {
                return;
            }
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const $element = $(entry.target);
                        this.loadReviewContent($element);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });
            
            $('.ctr-review-card, .ctr-review-item, .ctr-review-slide').each(function() {
                observer.observe(this);
            });
        },
        
        // Load review content
        loadReviewContent: function($element) {
            if ($element.hasClass('ctr-loaded')) {
                return;
            }
            
            // Add loading animation
            $element.addClass('ctr-loading');
            
            // Simulate content loading (in real implementation, this would fetch data)
            setTimeout(() => {
                $element.removeClass('ctr-loading').addClass('ctr-loaded');
                $element.trigger('ctr:contentLoaded');
            }, 300);
        },
        
        // Initialize smooth scrolling
        initSmoothScrolling: function() {
            if (!this.config.smoothScrolling) {
                return;
            }
            
            $('html').css('scroll-behavior', 'smooth');
        },
        
        // Initialize touch support
        initTouchSupport: function() {
            if (!this.config.touchEnabled) {
                return;
            }
            
            $('.ctr-carousel-container').each(function() {
                const $container = $(this);
                let startX = 0;
                let endX = 0;
                
                $container.on('touchstart', function(e) {
                    startX = e.originalEvent.touches[0].clientX;
                });
                
                $container.on('touchend', function(e) {
                    endX = e.originalEvent.changedTouches[0].clientX;
                    const diff = startX - endX;
                    
                    if (Math.abs(diff) > 50) {
                        if (diff > 0) {
                            // Swipe left - next slide
                            $container.find('.ctr-next').click();
                        } else {
                            // Swipe right - previous slide
                            $container.find('.ctr-prev').click();
                        }
                    }
                });
            });
        },
        
        // Initialize accessibility features
        initAccessibility: function() {
            // Add ARIA labels
            $('.ctr-carousel-container').attr('role', 'region');
            $('.ctr-carousel-container').attr('aria-label', 'Reseñas de Trustpilot');
            
            // Add focus management
            $('.ctr-prev, .ctr-next, .ctr-indicator').on('focus', function() {
                $(this).addClass('ctr-focused');
            }).on('blur', function() {
                $(this).removeClass('ctr-focused');
            });
            
            // Add keyboard navigation
            $('.ctr-carousel-container').attr('tabindex', '0');
        },
        
        // Handle review clicks
        handleReviewClick: function(e) {
            const $review = $(this);
            const isClickable = $review.hasClass('ctr-clickable');
            
            if (isClickable) {
                const reviewUrl = $review.data('review-url');
                if (reviewUrl) {
                    window.open(reviewUrl, '_blank', 'noopener,noreferrer');
                }
            }
        },
        
        // Handle keyboard navigation
        handleKeyboardNavigation: function(e) {
            const $focused = $(':focus');
            const $carousel = $focused.closest('.ctr-carousel-container');
            
            if ($carousel.length === 0) {
                return;
            }
            
            switch (e.keyCode) {
                case 37: // Left arrow
                    $carousel.find('.ctr-prev').click();
                    e.preventDefault();
                    break;
                case 39: // Right arrow
                    $carousel.find('.ctr-next').click();
                    e.preventDefault();
                    break;
                case 32: // Space bar
                    $carousel.find('.ctr-prev').click();
                    e.preventDefault();
                    break;
            }
        },
        
        // Initialize touch events
        initTouchEvents: function() {
            // Add touch-specific CSS classes
            $('html').addClass('ctr-touch-enabled');
            
            // Handle touch gestures
            let touchStartY = 0;
            let touchEndY = 0;
            
            $(document).on('touchstart', function(e) {
                touchStartY = e.originalEvent.touches[0].clientY;
            });
            
            $(document).on('touchend', function(e) {
                touchEndY = e.originalEvent.changedTouches[0].clientY;
                const diff = touchStartY - touchEndY;
                
                if (Math.abs(diff) > 100) {
                    // Vertical swipe detected
                    if (diff > 0) {
                        // Swipe up
                        $(document).trigger('ctr:swipeUp');
                    } else {
                        // Swipe down
                        $(document).trigger('ctr:swipeDown');
                    }
                }
            });
        },
        
        // Utility functions
        utils: {
            // Debounce function
            debounce: function(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            },
            
            // Throttle function
            throttle: function(func, limit) {
                let inThrottle;
                return function() {
                    const args = arguments;
                    const context = this;
                    if (!inThrottle) {
                        func.apply(context, args);
                        inThrottle = true;
                        setTimeout(() => inThrottle = false, limit);
                    }
                };
            },
            
            // Check if element is in viewport
            isInViewport: function(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        CTRReviews.init();
    });
    
    // Expose to global scope for external use
    window.CTRReviews = CTRReviews;
    
})(jQuery);