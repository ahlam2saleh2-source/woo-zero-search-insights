<?php
/**
 * Plugin Name:       Woo Zero Search Insights
 * Plugin URI:        https://github.com/ahlam2saleh2-source/woo-zero-search-insights
 * Description:       تتبع عمليات البحث في WooCommerce التي لا تُرجع نتائج، مع لوحة إحصائيات احترافية، تصدير CSV، Dashboard Widget، إشعارات Slack/Discord، شريط تنقل Tabs، 6 مواضيع (Light/Dark/Ocean/Forest/Sunset/Midnight)، دعم عربي/إنجليزي كامل، White-label.
 * Version:           12.0.3
 * Author:            az-soft4media
 * Author URI:        https://troyawin.tech
 * Text Domain:       woo-zero-search-insights
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ════════════ التعريفات ════════════
define('WZSI_VERSION',           '12.0.3');
define('WZSI_PLUGIN_FILE',        __FILE__);
define('WZSI_PLUGIN_DIR',         plugin_dir_path(__FILE__));
define('WZSI_PLUGIN_URL',         plugin_dir_url(__FILE__));
define('WZSI_PLUGIN_BASENAME',    plugin_basename(__FILE__));
define('WZSI_DB_TABLE',           'woo_zero_search_logs');
define('WZSI_NONCE_ACTION',       'wzsi_admin_nonce');
define('WZSI_CAP',                'manage_woocommerce');

// ════════════ Autoloader ════════════
spl_autoload_register(function ($class) {
    $prefix = 'WZSI_';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative  = substr($class, strlen($prefix));
    $filename = 'class-' . strtolower(str_replace('_', '-', $relative)) . '.php';
    $path     = WZSI_PLUGIN_DIR . 'includes/' . $filename;
    if (file_exists($path)) {
        require_once $path;
    }
});

// ════════════ التهيئة ════════════
function wzsi_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html(wzsi_t('notice_woocommerce_required')) . '</p></div>';
        });
        return;
    }

    load_plugin_textdomain('woo-zero-search-insights', false, dirname(WZSI_PLUGIN_BASENAME) . '/languages');

    // نظام الترجمة المخصص (يُحمّل أولاً)
    WZSI_i18n::instance();

    // الكلاسات الأساسية
    WZSI_Core::instance();

    // كلاسات Premium (بدون أي ميزات بريد)
    WZSI_Dashboard_Widget::instance();
    WZSI_Webhooks::instance();
    WZSI_Premium_UI::instance();
}
add_action('plugins_loaded', 'wzsi_init');

// ════════════ التفعيل ════════════
register_activation_hook(__FILE__, function () {
    require_once WZSI_PLUGIN_DIR . 'includes/class-database.php';
    require_once WZSI_PLUGIN_DIR . 'includes/class-cron.php';

    WZSI_Database::create_table();
    WZSI_Cron::schedule_events();

    if (!get_option('wzsi_settings')) {
        $defaults = WZSI_Settings::get_defaults();
        add_option('wzsi_settings', $defaults);
    }
    update_option('wzsi_db_version', WZSI_VERSION);
    flush_rewrite_rules();
});

// ════════════ الإيقاف ════════════
register_deactivation_hook(__FILE__, function () {
    require_once WZSI_PLUGIN_DIR . 'includes/class-cron.php';
    WZSI_Cron::clear_schedules();
    flush_rewrite_rules();
});

// ════════════ فحص تحديثات الجدول ════════════
add_action('admin_init', function () {
    $current_version = get_option('wzsi_db_version');
    if ($current_version !== WZSI_VERSION) {
        require_once WZSI_PLUGIN_DIR . 'includes/class-database.php';
        WZSI_Database::create_table();
        update_option('wzsi_db_version', WZSI_VERSION);
    }
});

// ════════════ رابط في قائمة الإضافات ════════════
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wzsi-settings')) . '">' . esc_html(wzsi_t('link_settings')) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
