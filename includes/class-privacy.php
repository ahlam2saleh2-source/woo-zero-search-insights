<?php
/**
 * TD-3: تكامل أدوات الخصوصية في ووردبريس
 * تسجيل الإضافة كـ exporter/eraser للبيانات الشخصية (GDPR)
 *
 * التصدير: يرجع سجلات البحث المرتبطة بمعرف المستخدم
 * المحو: يصفّر user_id ويمسح IP/UA/referer لسجلات المستخدم دون حذف السجل
 * (الإحصاءات التجميعية تبقى سليمة والهوية تُمحى)
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Privacy
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
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_eraser'));
    }

    public function register_exporter($exporters)
    {
        $exporters['woo-zero-search-insights'] = array(
            'exporter_friendly_name' => 'Woo Zero Search Insights',
            'callback'               => array($this, 'export_user_data'),
        );
        return $exporters;
    }

    public function register_eraser($erasers)
    {
        $erasers['woo-zero-search-insights'] = array(
            'eraser_friendly_name' => 'Woo Zero Search Insights',
            'callback'             => array($this, 'erase_user_data'),
        );
        return $erasers;
    }

    /**
     * تصدير بيانات بحث المستخدم
     */
    public function export_user_data($email_address, $page = 1)
    {
        global $wpdb;
        $export_items = array();
        $per_page = 100;

        $user = get_user_by('email', $email_address);
        if ($user) {
            $table = WZSI_Database::table_name();
            $offset = ((int) $page - 1) * $per_page;
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, search_term, results_count, searched_at, is_ajax, occurrence_count
                 FROM {$table} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
                $user->ID, $per_page, $offset
            ));

            // F-9 (v1.2.0): إشعارات المستخدم تُصدَّر أيضاً
            $n_table = WZSI_Notify::table_name();
            $notifs = $wpdb->get_results($wpdb->prepare(
                "SELECT id, term, channel, status, created_at
                 FROM {$n_table} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
                $user->ID, $per_page, $offset
            ));

            if (!empty($notifs)) {
                $ndata = array();
                foreach ($notifs as $n) {
                    $ndata[] = array(
                        'name'  => wzsi_t('hc_notifs_title'),
                        'value' => $n->term . ' (' . $n->channel . ', ' . $n->status . ', ' . $n->created_at . ')',
                    );
                }
                $export_items[] = array(
                    'group_id'    => 'wzsi_notifications',
                    'group_label' => 'Woo Zero Search Insights',
                    'item_id'     => 'wzsi-notifs-user-' . $user->ID . '-page-' . (int) $page,
                    'data'        => $ndata,
                );
            }

            if (!empty($rows)) {
                $data = array();
                foreach ($rows as $r) {
                    $data[] = array(
                        'name'  => wzsi_t('col_term'),
                        'value' => $r->search_term . ' (' . (int) $r->results_count . ' results, ' . $r->searched_at . ')',
                    );
                }
                $export_items[] = array(
                    'group_id'    => 'wzsi_search_logs',
                    'group_label' => 'Woo Zero Search Insights',
                    'item_id'     => 'wzsi-logs-user-' . $user->ID . '-page-' . (int) $page,
                    'data'        => $data,
                );
            }
        }

        $done = count($export_items) === 0 || (count($rows ?? array()) < $per_page && count($notifs ?? array()) < $per_page);
        return array(
            'data' => $export_items,
            'done' => $done,
        );
    }

    /**
     * محو بيانات المستخدم: إخفاء الهوية مع بقاء السجل للإحصاءات
     */
    public function erase_user_data($email_address, $page = 1)
    {
        global $wpdb;
        $user = get_user_by('email', $email_address);
        $items_removed = false;

        if ($user) {
            $table = WZSI_Database::table_name();
            $affected = $wpdb->query($wpdb->prepare(
                "UPDATE {$table}
                 SET user_id = 0, user_ip = NULL, user_agent = NULL, referer = NULL
                 WHERE user_id = %d",
                $user->ID
            ));
            $items_removed = ($affected > 0);

            // F-9 (v1.2.0): محو صندوق إشعارات المتجر + صفوف سجل الإشعارات للهوية
            delete_user_meta($user->ID, 'wzsi_inbox');
            $n_table = WZSI_Notify::table_name();
            $n_deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$n_table} WHERE user_id = %d",
                $user->ID
            ));
            if ($n_deleted > 0) {
                $items_removed = true;
            }
        }

        return array(
            'items_removed'  => $items_removed,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => true,
        );
    }
}
