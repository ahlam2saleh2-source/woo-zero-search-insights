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
                <?php if ($compare && null !== $compare['previous_rate'] && null !== $compare['current_rate']) :
                    $rate_diff = round($compare['current_rate'] - $compare['previous_rate'], 1);
                    $rate_arrow = ($rate_diff > 0) ? '&#9650;' : (($rate_diff < 0) ? '&#9660;' : '&#8226;');
                    // ارتفاع معدل الفشل سيء (أحمر) وانخفاضه جيد (أخضر)
                    $rate_color = ($rate_diff > 0) ? '#d63638' : '#00a32a';
                ?>
                <div class="stat-sub" style="color:<?php echo esc_attr($rate_color); ?>;font-weight:600;">
                    <?php printf('%s %s%% %s', $rate_arrow, number_format(abs($rate_diff), 1), esc_html(wzsi_t('stat_vs_previous'))); ?>
                </div>
                <?php endif; ?>
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
                        <th width="220"><?php echo esc_html(wzsi_t('col_action')); ?></th>
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
                                <button type="button" class="button button-small wzsm-map-btn"
                                        data-term="<?php echo esc_attr($row->search_term_normalized); ?>"
                                        title="<?php echo esc_attr(wzsi_t('map_modal_title')); ?>">
                                    &#128279; <?php echo esc_html(wzsi_t('map_btn_link')); ?>
                                </button>
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

<!-- F-4: نافذة الربط الذكي -->
<div id="wzsi-map-modal" style="display:none;">
    <div class="wzsi-map-backdrop"></div>
    <div class="wzsi-map-dialog">
        <h3 style="margin-top:0;">&#128279; <?php echo esc_html(wzsi_t('map_modal_title')); ?></h3>
        <p class="wzsm-subtitle" style="margin-bottom:10px;"><?php echo esc_html(wzsi_t('map_modal_desc')); ?></p>
        <label style="display:block;margin-bottom:4px;"><?php echo esc_html(wzsi_t('map_term_label')); ?></label>
        <input type="text" id="wzsi-map-term-input" class="regular-text" autocomplete="off">

        <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('map_target_type')); ?></label>
        <select id="wzsi-map-type">
            <option value="product"><?php echo esc_html(wzsi_t('map_type_product')); ?></option>
            <option value="category"><?php echo esc_html(wzsi_t('map_type_category')); ?></option>
            <option value="url"><?php echo esc_html(wzsi_t('map_type_url')); ?></option>
        </select>

        <div id="wzsi-map-search-wrap">
            <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('map_search_label')); ?></label>
            <input type="search" id="wzsi-map-search" class="regular-text" autocomplete="off" placeholder="<?php echo esc_attr(wzsi_t('map_search_placeholder')); ?>">
            <div id="wzsi-map-results"></div>
        </div>

        <div id="wzsi-map-url-wrap" style="display:none;">
            <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('map_url_label')); ?></label>
            <input type="url" id="wzsi-map-url" class="regular-text" placeholder="https://...">
        </div>

        <p class="wzsi-map-selected" style="display:none;color:#00a32a;font-weight:600;">
            &#9989; <span></span>
        </p>

        <div class="wzsi-map-actions" style="margin-top:16px;">
            <button type="button" class="button button-primary" id="wzsi-map-save"><?php echo esc_html(wzsi_t('map_btn_save')); ?></button>
            <button type="button" class="button" id="wzsi-map-cancel"><?php echo esc_html(wzsi_t('map_btn_cancel')); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var trend = <?php echo wp_json_encode($trend); ?>;
    // F-6: بيانات الفترة السابقة (مقارنة)
    var trendPrev = <?php echo wp_json_encode(isset($trend_previous) && is_array($trend_previous) ? $trend_previous : array()); ?>;
    var prevLabel = <?php echo wp_json_encode(wzsi_t('chart_previous_period')); ?>;

    // حارس: لا تنهار الصفحة إن تعذر تحميل Chart.js
    if (trend && trend.length && typeof Chart !== 'undefined') {
        var labels = trend.map(function(r) { return r.day; });
        var totals = trend.map(function(r) { return parseInt(r.total) || 0; });
        var zeros  = trend.map(function(r) { return parseInt(r.zero_count) || 0; });

        var datasets = [
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
        ];

        // F-6: خط متقطع للفترة السابقة (محاذاة بمؤشر اليوم)
        if (trendPrev && trendPrev.length) {
            var prevZeros = labels.map(function(_, i) {
                var j = trendPrev.length - labels.length + i;
                if (j < 0 || j >= trendPrev.length) return null;
                return parseInt(trendPrev[j].zero_count) || 0;
            });
            if (prevZeros.some(function(v) { return v !== null; })) {
                datasets.push({
                    label: prevLabel,
                    data: prevZeros,
                    borderColor: '#8c8f94',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0
                });
            }
        }

        var ctx = document.getElementById('wzsm-trend-chart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
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

    // ملاحظة: معالجات حذف المصطلح و"مسح الكل" تُدار في assets/js/admin.js فقط
    // (الإصلاح: كان مكرّرة هنا وفي admin.js معًا — يسبب طلبات AJAX مزدوجة وتأكيدات متكررة)
});
</script>
