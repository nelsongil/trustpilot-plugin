<?php
if (!defined('ABSPATH')) exit;

/**
 * Sistema de actualización estable para Trustpilot Reviews
 * Versión 2.0 - Completamente reescrito para mayor estabilidad
 */
class CTR_Plugin_Updater {
    
    private $plugin_slug;
    private $plugin_basename;
    private $plugin_name;
    private $update_url;
    private $update_check_interval;
    private $last_check_time;
    private $is_initialized;
    
    public function __construct() {
        $this->plugin_slug = CTR_PLUGIN_SLUG;
        $this->plugin_basename = CTR_PLUGIN_BASENAME;
        $this->plugin_name = 'Custom Trustpilot Reviews';
        $this->update_url = 'https://api.github.com/repos/nelsongil/trustpilot-reviews/releases/latest';
        $this->update_check_interval = 24 * HOUR_IN_SECONDS; // 24 horas
        $this->is_initialized = false;
        
        // Inicializar solo si es seguro
        add_action('init', array($this, 'safe_init'), 20);
    }
    
    /**
     * Inicialización segura del sistema de actualización
     */
    public function safe_init() {
        // Verificar que WordPress esté completamente cargado
        if (!did_action('init') || $this->is_initialized) {
            return;
        }
        
        // Verificar permisos y capacidades
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        // Verificar que no estemos en modo de mantenimiento
        if (wp_is_maintenance_mode()) {
            return;
        }
        
        try {
            $this->init_update_system();
            $this->is_initialized = true;
        } catch (Exception $e) {
            // Log del error pero no fallar
            error_log('CTR Updater Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Inicializar el sistema de actualización
     */
    private function init_update_system() {
        // Hooks para el sistema de actualización
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
        
        // Verificación manual de actualizaciones
        add_action('admin_init', array($this, 'admin_update_check'));
        add_action('wp_ajax_ctr_check_updates', array($this, 'ajax_check_updates'));
        
        // Programar verificación automática
        $this->schedule_update_checks();
    }
    
    /**
     * Programar verificaciones de actualización
     */
    private function schedule_update_checks() {
        if (!wp_next_scheduled('ctr_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'ctr_daily_update_check');
        }
        add_action('ctr_daily_update_check', array($this, 'scheduled_update_check'));
    }
    
    /**
     * Verificación programada de actualizaciones
     */
    public function scheduled_update_check() {
        try {
            $this->check_for_updates();
        } catch (Exception $e) {
            error_log('CTR Scheduled Update Check Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificación desde el admin
     */
    public function admin_update_check() {
        $this->last_check_time = get_transient('ctr_last_update_check');
        
        if (false === $this->last_check_time || 
            (time() - $this->last_check_time) > $this->update_check_interval) {
            
            try {
                $this->check_for_updates();
                set_transient('ctr_last_update_check', time(), $this->update_check_interval);
            } catch (Exception $e) {
                error_log('CTR Admin Update Check Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Verificación AJAX de actualizaciones
     */
    public function ajax_check_updates() {
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'ctr_update_check_nonce') || 
            !current_user_can('update_plugins')) {
            wp_die('Unauthorized');
        }
        
        try {
            $result = $this->check_for_updates();
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Verificar actualizaciones disponibles
     */
    public function check_for_updates($transient = null) {
        if (empty($transient)) {
            $transient = get_site_transient('update_plugins');
        }
        
        if (!$transient) {
            return $transient;
        }
        
        try {
            // Obtener información de la última versión
            $update_info = $this->get_latest_version_info();
            
            if ($update_info && !empty($update_info['version'])) {
                $current_version = CTR_PLUGIN_VERSION;
                $new_version = $update_info['version'];
                
                // Verificar si hay una nueva versión
                if (version_compare($new_version, $current_version, '>')) {
                    // Verificar compatibilidad antes de mostrar la actualización
                    $compatibility = $this->check_compatibility($update_info);
                    
                    if ($compatibility['compatible']) {
                        $update_object = $this->prepare_update_object($update_info);
                        $transient->response[$this->plugin_basename] = $update_object;
                        
                        // Guardar información de actualización disponible
                        set_transient('ctr_update_available', $update_info, $this->update_check_interval);
                        
                        // Notificar en el admin
                        $this->show_update_notification($update_info);
                    }
                } else {
                    // No hay actualizaciones disponibles
                    delete_transient('ctr_update_available');
                }
            }
        } catch (Exception $e) {
            error_log('CTR Update Check Error: ' . $e->getMessage());
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
        
        // Obtener desde GitHub API con timeout y reintentos
        $response = $this->make_github_request();
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['tag_name'])) {
            return false;
        }
        
        // Procesar información de la versión
        $version_info = array(
            'version' => ltrim($data['tag_name'], 'v'),
            'url' => $data['html_url'],
            'download_url' => $data['zipball_url'],
            'description' => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? '',
            'changelog' => $this->parse_changelog($data['body'] ?? ''),
            'installation' => $this->get_installation_instructions(),
            'requires' => $this->extract_requirements($data['body'] ?? ''),
            'requires_php' => $this->extract_php_requirement($data['body'] ?? ''),
            'tested' => $this->extract_tested_version($data['body'] ?? '')
        );
        
        // Guardar en caché por 24 horas
        set_transient('ctr_latest_version_info', $version_info, $this->update_check_interval);
        
        return $version_info;
    }
    
    /**
     * Realizar petición a GitHub con reintentos
     */
    private function make_github_request($retries = 3) {
        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        );
        
        for ($i = 0; $i < $retries; $i++) {
            $response = wp_remote_get($this->update_url, $args);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return $response;
            }
            
            // Esperar antes del reintento
            if ($i < $retries - 1) {
                sleep(2);
            }
        }
        
        return new WP_Error('github_request_failed', 'Failed to fetch update information after ' . $retries . ' attempts');
    }
    
    /**
     * Extraer requisitos del changelog
     */
    private function extract_requirements($body) {
        if (preg_match('/Requires WordPress:\s*([0-9.]+)/i', $body, $matches)) {
            return $matches[1];
        }
        return '5.6';
    }
    
    /**
     * Extraer requisito de PHP del changelog
     */
    private function extract_php_requirement($body) {
        if (preg_match('/Requires PHP:\s*([0-9.]+)/i', $body, $matches)) {
            return $matches[1];
        }
        return '7.4';
    }
    
    /**
     * Extraer versión probada del changelog
     */
    private function extract_tested_version($body) {
        if (preg_match('/Tested up to:\s*([0-9.]+)/i', $body, $matches)) {
            return $matches[1];
        }
        return '6.4';
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
     * Preparar objeto de actualización
     */
    private function prepare_update_object($update_info) {
        $update_object = new stdClass();
        $update_object->slug = $this->plugin_slug;
        $update_object->plugin = $this->plugin_basename;
        $update_object->new_version = $update_info['version'];
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
        
        return $update_object;
    }
    
    /**
     * Verificar compatibilidad antes de actualizar
     */
    private function check_compatibility($version_info) {
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
    
    /**
     * Mostrar notificación de actualización en el admin
     */
    private function show_update_notification($update_info) {
        add_action('admin_notices', function() use ($update_info) {
            $current_version = CTR_PLUGIN_VERSION;
            $new_version = $update_info['version'];
            
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>' . esc_html($this->plugin_name) . ':</strong> ';
            printf(
                __('Hay una nueva versión disponible (%s). <a href="%s">Actualizar ahora</a>', 'custom-trustpilot-reviews'),
                esc_html($new_version),
                esc_url(admin_url('plugins.php?action=upgrade-plugin&plugin=' . $this->plugin_basename))
            );
            echo '</p>';
            echo '</div>';
        });
    }
    
    /**
     * Información del plugin para la API de WordPress
     */
    public function plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information' || $response->slug !== $this->plugin_slug) {
            return $false;
        }
        
        try {
            $update_info = $this->get_latest_version_info();
            
            if (!$update_info) {
                return $false;
            }
            
            $response = new stdClass();
            $response->slug = $this->plugin_slug;
            $response->plugin_name = $this->plugin_name;
            $response->version = $update_info['version'];
            $response->author = 'Nelson Ariel Gil Olguin';
            $response->homepage = 'https://github.com/nelsongil/trustpilot-reviews';
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
        } catch (Exception $e) {
            error_log('CTR Plugin Info Error: ' . $e->getMessage());
            return $false;
        }
    }
    
    /**
     * Acciones post-instalación
     */
    public function post_install($true, $hook_extra, $result) {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            // Limpiar cachés de actualización
            delete_transient('ctr_update_available');
            delete_transient('ctr_latest_version_info');
            delete_transient('ctr_last_update_check');
            
            // Mostrar mensaje de éxito
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . esc_html($this->plugin_name) . ':</strong> ' . __('Plugin actualizado correctamente.', 'custom-trustpilot-reviews') . '</p>';
                echo '</div>';
            });
        }
        
        return $result;
    }
}

/**
 * Función helper para verificar actualizaciones manualmente
 */
function ctr_check_for_updates_manual() {
    if (current_user_can('update_plugins')) {
        try {
            $updater = new CTR_Plugin_Updater();
            $updater->check_for_updates();
            
            // Limpiar transientes para forzar nueva verificación
            delete_transient('ctr_latest_version_info');
            delete_transient('ctr_last_update_check');
            
            return true;
        } catch (Exception $e) {
            error_log('CTR Manual Update Check Error: ' . $e->getMessage());
            return false;
        }
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

/**
 * Función helper para limpiar cachés de actualización
 */
function ctr_clear_update_cache() {
    delete_transient('ctr_update_available');
    delete_transient('ctr_latest_version_info');
    delete_transient('ctr_last_update_check');
    
    return true;
}