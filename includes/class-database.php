<?php
/**
 * فئة قاعدة البيانات - إنشاء الجدول والعمليات CRUD
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Database
{
    /**
     * اسم الجدول (مع prefix)
     *
     * @return string
     */
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . WZSI_DB_TABLE;
    }

    /**
     * إنشاء جدول السجلات
     * يستخدم dbDelta لضمان ترقية آمنة
     */
    public static function create_table()
    {
        global $wpdb;
        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // تأكد من تحميل dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            search_term VARCHAR(255) NOT NULL,
            search_term_normalized VARCHAR(255) NOT NULL,
            results_count INT(11) NOT NULL DEFAULT 0,
            searched_at DATETIME NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_ip VARCHAR(100) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            referer VARCHAR(255) DEFAULT NULL,
            language VARCHAR(10) DEFAULT NULL,
            is_ajax TINYINT(1) NOT NULL DEFAULT 0,
            occurrence_count INT(11) NOT NULL DEFAULT 1,
            last_occurrence DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_search_term (search_term_normalized),
            KEY idx_searched_at (searched_at),
            KEY idx_user_id (user_id),
            KEY idx_results_count (results_count)
        ) {$charset_collate};";

        dbDelta($sql);

        // F-4: جدول الربط الذكي (مصطلح صفري ← منتج/تصنيف/رابط) — منفصل عن جدول السجلات
        $maps_table = self::maps_table_name();
        $sql_maps = "CREATE TABLE {$maps_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            term_normalized VARCHAR(255) NOT NULL,
            target_type VARCHAR(20) NOT NULL DEFAULT 'product',
            target_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            target_url VARCHAR(500) DEFAULT NULL,
            hits INT(11) NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_map_term (term_normalized)
        ) {$charset_collate};";
        dbDelta($sql_maps);

        // F-9 (v1.2.0): جدول سجل الإشعارات المرسلة للزبائن
        $notifs_table = self::notifs_table_name();
        $sql_notifs = "CREATE TABLE {$notifs_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_email VARCHAR(190) NOT NULL DEFAULT '',
            term VARCHAR(255) NOT NULL DEFAULT '',
            product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            product_url VARCHAR(500) DEFAULT NULL,
            channel VARCHAR(20) NOT NULL DEFAULT 'email',
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            error_text TEXT NULL,
            lang VARCHAR(5) NOT NULL DEFAULT 'ar',
            sent_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_notif_user (user_id),
            KEY idx_notif_created (created_at)
        ) {$charset_collate};";
        dbDelta($sql_notifs);

        // الإصلاح: كان "CREATE INDEX IF NOT EXISTS" صيغة MariaDB فقط وتسبب خطأ DB صامت على MySQL.
        // الآن نفحص وجود الفهرس أولاً بطريقة متوافقة مع MySQL وMariaDB معًا.
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
        $idx_exists = false;
        foreach ((array) $indexes as $idx) {
            if (isset($idx->Key_name) && 'idx_term_at' === $idx->Key_name) {
                $idx_exists = true;
                break;
            }
        }
        if (!$idx_exists) {
            $wpdb->query("CREATE INDEX idx_term_at ON {$table_name} (search_term_normalized, searched_at)");
        }
    }

    /**
     * اسم جدول الربط الذكي (مع prefix)
     *
     * @return string
     */
    public static function maps_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'woo_zero_search_maps';
    }

    /**
     * F-9: اسم جدول سجل الإشعارات (مع prefix)
     *
     * @return string
     */
    public static function notifs_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'woo_zero_search_notifications';
    }

    /**
     * إدراج سجل بحث جديد أو تحديث عدد التكرارات إن كان موجوداً
     *
     * @param array $data البيانات المراد إدراجها
     * @return int|false  معرف السجل أو false عند الفشل
     */
    public static function insert_log($data)
    {
        global $wpdb;
        $table_name = self::table_name();

        // تطبيع المصطلح: lowercase + trim + إزالة الرموز الزائدة
        $normalized = self::normalize_term($data['search_term']);

        // تحقق من تكرار نفس البحث في آخر 5 دقائق لنفس المستخدم/IP
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name}
             WHERE search_term_normalized = %s
               AND searched_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
               AND user_ip = %s
             LIMIT 1",
            $normalized,
            $data['user_ip'] ?? ''
        ));

        if ($existing) {
            // تحديث عدد التكرارات + تحديث results_count لآخر نتيجة فعلية
            // الإصلاح: كان البحث المتكرر بعد إضافة منتج يبقى محسوبًا "بلا نتائج" خطأً
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table_name}
                 SET occurrence_count = occurrence_count + 1,
                     last_occurrence = NOW(),
                     results_count = %d
                 WHERE id = %d",
                max(0, (int) $data['results_count']),
                $existing
            ));
            return (int) $existing;
        }

        // إدراج جديد
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'search_term'             => $data['search_term'],
                'search_term_normalized'  => $normalized,
                'results_count'           => $data['results_count'],
                'searched_at'             => current_time('mysql'),
                'user_id'                 => $data['user_id'] ?? 0,
                'user_ip'                 => $data['user_ip'] ?? null,
                'user_agent'              => $data['user_agent'] ?? null,
                'referer'                 => $data['referer'] ?? null,
                'language'                => $data['language'] ?? null,
                'is_ajax'                 => $data['is_ajax'] ?? 0,
                'last_occurrence'         => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        // TD-1: إبطال كاش اللوحة عند أي كتابة
        self::flush_cache();

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * حفظ عملية بحث فورية (AJAX / REST) مع دمج دفعة الكتابة
     *
     * ضغطات الكتابة المتتالية لنفس المستخدم خلال دقيقتين ("sh" ثم "shi" ثم "shirt")
     * تُدمج في سجل واحد يُحدَّث لمصطلحها الأخير وعدد نتائجه الحقيقي،
     * فلا يتضخم السجل ولا تضيع عمليات البحث الناجحة.
     *
     * @param array $data بيانات البحث
     * @return int|false معرف السجل أو false عند الفشل
     */
    public static function log_ajax_search($data)
    {
        global $wpdb;
        $table_name = self::table_name();

        $normalized = self::normalize_term($data['search_term']);
        $ip = isset($data['user_ip']) ? (string) $data['user_ip'] : '';

        if ('' !== $ip) {
            $prev = $wpdb->get_row($wpdb->prepare(
                "SELECT id, search_term_normalized FROM {$table_name}
                 WHERE user_ip = %s AND is_ajax = 1
                   AND searched_at > DATE_SUB(NOW(), INTERVAL 120 SECOND)
                 ORDER BY id DESC
                 LIMIT 1",
                $ip
            ));

            if ($prev && !empty($prev->search_term_normalized)) {
                $prev_norm = (string) $prev->search_term_normalized;
                // نفس دفعة الكتابة: المصطلح الجديد امتداد للسابق أو العكس (تصحيح Backspace)
                $same_burst = (0 === strpos($normalized, $prev_norm))
                    || (0 === strpos($prev_norm, $normalized));

                if ($same_burst) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$table_name}
                         SET search_term = %s,
                             search_term_normalized = %s,
                             results_count = %d,
                             searched_at = NOW(),
                             last_occurrence = NOW(),
                             occurrence_count = occurrence_count + 1
                         WHERE id = %d",
                        sanitize_text_field($data['search_term']),
                        $normalized,
                        max(0, (int) $data['results_count']),
                        (int) $prev->id
                    ));
                    return (int) $prev->id;
                }
            }
        }

        // دفعة جديدة — إدراج عادي عبر المسار الموحد
        $data['is_ajax'] = 1;
        return self::insert_log($data);
    }

    /**
     * تطبيع مصطلح البحث (للتجميع)
     * F-2: توحيد عربي ذكي — إزالة التشكيل والتطويل وتوحيد الهمزات/التاء المربوطة/الياء
     * حتى تجتمع "أحذية / احذيه / أحذيةَ" كفرصة واحدة دون تغيير النص الأصلي المعروض
     *
     * @param string $term
     * @return string
     */
    public static function normalize_term($term)
    {
        $term = mb_strtolower(trim($term), 'UTF-8');
        $term = preg_replace('/\s+/', ' ', $term);

        // نطبق التوحيد العربي فقط عند وجود حروف عربية (الإنجليزية كما هي)
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $term)) {
            // إزالة التشكيل (الفتحة/الضمة/الكسرة/السكون/التنوين/المدّ) والتطويل
            $term = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $term);
            // توحيد الحروف المتشابهة كتابةً واختلف استخداماً
            $term = strtr($term, array(
                'أ' => 'ا',
                'إ' => 'ا',
                'آ' => 'ا',
                'ٱ' => 'ا',
                'ى' => 'ي',
                'ة' => 'ه',
                'ؤ' => 'و',
                'ئ' => 'ي',
            ));
        }
        return $term;
    }

    /**
     * جلب أعلى عمليات البحث بدون نتائج (top N)
     *
     * @param int $limit عدد النتائج
     * @param int $days  عدد الأيام الأخيرة (0 = الكل)
     * @return array
     */
    public static function get_top_zero_searches($limit = 20, $days = 0)
    {
        global $wpdb;
        $table_name = self::table_name();

        // TD-1: كاش 5 دقائق يُبطَل عند أي كتابة
        $cache_key = self::cache_key('top_' . (int) $limit . '_' . (int) $days);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $where = "WHERE results_count = 0";
        if ($days > 0) {
            $where .= $wpdb->prepare(" AND searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        }

        $results = $wpdb->get_results(
            "SELECT
                search_term,
                search_term_normalized,
                SUM(occurrence_count) AS total_searches,
                COUNT(*) AS unique_searches,
                MIN(searched_at) AS first_seen,
                MAX(last_occurrence) AS last_seen
             FROM {$table_name}
             {$where}
             GROUP BY search_term_normalized
             ORDER BY total_searches DESC, unique_searches DESC
             LIMIT " . (int) $limit
        );

        set_transient($cache_key, $results, 5 * MINUTE_IN_SECONDS);
        return $results;
    }

    /**
     * إحصائيات شاملة
     *
     * @param int $days عدد الأيام (0 = الكل)
     * @return array
     */
    public static function get_stats($days = 0)
    {
        global $wpdb;
        $table_name = self::table_name();

        // TD-1: كاش 5 دقائق يُبطَل عند أي كتابة
        $cache_key = self::cache_key('stats_' . (int) $days);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $where = "WHERE 1=1";
        if ($days > 0) {
            $where .= $wpdb->prepare(" AND searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        }

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total_searches,
                SUM(occurrence_count) AS total_with_repeats,
                SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero_results,
                SUM(CASE WHEN results_count = 0 THEN occurrence_count ELSE 0 END) AS zero_with_repeats,
                COUNT(DISTINCT search_term_normalized) AS unique_terms,
                COUNT(DISTINCT CASE WHEN results_count = 0 THEN search_term_normalized ELSE NULL END) AS unique_zero_terms
             FROM {$table_name}
             {$where}"
        );

        $result = array(
            'total_searches'      => (int) ($stats->total_searches ?? 0),
            'total_with_repeats'  => (int) ($stats->total_with_repeats ?? 0),
            'zero_results'         => (int) ($stats->zero_results ?? 0),
            'zero_with_repeats'    => (int) ($stats->zero_with_repeats ?? 0),
            'unique_terms'         => (int) ($stats->unique_terms ?? 0),
            'unique_zero_terms'    => (int) ($stats->unique_zero_terms ?? 0),
            'zero_rate'            => ($stats->total_searches > 0)
                ? round(($stats->zero_results / $stats->total_searches) * 100, 1)
                : 0,
        );

        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        return $result;
    }

    /**
     * F-6: إحصاءات الفترة الحالية مقابل الفترة السابقة (نفس الطول)
     *
     * @param int $days
     * @return array
     */
    public static function get_compare_stats($days = 30)
    {
        global $wpdb;
        $table_name = self::table_name();
        $days = max(1, (int) $days);

        $cache_key = self::cache_key('cmp_' . $days);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $cur = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero
             FROM {$table_name}
             WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        $prev = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero
             FROM {$table_name}
             WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)
               AND searched_at <= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days * 2, $days
        ));

        $rate = static function ($r) {
            if (!$r || (int) $r->total === 0) {
                return null;
            }
            return round(((int) $r->zero / (int) $r->total) * 100, 1);
        };

        $result = array(
            'current_total'  => (int) ($cur->total ?? 0),
            'previous_total' => (int) ($prev->total ?? 0),
            'current_zero'   => (int) ($cur->zero ?? 0),
            'previous_zero'  => (int) ($prev->zero ?? 0),
            'current_rate'   => $rate($cur),
            'previous_rate'  => $rate($prev),
        );

        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        return $result;
    }

    /**
     * F-7: مجموع مرات بحث مصطلح معين خلال آخر 24 ساعة (عتبة الإشعارات)
     *
     * @param string $normalized المصطلح المطبعّن
     * @return int
     */
    public static function get_term_occurrences_24h($normalized)
    {
        global $wpdb;
        $table_name = self::table_name();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(occurrence_count) FROM {$table_name}
             WHERE search_term_normalized = %s
               AND searched_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $normalized
        ));
    }

    /**
     * F-5: جلب السجلات مع فلاتر (بحث نصي، صفرية، تاريخ من/إلى، نوع) + ترقيم
     *
     * @param array $args q|zero_only|from|to (DateTime)|type|page|per_page
     * @return array rows|total|page|per_page|pages
     */
    public static function get_logs_filtered($args = array())
    {
        global $wpdb;
        $table_name = self::table_name();

        $where  = "WHERE 1=1";
        $params = array();

        if (!empty($args['zero_only'])) {
            $where .= " AND results_count = 0";
        }
        if (!empty($args['q'])) {
            $where .= " AND search_term_normalized LIKE %s";
            $params[] = '%' . $wpdb->esc_like(self::normalize_term((string) $args['q'])) . '%';
        }
        if (!empty($args['from']) && $args['from'] instanceof DateTime) {
            $where .= " AND searched_at >= %s";
            $params[] = $args['from']->format('Y-m-d 00:00:00');
        }
        if (!empty($args['to']) && $args['to'] instanceof DateTime) {
            $where .= " AND searched_at <= %s";
            $params[] = $args['to']->format('Y-m-d 23:59:59');
        }
        if (isset($args['type']) && null !== $args['type'] && 'any' !== $args['type']) {
            $where .= " AND is_ajax = %d";
            $params[] = (int) $args['type'];
        }
        // F-10 (v1.2.0): فلتر المستخدم — null = الكل، -1 = الضيوف، >0 = مستخدم محدد
        if (isset($args['user_id']) && null !== $args['user_id']) {
            $uid = (int) $args['user_id'];
            if (-1 === $uid) {
                $where .= " AND user_id = 0";
            } elseif ($uid > 0) {
                $where .= " AND user_id = %d";
                $params[] = $uid;
            }
        }

        $per_page = max(1, (int) ($args['per_page'] ?? 50));
        $page     = max(1, (int) ($args['page'] ?? 1));
        $offset   = ($page - 1) * $per_page;

        $count_sql = "SELECT COUNT(*) FROM {$table_name} {$where}";
        $total = (int) (empty($params) ? $wpdb->get_var($count_sql) : $wpdb->get_var($wpdb->prepare($count_sql, $params)));

        $rows_sql = "SELECT * FROM {$table_name} {$where} ORDER BY searched_at DESC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results($wpdb->prepare($rows_sql, array_merge($params, array($per_page, $offset))));

        return array(
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => (int) ceil($total / $per_page),
        );
    }

    /**
     * جلب آخر عمليات البحث
     *
     * @param int $limit
     * @param int $zero_only 1=فقط بدون نتائج
     * @return array
     */
    public static function get_recent_searches($limit = 50, $zero_only = 0)
    {
        global $wpdb;
        $table_name = self::table_name();

        $where = $zero_only ? "WHERE results_count = 0" : "WHERE 1=1";

        return $wpdb->get_results(
            "SELECT *
             FROM {$table_name}
             {$where}
             ORDER BY searched_at DESC
             LIMIT " . (int) $limit
        );
    }

    /**
     * F-10 (v1.2.0): المستخدمون الذين لديهم عمليات بحث مسجلة — لقائمة فلترة المستخدم
     *
     * @return array[] {id, name, searches, zeros, last_at}
     */
    public static function get_logs_users($limit = 200)
    {
        global $wpdb;
        $t = self::table_name();
        $u = $wpdb->users;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT l.user_id AS id, u.display_name AS name,
                    COUNT(*) AS searches,
                    SUM(CASE WHEN l.results_count = 0 THEN 1 ELSE 0 END) AS zeros,
                    MAX(l.searched_at) AS last_at
             FROM {$t} l
             INNER JOIN {$u} u ON u.ID = l.user_id
             WHERE l.user_id > 0
             GROUP BY l.user_id, u.display_name
             ORDER BY zeros DESC, searches DESC
             LIMIT %d",
            max(1, (int) $limit)
        ));

        return is_array($rows) ? $rows : array();
    }

    /**
     * F-10 (v1.2.0): العرض المجمع حسب المنتج المبحوث (المصطلح)
     * كل صف = مصطلح صفري النتائج مع جمهوره: مسجلون (قابلون للإشعار) + ضيوف (غير قابلين)
     *
     * @param array $args {page, per_page, q, days}
     * @return array {rows, total, page, per_page, pages}
     */
    public static function get_grouped_terms($args = array())
    {
        global $wpdb;
        $t = self::table_name();

        $where  = "WHERE results_count = 0";
        $params = array();

        if (!empty($args['q'])) {
            $where .= " AND search_term_normalized LIKE %s";
            $params[] = '%' . $wpdb->esc_like(self::normalize_term((string) $args['q'])) . '%';
        }
        if (!empty($args['days']) && (int) $args['days'] > 0) {
            $where .= " AND searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)";
            $params[] = (int) $args['days'];
        }

        $per_page = max(1, (int) ($args['per_page'] ?? 25));
        $page     = max(1, (int) ($args['page'] ?? 1));
        $offset   = ($page - 1) * $per_page;

        $count_sql = "SELECT COUNT(DISTINCT search_term_normalized) FROM {$t} {$where}";
        $total = (int) (empty($params) ? $wpdb->get_var($count_sql) : $wpdb->get_var($wpdb->prepare($count_sql, $params)));

        $rows_sql = "SELECT search_term_normalized AS norm,
                    COUNT(*) AS searches,
                    COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id END) AS reg_users,
                    SUM(CASE WHEN user_id > 0 THEN 1 ELSE 0 END) AS reg_searches,
                    SUM(CASE WHEN user_id = 0 THEN 1 ELSE 0 END) AS guest_searches,
                    MAX(searched_at) AS last_at
             FROM {$t} {$where}
             GROUP BY search_term_normalized
             ORDER BY searches DESC, last_at DESC
             LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results($wpdb->prepare($rows_sql, array_merge($params, array($per_page, $offset))));
        if (!is_array($rows)) {
            $rows = array();
        }

        // نص العرض: أحدث صيغة أصلية للمصطلح (استعلام واحد لكل الصفحة — متوافق مع MySQL و SQLite)
        if (!empty($rows)) {
            $norms = array();
            foreach ($rows as $r) {
                $norms[] = $r->norm;
            }
            $placeholders = implode(',', array_fill(0, count($norms), '%s'));
            $display = $wpdb->get_results($wpdb->prepare(
                "SELECT search_term_normalized, search_term
                 FROM {$t}
                 WHERE search_term_normalized IN ({$placeholders})
                 ORDER BY id DESC",
                $norms
            ));
            $seen = array();
            if (is_array($display)) {
                foreach ($display as $d) {
                    if (!isset($seen[$d->search_term_normalized])) {
                        $seen[$d->search_term_normalized] = $d->search_term;
                    }
                }
            }
            foreach ($rows as $r) {
                $r->term = isset($seen[$r->norm]) ? $seen[$r->norm] : $r->norm;
            }
        }

        return array(
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => (int) ceil($total / $per_page),
        );
    }

    /**
     * اتجاه يومي (للرسم البياني)
     *
     * F-6: عند $compare = true يعيد مصفوفتين (current + previous) بنفس طول الفترة
     *
     * @param int  $days
     * @param bool $compare هل نُرجع الفترة السابقة أيضًا؟
     * @return array|object[]
     */
    public static function get_daily_trend($days = 30, $compare = false)
    {
        global $wpdb;
        $table_name = self::table_name();
        $days = max(1, (int) $days);

        // TD-1: كاش 5 دقائق يُبطَل عند أي كتابة
        $cache_key = self::cache_key('trend_' . $days . '_' . ($compare ? '1' : '0'));
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $current = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(searched_at) AS day,
                COUNT(*) AS total,
                SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero_count
             FROM {$table_name}
             WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(searched_at)
             ORDER BY day ASC",
            $days
        ));

        if (!$compare) {
            set_transient($cache_key, $current, 5 * MINUTE_IN_SECONDS);
            return $current;
        }

        $previous = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(searched_at) AS day,
                COUNT(*) AS total,
                SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero_count
             FROM {$table_name}
             WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)
               AND searched_at <= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(searched_at)
             ORDER BY day ASC",
            $days * 2, $days
        ));

        $result = array('current' => $current, 'previous' => $previous);
        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        return $result;
    }

    /**
     * حذف السجلات الأقدم من عدد أيام محدد
     *
     * @param int $days
     * @return int عدد الصفوف المحذوفة
     */
    public static function cleanup_old_logs($days)
    {
        global $wpdb;
        $table_name = self::table_name();

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name}
             WHERE searched_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            (int) $days
        ));

        self::flush_cache();

        return $deleted !== false ? (int) $deleted : 0;
    }

    /**
     * حذف كل السجلات
     *
     * @return bool
     */
    public static function truncate_logs()
    {
        global $wpdb;
        $table_name = self::table_name();
        $result = $wpdb->query("TRUNCATE TABLE {$table_name}") !== false;
        self::flush_cache();
        return $result;
    }

    /**
     * حذف سجلات محددة (بناءً على المصطلح المطبع)
     *
     * @param string $normalized_term
     * @return int
     */
    public static function delete_by_term($normalized_term)
    {
        global $wpdb;
        $table_name = self::table_name();
        $deleted = (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE search_term_normalized = %s",
            $normalized_term
        ));
        self::flush_cache();
        return $deleted;
    }

    /**
     * TD-1: مفتاح إصدار الكاش — الإبطال برفع الإصدار (لا SQL خام)
     * الحذف المباشر من جدول options يتجاوز كاش الذاكرة لوردبريس داخل نفس العملية
     * فيعود الصف القديم — رفع الإصدار يجعل المفاتيح القديمة غير مقروءة نهائياً
     * وتنتهي صلاحيتها طبيعياً بعد 5 دقائق
     */
    public static function cache_ver()
    {
        return max(1, (int) get_option('wzsi_cache_ver', 1));
    }

    public static function flush_cache()
    {
        update_option('wzsi_cache_ver', self::cache_ver() + 1, false);
    }

    private static function cache_key($name)
    {
        return 'wzsi_cache_v' . self::cache_ver() . '_' . $name;
    }

    /**
     * F-2: بدء/متابعة ترحيل التطبيع العربي — دفعات صغيرة مع قفل منع التزامن
     * يُستدعى من admin_init عند دخول الأدمين ومن النبضة اليومية wzsi_daily_tick
     *
     * @return array
     */
    public static function maybe_migrate_normalized_terms()
    {
        if (get_option('wzsi_norm_migration_done')) {
            return array('done' => true);
        }

        // قفل منع تزامن دورتين معًا
        if (get_transient('wzsi_norm_migrating')) {
            return array('done' => false, 'locked' => true);
        }
        set_transient('wzsi_norm_migrating', 1, 2 * MINUTE_IN_SECONDS);

        $result = self::migrate_normalized_terms(2000);

        delete_transient('wzsi_norm_migrating');
        return $result;
    }

    /**
     * F-2: إعادة تطبيع دفعة من السجلات القديمة من النص الأصلي
     * (search_term يحفظ النص كما كتبه الزائر بما فيه التشكيل — نعيد التطبيع منه)
     *
     * @param int $batch_size
     * @return array
     */
    public static function migrate_normalized_terms($batch_size = 2000)
    {
        global $wpdb;
        $table = self::table_name();
        $cursor = (int) get_option('wzsi_norm_migration_cursor', 0);

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, search_term FROM {$table} WHERE id > %d ORDER BY id ASC LIMIT %d",
            $cursor, (int) $batch_size
        ));

        if (empty($rows)) {
            update_option('wzsi_norm_migration_done', 1, false);
            delete_option('wzsi_norm_migration_cursor');
            self::flush_cache();
            return array('done' => true);
        }

        foreach ($rows as $row) {
            $new_norm = self::normalize_term((string) $row->search_term);
            $wpdb->update(
                $table,
                array('search_term_normalized' => $new_norm),
                array('id' => (int) $row->id),
                array('%s'),
                array('%d')
            );
            $cursor = (int) $row->id;
        }
        update_option('wzsi_norm_migration_cursor', $cursor, false);

        $remaining = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE id > %d", $cursor));

        if ($remaining === 0) {
            update_option('wzsi_norm_migration_done', 1, false);
            delete_option('wzsi_norm_migration_cursor');
            self::flush_cache();
        }

        return array(
            'done'      => ($remaining === 0),
            'processed' => count($rows),
            'remaining' => $remaining,
        );
    }

    /**
     * F-2: حالة الترحيل (تُعرض في فحص الصحة)
     *
     * @return array
     */
    public static function migration_status()
    {
        if (get_option('wzsi_norm_migration_done')) {
            return array('done' => true, 'remaining' => 0);
        }
        global $wpdb;
        $table = self::table_name();
        $cursor = (int) get_option('wzsi_norm_migration_cursor', 0);
        $remaining = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE id > %d", $cursor));
        return array('done' => false, 'remaining' => $remaining);
    }

    /**
     * حذف الجدول كاملاً (للإزالة)
     */
    public static function drop_table()
    {
        global $wpdb;
        $table_name = self::table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        $wpdb->query("DROP TABLE IF EXISTS " . self::maps_table_name());
    }
}
