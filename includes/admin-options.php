<?php
if (!defined('ABSPATH')) exit;

<<<<<<< HEAD
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
=======
function ctr_settings_page() {
    if (isset($_POST['ctr_save_settings'])) {
        update_option('ctr_api_url', sanitize_text_field($_POST['ctr_api_url']));
        update_option('ctr_reviews_count', intval($_POST['ctr_reviews_count']));
    }

    $api_url = get_option('ctr_api_url', '');
    $reviews_count = get_option('ctr_reviews_count', 5);
    ?>
    <div class="wrap">
        <h1>Configuración de Trustpilot Reviews</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row">URL de Trustpilot:</th>
                    <td><input type="text" name="ctr_api_url" value="<?php echo esc_url($api_url); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Número de reseñas a mostrar:</th>
                    <td><input type="number" name="ctr_reviews_count" value="<?php echo intval($reviews_count); ?>" min="1" class="small-text"></td>
                </tr>
            </table>
            <p class="submit"><button type="submit" name="ctr_save_settings" class="button button-primary">Guardar cambios</button></p>
        </form>
        <h2>Shortcode:</h2>
        <p>Usa el siguiente shortcode para mostrar las reseñas: <code>[custom_trustpilot_reviews]</code></p>
    </div>
    <?php
}
>>>>>>> 53026be42e90a9c49a795f2faf46160b26a22258
