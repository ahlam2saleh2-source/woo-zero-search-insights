<?php
/**
 * صفحة السجلات - تعرض كل عمليات البحث المسجلة
 * v1.2.0: طريقتا عرض (قائمة / مجمع حسب المنتج) + فلتر المستخدم + أزرار إشعار الزبائن
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

    <?php // F-5 + F-10: فلاتر السجلات — كل القيم عبر GET مع تحقق في render_logs_page ?>
    <form method="get" class="wzsm-filters-bar">
        <input type="hidden" name="page" value="wzsi-logs">
        <input type="hidden" name="zero_only" value="<?php echo esc_attr($zero_only); ?>">
        <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
        <input type="search" name="q" value="<?php echo esc_attr($q); ?>"
               placeholder="<?php echo esc_attr(wzsi_t('filter_search')); ?>">
        <?php if ('list' === $view) : ?>
        <select name="user_id">
            <option value="0" <?php selected($user_id, 0); ?>><?php echo esc_html(wzsi_t('filter_all_users')); ?></option>
            <option value="-1" <?php selected($user_id, -1); ?>><?php echo esc_html(wzsi_t('filter_guests_only')); ?></option>
            <?php foreach ($logs_users as $lu) : ?>
                <option value="<?php echo (int) $lu->id; ?>" <?php selected($user_id, (int) $lu->id); ?>>
                    <?php echo esc_html($lu->name . ' (' . number_format_i18n((int) $lu->zeros) . ' ' . wzsi_t('filter_zeros_short') . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label><?php echo esc_html(wzsi_t('filter_from')); ?> <input type="date" name="from" value="<?php echo esc_attr($from_raw); ?>"></label>
        <label><?php echo esc_html(wzsi_t('filter_to')); ?> <input type="date" name="to" value="<?php echo esc_attr($to_raw); ?>"></label>
        <select name="type">
            <option value="any" <?php selected($type, 'any'); ?>><?php echo esc_html(wzsi_t('filter_all_types')); ?></option>
            <option value="0" <?php selected($type, '0'); ?>>Page</option>
            <option value="1" <?php selected($type, '1'); ?>>AJAX</option>
        </select>
        <button type="submit" class="button button-primary"><?php echo esc_html(wzsi_t('filter_apply')); ?></button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wzsi-logs&zero_only=' . (int) $zero_only . '&view=' . $view)); ?>" class="button"><?php echo esc_html(wzsi_t('filter_reset')); ?></a>
    </form>

    <div class="wzsm-tabs">
        <a href="<?php echo esc_url(add_query_arg('zero_only', 1, $base_url)); ?>"
           class="nav-tab <?php echo $zero_only ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html(wzsi_t('tab_zero_only')); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('zero_only', 0, $base_url)); ?>"
           class="nav-tab <?php echo !$zero_only ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html(wzsi_t('tab_all_logs')); ?>
        </a>
        <span style="width:24px;display:inline-block;"></span>
        <a href="<?php echo esc_url(add_query_arg('view', 'list', $base_url)); ?>"
           class="nav-tab <?php echo ('list' === $view) ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html(wzsi_t('view_list')); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('view', 'grouped', $base_url)); ?>"
           class="nav-tab <?php echo ('grouped' === $view) ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html(wzsi_t('view_grouped')); ?>
        </a>
    </div>

<?php if ('grouped' === $view) : ?>

    <?php // ═══ F-10: العرض المجمع حسب المنتج المبحوث ═══ ?>
    <?php if (empty($logs)) : ?>
        <div class="wzsm-empty">
            <div class="wzsm-empty-icon">📦</div>
            <h3><?php echo esc_html(wzsi_t('empty_no_logs')); ?></h3>
            <p><?php echo esc_html(wzsi_t('empty_no_logs_desc')); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped wzsm-table">
            <thead>
                <tr>
                    <th width="40"><?php echo esc_html(wzsi_t('col_id')); ?></th>
                    <th><?php echo esc_html(wzsi_t('col_product_searched')); ?></th>
                    <th width="100"><?php echo esc_html(wzsi_t('col_searches')); ?></th>
                    <th width="120"><?php echo esc_html(wzsi_t('col_registered_users')); ?></th>
                    <th width="100"><?php echo esc_html(wzsi_t('col_guests')); ?></th>
                    <th width="180"><?php echo esc_html(wzsi_t('col_last_searched')); ?></th>
                    <th width="220"><?php echo esc_html(wzsi_t('col_action')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $i = $offset + 1; foreach ($logs as $g) : ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="wzsm-term"><strong><?php echo esc_html($g->term); ?></strong></td>
                        <td><strong><?php echo esc_html(number_format_i18n((int) $g->searches)); ?></strong></td>
                        <td>
                            <?php if ((int) $g->reg_users > 0) : ?>
                                <span class="wzsm-tag wzsm-tag-user">&#128100; <?php echo esc_html(number_format_i18n((int) $g->reg_users)); ?></span>
                            <?php else : ?>
                                <span class="wzsm-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $g->guest_searches > 0) : ?>
                                <span class="wzsm-tag wzsm-tag-soft"><?php echo esc_html(number_format_i18n((int) $g->guest_searches)); ?></span>
                            <?php else : ?>
                                <span class="wzsm-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($g->last_at))); ?></td>
                        <td>
                            <?php if ((int) $g->reg_users > 0) : ?>
                                <button type="button" class="button button-primary button-small wzsi-notify-group"
                                        data-term="<?php echo esc_attr($g->term); ?>"
                                        data-users="<?php echo (int) $g->reg_users; ?>"
                                        data-guests="<?php echo (int) $g->guest_searches; ?>">
                                    &#128276; <?php echo esc_html(wzsi_t('btn_notify_group')); ?>
                                </button>
                            <?php else : ?>
                                <span class="wzsm-muted wzsm-no-notify"><?php echo esc_html(wzsi_t('ntf_guests_only_note')); ?></span>
                            <?php endif; ?>
                        </td>
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
    <?php endif; ?>

<?php else : ?>

    <?php // ═══ عرض القائمة (السجلات الفردية) ═══ ?>
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
                    <th width="150"><?php echo esc_html(wzsi_t('col_user')); ?></th>
                    <th width="180"><?php echo esc_html(wzsi_t('col_date')); ?></th>
                    <th width="80"><?php echo esc_html(wzsi_t('col_repeat')); ?></th>
                    <th width="150"><?php echo esc_html(wzsi_t('col_action')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $i = $offset + 1; foreach ($logs as $log) :
                    $log_user = ($log->user_id > 0) ? get_user_by('id', $log->user_id) : false;
                ?>
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
                            if ($log_user) {
                                $u_link = add_query_arg(array('user_id' => (int) $log_user->ID, 'zero_only' => 1, 'view' => 'list'), $base_url);
                                echo '<a class="wzsm-user-link" href="' . esc_url($u_link) . '">' . esc_html($log_user->display_name) . '</a>';
                            } else {
                                echo '<span class="wzsm-muted">' . esc_html(wzsi_t('guest')) . '</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($log->searched_at))); ?></td>
                        <td><?php echo $log->occurrence_count > 1 ? '<strong>' . esc_html($log->occurrence_count) . 'x</strong>' : '1'; ?></td>
                        <td>
                            <?php if ($log->results_count == 0 && $log_user) : ?>
                                <button type="button" class="button button-small wzsi-notify-one"
                                        data-user="<?php echo (int) $log_user->ID; ?>"
                                        data-name="<?php echo esc_attr($log_user->display_name); ?>"
                                        data-email="<?php echo esc_attr($log_user->user_email); ?>"
                                        data-term="<?php echo esc_attr($log->search_term); ?>">
                                    &#128276; <?php echo esc_html(wzsi_t('btn_notify_one')); ?>
                                </button>
                            <?php elseif ($log->results_count == 0 && !$log_user) : ?>
                                <span class="wzsm-muted wzsm-no-notify" title="<?php echo esc_attr(wzsi_t('ntf_guests_cannot')); ?>">—</span>
                            <?php endif; ?>
                        </td>
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
<?php endif; ?>
</div>

<!-- F-9 (v1.2.0): نافذة إشعار الزبون — فردي وجماعي -->
<div id="wzsi-ntf-modal" style="display:none;">
    <div class="wzsi-map-backdrop"></div>
    <div class="wzsi-map-dialog" style="max-width:680px;max-height:88vh;overflow:auto;">
        <h3 style="margin-top:0;">&#128276; <?php echo esc_html(wzsi_t('ntf_modal_title')); ?></h3>

        <div id="wzsi-ntf-recipients-box" style="display:none;background:#f6f7f7;border:1px solid #e0e0e0;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
            <p style="margin:0 0 6px;font-weight:600;"><?php echo esc_html(wzsi_t('ntf_recipients')); ?></p>
            <div id="wzsi-ntf-recipients-list" style="max-height:150px;overflow:auto;"></div>
            <p class="description" style="margin:6px 0 0;" id="wzsi-ntf-guests-note"></p>
        </div>

        <div id="wzsi-ntf-single-box" style="display:none;background:#f6f7f7;border:1px solid #e0e0e0;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
            <p style="margin:0;font-weight:600;">
                &#128100; <span id="wzsi-ntf-single-name"></span>
                <span id="wzsi-ntf-single-email" style="color:#666;font-weight:400;"></span>
            </p>
        </div>

        <p style="margin:0 0 12px;">
            <strong><?php echo esc_html(wzsi_t('ntf_term_label')); ?>:</strong>
            <span class="wzsm-term" style="font-weight:700;" id="wzsi-ntf-term"></span>
        </p>

        <label style="display:block;margin-bottom:4px;"><?php echo esc_html(wzsi_t('ntf_product_label')); ?></label>
        <input type="hidden" id="wzsi-ntf-product-id" value="0">
        <input type="search" id="wzsi-ntf-product-search" class="regular-text" autocomplete="off"
               placeholder="<?php echo esc_attr(wzsi_t('map_search_placeholder')); ?>">
        <div id="wzsi-ntf-product-results"></div>
        <p class="wzsi-ntf-selected" style="display:none;color:#00a32a;font-weight:600;">&#9989; <span></span></p>

        <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('ntf_lang_label')); ?></label>
        <select id="wzsi-ntf-lang">
            <option value="auto"><?php echo esc_html(wzsi_t('ntf_lang_auto')); ?></option>
            <option value="ar"><?php echo esc_html(wzsi_t('lang_arabic')); ?></option>
            <option value="en"><?php echo esc_html(wzsi_t('lang_english')); ?></option>
        </select>

        <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('ntf_channels_label')); ?></label>
        <label style="margin-inline-end:18px;"><input type="checkbox" id="wzsi-ntf-ch-email" checked> <?php echo esc_html(wzsi_t('ntf_channel_email')); ?></label>
        <label><input type="checkbox" id="wzsi-ntf-ch-site"> <?php echo esc_html(wzsi_t('ntf_channel_site')); ?></label>

        <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('ntf_tpl_subject')); ?></label>
        <input type="text" id="wzsi-ntf-subject" class="large-text">

        <label style="display:block;margin:12px 0 4px;"><?php echo esc_html(wzsi_t('ntf_tpl_body')); ?></label>
        <textarea id="wzsi-ntf-body" class="large-text" rows="9"></textarea>
        <p class="description"><?php echo esc_html(wzsi_t('ntf_placeholders_hint')); ?></p>

        <div class="wzsi-map-actions" style="margin-top:16px;">
            <button type="button" class="button button-primary" id="wzsi-ntf-send">&#9993; <?php echo esc_html(wzsi_t('ntf_btn_send')); ?></button>
            <button type="button" class="button" id="wzsi-ntf-cancel"><?php echo esc_html(wzsi_t('map_btn_cancel')); ?></button>
            <span id="wzsi-ntf-status" style="margin-inline-start:10px;font-weight:600;"></span>
        </div>
    </div>
</div>
