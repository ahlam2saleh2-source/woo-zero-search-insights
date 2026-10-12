<?php
/**
 * فئة لوحة التحكم - الإدارة
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Admin
{
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
        add_action('admin_menu', array($this, 'register_admin_menu'), 99);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'maybe_show_setup_notice'));
    }

    public function register_admin_menu()
    {
        add_menu_page(
            'Woo Zero Search',
            wzsi_t('menu_zero_search'),
            WZSI_CAP,
            'wzsi-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-area',
            56
        );

        add_submenu_page('wzsi-dashboard', wzsi_t('menu_dashboard'), wzsi_t('menu_dashboard'), WZSI_CAP, 'wzsi-dashboard', array($this, 'render_dashboard_page'));
        add_submenu_page('wzsi-dashboard', wzsi_t('menu_mappings'), '&#128279; ' . wzsi_t('menu_mappings'), WZSI_CAP, 'wzsi-mappings', array($this, 'render_mappings_page'));
        add_submenu_page('wzsi-dashboard', wzsi_t('menu_logs'), wzsi_t('menu_logs'), WZSI_CAP, 'wzsi-logs', array($this, 'render_logs_page'));
        add_submenu_page('wzsi-dashboard', wzsi_t('menu_settings'), wzsi_t('menu_settings'), WZSI_CAP, 'wzsi-settings', array('WZSI_Settings', 'render_settings_page'));
        add_submenu_page('wzsi-dashboard', wzsi_t('menu_health_check'), '🩺 ' . wzsi_t('menu_health_check'), WZSI_CAP, 'wzsi-health-check', array($this, 'render_health_check_page'));
    }

    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'wzsm') === false && strpos($hook, 'wzsi') === false) {
            return;
        }
        wp_enqueue_style('wzsm-admin', WZSI_PLUGIN_URL . 'assets/css/admin.css', array(), WZSI_VERSION);
        wp_enqueue_script('wzsm-admin', WZSI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WZSI_VERSION, true);
        // الإصلاح: تم تضمين Chart.js محليًا بدل CDN — متطلب منصات البيع + يعمل بدون إنترنت
        wp_enqueue_script('chartjs', WZSI_PLUGIN_URL . 'assets/vendor/chart.umd.min.js', array(), '4.4.0', true);

        wp_localize_script('wzsm-admin', 'wzsm', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(WZSI_NONCE_ACTION),
            'i18n'     => array(
                'confirm_delete' => wzsi_t('msg_confirm_delete_all'),
                'confirm_delete_term' => wzsi_t('msg_confirm_delete_term'),
                'confirm_export'  => wzsi_t('msg_confirm_export'),
            ),
        ));

        // متغيرات الترجمة للـ JS
        wp_localize_script('wzsm-admin', 'wzsi_t_vars', array(
            'theme_changed' => wzsi_t('alert_theme_changed'),
            'term_deleted'  => wzsi_t('alert_term_deleted'),
            'delete_failed'  => wzsi_t('alert_delete_failed'),
            'network_error' => wzsi_t('alert_network_error'),
            'cleared'       => wzsi_t('alert_cleared'),
            'copied'        => wzsi_t('alert_copied'),
            'click_to_copy' => wzsi_t('click_to_copy'),
            // F-4: الربط الذكي
            'map_saved'       => wzsi_t('map_saved'),
            'map_failed'      => wzsi_t('map_failed'),
            'map_error_url'   => wzsi_t('map_error_url'),
            'map_error_target' => wzsi_t('map_error_target'),
            'map_error_term'  => wzsi_t('map_error_term'),
            // F-9 (v1.2.0): الإشعارات
            'ntf_loading'        => wzsi_t('ntf_loading'),
            'ntf_no_registered'  => wzsi_t('ntf_no_registered'),
            'ntf_guests_note_prefix' => wzsi_t('ntf_guests_note_prefix'),
            'ntf_guests_note_suffix' => wzsi_t('ntf_guests_note_suffix'),
            'ntf_error_channel'  => wzsi_t('ntf_error_channel'),
            'ntf_error_product'  => wzsi_t('ntf_error_product'),
            'ntf_error_email'    => wzsi_t('ntf_error_email'),
            'ntf_error_send'     => wzsi_t('ntf_error_send'),
            'ntf_done_ok'        => wzsi_t('ntf_done_ok'),
            'mail_test_ok'       => wzsi_t('mail_test_ok'),
            'mail_test_fail'     => wzsi_t('mail_test_fail'),
        ));

        // F-9: قوالب الإشعارات للنافذة (افتراضي أو المحفوظ)
        $settings_for_js = get_option('wzsi_settings', array());
        $tpl_ar_subj = trim((string) ($settings_for_js['notify_template_subject_ar'] ?? ''));
        $tpl_ar_body = trim((string) ($settings_for_js['notify_template_body_ar'] ?? ''));
        $tpl_en_subj = trim((string) ($settings_for_js['notify_template_subject_en'] ?? ''));
        $tpl_en_body = trim((string) ($settings_for_js['notify_template_body_en'] ?? ''));
        $ntf_defaults = WZSI_Notify::default_templates();
        wp_localize_script('wzsm-admin', 'wzsi_ntf_tpl', array(
            'ar' => array(
                'subject' => ('' !== $tpl_ar_subj) ? $tpl_ar_subj : $ntf_defaults['ar']['subject'],
                'body'    => ('' !== $tpl_ar_body) ? $tpl_ar_body : $ntf_defaults['ar']['body'],
            ),
            'en' => array(
                'subject' => ('' !== $tpl_en_subj) ? $tpl_en_subj : $ntf_defaults['en']['subject'],
                'body'    => ('' !== $tpl_en_body) ? $tpl_en_body : $ntf_defaults['en']['body'],
            ),
        ));
    }

    public function maybe_show_setup_notice()
    {
        if (!current_user_can(WZSI_CAP)) return;
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wzsi') === 0) return;
        $installed = get_option('wzsi_installed_at');
        if (!$installed) {
            update_option('wzsi_installed_at', current_time('mysql'));
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html(wzsi_t('notice_installed')) . ' <a href="' . esc_url(admin_url('admin.php?page=wzsi-dashboard')) . '">' . esc_html(wzsi_t('link_dashboard')) . '</a></p></div>';
        }
    }

    public function render_dashboard_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }
        $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        $days = in_array($days, array(7, 30, 90, 365, 0)) ? $days : 30;

        $stats = WZSI_Database::get_stats($days);
        $top_searches = WZSI_Database::get_top_zero_searches(20, $days);

        // F-6: مقارنة الفترة السابقة (الكل = بلا مقارنة)
        if ($days > 0) {
            $trend_pair = WZSI_Database::get_daily_trend($days, true);
            $trend = $trend_pair['current'];
            $trend_previous = $trend_pair['previous'];
            $compare = WZSI_Database::get_compare_stats($days);
        } else {
            $trend = WZSI_Database::get_daily_trend(90);
            $trend_previous = array();
            $compare = null;
        }

        require WZSI_PLUGIN_DIR . 'includes/views/dashboard.php';
    }

    public function render_logs_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }
        $zero_only = isset($_GET['zero_only']) ? (int) $_GET['zero_only'] : 1;

        // F-5: فلاتر السجلات — بحث نصي + تاريخ من/إلى + نوع الطلب
        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $from_raw = isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : '';
        $to_raw   = isset($_GET['to'])   ? sanitize_text_field(wp_unslash($_GET['to']))   : '';
        $type     = isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type']))   : 'any';
        if (!in_array($type, array('any', '0', '1'), true)) {
            $type = 'any';
        }

        // F-10 (v1.2.0): طريقة العرض + فلتر المستخدم
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'list';
        if (!in_array($view, array('list', 'grouped'), true)) {
            $view = 'list';
        }
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0; // 0=الكل، -1=ضيوف، >0=مستخدم

        // تحقق صارم من التواريخ (Y-m-d)
        $from = null;
        $to   = null;
        if ($from_raw) {
            $d = DateTime::createFromFormat('Y-m-d', $from_raw);
            if ($d && $d->format('Y-m-d') === $from_raw) {
                $from = $d;
            }
        }
        if ($to_raw) {
            $d = DateTime::createFromFormat('Y-m-d', $to_raw);
            if ($d && $d->format('Y-m-d') === $to_raw) {
                $to = $d;
            }
        }

        $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

        // قائمة المستخدمين للفلتر (سقف 200)
        $logs_users = WZSI_Database::get_logs_users(200);

        if ('grouped' === $view) {
            // العرض المجمع حسب المنتج المبحوث (صفرية فقط) — فلتر المستخدم لا ينطبق على التجميع
            $result = WZSI_Database::get_grouped_terms(array(
                'q'        => $q,
                'page'     => $page,
                'per_page' => 25,
            ));
            $logs  = $result['rows'];
            $total = $result['total'];
            $pages = $result['pages'];
            $offset = ($result['page'] - 1) * $result['per_page'];
        } else {
            $result = WZSI_Database::get_logs_filtered(array(
                'zero_only' => $zero_only,
                'q'         => $q,
                'from'      => $from,
                'to'        => $to,
                'type'      => ('any' === $type) ? null : $type,
                'user_id'   => $user_id,
                'page'      => $page,
                'per_page'  => 50,
            ));
            $logs  = $result['rows'];
            $total = $result['total'];
            $pages = $result['pages'];
            $offset = ($result['page'] - 1) * $result['per_page'];
        }

        require WZSI_PLUGIN_DIR . 'includes/views/logs.php';
    }

    /**
     * F-4: صفحة الربط الذكي
     */
    public function render_mappings_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }

        global $wpdb;
        $maps_table = WZSI_Mappings::table_name();
        $mappings = $wpdb->get_results("SELECT * FROM {$maps_table} ORDER BY id DESC LIMIT 200");
        if (!is_array($mappings)) {
            $mappings = array();
        }
        $settings = get_option('wzsi_settings', array());

        require WZSI_PLUGIN_DIR . 'includes/views/mappings.php';
    }

    public function render_health_check_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }
        require WZSI_PLUGIN_DIR . 'includes/views/health-check.php';
    }
}
