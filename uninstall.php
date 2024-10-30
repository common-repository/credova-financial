<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('woocommerce_credova_settings');
delete_option('credova_db_version');

global $wpdb;
$table_name = $wpdb->prefix . 'credova_info';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
