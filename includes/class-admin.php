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
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);

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
        $trend = WZSI_Database::get_daily_trend($days > 0 ? $days : 90);

        require WZSI_PLUGIN_DIR . 'includes/views/dashboard.php';
    }

    public function render_logs_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }
        $zero_only = isset($_GET['zero_only']) ? (int) $_GET['zero_only'] : 1;
        $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        global $wpdb;
        $table = WZSI_Database::table_name();
        $where = $zero_only ? "WHERE results_count = 0" : "WHERE 1=1";

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
        $logs = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY searched_at DESC LIMIT {$offset}, {$per_page}");
        $pages = ceil($total / $per_page);

        require WZSI_PLUGIN_DIR . 'includes/views/logs.php';
    }

    public function render_health_check_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }
        require WZSI_PLUGIN_DIR . 'includes/views/health-check.php';
    }
}
