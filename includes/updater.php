<?php
if (!defined('ABSPATH')) exit;

/**
 * Sistema de actualización automática para Trustpilot Reviews
 */
class CTR_Plugin_Updater {
    
    private $plugin_slug;
    private $plugin_basename;
    private $update_url;
    private $update_check_interval;
    
    public function __construct() {
        $this->plugin_slug = CTR_PLUGIN_SLUG;
        $this->plugin_basename = CTR_PLUGIN_BASENAME;
        $this->update_url = 'https://api.github.com/repos/nelsongil/trustpilot-plugin/releases/latest';
        $this->update_check_interval = 12 * HOUR_IN_SECONDS; // 12 horas
        
        // Hooks para el sistema de actualización
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
        add_action('admin_init', array($this, 'force_update_check'));
        add_action('ctr_check_for_updates', array($this, 'scheduled_update_check'));
        
        // Inicializar verificación de actualizaciones
        $this->init_update_check();
    }
    
    /**
     * Inicializar verificación de actualizaciones
     */
    public function init_update_check() {
        // Verificar si está habilitada la actualización automática
        if (get_option('ctr_auto_update_enabled', 1)) {
            // Programar verificación diaria
            if (!wp_next_scheduled('ctr_check_for_updates')) {
                wp_schedule_event(time(), 'daily', 'ctr_check_for_updates');
            }
            
            // Verificar actualizaciones en el admin
            if (is_admin()) {
                add_action('admin_init', array($this, 'admin_update_check'));
            }
        }
    }
    
    /**
     * Verificación de actualizaciones desde el admin
     */
    public function admin_update_check() {
        $last_check = get_transient('ctr_last_update_check');
        
        if (false === $last_check || (time() - $last_check) > $this->update_check_interval) {
            $this->check_for_updates();
            set_transient('ctr_last_update_check', time(), $this->update_check_interval);
        }
    }
    
    /**
     * Verificación programada de actualizaciones
     */
    public function scheduled_update_check() {
        $this->check_for_updates();
    }
    
    /**
     * Verificación forzada de actualizaciones
     */
    public function force_update_check() {
        if (isset($_GET['ctr_force_update_check']) && current_user_can('update_plugins')) {
            check_admin_referer('ctr_force_update_check');
            $this->check_for_updates();
            wp_redirect(admin_url('admin.php?page=ctr-settings&update_checked=1'));
            exit;
        }
    }
    
    /**
     * Verificar actualizaciones disponibles
     */
    public function check_for_updates($transient = null) {
        if (empty($transient)) {
            $transient = get_site_transient('update_plugins');
        }
        
        // Obtener información de la última versión
        $update_info = $this->get_latest_version_info();
        
        if ($update_info && !empty($update_info['version'])) {
            $current_version = CTR_PLUGIN_VERSION;
            $new_version = $update_info['version'];
            
            // Verificar si hay una nueva versión
            if (version_compare($new_version, $current_version, '>')) {
                // Preparar objeto de actualización
                $update_object = new stdClass();
                $update_object->slug = $this->plugin_slug;
                $update_object->plugin = $this->plugin_basename;
                $update_object->new_version = $new_version;
                $update_object->url = $update_info['url'];
                $update_object->package = $update_info['download_url'];
                $update_object->requires = $update_info['requires'] ?? '5.6';
                $update_object->requires_php = $update_info['requires_php'] ?? '7.4';
                $update_object->tested = $update_info['tested'] ?? '6.4';
                $update_object->last_updated = $update_info['published_at'] ?? '';
                $update_object->sections = array(
                    'description' => $update_info['description'] ?? '',
                    'changelog' => $update_info['changelog'] ?? '',
                    'installation' => $update_info['installation'] ?? ''
                );
                
                $transient->response[$this->plugin_basename] = $update_object;
                
                // Guardar información de actualización disponible
                set_transient('ctr_update_available', $update_info, $this->update_check_interval);
            } else {
                // No hay actualizaciones disponibles
                delete_transient('ctr_update_available');
            }
        }
        
        return $transient;
    }
    
    /**
     * Obtener información de la última versión
     */
    private function get_latest_version_info() {
        // Intentar obtener desde caché primero
        $cached_info = get_transient('ctr_latest_version_info');
        if (false !== $cached_info) {
            return $cached_info;
        }
        
        // Obtener desde GitHub API
        $response = wp_remote_get($this->update_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['tag_name'])) {
            return false;
        }
        
        // Procesar información de la versión
        // Prefer a release asset ZIP (browser_download_url) over the raw zipball.
        // The zipball from GitHub API extracts into a folder with a hash-based name
        // (e.g. nelsongil-trustpilot-plugin-a1b2c3d) which WordPress cannot map
        // to the correct plugin slug, causing the 'cannot unzip' / rename error.
        $download_url = $data['zipball_url']; // fallback
        if (!empty($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (isset($asset['browser_download_url']) && substr(strtolower($asset['browser_download_url']), -4) === '.zip') {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        $version_info = array(
            'version'      => ltrim($data['tag_name'], 'v'),
            'url'          => $data['html_url'],
            'download_url' => $download_url,
            'description'  => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? '',
            'changelog'    => $this->parse_changelog($data['body'] ?? ''),
            'installation' => $this->get_installation_instructions()
        );
        
        // Guardar en caché por 12 horas
        set_transient('ctr_latest_version_info', $version_info, $this->update_check_interval);
        
        return $version_info;
    }
    
    /**
     * Procesar changelog desde la descripción
     */
    private function parse_changelog($body) {
        if (empty($body)) {
            return '';
        }
        
        // Buscar sección de changelog
        if (preg_match('/## Changelog(.*?)(?=##|$)/s', $body, $matches)) {
            return trim($matches[1]);
        }
        
        // Si no hay sección específica, usar todo el body
        return $body;
    }
    
    /**
     * Obtener instrucciones de instalación
     */
    private function get_installation_instructions() {
        return '1. Hacer backup de tu sitio antes de actualizar
2. Desactivar el plugin actual
3. Subir la nueva versión
4. Reactivar el plugin
5. Verificar que todo funcione correctamente';
    }
    
    /**
     * Información del plugin para la API de WordPress
     */
    public function plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information' || $response->slug !== $this->plugin_slug) {
            return $false;
        }
        
        $update_info = $this->get_latest_version_info();
        
        if (!$update_info) {
            return $false;
        }
        
        $response = new stdClass();
        $response->slug = $this->plugin_slug;
        $response->plugin_name = 'Custom Trustpilot Reviews';
        $response->version = $update_info['version'];
        $response->author = 'Nelson Ariel Gil Olguin';
        $response->homepage = 'https://github.com/nelsongil/trustpilot-plugin';
        $response->requires = $update_info['requires'] ?? '5.6';
        $response->requires_php = $update_info['requires_php'] ?? '7.4';
        $response->tested = $update_info['tested'] ?? '6.4';
        $response->last_updated = $update_info['published_at'] ?? '';
        $response->sections = array(
            'description' => $update_info['description'] ?? '',
            'changelog' => $update_info['changelog'] ?? '',
            'installation' => $update_info['installation'] ?? ''
        );
        
        return $response;
    }
    
    /**
     * Acciones post-instalación
     */
    public function post_install($true, $hook_extra, $result) {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            global $wp_filesystem;
            
            // The extracted folder may have a generic name (e.g., nelsongil-trustpilot-plugin-abc123).
            // We need to move it to the correct plugin folder name.
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($this->plugin_basename);
            
            if ($wp_filesystem && isset($result['destination']) && $result['destination'] !== $plugin_dir) {
                $wp_filesystem->move($result['destination'], $plugin_dir);
                $result['destination'] = $plugin_dir;
            }
            
            // Reactivate the plugin
            activate_plugin($this->plugin_basename);
            
            // Limpiar cachés de actualización
            delete_transient('ctr_update_available');
            delete_transient('ctr_latest_version_info');
            delete_transient('ctr_last_update_check');
            
            // Mostrar mensaje de éxito
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . esc_html__('Trustpilot Reviews', 'custom-trustpilot-reviews') . ':</strong> ' . esc_html__('Plugin actualizado correctamente.', 'custom-trustpilot-reviews') . '</p>';
                echo '</div>';
            });
        }
        
        return $result;
    }
    
    /**
     * Verificar compatibilidad antes de actualizar
     */
    public function check_compatibility($version_info) {
        $current_wp_version = get_bloginfo('version');
        $current_php_version = PHP_VERSION;
        
        $requires_wp = $version_info['requires'] ?? '5.6';
        $requires_php = $version_info['requires_php'] ?? '7.4';
        
        if (version_compare($current_wp_version, $requires_wp, '<')) {
            return array(
                'compatible' => false,
                'message' => sprintf(
                    __('Requiere WordPress %s o superior. Tu versión: %s', 'custom-trustpilot-reviews'),
                    $requires_wp,
                    $current_wp_version
                )
            );
        }
        
        if (version_compare($current_php_version, $requires_php, '<')) {
            return array(
                'compatible' => false,
                'message' => sprintf(
                    __('Requiere PHP %s o superior. Tu versión: %s', 'custom-trustpilot-reviews'),
                    $requires_php,
                    $current_php_version
                )
            );
        }
        
        return array('compatible' => true);
    }
}

/**
 * Función helper para verificar actualizaciones manualmente
 */
function ctr_check_for_updates_manual() {
    if (current_user_can('update_plugins')) {
        $updater = new CTR_Plugin_Updater();
        $updater->check_for_updates();
        
        // Limpiar transientes para forzar nueva verificación
        delete_transient('ctr_latest_version_info');
        delete_transient('ctr_last_update_check');
        
        return true;
    }
    
    return false;
}

/**
 * Función helper para obtener información de actualización
 */
function ctr_get_update_info() {
    $update_info = get_transient('ctr_update_available');
    
    if ($update_info && !empty($update_info['version'])) {
        $current_version = CTR_PLUGIN_VERSION;
        $new_version = $update_info['version'];
        
        if (version_compare($new_version, $current_version, '>')) {
            return array(
                'has_update' => true,
                'current_version' => $current_version,
                'new_version' => $new_version,
                'update_url' => admin_url('plugins.php?action=upgrade-plugin&plugin=' . CTR_PLUGIN_BASENAME),
                'changelog' => $update_info['changelog'] ?? '',
                'published_at' => $update_info['published_at'] ?? ''
            );
        }
    }
    
    return array('has_update' => false);
}