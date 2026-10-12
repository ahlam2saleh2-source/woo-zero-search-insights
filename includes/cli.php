<?php
/**
 * F-8: أوامر WP-CLI — إدارة سريعة من الطرفية (للوكيلات والأتمتة)
 *
 *   wp wzsi stats  [--days=7|30|90|365|0] [--format=table|json|csv]
 *   wp wzsi top    [--days=...] [--limit=20] [--format=...]
 *   wp wzsi logs   [--zero-only] [--search=...] [--paged=1] [--format=...]
 *   wp wzsi clear  [--yes]
 *   wp wzsi prune  [--days=90] [--yes]
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    exit;
}

class WZSI_CLI_Commands
{
    /**
     * إحصائيات الإضافة
     *
     * [--days=<days>]
     * : نطاق الأيام (7/30/90/365/0=الكل). الافتراضي 30.
     *
     * [--format=<format>]
     * : table|json|csv. الافتراضي table.
     *
     * ## Examples
     *     wp wzsi stats --days=30 --format=json
     *
     * @subcommand stats
     */
    public function stats($args, $assoc_args)
    {
        $days = (int) ($assoc_args['days'] ?? 30);
        $format = WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

        $stats = WZSI_Database::get_stats($days);

        $row = array(
            'total_searches' => $stats['total_searches'],
            'zero_results'   => $stats['zero_results'],
            'failure_rate'   => $stats['zero_rate'] . '%',
            'unique_terms'   => $stats['unique_terms'],
            'unique_zero'    => $stats['unique_zero_terms'],
        );

        WP_CLI\Utils\format_items($format, array($row), array('total_searches', 'zero_results', 'failure_rate', 'unique_terms', 'unique_zero'));
    }

    /**
     * أعلى المصطلحات بلا نتائج
     *
     * [--days=<days>]
     * : نطاق الأيام (0=الكل). الافتراضي 30.
     *
     * [--limit=<limit>]
     * : عدد النتائج. الافتراضي 20.
     *
     * [--format=<format>]
     * : table|json|csv. الافتراضي table.
     *
     * ## Examples
     *     wp wzsi top --days=7 --limit=10 --format=csv
     *
     * @subcommand top
     */
    public function top($args, $assoc_args)
    {
        $days = (int) ($assoc_args['days'] ?? 30);
        $limit = (int) ($assoc_args['limit'] ?? 20);
        $format = WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

        $rows = WZSI_Database::get_top_zero_searches($limit, $days);
        if (empty($rows)) {
            WP_CLI::log('No zero-result terms found / لا توجد مصطلحات صفرية في هذه الفترة.');
            return;
        }

        $items = array();
        foreach ($rows as $r) {
            $items[] = array(
                'term'           => $r->search_term,
                'total_searches' => (int) $r->total_searches,
                'unique'         => (int) $r->unique_searches,
                'last_seen'      => $r->last_seen,
            );
        }

        WP_CLI\Utils\format_items($format, $items, array('term', 'total_searches', 'unique', 'last_seen'));
    }

    /**
     * عرض السجلات
     *
     * [--zero-only]
     * : عرض الصفرية فقط.
     *
     * [--search=<q>]
     * : بحث نصي في المصطلحات.
     *
     * [--paged=<page>]
     * : رقم الصفحة. الافتراضي 1.
     *
     * [--format=<format>]
     * : table|json|csv. الافتراضي table.
     *
     * @subcommand logs
     */
    public function logs($args, $assoc_args)
    {
        $format = WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

        $result = WZSI_Database::get_logs_filtered(array(
            'zero_only' => isset($assoc_args['zero-only']),
            'q'         => isset($assoc_args['search']) ? (string) $assoc_args['search'] : '',
            'page'      => (int) ($assoc_args['paged'] ?? 1),
            'per_page'  => 50,
        ));

        if (empty($result['rows'])) {
            WP_CLI::log('No logs found / لا توجد سجلات مطابقة.');
            return;
        }

        WP_CLI::log(sprintf('Total matching: %d (page %d/%d)', $result['total'], $result['page'], $result['pages']));

        $items = array();
        foreach ($result['rows'] as $r) {
            $items[] = array(
                'id'      => (int) $r->id,
                'term'    => $r->search_term,
                'results' => (int) $r->results_count,
                'type'    => $r->is_ajax ? 'AJAX' : 'Page',
                'date'    => $r->searched_at,
                'repeat'  => (int) $r->occurrence_count,
            );
        }

        WP_CLI\Utils\format_items($format, $items, array('id', 'term', 'results', 'type', 'date', 'repeat'));
    }

    /**
     * مسح كل السجلات (تدميري — يتطلب --yes)
     *
     * [--yes]
     * : تخطي تأكيد الحذف.
     *
     * @subcommand clear
     */
    public function clear($args, $assoc_args)
    {
        WP_CLI::confirm('This will DELETE all search logs. Are you sure?', $assoc_args);
        WZSI_Database::truncate_logs();
        WP_CLI::success('All logs cleared / تم مسح كل السجلات.');
    }

    /**
     * حذف السجلات الأقدم من عدد أيام محدد
     *
     * [--days=<days>]
     * : حذف الأقدم من هذا العدد. الافتراضي 90.
     *
     * [--yes]
     * : تخطي التأكيد.
     *
     * @subcommand prune
     */
    public function prune($args, $assoc_args)
    {
        $days = (int) ($assoc_args['days'] ?? 90);
        $days = max(1, $days);
        WP_CLI::confirm(sprintf('Delete logs older than %d days?', $days), $assoc_args);
        $deleted = WZSI_Database::cleanup_old_logs($days);
        WP_CLI::success(sprintf('Deleted %d old logs / حُذف %d سجلاً قديماً.', $deleted, $deleted));
    }
}

WP_CLI::add_command('wzsi', 'WZSI_CLI_Commands');
