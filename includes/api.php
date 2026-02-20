<?php
if (!defined('ABSPATH')) exit;

/**
 * Función para obtener reseñas de Trustpilot desde una página pública
 * Con sistema de caché y mejor manejo de errores
 */
function ctr_get_trustpilot_reviews() {
    // Check if caching is enabled
    $enable_cache = get_option('ctr_enable_cache', 1);
    $cached_reviews = $enable_cache ? get_transient('ctr_reviews_cache') : false;
    
    if ($enable_cache && $cached_reviews !== false) {
        return $cached_reviews;
    }
    
    $url = esc_url_raw(get_option('ctr_api_url', ''));

    // Validar si la URL está configurada
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = __('La URL de Trustpilot no es válida.', 'custom-trustpilot-reviews');
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    // Rate limiting - check if we've made too many requests recently
    $last_request_time = get_transient('ctr_last_request_time');
    $current_time = time();
    
    if ($last_request_time && ($current_time - $last_request_time) < 60) {
        // If we made a request less than 1 minute ago, return cached data or error
        if ($cached_reviews !== false) {
            return $cached_reviews;
        }
        return ['error' => __('Demasiadas solicitudes. Intenta de nuevo en unos minutos.', 'custom-trustpilot-reviews')];
    }
    
    // Set last request time
    set_transient('ctr_last_request_time', $current_time, 60);

    // Make HTTP request with better error handling
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ]
    ]);

    // Check for request errors
    if (is_wp_error($response)) {
        $error = sprintf(__('Error de conexión: %s', 'custom-trustpilot-reviews'), $response->get_error_message());
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    // Check HTTP response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $error = sprintf(__('Error HTTP %d: No se pudo acceder a la página de Trustpilot.', 'custom-trustpilot-reviews'), $response_code);
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    // Get response body
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
        $error = __('La respuesta de Trustpilot está vacía.', 'custom-trustpilot-reviews');
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    // Parse HTML with better error handling
    $reviews = ctr_parse_trustpilot_html($html, $url);
    
    // Cache the results if caching is enabled
    if ($enable_cache && !isset($reviews['error'])) {
        $cache_duration = get_option('ctr_cache_duration', 3600);
        set_transient('ctr_reviews_cache', $reviews, $cache_duration);
    }
    
    return $reviews;
}

/**
 * Parse Trustpilot HTML to extract reviews
 */
function ctr_parse_trustpilot_html($html, $base_url) {
    $reviews = [];
    
    // Suppress HTML parsing errors
    libxml_use_internal_errors(true);
    
    // Create DOM document
    $dom = new DOMDocument();
    $dom->encoding = 'UTF-8';
    
    // Load HTML with error suppression
    if (!@$dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
        libxml_clear_errors();
        return ['error' => __('Error al procesar el HTML de Trustpilot.', 'custom-trustpilot-reviews')];
    }
    
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // Try multiple selectors to find review cards
    $review_selectors = [
        '//article[contains(@class, "review")]',
        '//div[contains(@class, "review")]',
        '//div[contains(@class, "review-card")]',
        '//article',
        '//div[contains(@class, "review-content")]',
        '//div[contains(@class, "review-item")]'
    ];
    
    $review_cards = null;
    foreach ($review_selectors as $selector) {
        $review_cards = $xpath->query($selector);
        if ($review_cards && $review_cards->length > 0) {
            break;
        }
    }
    
    if (!$review_cards || $review_cards->length === 0) {
        return ['error' => __('No se encontraron reseñas en la página de Trustpilot.', 'custom-trustpilot-reviews')];
    }
    
    foreach ($review_cards as $card) {
        $review = ctr_extract_review_data($xpath, $card, $base_url);
        if ($review) {
            $reviews[] = $review;
        }
    }
    
    // If no reviews were extracted, try alternative method
    if (empty($reviews)) {
        $reviews = ctr_extract_reviews_alternative($xpath, $dom, $base_url);
    }
    
    if (empty($reviews)) {
        return ['error' => __('No se pudieron extraer las reseñas del HTML de Trustpilot.', 'custom-trustpilot-reviews')];
    }
    
    return $reviews;
}

/**
 * Extract review data from a review card element
 */
function ctr_extract_review_data($xpath, $card, $base_url) {
    // Try multiple selectors for title
    $title_selectors = [
        './/h2',
        './/h3',
        './/h4',
        './/div[contains(@class, "title")]',
        './/span[contains(@class, "title")]',
        './/a[contains(@class, "title")]'
    ];
    
    $title = '';
    foreach ($title_selectors as $selector) {
        $title_node = $xpath->query($selector, $card);
        if ($title_node && $title_node->length > 0) {
            $title = trim($title_node->item(0)->textContent);
            if (!empty($title)) break;
        }
    }
    
    // Try multiple selectors for content
    $content_selectors = [
        './/div[contains(@class, "reviewContent")]',
        './/div[contains(@class, "content")]',
        './/p[contains(@class, "review-text")]',
        './/div[contains(@class, "review-text")]',
        './/p[contains(@class, "content")]',
        './/p'
    ];
    
    $content = '';
    foreach ($content_selectors as $selector) {
        $content_node = $xpath->query($selector, $card);
        if ($content_node && $content_node->length > 0) {
            $content = trim($content_node->item(0)->textContent);
            if (!empty($content)) break;
        }
    }
    
    // Try to extract author name — Trustpilot frequently updates its HTML classes
    $author_selectors = [
        // Trustpilot specific – modern class names
        './/span[contains(@class, "typography_body-m__byoRM")]',
        './/span[contains(@class, "typography_heading-xxs")]',
        './/div[contains(@class, "consumer-info")]//span',
        './/div[@data-consumer-name]',
        './/a[contains(@href, "/users/")]',
        './/span[contains(@class, "review-author")]',
        './/a[contains(@class, "author")]',
        // Fallback generic selectors
        './/span[contains(@class, "author")]',
        './/div[contains(@class, "author")]',
        './/span[contains(@class, "name")]',
        './/div[contains(@class, "consumer")]//span',
        './/aside//span',
    ];
    
    $author = '';
    foreach ($author_selectors as $selector) {
        $author_node = $xpath->query($selector, $card);
        if ($author_node && $author_node->length > 0) {
            $author_text = trim($author_node->item(0)->textContent);
            if (!empty($author_text) && strlen($author_text) < 80) {
                $author = $author_text;
                break;
            }
        }
    }
    
    // Also try to get author from data attribute on the card itself
    if (empty($author)) {
        $author = $card->getAttribute('data-consumer-name');
    }
    
    if (empty($author)) {
        $author = __('Cliente Anónimo', 'custom-trustpilot-reviews');
    }
    
    // Extract star rating
    $rating = ctr_extract_star_rating($xpath, $card);
    
    // Extract review URL
    $review_url = ctr_extract_review_url($xpath, $card, $base_url);
    
    // Extract review date
    $review_date = ctr_extract_review_date($xpath, $card);
    
    // Only return review if we have at least title or content
    if (!empty($title) || !empty($content)) {
        return [
            'title' => $title ?: __('Reseña sin título', 'custom-trustpilot-reviews'),
            'content' => $content ?: __('Reseña sin contenido', 'custom-trustpilot-reviews'),
            'consumer' => ['displayName' => $author],
            'rating' => $rating,
            'review_url' => $review_url,
            'date' => $review_date
        ];
    }
    
    return null;
}

/**
 * Extract star rating from review card
 */
function ctr_extract_star_rating($xpath, $card) {
    // Try multiple selectors for star ratings
    $rating_selectors = [
        './/div[contains(@class, "star-rating")]',
        './/div[contains(@class, "rating")]',
        './/span[contains(@class, "stars")]',
        './/div[contains(@class, "stars")]',
        './/span[contains(@class, "rating")]',
        './/div[contains(@class, "review-rating")]'
    ];
    
    foreach ($rating_selectors as $selector) {
        $rating_node = $xpath->query($selector, $card);
        if ($rating_node && $rating_node->length > 0) {
            $rating_html = $rating_node->item(0)->getAttribute('aria-label') ?: $rating_node->item(0)->textContent;
            
            // Try to extract rating from aria-label or text
            if (preg_match('/(\d+)\s*(?:out of|de|stars?|estrellas?)/i', $rating_html, $matches)) {
                return intval($matches[1]);
            }
            
            // Try to count filled stars
            $filled_stars = $xpath->query('.//span[contains(@class, "filled")]', $rating_node->item(0));
            if ($filled_stars && $filled_stars->length > 0) {
                return $filled_stars->length;
            }
            
            // Try to count stars with specific classes
            $star_classes = ['star-filled', 'star-full', 'star-active', 'star-on'];
            foreach ($star_classes as $class) {
                $stars = $xpath->query('.//span[contains(@class, "' . $class . '")]', $rating_node->item(0));
                if ($stars && $stars->length > 0) {
                    return $stars->length;
                }
            }
        }
    }
    
    // Default rating if none found
    return 5;
}

/**
 * Extract review URL from review card
 */
function ctr_extract_review_url($xpath, $card, $base_url) {
    // Try to find review links
    $link_selectors = [
        './/a[contains(@class, "review")]',
        './/a[contains(@href, "review")]',
        './/a[contains(@class, "title")]',
        './/a'
    ];
    
    foreach ($link_selectors as $selector) {
        $link_node = $xpath->query($selector, $card);
        if ($link_node && $link_node->length > 0) {
            $href = $link_node->item(0)->getAttribute('href');
            if (!empty($href)) {
                // Convert relative URLs to absolute
                if (strpos($href, 'http') !== 0) {
                    $href = rtrim($base_url, '/') . '/' . ltrim($href, '/');
                }
                return $href;
            }
        }
    }
    
    return '';
}

/**
 * Extract review date from review card
 */
function ctr_extract_review_date($xpath, $card) {
    // Try multiple selectors for dates
    $date_selectors = [
        './/time',
        './/span[contains(@class, "date")]',
        './/div[contains(@class, "date")]',
        './/span[contains(@class, "review-date")]',
        './/div[contains(@class, "review-date")]'
    ];
    
    foreach ($date_selectors as $selector) {
        $date_node = $xpath->query($selector, $card);
        if ($date_node && $date_node->length > 0) {
            $date_text = trim($date_node->item(0)->textContent);
            if (!empty($date_text)) {
                return $date_text;
            }
        }
    }
    
    return '';
}

/**
 * Alternative method to extract reviews if the main method fails
 */
function ctr_extract_reviews_alternative($xpath, $dom, $base_url) {
    $reviews = [];
    
    // Look for any text that might be review content
    $text_nodes = $xpath->query('//p[contains(text(), " ") and string-length(text()) > 50]');
    
    if ($text_nodes && $text_nodes->length > 0) {
        $count = 0;
        foreach ($text_nodes as $node) {
            if ($count >= 10) break; // Limit to 10 reviews
            
            $text = trim($node->textContent);
            if (strlen($text) > 50 && strlen($text) < 1000) {
                $reviews[] = [
                    'title' => __('Reseña de cliente', 'custom-trustpilot-reviews'),
                    'content' => $text,
                    'consumer' => ['displayName' => __('Cliente Anónimo', 'custom-trustpilot-reviews')],
                    'rating' => 5,
                    'review_url' => '',
                    'date' => ''
                ];
                $count++;
            }
        }
    }
    
    return $reviews;
}

/**
 * Clear the reviews cache
 */
function ctr_clear_reviews_cache() {
    delete_transient('ctr_reviews_cache');
    delete_transient('ctr_last_request_time');
}

/**
 * Get cached reviews count
 */
function ctr_get_cached_reviews_count() {
    $cached = get_transient('ctr_reviews_cache');
    if ($cached && !isset($cached['error'])) {
        return count($cached);
    }
    return 0;
}