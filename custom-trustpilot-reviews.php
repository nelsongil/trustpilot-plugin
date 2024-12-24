<?php
/*
Plugin Name: Custom Trustpilot Reviews
Description: Muestra las valoraciones de Trustpilot en WordPress y Divi con un diseño personalizable.
Version: 1.5
Author: Nelson Ariel Gil Olguin
Text Domain: custom-trustpilot-reviews
Requires at least: 5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

// Incluir archivos necesarios
include_once plugin_dir_path(__FILE__) . 'includes/admin-options.php';
include_once plugin_dir_path(__FILE__) . 'includes/api.php';
include_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

// Registrar el menú del administrador
add_action('admin_menu', 'ctr_add_admin_menu');

function ctr_add_admin_menu() {
    add_menu_page(
        'Trustpilot Reviews',
        'Trustpilot Reviews',
        'manage_options',
        'ctr-settings',
        'ctr_settings_page',
        plugins_url('assets/img/icono.png', __FILE__), // Icono pequeño del menú
        60
    );
}

// Cargar estilos
function ctr_enqueue_assets() {
    wp_enqueue_style('ctr-styles', plugins_url('assets/css/styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'ctr_enqueue_assets');

// Registro del módulo de Divi
function ctr_register_divi_module() {
    if (class_exists('ET_Builder_Module')) {
        include_once plugin_dir_path(__FILE__) . 'includes/divi-module.php';
    }
}
add_action('et_builder_ready', 'ctr_register_divi_module');
