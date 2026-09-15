<?php
/**
 * صفحة السجلات - تعرض كل عمليات البحث المسجلة
 * النصوص عبر نظام i18n المخصص
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can(WZSI_CAP)) {
    wp_die(wzsi_t('notice_no_permission'));
}

$base_url = admin_url('admin.php?page=wzsi-logs');
?>
<div class="wrap wzsm-wrap">

    <h1>📋 <?php echo esc_html(wzsi_t('page_logs')); ?></h1>
    <p class="wzsm-subtitle"><?php echo esc_html(wzsi_t('subtitle_logs')); ?></p>

    <div class="wzsm-tabs">
        <a href="<?php echo esc_url(add_query_arg('zero_only', 1, $base_url)); ?>"
           class="nav-tab <?php echo $zero_only ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html(wzsi_t('tab_zero_only')); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('zero_only', 0, $base_url)); ?>"
           class="nav-tab <?php echo !$zero_only ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html(wzsi_t('tab_all_logs')); ?>
        </a>
    </div>

    <?php if (empty($logs)) : ?>
        <div class="wzsm-empty">
            <div class="wzsm-empty-icon">📭</div>
            <h3><?php echo esc_html(wzsi_t('empty_no_logs')); ?></h3>
            <p><?php echo esc_html(wzsi_t('empty_no_logs_desc')); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped wzsm-table">
            <thead>
                <tr>
                    <th width="40"><?php echo esc_html(wzsi_t('col_id')); ?></th>
                    <th><?php echo esc_html(wzsi_t('col_term')); ?></th>
                    <th width="80"><?php echo esc_html(wzsi_t('col_results')); ?></th>
                    <th width="120"><?php echo esc_html(wzsi_t('col_type')); ?></th>
                    <th width="100"><?php echo esc_html(wzsi_t('col_user')); ?></th>
                    <th width="180"><?php echo esc_html(wzsi_t('col_date')); ?></th>
                    <th width="80"><?php echo esc_html(wzsi_t('col_repeat')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $i = $offset + 1; foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="wzsm-term"><strong><?php echo esc_html($log->search_term); ?></strong></td>
                        <td>
                            <?php if ($log->results_count == 0) : ?>
                                <span class="wzsm-zero-badge">0</span>
                            <?php else : ?>
                                <?php echo esc_html($log->results_count); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $log->is_ajax ? '<span class="wzsm-tag">AJAX</span>' : '<span class="wzsm-tag wzsm-tag-soft">Page</span>'; ?>
                        </td>
                        <td>
                            <?php
                            if ($log->user_id > 0) {
                                $user = get_user_by('id', $log->user_id);
                                echo $user ? esc_html('#' . $user->ID) : esc_html('#' . $log->user_id);
                            } else {
                                echo '<span class="wzsm-muted">' . esc_html(wzsi_t('guest')) . '</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($log->searched_at))); ?></td>
                        <td><?php echo $log->occurrence_count > 1 ? '<strong>' . esc_html($log->occurrence_count) . 'x</strong>' : '1'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        if ($pages > 1) :
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links(array(
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'current'   => $page,
                'total'     => $pages,
                'prev_text' => wzsi_t('prev'),
                'next_text' => wzsi_t('next'),
            ));
            echo '</div></div>';
        endif;
        ?>

        <div class="wzsm-export-bar">
            <form method="post" id="wzsm-export-form">
                <input type="hidden" name="wzsi_action" value="export_csv">
                <input type="hidden" name="wzsi_zero_only" value="<?php echo esc_attr($zero_only); ?>">
                <?php wp_nonce_field(WZSI_NONCE_ACTION, 'wzsi_nonce'); ?>
                <button type="submit" class="button button-primary">
                    <?php echo esc_html(wzsi_t('tab_export_this_list')); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
