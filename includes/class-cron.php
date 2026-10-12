<?php
/**
 * فئة الكرون - جدولة التنظيف التلقائي للسجلات القديمة
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Cron
{
    const CLEANUP_HOOK = 'wzsm_daily_cleanup';
    // F-3/F-2: نبضة يومية للتقرير البريدي وإكمال ترحيل التطبيع
    const TICK_HOOK = 'wzsi_daily_tick';

    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action(self::CLEANUP_HOOK, array($this, 'run_cleanup'));
        add_action(self::TICK_HOOK, array($this, 'run_daily_tick'));
    }

    /**
     * جدولة المهام عند التفعيل
     */
    public static function schedule_events()
    {
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CLEANUP_HOOK);
        }
        if (!wp_next_scheduled(self::TICK_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::TICK_HOOK);
        }
    }

    /**
     * إلغاء الجدولات عند الإيقاف
     */
    public static function clear_schedules()
    {
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
        wp_clear_scheduled_hook(self::TICK_HOOK);
    }

    /**
     * F-2: النبضة اليومية تكمل ترحيل التطبيع إن كان معلقاً
     * (للمواقع الكبيرة التي لا يدخل مسؤولها لوحة التحكم كثيراً)
     */
    public function run_daily_tick()
    {
        if (!get_option('wzsi_norm_migration_done')) {
            require_once WZSI_PLUGIN_DIR . 'includes/class-database.php';
            WZSI_Database::maybe_migrate_normalized_terms();
        }
    }

    /**
     * تنفيذ التنظيف اليومي
     * يقرأ مدة الاحتفاظ من الإعدادات
     */
    public function run_cleanup()
    {
        $settings = get_option('wzsi_settings', array());
        if (empty($settings['auto_cleanup'])) {
            return;
        }

        $days = max(1, (int) ($settings['retention_days'] ?? 90));
        $deleted = WZSI_Database::cleanup_old_logs($days);

        // سجل في WP log إن أمكن
        if ($deleted > 0 && function_exists('error_log')) {
            error_log(sprintf('Woo Zero Search Insights: cleaned up %d old logs (older than %d days).', $deleted, $days));
        }
    }
}
