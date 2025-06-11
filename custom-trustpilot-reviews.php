<?php
/**
 * Plugin Name: Custom Trustpilot Reviews
 * Plugin URI: https://github.com/nelsongil/trustpilot-plugin
 * Description: Muestra las valoraciones de Trustpilot en WordPress y Divi con un diseño personalizable.
 * Version: 2.0
 * Requires at least: 5.6
 * Requires PHP: 8.0
 * Author: Nelson Gil
 * Author URI: https://github.com/nelsongil
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-trustpilot-reviews
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CTR_VERSION', '1.5');
define('CTR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar archivos necesarios
require_once CTR_PLUGIN_DIR . 'includes/api.php';
require_once CTR_PLUGIN_DIR . 'includes/shortcode.php';
require_once CTR_PLUGIN_DIR . 'includes/divi-module.php';
require_once CTR_PLUGIN_DIR . 'includes/admin-options.php';
require_once CTR_PLUGIN_DIR . 'includes/github-updater.php';

// Inicializar el actualizador de GitHub
if ( is_admin() ) {
    new CTR_GitHub_Updater( __FILE__, 'nelsongil', 'trustpilot-plugin' );
}

// Registrar activación y desactivación
register_activation_hook(__FILE__, 'ctr_activate');
register_deactivation_hook(__FILE__, 'ctr_deactivate');

// Encolar scripts y estilos
function ctr_enqueue_scripts() {
    wp_enqueue_style('ctr-styles', CTR_PLUGIN_URL . 'assets/css/ctr-styles.css', array(), CTR_VERSION);
    wp_enqueue_script('ctr-carousel', CTR_PLUGIN_URL . 'assets/js/ctr-carousel.js', array('jquery'), CTR_VERSION, true);
}
add_action('wp_enqueue_scripts', 'ctr_enqueue_scripts');

function ctr_activate() {
    // Establecer valores por defecto
    add_option('ctr_api_url', '');
    add_option('ctr_reviews_count', 5);
    add_option('ctr_show_rating', true);
    add_option('ctr_show_date', true);
    add_option('ctr_show_title', true);
    add_option('ctr_show_author', true);
    add_option('ctr_show_buttons', true);
    add_option('ctr_layout', 'list');
    add_option('ctr_max_width', '800px');
    add_option('ctr_spacing', '20px');
    add_option('ctr_star_color', '#00b67a');
    add_option('ctr_background_color', '#ffffff');
    add_option('ctr_text_color', '#333333');
    add_option('ctr_button_color', '#00b67a');
    add_option('ctr_button_text_color', '#ffffff');
    add_option('ctr_cache_duration', 3600);

    // Verificar requisitos mínimos
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere PHP 8.0 o superior.');
    }

    // Crear opciones por defecto
    add_option('ctr_api_url', '');
    add_option('ctr_reviews_count', 5);
    add_option('ctr_cache_duration', 3600); // 1 hora por defecto
    add_option('ctr_show_rating', true);
    add_option('ctr_show_date', true);

    // Activar el custom post type de presets
    ctr_register_preset_post_type();
    flush_rewrite_rules();
}

function ctr_deactivate() {
    // Limpiar caché al desactivar
    delete_transient('ctr_reviews_cache');
    // Desactivar el custom post type de presets
    flush_rewrite_rules();
}

// Register Custom Post Type for Trustpilot Presets
function ctr_register_preset_post_type() {
    $labels = array(
        'name'               => _x( 'Trustpilot Presets', 'Post Type General Name', 'custom-trustpilot-reviews' ),
        'singular_name'      => _x( 'Trustpilot Preset', 'Post Type Singular Name', 'custom-trustpilot-reviews' ),
        'menu_name'          => __( 'Trustpilot Presets', 'custom-trustpilot-reviews' ),
        'parent_item_colon'  => __( 'Parent Preset:', 'custom-trustpilot-reviews' ),
        'all_items'          => __( 'Todos los Presets', 'custom-trustpilot-reviews' ),
        'view_item'          => __( 'Ver Preset', 'custom-trustpilot-reviews' ),
        'add_new_item'       => __( 'Añadir Nuevo Preset', 'custom-trustpilot-reviews' ),
        'add_new'            => __( 'Añadir Nuevo', 'custom-trustpilot-reviews' ),
        'edit_item'          => __( 'Editar Preset', 'custom-trustpilot-reviews' ),
        'update_item'        => __( 'Actualizar Preset', 'custom-trustpilot-reviews' ),
        'search_items'       => __( 'Buscar Preset', 'custom-trustpilot-reviews' ),
        'not_found'          => __( 'No encontrado', 'custom-trustpilot-reviews' ),
        'not_found_in_trash' => __( 'No encontrado en la papelera', 'custom-trustpilot-reviews' ),
    );
    $args = array(
        'label'               => __( 'trustpilot_preset', 'custom-trustpilot-reviews' ),
        'description'         => __( 'Configuraciones de Trustpilot para shortcodes', 'custom-trustpilot-reviews' ),
        'labels'              => $labels,
        'supports'            => array( 'title' ), // Only title, custom fields for other settings
        'hierarchical'        => false,
        'public'              => false, // Not publicly viewable
        'show_ui'             => true, // Show in admin UI
        'show_in_menu'        => false, // Appears under our main menu
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'rewrite'             => false,
        'capability_type'     => 'post',
    );
    register_post_type( 'trustpilot_preset', $args );
}
add_action( 'init', 'ctr_register_preset_post_type' );

// Add meta box for Trustpilot Preset configuration
add_action( 'add_meta_boxes', 'ctr_add_preset_meta_box' );
function ctr_add_preset_meta_box() {
    add_meta_box(
        'ctr_preset_settings',
        __( 'Configuración del Preset', 'custom-trustpilot-reviews' ),
        'ctr_display_preset_meta_box',
        'trustpilot_preset',
        'normal',
        'high'
    );
}

// Display the meta box content
function ctr_display_preset_meta_box( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'ctr_preset_nonce' );

    $api_url = get_post_meta( $post->ID, '_ctr_api_url', true );
    $reviews_count = get_post_meta( $post->ID, '_ctr_reviews_count', true );
    $cache_duration = get_post_meta( $post->ID, '_ctr_cache_duration', true );
    $show_rating = get_post_meta( $post->ID, '_ctr_show_rating', true );
    $show_date = get_post_meta( $post->ID, '_ctr_show_date', true );
    $show_title = get_post_meta( $post->ID, '_ctr_show_title', true );
    $show_author = get_post_meta( $post->ID, '_ctr_show_author', true );
    $show_buttons = get_post_meta( $post->ID, '_ctr_show_buttons', true );
    $layout = get_post_meta( $post->ID, '_ctr_layout', true );
    $max_width = get_post_meta( $post->ID, '_ctr_max_width', true );
    $spacing = get_post_meta( $post->ID, '_ctr_spacing', true );
    $star_color = get_post_meta( $post->ID, '_ctr_star_color', true );
    $background_color = get_post_meta( $post->ID, '_ctr_background_color', true );
    $text_color = get_post_meta( $post->ID, '_ctr_text_color', true );
    $button_color = get_post_meta( $post->ID, '_ctr_button_color', true );
    $button_text_color = get_post_meta( $post->ID, '_ctr_button_text_color', true );
    $autoplay_interval = get_post_meta( $post->ID, '_ctr_autoplay_interval', true );

    // Valores por defecto si no están definidos
    if (empty($layout)) $layout = 'list';
    if (empty($max_width)) $max_width = '800px';
    if (empty($spacing)) $spacing = '20px';
    if (empty($star_color)) $star_color = '#00b67a';
    if (empty($background_color)) $background_color = '#ffffff';
    if (empty($text_color)) $text_color = '#333333';
    if (empty($button_color)) $button_color = '#00b67a';
    if (empty($button_text_color)) $button_text_color = '#ffffff';
    if (empty($autoplay_interval)) $autoplay_interval = 3000;

    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ctr_api_url"><?php _e('URL de Trustpilot:', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <input type="url" name="ctr_api_url" id="ctr_api_url" value="<?php echo esc_url($api_url); ?>" class="regular-text" required>
                <p class="description"><?php _e('Ingresa la URL de tu página de reseñas en Trustpilot para este preset.', 'custom-trustpilot-reviews'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ctr_reviews_count"><?php _e('Número de reseñas:', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <input type="number" name="ctr_reviews_count" id="ctr_reviews_count" value="<?php echo intval($reviews_count); ?>" min="1" max="20" class="small-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ctr_cache_duration"><?php _e('Duración del caché (segundos):', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <input type="number" name="ctr_cache_duration" id="ctr_cache_duration" value="<?php echo intval($cache_duration); ?>" min="300" class="small-text">
                <p class="description"><?php _e('Mínimo 300 segundos (5 minutos) para este preset.', 'custom-trustpilot-reviews'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Opciones de visualización:', 'custom-trustpilot-reviews'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="ctr_show_rating" <?php checked($show_rating, 'on'); ?>>
                    <?php _e('Mostrar calificación', 'custom-trustpilot-reviews'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="ctr_show_date" <?php checked($show_date, 'on'); ?>>
                    <?php _e('Mostrar fecha', 'custom-trustpilot-reviews'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="ctr_show_title" <?php checked($show_title, 'on'); ?>>
                    <?php _e('Mostrar título', 'custom-trustpilot-reviews'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="ctr_show_author" <?php checked($show_author, 'on'); ?>>
                    <?php _e('Mostrar autor', 'custom-trustpilot-reviews'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="ctr_show_buttons" <?php checked($show_buttons, 'on'); ?>>
                    <?php _e('Mostrar botones', 'custom-trustpilot-reviews'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ctr_layout"><?php _e('Diseño:', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <select name="ctr_layout" id="ctr_layout">
                    <option value="list" <?php selected($layout, 'list'); ?>><?php _e('Lista', 'custom-trustpilot-reviews'); ?></option>
                    <option value="grid" <?php selected($layout, 'grid'); ?>><?php _e('Cuadrícula', 'custom-trustpilot-reviews'); ?></option>
                    <option value="carousel" <?php selected($layout, 'carousel'); ?>><?php _e('Carrusel', 'custom-trustpilot-reviews'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ctr_max_width"><?php _e('Ancho Máximo:', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <input type="text" name="ctr_max_width" id="ctr_max_width" value="<?php echo esc_attr($max_width); ?>" class="regular-text">
                <p class="description"><?php _e('Ejemplo: 800px, 100%, etc.', 'custom-trustpilot-reviews'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ctr_spacing"><?php _e('Espaciado:', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <input type="text" name="ctr_spacing" id="ctr_spacing" value="<?php echo esc_attr($spacing); ?>" class="regular-text">
                <p class="description"><?php _e('Ejemplo: 20px, 1em, etc.', 'custom-trustpilot-reviews'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Colores:', 'custom-trustpilot-reviews'); ?></th>
            <td>
                <label for="ctr_star_color"><?php _e('Estrellas:', 'custom-trustpilot-reviews'); ?></label>
                <input type="color" name="ctr_star_color" id="ctr_star_color" value="<?php echo esc_attr($star_color); ?>"><br>

                <label for="ctr_background_color"><?php _e('Fondo:', 'custom-trustpilot-reviews'); ?></label>
                <input type="color" name="ctr_background_color" id="ctr_background_color" value="<?php echo esc_attr($background_color); ?>"><br>

                <label for="ctr_text_color"><?php _e('Texto:', 'custom-trustpilot-reviews'); ?></label>
                <input type="color" name="ctr_text_color" id="ctr_text_color" value="<?php echo esc_attr($text_color); ?>"><br>

                <label for="ctr_button_color"><?php _e('Botones:', 'custom-trustpilot-reviews'); ?></label>
                <input type="color" name="ctr_button_color" id="ctr_button_color" value="<?php echo esc_attr($button_color); ?>"><br>

                <label for="ctr_button_text_color"><?php _e('Texto de Botones:', 'custom-trustpilot-reviews'); ?></label>
                <input type="color" name="ctr_button_text_color" id="ctr_button_text_color" value="<?php echo esc_attr($button_text_color); ?>"><br>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ctr_autoplay_interval"><?php _e('Intervalo de Desplazamiento del Carrusel (ms):', 'custom-trustpilot-reviews'); ?></label></th>
            <td>
                <input type="number" name="ctr_autoplay_interval" id="ctr_autoplay_interval" value="<?php echo intval($autoplay_interval); ?>" min="0" class="small-text">
                <p class="description"><?php _e('Establece el tiempo entre transiciones automáticas en milisegundos (0 para deshabilitar). Por defecto: 3000ms (3 segundos).', 'custom-trustpilot-reviews'); ?></p>
            </td>
        </tr>
    </table>
    <?php

    // Display the shortcode for this preset
    $preset_slug = get_post_field('post_name', $post->ID);
    if ( ! empty( $preset_slug ) ) {
        echo '<h3>' . __('Shortcode para este Preset:', 'custom-trustpilot-reviews') . '</h3>';
        echo '<p>' . __('Copia y pega el siguiente shortcode donde quieras mostrar las reseñas de este preset:', 'custom-trustpilot-reviews') . '</p>';
        echo '<code>[custom_trustpilot_reviews config="' . esc_attr( $preset_slug ) . '"]</code>';
    }
}

// Save the meta box data
add_action( 'save_post', 'ctr_save_preset_meta_box_data' );
function ctr_save_preset_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['ctr_preset_nonce'] ) || ! wp_verify_nonce( $_POST['ctr_preset_nonce'], basename( __FILE__ ) ) ) {
        return $post_id;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    // Save API URL
    if ( isset( $_POST['ctr_api_url'] ) ) {
        update_post_meta( $post_id, '_ctr_api_url', sanitize_url( $_POST['ctr_api_url'] ) );
    }

    // Save Reviews Count
    if ( isset( $_POST['ctr_reviews_count'] ) ) {
        update_post_meta( $post_id, '_ctr_reviews_count', intval( $_POST['ctr_reviews_count'] ) );
    }

    // Save Cache Duration
    if ( isset( $_POST['ctr_cache_duration'] ) ) {
        update_post_meta( $post_id, '_ctr_cache_duration', intval( $_POST['ctr_cache_duration'] ) );
    }

    // Save show_rating
    $show_rating = isset( $_POST['ctr_show_rating'] ) ? 'on' : 'off';
    update_post_meta( $post_id, '_ctr_show_rating', $show_rating );

    // Save show_date
    $show_date = isset( $_POST['ctr_show_date'] ) ? 'on' : 'off';
    update_post_meta( $post_id, '_ctr_show_date', $show_date );

    // Save show_title
    $show_title = isset( $_POST['ctr_show_title'] ) ? 'on' : 'off';
    update_post_meta( $post_id, '_ctr_show_title', $show_title );

    // Save show_author
    $show_author = isset( $_POST['ctr_show_author'] ) ? 'on' : 'off';
    update_post_meta( $post_id, '_ctr_show_author', $show_author );

    // Save show_buttons
    $show_buttons = isset( $_POST['ctr_show_buttons'] ) ? 'on' : 'off';
    update_post_meta( $post_id, '_ctr_show_buttons', $show_buttons );

    // Save layout
    if ( isset( $_POST['ctr_layout'] ) ) {
        update_post_meta( $post_id, '_ctr_layout', sanitize_text_field( $_POST['ctr_layout'] ) );
    }

    // Save max_width
    if ( isset( $_POST['ctr_max_width'] ) ) {
        update_post_meta( $post_id, '_ctr_max_width', sanitize_text_field( $_POST['ctr_max_width'] ) );
    }

    // Save spacing
    if ( isset( $_POST['ctr_spacing'] ) ) {
        update_post_meta( $post_id, '_ctr_spacing', sanitize_text_field( $_POST['ctr_spacing'] ) );
    }

    // Save star_color
    if ( isset( $_POST['ctr_star_color'] ) ) {
        update_post_meta( $post_id, '_ctr_star_color', sanitize_hex_color( $_POST['ctr_star_color'] ) );
    }

    // Save background_color
    if ( isset( $_POST['ctr_background_color'] ) ) {
        update_post_meta( $post_id, '_ctr_background_color', sanitize_hex_color( $_POST['ctr_background_color'] ) );
    }

    // Save text_color
    if ( isset( $_POST['ctr_text_color'] ) ) {
        update_post_meta( $post_id, '_ctr_text_color', sanitize_hex_color( $_POST['ctr_text_color'] ) );
    }

    // Save button_color
    if ( isset( $_POST['ctr_button_color'] ) ) {
        update_post_meta( $post_id, '_ctr_button_color', sanitize_hex_color( $_POST['ctr_button_color'] ) );
    }

    // Save button_text_color
    if ( isset( $_POST['ctr_button_text_color'] ) ) {
        update_post_meta( $post_id, '_ctr_button_text_color', sanitize_hex_color( $_POST['ctr_button_text_color'] ) );
    }

    // Save autoplay_interval
    if ( array_key_exists( 'ctr_autoplay_interval', $_POST ) ) {
        update_post_meta( $post_id, '_ctr_autoplay_interval', intval( $_POST['ctr_autoplay_interval'] ) );
    }
}

// Desinstalar plugin
function ctr_uninstall() {
    // Eliminar opciones
    delete_option('ctr_api_url');
    delete_option('ctr_reviews_count');
    delete_option('ctr_show_rating');
    delete_option('ctr_show_date');
    delete_option('ctr_show_title');
    delete_option('ctr_show_author');
    delete_option('ctr_show_buttons');
    delete_option('ctr_layout');
    delete_option('ctr_max_width');
    delete_option('ctr_spacing');
    delete_option('ctr_star_color');
    delete_option('ctr_background_color');
    delete_option('ctr_text_color');
    delete_option('ctr_button_color');
    delete_option('ctr_button_text_color');
    delete_option('ctr_cache_duration');
    
    // Limpiar caché
    delete_transient('ctr_reviews_cache');
}
register_uninstall_hook(__FILE__, 'ctr_uninstall');

// Registrar el módulo de Divi
function ctr_register_divi_module() {
    if (class_exists('ET_Builder_Module')) {
        include_once plugin_dir_path(__FILE__) . 'includes/divi-module.php';
    }
}
add_action('et_builder_ready', 'ctr_register_divi_module');
