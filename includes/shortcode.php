<?php
if (!defined('ABSPATH')) exit;

<<<<<<< HEAD
/**
 * Función de shortcode para mostrar las reseñas de Trustpilot.
 *
 * @param array $atts Atributos del shortcode.
 * @return string HTML de las reseñas.
 */
function ctr_shortcode($atts) {
    // Atributos por defecto del shortcode
    $default_atts = [
        'config' => '',
        'count' => get_option('ctr_reviews_count', 5),
        'show_rating' => true,
        'show_date' => true,
        'show_title' => true,
        'show_author' => true,
        'show_buttons' => true,
        'layout' => 'list', // 'list', 'grid', o 'carousel'
        'max_width' => '800px',
        'spacing' => '20px',
        'star_color' => '#00b67a',
        'background_color' => '#ffffff',
        'text_color' => '#333333',
        'button_color' => '#00b67a',
        'button_text_color' => '#ffffff',
        'class' => '',
    ];

    // Combinar atributos con los valores por defecto
    $atts = shortcode_atts($default_atts, $atts, 'custom_trustpilot_reviews');

    $api_url = get_option('ctr_api_url', '');
    $reviews_count = intval($atts['count']);
    $cache_duration = get_option('ctr_cache_duration', 3600);
    $show_rating = filter_var($atts['show_rating'], FILTER_VALIDATE_BOOLEAN);
    $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
    $show_title = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN);
    $show_author = filter_var($atts['show_author'], FILTER_VALIDATE_BOOLEAN);
    $show_buttons = filter_var($atts['show_buttons'], FILTER_VALIDATE_BOOLEAN);
    $layout = sanitize_text_field($atts['layout']);
    $max_width = sanitize_text_field($atts['max_width']);
    $spacing = sanitize_text_field($atts['spacing']);
    $star_color = sanitize_hex_color($atts['star_color']);
    $background_color = sanitize_hex_color($atts['background_color']);
    $text_color = sanitize_hex_color($atts['text_color']);
    $button_color = sanitize_hex_color($atts['button_color']);
    $button_text_color = sanitize_hex_color($atts['button_text_color']);

    // Si se especificó un preset, intentar cargar sus configuraciones
    if (!empty($atts['config'])) {
        $preset_slug = sanitize_title($atts['config']);
        $preset_post = get_page_by_path($preset_slug, OBJECT, 'trustpilot_preset');

        if ($preset_post) {
            $api_url = get_post_meta($preset_post->ID, '_ctr_api_url', true);
            $reviews_count = intval(get_post_meta($preset_post->ID, '_ctr_reviews_count', true));
            $cache_duration = intval(get_post_meta($preset_post->ID, '_ctr_cache_duration', true));
            $show_rating = (get_post_meta($preset_post->ID, '_ctr_show_rating', true) === 'on');
            $show_date = (get_post_meta($preset_post->ID, '_ctr_show_date', true) === 'on');
            $show_title = (get_post_meta($preset_post->ID, '_ctr_show_title', true) === 'on');
            $show_author = (get_post_meta($preset_post->ID, '_ctr_show_author', true) === 'on');
            $show_buttons = (get_post_meta($preset_post->ID, '_ctr_show_buttons', true) === 'on');
            $layout = sanitize_text_field(get_post_meta($preset_post->ID, '_ctr_layout', true));
            $max_width = sanitize_text_field(get_post_meta($preset_post->ID, '_ctr_max_width', true));
            $spacing = sanitize_text_field(get_post_meta($preset_post->ID, '_ctr_spacing', true));
            $star_color = sanitize_hex_color(get_post_meta($preset_post->ID, '_ctr_star_color', true));
            $background_color = sanitize_hex_color(get_post_meta($preset_post->ID, '_ctr_background_color', true));
            $text_color = sanitize_hex_color(get_post_meta($preset_post->ID, '_ctr_text_color', true));
            $button_color = sanitize_hex_color(get_post_meta($preset_post->ID, '_ctr_button_color', true));
            $button_text_color = sanitize_hex_color(get_post_meta($preset_post->ID, '_ctr_button_text_color', true));
            $autoplay_interval = intval(get_post_meta($preset_post->ID, '_ctr_autoplay_interval', true));
        }
    }

    // Obtener reseñas
    $reviews = ctr_get_trustpilot_reviews($api_url, $reviews_count, $cache_duration);

    if (is_wp_error($reviews)) {
        return '<div class="ctr-error">' . $reviews->get_error_message() . '</div>';
    }

    if (empty($reviews)) {
        return '<div class="ctr-no-reviews">' . __('No hay reseñas para mostrar.', 'custom-trustpilot-reviews') . '</div>';
    }

    // Iniciar buffer de salida
    ob_start();

    // Extraer el nombre del negocio de la URL de Trustpilot para los botones
    $business_name = '';
    if (!empty($api_url)) {
        $parsed_url = parse_url($api_url);
        if (isset($parsed_url['path'])) {
            $path_parts = explode('/', trim($parsed_url['path'], '/'));
            if (!empty($path_parts)) {
                $business_name = end($path_parts);
            }
        }
    }
    $review_url = !empty($business_name) ? "https://es.trustpilot.com/evaluate/" . $business_name : $api_url;
    ?>
    <div class="ctr-reviews-container <?php echo esc_attr($atts['class']); ?> <?php echo esc_attr($layout); ?>"
         style="--ctr-max-width: <?php echo esc_attr($max_width); ?>;
                --ctr-spacing: <?php echo esc_attr($spacing); ?>;
                --ctr-star-color: <?php echo esc_attr($star_color); ?>;
                --ctr-background-color: <?php echo esc_attr($background_color); ?>;
                --ctr-text-color: <?php echo esc_attr($text_color); ?>;
                --ctr-button-color: <?php echo esc_attr($button_color); ?>;
                --ctr-button-text-color: <?php echo esc_attr($button_text_color); ?>;
                --ctr-button-hover-color: <?php echo esc_attr(adjust_brightness($button_color, -20)); ?>;
                --ctr-meta-text-color: <?php echo esc_attr(adjust_brightness($text_color, 40)); ?>;
                --ctr-autoplay-interval: <?php echo esc_attr($autoplay_interval); ?>ms;">
        <h2 class="ctr-main-title"><?php _e('Valoraciones de Trustpilot', 'custom-trustpilot-reviews'); ?></h2>
        <?php if ($show_buttons): // Condición para mostrar botones ?>
        <div class="ctr-buttons">
            <a href="<?php echo esc_url($review_url); ?>" target="_blank" class="ctr-button ctr-review-button"><?php _e('¡Valora en Trustpilot!', 'custom-trustpilot-reviews'); ?></a>
            <a href="<?php echo esc_url($api_url); ?>" target="_blank" class="ctr-button ctr-view-reviews-button"><?php _e('Ver valoraciones', 'custom-trustpilot-reviews'); ?></a>
        </div>
        <?php endif; ?>

        <?php if ($layout === 'carousel'): // Wrapper para carrusel ?>
        <div class="ctr-carousel-inner">
        <?php endif; ?>

        <?php foreach ($reviews as $review): ?>
            <div class="ctr-review">
                <?php if ($show_rating && isset($review['rating'])): ?>
                    <div class="ctr-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="ctr-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_title && !empty($review['title'])): ?>
                    <h3 class="ctr-title"><?php echo esc_html($review['title']); ?></h3>
                <?php endif; ?>

                <div class="ctr-content">
                    <?php echo wp_kses_post(wpautop($review['content'])); ?>
                </div>

                <div class="ctr-meta">
                    <?php if ($show_author): // Condición para mostrar autor ?>
                        <span class="ctr-author"><?php echo esc_html($review['consumer']['displayName']); ?></span>
                    <?php endif; ?>
                    <?php if ($show_date && !empty($review['date'])): ?>
                        <span class="ctr-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($review['date']))); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($layout === 'carousel'): // Cerrar wrapper de carrusel ?>
        </div>
        <?php endif; ?>

    </div>

    <?php
    return ob_get_clean();
}

/**
 * Función auxiliar para ajustar el brillo de un color
 */
function adjust_brightness($hex, $steps) {
    // Eliminar el # si existe
    $hex = ltrim($hex, '#');
    
    // Convertir a RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Ajustar brillo
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    // Convertir de nuevo a hex
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
add_shortcode('custom_trustpilot_reviews', 'ctr_shortcode');
=======
function ctr_render_reviews_carousel() {
    $title = get_option('ctr_reviews_title', 'Valoraciones de Trustpilot');
    $reviews_count = intval(get_option('ctr_reviews_count', 5));
    $reviews = ctr_get_trustpilot_reviews();

    // Si ocurre un error, mostrar un mensaje
    if (isset($reviews['error'])) {
        return '<p>' . esc_html($reviews['error']) . '</p>';
    }

    // Limitar el número de reseñas a mostrar
    $reviews = array_slice($reviews, 0, $reviews_count);

    // Generar salida HTML
    ob_start();
    ?>
    <div class="ctr-carousel" style="text-align: center;">
        <h2 style="display: inline-flex; align-items: center;">
            <?php echo esc_html($title); ?>
            <img src="<?php echo esc_url(plugins_url('assets/img/trustpilotlogo.png', dirname(__FILE__))); ?>" 
                alt="Trustpilot" style="margin-left: 10px; width: 24px; height: 24px;">
        </h2>

        <div style="margin-bottom: 20px;">
            <a href="https://es.trustpilot.com/evaluate/nelsongil.com" target="_blank" rel="noopener noreferrer" 
               style="display: inline-block; padding: 10px 15px; background-color: #0073e6; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                ¡Valora en Trustpilot!
            </a>
        </div>
        <div class="ctr-reviews" style="max-width: 800px; margin: 0 auto;">
            <?php foreach ($reviews as $review): ?>
                <div class="ctr-slide" style="background-color: #ffffff; margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 10px;"><?php echo esc_html($review['title']); ?></h3>
                    <p style="margin-bottom: 10px;"><?php echo esc_html($review['content']); ?></p>
                    <p style="font-style: italic; color: #666;"><strong><?php echo esc_html($review['consumer']['displayName'] ?? ''); ?></strong></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_trustpilot_reviews', 'ctr_render_reviews_carousel');
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
