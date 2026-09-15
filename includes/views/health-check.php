<?php
/**
 * صفحة فحص الصحة
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can(WZSI_CAP)) wp_die(wzsi_t('notice_no_permission'));

$classes_status = array(
    'WZSI_i18n'              => class_exists('WZSI_i18n'),
    'WZSI_Core'              => class_exists('WZSI_Core'),
    'WZSI_Database'          => class_exists('WZSI_Database'),
    'WZSI_Tracker'           => class_exists('WZSI_Tracker'),
    'WZSI_Admin'              => class_exists('WZSI_Admin'),
    'WZSI_Settings'          => class_exists('WZSI_Settings'),
    'WZSI_Export'             => class_exists('WZSI_Export'),
    'WZSI_Cron'                => class_exists('WZSI_Cron'),
    'WZSI_Dashboard_Widget'  => class_exists('WZSI_Dashboard_Widget'),
    'WZSI_Webhooks'           => class_exists('WZSI_Webhooks'),
    'WZSI_Premium_UI'        => class_exists('WZSI_Premium_UI'),
);

$settings = get_option('wzsi_settings', array());

global $wpdb;
$table = $wpdb->prefix . WZSI_DB_TABLE;
$table_exists = !empty($wpdb->get_results("SHOW TABLES LIKE '{$table}'", ARRAY_N));
$row_count = $table_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") : 0;
?>
<div class="wrap wzsm-wrap">
    <h1>🩺 <?php echo esc_html(wzsi_t('menu_health_check')); ?></h1>
    <p class="wzsm-subtitle"><?php echo esc_html(sprintf('%s: %s', 'Version', WZSI_VERSION)); ?></p>

    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2>🏗️ Classes</h2>
        <table class="widefat striped">
            <thead><tr><th>Class</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($classes_status as $cls => $loaded) : ?>
                <tr><td><code><?php echo esc_html($cls); ?></code></td>
                <td><?php echo $loaded ? '✅' : '❌'; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2>⚙️ Settings</h2>
        <table class="form-table">
            <tr><th>Theme</th><td><code><?php echo esc_html($settings['theme'] ?? 'light'); ?></code></td></tr>
            <tr><th>UI Language</th><td><code><?php echo esc_html($settings['ui_lang'] ?? 'ar'); ?></code></td></tr>
            <tr><th>Tracking Enabled</th><td><code><?php echo !empty($settings['enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th>Slack Enabled</th><td><code><?php echo !empty($settings['slack_enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th>Discord Enabled</th><td><code><?php echo !empty($settings['discord_enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th>Widget Enabled</th><td><code><?php echo !empty($settings['dashboard_widget_enabled']) ? '1' : '0'; ?></code></td></tr>
        </table>
    </div>

    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2>🗄️ Database</h2>
        <table class="form-table">
            <tr><th>Table Name</th><td><code><?php echo esc_html($table); ?></code></td></tr>
            <tr><th>Table Exists</th><td><?php echo $table_exists ? '✅' : '❌'; ?></td></tr>
            <tr><th>Row Count</th><td><code><?php echo esc_html(number_format_i18n($row_count)); ?></code></td></tr>
        </table>
        <p><a href="<?php echo esc_url(home_url('/?s=healthcheck_' . wp_rand(1000, 9999) . '&post_type=product')); ?>" target="_blank" class="button button-primary">🔍 Test: Search for non-existent term</a></p>
    </div>
</div>
