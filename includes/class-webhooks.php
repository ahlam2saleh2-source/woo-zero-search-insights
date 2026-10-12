<?php
/**
 * Webhooks - إشعارات Slack و Discord و Telegram
 *
 * F-7: عتبة إشعارات (لا تنبيه إلا بعد تكرار المصطلح N مرة خلال 24 ساعة)
 * F-7: قناة Telegram الجديدة + زر "إرسال تجريبي" لكل قناة من الإعدادات
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Webhooks
{
    private static $instance = null;
    private $recently_notified = array();

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wzsi_after_log_zero_search', array($this, 'maybe_send_webhooks'), 20, 1);
    }

    public function maybe_send_webhooks($data)
    {
        $settings = get_option('wzsi_settings', array());

        if (empty($data['search_term'])) {
            return;
        }

        $term = $data['search_term'];
        $normalized = WZSI_Database::normalize_term($term);

        // منع التكرار داخل نفس الطلب
        $cache_key = md5($normalized);
        if (isset($this->recently_notified[$cache_key])) {
            return;
        }
        $this->recently_notified[$cache_key] = true;

        // F-7: عتبة الإشعارات — لا تنبيه إلا إذا بلغ المصطلح N مرة خلال آخر 24 ساعة
        // ملاحظة: الفحص قبل ضبط المخنوق حتى يستمر العد، وعند بلوغ العتبة يُرسل إشعار واحد ثم يصمت ساعة
        $min = max(1, (int) ($settings['notify_min_occurrences'] ?? 1));
        if ($min > 1) {
            $occurrences = WZSI_Database::get_term_occurrences_24h($normalized);
            if ($occurrences < $min) {
                return;
            }
        }

        // منع سبام Slack/Discord/Telegram — إشعار واحد لكل مصطلح كل ساعة
        $throttle_key = 'wzsi_wh_' . md5($normalized);
        if (get_transient($throttle_key)) {
            return;
        }
        set_transient($throttle_key, 1, HOUR_IN_SECONDS);

        if (!empty($settings['slack_enabled']) && !empty($settings['slack_webhook_url'])) {
            $this->http_post($settings['slack_webhook_url'], $this->slack_payload($data));
        }

        if (!empty($settings['discord_enabled']) && !empty($settings['discord_webhook_url'])) {
            $this->http_post($settings['discord_webhook_url'], $this->discord_payload($data));
        }

        if (!empty($settings['telegram_enabled']) && !empty($settings['telegram_bot_token']) && !empty($settings['telegram_chat_id'])) {
            $this->send_telegram($data, $settings['telegram_bot_token'], $settings['telegram_chat_id']);
        }
    }

    // ════════════ بناء الحمولات (Payloads) ════════════

    private function slack_payload($data)
    {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=wzsi-dashboard');

        return array(
            'text' => sprintf('🔍 %s: %s', $site_name, wzsi_t('webhook_title')),
            'attachments' => array(
                array(
                    'color'   => '#FF7A45',
                    'fields'  => array(
                        array(
                            'title' => wzsi_t('col_term'),
                            'value' => $data['search_term'],
                            'short' => true,
                        ),
                        array(
                            'title' => wzsi_t('col_date'),
                            'value' => $data['occurred_at'],
                            'short' => true,
                        ),
                    ),
                    'actions' => array(
                        array(
                            'type'  => 'button',
                            'text'  => wzsi_t('btn_view_all_logs'),
                            'url'   => $admin_url,
                        ),
                    ),
                ),
            ),
        );
    }

    private function discord_payload($data)
    {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=wzsi-dashboard');
        $occurred  = !empty($data['occurred_at']) ? $data['occurred_at'] : current_time('mysql');

        $embed = array(
            'title'       => sprintf('🔍 %s: %s', $site_name, wzsi_t('webhook_title')),
            'description' => sprintf('%s **"%s"** %s',
                wzsi_t('webhook_searched_for'),
                $data['search_term'],
                wzsi_t('webhook_no_results')
            ),
            'url'         => $admin_url,
            'color'       => hexdec('FF7A45'),
            'fields'      => array(
                array(
                    'name'   => wzsi_t('col_date'),
                    'value'  => $occurred,
                    'inline' => true,
                ),
            ),
            'footer'      => array(
                'text' => 'Woo Zero Search Insights',
            ),
            'timestamp' => gmdate('c', strtotime($occurred)),
        );

        return array('embeds' => array($embed));
    }

    private function telegram_text($data)
    {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=wzsi-dashboard');
        $occurred  = !empty($data['occurred_at']) ? $data['occurred_at'] : current_time('mysql');

        $lines = array();
        $lines[] = sprintf('🔍 %s: %s', $site_name, wzsi_t('webhook_title'));
        $lines[] = '';
        $lines[] = sprintf('%s <strong>%s</strong> %s',
            wzsi_t('webhook_searched_for'),
            esc_html((string) $data['search_term']),
            wzsi_t('webhook_no_results')
        );
        $lines[] = '';
        $lines[] = sprintf('%s: %s', wzsi_t('col_date'), $occurred);
        $lines[] = $admin_url;

        return implode("\n", $lines);
    }

    private function send_telegram($data, $token, $chat_id)
    {
        // ملاحظة: التوكن يحمل ":" وهو آمن داخل مسار الرابط كما هو
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $token);

        $this->http_post($url, array(
            'chat_id'                   => $chat_id,
            'text'                      => $this->telegram_text($data),
            'parse_mode'                => 'HTML',
            'disable_web_page_preview'  => true,
        ));
    }

    // ════════════ الإرسال ════════════

    private function http_post($url, $payload)
    {
        $args = array(
            'body'        => wp_json_encode($payload),
            'headers'     => array('Content-Type' => 'application/json'),
            'timeout'     => 15,
            'redirection' => 5,
            'blocking'    => false,
        );
        wp_remote_post($url, $args);
    }

    /**
     * إرسال حاجب (لأزرار التجربة فقط — نحتاج الاستجابة لنجاح/فشل)
     */
    private function http_post_blocking($url, $payload)
    {
        return wp_remote_post($url, array(
            'body'        => wp_json_encode($payload),
            'headers'     => array('Content-Type' => 'application/json'),
            'timeout'     => 15,
            'redirection' => 5,
            'blocking'    => true,
        ));
    }

    /**
     * F-7: زر "إرسال تجريبي" — يختبر القناة برسالة تجريبية ويعيد النتيجة
     *
     * @param string $channel slack|discord|telegram
     * @return array ['ok' => bool, 'code' => int, 'msg' => string]
     */
    public function send_test($channel)
    {
        $settings = get_option('wzsi_settings', array());
        $data = array(
            'search_term' => wzsi_t('webhook_test_term'),
            'occurred_at' => current_time('mysql'),
        );

        switch ($channel) {
            case 'slack':
                $url = isset($settings['slack_webhook_url']) ? $settings['slack_webhook_url'] : '';
                if (empty($url)) {
                    return array('ok' => false, 'code' => 0, 'msg' => wzsi_t('msg_test_no_url'));
                }
                $resp = $this->http_post_blocking($url, $this->slack_payload($data));
                break;

            case 'discord':
                $url = isset($settings['discord_webhook_url']) ? $settings['discord_webhook_url'] : '';
                if (empty($url)) {
                    return array('ok' => false, 'code' => 0, 'msg' => wzsi_t('msg_test_no_url'));
                }
                $resp = $this->http_post_blocking($url, $this->discord_payload($data));
                break;

            case 'telegram':
                $token = isset($settings['telegram_bot_token']) ? $settings['telegram_bot_token'] : '';
                $chat  = isset($settings['telegram_chat_id']) ? $settings['telegram_chat_id'] : '';
                if (empty($token) || empty($chat)) {
                    return array('ok' => false, 'code' => 0, 'msg' => wzsi_t('msg_test_no_url'));
                }
                $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $token);
                $resp = $this->http_post_blocking($url, array(
                    'chat_id'    => $chat,
                    'text'       => $this->telegram_text($data),
                    'parse_mode' => 'HTML',
                ));
                break;

            default:
                return array('ok' => false, 'code' => 0, 'msg' => 'unknown channel');
        }

        if (is_wp_error($resp)) {
            return array('ok' => false, 'code' => 0, 'msg' => $resp->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($resp);
        return array(
            'ok'   => ($code >= 200 && $code < 300),
            'code' => $code,
            'msg'  => '',
        );
    }
}
