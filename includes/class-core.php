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

        // F-8: نقاط REST للقراءة فقط (wzsi/v1)
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // TD-3: أدوات الخصوصية (exporter/eraser)
        WZSI_Privacy::instance();

        // خطاف لإضافة معلومات في footer الأدمين
        add_action('in_admin_footer', array($this, 'maybe_add_admin_footer'));
    }

    /**
     * F-8: تسجيل نقاط REST للقراءة فقط
     * المصادقة القياسية لوردبريس (Application Passwords / Cookie) + صلاحية manage_woocommerce
     * الاستجابة بلا أي بيانات شخصية (مصطلحات وأرقام فقط — لا IP ولا user_agent)
     */
    public function register_rest_routes()
    {
        register_rest_route('wzsi/v1', '/stats', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_stats'),
            'permission_callback' => array($this, 'rest_permission'),
            'args'                => array(
                'days' => array('default' => 30, 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route('wzsi/v1', '/top-terms', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_top_terms'),
            'permission_callback' => array($this, 'rest_permission'),
            'args'                => array(
                'days'  => array('default' => 30, 'sanitize_callback' => 'absint'),
                'limit' => array('default' => 20, 'sanitize_callback' => 'absint'),
            ),
        ));
    }

    public function rest_permission()
    {
        return current_user_can(WZSI_CAP);
    }

    public function rest_stats($request)
    {
        $days = max(0, (int) $request->get_param('days'));
        return rest_ensure_response(WZSI_Database::get_stats($days));
    }

    public function rest_top_terms($request)
    {
        $days  = max(0, (int) $request->get_param('days'));
        $limit = min(100, max(1, (int) $request->get_param('limit')));
        return rest_ensure_response(WZSI_Database::get_top_zero_searches($limit, $days));
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
