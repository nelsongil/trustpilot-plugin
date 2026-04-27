<?php
if (!defined('ABSPATH')) exit;

/**
 * Función para obtener reseñas de Trustpilot.
 *
 * Estrategia v2.1:
 *   1. Lee `__NEXT_DATA__` (JSON embebido por Next.js) — método principal.
 *   2. Si la página no incluye `__NEXT_DATA__`, intenta una pasada DOM
 *      como red de seguridad muy laxa (cards genéricas con autor + texto).
 *   3. Si nada funciona, devuelve un error explícito. NUNCA inventa
 *      reseñas a partir de cualquier <p> del documento.
 *
 * Trustpilot sirve hoy un site Next.js detrás de Cloudflare. Las clases
 * CSS llevan sufijos hash que cambian en cada deploy, por eso parsear DOM
 * por clase es frágil. El JSON de `__NEXT_DATA__` es la fuente estable.
 */
function ctr_get_trustpilot_reviews() {
    $enable_cache   = get_option('ctr_enable_cache', 1);
    $cached_reviews = $enable_cache ? get_transient('ctr_reviews_cache') : false;

    if ($enable_cache && $cached_reviews !== false) {
        return $cached_reviews;
    }

    $url = esc_url_raw(get_option('ctr_api_url', ''));

    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = __('La URL de Trustpilot no es válida.', 'custom-trustpilot-reviews');
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    // Rate limiting (1 petición/min). Solo bloquea si la última fue exitosa.
    $last_request_time = get_transient('ctr_last_request_time');
    $current_time      = time();

    if ($last_request_time && ($current_time - $last_request_time) < 60) {
        if ($cached_reviews !== false) {
            return $cached_reviews;
        }
        return ['error' => __('Demasiadas solicitudes. Intenta de nuevo en unos minutos.', 'custom-trustpilot-reviews')];
    }

    $response = wp_remote_get($url, [
        'timeout'     => 30,
        'redirection' => 5,
        'user-agent'  => 'Mozilla/5.0 (compatible; CustomTrustpilotReviews/' . CTR_PLUGIN_VERSION . '; +' . home_url() . ')',
        'headers'     => [
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.5',
            'Accept-Encoding' => 'identity', // evita gzip si el host de WP no descomprime
            'Cache-Control'   => 'no-cache',
        ],
    ]);

    if (is_wp_error($response)) {
        $error = sprintf(__('Error de conexión: %s', 'custom-trustpilot-reviews'), $response->get_error_message());
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $error = sprintf(__('Error HTTP %d: No se pudo acceder a la página de Trustpilot.', 'custom-trustpilot-reviews'), $response_code);
        error_log('CTR Plugin Error: ' . $error);
        // Marcamos la petición fallida como hecha solo brevemente para no martillar
        set_transient('ctr_last_request_time', $current_time, 60);
        return ['error' => $error];
    }

    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
        $error = __('La respuesta de Trustpilot está vacía.', 'custom-trustpilot-reviews');
        error_log('CTR Plugin Error: ' . $error);
        return ['error' => $error];
    }

    // Marca la petición como exitosa para el rate limiter
    set_transient('ctr_last_request_time', $current_time, 60);

    // Parser principal: __NEXT_DATA__ JSON
    $reviews = ctr_parse_trustpilot_next_data($html, $url);

    // Solo si __NEXT_DATA__ falla, intenta DOM como red de seguridad
    if (isset($reviews['error'])) {
        $dom_reviews = ctr_parse_trustpilot_dom($html, $url);
        if (!isset($dom_reviews['error']) && !empty($dom_reviews)) {
            $reviews = $dom_reviews;
        }
    }

    if ($enable_cache && !isset($reviews['error']) && !empty($reviews)) {
        $cache_duration = get_option('ctr_cache_duration', 3600);
        set_transient('ctr_reviews_cache', $reviews, $cache_duration);
    }

    return $reviews;
}

/**
 * Parser principal: extrae las reseñas del bloque <script id="__NEXT_DATA__">.
 *
 * La ruta del JSON ha sido (y volverá a ser) inestable. Probamos varios
 * caminos conocidos y, si fallan, escaneamos el árbol completo en busca
 * de la primera lista que contenga objetos con keys típicas de review.
 */
function ctr_parse_trustpilot_next_data($html, $base_url) {
    if (!preg_match('/<script[^>]+id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $m)) {
        return ['error' => __('No se encontró __NEXT_DATA__ en la respuesta de Trustpilot.', 'custom-trustpilot-reviews')];
    }

    $data = json_decode($m[1], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => sprintf(__('JSON __NEXT_DATA__ inválido: %s', 'custom-trustpilot-reviews'), json_last_error_msg())];
    }

    // Rutas conocidas (de más específica a más genérica)
    $candidate_paths = [
        ['props', 'pageProps', 'reviews'],
        ['props', 'pageProps', 'businessUnit', 'reviews'],
        ['props', 'pageProps', 'reviewsState', 'reviews'],
    ];

    $reviews_raw = null;
    foreach ($candidate_paths as $path) {
        $node = $data;
        $ok   = true;
        foreach ($path as $key) {
            if (is_array($node) && array_key_exists($key, $node)) {
                $node = $node[$key];
            } else {
                $ok = false;
                break;
            }
        }
        if ($ok && is_array($node) && !empty($node)) {
            $reviews_raw = $node;
            break;
        }
    }

    // Fallback: walk del árbol entero buscando una lista de reviews
    if ($reviews_raw === null) {
        $reviews_raw = ctr_find_reviews_in_tree($data);
    }

    if (!is_array($reviews_raw) || empty($reviews_raw)) {
        return ['error' => __('Estructura JSON de Trustpilot no reconocida (posible cambio en su API).', 'custom-trustpilot-reviews')];
    }

    $reviews = [];
    foreach ($reviews_raw as $r) {
        if (!is_array($r)) continue;

        $title    = isset($r['title'])    ? trim($r['title'])    : '';
        $content  = isset($r['text'])     ? trim($r['text'])     : (isset($r['content']) ? trim($r['content']) : '');
        $rating   = isset($r['rating'])   ? intval($r['rating']) : (isset($r['stars']) ? intval($r['stars']) : 0);
        $author   = $r['consumer']['displayName'] ?? ($r['author']['displayName'] ?? '');
        $date     = $r['dates']['publishedDate']
                    ?? ($r['createdAt']
                    ?? ($r['date']
                    ?? ''));

        $review_url = '';
        if (!empty($r['id'])) {
            $review_url = rtrim($base_url, '/') . '#' . $r['id'];
        } elseif (!empty($r['url'])) {
            $review_url = $r['url'];
        }

        // Solo aceptamos si tiene contenido o título: evita basura
        if (empty($content) && empty($title)) {
            continue;
        }

        $reviews[] = [
            'title'      => $title !== '' ? $title : __('Reseña sin título', 'custom-trustpilot-reviews'),
            'content'    => $content !== '' ? $content : __('Reseña sin contenido', 'custom-trustpilot-reviews'),
            'consumer'   => ['displayName' => $author !== '' ? $author : __('Cliente Anónimo', 'custom-trustpilot-reviews')],
            'rating'     => ($rating >= 1 && $rating <= 5) ? $rating : 0,
            'review_url' => $review_url,
            'date'       => is_string($date) ? $date : '',
        ];
    }

    if (empty($reviews)) {
        return ['error' => __('Trustpilot devolvió 0 reseñas válidas.', 'custom-trustpilot-reviews')];
    }

    return $reviews;
}

/**
 * Walk recursivo: busca la primera lista cuyos elementos huelan a review.
 * Sirve como red de seguridad si Trustpilot reorganiza el árbol JSON.
 */
function ctr_find_reviews_in_tree($node, $depth = 0) {
    if ($depth > 8 || !is_array($node)) {
        return null;
    }

    // Lista de objetos con pinta de review
    if (array_keys($node) === range(0, count($node) - 1)) {
        if (count($node) >= 1 && is_array($node[0])) {
            $first = $node[0];
            $review_keys = ['rating', 'stars', 'text', 'title', 'consumer', 'author'];
            $score = 0;
            foreach ($review_keys as $k) {
                if (array_key_exists($k, $first)) $score++;
            }
            if ($score >= 2) {
                return $node;
            }
        }
    }

    foreach ($node as $child) {
        if (is_array($child)) {
            $found = ctr_find_reviews_in_tree($child, $depth + 1);
            if ($found !== null) {
                return $found;
            }
        }
    }

    return null;
}

/**
 * Parser DOM de respaldo (selectores muy genéricos).
 * Solo se invoca si __NEXT_DATA__ falló — y solo extrae cards bien formadas.
 * Si no encuentra nada con la heurística mínima, devuelve error: NUNCA
 * fabrica reviews a partir de párrafos sueltos.
 */
function ctr_parse_trustpilot_dom($html, $base_url) {
    $previous_state = libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->encoding = 'UTF-8';
    $loaded = @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

    libxml_clear_errors();
    libxml_use_internal_errors($previous_state);

    if (!$loaded) {
        return ['error' => __('No se pudo parsear el HTML de Trustpilot.', 'custom-trustpilot-reviews')];
    }

    $xpath  = new DOMXPath($dom);
    $cards  = $xpath->query('//article[contains(@class, "review")] | //div[@data-service-review-card-paper]');

    if (!$cards || $cards->length === 0) {
        return ['error' => __('No se encontraron cards de reseña en el DOM.', 'custom-trustpilot-reviews')];
    }

    $reviews = [];
    foreach ($cards as $card) {
        $title_node   = $xpath->query('.//h2 | .//h3', $card);
        $content_node = $xpath->query('.//p[@data-service-review-text-typography] | .//*[@data-service-review-text-typography] | .//p', $card);
        $author_attr  = $card->getAttribute('data-consumer-name');
        $author_node  = $xpath->query('.//*[@data-consumer-name-typography] | .//a[contains(@href, "/users/")]', $card);
        $rating_node  = $xpath->query('.//*[@data-service-review-rating]', $card);
        $time_node    = $xpath->query('.//time', $card);

        $title   = $title_node->length    ? trim($title_node->item(0)->textContent)   : '';
        $content = $content_node->length  ? trim($content_node->item(0)->textContent) : '';
        $author  = $author_attr ?: ($author_node->length ? trim($author_node->item(0)->textContent) : '');
        $rating  = 0;
        if ($rating_node->length) {
            $rating = intval($rating_node->item(0)->getAttribute('data-service-review-rating'));
        }
        $date    = $time_node->length ? trim($time_node->item(0)->getAttribute('datetime') ?: $time_node->item(0)->textContent) : '';

        // Heurística mínima: tiene que haber autor Y (título o contenido)
        if (empty($author) || (empty($title) && empty($content))) {
            continue;
        }

        $reviews[] = [
            'title'      => $title !== '' ? $title : __('Reseña sin título', 'custom-trustpilot-reviews'),
            'content'    => $content !== '' ? $content : __('Reseña sin contenido', 'custom-trustpilot-reviews'),
            'consumer'   => ['displayName' => $author],
            'rating'     => ($rating >= 1 && $rating <= 5) ? $rating : 0,
            'review_url' => $base_url,
            'date'       => $date,
        ];
    }

    if (empty($reviews)) {
        return ['error' => __('No se pudieron extraer reseñas con datos suficientes.', 'custom-trustpilot-reviews')];
    }

    return $reviews;
}

/**
 * Limpiar la caché de reseñas (llamada desde el panel de administración).
 */
function ctr_clear_reviews_cache() {
    delete_transient('ctr_reviews_cache');
    delete_transient('ctr_last_request_time');
}

/**
 * Número de reseñas en caché (para el panel).
 */
function ctr_get_cached_reviews_count() {
    $cached = get_transient('ctr_reviews_cache');
    if ($cached && is_array($cached) && !isset($cached['error'])) {
        return count($cached);
    }
    return 0;
}
