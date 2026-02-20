<?php
if (!defined('ABSPATH')) exit;

class Ctr_Trustpilot_Module extends ET_Builder_Module {
    public $slug       = 'ctr_trustpilot_module';
    public $vb_support = 'on';

    function init() {
        $this->name = esc_html__('Trustpilot Reviews', 'custom-trustpilot-reviews');
        $this->icon = 'j';
        $this->main_css_element = '%%order_class%%';
    }

    public function get_fields() {
        return array(
            'title' => array(
                'label'           => esc_html__('Título', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'default'         => esc_html__('Valoraciones de Trustpilot', 'custom-trustpilot-reviews'),
                'option_category' => 'basic_option',
                'description'     => esc_html__('Título que se mostrará junto a las reseñas.', 'custom-trustpilot-reviews'),
            ),
            'reviews_count' => array(
                'label'           => esc_html__('Número de reseñas', 'custom-trustpilot-reviews'),
                'type'            => 'number',
                'default'         => 5,
                'option_category' => 'basic_option',
                'description'     => esc_html__('Número de reseñas a mostrar (máximo 50).', 'custom-trustpilot-reviews'),
                'min'             => 1,
                'max'             => 50,
            ),
            'layout' => array(
                'label'           => esc_html__('Diseño', 'custom-trustpilot-reviews'),
                'type'            => 'select',
                'default'         => 'grid',
                'option_category' => 'layout',
                'options'         => array(
                    'grid'     => esc_html__('Grid', 'custom-trustpilot-reviews'),
                    'list'     => esc_html__('Lista', 'custom-trustpilot-reviews'),
                    'carousel' => esc_html__('Carrusel', 'custom-trustpilot-reviews'),
                    'masonry'  => esc_html__('Masonry', 'custom-trustpilot-reviews'),
                    'timeline' => esc_html__('Timeline', 'custom-trustpilot-reviews'),
                ),
                'description'     => esc_html__('Selecciona el diseño de las reseñas.', 'custom-trustpilot-reviews'),
            ),
            'columns' => array(
                'label'           => esc_html__('Columnas', 'custom-trustpilot-reviews'),
                'type'            => 'number',
                'default'         => 1,
                'option_category' => 'layout',
                'description'     => esc_html__('Número de columnas para el diseño grid (1-4).', 'custom-trustpilot-reviews'),
                'min'             => 1,
                'max'             => 4,
                'show_if'         => array('layout' => array('grid', 'masonry')),
            ),
            'card_style' => array(
                'label'           => esc_html__('Estilo de tarjeta', 'custom-trustpilot-reviews'),
                'type'            => 'select',
                'default'         => 'modern',
                'option_category' => 'layout',
                'options'         => array(
                    'modern'  => esc_html__('Moderno', 'custom-trustpilot-reviews'),
                    'classic' => esc_html__('Clásico', 'custom-trustpilot-reviews'),
                    'minimal' => esc_html__('Minimalista', 'custom-trustpilot-reviews'),
                    'elegant' => esc_html__('Elegante', 'custom-trustpilot-reviews'),
                    'bold'    => esc_html__('Audaz', 'custom-trustpilot-reviews'),
                ),
                'description'     => esc_html__('Selecciona el estilo visual de las tarjetas.', 'custom-trustpilot-reviews'),
            ),
            'color_scheme' => array(
                'label'           => esc_html__('Esquema de colores', 'custom-trustpilot-reviews'),
                'type'            => 'select',
                'default'         => 'default',
                'option_category' => 'layout',
                'options'         => array(
                    'default' => esc_html__('Por defecto', 'custom-trustpilot-reviews'),
                    'blue'    => esc_html__('Azul', 'custom-trustpilot-reviews'),
                    'green'   => esc_html__('Verde', 'custom-trustpilot-reviews'),
                    'purple'  => esc_html__('Púrpura', 'custom-trustpilot-reviews'),
                    'orange'  => esc_html__('Naranja', 'custom-trustpilot-reviews'),
                    'dark'    => esc_html__('Oscuro', 'custom-trustpilot-reviews'),
                ),
                'description'     => esc_html__('Selecciona el esquema de colores.', 'custom-trustpilot-reviews'),
            ),
            'show_stars' => array(
                'label'           => esc_html__('Mostrar estrellas', 'custom-trustpilot-reviews'),
                'type'            => 'yes_no_button',
                'default'         => 'on',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Mostrar las estrellas de valoración.', 'custom-trustpilot-reviews'),
            ),
            'show_dates' => array(
                'label'           => esc_html__('Mostrar fechas', 'custom-trustpilot-reviews'),
                'type'            => 'yes_no_button',
                'default'         => 'on',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Mostrar la fecha de la reseña.', 'custom-trustpilot-reviews'),
            ),
            'clickable_reviews' => array(
                'label'           => esc_html__('Clickeable', 'custom-trustpilot-reviews'),
                'type'            => 'yes_no_button',
                'default'         => 'on',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Hacer que la reseña lleve a Trustpilot al hacer clic.', 'custom-trustpilot-reviews'),
            ),
            'show_button' => array(
                'label'           => esc_html__('Mostrar botón principal', 'custom-trustpilot-reviews'),
                'type'            => 'yes_no_button',
                'default'         => 'on',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Mostrar el botón superior para valorar en Trustpilot.', 'custom-trustpilot-reviews'),
            ),
            'button_text' => array(
                'label'           => esc_html__('Texto del botón', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'default'         => esc_html__('¡Valora en Trustpilot!', 'custom-trustpilot-reviews'),
                'option_category' => 'basic_option',
                'show_if'         => array('show_button' => 'on'),
            ),
            'button_url' => array(
                'label'           => esc_html__('URL del botón', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'default'         => '',
                'option_category' => 'basic_option',
                'show_if'         => array('show_button' => 'on'),
            ),
            'admin_label' => array(
                'label'       => esc_html__('Admin Label', 'custom-trustpilot-reviews'),
                'type'        => 'text',
                'description' => esc_html__('This will change the label of the module in the builder for easy identification.', 'custom-trustpilot-reviews'),
            ),
        );
    }

    public function render($attrs, $content = null, $render_slug) {
        $title         = $this->props['title'];
        $reviews_count = absint($this->props['reviews_count']);
        $layout        = $this->props['layout'];
        $columns       = absint($this->props['columns']);
        $card_style    = $this->props['card_style'];
        $color_scheme  = $this->props['color_scheme'];
        $show_stars    = $this->props['show_stars'] === 'on';
        $show_dates    = $this->props['show_dates'] === 'on';
        $clickable     = $this->props['clickable_reviews'] === 'on';
        $show_button   = $this->props['show_button'];
        $button_text   = $this->props['button_text'];
        $button_url    = $this->props['button_url'];
        
        // Validate inputs
        if ($reviews_count < 1 || $reviews_count > 50) {
            $reviews_count = 5;
        }
        if (!in_array($layout, ['grid', 'list', 'carousel', 'masonry', 'timeline'])) {
            $layout = 'grid';
        }
        if ($columns < 1 || $columns > 4) {
            $columns = 1;
        }
        
        // Get reviews using the plugin's function
        $reviews = ctr_get_trustpilot_reviews();
        
        // Generate unique ID for this instance
        $module_id = 'ctr-carousel-' . uniqid();
        
        // Start output buffering
        ob_start();
        ?>
        <div class="ctr-carousel ctr-divi-module ctr-layout-<?php echo esc_attr($layout); ?> ctr-style-<?php echo esc_attr($card_style); ?> ctr-colors-<?php echo esc_attr($color_scheme); ?>">
            <?php if (!empty($title)): ?>
                <h2 class="ctr-title">
                    <?php echo esc_html($title); ?>
                    <img src="<?php echo esc_url(CTR_PLUGIN_URL . 'assets/img/trustpilotlogo.png'); ?>" alt="Trustpilot">
                </h2>
            <?php endif; ?>
            
            <?php if ($show_button === 'on'): ?>
                <div class="ctr-button-container">
                    <a href="<?php echo esc_url($button_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="ctr-button">
                        <?php echo esc_html($button_text); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($reviews) && !isset($reviews['error'])): ?>
                <?php 
                $reviews = array_slice($reviews, 0, $reviews_count);
                ?>
                
                <div class="ctr-reviews ctr-<?php echo esc_attr($layout); ?>">
                    <?php if ($layout === 'grid'): ?>
                        <div class="ctr-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
                            <?php foreach ($reviews as $index => $review): ?>
                                <div class="ctr-review-card <?php echo $clickable ? 'ctr-clickable' : ''; ?>" 
                                     <?php if ($clickable && !empty($review['review_url'])) echo 'onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"'; ?>>
                                    <?php echo ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php elseif ($layout === 'list'): ?>
                        <div class="ctr-list">
                            <?php foreach ($reviews as $index => $review): ?>
                                <div class="ctr-review-item <?php echo $clickable ? 'ctr-clickable' : ''; ?>"
                                     <?php if ($clickable && !empty($review['review_url'])) echo 'onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"'; ?>>
                                    <?php echo ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php elseif ($layout === 'masonry'): ?>
                        <div class="ctr-masonry" style="columns: <?php echo esc_attr($columns); ?>;">
                            <?php foreach ($reviews as $index => $review): ?>
                                <div class="ctr-masonry-item">
                                    <div class="ctr-review-card <?php echo $clickable ? 'ctr-clickable' : ''; ?>"
                                         <?php if ($clickable && !empty($review['review_url'])) echo 'onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"'; ?>>
                                        <?php echo ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                    </div>
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
                                        <div class="ctr-review-card <?php echo $clickable ? 'ctr-clickable' : ''; ?>"
                                             <?php if ($clickable && !empty($review['review_url'])) echo 'onclick="window.open(\'' . esc_url($review['review_url']) . '\', \'_blank\')"'; ?>>
                                            <?php echo ctr_render_review_content($review, $show_stars, $show_dates, $clickable, $card_style, $index); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: // carousel layout ?>
                        <div class="ctr-carousel-container" id="<?php echo esc_attr($module_id); ?>">
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
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <p class="ctr-error">
                    <?php echo esc_html($reviews['error'] ?? __('No hay reseñas disponibles en este momento.', 'custom-trustpilot-reviews')); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($layout === 'carousel' && count($reviews) > 1): ?>
            <script>
            (function($) {
                var containerId = '<?php echo esc_js($module_id); ?>';
                var $container = $('#' + containerId);
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
            })(jQuery);
            </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize the module
new Ctr_Trustpilot_Module();
