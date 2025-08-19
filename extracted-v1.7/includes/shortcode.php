<?php
if (!defined('ABSPATH')) exit;

function ctr_render_reviews_carousel($atts = array()) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'count' => get_option('ctr_reviews_count', 5),
        'title' => get_option('ctr_reviews_title', __('Valoraciones de Trustpilot', 'custom-trustpilot-reviews')),
        'show_button' => get_option('ctr_show_review_button', 1) ? 'true' : 'false',
        'button_text' => get_option('ctr_button_text', __('¡Valora en Trustpilot!', 'custom-trustpilot-reviews')),
        'button_url' => get_option('ctr_button_url', 'https://es.trustpilot.com/evaluate/nelsongil.com'),
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
    <div class="ctr-carousel ctr-layout-<?php echo esc_attr($layout); ?> ctr-style-<?php echo esc_attr($card_style); ?> ctr-colors-<?php echo esc_attr($color_scheme); ?>" style="text-align: center;">
        <?php if (!empty($title)): ?>
            <h2 class="ctr-title" style="display: inline-flex; align-items: center; margin-bottom: 20px;">
                <?php echo esc_html($title); ?>
                <img src="<?php echo esc_url(CTR_PLUGIN_URL . 'assets/img/trustpilotlogo.png'); ?>" 
                    alt="Trustpilot" style="margin-left: 10px; width: 24px; height: 24px;">
            </h2>
        <?php endif; ?>

        <?php if ($show_button): ?>
            <div class="ctr-button-container" style="margin-bottom: 30px;">
                <a href="<?php echo esc_url($button_url); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="ctr-button"
                   style="display: inline-block; padding: 12px 20px; background-color: #0073e6; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background-color 0.3s ease;">
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($reviews)): ?>
            <div class="ctr-reviews ctr-<?php echo esc_attr($layout); ?>" 
                 style="max-width: <?php echo esc_attr($layout === 'grid' ? '1200px' : ($layout === 'masonry' ? '1400px' : '800px')); ?>; margin: 0 auto;">
                
                <?php if ($layout === 'grid'): ?>
                    <div class="ctr-grid" style="display: grid; grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr); gap: 20px;">
                        <?php foreach ($reviews as $review): ?>
                            <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style); ?>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($layout === 'list'): ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php echo ctr_render_review_item($review, $show_stars, $show_dates, $clickable, $card_style); ?>
                    <?php endforeach; ?>
                    
                <?php elseif ($layout === 'carousel'): ?>
                    <div class="ctr-carousel-container" style="position: relative; overflow: hidden;">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-review-slide" 
                                 data-slide="<?php echo esc_attr($index); ?>"
                                 style="background-color: #ffffff; border: 1px solid #e1e5e9; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: 0 auto; max-width: 600px; <?php echo $index === 0 ? '' : 'display: none;'; ?>">
                                <?php echo ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $card_style); ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($reviews) > 1): ?>
                            <div class="ctr-carousel-nav" style="margin-top: 20px; text-align: center;">
                                <button class="ctr-prev" style="background: #0073e6; color: white; border: none; padding: 8px 15px; border-radius: 4px; margin-right: 10px; cursor: pointer;">
                                    <?php _e('Anterior', 'custom-trustpilot-reviews'); ?>
                                </button>
                                <button class="ctr-next" style="background: #0073e6; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                    <?php _e('Siguiente', 'custom-trustpilot-reviews'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($layout === 'masonry'): ?>
                    <div class="ctr-masonry" style="columns: <?php echo esc_attr($columns); ?>; column-gap: 20px;">
                        <?php foreach ($reviews as $review): ?>
                            <div class="ctr-masonry-item" style="break-inside: avoid; margin-bottom: 20px;">
                                <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($layout === 'timeline'): ?>
                    <div class="ctr-timeline" style="position: relative; max-width: 800px; margin: 0 auto;">
                        <div class="ctr-timeline-line" style="position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: #0073e6; transform: translateX(-50%);"></div>
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-timeline-item" style="position: relative; margin-bottom: 30px; <?php echo $index % 2 === 0 ? 'text-align: left;' : 'text-align: right;'; ?>">
                                <div class="ctr-timeline-marker" style="position: absolute; left: 50%; top: 20px; width: 16px; height: 16px; background: #0073e6; border-radius: 50%; transform: translateX(-50%); border: 3px solid #fff; box-shadow: 0 0 0 3px #0073e6;"></div>
                                <div class="ctr-timeline-content" style="width: 45%; <?php echo $index % 2 === 0 ? 'margin-right: 55%;' : 'margin-left: 55%;'; ?>">
                                    <?php echo ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $card_style); ?>
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
            var currentSlide = 0;
            var totalSlides = <?php echo count($reviews); ?>;
            
            $('.ctr-next').on('click', function() {
                $('.ctr-review-slide[data-slide="' + currentSlide + '"]').hide();
                currentSlide = (currentSlide + 1) % totalSlides;
                $('.ctr-review-slide[data-slide="' + currentSlide + '"]').show();
            });
            
            $('.ctr-prev').on('click', function() {
                $('.ctr-review-slide[data-slide="' + currentSlide + '"]').hide();
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                $('.ctr-review-slide[data-slide="' + currentSlide + '"]').show();
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
function ctr_render_review_card($review, $show_stars, $show_dates, $clickable, $style) {
    $card_class = 'ctr-slide ctr-review-card';
    $card_style = 'background-color: #ffffff; border: 1px solid #e1e5e9; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s ease, box-shadow 0.2s ease;';
    
    if ($clickable && !empty($review['review_url'])) {
        $card_class .= ' ctr-clickable';
        $card_style .= ' cursor: pointer;';
    }
    
    $output = '<div class="' . esc_attr($card_class) . '" style="' . esc_attr($card_style) . '"';
    
    if ($clickable && !empty($review['review_url'])) {
        $output .= ' onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"';
    }
    
    $output .= '>';
    $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style);
    $output .= '</div>';
    
    return $output;
}

/**
 * Render a review item for list layout
 */
function ctr_render_review_item($review, $show_stars, $show_dates, $clickable, $style) {
    $item_class = 'ctr-slide ctr-review-item';
    $item_style = 'background-color: #ffffff; margin-bottom: 20px; border: 1px solid #e1e5e9; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: left;';
    
    if ($clickable && !empty($review['review_url'])) {
        $item_class .= ' ctr-clickable';
        $item_style .= ' cursor: pointer;';
    }
    
    $output = '<div class="' . esc_attr($item_class) . '" style="' . esc_attr($item_style) . '"';
    
    if ($clickable && !empty($review['review_url'])) {
        $output .= ' onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"';
    }
    
    $output .= '>';
    $output .= ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style);
    $output .= '</div>';
    
    return $output;
}

/**
 * Render review content (title, content, stars, date, author)
 */
function ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $style) {
    $output = '';
    
    // Show stars if enabled
    if ($show_stars && isset($review['rating'])) {
        $output .= ctr_render_stars($review['rating']);
    }
    
    // Title
    if (!empty($review['title'])) {
        $output .= '<h3 class="ctr-review-title" style="margin-bottom: 15px; color: #333; font-size: 18px; line-height: 1.4;">';
        $output .= esc_html($review['title']);
        $output .= '</h3>';
    }
    
    // Content
    if (!empty($review['content'])) {
        $output .= '<p class="ctr-review-content" style="margin-bottom: 15px; color: #555; line-height: 1.6; font-size: 14px;">';
        $output .= esc_html($review['content']);
        $output .= '</p>';
    }
    
    // Date if enabled
    if ($show_dates && !empty($review['date'])) {
        $output .= '<p class="ctr-review-date" style="font-size: 12px; color: #888; margin-bottom: 10px;">';
        $output .= esc_html($review['date']);
        $output .= '</p>';
    }
    
    // Author
    $output .= '<p class="ctr-review-author" style="font-style: italic; color: #666; margin: 0; font-size: 13px;">';
    $output .= '<strong>- ' . esc_html($review['consumer']['displayName'] ?? __('Cliente Anónimo', 'custom-trustpilot-reviews')) . '</strong>';
    $output .= '</p>';
    
    return $output;
}

/**
 * Render star rating
 */
function ctr_render_stars($rating) {
    $output = '<div class="ctr-stars" style="margin-bottom: 15px; text-align: center;">';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<span class="ctr-star ctr-star-filled" style="color: #ffd700; font-size: 20px; margin: 0 2px;">★</span>';
        } else {
            $output .= '<span class="ctr-star ctr-star-empty" style="color: #ddd; font-size: 20px; margin: 0 2px;">☆</span>';
        }
    }
    
    $output .= '<span class="ctr-rating-text" style="margin-left: 8px; font-size: 14px; color: #666;">(' . esc_html($rating) . '/5)</span>';
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
