<?php
/**
 * الكلاس الرئيسي للإضافة - ينسق بين باقي الكلاسات
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Core
{
    /**
     * Singleton instance
     *
     * @var WZSI_Core|null
     */
    private static $instance = null;

    /**
     * الحصول على Singleton instance
     *
     * @return WZSI_Core
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * المنشئ - يبدأ الكلاسات الأخرى
     */
    private function __construct()
    {
        // بدء المتتبع (يستقبل عمليات البحث)
        WZSI_Tracker::instance();

        // بدء الأدمين (لوحة التحكم) - فقط في لوحة الإدارة
        if (is_admin()) {
            WZSI_Admin::instance();
            WZSI_Settings::instance();
            WZSI_Export::instance();
        }

        // بدء مجدول الكرون
        WZSI_Cron::instance();

        // خطاف لإضافة معلومات في footer الأدمين
        add_action('in_admin_footer', array($this, 'maybe_add_admin_footer'));
    }

    /**
     * إضافة معلومات الإصدار في الـ footer (اختياري)
     * الإصلاح: كان الفحص يبحث عن 'wzsm' بينما معرفات الشاشات تحمل 'wzsi' — التذييل لم يكن يظهر أبدًا.
     * ويحترم الآن إعداد White-label (إخفاء العلامة التجارية).
     */
    public function maybe_add_admin_footer()
    {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'wzsi') !== false) {
            $settings = get_option('wzsi_settings', array());
            if (!empty($settings['white_label'])) {
                return; // White-label: إخفاء تذييل العلامة
            }
            echo '<p style="text-align:center; color:#666; margin-top:20px;">';
            printf(
                esc_html(wzsi_t('footer_made_by')),
                esc_html(WZSI_VERSION)
            );
            echo '</p>';
        }
    }
}
