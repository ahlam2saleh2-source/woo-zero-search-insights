<?php
/**
 * نظام المواضيع + شريط التنقل + Breadcrumbs
 * بدون أي ميزات بريد (محذوفة نهائياً)
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Premium_UI
{
    private static $instance = null;

    public static function get_themes()
    {
        return array(
            'light'     => array('key' => 'theme_light',     'icon' => 'dashicons-superhero'),
            'dark'      => array('key' => 'theme_dark',      'icon' => 'dashicons-dark-mode-2'),
            'ocean'     => array('key' => 'theme_ocean',     'icon' => 'dashicons-water'),
            'forest'    => array('key' => 'theme_forest',    'icon' => 'dashicons-palmtree'),
            'sunset'    => array('key' => 'theme_sunset',    'icon' => 'dashicons-sun'),
            'midnight'   => array('key' => 'theme_midnight',  'icon' => 'dashicons-moon'),
        );
    }

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_notices', array($this, 'inject_navigation'), 1);
        add_action('admin_notices', array($this, 'inject_breadcrumbs'), 2);
        add_action('admin_footer', array($this, 'inject_theme_toggle_button'));
        add_filter('admin_body_class', array($this, 'apply_theme_class'));
        add_action('wp_ajax_wzsi_set_theme', array($this, 'ajax_set_theme'));
    }

    private function is_wzsi_page()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) {
            return false;
        }
        return strpos($screen->id, 'wzsm') !== false || strpos($screen->id, 'wzsi') !== false;
    }

    private function get_current_theme()
    {
        $settings = get_option('wzsi_settings', array());
        $theme = isset($settings['theme']) ? $settings['theme'] : 'light';
        if (empty($theme)) {
            $theme = !empty($settings['dark_mode']) ? 'dark' : 'light';
        }
        $themes = self::get_themes();
        return isset($themes[$theme]) ? $theme : 'light';
    }

    public function apply_theme_class($classes)
    {
        $theme = $this->get_current_theme();
        if ($theme !== 'light') {
            $classes .= ' wzsi-theme-' . esc_attr($theme);
        }
        return $classes;
    }

    public function inject_navigation()
    {
        if (!$this->is_wzsi_page()) {
            return;
        }
        $current = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        $tabs = array(
            'wzsi-dashboard' => array('key' => 'menu_dashboard', 'icon' => 'dashicons-chart-area'),
            'wzsi-logs' => array('key' => 'menu_logs', 'icon' => 'dashicons-list-view'),
            'wzsi-settings' => array('key' => 'menu_settings', 'icon' => 'dashicons-admin-generic'),
            'wzsi-health-check' => array('key' => 'menu_health_check', 'icon' => 'dashicons-heart'),
        );

        echo '<div class="wzsi-nav-tabs"><div class="wzsi-nav-tabs-inner">';
        foreach ($tabs as $page => $data) {
            $url = admin_url('admin.php?page=' . $page);
            $active = ($current === $page) ? ' active' : '';
            printf(
                '<a href="%s" class="wzsi-nav-tab%s"><span class="dashicons %s"></span> <span class="tab-label">%s</span></a>',
                esc_url($url),
                esc_attr($active),
                esc_attr($data['icon']),
                esc_html(wzsi_t($data['key']))
            );
        }
        echo '</div></div>';
    }

    public function inject_breadcrumbs()
    {
        if (!$this->is_wzsi_page()) {
            return;
        }
        $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'wzsi-dashboard';
        $page_titles = array(
            'wzsi-dashboard' => 'menu_dashboard',
            'wzsi-logs'      => 'menu_logs',
            'wzsi-settings' => 'menu_settings',
            'wzsi-health-check' => 'menu_health_check',
        );
        $title_key = isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'menu_dashboard';

        echo '<div class="wzsi-breadcrumbs">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=wzsi-dashboard')) . '">Zero Search</a>';
        echo ' <span class="sep">›</span> ';
        echo '<span class="current">' . esc_html(wzsi_t($title_key)) . '</span>';
        echo '</div>';
    }

    public function inject_theme_toggle_button()
    {
        if (!$this->is_wzsi_page()) {
            return;
        }
        $current_theme = $this->get_current_theme();
        $themes = self::get_themes();
        $theme_data = $themes[$current_theme];
        $next_theme = $this->get_next_theme($current_theme);
        ?>
        <div class="wzsi-theme-toggle">
            <button type="button" id="wzsi-toggle-theme" class="button button-secondary"
                    data-current-theme="<?php echo esc_attr($current_theme); ?>"
                    data-next-theme="<?php echo esc_attr($next_theme); ?>"
                    title="<?php echo esc_attr(wzsi_t('desc_theme')); ?>">
                <span class="dashicons <?php echo esc_attr($theme_data['icon']); ?>"></span>
                <span class="toggle-text"><?php echo esc_html(wzsi_t($theme_data['key'])); ?></span>
            </button>
        </div>
        <?php
    }

    private function get_next_theme($current)
    {
        $themes = array_keys(self::get_themes());
        $idx = array_search($current, $themes);
        if ($idx === false) {
            return 'light';
        }
        $next_idx = ($idx + 1) % count($themes);
        return $themes[$next_idx];
    }

    public function ajax_set_theme()
    {
        check_ajax_referer('wzsi_admin_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(wzsi_t('notice_no_permission'), 403);
        }
        $theme = isset($_POST['theme']) ? sanitize_key($_POST['theme']) : 'light';
        $themes = self::get_themes();
        if (!isset($themes[$theme])) {
            wp_send_json_error('Invalid theme');
        }

        $settings = get_option('wzsi_settings', array());
        $settings['theme'] = $theme;
        $settings['dark_mode'] = ($theme === 'dark') ? 1 : 0;
        update_option('wzsi_settings', $settings);

        $next_theme = $this->get_next_theme($theme);
        $next_data = $themes[$next_theme];

        wp_send_json_success(array(
            'theme'        => $theme,
            'theme_label'  => wzsi_t($themes[$theme]['key']),
            'theme_icon'   => $themes[$theme]['icon'],
            'next_theme'   => $next_theme,
            'next_label'   => wzsi_t($next_data['key']),
        ));
    }
}
