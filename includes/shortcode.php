<?php
if (!defined('ABSPATH')) exit;

function ctr_render_reviews_carousel($atts = array()) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'count' => get_option('ctr_reviews_count', 5),
        'title' => get_option('ctr_reviews_title', __('Valoraciones de Trustpilot', 'custom-trustpilot-reviews')),
        'show_button' => get_option('ctr_show_review_button', 1) ? 'true' : 'false',
        'button_text' => get_option('ctr_button_text', __('¡Valora en Trustpilot!', 'custom-trustpilot-reviews')),
        'button_url' => get_option('ctr_button_url', ''),
        'layout' => get_option('ctr_default_layout', 'grid'),
        'columns' => get_option('ctr_default_columns', 1),
        'show_stars' => get_option('ctr_show_stars', 1) ? 'true' : 'false',
        'show_dates' => get_option('ctr_show_dates', 1) ? 'true' : 'false',
        'clickable' => get_option('ctr_clickable_reviews', 1) ? 'true' : 'false',
        'style' => get_option('ctr_card_style', 'modern'),
        'colors' => get_option('ctr_color_scheme', 'default')
    ), $atts, 'custom_trustpilot_reviews');
    
    // Validate and sanitize attributes
    $reviews_count = absint($atts['count']);
    if ($reviews_count < 1 || $reviews_count > 50) {
        $reviews_count = 5;
    }
    
    $title = sanitize_text_field($atts['title']);
    $show_button = filter_var($atts['show_button'], FILTER_VALIDATE_BOOLEAN);
    $button_text = sanitize_text_field($atts['button_text']);
    $button_url = esc_url_raw($atts['button_url']);
    $layout = sanitize_text_field($atts['layout']);
    $columns = absint($atts['columns']);
    $show_stars = filter_var($atts['show_stars'], FILTER_VALIDATE_BOOLEAN);
    $show_dates = filter_var($atts['show_dates'], FILTER_VALIDATE_BOOLEAN);
    $clickable = filter_var($atts['clickable'], FILTER_VALIDATE_BOOLEAN);
    $card_style = sanitize_text_field($atts['style']);
    $color_scheme = sanitize_text_field($atts['colors']);
    
    // Validate layout and columns
    if (!in_array($layout, ['grid', 'list', 'carousel', 'masonry', 'timeline'])) {
        $layout = 'grid';
    }
    if ($columns < 1 || $columns > 4) {
        $columns = 1;
    }
    
    // Get reviews
    $reviews = ctr_get_trustpilot_reviews();

    // Handle errors
    if (isset($reviews['error'])) {
        return '<div class="ctr-error">' . esc_html($reviews['error']) . '</div>';
    }

    // Limit the number of reviews to show
    $reviews = array_slice($reviews, 0, $reviews_count);

    // Generate HTML output
    ob_start();
    ?>
    <div class="ctr-carousel ctr-layout-<?php echo esc_attr($layout); ?> ctr-style-<?php echo esc_attr($card_style); ?> ctr-colors-<?php echo esc_attr($color_scheme); ?>">
        <?php if (!empty($title)): ?>
            <h2 class="ctr-title">
                <?php echo esc_html($title); ?>
                <img src="<?php echo esc_url(CTR_PLUGIN_URL . 'assets/img/trustpilotlogo.png'); ?>" alt="Trustpilot">
            </h2>
        <?php endif; ?>

        <?php if ($show_button): ?>
            <div class="ctr-button-container">
                <a href="<?php echo esc_url($button_url); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="ctr-button">
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($reviews)): ?>
            <div class="ctr-reviews ctr-<?php echo esc_attr($layout); ?>">
                
                <?php if ($layout === 'grid'): ?>
                    <div class="ctr-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
                        <?php foreach ($reviews as $index => $review): ?>
                            <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($layout === 'list'): ?>
                    <div class="ctr-list">
                        <?php foreach ($reviews as $index => $review): ?>
                            <?php echo ctr_render_review_item($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($layout === 'carousel'): ?>
                    <div class="ctr-carousel-container">
                        <?php if (count($reviews) > 1): ?>
                            <button class="ctr-prev" aria-label="<?php esc_attr_e('Anterior', 'custom-trustpilot-reviews'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
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
                            <button class="ctr-next" aria-label="<?php esc_attr_e('Siguiente', 'custom-trustpilot-reviews'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($layout === 'masonry'): ?>
                    <div class="ctr-masonry" style="columns: <?php echo esc_attr($columns); ?>;">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-masonry-item">
                                <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($layout === 'timeline'): ?>
                    <div class="ctr-timeline">
                        <div class="ctr-timeline-line"></div>
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-timeline-item">
                                <div class="ctr-timeline-marker"></div>
                                <div class="ctr-timeline-content">
                                    <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="ctr-no-reviews" style="color: #666; font-style: italic;">
                <?php _e('No hay reseñas disponibles en este momento.', 'custom-trustpilot-reviews'); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <?php if ($layout === 'carousel' && count($reviews) > 1): ?>
        <script>
        jQuery(document).ready(function($) {
            $('.ctr-carousel-container').each(function() {
                var $container = $(this);
                var $slides = $container.find('.ctr-carousel-slides');
                var count = $slides.find('.ctr-review-slide').length;
                var current = 0;
                
                $container.find('.ctr-next').on('click', function() {
                    current = (current + 1) % count;
                    $slides.css('transform', 'translateX(-' + (current * 100) + '%)');
                });
                
                $container.find('.ctr-prev').on('click', function() {
                    current = (current - 1 + count) % count;
                    $slides.css('transform', 'translateX(-' + (current * 100) + '%)');
                });
            });
        });
        </script>
    <?php endif; ?>
    
    <?php
    return ob_get_clean();
}

/**
 * Render a review card for grid and masonry layouts
 */
function ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $style, $index = 0) {
    $card_class = 'ctr-review-card';
    if ($clickable && !empty($review['review_url'])) {
        $card_class .= ' ctr-clickable';
    }
    
    $output = '<div class="' . esc_attr($card_class) . '"';
    if ($clickable && !empty($review['review_url'])) {
        $output .= ' onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"';
    }
    $output .= '>';
    $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index);
    $output .= '</div>';
    
    return $output;
}

/**
 * Render a review item for list layout
 */
function ctr_render_review_item($review, $show_stars, $show_dates, $clickable, $style, $index = 0) {
    $item_class = 'ctr-review-item';
    if ($clickable && !empty($review['review_url'])) {
        $item_class .= ' ctr-clickable';
    }
    
    $output = '<div class="' . esc_attr($item_class) . '"';
    if ($clickable && !empty($review['review_url'])) {
        $output .= ' onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"';
    }
    $output .= '>';
    $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index);
    $output .= '</div>';
    
    return $output;
}

/**
 * Render review content (title, content, stars, date, author)
 */
function ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style, $index = 0) {
    $output = '';
    
    // Show stars if enabled
    if ($show_stars && isset($review['rating'])) {
        $output .= ctr_render_stars($review['rating']);
    }
    
    // Title
    if (!empty($review['title'])) {
        $output .= '<h3 class="ctr-review-title">';
        $output .= esc_html($review['title']);
        $output .= '</h3>';
    }
    
    // Content
    if (!empty($review['content'])) {
        $output .= '<p class="ctr-review-content">';
        $output .= esc_html($review['content']);
        $output .= '</p>';
    }
    
    // Divider
    $output .= '<hr class="ctr-review-divider">';
    
    // Author complex structure
    $author_name = $review['consumer']['displayName'] ?? __('Cliente Anónimo', 'custom-trustpilot-reviews');
    $initials = '';
    if (!empty($author_name)) {
        $parts = explode(' ', $author_name);
        foreach ($parts as $part) {
            $initials .= mb_substr($part, 0, 1);
        }
        $initials = mb_substr($initials, 0, 2);
    }
    
    $avatar_class = 'ctr-avatar ctr-avatar-' . ($index % 5);
    
    $output .= '<div class="ctr-review-author">';
    $output .= '<div class="' . esc_attr($avatar_class) . '">' . esc_html($initials) . '</div>';
    $output .= '<div class="ctr-author-info">';
    $output .= '<strong class="ctr-author-name">' . esc_html($author_name) . '</strong>';
    
    // Date if enabled
    if ($show_dates && !empty($review['date'])) {
        $output .= '<span class="ctr-review-date">' . esc_html($review['date']) . '</span>';
    }
    
    $output .= '</div>'; // .ctr-author-info
    $output .= '</div>'; // .ctr-review-author
    
    return $output;
}

/**
 * Render star rating
 */
function ctr_render_stars($rating) {
    $output = '<div class="ctr-stars">';
    
    for ($i = 1; $i <= 5; $i++) {
        $class = ($i <= $rating) ? 'ctr-star ctr-star-filled' : 'ctr-star ctr-star-empty';
        $star = ($i <= $rating) ? '★' : '☆';
        $output .= '<span class="' . esc_attr($class) . '">' . $star . '</span>';
    }
    
    $output .= '<span class="ctr-rating-text">(' . esc_html($rating) . '/5)</span>';
    $output .= '</div>';
    
    return $output;
}

// Register shortcode
add_shortcode('custom_trustpilot_reviews', 'ctr_render_reviews_carousel');

// Add shortcode documentation
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
        
        <h4><?php _e('Nuevos layouts disponibles:', 'custom-trustpilot-reviews'); ?></h4>
        <ul style="margin-left: 20px;">
            <li><strong>Grid:</strong> Diseño en cuadrícula con columnas configurables</li>
            <li><strong>List:</strong> Lista vertical de reseñas</li>
            <li><strong>Carousel:</strong> Carrusel con navegación</li>
            <li><strong>Masonry:</strong> Diseño tipo Pinterest con columnas</li>
            <li><strong>Timeline:</strong> Línea de tiempo vertical</li>
        </ul>
    </div>
    <?php
}

// Add help to admin page
add_action('ctr_admin_after_content', 'ctr_shortcode_help');
