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
            'stat_sub_total' => array('ar' => '%s مصطلح فريد', 'en' => '%s unique terms'),
            'stat_sub_zero' => array('ar' => '%s مصطلح فريد بلا نتائج', 'en' => '%s unique zero terms'),
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
            'field_premium_ui' => array('ar' => 'واجهة Premium (Tabs وBreadcrumbs)', 'en' => 'Premium UI (Tabs & Breadcrumbs)'),
            'field_capture_debug' => array('ar' => 'سجل الالتقاط التشخيصي (للمطوّرين)', 'en' => 'Capture debug trail (developers)'),

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
            'desc_premium_ui' => array(
                'ar' => 'شريط التبويبات العلوي وBreadcrumbs في صفحات الإضافة.',
                'en' => 'Top tabs bar and breadcrumbs across plugin pages.'
            ),
            'desc_capture_debug' => array(
                'ar' => 'يُظهر محاولات الالتقاط في صفحة فحص الصحة — فعّله فقط للاستكشاف، ثم عطّله.',
                'en' => 'Shows capture attempts on the Health Check page — enable only for debugging, then disable.'
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

            // === رسائل AJAX والتصدير ===
            'ajax_no_permission' => array('ar' => 'لا صلاحية.', 'en' => 'No permission.'),
            'ajax_logs_cleared' => array('ar' => 'تم مسح كل السجلات.', 'en' => 'All logs cleared.'),
            'ajax_invalid_term' => array('ar' => 'مصطلح غير صالح.', 'en' => 'Invalid term.'),
            'ajax_term_deleted' => array('ar' => 'تم حذف %d سجل.', 'en' => '%d log(s) deleted.'),

            // === أعمدة تصدير CSV ===
            'csv_term' => array('ar' => 'مصطلح البحث', 'en' => 'Search Term'),
            'csv_results' => array('ar' => 'عدد النتائج', 'en' => 'Results Count'),
            'csv_date' => array('ar' => 'تاريخ البحث', 'en' => 'Search Date'),
            'csv_last' => array('ar' => 'آخر ظهور', 'en' => 'Last Seen'),
            'csv_repeat' => array('ar' => 'عدد التكرار', 'en' => 'Repeat Count'),
            'csv_user' => array('ar' => 'معرف المستخدم', 'en' => 'User ID'),
            'csv_ip' => array('ar' => 'عنوان IP', 'en' => 'IP Address'),
            'csv_lang' => array('ar' => 'اللغة', 'en' => 'Language'),
            'csv_type' => array('ar' => 'نوع الطلب', 'en' => 'Request Type'),
            'csv_referer' => array('ar' => 'المرجع', 'en' => 'Referrer'),

            // === إشعارات Slack/Discord ===
            'webhook_title' => array('ar' => 'بحث بدون نتائج', 'en' => 'Zero-result search'),
            'webhook_searched_for' => array('ar' => 'تم البحث عن', 'en' => 'Customer searched for'),
            'webhook_no_results' => array('ar' => 'ولم تُرجع أي نتائج', 'en' => 'with no results found'),

            // === صفحة فحص الصحة ===
            'hc_version' => array('ar' => 'الإصدار', 'en' => 'Version'),
            'hc_classes' => array('ar' => 'الكلاسات', 'en' => 'Classes'),
            'hc_class' => array('ar' => 'الكلاس', 'en' => 'Class'),
            'hc_status' => array('ar' => 'الحالة', 'en' => 'Status'),
            'hc_settings' => array('ar' => 'الإعدادات', 'en' => 'Settings'),
            'hc_theme' => array('ar' => 'الموضوع', 'en' => 'Theme'),
            'hc_ui_lang' => array('ar' => 'لغة الواجهة', 'en' => 'UI Language'),
            'hc_tracking' => array('ar' => 'التتبع مفعّل', 'en' => 'Tracking Enabled'),
            'hc_slack' => array('ar' => 'Slack مفعّل', 'en' => 'Slack Enabled'),
            'hc_discord' => array('ar' => 'Discord مفعّل', 'en' => 'Discord Enabled'),
            'hc_widget' => array('ar' => 'Widget مفعّل', 'en' => 'Widget Enabled'),
            'hc_database' => array('ar' => 'قاعدة البيانات', 'en' => 'Database'),
            'hc_table_name' => array('ar' => 'اسم الجدول', 'en' => 'Table Name'),
            'hc_table_exists' => array('ar' => 'الجدول موجود', 'en' => 'Table Exists'),
            'hc_row_count' => array('ar' => 'عدد السجلات', 'en' => 'Row Count'),
            'hc_test_search' => array('ar' => '🔍 اختبار: ابحث عن مصطلح غير موجود', 'en' => '🔍 Test: Search for a non-existent term'),
            'hc_debug_title' => array('ar' => '🔎 سجل الالتقاط التشخيصي', 'en' => '🔎 Capture Debug Trail'),
            'hc_debug_disabled' => array(
                'ar' => 'سجل التشخيص معطّل افتراضيًا حفاظًا على الأداء. فعّله من الإعدادات (سجل الالتقاط التشخيصي) ثم قم ببحث تجريبي.',
                'en' => 'Debug trail is disabled by default for performance. Enable it in Settings (Capture debug trail), then run a test search.'
            ),
            'hc_debug_empty' => array(
                'ar' => 'لا توجد محاولات التقاط مسجلة بعد — قم بعملية بحث من واجهة المتجر ثم أعد تحميل هذه الصفحة.',
                'en' => 'No capture attempts yet — run a search from the storefront and reload this page.'
            ),
            'hc_debug_note' => array(
                'ar' => 'سجل تشخيصي مؤقت (آخر 25 محاولة فقط). إن بحثت من المتجر ولم يظهر أي سطر فالطلب لم يصل إلى PHP (كاش خادم) أو استخدم مسارًا غير معروف.',
                'en' => 'Temporary debug trail (last 25 attempts). If you searched but see no rows, the request never reached PHP (server cache) or used an unknown path.'
            ),
            'hc_col_time' => array('ar' => 'الوقت', 'en' => 'Time'),
            'hc_col_source' => array('ar' => 'المصدر', 'en' => 'Source'),
            'hc_col_term' => array('ar' => 'المصطلح', 'en' => 'Term'),
            'hc_col_results' => array('ar' => 'النتائج', 'en' => 'Results'),
            'hc_col_decision' => array('ar' => 'القرار', 'en' => 'Decision'),
            'hc_col_details' => array('ar' => 'التفاصيل', 'en' => 'Details'),
            'hc_logged' => array('ar' => '✅ سُجّل', 'en' => '✅ Logged'),
            'hc_skipped' => array('ar' => '⏭️ تخطّي', 'en' => '⏭️ Skipped'),

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

            // ═══════════ v1.1.0 ═══════════

            // === F-4: الربط الذكي ===
            'menu_mappings' => array('ar' => 'الربط الذكي', 'en' => 'Smart Mappings'),
            'map_page_subtitle' => array(
                'ar' => 'اربط المصطلحات التي لم يجد لها عملاؤك نتائج بمنتج أو تصنيف موجود — فيظهر للزائر بدل صفحة فارغة.',
                'en' => 'Map zero-result search terms to an existing product or category — so visitors see results instead of an empty page.'
            ),
            'map_page_behavior_hint' => array('ar' => 'سلوك الربط الحالي في واجهة المتجر:', 'en' => 'Current storefront mapping behavior:'),
            'map_add_heading' => array('ar' => 'الربطات الحالية', 'en' => 'Current mappings'),
            'map_btn_new' => array('ar' => 'إضافة ربط جديد', 'en' => 'Add new mapping'),
            'map_empty_title' => array('ar' => 'لا ربطات بعد', 'en' => 'No mappings yet'),
            'map_empty_desc' => array(
                'ar' => 'افتح لوحة المعلومات واضغط زر "ربط" بجانب أي مصطلح بلا نتائج، أو أضف ربطاً جديداً من هنا.',
                'en' => 'Open the dashboard and click "Map" next to any zero-result term, or add a new mapping here.'
            ),
            'map_modal_title' => array('ar' => 'ربط المصطلح بهدف', 'en' => 'Map term to a target'),
            'map_modal_desc' => array(
                'ar' => 'عندما يبحث زائر عن هذا المصطلح ولم يجد نتائج، سيظهر له هدف الربط حسب سلوك الربط المختار.',
                'en' => 'When a visitor searches this term with no results, the mapping target will be shown per the chosen behavior.'
            ),
            'map_term_label' => array('ar' => 'مصطلح البحث', 'en' => 'Search term'),
            'map_target_type' => array('ar' => 'نوع الهدف', 'en' => 'Target type'),
            'map_type_product' => array('ar' => 'منتج', 'en' => 'Product'),
            'map_type_category' => array('ar' => 'تصنيف منتجات', 'en' => 'Product category'),
            'map_type_url' => array('ar' => 'رابط خارجي', 'en' => 'External URL'),
            'map_search_label' => array('ar' => 'ابحث واختر المنتج/التصنيف', 'en' => 'Search & pick product/category'),
            'map_search_placeholder' => array('ar' => 'اكتب حرفين على الأقل…', 'en' => 'Type at least 2 characters…'),
            'map_url_label' => array('ar' => 'الرابط', 'en' => 'URL'),
            'map_btn_save' => array('ar' => 'حفظ الربط', 'en' => 'Save mapping'),
            'map_btn_cancel' => array('ar' => 'إلغاء', 'en' => 'Cancel'),
            'map_btn_link' => array('ar' => 'ربط', 'en' => 'Map'),
            'map_btn_activate' => array('ar' => 'تفعيل', 'en' => 'Activate'),
            'map_btn_pause' => array('ar' => 'إيقاف', 'en' => 'Pause'),
            'map_col_target' => array('ar' => 'الهدف', 'en' => 'Target'),
            'map_col_type' => array('ar' => 'النوع', 'en' => 'Type'),
            'map_col_hits' => array('ar' => 'استفادات', 'en' => 'Uses'),
            'map_col_status' => array('ar' => 'الحالة', 'en' => 'Status'),
            'status_active' => array('ar' => 'مفعّل', 'en' => 'Active'),
            'status_inactive' => array('ar' => 'موقوف', 'en' => 'Paused'),
            'map_saved' => array('ar' => 'تم حفظ الربط — سيعمل للزوار حسب سلوك الربط', 'en' => 'Mapping saved — it will apply to visitors per the mapping behavior'),
            'map_failed' => array('ar' => 'فشل حفظ الربط', 'en' => 'Failed to save mapping'),
            'map_error_term' => array('ar' => 'اكتب مصطلح البحث أولاً', 'en' => 'Enter the search term first'),
            'map_error_target' => array('ar' => 'اختر منتجاً أو تصنيفاً من نتائج البحث', 'en' => 'Pick a product or category from the results'),
            'map_error_url' => array('ar' => 'أدخل الرابط أولاً', 'en' => 'Enter the URL first'),
            'field_mapping_behavior' => array('ar' => 'سلوك الربط في واجهة المتجر', 'en' => 'Storefront mapping behavior'),
            'map_behavior_none' => array('ar' => 'بدون تغيير (الافتراضي — آمن)', 'en' => 'No change (default — safe)'),
            'map_behavior_add_result' => array('ar' => 'إضافة المنتج للنتائج عند الصفرية', 'en' => 'Add mapped product to empty results'),
            'map_behavior_redirect' => array('ar' => 'تحويل الزائر للهدف مباشرة (302)', 'en' => 'Redirect visitor to the target (302)'),
            'section_mapping' => array('ar' => 'الربط الذكي (مصطلح ← منتج)', 'en' => 'Smart Mappings (term → product)'),
            'desc_mapping_behavior' => array(
                'ar' => 'حوّل المصطلحات الصفرية إلى نتائج: اربط المصطلح بمنتج أو تصنيف من صفحة "الربط الذكي" ثم اختر سلوكه في المتجر. الافتراضي بلا أي تغيير على نتائج البحث.',
                'en' => 'Turn zero terms into results: map a term to a product/category from the Smart Mappings page, then choose its storefront behavior. Default changes nothing.'
            ),
            'desc_mapping_options' => array(
                'ar' => 'يؤثر فقط على البحوث الصفرية في نطاق المنتجات، ويزيد عداد الاستفادات عند كل استفادة فعلية.',
                'en' => 'Affects only zero-result product searches, and counts a "use" every time it actually helps.'
            ),

            // === F-3: التقرير البريدي ===
            'section_digest' => array('ar' => 'التقرير البريدي الدوري', 'en' => 'Scheduled Email Digest'),
            'desc_digest' => array(
                'ar' => 'ملخص يصل بريدك بالأرقام وأعلى المصطلحات الصفرية دون فتح لوحة التحكم. يعتمد على نبضة WP-Cron اليومية — للاستضافات الجادة فعّل cron حقيقياً.',
                'en' => 'A summary straight to your inbox with stats and top zero terms — no dashboard visits needed. Runs on a daily WP-Cron tick; enable a real cron on serious hosts.'
            ),
            'field_digest_enabled' => array('ar' => 'تفعيل التقرير البريدي', 'en' => 'Enable email digest'),
            'field_digest_frequency' => array('ar' => 'تردد التقرير', 'en' => 'Digest frequency'),
            'field_digest_email' => array('ar' => 'بريد استلام التقرير', 'en' => 'Digest recipient email'),
            'digest_weekly' => array('ar' => 'أسبوعي (آخر 7 أيام)', 'en' => 'Weekly (last 7 days)'),
            'digest_daily' => array('ar' => 'يومي (آخر 24 ساعة)', 'en' => 'Daily (last 24 hours)'),
            'digest_send_test' => array('ar' => 'إرسال تقرير تجريبي الآن', 'en' => 'Send a test digest now'),
            'digest_test_ok' => array('ar' => 'أُرسل التقرير التجريبي — افحص صندوق البريد (ومجلد Spam)', 'en' => 'Test digest sent — check the inbox (and Spam folder)'),
            'digest_test_fail' => array('ar' => 'فشل إرسال التقرير — تحقق من البريد المدخل وإعدادات بريد الموقع', 'en' => 'Digest failed — check the entered email and the site mail settings'),
            'digest_invalid_email' => array('ar' => 'بريد غير صالح — أضف بريد التقرير في الإعدادات أولاً', 'en' => 'Invalid email — set the digest recipient in settings first'),
            'digest_sent_ok' => array('ar' => 'أُرسل التقرير إلى %s', 'en' => 'Digest sent to %s'),
            'digest_sent_fail' => array('ar' => 'فشل الإرسال — تحقق من إعدادات بريد الموقع', 'en' => 'Sending failed — check the site mail settings'),
            'digest_subject' => array('ar' => 'تقرير البحث الصفري', 'en' => 'Zero-Search Report'),
            'digest_title' => array('ar' => 'تقرير البحث الصفري', 'en' => 'Zero-Search Report'),
            'digest_hello' => array('ar' => 'مرحباً،', 'en' => 'Hello,'),
            'digest_last_24h' => array('ar' => 'إحصاءات آخر 24 ساعة في متجرك:', 'en' => 'Last 24 hours in your store:'),
            'digest_last_7d' => array('ar' => 'إحصاءات آخر 7 أيام في متجرك:', 'en' => 'Last 7 days in your store:'),
            'digest_top_heading' => array('ar' => 'أعلى 10 مصطلحات بلا نتائج', 'en' => 'Top 10 zero-result terms'),
            'digest_delta_vs_prev' => array('ar' => 'مقارنة بالفترة السابقة', 'en' => 'vs previous period'),
            'digest_no_terms' => array('ar' => 'لا مصطلحات صفرية في هذه الفترة — عملاؤك يجدون ما يبحثون عنه!', 'en' => 'No zero-result terms this period — your customers are finding what they need!'),
            'digest_cta' => array('ar' => 'افتح لوحة المعلومات', 'en' => 'Open dashboard'),
            'digest_footer' => array('ar' => 'أُرسل بواسطة Woo Zero Search Insights v%s', 'en' => 'Sent by Woo Zero Search Insights v%s'),

            // === F-7: عتبة الإشعارات + Telegram ===
            'field_notify_min' => array('ar' => 'عتبة الإشعار (تكرارات المصطلح خلال 24 ساعة)', 'en' => 'Notification threshold (term repeats in 24h)'),
            'field_telegram_token' => array('ar' => 'Telegram Bot Token', 'en' => 'Telegram Bot Token'),
            'field_telegram_chat' => array('ar' => 'Telegram Chat ID', 'en' => 'Telegram Chat ID'),
            'desc_test_buttons' => array(
                'ar' => 'زر التجربة يرسل رسالة فعلية للقناة ويعرض نجاح/فشل الاستجابة — أسرع طريقة للتحقق من الروابط والتوكنات.',
                'en' => 'The test button sends a real message and reports success/failure — the fastest way to verify URLs and tokens.'
            ),
            'btn_test' => array('ar' => 'اختبار القنوات', 'en' => 'Channel tests'),
            'msg_test_ok' => array('ar' => 'تم الإرسال بنجاح — افحص القناة', 'en' => 'Sent successfully — check the channel'),
            'msg_test_fail' => array('ar' => 'فشل الإرسال — تحقق من الرابط/التوكن واتصال الموقع بالخارج', 'en' => 'Send failed — check the URL/token and the site outbound connection'),
            'msg_test_no_url' => array('ar' => 'أدخل رابط/توكن القناة واحفظ الإعدادات أولاً', 'en' => 'Enter the channel URL/token and save settings first'),
            'webhook_test_term' => array(
                'ar' => 'رسالة اختبار من Woo Zero Search Insights — إن وصلتك فهذا يعني أن الإعداد سليم',
                'en' => 'Test message from Woo Zero Search Insights — receiving this means your setup works'
            ),

            // === F-1: الروبوتات ===
            'field_exclude_bots' => array('ar' => 'استثناء روبوتات الزحف من التتبع', 'en' => 'Exclude crawl bots from tracking'),
            'desc_exclude_bots' => array(
                'ar' => 'يفلتر User-Agent المعروفة (Googlebot، Ahrefs، curl…) حتى تعكس أرقامك بشراً حقيقياً. قابلة للتوسعة عبر فلتر wzsi_bot_patterns.',
                'en' => 'Filters known bot user agents (Googlebot, Ahrefs, curl…) so your numbers reflect real humans. Extendable via the wzsi_bot_patterns filter.'
            ),

            // === F-5: فلاتر السجلات ===
            'filter_search' => array('ar' => 'بحث في المصطلحات…', 'en' => 'Search terms…'),
            'filter_from' => array('ar' => 'من', 'en' => 'From'),
            'filter_to' => array('ar' => 'إلى', 'en' => 'To'),
            'filter_all_types' => array('ar' => 'كل الأنواع', 'en' => 'All types'),
            'filter_apply' => array('ar' => 'تطبيق', 'en' => 'Apply'),
            'filter_reset' => array('ar' => 'مسح الفلاتر', 'en' => 'Reset filters'),

            // === F-6: المقارنة ===
            'stat_vs_previous' => array('ar' => 'مقارنة بالفترة السابقة', 'en' => 'vs previous period'),
            'chart_previous_period' => array('ar' => 'الفترة السابقة', 'en' => 'Previous period'),

            // === TD-2: نسخ الإعدادات ===
            'section_backup' => array('ar' => 'نسخ الإعدادات', 'en' => 'Settings backup'),
            'desc_backup' => array(
                'ar' => 'نزّل إعداداتك ملف JSON وأعد استيرادها على متجر آخر — مفيد للوكيلات التي تدير عدة متاجر بنفس الإعداد.',
                'en' => 'Download your settings as JSON and import them on another store — handy for agencies managing multiple stores.'
            ),
            'btn_export_settings' => array('ar' => 'تصدير الإعدادات (JSON)', 'en' => 'Export settings (JSON)'),
            'btn_import_settings' => array('ar' => 'استيراد الإعدادات', 'en' => 'Import settings'),
            'msg_import_ok' => array('ar' => 'تم استيراد الإعدادات بنجاح', 'en' => 'Settings imported successfully'),
            'msg_import_bad' => array('ar' => 'ملف غير صالح — ارفع ملف JSON مُصدَّراً من هذه الإضافة', 'en' => 'Invalid file — upload a JSON exported from this plugin'),

            // === فحص الصحة ===
            'hc_migration' => array('ar' => 'ترحيل التطبيع العربي (v1.1.0)', 'en' => 'Arabic normalization migration (v1.1.0)'),
            'hc_migration_done' => array('ar' => 'مكتمل', 'en' => 'Complete'),
            'hc_migration_pending' => array('ar' => 'قيد التنفيذ — متبقي %s سجلاً (يكمل تلقائياً)', 'en' => 'In progress — %s rows remaining (auto-completes)'),
            'hc_maps_table' => array('ar' => 'جدول الربط الذكي (سجلات)', 'en' => 'Smart mappings table (rows)'),
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
