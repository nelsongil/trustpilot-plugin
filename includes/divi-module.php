<?php
if (!class_exists('ET_Builder_Module')) return;

class Custom_Trustpilot_Reviews_Module extends ET_Builder_Module {
    public $slug       = 'custom_trustpilot_reviews_module';
    public $vb_support = 'on';

    function init() {
        $this->name = esc_html__('Trustpilot Reviews', 'custom-trustpilot-reviews');
        $this->icon_path = plugin_dir_path(__FILE__) . 'icon.svg';
    }

    function get_fields() {
        return [
            'title' => [
                'label'       => esc_html__('Título', 'custom-trustpilot-reviews'),
                'type'        => 'text',
                'description' => esc_html__('Título del carrusel de reseñas.', 'custom-trustpilot-reviews'),
                'default'     => 'Valoraciones de Trustpilot',
            ],
            'number' => [
                'label'       => esc_html__('Número de Reseñas', 'custom-trustpilot-reviews'),
                'type'        => 'number',
                'default'     => 5,
            ],
        ];
    }

    function render($attrs, $content = null, $render_slug) {
        // Obtener reseñas
        $reviews = ctr_get_trustpilot_reviews();
        $title = $this->props['title'];
        $number = intval($this->props['number']);
        $reviews = array_slice($reviews, 0, $number);

        // Generar salida HTML
        ob_start();
        ?>
        <div class="ctr-carousel">
            <h2><?php echo esc_html($title); ?></h2>
            <div class="ctr-reviews">
                <?php foreach ($reviews as $review): ?>
                    <div class="ctr-slide">
                        <p>"<?php echo esc_html($review['text']); ?>"</p>
                        <p><strong>- <?php echo esc_html($review['consumer']['displayName']); ?></strong></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Custom_Trustpilot_Reviews_Module();
