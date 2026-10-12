<?php
/**
 * F-9 (v1.2.0): إشعارات الزبائن — إغلاق الحلقة مع العميل
 *
 * نوعان:
 *   فردي — من صفحة السجلات (فلترة حسب المستخدم): زر بجانب كل بحث صفري لمستخدم مسجل
 *   جماعي — من العرض المجمع حسب المنتج: كل المستخدمين المسجلين الذين بحثوا المصطلح دون نتيجة
 *
 * قناتا التسليم:
 *   site  — إشعار داخل المتجر (شريط ظاهر للعضو عند زيارته التالية + زر إخفاء)
 *   email — بريد HTML عبر wp_mail (للمستخدمين المسجلين ذوي البريد فقط؛ الضيوف لا يمكن إشعارهم)
 *
 * القوالب: عربي/إنجليزي قابلة للتعديل من الإعدادات مع حقول {placeholders}
 * سجل كل إرسال يُحفظ في جدول woo_zero_search_notifications مع الحالة وسبب الفشل.
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Notify
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** حقول القالب المتاحة في القوالب */
    public static function placeholders()
    {
        return array('{customer_name}', '{term}', '{product_name}', '{product_url}', '{site_name}');
    }

    /**
     * القوالب الافتراضية — صياغة تجارية محفزة للعودة (عربي + إنجليزي)
     */
    public static function default_templates()
    {
        return array(
            'ar' => array(
                'subject' => 'خبر يسعدنا: «{term}» أصبح متوفراً الآن في {site_name}',
                'body' => "عزيزي {customer_name}،\n\n"
                    . "تهانينا — بطلَبك ساعدتنا نكتشف ما يفتقده المتجر!\n\n"
                    . "المنتج الذي كنت تبحث عنه («{term}») أصبح متوفراً الآن:\n"
                    . "{product_name}\n{product_url}\n\n"
                    . "أضفناه بناءً على دراسة حقيقية لطلبات زبائننا وما يبحثون عنه فعلاً، "
                    . "ونحب أن تكون أول من يجرّبه.\n\n"
                    . "قسمنا الجديد لا يتوقف — نراك قريباً في {site_name}.\n"
                    . "فريق {site_name}",
            ),
            'en' => array(
                'subject' => 'Great news: "{term}" is now available at {site_name}',
                'body' => "Hello {customer_name},\n\n"
                    . "Thank you — your search helped us discover a gap in our catalog!\n\n"
                    . "The product you were looking for (\"{term}\") is now available:\n"
                    . "{product_name}\n{product_url}\n\n"
                    . "We added it based on real analysis of what our customers search for, "
                    . "and we'd love you to be among the first to try it.\n\n"
                    . "Our catalog keeps growing — see you soon at {site_name}.\n"
                    . "The {site_name} Team",
            ),
        );
    }

    private function __construct()
    {
        // واجهة المتجر: شريط الإشعارات للأعضاء
        add_action('wp_footer', array($this, 'render_inbox_banner'));

        if (is_admin()) {
            add_action('wp_ajax_wzsi_notify_recipients', array($this, 'ajax_recipients'));
            add_action('wp_ajax_wzsi_notify_send', array($this, 'ajax_send'));
            add_action('wp_ajax_wzsi_mail_test', array($this, 'ajax_mail_test'));
        }

        // زر الإخفاء من الواجهة الأمامية (للأعضاء فقط — لا حاجة لـ nopriv)
        add_action('wp_ajax_wzsi_notify_dismiss', array($this, 'ajax_dismiss'));
    }

    // ════════════ المستلمون ════════════

    /**
     * جمهور مصطلح صفري: المستخدمون المسجلون الذين بحثوه دون نتيجة + عدد الزوار
     *
     * @param string $term المصطلح كما ظهر في البحث (يُطبَّع داخلياً)
     * @return array {registered:[{id,name,email,searches,last}], guests:int}
     */
    public static function get_audience($term)
    {
        global $wpdb;
        $logs  = WZSI_Database::table_name();
        $users = $wpdb->users;
        $norm  = WZSI_Database::normalize_term($term);

        $registered = $wpdb->get_results($wpdb->prepare(
            "SELECT l.user_id AS id, u.display_name AS name, u.user_email AS email,
                    COUNT(*) AS searches, MAX(l.searched_at) AS last_at
             FROM {$logs} l
             INNER JOIN {$users} u ON u.ID = l.user_id
             WHERE l.search_term_normalized = %s
               AND l.results_count = 0
               AND l.user_id > 0
             GROUP BY l.user_id, u.display_name, u.user_email
             ORDER BY searches DESC
             LIMIT 100",
            $norm
        ));
        if (!is_array($registered)) {
            $registered = array();
        }

        $guests = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs}
             WHERE search_term_normalized = %s AND results_count = 0 AND user_id = 0",
            $norm
        ));

        return array('registered' => $registered, 'guests' => $guests);
    }

    // ════════════ القوالب ════════════

    /**
     * جلب القالب (افتراضي أو من الإعدادات) مع تعويض الحقول
     *
     * @param string $lang  ar|en|auto (auto = لغة المستخدم، وافتراضاً عربي للضيوف)
     * @param int    $user_id لتقرير لغة auto
     */
    public static function build_message($lang, $user_id, $term, $product_name, $product_url, $subject_override = '', $body_override = '')
    {
        $settings = get_option('wzsi_settings', array());
        $defaults = self::default_templates();

        if ('auto' === $lang) {
            $u = $user_id ? get_userdata($user_id) : false;
            $user_locale = $u ? (string) get_user_meta($u->ID, 'locale', true) : '';
            if ('' === $user_locale && $u) {
                $user_locale = (string) $u->locale; // خاصية WP_User تقرأ user_locale
            }
            if ('' === $user_locale) {
                $user_locale = (string) get_locale(); // لغة الموقع كافتراض
            }
            $lang = (0 === strpos($user_locale, 'ar')) ? 'ar' : 'en';
        }
        if (!in_array($lang, array('ar', 'en'), true)) {
            $lang = 'ar';
        }

        $tpl = $defaults[$lang];
        // القوالب المحفوظة تتقدم على الافتراضية إن لم تكن فارغة
        $saved_subj = isset($settings['notify_template_subject_' . $lang]) ? trim((string) $settings['notify_template_subject_' . $lang]) : '';
        $saved_body = isset($settings['notify_template_body_' . $lang]) ? trim((string) $settings['notify_template_body_' . $lang]) : '';
        $subject = ('' !== $subject_override) ? $subject_override : ('' !== $saved_subj ? $saved_subj : $tpl['subject']);
        $body    = ('' !== $body_override) ? $body_override    : ('' !== $saved_body ? $saved_body : $tpl['body']);

        $customer_name = '';
        $u = $user_id ? get_userdata($user_id) : false;
        if ($u) {
            $customer_name = trim($u->display_name);
        }
        if ('' === $customer_name) {
            $customer_name = wzsi_t('ntf_guest_greeting');
        }

        $map = array(
            '{customer_name}' => $customer_name,
            '{term}'          => (string) $term,
            '{product_name}'  => (string) $product_name,
            '{product_url}'   => (string) $product_url,
            '{site_name}'     => get_bloginfo('name'),
        );

        return array(
            'lang'    => $lang,
            'subject' => strtr($subject, $map),
            'body'    => strtr($body, $map),
        );
    }

    // ════════════ التسليم ════════════

    /**
     * قناة المتجر: إضافة الإشعار إلى صندوق العضو (يظهر عند زيارته التالية)
     */
    public static function deliver_site($user_id, $subject, $body, $product_url, $product_name, $notif_id)
    {
        $inbox = get_user_meta($user_id, 'wzsi_inbox', true);
        if (!is_array($inbox)) {
            $inbox = array();
        }
        // سقف 10 إشعارات — الأقدم يُزال
        if (count($inbox) >= 10) {
            usort($inbox, function ($a, $b) {
                return strcmp($a['created_at'], $b['created_at']);
            });
            $inbox = array_slice($inbox, -9);
        }
        $inbox[] = array(
            'id'           => (int) $notif_id,
            'subject'      => (string) $subject,
            'body'         => (string) $body,
            'product_url'  => (string) $product_url,
            'product_name' => (string) $product_name,
            'created_at'   => current_time('mysql'),
            'read'         => 0,
        );
        update_user_meta($user_id, 'wzsi_inbox', $inbox);
        return true;
    }

    /**
     * قناة البريد: إرسال HTML مع التقاط خطأ wp_mail_failed
     *
     * @return array {ok:bool, error:string}
     */
    public static function deliver_email($to, $subject, $body)
    {
        $last_error = '';
        $capture = function ($wp_error) use (&$last_error) {
            if (is_wp_error($wp_error)) {
                $last_error = $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $capture, 10, 1);

        $html = self::email_wrap($subject, $body);
        $sent = wp_mail(
            $to,
            $subject,
            $html,
            array('Content-Type: text/html; charset=UTF-8')
        );

        remove_action('wp_mail_failed', $capture, 10);
        return array('ok' => (bool) $sent, 'error' => $last_error);
    }

    /** غلاف HTML موحد للبريد (RTL تلقائي حسب لغة المتجر) */
    private static function email_wrap($subject, $body_text)
    {
        $settings = get_option('wzsi_settings', array());
        $default_lang = function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar';
        $is_rtl = ('ar' === ($settings['ui_lang'] ?? $default_lang));
        $dir = $is_rtl ? 'rtl' : 'ltr';
        $align = $is_rtl ? 'right' : 'left';

        // نص القالب → فقرات HTML آمنة (القيم سبق تعويضها مقيدة)
        $paras = explode("\n\n", (string) $body_text);
        $html_paras = '';
        foreach ($paras as $p) {
            $p = trim($p);
            if ('' === $p) {
                continue;
            }
            $safe = esc_html($p);
            $safe = make_clickable($safe);
            $safe = nl2br($safe);
            $html_paras .= '<p style="margin:0 0 12px;text-align:' . $align . ';color:#333;font-size:14px;line-height:1.8;">' . $safe . '</p>';
        }

        $site = esc_html(get_bloginfo('name'));
        return '<!DOCTYPE html><html dir="' . $dir . '"><head><meta charset="UTF-8"></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f7;font-family:Tahoma,Arial,sans-serif;">'
            . '<div style="max-width:600px;margin:0 auto;padding:24px 16px;">'
            . '<div style="background:#fff;border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;">'
            . '<div style="background:#1d2327;padding:18px 20px;color:#fff;font-size:16px;font-weight:700;">'
            . esc_html($subject)
            . '</div>'
            . '<div style="padding:20px;">' . $html_paras . '</div>'
            . '<div style="background:#f6f7f7;padding:12px 20px;text-align:center;color:#999;font-size:11px;">'
            . $site . '</div>'
            . '</div></div></body></html>';
    }

    /**
     * تسجيل الإرسال في جدول السجل
     */
    public static function record($args)
    {
        global $wpdb;
        $t = self::table_name();
        $wpdb->insert($t, array(
            'user_id'      => (int) ($args['user_id'] ?? 0),
            'user_email'   => (string) ($args['user_email'] ?? ''),
            'term'         => (string) ($args['term'] ?? ''),
            'product_id'   => (int) ($args['product_id'] ?? 0),
            'product_url'  => (string) ($args['product_url'] ?? ''),
            'channel'      => in_array($args['channel'] ?? '', array('site', 'email'), true) ? $args['channel'] : 'email',
            'status'       => in_array($args['status'] ?? '', array('sent', 'failed'), true) ? $args['status'] : 'sent',
            'error_text'   => (string) ($args['error_text'] ?? ''),
            'lang'         => in_array($args['lang'] ?? '', array('ar', 'en'), true) ? $args['lang'] : 'ar',
            'sent_by'      => get_current_user_id(),
            'created_at'   => current_time('mysql'),
        ), array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s'));
        return (int) $wpdb->insert_id;
    }

    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'woo_zero_search_notifications';
    }

    // ════════════ واجهة المتجر الأمامية ════════════

    /**
     * شريط إشعارات العضو أسفل الصفحة (يظهر فقط عند وجود إشعارات غير مقروءة)
     */
    public function render_inbox_banner()
    {
        if (!is_user_logged_in() || is_admin()) {
            return;
        }
        $inbox = get_user_meta(get_current_user_id(), 'wzsi_inbox', true);
        if (!is_array($inbox) || empty($inbox)) {
            return;
        }
        $unread = array_values(array_filter($inbox, function ($n) {
            return empty($n['read']);
        }));
        if (empty($unread)) {
            return;
        }

        $is_rtl = is_rtl();
        $dir  = $is_rtl ? 'rtl' : 'ltr';
        $side = $is_rtl ? 'right' : 'left';

        $n = $unread[0]; // نعرض الأحدث، الزر يخفي الجميع
        $subj = esc_html($n['subject']);
        $body = esc_html(wp_strip_all_tags($n['body']));
        if (mb_strlen($body) > 220) {
            $body = mb_substr($body, 0, 220) . '…';
        }
        $url = esc_url($n['product_url']);
        $link = $url ? '<a href="' . $url . '" style="color:#2271b1;font-weight:600;text-decoration:none;">' . esc_html(wzsi_t('ntf_view_product')) . ' →</a>' : '';

        $nonce = wp_create_nonce('wzsi_front_nonce');
        $count = count($unread);
        $label = sprintf(esc_html(wzsi_t('ntf_new_notifs')), number_format_i18n($count));

        echo '<div id="wzsi-inbox" dir="' . esc_attr($dir) . '" style="position:fixed;bottom:18px;' . esc_attr($side) . ':18px;z-index:99999;max-width:340px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.18);font-family:inherit;padding:16px;">'
            . '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
            . '<strong style="font-size:13px;color:#1d2327;">' . $subj . '</strong>'
            . '<button type="button" id="wzsi-inbox-close" title="' . esc_attr(wzsi_t('ntf_dismiss')) . '" style="background:none;border:0;cursor:pointer;font-size:18px;line-height:1;color:#666;">&times;</button>'
            . '</div>'
            . '<div style="font-size:12.5px;color:#444;line-height:1.7;margin-bottom:10px;">' . $body . '</div>'
            . '<div style="display:flex;justify-content:space-between;align-items:center;">'
            . '<span style="font-size:11px;color:#888;">' . $label . '</span>'
            . ($link ? '<span style="font-size:13px;">' . $link . '</span>' : '')
            . '</div></div>'
            . '<script>(function(){var b=document.getElementById("wzsi-inbox");if(!b)return;'
            . 'document.getElementById("wzsi-inbox-close").addEventListener("click",function(){'
            . 'b.style.display="none";'
            . 'var x=new XMLHttpRequest();x.open("POST", "' . esc_url_raw(admin_url('admin-ajax.php')) . '");'
            . 'x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");'
            . 'x.send("action=wzsi_notify_dismiss&nonce=' . esc_attr($nonce) . '");});})();</script>';
    }

    public function ajax_dismiss()
    {
        check_ajax_referer('wzsi_front_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(array(), 403);
        }
        $inbox = get_user_meta(get_current_user_id(), 'wzsi_inbox', true);
        if (is_array($inbox)) {
            foreach ($inbox as $k => $n) {
                $inbox[$k]['read'] = 1;
            }
            update_user_meta(get_current_user_id(), 'wzsi_inbox', $inbox);
        }
        wp_send_json_success();
    }

    // ════════════ AJAX الإدارة ════════════

    private function guard()
    {
        check_ajax_referer(WZSI_NONCE_ACTION, 'nonce');
        if (!current_user_can(WZSI_CAP)) {
            wp_send_json_error(array('msg' => 'forbidden'), 403);
        }
    }

    /**
     * جمهور مصطلح — للنافذة الجماعية
     */
    public function ajax_recipients()
    {
        $this->guard();
        $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
        if ('' === trim($term)) {
            wp_send_json_error(array('msg' => wzsi_t('ntf_error_term')));
        }
        $aud = self::get_audience($term);
        wp_send_json_success(array(
            'registered' => $aud['registered'],
            'guests'     => (int) $aud['guests'],
        ));
    }

    /**
     * فحص صحة البريد + إرسال تجريبي — من صفحة فحص الصحة
     */
    public function ajax_mail_test()
    {
        $this->guard();
        $to = isset($_POST['to']) ? sanitize_email(wp_unslash($_POST['to'])) : '';
        if (!is_email($to)) {
            wp_send_json_error(array('msg' => wzsi_t('ntf_error_email')));
        }

        $subject = sprintf('[%s] %s', get_bloginfo('name'), wzsi_t('mail_test_subject'));
        $body    = wzsi_t('mail_test_body');
        $res     = self::deliver_email($to, $subject, $body);

        // حفظ نتيجة الفحص للعرض في صفحة فحص الصحة
        update_option('wzsi_mail_health', array(
            'ok'      => (bool) $res['ok'],
            'to'      => $to,
            'error'   => (string) $res['error'],
            'checked_at' => current_time('mysql'),
            'checks'  => self::mail_diagnostics(),
        ), false);

        wp_send_json_success(array(
            'ok'      => (bool) $res['ok'],
            'error'   => (string) $res['error'],
            'checks'  => self::mail_diagnostics(),
        ));
    }

    /**
     * تشخيصات بيئة البريد — "دراسة آلية اختبار الإضافة للبريد"
     */
    public static function mail_diagnostics()
    {
        global $wp_version;
        $checks = array();

        $checks['wp_mail_exists'] = function_exists('wp_mail');
        $checks['from_address']   = (string) get_option('admin_email');
        $from_filtered = has_filter('wp_mail_from');
        $checks['custom_from']    = $from_filtered ? 1 : 0;

        $smtp_plugins = array(
            'WP Mail SMTP'      => defined('WPMS_PLUGIN_URL') || class_exists('WPMailSMTP\\Options'),
            'Post SMTP'         => class_exists('PostmanNotificationCenter'),
            'Easy WP SMTP'      => class_exists('Easy_WP_SMTP'),
            'FluentSMTP'        => defined('FLUENTSMTP'),
        );
        $active = array();
        foreach ($smtp_plugins as $name => $on) {
            if ($on) {
                $active[] = $name;
            }
        }
        $checks['smtp_plugin'] = implode(', ', $active);

        $checks['mail_return_path'] = has_filter('wp_mail_from_name') ? 1 : 0;
        $last_health = get_option('wzsi_mail_health', array());
        $checks['last_result'] = !empty($last_health['ok']) ? 'ok' : (!empty($last_health['checked_at']) ? 'failed' : 'never');

        return $checks;
    }

    /**
     * الإرسال — فردي أو جماعي
     *
     * POST: user_ids (csv أو all) | term | product_id | lang (ar|en|auto)
     *       channels (csv: site,email) | subject | body (اختياريان — تجاوز القالب)
     */
    public function ajax_send()
    {
        global $wpdb;
        $this->guard();

        $term   = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
        $pid    = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $lang   = isset($_POST['lang']) ? sanitize_key(wp_unslash($_POST['lang'])) : 'ar';
        $chan_raw = isset($_POST['channels']) ? sanitize_text_field(wp_unslash($_POST['channels'])) : 'email';
        $channels = array_intersect(array('site', 'email'), array_map('trim', explode(',', $chan_raw)));
        if (empty($channels)) {
            $channels = array('email');
        }
        $subject_raw = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $body_raw    = isset($_POST['body']) ? sanitize_textarea_field(wp_unslash($_POST['body'])) : '';
        $override    = ('' !== $subject_raw || '' !== $body_raw);

        if ('' === trim($term)) {
            wp_send_json_error(array('msg' => wzsi_t('ntf_error_term')));
        }

        $product = $pid ? wc_get_product($pid) : null;
        if (!$product && $pid && get_post($pid)) {
            $product_name = get_the_title($pid);
            $product_url  = (string) get_permalink($pid);
        } elseif ($product) {
            $product_name = $product->get_name();
            $product_url  = (string) $product->get_permalink();
        } else {
            wp_send_json_error(array('msg' => wzsi_t('ntf_error_product')));
        }

        // المستلمون: قائمة صريحة أو "all" = كل المسجلين لذاك المصطلح
        $ids_in = isset($_POST['user_ids']) ? sanitize_text_field(wp_unslash($_POST['user_ids'])) : '';
        if ('all' === $ids_in) {
            $aud = self::get_audience($term);
            $recipients = $aud['registered'];
        } else {
            $ids = array_filter(array_map('intval', explode(',', $ids_in)));
            $ids = array_slice(array_unique($ids), 0, 100);
            $recipients = array();
            foreach ($ids as $uid) {
                $u = get_userdata($uid);
                if ($u) {
                    $recipients[] = (object) array(
                        'id'       => (int) $u->ID,
                        'name'     => $u->display_name,
                        'email'    => $u->user_email,
                        'searches' => 0,
                    );
                }
            }
        }

        if (empty($recipients)) {
            wp_send_json_error(array('msg' => wzsi_t('ntf_no_recipients')));
        }

        $sent_site = 0;
        $sent_email = 0;
        $failed_email = 0;
        $errors = array();

        foreach ($recipients as $r) {
            $uid = (int) $r->id;
            $msg = self::build_message($lang, $uid, $term, $product_name, $product_url,
                $override ? $subject_raw : '', $override ? $body_raw : '');

            if (in_array('site', $channels, true)) {
                $nid = self::record(array(
                    'user_id' => $uid, 'user_email' => (string) $r->email, 'term' => $term,
                    'product_id' => $pid, 'product_url' => $product_url,
                    'channel' => 'site', 'status' => 'sent', 'lang' => $msg['lang'],
                ));
                if ($nid) {
                    self::deliver_site($uid, $msg['subject'], $msg['body'], $product_url, $product_name, $nid);
                    $sent_site++;
                }
            }

            if (in_array('email', $channels, true) && is_email((string) $r->email)) {
                $res = self::deliver_email($r->email, $msg['subject'], $msg['body']);
                self::record(array(
                    'user_id' => $uid, 'user_email' => (string) $r->email, 'term' => $term,
                    'product_id' => $pid, 'product_url' => $product_url,
                    'channel' => 'email',
                    'status'  => $res['ok'] ? 'sent' : 'failed',
                    'error_text' => $res['error'],
                    'lang'    => $msg['lang'],
                ));
                if ($res['ok']) {
                    $sent_email++;
                } else {
                    $failed_email++;
                    if (count($errors) < 3) {
                        $errors[] = (string) $r->email . ': ' . $res['error'];
                    }
                }
            }
        }

        wp_send_json_success(array(
            'sent_site'  => $sent_site,
            'sent_email' => $sent_email,
            'failed'     => $failed_email,
            'errors'     => $errors,
            'msg'        => wzsi_t('ntf_sent'),
        ));
    }
}
