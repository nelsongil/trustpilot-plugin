<?php
<<<<<<< HEAD
if (!class_exists('ET_Builder_Module')) return;

class Custom_Trustpilot_Reviews_Module extends ET_Builder_Module {
    public $slug       = 'custom_trustpilot_reviews_module';
=======
if (!defined('ABSPATH')) exit;

class Ctr_Trustpilot_Module extends ET_Builder_Module {
    public $slug       = 'ctr_trustpilot_module';
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
    public $vb_support = 'on';

    function init() {
        $this->name = esc_html__('Trustpilot Reviews', 'custom-trustpilot-reviews');
<<<<<<< HEAD
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
=======
    }

    public function get_fields() {
        return array(
            'title' => array(
                'label'           => esc_html__('Título', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'default'         => 'Valoraciones de Trustpilot',
                'option_category' => 'basic_option',
            ),
            'url' => array(
                'label'           => esc_html__('URL de Trustpilot', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'option_category' => 'basic_option',
            ),
            'reviews_count' => array(
                'label'           => esc_html__('Número de reseñas', 'custom-trustpilot-reviews'),
                'type'            => 'number',
                'default'         => 5,
                'option_category' => 'basic_option',
            ),
        );
    }

    public function render($attrs, $content = null, $render_slug) {
        $title         = $this->props['title'];
        $url           = $this->props['url'];
        $reviews_count = intval($this->props['reviews_count']);

        // Obtener reseñas (usando la lógica existente del plugin)
        $reviews = ctr_get_trustpilot_reviews();
        ob_start();
        ?>
        <div class="ctr-reviews">
            <h2 style="display: flex; align-items: center;">
                <?php echo esc_html($title); ?>
                <img src="<?php echo esc_url(plugins_url('assets/img/trustpilotlogo.png', dirname(__FILE__))); ?>" 
                     alt="Trustpilot" style="margin-left: 10px; width: 24px; height: 24px;">
            </h2>
            <?php if (!empty($reviews) && !isset($reviews['error'])): ?>
                <?php foreach (array_slice($reviews, 0, $reviews_count) as $review): ?>
                    <div class="ctr-review" style="background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                        <p><?php echo esc_html($review['text']); ?></p>
                        <p><strong>- <?php echo esc_html($review['consumer']['displayName'] ?? 'Cliente Anónimo'); ?></strong></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php echo esc_html($reviews['error'] ?? 'No hay reseñas disponibles.'); ?></p>
            <?php endif; ?>
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
        </div>
        <?php
        return ob_get_clean();
    }
}

<<<<<<< HEAD
new Custom_Trustpilot_Reviews_Module();
=======
new Ctr_Trustpilot_Module();
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
