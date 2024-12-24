<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Eliminar opciones guardadas del plugin
delete_option('ctr_api_url');
delete_option('ctr_reviews_count');
