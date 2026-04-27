<?php
/*
Plugin Name: Custom Trustpilot Reviews
Description: Muestra las valoraciones de Trustpilot en WordPress y Divi con un diseño personalizable.
Version: 2.1
Author: Nelson Ariel Gil Olguin
Text Domain: custom-trustpilot-reviews
Requires at least: 5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('CTR_PLUGIN_VERSION', '2.1');
define('CTR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CTR_PLUGIN_SLUG', 'custom-trustpilot-reviews');
define('CTR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Incluir archivos necesarios
require_once CTR_PLUGIN_PATH . 'includes/admin-options.php';
require_once CTR_PLUGIN_PATH . 'includes/api.php';
require_once CTR_PLUGIN_PATH . 'includes/shortcode.php';
require_once CTR_PLUGIN_PATH . 'includes/updater.php';

// Inicializar el updater (solo en admin)
if (is_admin()) {
    new CTR_Plugin_Updater();
}

// Registrar el menú del administrador
add_action('admin_menu', 'ctr_add_admin_menu');

function ctr_add_admin_menu() {
    add_menu_page(
        'Trustpilot Reviews',
        'Trustpilot Reviews',
        'manage_options',
        'ctr-settings',
        'ctr_settings_page',
        CTR_PLUGIN_URL . 'assets/img/icono.png',
        60
    );
}

// Cargar CSS y JS del front
function ctr_enqueue_assets() {
    wp_enqueue_style(
        'ctr-styles',
        CTR_PLUGIN_URL . 'assets/css/styles.css',
        array(),
        CTR_PLUGIN_VERSION
    );

    // El carrusel ya no depende de jQuery: vanilla JS al pie de la página
    wp_register_script(
        'ctr-carousel',
        CTR_PLUGIN_URL . 'assets/js/ctr-carousel.js',
        array(),
        CTR_PLUGIN_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'ctr_enqueue_assets');

// Registro del módulo de Divi
function ctr_register_divi_module() {
    if (class_exists('ET_Builder_Module')) {
        require_once CTR_PLUGIN_PATH . 'includes/divi-module.php';
    }
}
add_action('et_builder_ready', 'ctr_register_divi_module');

// Activation hook
register_activation_hook(__FILE__, 'ctr_activate_plugin');

function ctr_activate_plugin() {
    // Set default options (solo si aún no existen)
    if (false === get_option('ctr_api_url', false))             update_option('ctr_api_url', '');
    if (false === get_option('ctr_reviews_count', false))       update_option('ctr_reviews_count', 5);
    if (false === get_option('ctr_reviews_title', false))       update_option('ctr_reviews_title', 'Valoraciones de Trustpilot');
    if (false === get_option('ctr_cache_duration', false))      update_option('ctr_cache_duration', 3600);
    if (false === get_option('ctr_enable_cache', false))        update_option('ctr_enable_cache', 1);

    // Layout & display
    if (false === get_option('ctr_default_layout', false))      update_option('ctr_default_layout', 'grid');
    if (false === get_option('ctr_default_columns', false))     update_option('ctr_default_columns', 1);
    if (false === get_option('ctr_show_stars', false))          update_option('ctr_show_stars', 1);
    if (false === get_option('ctr_show_dates', false))          update_option('ctr_show_dates', 1);
    if (false === get_option('ctr_clickable_reviews', false))   update_option('ctr_clickable_reviews', 1);
    if (false === get_option('ctr_show_review_button', false))  update_option('ctr_show_review_button', 1);
    if (false === get_option('ctr_button_text', false))         update_option('ctr_button_text', '¡Valora en Trustpilot!');
    if (false === get_option('ctr_button_url', false))          update_option('ctr_button_url', '');

    // Style
    if (false === get_option('ctr_card_style', false))          update_option('ctr_card_style', 'modern');
    if (false === get_option('ctr_color_scheme', false))        update_option('ctr_color_scheme', 'default');
    if (false === get_option('ctr_enable_animations', false))   update_option('ctr_enable_animations', 1);
    if (false === get_option('ctr_enable_hover_effects', false)) update_option('ctr_enable_hover_effects', 1);

    // Updates
    if (false === get_option('ctr_auto_update_enabled', false)) update_option('ctr_auto_update_enabled', 1);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ctr_deactivate_plugin');

function ctr_deactivate_plugin() {
    // Limpia cron del updater (este sí estaba programado)
    wp_clear_scheduled_hook('ctr_check_for_updates');
    // Limpia cron heredado por si quedó de versiones anteriores
    wp_clear_scheduled_hook('ctr_clear_cache');

    // Limpia transientes
    delete_transient('ctr_reviews_cache');
    delete_transient('ctr_last_request_time');
    delete_transient('ctr_latest_version_info');
    delete_transient('ctr_last_update_check');
    delete_transient('ctr_update_available');
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ctr_add_settings_link');

function ctr_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ctr-settings') . '">' . __('Configuración', 'custom-trustpilot-reviews') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
