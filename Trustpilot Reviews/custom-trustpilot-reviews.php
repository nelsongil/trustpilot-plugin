<?php
/*
Plugin Name: Custom Trustpilot Reviews
Description: Muestra las valoraciones de Trustpilot en WordPress y Divi con un diseño personalizable.
Version: 1.8
Author: Nelson Ariel Gil Olguin
Text Domain: custom-trustpilot-reviews
Requires at least: 5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('CTR_PLUGIN_VERSION', '1.8');
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

// Cargar estilos con optimización de rendimiento
function ctr_enqueue_assets() {
    // Solo cargar en páginas que lo necesiten
    if (is_admin() || has_shortcode(get_the_content(), 'custom_trustpilot_reviews') || 
        is_active_widget(false, false, 'ctr_reviews_widget') || 
        ctr_is_divi_page()) {
        
        wp_enqueue_style('ctr-styles', CTR_PLUGIN_URL . 'assets/css/styles.css', array(), CTR_PLUGIN_VERSION);
        
        // Cargar JavaScript solo si es necesario
        if (has_shortcode(get_the_content(), 'custom_trustpilot_reviews')) {
            wp_enqueue_script('ctr-scripts', CTR_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), CTR_PLUGIN_VERSION, true);
        }
    }
}
add_action('wp_enqueue_scripts', 'ctr_enqueue_assets');

// Función helper para detectar páginas de Divi
function ctr_is_divi_page() {
    global $post;
    if ($post && has_shortcode($post->post_content, 'et_pb_trustpilot_reviews')) {
        return true;
    }
    return false;
}

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
    
    // Performance options
    if (!get_option('ctr_enable_lazy_loading')) {
        update_option('ctr_enable_lazy_loading', 1);
    }
    if (!get_option('ctr_enable_minification')) {
        update_option('ctr_enable_minification', 0);
    }
    if (!get_option('ctr_enable_cdn')) {
        update_option('ctr_enable_cdn', 0);
    }
    
    // Update system options
    if (!get_option('ctr_auto_update_enabled')) {
        update_option('ctr_auto_update_enabled', 1);
    }
    if (!get_option('ctr_update_channel')) {
        update_option('ctr_update_channel', 'stable');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ctr_deactivate_plugin');

function ctr_deactivate_plugin() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('ctr_clear_cache');
    wp_clear_scheduled_hook('ctr_daily_update_check');
    
    // Clear plugin caches
    ctr_clear_all_caches();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ctr_add_settings_link');

function ctr_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ctr-settings') . '">' . __('Configuración', 'custom-trustpilot-reviews') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Initialize the stable auto-updater
add_action('init', 'ctr_init_auto_updater');

function ctr_init_auto_updater() {
    // Only initialize if user has permissions and auto-updates are enabled
    if (current_user_can('update_plugins') && get_option('ctr_auto_update_enabled', 1)) {
        try {
            new CTR_Plugin_Updater();
        } catch (Exception $e) {
            error_log('CTR Updater Initialization Error: ' . $e->getMessage());
        }
    }
}

// Performance optimization: Lazy loading for reviews
add_action('wp_footer', 'ctr_lazy_loading_script');

function ctr_lazy_loading_script() {
    if (get_option('ctr_enable_lazy_loading', 1) && 
        (has_shortcode(get_the_content(), 'custom_trustpilot_reviews') || ctr_is_divi_page())) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lazy loading implementation
            if ('IntersectionObserver' in window) {
                const reviewObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('ctr-loaded');
                            reviewObserver.unobserve(entry.target);
                        }
                    });
                });
                
                document.querySelectorAll('.ctr-review-card, .ctr-review-item, .ctr-review-slide').forEach(card => {
                    reviewObserver.observe(card);
                });
            }
        });
        </script>
        <?php
    }
}

// Performance optimization: Cache warming
add_action('wp_ajax_ctr_warm_cache', 'ctr_warm_cache_ajax');

function ctr_warm_cache_ajax() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'ctr_warm_cache_nonce')) {
        wp_die('Unauthorized');
    }
    
    try {
        // Pre-fetch reviews to warm up cache
        $reviews = ctr_get_trustpilot_reviews();
        if (!isset($reviews['error'])) {
            wp_send_json_success(array('message' => 'Cache warmed successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to warm cache'));
        }
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// Performance optimization: Clear all caches
function ctr_clear_all_caches() {
    // Clear plugin transients
    delete_transient('ctr_reviews_cache');
    delete_transient('ctr_last_request_time');
    
    // Clear update transients
    delete_transient('ctr_update_available');
    delete_transient('ctr_latest_version_info');
    delete_transient('ctr_last_update_check');
    
    // Clear WordPress object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Clear any external caches if configured
    do_action('ctr_clear_external_caches');
}

// Performance optimization: Add cache headers
add_action('wp_head', 'ctr_add_cache_headers');

function ctr_add_cache_headers() {
    if (has_shortcode(get_the_content(), 'custom_trustpilot_reviews') || ctr_is_divi_page()) {
        $cache_duration = get_option('ctr_cache_duration', 3600);
        header('Cache-Control: public, max-age=' . $cache_duration);
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_duration));
    }
}

// Performance optimization: Minify CSS if enabled
add_filter('ctr_css_output', 'ctr_minify_css');

function ctr_minify_css($css) {
    if (get_option('ctr_enable_minification', 0)) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove unnecessary whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t"), '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(array(' { ', ' } ', '{ ', ' }'), array('{', '}', '{', '}'), $css);
        $css = str_replace(array('; ', ' :'), array(';', ':'), $css);
    }
    return $css;
}

// Performance optimization: CDN support
function ctr_get_asset_url($path) {
    if (get_option('ctr_enable_cdn', 0)) {
        $cdn_url = get_option('ctr_cdn_url', '');
        if (!empty($cdn_url)) {
            return trailingslashit($cdn_url) . ltrim($path, '/');
        }
    }
    return CTR_PLUGIN_URL . $path;
}

// Performance optimization: Database query optimization
add_action('wp_dashboard_setup', 'ctr_optimize_dashboard_queries');

function ctr_optimize_dashboard_queries() {
    // Only load dashboard widgets if user has permissions
    if (current_user_can('manage_options')) {
        add_action('wp_dashboard_setup', 'ctr_add_dashboard_widget');
    }
}

function ctr_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'ctr_dashboard_widget',
        'Trustpilot Reviews Status',
        'ctr_dashboard_widget_callback'
    );
}

function ctr_dashboard_widget_callback() {
    $reviews_count = get_option('ctr_reviews_count', 5);
    $cache_enabled = get_option('ctr_enable_cache', 1);
    $last_update = get_transient('ctr_last_update_check');
    
    echo '<p><strong>Configuración actual:</strong></p>';
    echo '<ul>';
    echo '<li>Reseñas a mostrar: ' . esc_html($reviews_count) . '</li>';
    echo '<li>Caché habilitado: ' . ($cache_enabled ? 'Sí' : 'No') . '</li>';
    echo '<li>Última verificación: ' . ($last_update ? date('d/m/Y H:i', $last_update) : 'Nunca') . '</li>';
    echo '</ul>';
    
    echo '<p><a href="' . admin_url('admin.php?page=ctr-settings') . '" class="button button-primary">Configurar</a></p>';
}

// Performance optimization: Cleanup on uninstall
register_uninstall_hook(__FILE__, 'ctr_cleanup_on_uninstall');

function ctr_cleanup_on_uninstall() {
    // Clear all plugin data
    ctr_clear_all_caches();
    
    // Clear scheduled events
    wp_clear_scheduled_hook('ctr_clear_cache');
    wp_clear_scheduled_hook('ctr_daily_update_check');
    
    // Remove all plugin options
    delete_option('ctr_api_url');
    delete_option('ctr_reviews_count');
    delete_option('ctr_reviews_title');
    delete_option('ctr_cache_duration');
    delete_option('ctr_enable_cache');
    delete_option('ctr_default_layout');
    delete_option('ctr_default_columns');
    delete_option('ctr_show_stars');
    delete_option('ctr_show_dates');
    delete_option('ctr_clickable_reviews');
    delete_option('ctr_show_review_button');
    delete_option('ctr_button_text');
    delete_option('ctr_button_url');
    delete_option('ctr_card_style');
    delete_option('ctr_color_scheme');
    delete_option('ctr_enable_animations');
    delete_option('ctr_enable_hover_effects');
    delete_option('ctr_enable_lazy_loading');
    delete_option('ctr_enable_minification');
    delete_option('ctr_enable_cdn');
    delete_option('ctr_auto_update_enabled');
    delete_option('ctr_update_channel');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
