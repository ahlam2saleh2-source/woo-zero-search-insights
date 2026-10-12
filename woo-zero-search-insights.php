<?php
/**
 * Plugin Name:       Woo Zero Search Insights
 * Plugin URI:        https://github.com/ahlam2saleh2-source/woo-zero-search-insights
 * Description:       Track WooCommerce searches that return no results — and close the gap: smart term→product mappings, customer notifications (in-store + email), email digest, Slack/Discord/Telegram, AR/EN.
 * Version:           1.2.0
 * Author:            az-soft4media
 * Author URI:        https://troyawin.tech
 * Text Domain:       woo-zero-search-insights
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   11.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// ════════════ التعريفات ════════════
define('WZSI_VERSION',           '1.2.0');
define('WZSI_PLUGIN_FILE',        __FILE__);
define('WZSI_PLUGIN_DIR',         plugin_dir_path(__FILE__));
define('WZSI_PLUGIN_URL',         plugin_dir_url(__FILE__));
define('WZSI_PLUGIN_BASENAME',    plugin_basename(__FILE__));
define('WZSI_DB_TABLE',           'woo_zero_search_logs');
define('WZSI_NONCE_ACTION',       'wzsi_admin_nonce');
define('WZSI_CAP',                'manage_woocommerce');

// ════════════ دالة اللغة الافتراضية الذكية ════════════
// تكتشف لغة الموقع تلقائيًا عند أول تفعيل (عربي للمواقع العربية، إنجليزي لغيرها)
// ويبقى الاختيار اليدوي من الإعدادات محفوظًا وله الأولوية دائمًا
function wzsi_default_ui_lang() {
    return (strpos(get_locale(), 'ar') === 0) ? 'ar' : 'en';
}

// ════════════ إعلان توافق ميزات WooCommerce ════════════
// الإضافة للقراءة فقط (لا تمس الطلبات ولا السلة) — نعلن التوافق رسميًا لإزالة تحذير وووكومرس
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

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

// ════════════ F-8: أوامر WP-CLI (wzsi stats/top/logs/clear/prune) ════════════
if (defined('WP_CLI') && WP_CLI) {
    require_once WZSI_PLUGIN_DIR . 'includes/cli.php';
}

// ════════════ التهيئة ════════════
function wzsi_init() {
    // نظام الترجمة المخصص (يُحمّل أولاً دائمًا — حتى بدون WooCommerce)
    // الإصلاح: كان استدعاء wzsi_t() في رسالة انعدام WooCommerce يسبب Fatal Error
    // لأن تعريف الدالة لا يُحمّل إلا مع كلاس الترجمة.
    require_once WZSI_PLUGIN_DIR . 'includes/class-i18n.php';
    WZSI_i18n::instance();

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html(wzsi_t('notice_woocommerce_required')) . '</p></div>';
        });
        return;
    }

    load_plugin_textdomain('woo-zero-search-insights', false, dirname(WZSI_PLUGIN_BASENAME) . '/languages');

    // الكلاسات الأساسية
    WZSI_Core::instance();

    // F-4: الربط الذكي (مصطلح ← منتج/تصنيف)
    WZSI_Mappings::instance();

    // F-3: التقرير البريدي الدوري
    WZSI_Email::instance();

    // F-9 (v1.2.0): إشعارات الزبائن (فردي + جماعي، داخل المتجر + بريد)
    WZSI_Notify::instance();

    // كلاسات Premium
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

        // الترقيات لا تمر بالتفعيل — نضمن جدولة النبضة اليومية الجديدة (F-3)
        require_once WZSI_PLUGIN_DIR . 'includes/class-cron.php';
        WZSI_Cron::schedule_events();
    }

    // F-2: إكمال ترحيل التطبيع العربي دفعات صغيرة عند دخول الأدمين (وحتى عبر الكرون)
    if (!get_option('wzsi_norm_migration_done')) {
        require_once WZSI_PLUGIN_DIR . 'includes/class-database.php';
        WZSI_Database::maybe_migrate_normalized_terms();
    }
});

// ════════════ رابط في قائمة الإضافات ════════════
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wzsi-settings')) . '">' . esc_html(wzsi_t('link_settings')) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
