<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Eliminar opciones guardadas del plugin
delete_option('ctr_api_url');
delete_option('ctr_reviews_count');
delete_option('ctr_reviews_title');
delete_option('ctr_cache_duration');
delete_option('ctr_enable_cache');

// Eliminar transientes del plugin
delete_transient('ctr_reviews_cache');
delete_transient('ctr_last_request_time');

// Limpiar cualquier caché que pueda haber quedado
wp_cache_flush();
