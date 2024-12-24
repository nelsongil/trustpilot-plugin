<?php
if (!defined('ABSPATH')) exit;

/**
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
    }

    // Obtener el HTML de la respuesta
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
        return ['error' => 'La respuesta está vacía.'];
    }

    // Parsear el HTML con DOMDocument
    $reviews = [];
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // La @ suprime errores de HTML malformado
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Buscar las tarjetas de reseñas de forma general
    $review_cards = $xpath->query('//article');

    foreach ($review_cards as $card) {
        // Extraer título de la reseña
        $title_node = $xpath->query('.//h2', $card);
        $title = ($title_node->length > 0) ? sanitize_text_field(trim($title_node->item(0)->textContent)) : 'Título no disponible';

        // Extraer contenido de la reseña
        $content_node = $xpath->query('.//div[contains(@class, "reviewContent")]', $card);
        $content = ($content_node->length > 0) ? sanitize_text_field(trim($content_node->item(0)->textContent)) : 'Reseña no disponible';

        // Validar y añadir reseña
        if (!empty($title) || !empty($content)) {
            $reviews[] = [
                'title'   => $title,
                'content' => $content,
                'consumer' => ['displayName' => 'Cliente Anónimo']
            ];
        }
    }

    return $reviews;
}