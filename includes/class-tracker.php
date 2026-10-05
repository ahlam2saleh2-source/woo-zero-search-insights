<?php
/**
 * فئة المتتبع - تلتقط كل عمليات البحث في WooCommerce (مع النتائج وبدونها)
 *
 * المسارات المشمولة:
 *  1. صفحة نتائج البحث الكلاسيكية (?s=) — template_redirect بأولوية مبكرة
 *  2. البحث الفوري AJAX (wc-ajax=woocommerce_ajax_search) — مع النتائج وبدونها
 *  3. بحث القوالب الحديثة عبر REST Store API (/wc/store/...?search=)
 *  4. احتياط: خطاف woocommerce_no_products_found + shortcode
 *
 * دمج دفعة الكتابة: ضغطات الكتابة المتتالية في البحث الفوري تُدمج في سجل واحد
 * يحمل المصطلح النهائي، فلا يتضخم السجل ولا تضيع النتائج الناجحة.
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Tracker
{
    private static $instance = null;

    /**
     * قفل ضد التسجيل المزدوج لنفس الطلب
     * (template_redirect + woocommerce_no_products_found قد يعملان معًا على نفس البحث)
     */
    private static $logged_this_request = false;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // الاحتياط الأول: عند عرض صفحة "لا توجد منتجات" (لا يسجل إن سبق الالتقاط)
        add_action('woocommerce_no_products_found', array($this, 'capture_zero_search'), 10);
        add_action('woocommerce_shortcode_no_products', array($this, 'capture_shortcode_no_products'), 10, 1);

        // تتبع البحث الفوري AJAX إن وفره القالب أو إضافة بحث (مع النتائج وبدونها)
        add_action('wc_ajax_woocommerce_ajax_search', array($this, 'maybe_capture_ajax_search'), 5);

        // تتبع بحث القوالب الحديثة عبر REST Store API (WooCommerce Blocks)
        add_filter('rest_pre_dispatch', array($this, 'capture_rest_store_search'), 10, 3);

        // الالتقاط الرئيسي: كل عمليات بحث الصفحة (أولوية مبكرة قبل أي تحويل قالب)
        add_action('template_redirect', array($this, 'capture_search_query_on_template'), 5);
    }

    /**
     * جلب إعدادات الإضافة
     */
    private function get_settings()
    {
        $defaults = array(
            'enabled'             => 1,
            'retention_days'      => 90,
            'min_term_length'     => 2,
            'max_term_length'     => 100,
            'excluded_terms'      => '',
            'track_logged_in'     => 1,
            'track_anonymous'     => 1,
            'ip_anonymize'        => 1,
            'auto_cleanup'        => 1,
        );
        $saved = get_option('wzsi_settings', array());
        return wp_parse_args($saved, $defaults);
    }

    /**
     * جلب معلومات المستخدم بصورة مجهولة
     */
    private function get_user_info()
    {
        $settings = $this->get_settings();
        $user_id = get_current_user_id();

        // تخفي IP إن كان مفعل
        $ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
            if (!empty($settings['ip_anonymize'])) {
                $ip = $this->anonymize_ip($ip);
            }
        }

        $user_agent = !empty($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
            : '';
        $user_agent = substr($user_agent, 0, 255);

        $referer = !empty($_SERVER['HTTP_REFERER'])
            ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']))
            : '';
        $referer = substr($referer, 0, 255);

        // اللغة من خلال locale
        $language = substr(get_locale(), 0, 10);

        return array(
            'user_id'    => $user_id,
            'user_ip'    => $ip,
            'user_agent'  => $user_agent,
            'referer'     => $referer,
            'language'    => $language,
        );
    }

    /**
     * إخفاء جزء من IP لخصوصية أكبر
     */
    private function anonymize_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: إخفاء آخر أوكتيت
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: إخفاء آخر مقطعين
            $parts = explode(':', $ip);
            $count = count($parts);
            if ($count > 2) {
                $parts[$count - 1] = '0';
                $parts[$count - 2] = '0';
                return implode(':', $parts);
            }
        }
        return $ip;
    }

    /**
     * التحقق إن كان البحث صالحاً للتسجيل
     */
    private function should_log($term, $is_ajax = false)
    {
        $settings = $this->get_settings();

        if (empty($settings['enabled'])) {
            return false;
        }

        $term = trim($term);
        $len = function_exists('mb_strlen') ? mb_strlen($term) : strlen($term);

        // طول المصطلح ضمن النطاق المسموح
        if ($len < (int) $settings['min_term_length']) {
            return false;
        }
        if ($len > (int) $settings['max_term_length']) {
            return false;
        }

        // قائمة المصطلحات المستثناة
        if (!empty($settings['excluded_terms'])) {
            $excluded = array_filter(array_map('trim', explode("\n", $settings['excluded_terms'])));
            $term_lower = function_exists('mb_strtolower') ? mb_strtolower($term, 'UTF-8') : strtolower($term);
            foreach ($excluded as $ex) {
                if ($ex && stripos($term_lower, $ex) !== false) {
                    return false;
                }
            }
        }

        // تسجيل دخول / غير مسجل
        $user_id = get_current_user_id();
        if ($user_id > 0 && empty($settings['track_logged_in'])) {
            return false;
        }
        if ($user_id === 0 && empty($settings['track_anonymous'])) {
            return false;
        }

        return true;
    }

    /**
     * سجل تشخيصي مؤقت لآخر محاولات الالتقاط (يظهر في صفحة فحص الصحة)
     * يُكتب فقط داخل سياق بحث فعلي — الصفحات العادية لا تكتب شيئًا
     */
    private function debug_note($source, $term, $found, $decision, $reason = '')
    {
        $entries = get_option('wzsi_capture_debug', array());
        if (!is_array($entries)) {
            $entries = array();
        }
        array_unshift($entries, array(
            't'     => current_time('mysql'),
            'src'   => sanitize_text_field((string) $source),
            'term'  => function_exists('mb_substr') ? mb_substr((string) $term, 0, 60) : substr((string) $term, 0, 60),
            'found' => (int) $found,
            'do'    => ('logged' === $decision) ? 'logged' : 'skip',
            'why'   => sanitize_text_field((string) $reason),
            'uri'   => isset($_SERVER['REQUEST_URI'])
                ? substr(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), 0, 120)
                : '',
        ));
        if (count($entries) > 25) {
            $entries = array_slice($entries, 0, 25);
        }
        update_option('wzsi_capture_debug', $entries);
    }

    /**
     * تسجيل عملية بحث (مع نتائج أو بدونها) — النقطة المركزية للتسجيل
     *
     * @return bool هل سُجل فعلاً؟
     */
    private function log_search($term, $results_count, $is_ajax = 0)
    {
        if (!$this->should_log($term, $is_ajax)) {
            return false;
        }

        $user_info = $this->get_user_info();

        WZSI_Database::insert_log(array(
            'search_term'   => sanitize_text_field($term),
            'results_count' => max(0, (int) $results_count),
            'is_ajax'        => $is_ajax,
            'user_id'        => $user_info['user_id'],
            'user_ip'        => $user_info['user_ip'],
            'user_agent'      => $user_info['user_agent'],
            'referer'         => $user_info['referer'],
            'language'        => $user_info['language'],
        ));

        self::$logged_this_request = true;
        return true;
    }

    /**
     * تسجيل عملية بحث بدون نتائج (results_count = 0)
     */
    private function log_zero_search($term, $is_ajax = 0)
    {
        return $this->log_search($term, 0, $is_ajax);
    }

    /**
     * عدّ نتائج المنتجات لمصطلح بحث (استعلام خفيف مع found_rows)
     */
    private function count_product_results($term)
    {
        $args = array(
            's'              => $term,
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => array('exclude-from-search'),
                    'operator' => 'NOT IN',
                ),
            ),
        );
        $args = apply_filters('wzsi_count_search_args', $args, $term);

        $q = new WP_Query($args);
        $found = (int) $q->found_posts;
        wp_reset_postdata();

        return $found;
    }

    /**
     * حفظ عملية بحث فورية (AJAX / REST) مع دمج دفعة الكتابة
     *
     * @return bool هل حُفظ؟
     */
    private function persist_ajax_search($term, $found)
    {
        $user_info = $this->get_user_info();

        $id = WZSI_Database::log_ajax_search(array(
            'search_term'   => sanitize_text_field($term),
            'results_count' => max(0, (int) $found),
            'user_id'       => $user_info['user_id'],
            'user_ip'       => $user_info['user_ip'],
            'user_agent'    => $user_info['user_agent'],
            'referer'       => $user_info['referer'],
            'language'      => $user_info['language'],
        ));

        if ($id) {
            self::$logged_this_request = true;
            return true;
        }
        return false;
    }

    /**
     * التقاط خطاف woocommerce_no_products_found
     * يعمل كاحتياط فقط — لا يسجل إن سبق الالتقاط في نفس الطلب
     */
    public function capture_zero_search()
    {
        if (!is_search() || !function_exists('WC')) {
            return;
        }
        $term = get_search_query(false);
        if (self::$logged_this_request) {
            $this->debug_note('no_products_found', $term, 0, 'skip', 'سبق التسجيل في نفس الطلب');
            return;
        }
        if (!$term) {
            return;
        }
        $logged = $this->log_zero_search($term, 0);
        $this->debug_note('no_products_found', $term, 0, $logged ? 'logged' : 'skip', $logged ? 'احتياط: صفرية' : 'should_log رفض');
    }

    /**
     * التقاط Shortcode بدون نتائج (احتياط — يلتقط ما لا يغطيه template_redirect)
     */
    public function capture_shortcode_no_products($query)
    {
        if (self::$logged_this_request) {
            return;
        }
        if (!is_a($query, 'WC_Query') && !is_object($query)) {
            return;
        }
        $term = get_search_query(false);
        if (!$term) {
            return;
        }
        $logged = $this->log_zero_search($term, 0);
        $this->debug_note('shortcode_no_products', $term, 0, $logged ? 'logged' : 'skip', $logged ? 'احتياط: صفرية' : 'should_log رفض');
    }

    /**
     * التقاط البحث الفوري AJAX — يسجل الناجحة والصفرية معًا
     * (دمج دفعة الكتابة يمنع تضخيم السجل بكل ضغطة حرف)
     */
    public function maybe_capture_ajax_search()
    {
        $term = isset($_REQUEST['term']) ? sanitize_text_field(wp_unslash($_REQUEST['term'])) : '';
        if (!$term) {
            $term = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        }
        if (!$term) {
            return;
        }
        if (!$this->should_log($term, true)) {
            $this->debug_note('wc_ajax_search', $term, 0, 'skip', 'should_log رفض');
            return;
        }

        $found = $this->count_product_results($term);
        $logged = $this->persist_ajax_search($term, $found);
        $this->debug_note('wc_ajax_search', $term, $found, $logged ? 'logged' : 'skip', $logged ? 'بحث فوري AJAX' : 'فشل الحفظ');
    }

    /**
     * التقاط بحث REST Store API (/wc/store/... search=)
     * يستخدمه WooCommerce Blocks والقوالب الحديثة للبحث الفوري
     * لا يكسر REST إطلاقًا (try/catch + إرجاع النتيجة كما هي)
     */
    public function capture_rest_store_search($result, $server, $request)
    {
        try {
            if (!is_a($request, 'WP_REST_Request')) {
                return $result;
            }
            $route = $request->get_route();
            if (!is_string($route) || strpos($route, '/wc/store') !== 0) {
                return $result;
            }
            $term = $request->get_param('search');
            if (!is_string($term) || '' === trim($term)) {
                return $result;
            }
            $term = sanitize_text_field($term);

            if (!$this->should_log($term, true)) {
                $this->debug_note('rest_store_api', $term, 0, 'skip', 'should_log رفض');
                return $result;
            }

            $found = $this->count_product_results($term);
            $logged = $this->persist_ajax_search($term, $found);
            $this->debug_note('rest_store_api', $term, $found, $logged ? 'logged' : 'skip', $logged ? 'بحث REST' : 'فشل الحفظ');
        } catch (\Throwable $e) {
            try {
                $this->debug_note('rest_store_api', '', 0, 'skip', 'استثناء: ' . substr($e->getMessage(), 0, 60));
            } catch (\Exception $inner) {
                // تجاهل
            }
        }
        return $result;
    }

    /**
     * الالتقاط الرئيسي على template_redirect (أولوية 5 قبل أي تحويل)
     * يسجل كل عمليات بحث الصفحة: مع النتائج (results_count > 0) وبدونها (0)
     * — يجعل "Total Searches" ومعدل الفشل مؤشرين حقيقيين
     */
    public function capture_search_query_on_template()
    {
        if (!is_search() || !function_exists('WC') || is_admin()) {
            return; // ليست صفحة بحث — لا نكتب شيئًا
        }

        $term = get_search_query(false);

        if (self::$logged_this_request) {
            $this->debug_note('template_redirect', $term, 0, 'skip', 'سبق التسجيل في نفس الطلب');
            return;
        }
        if (!$term) {
            $this->debug_note('template_redirect', '', 0, 'skip', 's فارغ');
            return;
        }

        // نطاق البحث: منتجات مباشرة أم بحث عام؟
        $qtype = get_query_var('post_type');
        $scope = 'generic';

        if (is_array($qtype)) {
            if (!in_array('product', $qtype, true)) {
                $this->debug_note('template_redirect', $term, 0, 'skip', 'post_type مصفوفة بلا product');
                return;
            }
            $scope = 'product';
        } elseif ('product' === $qtype) {
            $scope = 'product';
        } elseif ('' !== $qtype && 'any' !== $qtype) {
            $this->debug_note('template_redirect', $term, 0, 'skip', 'post_type=' . sanitize_text_field((string) $qtype) . ' لا يشمل المنتجات');
            return;
        }

        // عدد النتائج الفعلي للاستعلام الرئيسي (0 = بلا نتائج)
        global $wp_query;
        $found = ($wp_query instanceof WP_Query) ? (int) $wp_query->found_posts : 0;

        // البحث العام: نعدّ المنتجات فقط لضمان دقة "الصفرية"
        if ('generic' === $scope) {
            $found = $this->count_product_results($term);
        }

        $logged = $this->log_search($term, $found, 0);
        $this->debug_note(
            'template_redirect',
            $term,
            $found,
            $logged ? 'logged' : 'skip',
            $logged ? ('scope=' . $scope) : 'should_log رفض (إعدادات)'
        );
    }
}
