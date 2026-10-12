<?php
/**
 * F-4: الربط الذكي — ربط مصطلح بحث صفري بمنتج/تصنيف/رابط
 *
 * الحلقة الكاملة: الإضافة تكتشف الفجوة، وهذه الكلاس تتيح سدّها فوراً.
 * السلوك في واجهة المتجر ثلاثي (إعداد mapping_behavior):
 *   none       (الافتراضي) — لا تغيير إطلاقاً على نتائج البحث
 *   add_result — عند بحث منتجات صفري يُضاف منتج الربط للنتائج (pre_get_posts)
 *   redirect   — تطابق تام مع المصطلح يحوّل الزائر للهدف (302)
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Mappings
{
    private static $instance = null;

    /**
     * حارس ضد الاستدعاء الذاتي: استعلام البروب (WP_Query بباراميتر s)
     * يُطلق pre_get_posts من جديد — بدونه يرى البروب المنتج المربوط عبر نفس
     * الفلتر فيظن أن هناك نتائج ولا يُطبق الربط أبداً
     */
    private static $in_mapping_probe = false;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function table_name()
    {
        return WZSI_Database::maps_table_name();
    }

    private function __construct()
    {
        // سلوك واجهة المتجر — يعمل فقط عند تفعيل صريح من الإعدادات
        add_action('template_redirect', array($this, 'maybe_redirect'), 3);
        add_filter('pre_get_posts', array($this, 'maybe_add_result'), 99);

        // واجهة الإدارة (AJAX)
        if (is_admin()) {
            add_action('wp_ajax_wzsi_search_products', array($this, 'ajax_search_products'));
            add_action('wp_ajax_wzsi_add_mapping', array($this, 'ajax_add_mapping'));
            add_action('wp_ajax_wzsi_delete_mapping', array($this, 'ajax_delete_mapping'));
            add_action('wp_ajax_wzsi_toggle_mapping', array($this, 'ajax_toggle_mapping'));
        }
    }

    // ════════════ CRUD ════════════

    /**
     * جلب ربط نشط لمصطلح (استعلام واحد مفهرس — لا يظهر إلا في صفحات البحث)
     */
    public static function find_active_mapping($term)
    {
        global $wpdb;
        $t = self::table_name();
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$t} WHERE term_normalized = %s AND active = 1 LIMIT 1",
            WZSI_Database::normalize_term($term)
        ));
    }

    public static function add_mapping($term, $target_type, $target_id = 0, $target_url = '')
    {
        global $wpdb;
        $t = self::table_name();

        $norm = WZSI_Database::normalize_term($term);
        if ('' === $norm) {
            return false;
        }

        $data = array(
            'term_normalized' => $norm,
            'target_type'     => in_array($target_type, array('product', 'category', 'url'), true) ? $target_type : 'product',
            'target_id'       => max(0, (int) $target_id),
            'target_url'      => esc_url_raw((string) $target_url),
            'created_at'      => current_time('mysql'),
            'created_by'      => get_current_user_id(),
            'active'          => 1,
        );

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE term_normalized = %s", $norm));
        if ($exists) {
            $wpdb->update($t, $data, array('id' => (int) $exists));
            return (int) $exists;
        }

        $inserted = $wpdb->insert($t, $data);
        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function delete_mapping($id)
    {
        global $wpdb;
        return (int) $wpdb->delete(self::table_name(), array('id' => (int) $id), array('%d'));
    }

    public static function toggle_mapping($id)
    {
        global $wpdb;
        $t = self::table_name();
        $wpdb->query($wpdb->prepare("UPDATE {$t} SET active = 1 - active WHERE id = %d", (int) $id));
        return (int) $wpdb->get_var($wpdb->prepare("SELECT active FROM {$t} WHERE id = %d", (int) $id));
    }

    public static function bump_hits($id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . self::table_name() . " SET hits = hits + 1 WHERE id = %d", (int) $id));
    }

    /**
     * رابط الهدف حسب نوعه
     */
    public static function mapping_url($mapping)
    {
        if (!$mapping) {
            return '';
        }
        if ('url' === $mapping->target_type && !empty($mapping->target_url)) {
            return esc_url_raw($mapping->target_url);
        }
        if ('product' === $mapping->target_type && !empty($mapping->target_id)) {
            $permalink = get_permalink((int) $mapping->target_id);
            return $permalink ? $permalink : '';
        }
        if ('category' === $mapping->target_type && !empty($mapping->target_id)) {
            $link = get_term_link((int) $mapping->target_id, 'product_cat');
            return (is_wp_error($link)) ? '' : $link;
        }
        return '';
    }

    // ════════════ سلوك واجهة المتجر ════════════

    /**
     * سلوك redirect — تحويل فوري عند التطابق التام
     * أولوية 3: قبل التقاط التتبع (5) حتى لا يُسجل البحث قبل التحويل
     */
    public function maybe_redirect()
    {
        if (!is_search() || is_admin()) {
            return;
        }

        $settings = get_option('wzsi_settings', array());
        if ('redirect' !== ($settings['mapping_behavior'] ?? 'none')) {
            return;
        }

        $term = get_search_query(false);
        if (!$term) {
            return;
        }

        $mapping = self::find_active_mapping($term);
        if (!$mapping) {
            return;
        }

        $url = self::mapping_url($mapping);
        if (!$url) {
            return;
        }

        self::bump_hits((int) $mapping->id);
        wp_safe_redirect($url, 302);
        exit;
    }

    /**
     * سلوك add_result — إضافة منتج الربط للنتائج عند بحث منتجات صفري
     */
    public function maybe_add_result($query)
    {
        if (self::$in_mapping_probe || is_admin() || !$query->is_search()) {
            return $query;
        }

        $settings = get_option('wzsi_settings', array());
        if ('add_result' !== ($settings['mapping_behavior'] ?? 'none')) {
            return $query;
        }

        // نطاق بحث المنتجات فقط (كما في خطة F-4)
        $pt = $query->get('post_type');
        $is_product = ('product' === $pt) || (is_array($pt) && in_array('product', $pt, true));
        if (!$is_product) {
            return $query;
        }

        $term = $query->get('s');
        if (!$term || !is_string($term)) {
            return $query;
        }

        $mapping = self::find_active_mapping($term);
        if (!$mapping || 'product' !== $mapping->target_type || empty($mapping->target_id)) {
            return $query;
        }

        // فقط عند الصفرية: عدّ خفيف بلا تحميل نتائج (بحارس ضد الاستدعاء الذاتي)
        self::$in_mapping_probe = true;
        $probe = new WP_Query(array(
            's'              => $term,
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
        ));
        self::$in_mapping_probe = false;
        $found = (int) $probe->found_posts;

        if ($found > 0) {
            return $query;
        }

        self::bump_hits((int) $mapping->id);

        $query->set('post__in', array((int) $mapping->target_id));
        $query->set('ignore_sticky_posts', true);

        // مهم: شرط البحث (LIKE على العنوان/المحتوى) يبقى في WHERE حتى مع post__in
        // فيستبعد المنتج المربوط لأن عنوانه لا يحمل المصطلح — نحيّده لهذا الطلب فقط
        add_filter('posts_search', array($this, 'neutralize_search_sql'), 999);
        return $query;
    }

    /**
     * يحيد شرط البحث لمرة واحدة (يُزال نفسه بعد أول تطبيق) ليُظهر post__in المنتج المربوط
     */
    public function neutralize_search_sql($search)
    {
        remove_filter('posts_search', array($this, 'neutralize_search_sql'), 999);
        return '';
    }

    // ════════════ واجهة الإدارة (AJAX) ════════════

    private function guard()
    {
        check_ajax_referer(WZSI_NONCE_ACTION, 'nonce');
        if (!current_user_can(WZSI_CAP)) {
            wp_send_json_error(array('msg' => 'forbidden'), 403);
        }
    }

    /**
     * بحث لحظي عن المنتجات/التصنيفات لاختيار هدف الربط
     */
    public function ajax_search_products()
    {
        $this->guard();

        $q = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
        $results = array();

        $query = new WP_Query(array(
            's'              => $q,
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));
        foreach ($query->posts as $pid) {
            $results[] = array(
                'id'    => (int) $pid,
                'title' => get_the_title($pid),
                'type'  => 'product',
            );
        }

        $cats = get_terms(array(
            'taxonomy'   => 'product_cat',
            'name__like' => $q,
            'number'     => 6,
            'hide_empty' => false,
        ));
        if (!is_wp_error($cats)) {
            foreach ($cats as $c) {
                $results[] = array(
                    'id'    => (int) $c->term_id,
                    'title' => '&#127968; ' . $c->name,
                    'type'  => 'category',
                );
            }
        }

        wp_send_json_success($results);
    }

    public function ajax_add_mapping()
    {
        $this->guard();

        $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
        $type = isset($_POST['target_type']) ? sanitize_key(wp_unslash($_POST['target_type'])) : 'product';
        $tid  = isset($_POST['target_id']) ? (int) $_POST['target_id'] : 0;
        $turl = isset($_POST['target_url']) ? esc_url_raw(wp_unslash($_POST['target_url'])) : '';

        if ('' === trim($term)) {
            wp_send_json_error(array('msg' => wzsi_t('map_error_term')));
        }
        if ('url' === $type && empty($turl)) {
            wp_send_json_error(array('msg' => wzsi_t('map_error_url')));
        }
        if ('url' !== $type && !$tid) {
            wp_send_json_error(array('msg' => wzsi_t('map_error_target')));
        }

        $id = self::add_mapping($term, $type, $tid, $turl);
        if (!$id) {
            wp_send_json_error(array('msg' => wzsi_t('map_failed')));
        }

        wp_send_json_success(array('id' => $id, 'msg' => wzsi_t('map_saved')));
    }

    public function ajax_delete_mapping()
    {
        $this->guard();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $deleted = self::delete_mapping($id);
        wp_send_json_success(array('deleted' => $deleted));
    }

    public function ajax_toggle_mapping()
    {
        $this->guard();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $active = self::toggle_mapping($id);
        wp_send_json_success(array('active' => $active));
    }
}
