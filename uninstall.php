<?php
/**
 * ملف الإزالة — تنظيف كامل لكل مواقع الشبكة
 *
 * الإصلاحات:
 *  - كان يمسح جدولة cron خاطئة (wzsi_daily_cleanup) بينما الخطاف الفعلي wzsm_daily_cleanup
 *    فتبقى مهمة يتيمة بعد حذف الإضافة.
 *  - كان لا ينظف خيار wzsi_capture_debug ولا transients الإشعارات.
 *  - يُنظّف الآن كل مواقع الشبكة (Multisite) كما تعلن الوثائق.
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * تنظيف موقع واحد (يُستدعى لكل موقع في الشبكة)
 */
function wzsi_uninstall_site()
{
    global $wpdb;

    // خيارات الإضافة
    delete_option('wzsi_settings');
    delete_option('wzsi_db_version');
    delete_option('wzsi_installed_at');
    delete_option('wzsi_capture_debug');

    // F-2: خيارات ترحيل التطبيع العربي + TD-1: مفتاح إصدار الكاش
    delete_option('wzsi_norm_migration_cursor');
    delete_option('wzsi_norm_migration_done');
    delete_option('wzsi_cache_ver');

    // جدولة التنظيف اليومي + النبضة اليومية (F-3)
    wp_clear_scheduled_hook('wzsm_daily_cleanup');
    wp_clear_scheduled_hook('wzsi_daily_tick');

    // جدول السجلات + جدول الربط الذكي (F-4)
    $table = $wpdb->prefix . 'woo_zero_search_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
    $maps = $wpdb->prefix . 'woo_zero_search_maps';
    $wpdb->query("DROP TABLE IF EXISTS {$maps}");

    // transients منع سبام الإشعارات (wzsi_wh_*) + كاش اللوحة (TD-1) + قفل الترحيل
    // صيغة LIKE بلا شرطات مُهرّبة — تعمل على MySQL وSQLite معًا
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '%wzsi_wh_%'
            OR option_name LIKE '%wzsi_cache_%'
            OR option_name LIKE '%wzsi_norm_migrating%'"
    );

    // transients قديمة عامة
    delete_transient('wzsi_stats_cache');
}

if (is_multisite()) {
    // تنظيف كل مواقع الشبكة (يتوافق مع تعلن "Multisite compatible")
    $site_ids = get_sites(array('fields' => 'ids', 'number' => 0));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        wzsi_uninstall_site();
        restore_current_blog();
    }
} else {
    wzsi_uninstall_site();
}

// usermeta مشتركة
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wzsi_%'");
