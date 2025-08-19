<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Eliminar opciones guardadas del plugin
delete_option('ctr_api_url');
delete_option('ctr_reviews_count');
delete_option('ctr_reviews_title');
delete_option('ctr_cache_duration');
delete_option('ctr_enable_cache');

// Eliminar opciones de layout y visualización
delete_option('ctr_default_layout');
delete_option('ctr_default_columns');
delete_option('ctr_show_stars');
delete_option('ctr_show_dates');
delete_option('ctr_clickable_reviews');
delete_option('ctr_show_review_button');
delete_option('ctr_button_text');
delete_option('ctr_button_url');

// Eliminar opciones de estilo
delete_option('ctr_card_style');
delete_option('ctr_color_scheme');
delete_option('ctr_enable_animations');
delete_option('ctr_enable_hover_effects');

// Eliminar opciones del sistema de actualización
delete_option('ctr_auto_update_enabled');
delete_option('ctr_update_channel');

// Eliminar transientes del plugin
delete_transient('ctr_reviews_cache');
delete_transient('ctr_last_request_time');

// Eliminar transientes del sistema de actualización
delete_transient('ctr_update_available');
delete_transient('ctr_latest_version_info');
delete_transient('ctr_last_update_check');

// Limpiar cualquier caché que pueda haber quedado
wp_cache_flush();

// Limpiar eventos programados
wp_clear_scheduled_hook('ctr_clear_cache');
wp_clear_scheduled_hook('ctr_check_for_updates');
