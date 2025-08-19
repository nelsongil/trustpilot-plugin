<?php
if (!defined('ABSPATH')) exit;

function ctr_render_reviews_carousel($atts = array()) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'count' => get_option('ctr_reviews_count', 5),
        'title' => get_option('ctr_reviews_title', __('Valoraciones de Trustpilot', 'custom-trustpilot-reviews')),
        'show_button' => 'true',
        'button_text' => __('¡Valora en Trustpilot!', 'custom-trustpilot-reviews'),
        'button_url' => 'https://es.trustpilot.com/evaluate/nelsongil.com',
        'layout' => 'grid', // grid, list, carousel
        'columns' => 1
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
    
    // Validate layout and columns
    if (!in_array($layout, ['grid', 'list', 'carousel'])) {
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
    <div class="ctr-carousel ctr-layout-<?php echo esc_attr($layout); ?>" style="text-align: center;">
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
                 style="max-width: <?php echo esc_attr($layout === 'grid' ? '1200px' : '800px'); ?>; margin: 0 auto;">
                
                <?php if ($layout === 'grid'): ?>
                    <div class="ctr-grid" style="display: grid; grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr); gap: 20px;">
                        <?php foreach ($reviews as $review): ?>
                            <div class="ctr-slide ctr-review-card" 
                                 style="background-color: #ffffff; border: 1px solid #e1e5e9; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                                <?php if (!empty($review['title'])): ?>
                                    <h3 class="ctr-review-title" style="margin-bottom: 15px; color: #333; font-size: 18px; line-height: 1.4;">
                                        <?php echo esc_html($review['title']); ?>
                                    </h3>
                                <?php endif; ?>
                                
                                <?php if (!empty($review['content'])): ?>
                                    <p class="ctr-review-content" style="margin-bottom: 15px; color: #555; line-height: 1.6; font-size: 14px;">
                                        <?php echo esc_html($review['content']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="ctr-review-author" style="font-style: italic; color: #666; margin: 0; font-size: 13px;">
                                    <strong>- <?php echo esc_html($review['consumer']['displayName'] ?? __('Cliente Anónimo', 'custom-trustpilot-reviews')); ?></strong>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($layout === 'list'): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="ctr-slide ctr-review-item" 
                             style="background-color: #ffffff; margin-bottom: 20px; border: 1px solid #e1e5e9; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: left;">
                            <?php if (!empty($review['title'])): ?>
                                <h3 class="ctr-review-title" style="margin-bottom: 15px; color: #333; font-size: 18px; line-height: 1.4;">
                                    <?php echo esc_html($review['title']); ?>
                                </h3>
                            <?php endif; ?>
                            
                            <?php if (!empty($review['content'])): ?>
                                <p class="ctr-review-content" style="margin-bottom: 15px; color: #555; line-height: 1.6; font-size: 14px;">
                                    <?php echo esc_html($review['content']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="ctr-review-author" style="font-style: italic; color: #666; margin: 0; font-size: 13px;">
                                <strong>- <?php echo esc_html($review['consumer']['displayName'] ?? __('Cliente Anónimo', 'custom-trustpilot-reviews')); ?></strong>
                            </p>
                        </div>
                    <?php endforeach; ?>
                    
                <?php else: // carousel layout ?>
                    <div class="ctr-carousel-container" style="position: relative; overflow: hidden;">
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="ctr-slide ctr-review-slide" 
                                 data-slide="<?php echo esc_attr($index); ?>"
                                 style="background-color: #ffffff; border: 1px solid #e1e5e9; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: 0 auto; max-width: 600px; <?php echo $index === 0 ? '' : 'display: none;'; ?>">
                                <?php if (!empty($review['title'])): ?>
                                    <h3 class="ctr-review-title" style="margin-bottom: 15px; color: #333; font-size: 18px; line-height: 1.4;">
                                        <?php echo esc_html($review['title']); ?>
                                    </h3>
                                <?php endif; ?>
                                
                                <?php if (!empty($review['content'])): ?>
                                    <p class="ctr-review-content" style="margin-bottom: 15px; color: #555; line-height: 1.6; font-size: 14px;">
                                        <?php echo esc_html($review['content']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="ctr-review-author" style="font-style: italic; color: #666; margin: 0; font-size: 13px;">
                                    <strong>- <?php echo esc_html($review['consumer']['displayName'] ?? __('Cliente Anónimo', 'custom-trustpilot-reviews')); ?></strong>
                                </p>
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

// Register shortcode
add_shortcode('custom_trustpilot_reviews', 'ctr_render_reviews_carousel');

// Add shortcode documentation
function ctr_shortcode_help() {
    ?>
    <div class="ctr-shortcode-help" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073e6; margin: 20px 0;">
        <h4><?php _e('Uso del Shortcode:', 'custom-trustpilot-reviews'); ?></h4>
        <p><strong><?php _e('Básico:', 'custom-trustpilot-reviews'); ?></strong> <code>[custom_trustpilot_reviews]</code></p>
        <p><strong><?php _e('Con opciones:', 'custom-trustpilot-reviews'); ?></strong> <code>[custom_trustpilot_reviews count="3" layout="grid" columns="2"]</code></p>
        
        <h4><?php _e('Parámetros disponibles:', 'custom-trustpilot-reviews'); ?></h4>
        <ul style="margin-left: 20px;">
            <li><code>count</code>: <?php _e('Número de reseñas (1-50)', 'custom-trustpilot-reviews'); ?></li>
            <li><code>title</code>: <?php _e('Título personalizado', 'custom-trustpilot-reviews'); ?></li>
            <li><code>layout</code>: <?php _e('grid, list, o carousel', 'custom-trustpilot-reviews'); ?></li>
            <li><code>columns</code>: <?php _e('Número de columnas para grid (1-4)', 'custom-trustpilot-reviews'); ?></li>
            <li><code>show_button</code>: <?php _e('Mostrar botón de valoración (true/false)', 'custom-trustpilot-reviews'); ?></li>
        </ul>
    </div>
    <?php
}

// Add help to admin page
add_action('ctr_admin_after_content', 'ctr_shortcode_help');
