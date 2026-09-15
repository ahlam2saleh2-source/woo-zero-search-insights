<?php
/**
 * فئة التصدير - تصدير السجلات إلى CSV
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Export
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
        // AJAX handlers
        add_action('wp_ajax_wzsi_export', array($this, 'ajax_export'));
        add_action('wp_ajax_wzsi_clear_all', array($this, 'ajax_clear_all'));
        add_action('wp_ajax_wzsi_delete_term', array($this, 'ajax_delete_term'));
    }

    /**
     * تصدير السجلات إلى CSV وتنزيلها
     *
     * @param int $days      عدد الأيام (0 = الكل)
     * @param int $zero_only 1=فقط بدون نتائج
     */
    public static function export_csv($days = 0, $zero_only = 1)
    {
        global $wpdb;
        $table = WZSI_Database::table_name();

        $where = "WHERE 1=1";
        if ($zero_only) {
            $where .= " AND results_count = 0";
        }
        if ($days > 0) {
            $where .= $wpdb->prepare(" AND searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        }

        $rows = $wpdb->get_results(
            "SELECT search_term, results_count, searched_at, last_occurrence, occurrence_count,
                    user_id, user_ip, language, is_ajax, referer
             FROM {$table} {$where}
             ORDER BY searched_at DESC"
        );

        $filename = 'woo-zero-search-' . date('Y-m-d-His') . '.csv';

        // Headers للتنزيل
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // UTF-8 BOM لمساعدة Excel على عرض العربية بشكل صحيح
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        // الترويسة
        fputcsv($out, array(
            __('مصطلح البحث', 'woo-zero-search-insights'),
            __('عدد النتائج', 'woo-zero-search-insights'),
            __('تاريخ البحث', 'woo-zero-search-insights'),
            __('آخر مرة', 'woo-zero-search-insights'),
            __('عدد التكرار', 'woo-zero-search-insights'),
            __('معرف المستخدم', 'woo-zero-search-insights'),
            __('IP', 'woo-zero-search-insights'),
            __('اللغة', 'woo-zero-search-insights'),
            __('نوع الطلب', 'woo-zero-search-insights'),
            __('المرجع', 'woo-zero-search-insights'),
        ));

        foreach ($rows as $row) {
            fputcsv($out, array(
                $row->search_term,
                $row->results_count,
                $row->searched_at,
                $row->last_occurrence,
                $row->occurrence_count,
                $row->user_id,
                $row->user_ip,
                $row->language,
                $row->is_ajax ? 'AJAX' : 'Page',
                $row->referer,
            ));
        }

        fclose($out);
        exit;
    }

    /**
     * AJAX handler للتصدير
     */
    public function ajax_export()
    {
        check_ajax_referer(WZSI_NONCE_ACTION, 'nonce');
        if (!current_user_can(WZSI_CAP)) {
            wp_send_json_error(__('لا صلاحية.', 'woo-zero-search-insights'), 403);
        }
        $days = (int) ($_POST['days'] ?? 0);
        $zero_only = (int) ($_POST['zero_only'] ?? 1);
        self::export_csv($days, $zero_only);
    }

    /**
     * AJAX: مسح كل السجلات
     */
    public function ajax_clear_all()
    {
        check_ajax_referer(WZSI_NONCE_ACTION, 'nonce');
        if (!current_user_can(WZSI_CAP)) {
            wp_send_json_error(__('لا صلاحية.', 'woo-zero-search-insights'), 403);
        }
        $deleted = WZSI_Database::truncate_logs();
        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => __('تم مسح كل السجلات.', 'woo-zero-search-insights'),
        ));
    }

    /**
     * AJAX: حذف مصطلح محدد
     */
    public function ajax_delete_term()
    {
        check_ajax_referer(WZSI_NONCE_ACTION, 'nonce');
        if (!current_user_can(WZSI_CAP)) {
            wp_send_json_error(__('لا صلاحية.', 'woo-zero-search-insights'), 403);
        }
        $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
        if (!$term) {
            wp_send_json_error(__('مصطلح غير صالح.', 'woo-zero-search-insights'));
        }
        $deleted = WZSI_Database::delete_by_term($term);
        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => sprintf(
                /* translators: %d: deleted rows count */
                __('تم حذف %d سجل.', 'woo-zero-search-insights'),
                $deleted
            ),
        ));
    }
}
