<?php
/**
 * نظام الترجمة المخصص - يحترم إعداد ui_lang
 * يحوي كل نصوص الواجهة بالعربية والإنجليزية
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_i18n
{
    private static $instance = null;
    private $strings = array();
    private $current_lang = 'ar';

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $settings = get_option('wzsi_settings', array());
        $this->current_lang = isset($settings['ui_lang']) ? $settings['ui_lang'] : (function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar');
        $this->load_strings();
    }

    private function load_strings()
    {
        $this->strings = array(
            // === القائمة الرئيسية ===
            'menu_zero_search' => array('ar' => 'Zero Search', 'en' => 'Zero Search'),
            'menu_dashboard' => array('ar' => 'لوحة المعلومات', 'en' => 'Dashboard'),
            'menu_logs' => array('ar' => 'السجلات', 'en' => 'Logs'),
            'menu_settings' => array('ar' => 'الإعدادات', 'en' => 'Settings'),
            'menu_health_check' => array('ar' => 'فحص الصحة', 'en' => 'Health Check'),

            // === عناوين الصفحات ===
            'page_dashboard' => array('ar' => 'Woo Zero Search Insights', 'en' => 'Woo Zero Search Insights'),
            'page_logs' => array('ar' => 'سجلات عمليات البحث', 'en' => 'Search Logs'),
            'page_settings' => array('ar' => 'إعدادات Woo Zero Search Insights', 'en' => 'Woo Zero Search Insights Settings'),
            'page_health' => array('ar' => 'فحص الصحة', 'en' => 'Health Check'),

            // === الوصف العام ===
            'subtitle_dashboard' => array(
                'ar' => 'اكتشف ما يبحث عنه عملاؤك دون أن يجدوه - بيانات حقيقية تساعدك على تحسين متجرك ومخزونك.',
                'en' => 'Discover what your customers search for but cannot find - real data to improve your store.'
            ),
            'subtitle_logs' => array(
                'ar' => 'عرض جميع عمليات البحث المسجلة، يمكن التبديل بين عرض الكل وعرض فقط بدون نتائج.',
                'en' => 'View all logged searches. Toggle between all searches and zero-result only.'
            ),
            'subtitle_settings' => array(
                'ar' => 'تحكم في كيفية تتبع عمليات البحث وحفظها.',
                'en' => 'Control how searches are tracked and stored.'
            ),

            // === بطاقات الإحصائيات ===
            'stat_total_searches' => array('ar' => 'إجمالي عمليات البحث', 'en' => 'Total Searches'),
            'stat_zero_results' => array('ar' => 'عمليات بلا نتائج', 'en' => 'Zero-Result Searches'),
            'stat_failure_rate' => array('ar' => 'معدل الفشل', 'en' => 'Failure Rate'),
            'stat_opportunities' => array('ar' => 'فرص تحسين', 'en' => 'Improvement Opportunities'),
            'stat_sub_total' => array('ar' => 'مصطلح فريد', 'en' => 'unique terms'),
            'stat_sub_zero' => array('ar' => 'مصطلح فريد بلا نتائج', 'en' => 'unique zero terms'),
            'stat_sub_rate' => array('ar' => 'نسبة عمليات البحث التي لم تجد نتائج', 'en' => 'percentage of searches with no results'),
            'stat_sub_opp' => array('ar' => 'مصطلحات يمكنك إضافة منتجات لها', 'en' => 'terms you can add products for'),

            // === أقسام ===
            'section_top_zero' => array('ar' => 'أعلى عمليات البحث بلا نتائج', 'en' => 'Top Zero-Result Searches'),
            'section_trend' => array('ar' => 'اتجاه عمليات البحث', 'en' => 'Search Trend'),
            'section_quick_actions' => array('ar' => 'إجراءات سريعة', 'en' => 'Quick Actions'),
            'section_general' => array('ar' => 'الإعدادات العامة', 'en' => 'General Settings'),
            'section_ui' => array('ar' => 'الواجهة', 'en' => 'Interface'),

            // === أعمدة الجداول ===
            'col_id' => array('ar' => '#', 'en' => '#'),
            'col_term' => array('ar' => 'مصطلح البحث', 'en' => 'Search Term'),
            'col_count' => array('ar' => 'مرات البحث', 'en' => 'Search Count'),
            'col_unique' => array('ar' => 'فريدة', 'en' => 'Unique'),
            'col_last_seen' => array('ar' => 'آخر مرة', 'en' => 'Last Seen'),
            'col_action' => array('ar' => 'إجراء', 'en' => 'Action'),
            'col_results' => array('ar' => 'نتائج', 'en' => 'Results'),
            'col_type' => array('ar' => 'نوع', 'en' => 'Type'),
            'col_user' => array('ar' => 'المستخدم', 'en' => 'User'),
            'col_date' => array('ar' => 'التاريخ', 'en' => 'Date'),
            'col_repeat' => array('ar' => 'مكرر', 'en' => 'Repeat'),

            // === الأزرار ===
            'btn_view_all_logs' => array('ar' => 'عرض كل السجلات', 'en' => 'View All Logs'),
            'btn_export_csv' => array('ar' => 'تصدير CSV', 'en' => 'Export CSV'),
            'btn_clear_all' => array('ar' => 'مسح الكل', 'en' => 'Clear All'),
            'btn_delete' => array('ar' => 'حذف', 'en' => 'Delete'),
            'btn_view_logs' => array('ar' => 'عرض السجلات', 'en' => 'View Logs'),
            'btn_settings' => array('ar' => 'الإعدادات', 'en' => 'Settings'),
            'btn_save' => array('ar' => 'حفظ الإعدادات', 'en' => 'Save Settings'),
            'btn_yes_enable' => array('ar' => 'نعم، فعّل', 'en' => 'Yes, enable'),

            // === الحقول ===
            'field_period' => array('ar' => 'الفترة:', 'en' => 'Period:'),
            'field_enabled' => array('ar' => 'تفعيل التتبع', 'en' => 'Enable Tracking'),
            'field_track_logged_in' => array('ar' => 'تتبع المستخدمين المسجلين', 'en' => 'Track Logged-in Users'),
            'field_track_anonymous' => array('ar' => 'تتبع الزوار', 'en' => 'Track Anonymous Visitors'),
            'field_ip_anonymize' => array('ar' => 'إخفاء آخر IP للمستخدم (للخصوصية)', 'en' => 'Anonymize user IP (GDPR)'),
            'field_auto_cleanup' => array('ar' => 'تنظيف السجلات القديمة تلقائياً', 'en' => 'Auto-cleanup old logs'),
            'field_retention' => array('ar' => 'مدة الاحتفاظ (أيام)', 'en' => 'Retention period (days)'),
            'field_min_length' => array('ar' => 'الحد الأدنى لطول المصطلح', 'en' => 'Minimum term length'),
            'field_max_length' => array('ar' => 'الحد الأقصى لطول المصطلح', 'en' => 'Maximum term length'),
            'field_excluded' => array('ar' => 'مصطلحات مستثناة (كل مصطلح في سطر)', 'en' => 'Excluded terms (one per line)'),
            'field_theme' => array('ar' => 'الموضوع (Theme)', 'en' => 'Theme'),
            'field_language' => array('ar' => 'لغة الواجهة', 'en' => 'Interface Language'),
            'field_dark_mode' => array('ar' => 'الوضع الداكن', 'en' => 'Dark Mode'),
            'field_widget' => array('ar' => 'إظهار Widget', 'en' => 'Show Widget'),
            'field_white_label' => array('ar' => 'إزالة العلامة التجارية', 'en' => 'Remove branding'),

            // === المواضيع ===
            'theme_light' => array('ar' => 'فاتح', 'en' => 'Light'),
            'theme_dark' => array('ar' => 'داكن', 'en' => 'Dark'),
            'theme_ocean' => array('ar' => 'محيط', 'en' => 'Ocean'),
            'theme_forest' => array('ar' => 'غابة', 'en' => 'Forest'),
            'theme_sunset' => array('ar' => 'غروب', 'en' => 'Sunset'),
            'theme_midnight' => array('ar' => 'منتصف الليل', 'en' => 'Midnight'),

            // === اللغات ===
            'lang_arabic' => array('ar' => 'العربية', 'en' => 'Arabic'),
            'lang_english' => array('ar' => 'الإنجليزية', 'en' => 'English'),

            // === الفلاتر ===
            'filter_7days' => array('ar' => 'آخر 7 أيام', 'en' => 'Last 7 days'),
            'filter_30days' => array('ar' => 'آخر 30 يوم', 'en' => 'Last 30 days'),
            'filter_90days' => array('ar' => 'آخر 90 يوم', 'en' => 'Last 90 days'),
            'filter_year' => array('ar' => 'آخر سنة', 'en' => 'Last year'),
            'filter_all' => array('ar' => 'الكل', 'en' => 'All'),
            'filter_zero_only' => array('ar' => 'بلا نتائج', 'en' => 'Zero only'),
            'filter_all_logs' => array('ar' => 'كل السجلات', 'en' => 'All logs'),

            // === الحالات الفارغة ===
            'empty_no_data' => array('ar' => 'لا توجد بيانات بعد', 'en' => 'No data yet'),
            'empty_no_logs' => array('ar' => 'لا توجد سجلات بعد', 'en' => 'No logs yet'),
            'empty_no_data_desc' => array(
                'ar' => 'بمجرد أن يبحث عملاؤك عن مصطلحات غير موجودة في متجرك، ستظهر هنا.',
                'en' => 'When customers search for non-existent products, they will appear here.'
            ),
            'empty_no_logs_desc' => array(
                'ar' => 'عمليات البحث المسجلة ستظهر هنا تلقائياً.',
                'en' => 'Logged searches will appear here automatically.'
            ),

            // === رسائل الأكشن ===
            'msg_confirm_delete_all' => array('ar' => 'هل أنت متأكد من حذف جميع السجلات؟', 'en' => 'Are you sure you want to delete all logs?'),
            'msg_confirm_delete_term' => array('ar' => 'سيتم حذف جميع سجلات هذا المصطلح. متابعة؟', 'en' => 'All logs for this term will be deleted. Continue?'),
            'msg_confirm_export' => array('ar' => 'سيتم تصدير السجلات بصيغة CSV. متابعة؟', 'en' => 'Logs will be exported as CSV. Continue?'),
            'msg_loading' => array('ar' => 'جارٍ التحميل...', 'en' => 'Loading...'),
            'msg_no_data' => array('ar' => 'لا توجد بيانات', 'en' => 'No data'),

            // === وصف الحقول ===
            'desc_excluded' => array(
                'ar' => 'أدخل كلمة أو جملة في كل سطر. أي مصطلح يحتوي على هذه الكلمات لن يُسجل.',
                'en' => 'Enter one word or phrase per line. Any term containing these words will not be logged.'
            ),
            'desc_theme' => array(
                'ar' => 'اختر الموضوع. أو اضغط زر التبديل 💡 في أسفل الشاشة للتنقل بين المواضيع الستة.',
                'en' => 'Choose a theme. Or click the 💡 button at the bottom to cycle through 6 themes.'
            ),
            'desc_language' => array(
                'ar' => 'اختر لغة واجهة الإضافة - كل النصوص ستتغير فوراً.',
                'en' => 'Choose interface language - all texts will change instantly.'
            ),
            'desc_white_label' => array(
                'ar' => 'إخفاء اسم az-soft4media من كل الواجهات.',
                'en' => 'Hide az-soft4media brand from all interfaces.'
            ),

            // === وصف الجداول ===
            'log_table_subtitle' => array(
                'ar' => 'قراءة سريعة لكل طريقة عبر 5 محاور قرار أساسية',
                'en' => 'Quick comparison across 5 key dimensions'
            ),
            'logs_table_empty' => array(
                'ar' => 'السوق خالٍ من إضافة مستقلة تحل هذه المشكلة.',
                'en' => 'The market is empty of a standalone plugin solving this problem.'
            ),

            // === الوصف العام ===
            'plugin_description' => array(
                'ar' => 'تتبع عمليات البحث في WooCommerce التي لا تُرجع نتائج، مع لوحة إحصائيات احترافية، تصدير CSV، Dashboard Widget، إشعارات Slack/Discord، شريط تنقل Tabs، Dark Mode، White-label.',
                'en' => 'Track WooCommerce zero-result searches with professional dashboard, CSV export, Dashboard Widget, Slack/Discord notifications, Tabs navigation, Dark Mode, White-label.'
            ),

            // === تذييل ===
            'footer_made_by' => array(
                'ar' => 'Woo Zero Search Insights — الإصدار %s | صنع بحب لمجتمع WooCommerce العربي',
                'en' => 'Woo Zero Search Insights — Version %s | Made with love for the WooCommerce community'
            ),

            // === رسائل التثبيت ===
            'notice_installed' => array(
                'ar' => 'تم تفعيل Woo Zero Search Insights بنجاح. ابدأ بمتابعة عمليات البحث في',
                'en' => 'Woo Zero Search Insights activated successfully. Start monitoring searches in'
            ),
            'notice_woocommerce_required' => array(
                'ar' => 'Woo Zero Search Insights يتطلب تفعيل WooCommerce أولاً. فعّل WooCommerce ثم أعد تفعيل هذه الإضافة.',
                'en' => 'Woo Zero Search Insights requires WooCommerce to be active. Activate WooCommerce first, then re-activate this plugin.'
            ),
            'notice_no_permission' => array('ar' => 'ليس لديك صلاحية.', 'en' => 'You do not have permission.'),
            'notice_invalid_nonce' => array('ar' => 'رمز الأمان غير صالح.', 'en' => 'Invalid security token.'),
            'notice_logs_cleared' => array('ar' => 'تم حذف جميع السجلات.', 'en' => 'All logs have been deleted.'),

            // === ربط الإضافة ===
            'link_settings' => array('ar' => 'الإعدادات', 'en' => 'Settings'),
            'link_dashboard' => array('ar' => 'لوحة المعلومات', 'en' => 'Dashboard'),

            // === تنبيهات قابلة للترجمة ===
            'alert_term_deleted' => array('ar' => 'تم حذف المصطلح بنجاح', 'en' => 'Term deleted successfully'),
            'alert_delete_failed' => array('ar' => 'فشل الحذف', 'en' => 'Delete failed'),
            'alert_network_error' => array('ar' => 'خطأ في الاتصال', 'en' => 'Network error'),
            'alert_cleared' => array('ar' => 'تم بنجاح', 'en' => 'Done successfully'),
            'alert_copied' => array('ar' => 'تم النسخ', 'en' => 'Copied'),
            'alert_theme_changed' => array('ar' => 'تم التبديل للموضوع', 'en' => 'Switched to theme'),

            // === التبويب ===
            'tab_zero_only' => array('ar' => '❌ بلا نتائج', 'en' => '❌ Zero only'),
            'tab_all_logs' => array('ar' => '📊 كل السجلات', 'en' => '📊 All logs'),
            'tab_export_this_list' => array('ar' => '⬇ تصدير هذه القائمة (CSV)', 'en' => '⬇ Export this list (CSV)'),

            // === خيارات متنوعة ===
            'guest' => array('ar' => 'زائر', 'en' => 'Guest'),
            'next' => array('ar' => 'التالي', 'en' => 'Next'),
            'prev' => array('ar' => 'السابق', 'en' => 'Previous'),
            'click_to_copy' => array('ar' => 'انقر للنسخ', 'en' => 'Click to copy'),

            // === الصفحة الرئيسية Dashboard ===
            'home_make_money_desc' => array(
                'ar' => 'اكتشف ما يبحث عنه عملاؤك دون أن يجدوه. تسجّل الإضافة عمليات البحث في WooCommerce التي لا تُرجع نتائج، مع لوحة إحصائيات احترافية وتصدير CSV وميزات تنظيف تلقائي.',
                'en' => 'Discover what your customers search for but cannot find. Track WooCommerce zero-result searches with professional dashboard, CSV export, and auto-cleanup.'
            ),
        );
    }

    /**
     * ترجمة مفتاح
     */
    public function t($key)
    {
        if (!isset($this->strings[$key])) {
            return $key;
        }
        $lang = $this->current_lang;
        if (!isset($this->strings[$key][$lang])) {
            $lang = 'ar';
        }
        return $this->strings[$key][$lang];
    }

    public function get_lang()
    {
        return $this->current_lang;
    }

    public function is_arabic()
    {
        return $this->current_lang === 'ar';
    }
}

/**
 * دالة مساعدة سريعة
 */
function wzsi_t($key)
{
    return WZSI_i18n::instance()->t($key);
}
