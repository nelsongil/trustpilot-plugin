<?php
if (!defined('ABSPATH')) exit;

function ctr_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'custom-trustpilot-reviews'));
    }

    // Handle form submission
    if (isset($_POST['ctr_save_settings']) && check_admin_referer('ctr_settings_nonce', 'ctr_nonce')) {
        
        // Validate and sanitize inputs
        $api_url = esc_url_raw($_POST['ctr_api_url']);
        $reviews_count = absint($_POST['ctr_reviews_count']);
        $reviews_title = sanitize_text_field($_POST['ctr_reviews_title']);
        $cache_duration = absint($_POST['ctr_cache_duration']);
        $enable_cache = isset($_POST['ctr_enable_cache']) ? 1 : 0;
        
        // Validate reviews count
        if ($reviews_count < 1 || $reviews_count > 50) {
            $reviews_count = 5;
        }
        
        // Validate cache duration
        if ($cache_duration < 300 || $cache_duration > 86400) {
            $cache_duration = 3600;
        }
        
        // Update options
        update_option('ctr_api_url', $api_url);
        update_option('ctr_reviews_count', $reviews_count);
        update_option('ctr_reviews_title', $reviews_title);
        update_option('ctr_cache_duration', $cache_duration);
        update_option('ctr_enable_cache', $enable_cache);
        
        // Clear cache if cache settings changed
        if ($enable_cache) {
            delete_transient('ctr_reviews_cache');
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'custom-trustpilot-reviews') . '</p></div>';
    }

    // Get current options
    $api_url = get_option('ctr_api_url', '');
    $reviews_count = get_option('ctr_reviews_count', 5);
    $reviews_title = get_option('ctr_reviews_title', 'Valoraciones de Trustpilot');
    $cache_duration = get_option('ctr_cache_duration', 3600);
    $enable_cache = get_option('ctr_enable_cache', 1);
    
    ?>
    <div class="wrap">
        <h1><?php _e('Configuración de Trustpilot Reviews', 'custom-trustpilot-reviews'); ?></h1>
        
        <form method="POST">
            <?php wp_nonce_field('ctr_settings_nonce', 'ctr_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ctr_api_url"><?php _e('URL de Trustpilot:', 'custom-trustpilot-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="url" 
                               id="ctr_api_url"
                               name="ctr_api_url" 
                               value="<?php echo esc_attr($api_url); ?>" 
                               class="regular-text"
                               placeholder="https://es.trustpilot.com/review/example.com"
                               required>
                        <p class="description">
                            <?php _e('Ingresa la URL de la página de reseñas de Trustpilot de tu empresa.', 'custom-trustpilot-reviews'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ctr_reviews_title"><?php _e('Título de las reseñas:', 'custom-trustpilot-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="ctr_reviews_title"
                               name="ctr_reviews_title" 
                               value="<?php echo esc_attr($reviews_title); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Título que se mostrará junto a las reseñas.', 'custom-trustpilot-reviews'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ctr_reviews_count"><?php _e('Número de reseñas a mostrar:', 'custom-trustpilot-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="ctr_reviews_count"
                               name="ctr_reviews_count" 
                               value="<?php echo intval($reviews_count); ?>" 
                               min="1" 
                               max="50"
                               class="small-text">
                        <p class="description">
                            <?php _e('Número de reseñas que se mostrarán (máximo 50).', 'custom-trustpilot-reviews'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ctr_enable_cache"><?php _e('Habilitar caché:', 'custom-trustpilot-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" 
                               id="ctr_enable_cache"
                               name="ctr_enable_cache" 
                               value="1" 
                               <?php checked($enable_cache, 1); ?>>
                        <label for="ctr_enable_cache">
                            <?php _e('Activar caché para mejorar el rendimiento', 'custom-trustpilot-reviews'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ctr_cache_duration"><?php _e('Duración del caché (segundos):', 'custom-trustpilot-reviews'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="ctr_cache_duration"
                               name="ctr_cache_duration" 
                               value="<?php echo intval($cache_duration); ?>" 
                               min="300" 
                               max="86400"
                               class="small-text">
                        <p class="description">
                            <?php _e('Tiempo en segundos que se mantendrán las reseñas en caché (mínimo 5 minutos, máximo 24 horas).', 'custom-trustpilot-reviews'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="ctr_save_settings" class="button button-primary">
                    <?php _e('Guardar cambios', 'custom-trustpilot-reviews'); ?>
                </button>
                
                <?php if ($enable_cache): ?>
                    <button type="button" id="ctr_clear_cache" class="button button-secondary" style="margin-left: 10px;">
                        <?php _e('Limpiar caché', 'custom-trustpilot-reviews'); ?>
                    </button>
                <?php endif; ?>
            </p>
        </form>
        
        <hr>
        
        <h2><?php _e('Uso del plugin:', 'custom-trustpilot-reviews'); ?></h2>
        
        <h3><?php _e('Shortcode:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('Usa el siguiente shortcode para mostrar las reseñas:', 'custom-trustpilot-reviews'); ?></p>
        <code>[custom_trustpilot_reviews]</code>
        
        <h3><?php _e('Módulo de Divi:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('Si usas Divi, puedes agregar el módulo "Trustpilot Reviews" desde el editor de Divi.', 'custom-trustpilot-reviews'); ?></p>
        
        <h3><?php _e('PHP Function:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('También puedes usar esta función en tu tema:', 'custom-trustpilot-reviews'); ?></p>
        <code>&lt;?php echo ctr_render_reviews_carousel(); ?&gt;</code>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ctr_clear_cache').on('click', function() {
                if (confirm('<?php _e('¿Estás seguro de que quieres limpiar el caché?', 'custom-trustpilot-reviews'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'ctr_clear_cache',
                        nonce: '<?php echo wp_create_nonce('ctr_clear_cache_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php _e('Caché limpiado correctamente.', 'custom-trustpilot-reviews'); ?>');
                        } else {
                            alert('<?php _e('Error al limpiar el caché.', 'custom-trustpilot-reviews'); ?>');
                        }
                    });
                }
            });
        });
        </script>
    </div>
    <?php
}

// AJAX handler for clearing cache
add_action('wp_ajax_ctr_clear_cache', 'ctr_ajax_clear_cache');

function ctr_ajax_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_die();
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'ctr_clear_cache_nonce')) {
        wp_die();
    }
    
    delete_transient('ctr_reviews_cache');
    wp_send_json_success();
}
