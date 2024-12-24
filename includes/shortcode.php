<?php
if (!defined('ABSPATH')) exit;

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
