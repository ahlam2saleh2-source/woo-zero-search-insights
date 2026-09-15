<?php
/**
 * صفحة لوحة المعلومات - تعرض الإحصائيات وأعلى عمليات البحث
 * النصوص عبر نظام i18n المخصص (عربي/إنجليزي)
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can(WZSI_CAP)) {
    wp_die(wzsi_t('notice_no_permission'));
}
?>
<div class="wrap wzsm-wrap">

    <div class="wzsm-page-header">
        <h1>🔍 <?php echo esc_html(wzsi_t('page_dashboard')); ?></h1>
        <div class="wzsm-filter">
            <form method="get">
                <input type="hidden" name="page" value="wzsi-dashboard">
                <label for="wzsm-days"><?php echo esc_html(wzsi_t('field_period')); ?></label>
                <select name="days" id="wzsm-days" onchange="this.form.submit()">
                    <option value="7"   <?php selected($days, 7); ?>><?php echo esc_html(wzsi_t('filter_7days')); ?></option>
                    <option value="30"  <?php selected($days, 30); ?>><?php echo esc_html(wzsi_t('filter_30days')); ?></option>
                    <option value="90"  <?php selected($days, 90); ?>><?php echo esc_html(wzsi_t('filter_90days')); ?></option>
                    <option value="365" <?php selected($days, 365); ?>><?php echo esc_html(wzsi_t('filter_year')); ?></option>
                    <option value="0"   <?php selected($days, 0); ?>><?php echo esc_html(wzsi_t('filter_all')); ?></option>
                </select>
            </form>
        </div>
    </div>

    <p class="wzsm-subtitle"><?php echo esc_html(wzsi_t('subtitle_dashboard')); ?></p>

    <!-- بطاقات الإحصائيات -->
    <div class="wzsm-stats-grid">
        <div class="wzsm-stat-card stat-card-primary">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-label"><?php echo esc_html(wzsi_t('stat_total_searches')); ?></div>
                <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['total_searches'])); ?></div>
                <div class="stat-sub">
                    <?php
                    printf(
                        esc_html(wzsi_t('stat_sub_total')),
                        number_format_i18n($stats['unique_terms'])
                    );
                    ?>
                </div>
            </div>
        </div>

        <div class="wzsm-stat-card stat-card-danger">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-label"><?php echo esc_html(wzsi_t('stat_zero_results')); ?></div>
                <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['zero_results'])); ?></div>
                <div class="stat-sub">
                    <?php
                    printf(
                        esc_html(wzsi_t('stat_sub_zero')),
                        number_format_i18n($stats['unique_zero_terms'])
                    );
                    ?>
                </div>
            </div>
        </div>

        <div class="wzsm-stat-card stat-card-warning">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-label"><?php echo esc_html(wzsi_t('stat_failure_rate')); ?></div>
                <div class="stat-value"><?php echo esc_html($stats['zero_rate']); ?>%</div>
                <div class="stat-sub"><?php echo esc_html(wzsi_t('stat_sub_rate')); ?></div>
            </div>
        </div>

        <div class="wzsm-stat-card stat-card-success">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <div class="stat-label"><?php echo esc_html(wzsi_t('stat_opportunities')); ?></div>
                <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['unique_zero_terms'])); ?></div>
                <div class="stat-sub"><?php echo esc_html(wzsi_t('stat_sub_opp')); ?></div>
            </div>
        </div>
    </div>

    <!-- الرسم البياني للاتجاه -->
    <div class="wzsm-chart-section">
        <h2><?php echo esc_html(wzsi_t('section_trend')); ?></h2>
        <div class="wzsm-chart-container">
            <canvas id="wzsm-trend-chart" height="80"></canvas>
        </div>
    </div>

    <!-- أعلى عمليات البحث بلا نتائج -->
    <div class="wzsm-top-section">
        <div class="wzsm-section-header">
            <h2>🎯 <?php echo esc_html(wzsi_t('section_top_zero')); ?></h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wzsi-logs&zero_only=1')); ?>" class="button button-secondary">
                <?php echo esc_html(wzsi_t('btn_view_all_logs')); ?>
            </a>
        </div>

        <?php if (empty($top_searches)) : ?>
            <div class="wzsm-empty">
                <div class="wzsm-empty-icon">✨</div>
                <h3><?php echo esc_html(wzsi_t('empty_no_data')); ?></h3>
                <p><?php echo esc_html(wzsi_t('empty_no_data_desc')); ?></p>
            </div>
        <?php else : ?>
            <table class="widefat striped wzsm-table">
                <thead>
                    <tr>
                        <th width="40"><?php echo esc_html(wzsi_t('col_id')); ?></th>
                        <th><?php echo esc_html(wzsi_t('col_term')); ?></th>
                        <th width="120"><?php echo esc_html(wzsi_t('col_count')); ?></th>
                        <th width="120"><?php echo esc_html(wzsi_t('col_unique')); ?></th>
                        <th width="180"><?php echo esc_html(wzsi_t('col_last_seen')); ?></th>
                        <th width="120"><?php echo esc_html(wzsi_t('col_action')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($top_searches as $row) : ?>
                        <tr>
                            <td class="wzsm-rank">#<?php echo $i++; ?></td>
                            <td class="wzsm-term">
                                <strong><?php echo esc_html($row->search_term); ?></strong>
                            </td>
                            <td><span class="wzsm-count"><?php echo esc_html(number_format_i18n($row->total_searches)); ?></span></td>
                            <td><?php echo esc_html(number_format_i18n($row->unique_searches)); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($row->last_seen))); ?></td>
                            <td>
                                <button class="button button-small button-link-delete wzsm-delete-term"
                                        data-term="<?php echo esc_attr($row->search_term_normalized); ?>"
                                        title="<?php echo esc_attr(wzsi_t('btn_delete')); ?>">
                                    <?php echo esc_html(wzsi_t('btn_delete')); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- إجراءات سريعة -->
    <div class="wzsm-quick-actions">
        <h2>⚡ <?php echo esc_html(wzsi_t('section_quick_actions')); ?></h2>
        <div class="wzsm-actions-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wzsi-logs')); ?>" class="wzsm-action-card">
                <span class="dashicons dashicons-list-view"></span>
                <span><?php echo esc_html(wzsi_t('btn_view_logs')); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wzsi-settings')); ?>" class="wzsm-action-card">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php echo esc_html(wzsi_t('btn_settings')); ?></span>
            </a>
            <form method="post" class="wzsm-action-form" id="wzsm-export-form">
                <input type="hidden" name="wzsi_action" value="export_csv">
                <input type="hidden" name="wzsi_days" value="<?php echo esc_attr($days); ?>">
                <?php wp_nonce_field(WZSI_NONCE_ACTION, 'wzsi_nonce'); ?>
                <button type="submit" class="wzsm-action-card">
                    <span class="dashicons dashicons-download"></span>
                    <span><?php echo esc_html(wzsi_t('btn_export_csv')); ?></span>
                </button>
            </form>
            <button type="button" class="wzsm-action-card wzsm-danger" id="wzsm-clear-all">
                <span class="dashicons dashicons-trash"></span>
                <span><?php echo esc_html(wzsi_t('btn_clear_all')); ?></span>
            </button>
        </div>
    </div>

</div>

<script>
jQuery(function($) {
    var trend = <?php echo wp_json_encode($trend); ?>;
    if (trend && trend.length) {
        var labels = trend.map(function(r) { return r.day; });
        var totals = trend.map(function(r) { return parseInt(r.total) || 0; });
        var zeros  = trend.map(function(r) { return parseInt(r.zero_count) || 0; });
        var ctx = document.getElementById('wzsm-trend-chart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '<?php echo esc_js(wzsi_t('stat_total_searches')); ?>',
                            data: totals,
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34,113,177,0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: '<?php echo esc_js(wzsi_t('stat_zero_results')); ?>',
                            data: zeros,
                            borderColor: '#d63638',
                            backgroundColor: 'rgba(214,54,56,0.15)',
                            tension: 0.3,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    }

    $('.wzsm-delete-term').on('click', function(e) {
        e.preventDefault();
        if (!confirm(wzsm.i18n.confirm_delete_term)) return;
        var $btn = $(this);
        var term = $btn.data('term');
        $.post(wzsm.ajax_url, {
            action: 'wzsi_delete_term',
            nonce: wzsm.nonce,
            term: term
        }, function(resp) {
            if (resp && resp.success) {
                $btn.closest('tr').fadeOut(300, function(){ $(this).remove(); });
            }
        });
    });

    $('#wzsm-clear-all').on('click', function(e) {
        e.preventDefault();
        if (!confirm(wzsm.i18n.confirm_delete)) return;
        $.post(wzsm.ajax_url, {
            action: 'wzsi_clear_all',
            nonce: wzsm.nonce
        }, function(resp) {
            if (resp && resp.success) {
                location.reload();
            }
        });
    });
});
</script>
