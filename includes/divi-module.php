<?php
if (!defined('ABSPATH')) exit;

class Ctr_Trustpilot_Module extends ET_Builder_Module {
    public $slug       = 'ctr_trustpilot_module';
    public $vb_support = 'on';

    function init() {
        $this->name             = esc_html__('Trustpilot Reviews', 'custom-trustpilot-reviews');
        $this->icon             = 'j';
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

    /**
     * Render delega TODO en el shortcode central. Así no tenemos dos
     * implementaciones HTML divergentes y los cambios de UI van en un único
     * sitio.
     */
    public function render($attrs, $content = null, $render_slug = '') {
        // Mapeo Divi → atributos del shortcode
        $count       = absint($this->props['reviews_count']);
        if ($count < 1 || $count > 50) $count = 5;

        $columns     = absint($this->props['columns']);
        if ($columns < 1 || $columns > 4) $columns = 1;

        $layout      = $this->props['layout'];
        $allowed     = ['grid', 'list', 'carousel', 'masonry', 'timeline'];
        if (!in_array($layout, $allowed, true)) $layout = 'grid';

        $atts = array(
            'count'       => $count,
            'title'       => $this->props['title'],
            'show_button' => $this->props['show_button'] === 'on' ? 'true' : 'false',
            'button_text' => $this->props['button_text'],
            'button_url'  => $this->props['button_url'],
            'layout'      => $layout,
            'columns'     => $columns,
            'show_stars'  => $this->props['show_stars']        === 'on' ? 'true' : 'false',
            'show_dates'  => $this->props['show_dates']        === 'on' ? 'true' : 'false',
            'clickable'   => $this->props['clickable_reviews'] === 'on' ? 'true' : 'false',
            'style'       => $this->props['card_style'],
            'colors'      => $this->props['color_scheme'],
        );

        return ctr_render_reviews_carousel($atts);
    }
}

// Initialize the module
new Ctr_Trustpilot_Module();
