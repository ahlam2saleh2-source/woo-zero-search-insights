<?php
/**
 * فئة الإعدادات - بدون أي ميزات بريد
 *
 * @package Woo_Zero_Search_Insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSI_Settings
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
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    public static function get_defaults()
    {
        return array(
            'enabled'             => 1,
            'retention_days'      => 90,
            'min_term_length'     => 2,
            'max_term_length'     => 100,
            'excluded_terms'      => '',
            'track_logged_in'     => 1,
            'track_anonymous'     => 1,
            'ip_anonymize'        => 1,
            'auto_cleanup'        => 1,
            'slack_webhook_url'    => '',
            'slack_enabled'        => 0,
            'discord_webhook_url' => '',
            'discord_enabled'      => 0,
            'dashboard_widget_enabled' => 1,
            'premium_ui_enabled'   => 1,
            'dark_mode'            => 0,
            'white_label'          => 0,
            'theme'                => 'light',
            'ui_lang'              => 'ar',
        );
    }

    public function register_settings()
    {
        register_setting('wzsi_settings_group', 'wzsi_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
            'default'            => self::get_defaults(),
        ));

        // قسم عام
        add_settings_section(
            'wzsi_general_section',
            wzsi_t('section_general'),
            function () {
                echo '<p>' . esc_html(wzsi_t('subtitle_settings')) . '</p>';
            },
            'wzsi_settings_page'
        );

        $fields = array(
            'enabled'         => 'field_enabled',
            'track_logged_in' => 'field_track_logged_in',
            'track_anonymous' => 'field_track_anonymous',
            'ip_anonymize'    => 'field_ip_anonymize',
            'auto_cleanup'   => 'field_auto_cleanup',
        );

        foreach ($fields as $key => $label_key) {
            add_settings_field(
                'wzsi_' . $key,
                wzsi_t($label_key),
                array($this, 'render_checkbox_field'),
                'wzsi_settings_page',
                'wzsi_general_section',
                array('key' => $key)
            );
        }

        add_settings_field('wzsi_retention', wzsi_t('field_retention'), array($this, 'render_number_field'), 'wzsi_settings_page', 'wzsi_general_section', array('key' => 'retention_days', 'min' => 1, 'max' => 3650));
        add_settings_field('wzsi_min_length', wzsi_t('field_min_length'), array($this, 'render_number_field'), 'wzsi_settings_page', 'wzsi_general_section', array('key' => 'min_term_length', 'min' => 1, 'max' => 50));
        add_settings_field('wzsi_max_length', wzsi_t('field_max_length'), array($this, 'render_number_field'), 'wzsi_settings_page', 'wzsi_general_section', array('key' => 'max_term_length', 'min' => 10, 'max' => 255));
        add_settings_field('wzsi_excluded', wzsi_t('field_excluded'), array($this, 'render_textarea_field'), 'wzsi_settings_page', 'wzsi_general_section', array('key' => 'excluded_terms'));

        // قسم Slack/Discord
        add_settings_section(
            'wzsi_webhooks_section',
            wzsi_t('section_ui') . ' - Slack/Discord',
            '__return_false',
            'wzsi_settings_page'
        );

        add_settings_field('wzsi_slack', wzsi_t('section_ui') . ' - Slack', array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'slack_enabled'));
        add_settings_field('wzsi_slack_url', 'Slack Webhook URL', array($this, 'render_url_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'slack_webhook_url'));
        add_settings_field('wzsi_discord', wzsi_t('section_ui') . ' - Discord', array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'discord_enabled'));
        add_settings_field('wzsi_discord_url', 'Discord Webhook URL', array($this, 'render_url_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'discord_webhook_url'));

        // قسم الواجهة
        add_settings_section(
            'wzsi_ui_section',
            wzsi_t('section_ui'),
            '__return_false',
            'wzsi_settings_page'
        );

        add_settings_field('wzsi_theme', wzsi_t('field_theme'), array($this, 'render_theme_selector'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'theme'));
        add_settings_field('wzsi_ui_lang', wzsi_t('field_language'), array($this, 'render_lang_selector'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'ui_lang'));
        add_settings_field('wzsi_dark_mode', wzsi_t('field_dark_mode'), array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'dark_mode'));
        add_settings_field('wzsi_widget', wzsi_t('field_widget'), array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'dashboard_widget_enabled'));
        add_settings_field('wzsi_white_label', wzsi_t('field_white_label'), array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'white_label'));
    }

    public function handle_actions()
    {
        if (!current_user_can(WZSI_CAP)) return;
        if (empty($_POST['wzsi_action'])) return;
        if (!isset($_POST['wzsi_nonce']) || !wp_verify_nonce($_POST['wzsi_nonce'], WZSI_NONCE_ACTION)) {
            wp_die(wzsi_t('notice_invalid_nonce'));
        }

        $action = sanitize_key($_POST['wzsi_action']);
        switch ($action) {
            case 'export_csv':
                $days      = isset($_POST['wzsi_days'])      ? (int) $_POST['wzsi_days']      : 0;
                $zero_only = isset($_POST['wzsi_zero_only']) ? (int) $_POST['wzsi_zero_only'] : 0;
                WZSI_Export::export_csv($days, $zero_only);
                break;
            case 'clear_all':
                WZSI_Database::truncate_logs();
                add_settings_error('wzsi', 'wzsi_cleared', wzsi_t('notice_logs_cleared'), 'updated');
                break;
        }
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();
        $sanitized['enabled']         = !empty($input['enabled']) ? 1 : 0;
        $sanitized['retention_days']  = max(1, min(3650, (int) ($input['retention_days'] ?? 90)));
        $sanitized['min_term_length'] = max(1, min(50,  (int) ($input['min_term_length'] ?? 2)));
        $sanitized['max_term_length'] = max(10, min(255, (int) ($input['max_term_length'] ?? 100)));
        $sanitized['excluded_terms']   = sanitize_textarea_field($input['excluded_terms'] ?? '');
        $sanitized['track_logged_in'] = !empty($input['track_logged_in']) ? 1 : 0;
        $sanitized['track_anonymous'] = !empty($input['track_anonymous']) ? 1 : 0;
        $sanitized['ip_anonymize']    = !empty($input['ip_anonymize']) ? 1 : 0;
        $sanitized['auto_cleanup']    = !empty($input['auto_cleanup']) ? 1 : 0;
        $sanitized['slack_webhook_url']    = esc_url_raw($input['slack_webhook_url'] ?? '');
        $sanitized['slack_enabled']        = !empty($input['slack_enabled']) ? 1 : 0;
        $sanitized['discord_webhook_url'] = esc_url_raw($input['discord_webhook_url'] ?? '');
        $sanitized['discord_enabled']      = !empty($input['discord_enabled']) ? 1 : 0;
        $sanitized['dashboard_widget_enabled'] = !empty($input['dashboard_widget_enabled']) ? 1 : 0;
        $sanitized['premium_ui_enabled']   = !empty($input['premium_ui_enabled']) ? 1 : 0;
        $sanitized['dark_mode']            = !empty($input['dark_mode']) ? 1 : 0;
        $sanitized['white_label']          = !empty($input['white_label']) ? 1 : 0;
        $allowed_themes = array('light', 'dark', 'ocean', 'forest', 'sunset', 'midnight');
        $sanitized['theme']                = in_array($input['theme'] ?? 'light', $allowed_themes, true) ? $input['theme'] : 'light';
        $sanitized['ui_lang']              = in_array($input['ui_lang'] ?? 'ar', array('ar', 'en'), true) ? $input['ui_lang'] : 'ar';
        return $sanitized;
    }

    public function render_checkbox_field($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $value = !empty($options[$args['key']]) ? 1 : 0;
        echo '<label><input type="checkbox" name="wzsi_settings[' . esc_attr($args['key']) . ']" value="1" ' . checked($value, 1, false) . '/> ';
        echo esc_html(wzsi_t('btn_yes_enable'));
        echo '</label>';
    }

    public function render_number_field($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        echo '<input type="number" name="wzsi_settings[' . esc_attr($args['key']) . ']" value="' . esc_attr($value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
    }

    public function render_textarea_field($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        echo '<textarea name="wzsi_settings[' . esc_attr($args['key']) . ']" rows="6" cols="60" class="large-text code">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html(wzsi_t('desc_excluded')) . '</p>';
    }

    public function render_url_field($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        echo '<input type="url" name="wzsi_settings[' . esc_attr($args['key']) . ']" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://..." />';
    }

    public function render_theme_selector($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $current = isset($options['theme']) ? $options['theme'] : 'light';
        if (empty($current)) $current = !empty($options['dark_mode']) ? 'dark' : 'light';
        $themes = WZSI_Premium_UI::get_themes();
        echo '<select name="wzsi_settings[' . esc_attr($args['key']) . ']">';
        foreach ($themes as $key => $data) {
            printf('<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($current, $key, false),
                esc_html(wzsi_t($data['key']))
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html(wzsi_t('desc_theme')) . '</p>';
    }

    public function render_lang_selector($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $current = isset($options['ui_lang']) ? $options['ui_lang'] : 'ar';
        $langs = array('ar' => wzsi_t('lang_arabic'), 'en' => wzsi_t('lang_english'));
        echo '<select name="wzsi_settings[' . esc_attr($args['key']) . ']">';
        foreach ($langs as $code => $name) {
            printf('<option value="%s" %s>%s</option>',
                esc_attr($code),
                selected($current, $code, false),
                esc_html($name)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html(wzsi_t('desc_language')) . '</p>';
    }

    public static function render_settings_page()
    {
        if (!current_user_can(WZSI_CAP)) {
            wp_die(wzsi_t('notice_no_permission'));
        }
        ?>
        <div class="wrap wzsm-wrap">
            <h1>⚙️ <?php echo esc_html(wzsi_t('page_settings')); ?></h1>
            <p class="wzsm-subtitle"><?php echo esc_html(wzsi_t('subtitle_settings')); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('wzsi_settings_group');
                do_settings_sections('wzsi_settings_page');
                submit_button(wzsi_t('btn_save'));
                ?>
            </form>
        </div>
        <?php
    }
}
