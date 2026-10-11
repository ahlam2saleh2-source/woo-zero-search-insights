<?php
/**
 * Webhooks - إشعارات Slack و Discord
 * منفصلة عن البريد تماماً
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

        // الإصلاح: منع سبام Slack/Discord — كان كل بحث جديد لنفس المصطلح
        // يرسل إشعارًا من جديد. الآن إشعار واحد لكل مصطلح كل ساعة.
        $throttle_key = 'wzsi_wh_' . md5($normalized);
        if (get_transient($throttle_key)) {
            return;
        }
        set_transient($throttle_key, 1, HOUR_IN_SECONDS);

        if (!empty($settings['slack_enabled']) && !empty($settings['slack_webhook_url'])) {
            $this->send_slack($data, $settings['slack_webhook_url']);
        }

        if (!empty($settings['discord_enabled']) && !empty($settings['discord_webhook_url'])) {
            $this->send_discord($data, $settings['discord_webhook_url']);
        }
    }

    private function send_slack($data, $webhook_url)
    {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=wzsi-dashboard');

        $payload = array(
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

        $this->http_post($webhook_url, $payload);
    }

    private function send_discord($data, $webhook_url)
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

        $payload = array('embeds' => array($embed));
        $this->http_post($webhook_url, $payload);
    }

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
}
