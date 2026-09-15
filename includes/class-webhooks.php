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

        $term = $data['search_term'];
        $normalized = WZSI_Database::normalize_term($term);

        $cache_key = md5($normalized);
        if (isset($this->recently_notified[$cache_key])) {
            return;
        }
        $this->recently_notified[$cache_key] = true;

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
            'text' => sprintf('🔍 %s: %s', $site_name, wzsi_t('email_subject_new')),
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

        $embed = array(
            'title'       => sprintf('🔍 %s: %s', $site_name, wzsi_t('email_subject_new')),
            'description' => sprintf('%s "**%s**" %s',
                wzsi_t('email_searched_for'),
                $data['search_term'],
                wzsi_t('email_no_results')
            ),
            'url'         => $admin_url,
            'color'       => hexdec('FF7A45'),
            'fields'      => array(
                array(
                    'name'   => wzsi_t('col_date'),
                    'value'  => $data['occurred_at'],
                    'inline' => true,
                ),
            ),
            'footer'      => array(
                'text' => 'Woo Zero Search Insights',
            ),
            'timestamp' => gmdate('c', strtotime($data['occurred_at'])),
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
