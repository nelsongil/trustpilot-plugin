<?php
if (!defined('ABSPATH')) exit;

/**
<<<<<<< HEAD
 * Función para validar la URL de Trustpilot
 */
function ctr_validate_trustpilot_url($url) {
    if (empty($url)) {
        return false;
    }
    
    $parsed_url = parse_url($url);
    if (!$parsed_url || !isset($parsed_url['host'])) {
        return false;
    }
    
    return strpos($parsed_url['host'], 'trustpilot.com') !== false;
}

/**
 * Función para obtener reseñas de Trustpilot
 */
function ctr_get_trustpilot_reviews($url_param, $count_param, $cache_duration_param) {
    $url = $url_param;
    $count = intval($count_param);
    $cache_duration = intval($cache_duration_param);

    // Validar URL
    if (!ctr_validate_trustpilot_url($url)) {
        return ['error' => __('La URL de Trustpilot no es válida.', 'custom-trustpilot-reviews')];
    }

    // Intentar obtener del caché
    $cache_key = 'ctr_reviews_cache_' . md5($url . $count . $cache_duration); // Cache key based on URL, count, and duration
    $cached_reviews = get_transient($cache_key);
    
    if ($cached_reviews !== false) {
        return $cached_reviews;
    }

    // Hacer la solicitud HTTP con timeout y user agent
    $args = [
        'timeout' => 15,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
        ]
    ];

    $response = wp_remote_get($url, $args);

    // Verificar errores en la solicitud
    if (is_wp_error($response)) {
        error_log('Error Trustpilot Reviews: ' . $response->get_error_message());
        return ['error' => __('Error al conectar con Trustpilot. Por favor, intenta más tarde.', 'custom-trustpilot-reviews')];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        error_log('Error Trustpilot Reviews: Código de respuesta ' . $response_code);
        return ['error' => __('Error al obtener las reseñas. Por favor, verifica la URL.', 'custom-trustpilot-reviews')];
=======
 * Función para obtener reseñas de Trustpilot desde una página pública
 */
function ctr_get_trustpilot_reviews() {
    $url = esc_url_raw(get_option('ctr_api_url', ''));

    // Validar si la URL está configurada
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['error' => 'La URL de Trustpilot no es válida.'];
    }

    // Hacer la solicitud HTTP
    $response = wp_remote_get($url, ['timeout' => 10]);

    // Verificar errores en la solicitud
    if (is_wp_error($response)) {
        return ['error' => 'No se pudo conectar a Trustpilot.'];
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
    }

    // Obtener el HTML de la respuesta
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
<<<<<<< HEAD
        return ['error' => __('No se encontraron reseñas.', 'custom-trustpilot-reviews')];
=======
        return ['error' => 'La respuesta está vacía.'];
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
    }

    // Parsear el HTML con DOMDocument
    $reviews = [];
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
<<<<<<< HEAD
    
    try {
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    } catch (Exception $e) {
        error_log('Error parsing Trustpilot HTML: ' . $e->getMessage());
        return ['error' => __('Error al procesar las reseñas.', 'custom-trustpilot-reviews')];
    }
    
=======
    @$dom->loadHTML($html); // La @ suprime errores de HTML malformado
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

<<<<<<< HEAD
    // Buscar las tarjetas de reseñas
    $review_cards = $xpath->query('//article[contains(@class, "review")]');

    if ($review_cards->length === 0) {
        return ['error' => __('No se encontraron reseñas en la página.', 'custom-trustpilot-reviews')];
    }

    foreach ($review_cards as $card) {
        // Extraer título
        $title_node = $xpath->query('.//h2', $card);
        $title = ($title_node->length > 0) ? sanitize_text_field(trim($title_node->item(0)->textContent)) : '';

        // Extraer contenido
        $content_node = $xpath->query('.//div[contains(@class, "reviewContent")]', $card);
        $content = ($content_node->length > 0) ? sanitize_text_field(trim($content_node->item(0)->textContent)) : '';

        // Extraer calificación
        $rating_node = $xpath->query('.//div[contains(@class, "styles_reviewHeader")]', $card);
        $raw_rating = ($rating_node->length > 0) ? $rating_node->item(0)->getAttribute('data-service-review-rating') : '';
        $rating = intval($raw_rating);

        // Extraer fecha
        $date_node = $xpath->query('.//time', $card);
        $date = ($date_node->length > 0) ? sanitize_text_field(trim($date_node->item(0)->getAttribute('datetime'))) : '';

        // Extraer nombre del cliente
        $name_node = $xpath->query('.//div[contains(@class, "consumer-name")]', $card);
        $name = ($name_node->length > 0) ? sanitize_text_field(trim($name_node->item(0)->textContent)) : __('Cliente Anónimo', 'custom-trustpilot-reviews');
=======
    // Buscar las tarjetas de reseñas de forma general
    $review_cards = $xpath->query('//article');

    foreach ($review_cards as $card) {
        // Extraer título de la reseña
        $title_node = $xpath->query('.//h2', $card);
        $title = ($title_node->length > 0) ? sanitize_text_field(trim($title_node->item(0)->textContent)) : 'Título no disponible';

        // Extraer contenido de la reseña
        $content_node = $xpath->query('.//div[contains(@class, "reviewContent")]', $card);
        $content = ($content_node->length > 0) ? sanitize_text_field(trim($content_node->item(0)->textContent)) : 'Reseña no disponible';
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258

        // Validar y añadir reseña
        if (!empty($title) || !empty($content)) {
            $reviews[] = [
<<<<<<< HEAD
                'title' => $title,
                'content' => $content,
                'rating' => $rating,
                'date' => $date,
                'consumer' => [
                    'displayName' => $name
                ]
=======
                'title'   => $title,
                'content' => $content,
                'consumer' => ['displayName' => 'Cliente Anónimo']
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
            ];
        }
    }

<<<<<<< HEAD
    // Limitar el número de reseñas si se especifica
    if ($count !== null && is_numeric($count)) {
        $reviews = array_slice($reviews, 0, intval($count));
    }

    // Guardar en caché
    set_transient($cache_key, $reviews, $cache_duration);

    return $reviews;
}
=======
    return $reviews;
}
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
