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
                'show_if'         => array('layout' => 'grid'),
            ),
            'show_button' => array(
                'label'           => esc_html__('Mostrar botón', 'custom-trustpilot-reviews'),
                'type'            => 'yes_no_button',
                'default'         => 'on',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Mostrar el botón para valorar en Trustpilot.', 'custom-trustpilot-reviews'),
            ),
            'button_text' => array(
                'label'           => esc_html__('Texto del botón', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'default'         => esc_html__('¡Valora en Trustpilot!', 'custom-trustpilot-reviews'),
                'option_category' => 'basic_option',
                'description'     => esc_html__('Texto que se mostrará en el botón.', 'custom-trustpilot-reviews'),
                'show_if'         => array('show_button' => 'on'),
            ),
            'button_url' => array(
                'label'           => esc_html__('URL del botón', 'custom-trustpilot-reviews'),
                'type'            => 'text',
                'default'         => 'https://es.trustpilot.com/evaluate/nelsongil.com',
                'option_category' => 'basic_option',
                'description'     => esc_html__('URL a la que llevará el botón.', 'custom-trustpilot-reviews'),
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
        $show_button   = $this->props['show_button'];
        $button_text   = $this->props['button_text'];
        $button_url    = $this->props['button_url'];
        
        // Validate inputs
        if ($reviews_count < 1 || $reviews_count > 50) {
            $reviews_count = 5;
        }
        if (!in_array($layout, ['grid', 'list', 'carousel'])) {
            $layout = 'grid';
        }
        if ($columns < 1 || $columns > 4) {
            $columns = 1;
        }
        
        // Get reviews using the plugin's function
        $reviews = ctr_get_trustpilot_reviews();
        
        // Start output buffering
        ob_start();
        ?>
        <div class="ctr-reviews ctr-divi-module ctr-layout-<?php echo esc_attr($layout); ?>">
            <?php if (!empty($title)): ?>
                <h2 class="ctr-title" style="display: flex; align-items: center; margin-bottom: 20px; text-align: center; justify-content: center;">
                    <?php echo esc_html($title); ?>
                    <img src="<?php echo esc_url(CTR_PLUGIN_URL . 'assets/img/trustpilotlogo.png'); ?>" 
                         alt="Trustpilot" style="margin-left: 10px; width: 24px; height: 24px;">
                </h2>
            <?php endif; ?>
            
            <?php if ($show_button === 'on'): ?>
                <div class="ctr-button-container" style="margin-bottom: 30px; text-align: center;">
                    <a href="<?php echo esc_url($button_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="ctr-button"
                       style="display: inline-block; padding: 12px 20px; background-color: #0073e6; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background-color 0.3s ease;">
                        <?php echo esc_html($button_text); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($reviews) && !isset($reviews['error'])): ?>
                <?php 
                // Limit reviews to specified count
                $reviews = array_slice($reviews, 0, $reviews_count);
                ?>
                
                <?php if ($layout === 'grid'): ?>
                    <div class="ctr-grid" style="display: grid; grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr); gap: 20px;">
                        <?php foreach ($reviews as $review): ?>
                            <div class="ctr-review-card" 
                                 style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #e1e5e9;">
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
                        <div class="ctr-review-item" 
                             style="background: #fff; padding: 20px; margin-bottom: 15px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #e1e5e9;">
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
                            <div class="ctr-review-slide" 
                                 data-slide="<?php echo esc_attr($index); ?>"
                                 style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: 1px solid #e1e5e9; margin: 0 auto; max-width: 600px; <?php echo $index === 0 ? '' : 'display: none;'; ?>">
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
                                    <?php esc_html_e('Anterior', 'custom-trustpilot-reviews'); ?>
                                </button>
                                <button class="ctr-next" style="background: #0073e6; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                    <?php esc_html_e('Siguiente', 'custom-trustpilot-reviews'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <p class="ctr-error" style="color: #d63638; text-align: center; padding: 20px; background: #fef7f1; border: 1px solid #d63638; border-radius: 5px;">
                    <?php echo esc_html($reviews['error'] ?? __('No hay reseñas disponibles en este momento.', 'custom-trustpilot-reviews')); ?>
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
}

// Initialize the module
new Ctr_Trustpilot_Module();
