<?php
/**
 * F-3: التقرير البريدي الدوري — ملخص يصل بريد صاحب المتجر دون دخول اللوحة
 *
 * التصميم: نبضة يومية واحدة (wzsi_daily_tick) تفحص بنفسها هل بلغ موعد التقرير
 * (أكثر موثوقية مع WP-Cron من جدولة أسبوعية هشة) مع حارس digest_last_sent ضد التكرار.
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Email
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wzsi_daily_tick', array($this, 'maybe_send_digest'));
    }

    /**
     * نطاق التقرير بالأيام حسب التردد المختار
     */
    private function period_days()
    {
        $settings = get_option('wzsi_settings', array());
        return ('daily' === ($settings['digest_frequency'] ?? 'weekly')) ? 1 : 7;
    }

    /**
     * معالج النبضة اليومية — يفحص هل حان موعد التقرير
     */
    public function maybe_send_digest()
    {
        $settings = get_option('wzsi_settings', array());

        if (empty($settings['digest_enabled'])) {
            return;
        }

        $email = sanitize_email($settings['digest_email'] ?? '');
        if (!is_email($email)) {
            return;
        }

        $interval = ('daily' === ($settings['digest_frequency'] ?? 'weekly')) ? DAY_IN_SECONDS : WEEK_IN_SECONDS;
        $last = strtotime((string) ($settings['digest_last_sent'] ?? ''));
        $now  = current_time('timestamp');

        // حارس التكرار: اترك هامش 60 ثانية للفروقات في مواعيد الكرون
        if ($last && ($last + $interval - 60) > $now) {
            return;
        }

        $this->send_digest(false);
    }

    /**
     * بناء التقرير وإرساله
     *
     * @param bool $is_test إرسال تجريبي من الإعدادات (لا يحدّث digest_last_sent)
     * @return array ['ok' => bool, 'msg' => string]
     */
    public function send_digest($is_test = false)
    {
        $settings = get_option('wzsi_settings', array());
        $email = sanitize_email($settings['digest_email'] ?? '');

        if (!is_email($email)) {
            return array('ok' => false, 'msg' => wzsi_t('digest_invalid_email'));
        }

        $days  = $this->period_days();
        $stats = WZSI_Database::get_stats($days);
        $top   = WZSI_Database::get_top_zero_searches(10, $days);
        $cmp   = WZSI_Database::get_compare_stats($days);

        $freq_label = ('daily' === ($settings['digest_frequency'] ?? 'weekly')) ? wzsi_t('digest_daily') : wzsi_t('digest_weekly');
        $subject = sprintf('[%s] %s — %s', get_bloginfo('name'), wzsi_t('digest_subject'), $freq_label);

        $html = $this->build_html($stats, $top, $cmp, $days, $settings, $freq_label);

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($email, $subject, $html, $headers);

        if ($sent && !$is_test) {
            $settings['digest_last_sent'] = current_time('mysql');
            update_option('wzsi_settings', $settings);
        }

        return $sent
            ? array('ok' => true, 'msg' => sprintf(wzsi_t('digest_sent_ok'), $email))
            : array('ok' => false, 'msg' => wzsi_t('digest_sent_fail'));
    }

    /**
     * قالب HTML بأسلوب داخلي (توافق عملاء البريد) مع دعم RTL تلقائي
     */
    private function build_html($stats, $top, $cmp, $days, $settings, $freq_label)
    {
        $default_lang = function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar';
        $is_rtl = ('ar' === ($settings['ui_lang'] ?? $default_lang));
        $dir    = $is_rtl ? 'rtl' : 'ltr';
        $align  = $is_rtl ? 'right' : 'left';

        // صفوف جدول أعلى المصطلحات
        $rows = '';
        if (!empty($top)) {
            foreach ($top as $i => $r) {
                $rows .= '<tr>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #eee;text-align:' . $align . ';color:#999;">' . ($i + 1) . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #eee;text-align:' . $align . ';font-weight:600;">' . esc_html($r->search_term) . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #eee;text-align:center;">' . (int) $r->total_searches . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #eee;text-align:center;color:#666;">' . esc_html(mysql2date(get_option('date_format'), $r->last_seen)) . '</td>'
                    . '</tr>';
            }
        } else {
            $rows = '<tr><td colspan="4" style="padding:14px;text-align:center;color:#777;">' . esc_html(wzsi_t('digest_no_terms')) . '</td></tr>';
        }

        // F-6: تغيّر معدل الفشل مقارنة بالفترة السابقة
        $delta_line = '';
        if ($cmp && null !== $cmp['previous_rate'] && null !== $cmp['current_rate']) {
            $diff  = round($cmp['current_rate'] - $cmp['previous_rate'], 1);
            $arrow = ($diff > 0) ? '&#9650;' : (($diff < 0) ? '&#9660;' : '&#8226;');
            // ارتفاع الفشل سيء (أحمر)، انخفاضه جيد (أخضر)
            $color = ($diff > 0) ? '#d63638' : '#00a32a';
            $delta_line = '<p style="margin:0 0 14px;color:' . $color . ';font-weight:600;font-size:13px;">'
                . sprintf('%s %s%% %s', $arrow, number_format(abs($diff), 1), esc_html(wzsi_t('digest_delta_vs_prev')))
                . '</p>';
        }

        $period_label = (1 === $days)
            ? esc_html(wzsi_t('digest_last_24h'))
            : esc_html(wzsi_t('digest_last_7d'));

        $footer_brand = empty($settings['white_label'])
            ? '<p style="color:#999;font-size:11px;margin:0;">' . sprintf(esc_html(wzsi_t('digest_footer')), esc_html(WZSI_VERSION)) . '</p>'
            : '';

        $cta = admin_url('admin.php?page=wzsi-dashboard');

        return '<!DOCTYPE html><html dir="' . $dir . '"><head><meta charset="UTF-8"></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f7;font-family:Tahoma,Arial,sans-serif;">'
            . '<div style="max-width:600px;margin:0 auto;padding:24px 16px;">'
            . '<div style="background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;">'
            . '<div style="background:#1d2327;padding:18px 20px;color:#fff;font-size:16px;font-weight:700;">'
            . '&#128269; ' . esc_html(get_bloginfo('name')) . ' — ' . esc_html(wzsi_t('digest_title'))
            . '</div>'
            . '<div style="padding:20px;">'
            . '<p style="color:#555;margin:0 0 14px;font-size:13px;">' . esc_html(wzsi_t('digest_hello')) . '<br>' . $period_label . '</p>'
            . $delta_line
            . '<table role="presentation" width="100%" style="border-collapse:collapse;margin:0 0 18px;">'
            . '<tr>'
            . '<td style="background:#f0f6fc;padding:12px;border-radius:8px;text-align:center;">'
            . '<div style="font-size:20px;font-weight:700;color:#2271b1;">' . number_format_i18n($stats['total_searches']) . '</div>'
            . '<div style="color:#666;font-size:12px;">' . esc_html(wzsi_t('stat_total_searches')) . '</div></td>'
            . '<td width="12"></td>'
            . '<td style="background:#fcf0f1;padding:12px;border-radius:8px;text-align:center;">'
            . '<div style="font-size:20px;font-weight:700;color:#d63638;">' . number_format_i18n($stats['zero_results']) . '</div>'
            . '<div style="color:#666;font-size:12px;">' . esc_html(wzsi_t('stat_zero_results')) . '</div></td>'
            . '<td width="12"></td>'
            . '<td style="background:#fcf9e8;padding:12px;border-radius:8px;text-align:center;">'
            . '<div style="font-size:20px;font-weight:700;color:#996800;">' . esc_html($stats['zero_rate']) . '%</div>'
            . '<div style="color:#666;font-size:12px;">' . esc_html(wzsi_t('stat_failure_rate')) . '</div></td>'
            . '</tr></table>'
            . '<h3 style="margin:0 0 10px;font-size:14px;color:#1d2327;">' . esc_html(wzsi_t('digest_top_heading')) . ' (' . esc_html($freq_label) . ')</h3>'
            . '<table width="100%" style="border-collapse:collapse;font-size:13px;">'
            . '<thead><tr style="background:#f6f7f7;">'
            . '<th style="padding:8px 10px;text-align:' . $align . ';">#</th>'
            . '<th style="padding:8px 10px;text-align:' . $align . ';">' . esc_html(wzsi_t('col_term')) . '</th>'
            . '<th style="padding:8px 10px;text-align:center;">' . esc_html(wzsi_t('col_count')) . '</th>'
            . '<th style="padding:8px 10px;text-align:center;">' . esc_html(wzsi_t('col_last_seen')) . '</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>'
            . '<p style="margin:22px 0 0;text-align:center;">'
            . '<a href="' . esc_url($cta) . '" style="display:inline-block;background:#2271b1;color:#fff;text-decoration:none;padding:10px 22px;border-radius:6px;font-weight:600;">' . esc_html(wzsi_t('digest_cta')) . '</a>'
            . '</p>'
            . '</div>'
            . '<div style="background:#f6f7f7;padding:12px 20px;text-align:center;">' . $footer_brand . '</div>'
            . '</div>'
            . '</div></body></html>';
    }
}
