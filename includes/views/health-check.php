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
    'WZSI_Mappings'           => class_exists('WZSI_Mappings'),
    'WZSI_Email'              => class_exists('WZSI_Email'),
);

$settings = get_option('wzsi_settings', array());

global $wpdb;
$table = $wpdb->prefix . WZSI_DB_TABLE;
$table_exists = !empty($wpdb->get_results("SHOW TABLES LIKE '{$table}'", ARRAY_N));
$row_count = $table_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") : 0;

$debug_enabled = !empty($settings['capture_debug']);

// F-2: حالة ترحيل التطبيع العربي
$migration = class_exists('WZSI_Database') ? WZSI_Database::migration_status() : array('done' => true, 'remaining' => 0);

// F-4: جدول الربط
$maps_table = $wpdb->prefix . 'woo_zero_search_maps';
$maps_exists = !empty($wpdb->get_results("SHOW TABLES LIKE '{$maps_table}'", ARRAY_N));
$maps_count = $maps_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$maps_table}") : 0;
?>
<div class="wrap wzsm-wrap">
    <h1>🩺 <?php echo esc_html(wzsi_t('menu_health_check')); ?></h1>
    <p class="wzsm-subtitle"><?php echo esc_html(sprintf('%s: %s', wzsi_t('hc_version'), WZSI_VERSION)); ?></p>

    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2>🏗️ <?php echo esc_html(wzsi_t('hc_classes')); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php echo esc_html(wzsi_t('hc_class')); ?></th><th><?php echo esc_html(wzsi_t('hc_status')); ?></th></tr></thead>
            <tbody>
            <?php foreach ($classes_status as $cls => $loaded) : ?>
                <tr><td><code><?php echo esc_html($cls); ?></code></td>
                <td><?php echo $loaded ? '✅' : '❌'; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2>⚙️ <?php echo esc_html(wzsi_t('hc_settings')); ?></h2>
        <table class="form-table">
            <tr><th><?php echo esc_html(wzsi_t('hc_theme')); ?></th><td><code><?php echo esc_html($settings['theme'] ?? 'light'); ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_ui_lang')); ?></th><td><code><?php echo esc_html($settings['ui_lang'] ?? (function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar')); ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_tracking')); ?></th><td><code><?php echo !empty($settings['enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('field_exclude_bots')); ?></th><td><code><?php echo !empty($settings['exclude_bots']) ? '1' : '0'; ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('field_mapping_behavior')); ?></th><td><code><?php echo esc_html($settings['mapping_behavior'] ?? 'none'); ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_slack')); ?></th><td><code><?php echo !empty($settings['slack_enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_discord')); ?></th><td><code><?php echo !empty($settings['discord_enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th>Telegram</th><td><code><?php echo !empty($settings['telegram_enabled']) ? '1' : '0'; ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('section_digest')); ?></th><td><code><?php echo !empty($settings['digest_enabled']) ? esc_html($settings['digest_frequency'] ?? 'weekly') : '0'; ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('field_notify_min')); ?></th><td><code><?php echo esc_html((string) ($settings['notify_min_occurrences'] ?? 1)); ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_widget')); ?></th><td><code><?php echo !empty($settings['dashboard_widget_enabled']) ? '1' : '0'; ?></code></td></tr>
        </table>
    </div>

    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2>🗄️ <?php echo esc_html(wzsi_t('hc_database')); ?></h2>
        <table class="form-table">
            <tr><th><?php echo esc_html(wzsi_t('hc_table_name')); ?></th><td><code><?php echo esc_html($table); ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_table_exists')); ?></th><td><?php echo $table_exists ? '✅' : '❌'; ?></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_row_count')); ?></th><td><code><?php echo esc_html(number_format_i18n($row_count)); ?></code></td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_migration')); ?></th><td>
                <?php if (!empty($migration['done'])) : ?>
                    ✅ <?php echo esc_html(wzsi_t('hc_migration_done')); ?>
                <?php else : ?>
                    ⏳ <?php printf(esc_html(wzsi_t('hc_migration_pending')), number_format_i18n((int) $migration['remaining'])); ?>
                <?php endif; ?>
            </td></tr>
            <tr><th><?php echo esc_html(wzsi_t('hc_maps_table')); ?></th><td>
                <?php echo $maps_exists ? '✅' : '❌'; ?>
                <code><?php echo esc_html(number_format_i18n($maps_count)); ?></code>
            </td></tr>
        </table>
        <p><a href="<?php echo esc_url(home_url('/?s=healthcheck_' . wp_rand(1000, 9999) . '&post_type=product')); ?>" target="_blank" class="button button-primary"><?php echo esc_html(wzsi_t('hc_test_search')); ?></a></p>
    </div>

    <?php
    $debug_entries = get_option('wzsi_capture_debug', array());
    if (!is_array($debug_entries)) {
        $debug_entries = array();
    }
    ?>
    <div class="wzsm-stat-card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
        <h2><?php echo esc_html(wzsi_t('hc_debug_title')); ?></h2>
        <?php if (!$debug_enabled) : ?>
            <p style="color:#8a6d3b; background:#fff8e5; border:1px solid #f0d38e; padding:10px; border-radius:6px;">
                ℹ️ <?php echo esc_html(wzsi_t('hc_debug_disabled')); ?>
            </p>
        <?php elseif (empty($debug_entries)) : ?>
            <p><?php echo esc_html(wzsi_t('hc_debug_empty')); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html(wzsi_t('hc_col_time')); ?></th>
                        <th><?php echo esc_html(wzsi_t('hc_col_source')); ?></th>
                        <th><?php echo esc_html(wzsi_t('hc_col_term')); ?></th>
                        <th><?php echo esc_html(wzsi_t('hc_col_results')); ?></th>
                        <th><?php echo esc_html(wzsi_t('hc_col_decision')); ?></th>
                        <th><?php echo esc_html(wzsi_t('hc_col_details')); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($debug_entries as $entry) : ?>
                    <tr>
                        <td><code><?php echo esc_html($entry['t']); ?></code></td>
                        <td><code><?php echo esc_html($entry['src']); ?></code></td>
                        <td><?php echo esc_html($entry['term']); ?></td>
                        <td><?php echo esc_html((string) $entry['found']); ?></td>
                        <td><?php echo ('logged' === $entry['do']) ? esc_html(wzsi_t('hc_logged')) : esc_html(wzsi_t('hc_skipped')); ?></td>
                        <td><?php echo esc_html($entry['why']); ?><?php echo !empty($entry['uri']) ? '<br><code style="font-size:10px;">' . esc_html($entry['uri']) . '</code>' : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="color:#666; font-size:11px;">
                <?php echo esc_html(wzsi_t('hc_debug_note')); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
