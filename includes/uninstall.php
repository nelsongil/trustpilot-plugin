<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('ctr_api_url');
delete_option('ctr_reviews_title');
delete_option('ctr_reviews_count');
