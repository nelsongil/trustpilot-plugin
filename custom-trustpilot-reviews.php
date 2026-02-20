<?php
/*
Plugin Name: Custom Trustpilot Reviews
Description: Muestra las valoraciones de Trustpilot en WordPress y Divi con un diseño personalizable.
Version: 2.0
Author: Nelson Ariel Gil Olguin
Text Domain: custom-trustpilot-reviews
Requires at least: 5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('CTR_PLUGIN_VERSION', '2.0');
define('CTR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CTR_PLUGIN_SLUG', 'custom-trustpilot-reviews');
define('CTR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Incluir archivos necesarios
require_once CTR_PLUGIN_PATH . 'includes/admin-options.php';
require_once CTR_PLUGIN_PATH . 'includes/api.php';
require_once CTR_PLUGIN_PATH . 'includes/shortcode.php';
require_once CTR_PLUGIN_PATH . 'includes/updater.php';

// Inicializar el updater
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

// Cargar estilos
function ctr_enqueue_assets() {
    wp_enqueue_style('ctr-styles', CTR_PLUGIN_URL . 'assets/css/styles.css', array(), CTR_PLUGIN_VERSION);
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
    // Set default options
    if (!get_option('ctr_api_url')) {
        update_option('ctr_api_url', '');
    }
    if (!get_option('ctr_reviews_count')) {
        update_option('ctr_reviews_count', 5);
    }
    if (!get_option('ctr_reviews_title')) {
        update_option('ctr_reviews_title', 'Valoraciones de Trustpilot');
    }
    if (!get_option('ctr_cache_duration')) {
        update_option('ctr_cache_duration', 3600); // 1 hour
    }
    
    // New layout and display options
    if (!get_option('ctr_default_layout')) {
        update_option('ctr_default_layout', 'grid');
    }
    if (!get_option('ctr_default_columns')) {
        update_option('ctr_default_columns', 1);
    }
    if (!get_option('ctr_show_stars')) {
        update_option('ctr_show_stars', 1);
    }
    if (!get_option('ctr_show_dates')) {
        update_option('ctr_show_dates', 1);
    }
    if (!get_option('ctr_clickable_reviews')) {
        update_option('ctr_clickable_reviews', 1);
    }
    if (!get_option('ctr_show_review_button')) {
        update_option('ctr_show_review_button', 1);
    }
    if (!get_option('ctr_button_text')) {
        update_option('ctr_button_text', '¡Valora en Trustpilot!');
    }
    if (!get_option('ctr_button_url')) {
        update_option('ctr_button_url', '');
    }
    
    // Style options
    if (!get_option('ctr_card_style')) {
        update_option('ctr_card_style', 'modern');
    }
    if (!get_option('ctr_color_scheme')) {
        update_option('ctr_color_scheme', 'default');
    }
    if (!get_option('ctr_enable_animations')) {
        update_option('ctr_enable_animations', 1);
    }
    if (!get_option('ctr_enable_hover_effects')) {
        update_option('ctr_enable_hover_effects', 1);
    }
    
    // Update system options
    if (!get_option('ctr_auto_update_enabled')) {
        update_option('ctr_auto_update_enabled', 1);
    }
    if (!get_option('ctr_update_channel')) {
        update_option('ctr_update_channel', 'stable');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ctr_deactivate_plugin');

function ctr_deactivate_plugin() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('ctr_clear_cache');
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ctr_add_settings_link');

function ctr_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ctr-settings') . '">' . __('Configuración', 'custom-trustpilot-reviews') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
