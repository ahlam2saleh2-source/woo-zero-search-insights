<?php
/**
 * Dashboard Widget - عنصر على الصفحة الرئيسية لـ WordPress admin
 * بدون أي ميزات بريد
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Dashboard_Widget
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
        add_action('wp_dashboard_setup', array($this, 'register_widget'));
    }

    public function register_widget()
    {
        $settings = get_option('wzsi_settings', array());
        if (empty($settings['dashboard_widget_enabled'])) {
            return;
        }
        wp_add_dashboard_widget(
            'wzsi_dashboard_widget',
            '🔍 ' . wzsi_t('page_dashboard'),
            array($this, 'render_widget')
        );
    }

    public function render_widget()
    {
        $stats = WZSI_Database::get_stats(1);
        $recent = WZSI_Database::get_recent_searches(5, 1);
        $admin_url = admin_url('admin.php?page=wzsi-dashboard');

        echo '<div class="wzsi-widget">';
        echo '<div class="wzsi-widget-stats">';
        echo '<div class="wzsi-widget-stat"><span class="num">' . esc_html(number_format_i18n($stats['zero_results'])) . '</span><span class="lbl">' . esc_html(wzsi_t('stat_zero_results')) . '</span></div>';
        echo '<div class="wzsi-widget-stat"><span class="num">' . esc_html($stats['zero_rate']) . '%</span><span class="lbl">' . esc_html(wzsi_t('stat_failure_rate')) . '</span></div>';
        echo '<div class="wzsi-widget-stat"><span class="num">' . esc_html(number_format_i18n($stats['unique_zero_terms'])) . '</span><span class="lbl">' . esc_html(wzsi_t('stat_sub_zero')) . '</span></div>';
        echo '</div>';

        if (empty($recent)) {
            echo '<p style="text-align:center; color:#666; padding:20px 0;">';
            echo esc_html(wzsi_t('empty_no_data'));
            echo '</p>';
        } else {
            echo '<table class="wzsi-widget-table"><tbody>';
            foreach ($recent as $row) {
                echo '<tr>';
                echo '<td class="wzsi-term">' . esc_html($row->search_term) . '</td>';
                echo '<td class="wzsi-time">' . esc_html(human_time_diff(strtotime($row->searched_at), current_time('timestamp'))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<p style="text-align:center; margin-top:12px;"><a href="' . esc_url($admin_url) . '" class="button button-primary button-small">' . esc_html(wzsi_t('btn_view_all_logs')) . '</a></p>';
        echo '</div>';

        echo '<style>
        .wzsi-widget-stats { display:flex; gap:10px; margin-bottom:12px; }
        .wzsi-widget-stat { flex:1; background:#f8f9fa; padding:10px; border-radius:6px; text-align:center; }
        .wzsi-widget-stat .num { display:block; font-size:20px; font-weight:700; color:#2271b1; }
        .wzsi-widget-stat .lbl { display:block; font-size:11px; color:#666; }
        .wzsi-widget-table { width:100%; border-collapse:collapse; }
        .wzsi-widget-table td { padding:6px 4px; border-bottom:1px solid #eee; font-size:13px; }
        .wzsi-widget-table .wzsi-term { font-weight:600; }
        .wzsi-widget-table .wzsi-time { text-align:left; color:#888; font-size:11px; }
        </style>';
    }
}
