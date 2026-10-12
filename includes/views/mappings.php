<?php
/**
 * F-4: صفحة الربط الذكي — إدارة ربطات المصطلحات الصفرية بالمنتجات/التصنيفات/الروابط
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can(WZSI_CAP)) {
    wp_die(wzsi_t('notice_no_permission'));
}

$mapping_behavior = $settings['mapping_behavior'] ?? 'none';
?>
<div class="wrap wzsm-wrap">

    <h1>&#128279; <?php echo esc_html(wzsi_t('menu_mappings')); ?></h1>
    <p class="wzsm-subtitle"><?php echo esc_html(wzsi_t('map_page_subtitle')); ?></p>

    <p style="background:#f0f6fc;border:1px solid #c5d9ed;border-radius:8px;padding:10px 14px;">
        <?php echo esc_html(wzsi_t('map_page_behavior_hint')); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wzsi-settings')); ?>"><?php echo esc_html(wzsi_t('btn_settings')); ?></a>
        —
        <code><?php echo esc_html($mapping_behavior); ?></code>
    </p>

    <div class="wzsm-section-header" style="margin-top:18px;">
        <h2 style="font-size:15px;"><?php echo esc_html(wzsi_t('map_add_heading')); ?></h2>
        <button type="button" class="button button-primary wzsi-map-new">&#10133; <?php echo esc_html(wzsi_t('map_btn_new')); ?></button>
    </div>

    <?php if (empty($mappings)) : ?>
        <div class="wzsm-empty">
            <div class="wzsm-empty-icon">&#128279;</div>
            <h3><?php echo esc_html(wzsi_t('map_empty_title')); ?></h3>
            <p><?php echo esc_html(wzsi_t('map_empty_desc')); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped wzsm-table">
            <thead>
                <tr>
                    <th width="40"><?php echo esc_html(wzsi_t('col_id')); ?></th>
                    <th><?php echo esc_html(wzsi_t('col_term')); ?></th>
                    <th><?php echo esc_html(wzsi_t('map_col_target')); ?></th>
                    <th width="100"><?php echo esc_html(wzsi_t('map_col_type')); ?></th>
                    <th width="90"><?php echo esc_html(wzsi_t('map_col_hits')); ?></th>
                    <th width="110"><?php echo esc_html(wzsi_t('map_col_status')); ?></th>
                    <th width="160"><?php echo esc_html(wzsi_t('col_action')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mappings as $m) : ?>
                    <?php
                    // اسم الهدف المقروء
                    $target_label = '';
                    if ('url' === $m->target_type) {
                        $target_label = (string) $m->target_url;
                    } elseif ('product' === $m->target_type && !empty($m->target_id)) {
                        $target_label = get_the_title((int) $m->target_id);
                        if (!$target_label) {
                            $target_label = '#' . (int) $m->target_id;
                        }
                    } elseif ('category' === $m->target_type && !empty($m->target_id)) {
                        $cat = get_term((int) $m->target_id, 'product_cat');
                        $target_label = (!is_wp_error($cat) && $cat) ? $cat->name : '#' . (int) $m->target_id;
                    }
                    ?>
                    <tr>
                        <td>#<?php echo (int) $m->id; ?></td>
                        <td class="wzsm-term"><strong><?php echo esc_html($m->term_normalized); ?></strong></td>
                        <td>
                            <?php if ('url' === $m->target_type && $m->target_url) : ?>
                                <a href="<?php echo esc_url($m->target_url); ?>" target="_blank" rel="noopener"><?php echo esc_html(wp_basename((string) parse_url($m->target_url, PHP_URL_PATH)) ?: $m->target_url); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($target_label); ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="wzsm-tag"><?php echo esc_html($m->target_type); ?></span></td>
                        <td><strong><?php echo esc_html(number_format_i18n((int) $m->hits)); ?></strong></td>
                        <td>
                            <?php if (!empty($m->active)) : ?>
                                <span style="color:#00a32a;font-weight:600;">&#9679; <?php echo esc_html(wzsi_t('status_active')); ?></span>
                            <?php else : ?>
                                <span style="color:#996800;">&#9679; <?php echo esc_html(wzsi_t('status_inactive')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small wzsm-map-toggle" data-id="<?php echo (int) $m->id; ?>">
                                <?php echo empty($m->active) ? esc_html(wzsi_t('map_btn_activate')) : esc_html(wzsi_t('map_btn_pause')); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete wzsm-map-delete" data-id="<?php echo (int) $m->id; ?>">
                                <?php echo esc_html(wzsi_t('btn_delete')); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- F-4: نافذة الربط (مشتركة مع اللوحة) -->
<div id="wzsi-map-modal" style="display:none;">
    <div class="wzsi-map-backdrop"></div>
    <div class="wzsi-map-dialog">
        <h3 style="margin-top:0;">&#128279; <?php echo esc_html(wzsi_t('map_modal_title')); ?></h3>
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
