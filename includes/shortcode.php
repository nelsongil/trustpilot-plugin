<?php
if (!defined('ABSPATH')) exit;

/**
 * Renderiza el bloque de reseñas. Usado tanto por el shortcode
 * `[custom_trustpilot_reviews]` como por el módulo Divi (que delega aquí).
 */
function ctr_render_reviews_carousel($atts = array()) {
    $atts = shortcode_atts(array(
        'count'       => get_option('ctr_reviews_count', 5),
        'per_page'    => get_option('ctr_reviews_per_page', 0), // 0 = sin paginación
        'title'       => get_option('ctr_reviews_title', __('Valoraciones de Trustpilot', 'custom-trustpilot-reviews')),
        'show_button' => get_option('ctr_show_review_button', 1) ? 'true' : 'false',
        'button_text' => get_option('ctr_button_text', __('¡Valora en Trustpilot!', 'custom-trustpilot-reviews')),
        'button_url'  => get_option('ctr_button_url', ''),
        'layout'      => get_option('ctr_default_layout', 'grid'),
        'columns'     => get_option('ctr_default_columns', 1),
        'show_stars'  => get_option('ctr_show_stars', 1) ? 'true' : 'false',
        'show_dates'  => get_option('ctr_show_dates', 1) ? 'true' : 'false',
        'clickable'   => get_option('ctr_clickable_reviews', 1) ? 'true' : 'false',
        'style'       => get_option('ctr_card_style', 'modern'),
        'colors'      => get_option('ctr_color_scheme', 'default'),
    ), $atts, 'custom_trustpilot_reviews');

    $reviews_count = absint($atts['count']);
    if ($reviews_count < 1 || $reviews_count > 50) $reviews_count = 5;

    $per_page     = absint($atts['per_page']);
    // per_page debe ser menor que count; si no, desactivar paginación
    if ($per_page < 1 || $per_page >= $reviews_count) $per_page = 0;

    $title        = sanitize_text_field($atts['title']);
    $show_button  = filter_var($atts['show_button'], FILTER_VALIDATE_BOOLEAN);
    $button_text  = sanitize_text_field($atts['button_text']);
    $button_url   = esc_url_raw($atts['button_url']);
    $layout       = sanitize_text_field($atts['layout']);
    $columns      = absint($atts['columns']);
    $show_stars   = filter_var($atts['show_stars'], FILTER_VALIDATE_BOOLEAN);
    $show_dates   = filter_var($atts['show_dates'], FILTER_VALIDATE_BOOLEAN);
    $clickable    = filter_var($atts['clickable'], FILTER_VALIDATE_BOOLEAN);
    $card_style   = sanitize_text_field($atts['style']);
    $color_scheme = sanitize_text_field($atts['colors']);

    if (!in_array($layout, ['grid', 'list', 'carousel', 'masonry', 'timeline'], true)) $layout = 'grid';
    if ($columns < 1 || $columns > 4) $columns = 1;

    $reviews   = ctr_get_trustpilot_reviews();
    $has_error = !is_array($reviews) || isset($reviews['error']);

    if (!$has_error) {
        $reviews = array_slice($reviews, 0, $reviews_count);
    }

    if ($layout === 'carousel') {
        wp_enqueue_script('ctr-carousel');
    }

    ob_start();
    ?>
    <div class="ctr-carousel ctr-layout-<?php echo esc_attr($layout); ?> ctr-style-<?php echo esc_attr($card_style); ?> ctr-colors-<?php echo esc_attr($color_scheme); ?>">
        <?php if (!empty($title)): ?>
            <h2 class="ctr-title">
                <?php echo esc_html($title); ?>
                <img src="<?php echo esc_url(CTR_PLUGIN_URL . 'assets/img/trustpilotlogo.png'); ?>" alt="Trustpilot">
            </h2>
        <?php endif; ?>

        <?php if ($has_error): ?>
            <p class="ctr-error" role="alert">
                <?php echo esc_html(is_array($reviews) ? ($reviews['error'] ?? __('No hay reseñas disponibles en este momento.', 'custom-trustpilot-reviews')) : __('No hay reseñas disponibles en este momento.', 'custom-trustpilot-reviews')); ?>
            </p>
        <?php endif; ?>

        <?php if ($show_button && !empty($button_url)): ?>
            <div class="ctr-button-container">
                <a href="<?php echo esc_url($button_url); ?>" target="_blank" rel="noopener noreferrer" class="ctr-button">
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!$has_error && !empty($reviews)): ?>
            <?php $uid = 'ctr-' . uniqid(); ?>
            <div class="ctr-reviews ctr-<?php echo esc_attr($layout); ?>"
                 id="<?php echo esc_attr($uid); ?>"
                 <?php if ($per_page > 0): ?>
                 data-ctr-per-page="<?php echo esc_attr($per_page); ?>"
                 data-ctr-total="<?php echo esc_attr(count($reviews)); ?>"
                 data-ctr-page="0"
                 <?php endif; ?>>

                <?php if ($layout === 'grid'): ?>
                    <div class="ctr-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-page-item" data-ctr-item="<?php echo esc_attr($index); ?>"
                                 <?php if ($per_page > 0 && $index >= $per_page): ?>style="display:none"<?php endif; ?>>
                                <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($layout === 'list'): ?>
                    <div class="ctr-list">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-page-item" data-ctr-item="<?php echo esc_attr($index); ?>"
                                 <?php if ($per_page > 0 && $index >= $per_page): ?>style="display:none"<?php endif; ?>>
                                <?php echo ctr_render_review_item($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($layout === 'carousel'): ?>
                    <div class="ctr-carousel-container" data-ctr-carousel="1">
                        <?php if (count($reviews) > 1): ?>
                            <button type="button" class="ctr-prev" aria-label="<?php esc_attr_e('Anterior', 'custom-trustpilot-reviews'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                        <?php endif; ?>
                        <div class="ctr-carousel-slides">
                            <?php foreach ($reviews as $index => $review): ?>
                                <div class="ctr-review-slide" data-slide="<?php echo esc_attr($index); ?>">
                                    <?php echo ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($reviews) > 1): ?>
                            <button type="button" class="ctr-next" aria-label="<?php esc_attr_e('Siguiente', 'custom-trustpilot-reviews'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        <?php endif; ?>
                    </div>

                <?php elseif ($layout === 'masonry'): ?>
                    <div class="ctr-masonry" style="columns: <?php echo esc_attr($columns); ?>;">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-masonry-item ctr-page-item" data-ctr-item="<?php echo esc_attr($index); ?>"
                                 <?php if ($per_page > 0 && $index >= $per_page): ?>style="display:none"<?php endif; ?>>
                                <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($layout === 'timeline'): ?>
                    <div class="ctr-timeline">
                        <div class="ctr-timeline-line"></div>
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-timeline-item ctr-page-item" data-ctr-item="<?php echo esc_attr($index); ?>"
                                 <?php if ($per_page > 0 && $index >= $per_page): ?>style="display:none"<?php endif; ?>>
                                <div class="ctr-timeline-marker"></div>
                                <div class="ctr-timeline-content">
                                    <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($per_page > 0 && count($reviews) > $per_page): ?>
                    <div class="ctr-pagination" id="<?php echo esc_attr($uid); ?>-pager">
                        <button type="button" class="ctr-pager-prev" disabled
                                aria-label="<?php esc_attr_e('Página anterior', 'custom-trustpilot-reviews'); ?>">
                            &#8592; <?php esc_html_e('Anterior', 'custom-trustpilot-reviews'); ?>
                        </button>
                        <span class="ctr-pager-info">
                            <?php printf(
                                esc_html__('Página %1$s de %2$s', 'custom-trustpilot-reviews'),
                                '<span class="ctr-pager-current">1</span>',
                                '<span class="ctr-pager-total">' . esc_html(ceil(count($reviews) / $per_page)) . '</span>'
                            ); ?>
                        </span>
                        <button type="button" class="ctr-pager-next"
                                aria-label="<?php esc_attr_e('Página siguiente', 'custom-trustpilot-reviews'); ?>">
                            <?php esc_html_e('Siguiente', 'custom-trustpilot-reviews'); ?> &#8594;
                        </button>
                    </div>
                    <script>
                    (function(){
                        var wrap    = document.getElementById('<?php echo esc_js($uid); ?>');
                        var pager   = document.getElementById('<?php echo esc_js($uid); ?>-pager');
                        var perPage = <?php echo (int) $per_page; ?>;
                        var total   = <?php echo count($reviews); ?>;
                        var pages   = Math.ceil(total / perPage);
                        var current = 0;

                        function showPage(p) {
                            current = p;
                            var items = wrap.querySelectorAll('.ctr-page-item');
                            var start = p * perPage;
                            var end   = start + perPage;
                            items.forEach(function(el, i) {
                                el.style.display = (i >= start && i < end) ? '' : 'none';
                            });
                            pager.querySelector('.ctr-pager-current').textContent = p + 1;
                            pager.querySelector('.ctr-pager-prev').disabled = (p === 0);
                            pager.querySelector('.ctr-pager-next').disabled = (p >= pages - 1);
                            // Scroll suave al inicio del bloque
                            wrap.scrollIntoView({behavior: 'smooth', block: 'nearest'});
                        }

                        pager.querySelector('.ctr-pager-prev').addEventListener('click', function(){
                            if (current > 0) showPage(current - 1);
                        });
                        pager.querySelector('.ctr-pager-next').addEventListener('click', function(){
                            if (current < pages - 1) showPage(current + 1);
                        });
                    })();
                    </script>
                <?php endif; ?>

            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Tarjeta para grid/masonry. Si la review es clickable usamos un <a> real
 * (accesible, focuseable, indexable) en vez de onclick="window.open(...)".
 */
function ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $style, $index = 0) {
    $is_link = $clickable && !empty($review['review_url']);

    if ($is_link) {
        $output  = '<a class="ctr-review-card ctr-clickable" href="' . esc_url($review['review_url']) . '" target="_blank" rel="noopener noreferrer">';
        $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index);
        $output .= '</a>';
    } else {
        $output  = '<div class="ctr-review-card">';
        $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index);
        $output .= '</div>';
    }

    return $output;
}

/**
 * Item para layout list, misma lógica de accesibilidad que la tarjeta.
 */
function ctr_render_review_item($review, $show_stars, $show_dates, $clickable, $style, $index = 0) {
    $is_link = $clickable && !empty($review['review_url']);

    if ($is_link) {
        $output  = '<a class="ctr-review-item ctr-clickable" href="' . esc_url($review['review_url']) . '" target="_blank" rel="noopener noreferrer">';
        $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index);
        $output .= '</a>';
    } else {
        $output  = '<div class="ctr-review-item">';
        $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index);
        $output .= '</div>';
    }

    return $output;
}

/**
 * Contenido interno de la tarjeta (estrellas, título, texto, autor, fecha).
 */
function ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index = 0) {
    $output = '';

    if ($show_stars && isset($review['rating']) && intval($review['rating']) > 0) {
        $output .= ctr_render_stars(intval($review['rating']));
    }

    if (!empty($review['title'])) {
        $output .= '<h3 class="ctr-review-title">' . esc_html($review['title']) . '</h3>';
    }

    if (!empty($review['content'])) {
        $output .= '<p class="ctr-review-content">' . esc_html($review['content']) . '</p>';
    }

    $output .= '<hr class="ctr-review-divider">';

    $author_name = $review['consumer']['displayName'] ?? __('Cliente Anónimo', 'custom-trustpilot-reviews');
    $initials    = '';
    if (!empty($author_name)) {
        $parts = preg_split('/\s+/', trim($author_name));
        foreach ($parts as $part) {
            if ($part === '') continue;
            $initials .= mb_substr($part, 0, 1);
        }
        $initials = mb_strtoupper(mb_substr($initials, 0, 2));
    }

    $avatar_class = 'ctr-avatar ctr-avatar-' . ($index % 5);

    $output .= '<div class="ctr-review-author">';
    $output .= '<div class="' . esc_attr($avatar_class) . '" aria-hidden="true">' . esc_html($initials) . '</div>';
    $output .= '<div class="ctr-author-info">';
    $output .= '<strong class="ctr-author-name">' . esc_html($author_name) . '</strong>';

    if ($show_dates && !empty($review['date'])) {
        $output .= '<span class="ctr-review-date">' . esc_html($review['date']) . '</span>';
    }

    $output .= '</div></div>';

    return $output;
}

/**
 * Estrellas (1-5).
 */
function ctr_render_stars($rating) {
    $rating = max(0, min(5, intval($rating)));
    $output = '<div class="ctr-stars" aria-label="' . esc_attr(sprintf(__('Valoración %d de 5', 'custom-trustpilot-reviews'), $rating)) . '">';

    for ($i = 1; $i <= 5; $i++) {
        $class = ($i <= $rating) ? 'ctr-star ctr-star-filled' : 'ctr-star ctr-star-empty';
        $star  = ($i <= $rating) ? '★' : '☆';
        $output .= '<span class="' . esc_attr($class) . '" aria-hidden="true">' . $star . '</span>';
    }

    $output .= '<span class="ctr-rating-text">(' . esc_html($rating) . '/5)</span>';
    $output .= '</div>';

    return $output;
}

// Registro del shortcode
add_shortcode('custom_trustpilot_reviews', 'ctr_render_reviews_carousel');

/**
 * Documentación visible en el panel de admin.
 */
function ctr_shortcode_help() {
    ?>
    <div class="ctr-shortcode-help" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073e6; margin: 20px 0;">
        <h4><?php _e('Uso del Shortcode:', 'custom-trustpilot-reviews'); ?></h4>
        <p><strong><?php _e('Básico:', 'custom-trustpilot-reviews'); ?></strong> <code>[custom_trustpilot_reviews]</code></p>
        <p><strong><?php _e('Con opciones:', 'custom-trustpilot-reviews'); ?></strong> <code>[custom_trustpilot_reviews layout="masonry" count="6" columns="3" show_stars="true"]</code></p>

        <h4><?php _e('Parámetros disponibles:', 'custom-trustpilot-reviews'); ?></h4>
        <ul style="margin-left: 20px;">
            <li><code>layout</code>: grid, list, carousel, masonry, timeline</li>
            <li><code>count</code>: número de reseñas (1-50)</li>
            <li><code>columns</code>: número de columnas para grid (1-4)</li>
            <li><code>show_stars</code>: mostrar estrellas (true/false)</li>
            <li><code>show_dates</code>: mostrar fechas (true/false)</li>
            <li><code>clickable</code>: reseñas clickeables (true/false)</li>
            <li><code>style</code>: modern, classic, minimal, elegant, bold</li>
            <li><code>colors</code>: default, blue, green, purple, orange, dark</li>
        </ul>
    </div>
    <?php
}
add_action('ctr_admin_after_content', 'ctr_shortcode_help');
