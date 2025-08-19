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
        
        // Layout and display settings
        $default_layout = sanitize_text_field($_POST['ctr_default_layout']);
        $default_columns = absint($_POST['ctr_default_columns']);
        $show_stars = isset($_POST['ctr_show_stars']) ? 1 : 0;
        $show_dates = isset($_POST['ctr_show_dates']) ? 1 : 0;
        $clickable_reviews = isset($_POST['ctr_clickable_reviews']) ? 1 : 0;
        $show_review_button = isset($_POST['ctr_show_review_button']) ? 1 : 0;
        $button_text = sanitize_text_field($_POST['ctr_button_text']);
        $button_url = esc_url_raw($_POST['ctr_button_url']);
        
        // Style settings
        $card_style = sanitize_text_field($_POST['ctr_card_style']);
        $color_scheme = sanitize_text_field($_POST['ctr_color_scheme']);
        $enable_animations = isset($_POST['ctr_enable_animations']) ? 1 : 0;
        $enable_hover_effects = isset($_POST['ctr_enable_hover_effects']) ? 1 : 0;
        
        // Performance settings
        $enable_lazy_loading = isset($_POST['ctr_enable_lazy_loading']) ? 1 : 0;
        $enable_minification = isset($_POST['ctr_enable_minification']) ? 1 : 0;
        $enable_cdn = isset($_POST['ctr_enable_cdn']) ? 1 : 0;
        $cdn_url = esc_url_raw($_POST['ctr_cdn_url']);
        
        // Update system settings
        $auto_update_enabled = isset($_POST['ctr_auto_update_enabled']) ? 1 : 0;
        $update_channel = sanitize_text_field($_POST['ctr_update_channel']);
        
        // Validate reviews count
        if ($reviews_count < 1 || $reviews_count > 50) {
            $reviews_count = 5;
        }
        
        // Validate cache duration
        if ($cache_duration < 300 || $cache_duration > 86400) {
            $cache_duration = 3600;
        }
        
        // Validate columns
        if ($default_columns < 1 || $default_columns > 4) {
            $default_columns = 1;
        }
        
        // Update options
        update_option('ctr_api_url', $api_url);
        update_option('ctr_reviews_count', $reviews_count);
        update_option('ctr_reviews_title', $reviews_title);
        update_option('ctr_cache_duration', $cache_duration);
        update_option('ctr_enable_cache', $enable_cache);
        
        // Layout and display options
        update_option('ctr_default_layout', $default_layout);
        update_option('ctr_default_columns', $default_columns);
        update_option('ctr_show_stars', $show_stars);
        update_option('ctr_show_dates', $show_dates);
        update_option('ctr_clickable_reviews', $clickable_reviews);
        update_option('ctr_show_review_button', $show_review_button);
        update_option('ctr_button_text', $button_text);
        update_option('ctr_button_url', $button_url);
        
        // Style options
        update_option('ctr_card_style', $card_style);
        update_option('ctr_color_scheme', $color_scheme);
        update_option('ctr_enable_animations', $enable_animations);
        update_option('ctr_enable_hover_effects', $enable_hover_effects);
        
        // Performance options
        update_option('ctr_enable_lazy_loading', $enable_lazy_loading);
        update_option('ctr_enable_minification', $enable_minification);
        update_option('ctr_enable_cdn', $enable_cdn);
        update_option('ctr_cdn_url', $cdn_url);
        
        // Update system options
        update_option('ctr_auto_update_enabled', $auto_update_enabled);
        update_option('ctr_update_channel', $update_channel);
        
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
    
    // Layout and display options
    $default_layout = get_option('ctr_default_layout', 'grid');
    $default_columns = get_option('ctr_default_columns', 1);
    $show_stars = get_option('ctr_show_stars', 1);
    $show_dates = get_option('ctr_show_dates', 1);
    $clickable_reviews = get_option('ctr_clickable_reviews', 1);
    $show_review_button = get_option('ctr_show_review_button', 1);
    $button_text = get_option('ctr_button_text', '¡Valora en Trustpilot!');
    $button_url = get_option('ctr_button_url', 'https://es.trustpilot.com/evaluate/nelsongil.com');
    
    // Style options
    $card_style = get_option('ctr_card_style', 'modern');
    $color_scheme = get_option('ctr_color_scheme', 'default');
    $enable_animations = get_option('ctr_enable_animations', 1);
    $enable_hover_effects = get_option('ctr_enable_hover_effects', 1);
    
    // Performance options
    $enable_lazy_loading = get_option('ctr_enable_lazy_loading', 1);
    $enable_minification = get_option('ctr_enable_minification', 0);
    $enable_cdn = get_option('ctr_enable_cdn', 0);
    $cdn_url = get_option('ctr_cdn_url', '');
    
    // Update system options
    $auto_update_enabled = get_option('ctr_auto_update_enabled', 1);
    $update_channel = get_option('ctr_update_channel', 'stable');
    
    // Get update information
    $update_info = ctr_get_update_info();
    
    ?>
    <div class="wrap">
        <h1><?php _e('Configuración de Trustpilot Reviews', 'custom-trustpilot-reviews'); ?></h1>
        
        <!-- Update Notification -->
        <?php if ($update_info['has_update']): ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('¡Nueva versión disponible!', 'custom-trustpilot-reviews'); ?></strong>
                    <?php printf(
                        __('Versión actual: %s | Nueva versión: %s', 'custom-trustpilot-reviews'),
                        esc_html($update_info['current_version']),
                        esc_html($update_info['new_version'])
                    ); ?>
                    <a href="<?php echo esc_url($update_info['update_url']); ?>" class="button button-primary" style="margin-left: 10px;">
                        <?php _e('Actualizar ahora', 'custom-trustpilot-reviews'); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php wp_nonce_field('ctr_settings_nonce', 'ctr_nonce'); ?>
            
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'custom-trustpilot-reviews'); ?></a>
                <a href="#display" class="nav-tab"><?php _e('Visualización', 'custom-trustpilot-reviews'); ?></a>
                <a href="#style" class="nav-tab"><?php _e('Estilos', 'custom-trustpilot-reviews'); ?></a>
                <a href="#performance" class="nav-tab"><?php _e('Rendimiento', 'custom-trustpilot-reviews'); ?></a>
                <a href="#updates" class="nav-tab"><?php _e('Actualizaciones', 'custom-trustpilot-reviews'); ?></a>
                <a href="#advanced" class="nav-tab"><?php _e('Avanzado', 'custom-trustpilot-reviews'); ?></a>
            </h2>
            
            <!-- General Settings Tab -->
            <div id="general" class="tab-content">
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
                            <label for="ctr_reviews_count"><?php _e('Número de reseñas:', 'custom-trustpilot-reviews'); ?></label>
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
                                <?php _e('Número de reseñas que se mostrarán por defecto (máximo 50).', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_reviews_title"><?php _e('Título por defecto:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="ctr_reviews_title"
                                   name="ctr_reviews_title" 
                                   value="<?php echo esc_attr($reviews_title); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Título que se mostrará por defecto junto a las reseñas.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Display Settings Tab -->
            <div id="display" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ctr_default_layout"><?php _e('Layout por defecto:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <select id="ctr_default_layout" name="ctr_default_layout">
                                <option value="grid" <?php selected($default_layout, 'grid'); ?>><?php _e('Grid', 'custom-trustpilot-reviews'); ?></option>
                                <option value="list" <?php selected($default_layout, 'list'); ?>><?php _e('Lista', 'custom-trustpilot-reviews'); ?></option>
                                <option value="carousel" <?php selected($default_layout, 'carousel'); ?>><?php _e('Carrusel', 'custom-trustpilot-reviews'); ?></option>
                                <option value="masonry" <?php selected($default_layout, 'masonry'); ?>><?php _e('Masonry', 'custom-trustpilot-reviews'); ?></option>
                                <option value="timeline" <?php selected($default_layout, 'timeline'); ?>><?php _e('Timeline', 'custom-trustpilot-reviews'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Layout predeterminado para mostrar las reseñas.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_default_columns"><?php _e('Columnas por defecto:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="ctr_default_columns"
                                   name="ctr_default_columns" 
                                   value="<?php echo intval($default_columns); ?>" 
                                   min="1" 
                                   max="4"
                                   class="small-text">
                            <p class="description">
                                <?php _e('Número de columnas para el layout grid por defecto.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Elementos a mostrar:', 'custom-trustpilot-reviews'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="ctr_show_stars" 
                                       value="1" 
                                       <?php checked($show_stars, 1); ?>>
                                <?php _e('Mostrar estrellas de valoración', 'custom-trustpilot-reviews'); ?>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" 
                                       name="ctr_show_dates" 
                                       value="1" 
                                       <?php checked($show_dates, 1); ?>>
                                <?php _e('Mostrar fechas de las reseñas', 'custom-trustpilot-reviews'); ?>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" 
                                       name="ctr_clickable_reviews" 
                                       value="1" 
                                       <?php checked($clickable_reviews, 1); ?>>
                                <?php _e('Hacer las reseñas clickeables (enlace a Trustpilot)', 'custom-trustpilot-reviews'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_show_review_button"><?php _e('Botón de valoración:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="ctr_show_review_button"
                                       name="ctr_show_review_button" 
                                       value="1" 
                                       <?php checked($show_review_button, 1); ?>>
                                <?php _e('Mostrar botón para valorar en Trustpilot', 'custom-trustpilot-reviews'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_button_text"><?php _e('Texto del botón:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="ctr_button_text"
                                   name="ctr_button_text" 
                                   value="<?php echo esc_attr($button_text); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_button_url"><?php _e('URL del botón:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="ctr_button_url"
                                   name="ctr_button_url" 
                                   value="<?php echo esc_attr($button_url); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Style Settings Tab -->
            <div id="style" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ctr_card_style"><?php _e('Estilo de tarjetas:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <select id="ctr_card_style" name="ctr_card_style">
                                <option value="modern" <?php selected($card_style, 'modern'); ?>><?php _e('Moderno', 'custom-trustpilot-reviews'); ?></option>
                                <option value="classic" <?php selected($card_style, 'classic'); ?>><?php _e('Clásico', 'custom-trustpilot-reviews'); ?></option>
                                <option value="minimal" <?php selected($card_style, 'minimal'); ?>><?php _e('Minimalista', 'custom-trustpilot-reviews'); ?></option>
                                <option value="elegant" <?php selected($card_style, 'elegant'); ?>><?php _e('Elegante', 'custom-trustpilot-reviews'); ?></option>
                                <option value="bold" <?php selected($card_style, 'bold'); ?>><?php _e('Audaz', 'custom-trustpilot-reviews'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Estilo visual de las tarjetas de reseñas.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_color_scheme"><?php _e('Esquema de colores:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <select id="ctr_color_scheme" name="ctr_color_scheme">
                                <option value="default" <?php selected($color_scheme, 'default'); ?>><?php _e('Por defecto', 'custom-trustpilot-reviews'); ?></option>
                                <option value="blue" <?php selected($color_scheme, 'blue'); ?>><?php _e('Azul', 'custom-trustpilot-reviews'); ?></option>
                                <option value="green" <?php selected($color_scheme, 'green'); ?>><?php _e('Verde', 'custom-trustpilot-reviews'); ?></option>
                                <option value="purple" <?php selected($color_scheme, 'purple'); ?>><?php _e('Púrpura', 'custom-trustpilot-reviews'); ?></option>
                                <option value="orange" <?php selected($color_scheme, 'orange'); ?>><?php _e('Naranja', 'custom-trustpilot-reviews'); ?></option>
                                <option value="dark" <?php selected($color_scheme, 'dark'); ?>><?php _e('Oscuro', 'custom-trustpilot-reviews'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Efectos visuales:', 'custom-trustpilot-reviews'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="ctr_enable_animations" 
                                       value="1" 
                                       <?php checked($enable_animations, 1); ?>>
                                <?php _e('Habilitar animaciones', 'custom-trustpilot-reviews'); ?>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" 
                                       name="ctr_enable_hover_effects" 
                                       value="1" 
                                       <?php checked($enable_hover_effects, 1); ?>>
                                <?php _e('Habilitar efectos hover', 'custom-trustpilot-reviews'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Performance Settings Tab -->
            <div id="performance" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ctr_enable_lazy_loading"><?php _e('Lazy Loading:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="ctr_enable_lazy_loading"
                                       name="ctr_enable_lazy_loading" 
                                       value="1" 
                                       <?php checked($enable_lazy_loading, 1); ?>>
                                <?php _e('Habilitar carga diferida de reseñas', 'custom-trustpilot-reviews'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Mejora el rendimiento cargando las reseñas solo cuando son visibles.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_enable_minification"><?php _e('Minificación CSS:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="ctr_enable_minification"
                                       name="ctr_enable_minification" 
                                       value="1" 
                                       <?php checked($enable_minification, 0); ?>>
                                <?php _e('Habilitar minificación de CSS', 'custom-trustpilot-reviews'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Reduce el tamaño de los archivos CSS para mejorar la velocidad de carga.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_enable_cdn"><?php _e('CDN:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="ctr_enable_cdn"
                                       name="ctr_enable_cdn" 
                                       value="1" 
                                       <?php checked($enable_cdn, 0); ?>>
                                <?php _e('Habilitar CDN para assets', 'custom-trustpilot-reviews'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Sirve los archivos estáticos desde una CDN para mejorar la velocidad.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_cdn_url"><?php _e('URL del CDN:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="ctr_cdn_url"
                                   name="ctr_cdn_url" 
                                   value="<?php echo esc_attr($cdn_url); ?>" 
                                   class="regular-text"
                                   placeholder="https://cdn.example.com">
                            <p class="description">
                                <?php _e('URL base de tu CDN (ej: https://cdn.example.com).', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Updates Settings Tab -->
            <div id="updates" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ctr_auto_update_enabled"><?php _e('Actualizaciones automáticas:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="ctr_auto_update_enabled"
                                       name="ctr_auto_update_enabled" 
                                       value="1" 
                                       <?php checked($auto_update_enabled, 1); ?>>
                                <?php _e('Habilitar verificación automática de actualizaciones', 'custom-trustpilot-reviews'); ?>
                            </label>
                            <p class="description">
                                <?php _e('El plugin verificará automáticamente si hay nuevas versiones disponibles.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="ctr_update_channel"><?php _e('Canal de actualizaciones:', 'custom-trustpilot-reviews'); ?></label>
                        </th>
                        <td>
                            <select id="ctr_update_channel" name="ctr_update_channel">
                                <option value="stable" <?php selected($update_channel, 'stable'); ?>><?php _e('Estable (Recomendado)', 'custom-trustpilot-reviews'); ?></option>
                                <option value="beta" <?php selected($update_channel, 'beta'); ?>><?php _e('Beta (Para desarrolladores)', 'custom-trustpilot-reviews'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Canal de actualizaciones que quieres seguir.', 'custom-trustpilot-reviews'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Estado actual:', 'custom-trustpilot-reviews'); ?></th>
                        <td>
                            <p>
                                <strong><?php _e('Versión actual:', 'custom-trustpilot-reviews'); ?></strong> 
                                <?php echo esc_html(CTR_PLUGIN_VERSION); ?>
                            </p>
                            
                            <?php if ($update_info['has_update']): ?>
                                <p style="color: #0073e6;">
                                    <strong><?php _e('Nueva versión disponible:', 'custom-trustpilot-reviews'); ?></strong> 
                                    <?php echo esc_html($update_info['new_version']); ?>
                                </p>
                                <p>
                                    <a href="<?php echo esc_url($update_info['update_url']); ?>" class="button button-primary">
                                        <?php _e('Actualizar ahora', 'custom-trustpilot-reviews'); ?>
                                    </a>
                                </p>
                            <?php else: ?>
                                <p style="color: #28a745;">
                                    <strong><?php _e('Estás usando la versión más reciente.', 'custom-trustpilot-reviews'); ?></strong>
                                </p>
                            <?php endif; ?>
                            
                            <p>
                                <button type="button" id="ctr_check_updates" class="button button-secondary">
                                    <?php _e('Verificar actualizaciones ahora', 'custom-trustpilot-reviews'); ?>
                                </button>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Advanced Settings Tab -->
            <div id="advanced" class="tab-content" style="display: none;">
                <table class="form-table">
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
            </div>
            
            <p class="submit">
                <button type="submit" name="ctr_save_settings" class="button button-primary">
                    <?php _e('Guardar cambios', 'custom-trustpilot-reviews'); ?>
                </button>
                
                <?php if ($enable_cache): ?>
                    <button type="button" id="ctr_clear_cache" class="button button-secondary" style="margin-left: 10px;">
                        <?php _e('Limpiar caché', 'custom-trustpilot-reviews'); ?>
                    </button>
                    
                    <button type="button" id="ctr_warm_cache" class="button button-secondary" style="margin-left: 10px;">
                        <?php _e('Calentar caché', 'custom-trustpilot-reviews'); ?>
                    </button>
                <?php endif; ?>
            </p>
        </form>
        
        <hr>
        
        <h2><?php _e('Uso del plugin:', 'custom-trustpilot-reviews'); ?></h2>
        
        <h3><?php _e('Shortcode básico:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('Usa el siguiente shortcode para mostrar las reseñas:', 'custom-trustpilot-reviews'); ?></p>
        <code>[custom_trustpilot_reviews]</code>
        
        <h3><?php _e('Shortcode con opciones:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('Personaliza la visualización con parámetros:', 'custom-trustpilot-reviews'); ?></p>
        <code>[custom_trustpilot_reviews layout="masonry" count="6" columns="3" show_stars="true"]</code>
        
        <h3><?php _e('Parámetros disponibles:', 'custom-trustpilot-reviews'); ?></h3>
        <ul style="margin-left: 20px;">
            <li><code>layout</code>: grid, list, carousel, masonry, timeline</li>
            <li><code>count</code>: número de reseñas (1-50)</li>
            <li><code>columns</code>: número de columnas para grid (1-4)</li>
            <li><code>show_stars</code>: mostrar estrellas (true/false)</li>
            <li><code>show_dates</code>: mostrar fechas (true/false)</li>
            <li><code>clickable</code>: reseñas clickeables (true/false)</li>
            <li><code>style</code>: modern, classic, minimal, elegant, bold</li>
            <li><code>colors</code>: default, blue, green, purple, orange, dark</li>
        </ul>
        
        <h3><?php _e('Módulo de Divi:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('Si usas Divi, puedes agregar el módulo "Trustpilot Reviews" desde el editor de Divi.', 'custom-trustpilot-reviews'); ?></p>
        
        <h3><?php _e('PHP Function:', 'custom-trustpilot-reviews'); ?></h3>
        <p><?php _e('También puedes usar esta función en tu tema:', 'custom-trustpilot-reviews'); ?></p>
        <code>&lt;?php echo ctr_render_reviews_carousel(['layout' => 'masonry']); ?&gt;</code>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
            
            // Cache clearing
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
            
            // Cache warming
            $('#ctr_warm_cache').on('click', function() {
                if (confirm('<?php _e('¿Quieres calentar el caché para mejorar el rendimiento?', 'custom-trustpilot-reviews'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'ctr_warm_cache',
                        nonce: '<?php echo wp_create_nonce('ctr_warm_cache_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('<?php _e('Caché calentado correctamente.', 'custom-trustpilot-reviews'); ?>');
                        } else {
                            alert('<?php _e('Error al calentar el caché.', 'custom-trustpilot-reviews'); ?>');
                        }
                    });
                }
            });
            
            // Check for updates
            $('#ctr_check_updates').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Verificando...', 'custom-trustpilot-reviews'); ?>');
                
                $.post(ajaxurl, {
                    action: 'ctr_check_updates',
                    nonce: '<?php echo wp_create_nonce('ctr_update_check_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Verificación completada. Recarga la página para ver si hay actualizaciones.', 'custom-trustpilot-reviews'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Error al verificar actualizaciones.', 'custom-trustpilot-reviews'); ?>');
                    }
                    $button.prop('disabled', false).text('<?php _e('Verificar actualizaciones ahora', 'custom-trustpilot-reviews'); ?>');
                });
            });
        });
        </script>
        
        <style>
        .nav-tab-wrapper { margin-bottom: 20px; }
        .tab-content { margin-top: 20px; }
        .tab-content:first-of-type { display: block; }
        </style>
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
