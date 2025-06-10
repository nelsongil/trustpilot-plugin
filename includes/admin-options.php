<?php
if (!defined('ABSPATH')) exit;

// Añadir menú de administración
function ctr_admin_menu() {
    add_menu_page(
        __('Trustpilot Reviews', 'custom-trustpilot-reviews'), // Page title
        __('Trustpilot Reviews', 'custom-trustpilot-reviews'), // Menu title
        'manage_options', // Capability required
        'edit.php?post_type=trustpilot_preset', // Menu slug - direct link to custom post type list
        null, // Callback function (null for direct link)
        'dashicons-star-filled', // Icon URL
        30 // Position in the menu order
    );
    // Note: No need for a separate submenu "Todos los Presets" as the main menu item now leads there.
}
add_action('admin_menu', 'ctr_admin_menu');
