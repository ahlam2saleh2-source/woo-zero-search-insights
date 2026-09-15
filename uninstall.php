<?php
/**
 * ملف الإزالة
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('wzsi_settings');
delete_option('wzsi_db_version');
delete_option('wzsi_installed_at');

wp_clear_scheduled_hook('wzsi_daily_cleanup');

global $wpdb;
$table = $wpdb->prefix . 'woo_zero_search_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table}");
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wzsi_%'");
delete_transient('wzsi_stats_cache');
