<?php
/*
Plugin Name: Custom Trustpilot Reviews
Description: Muestra las valoraciones de Trustpilot en WordPress y Divi con un diseño personalizable.
Version: 1.7
Author: Nelson Ariel Gil Olguin
Text Domain: custom-trustpilot-reviews
Requires at least: 5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Update URI: https://api.github.com/repos/nelsongil/trustpilot-reviews/releases/latest
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('CTR_PLUGIN_VERSION', '1.7');
define('CTR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CTR_PLUGIN_SLUG', 'custom-trustpilot-reviews');
define('CTR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Incluir archivos necesarios
require_once CTR_PLUGIN_PATH . 'includes/admin-options.php';
require_once CTR_PLUGIN_PATH . 'includes/api.php';
require_once CTR_PLUGIN_PATH . 'includes/shortcode.php';
require_once CTR_PLUGIN_PATH . 'includes/updater.php';

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
        update_option('ctr_button_url', 'https://es.trustpilot.com/evaluate/nelsongil.com');
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
    wp_clear_scheduled_hook('ctr_check_for_updates');
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ctr_add_settings_link');

function ctr_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ctr-settings') . '">' . __('Configuración', 'custom-trustpilot-reviews') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Initialize the auto-updater
add_action('init', 'ctr_init_auto_updater');

function ctr_init_auto_updater() {
    // Only initialize if user has permissions
    if (current_user_can('update_plugins')) {
        new CTR_Plugin_Updater();
    }
}

// Add update notification in admin
add_action('admin_notices', 'ctr_admin_update_notice');

function ctr_admin_update_notice() {
    if (current_user_can('update_plugins')) {
        $update_info = get_transient('ctr_update_available');
        if ($update_info && !empty($update_info['version'])) {
            $current_version = CTR_PLUGIN_VERSION;
            $new_version = $update_info['version'];
            
            if (version_compare($new_version, $current_version, '>')) {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>Trustpilot Reviews:</strong> ';
                printf(
                    __('Hay una nueva versión disponible (%s). <a href="%s">Actualizar ahora</a>', 'custom-trustpilot-reviews'),
                    esc_html($new_version),
                    esc_url(admin_url('plugins.php?action=upgrade-plugin&plugin=' . CTR_PLUGIN_BASENAME))
                );
                echo '</p>';
                echo '</div>';
            }
        }
    }
}

// Schedule update checks
add_action('wp_scheduled_delete', 'ctr_schedule_update_checks');

function ctr_schedule_update_checks() {
    if (!wp_next_scheduled('ctr_check_for_updates')) {
        wp_schedule_event(time(), 'daily', 'ctr_check_for_updates');
    }
}

// Clear scheduled events on deactivation
register_deactivation_hook(__FILE__, 'ctr_clear_scheduled_events');

function ctr_clear_scheduled_events() {
    wp_clear_scheduled_hook('ctr_check_for_updates');
}
