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
            'exclude_bots'        => 1,
            'mapping_behavior'    => 'none',
            'slack_webhook_url'    => '',
            'slack_enabled'        => 0,
            'discord_webhook_url' => '',
            'discord_enabled'      => 0,
            'telegram_enabled'    => 0,
            'telegram_bot_token'  => '',
            'telegram_chat_id'    => '',
            'notify_min_occurrences' => 1,
            // F-9 (v1.2.0): قوالب إشعارات الزبائن — فارغ = القالب الافتراضي من WZSI_Notify::default_templates()
            'notify_template_subject_ar' => '',
            'notify_template_body_ar'    => '',
            'notify_template_subject_en' => '',
            'notify_template_body_en'    => '',
            'digest_enabled'      => 0,
            'digest_frequency'    => 'weekly',
            'digest_email'        => '',
            'digest_last_sent'    => '',
            'dashboard_widget_enabled' => 1,
            'premium_ui_enabled'   => 1,
            'dark_mode'            => 0,
            'white_label'          => 0,
            'capture_debug'        => 0,
            'theme'                => 'light',
            'ui_lang'              => function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar',
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
            'exclude_bots'   => 'field_exclude_bots',
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

        // قسم F-4: الربط الذكي
        add_settings_section(
            'wzsi_mapping_section',
            wzsi_t('section_mapping'),
            function () {
                echo '<p>' . esc_html(wzsi_t('desc_mapping_behavior')) . '</p>';
            },
            'wzsi_settings_page'
        );
        add_settings_field('wzsi_mapping_behavior', wzsi_t('field_mapping_behavior'), array($this, 'render_mapping_behavior_field'), 'wzsi_settings_page', 'wzsi_mapping_section', array());

        // قسم Slack/Discord/Telegram + عتبة الإشعارات
        add_settings_section(
            'wzsi_webhooks_section',
            wzsi_t('section_ui') . ' - Slack / Discord / Telegram',
            '__return_false',
            'wzsi_settings_page'
        );

        add_settings_field('wzsi_slack', wzsi_t('section_ui') . ' - Slack', array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'slack_enabled'));
        add_settings_field('wzsi_slack_url', 'Slack Webhook URL', array($this, 'render_url_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'slack_webhook_url'));
        add_settings_field('wzsi_discord', wzsi_t('section_ui') . ' - Discord', array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'discord_enabled'));
        add_settings_field('wzsi_discord_url', 'Discord Webhook URL', array($this, 'render_url_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'discord_webhook_url'));
        add_settings_field('wzsi_telegram', wzsi_t('section_ui') . ' - Telegram', array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'telegram_enabled'));
        add_settings_field('wzsi_telegram_token', wzsi_t('field_telegram_token'), array($this, 'render_text_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'telegram_bot_token'));
        add_settings_field('wzsi_telegram_chat', wzsi_t('field_telegram_chat'), array($this, 'render_text_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'telegram_chat_id'));
        add_settings_field('wzsi_notify_min', wzsi_t('field_notify_min'), array($this, 'render_number_field'), 'wzsi_settings_page', 'wzsi_webhooks_section', array('key' => 'notify_min_occurrences', 'min' => 1, 'max' => 1000));
        add_settings_field('wzsi_test_buttons', wzsi_t('btn_test'), array($this, 'render_test_buttons'), 'wzsi_settings_page', 'wzsi_webhooks_section', array());

        // قسم F-3: التقرير البريدي الدوري
        add_settings_section(
            'wzsi_digest_section',
            wzsi_t('section_digest'),
            function () {
                echo '<p>' . esc_html(wzsi_t('desc_digest')) . '</p>';
            },
            'wzsi_settings_page'
        );
        add_settings_field('wzsi_digest_enabled', wzsi_t('field_digest_enabled'), array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_digest_section', array('key' => 'digest_enabled'));
        add_settings_field('wzsi_digest_freq', wzsi_t('field_digest_frequency'), array($this, 'render_digest_freq_field'), 'wzsi_settings_page', 'wzsi_digest_section', array());
        add_settings_field('wzsi_digest_email', wzsi_t('field_digest_email'), array($this, 'render_email_field'), 'wzsi_settings_page', 'wzsi_digest_section', array('key' => 'digest_email'));
        add_settings_field('wzsi_digest_test', wzsi_t('digest_send_test'), array($this, 'render_digest_test_button'), 'wzsi_settings_page', 'wzsi_digest_section', array());

        // قسم F-9 (v1.2.0): قوالب إشعارات الزبائن (عربي + إنجليزي)
        add_settings_section(
            'wzsi_notify_tpl_section',
            wzsi_t('section_ntf_templates'),
            function () {
                echo '<p>' . esc_html(wzsi_t('desc_ntf_templates')) . '</p>';
            },
            'wzsi_settings_page'
        );
        add_settings_field('wzsi_ntf_tpl_ar', wzsi_t('ntf_tpl_arabic'), array($this, 'render_notify_templates_ar'), 'wzsi_settings_page', 'wzsi_notify_tpl_section', array());
        add_settings_field('wzsi_ntf_tpl_en', wzsi_t('ntf_tpl_english'), array($this, 'render_notify_templates_en'), 'wzsi_settings_page', 'wzsi_notify_tpl_section', array());

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
        // الإصلاح: كان إعداد premium_ui_enabled يُخزَّن ولا يُعرض — الآن قابل للتحكم
        add_settings_field('wzsi_premium_ui', wzsi_t('field_premium_ui'), array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'premium_ui_enabled'));
        // الإصلاح: سجل التشخيص أصبح opt-in من الإعدادات (كان مكتوبًا في DB عند كل بحث)
        add_settings_field('wzsi_capture_debug', wzsi_t('field_capture_debug'), array($this, 'render_checkbox_field'), 'wzsi_settings_page', 'wzsi_ui_section', array('key' => 'capture_debug'));

        // قسم TD-2: نسخ الإعدادات (تصدير/استيراد JSON)
        add_settings_section(
            'wzsi_backup_section',
            wzsi_t('section_backup'),
            function () {
                echo '<p>' . esc_html(wzsi_t('desc_backup')) . '</p>';
            },
            'wzsi_settings_page'
        );
        add_settings_field('wzsi_backup_buttons', wzsi_t('section_backup'), array($this, 'render_backup_buttons'), 'wzsi_settings_page', 'wzsi_backup_section', array());
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

            // F-7: أزرار تجربة القنوات
            case 'test_slack':
            case 'test_discord':
            case 'test_telegram':
                $channel = str_replace('test_', '', $action);
                $result = WZSI_Webhooks::instance()->send_test($channel);
                wp_safe_redirect(add_query_arg(
                    'wzsi_test',
                    $result['ok'] ? 'ok_' . $channel : 'fail_' . $channel,
                    admin_url('admin.php?page=wzsi-settings')
                ));
                exit;

            // F-3: إرسال تقرير تجريبي فوراً
            case 'digest_test':
                $result = WZSI_Email::instance()->send_digest(true);
                wp_safe_redirect(add_query_arg(
                    'wzsi_digest_test',
                    $result['ok'] ? 'ok' : 'fail',
                    admin_url('admin.php?page=wzsi-settings')
                ));
                exit;

            // TD-2: تصدير الإعدادات JSON
            case 'export_settings':
                $settings = get_option('wzsi_settings', array());
                nocache_headers();
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename=wzsi-settings-' . gmdate('Y-m-d') . '.json');
                echo wp_json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;

            // TD-2: استيراد الإعدادات JSON
            case 'import_settings':
                $import_ok = false;
                if (!empty($_FILES['wzsi_settings_file']['tmp_name']) && is_uploaded_file($_FILES['wzsi_settings_file']['tmp_name'])) {
                    $raw = file_get_contents($_FILES['wzsi_settings_file']['tmp_name']);
                    $data = json_decode($raw, true);
                    if (is_array($data)) {
                        // الاستيراد يمر على نفس منظّف الإعدادات حقلًا بحقل — آمن
                        $clean = $this->sanitize_settings($data);
                        update_option('wzsi_settings', $clean);
                        $import_ok = true;
                    }
                }
                wp_safe_redirect(add_query_arg('wzsi_import', $import_ok ? 'ok' : 'bad', admin_url('admin.php?page=wzsi-settings')));
                exit;
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
        $sanitized['exclude_bots']    = !empty($input['exclude_bots']) ? 1 : 0;
        $allowed_behaviors = array('none', 'add_result', 'redirect');
        $sanitized['mapping_behavior'] = in_array($input['mapping_behavior'] ?? 'none', $allowed_behaviors, true) ? $input['mapping_behavior'] : 'none';
        $sanitized['slack_webhook_url']    = esc_url_raw($input['slack_webhook_url'] ?? '');
        $sanitized['slack_enabled']        = !empty($input['slack_enabled']) ? 1 : 0;
        $sanitized['discord_webhook_url'] = esc_url_raw($input['discord_webhook_url'] ?? '');
        $sanitized['discord_enabled']      = !empty($input['discord_enabled']) ? 1 : 0;
        $sanitized['telegram_enabled']    = !empty($input['telegram_enabled']) ? 1 : 0;
        $sanitized['telegram_bot_token']  = sanitize_text_field($input['telegram_bot_token'] ?? '');
        $sanitized['telegram_chat_id']    = sanitize_text_field($input['telegram_chat_id'] ?? '');
        $sanitized['notify_min_occurrences'] = max(1, min(1000, (int) ($input['notify_min_occurrences'] ?? 1)));
        // F-9: قوالب الإشعارات (فارغ يعني: استخدم القالب الافتراضي)
        $sanitized['notify_template_subject_ar'] = sanitize_text_field($input['notify_template_subject_ar'] ?? '');
        $sanitized['notify_template_body_ar']    = sanitize_textarea_field($input['notify_template_body_ar'] ?? '');
        $sanitized['notify_template_subject_en'] = sanitize_text_field($input['notify_template_subject_en'] ?? '');
        $sanitized['notify_template_body_en']    = sanitize_textarea_field($input['notify_template_body_en'] ?? '');
        $sanitized['digest_enabled']      = !empty($input['digest_enabled']) ? 1 : 0;
        $freq_in = isset($input['digest_frequency']) ? $input['digest_frequency'] : 'weekly';
        $sanitized['digest_frequency']    = in_array($freq_in, array('daily', 'weekly'), true) ? $freq_in : 'weekly';
        $sanitized['digest_email']        = sanitize_email($input['digest_email'] ?? '');
        $sanitized['dashboard_widget_enabled'] = !empty($input['dashboard_widget_enabled']) ? 1 : 0;
        $sanitized['premium_ui_enabled']   = !empty($input['premium_ui_enabled']) ? 1 : 0;
        $sanitized['dark_mode']            = !empty($input['dark_mode']) ? 1 : 0;
        $sanitized['white_label']          = !empty($input['white_label']) ? 1 : 0;
        $sanitized['capture_debug']        = !empty($input['capture_debug']) ? 1 : 0;
        $allowed_themes = array('light', 'dark', 'ocean', 'forest', 'sunset', 'midnight');
        $theme_in = isset($input['theme']) ? $input['theme'] : 'light';
        $sanitized['theme']                = in_array($theme_in, $allowed_themes, true) ? $theme_in : 'light';
        $default_lang = function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar';
        $lang_in = isset($input['ui_lang']) ? $input['ui_lang'] : $default_lang;
        $sanitized['ui_lang']              = in_array($lang_in, array('ar', 'en'), true) ? $lang_in : $default_lang;

        // حارس مهم: digest_last_sent ليس حقلاً في النموذج — لا يُمسح عند حفظ الإعدادات
        // وإلا أعاد البريد الإرسال فوراً بعد كل حفظ
        $current = get_option('wzsi_settings', array());
        $sanitized['digest_last_sent'] = isset($input['digest_last_sent'])
            ? sanitize_text_field((string) $input['digest_last_sent'])
            : (string) ($current['digest_last_sent'] ?? '');

        return $sanitized;
    }

    public function render_checkbox_field($args)
    {
        // دمج الافتراضيات: المفاتيح الجديدة (v1.1.0) تظهر بحالتها الصحيحة قبل أول حفظ
        $options = wp_parse_args(get_option('wzsi_settings', array()), self::get_defaults());
        $value = !empty($options[$args['key']]) ? 1 : 0;
        echo '<label><input type="checkbox" name="wzsi_settings[' . esc_attr($args['key']) . ']" value="1" ' . checked($value, 1, false) . '/> ';
        echo esc_html(wzsi_t('btn_yes_enable'));
        echo '</label>';
    }

    public function render_number_field($args)
    {
        $options = wp_parse_args(get_option('wzsi_settings', array()), self::get_defaults());
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

    /**
     * F-7: حقل نص عام (توكن Telegram / معرف الدردشة)
     */
    public function render_text_field($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        echo '<input type="text" name="wzsi_settings[' . esc_attr($args['key']) . ']" value="' . esc_attr($value) . '" class="regular-text" autocomplete="off" />';
    }

    /**
     * F-3: حقل بريد
     */
    public function render_email_field($args)
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        echo '<input type="email" name="wzsi_settings[' . esc_attr($args['key']) . ']" value="' . esc_attr($value) . '" class="regular-text" placeholder="reports@example.com" />';
    }

    /**
     * F-3: اختيار تردد التقرير
     */
    public function render_digest_freq_field()
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $current = $options['digest_frequency'] ?? 'weekly';
        echo '<select name="wzsi_settings[digest_frequency]">';
        printf('<option value="weekly" %s>%s</option>', selected($current, 'weekly', false), esc_html(wzsi_t('digest_weekly')));
        printf('<option value="daily" %s>%s</option>', selected($current, 'daily', false), esc_html(wzsi_t('digest_daily')));
        echo '</select>';
    }

    /**
     * F-7: أزرار تجربة القنوات الثلاث
     */
    public function render_test_buttons()
    {
        $nonce = wp_nonce_field(WZSI_NONCE_ACTION, 'wzsi_nonce', true, false);
        $channels = array('slack' => 'Slack', 'discord' => 'Discord', 'telegram' => 'Telegram');
        echo '<div style="display:flex;gap:8px;flex-wrap:wrap;">';
        foreach ($channels as $channel => $label) {
            printf(
                '<form method="post" style="display:inline;">%s<input type="hidden" name="wzsi_action" value="test_%s" /><button type="submit" class="button button-secondary">%s %s</button></form>',
                $nonce,
                esc_attr($channel),
                '&#9654;',
                esc_html($label)
            );
        }
        echo '</div>';
        echo '<p class="description">' . esc_html(wzsi_t('desc_test_buttons')) . '</p>';
    }

    /**
     * F-3: زر إرسال تقرير تجريبي
     */
    public function render_digest_test_button()
    {
        $nonce = wp_nonce_field(WZSI_NONCE_ACTION, 'wzsi_nonce', true, false);
        printf(
            '<form method="post" style="display:inline;">%s<input type="hidden" name="wzsi_action" value="digest_test" /><button type="submit" class="button button-secondary">%s %s</button></form>',
            $nonce,
            '&#9993;',
            esc_html(wzsi_t('digest_send_test'))
        );
    }

    /**
     * F-9 (v1.2.0): قوالب الإشعارات — عربي ثم إنجليزي
     * فارغ في أي حقل = يُستخدم القالب الافتراضي من WZSI_Notify::default_templates()
     */
    private function render_template_pair($lang)
    {
        $options  = get_option('wzsi_settings', self::get_defaults());
        $defaults = WZSI_Notify::default_templates();

        $subj = $options['notify_template_subject_' . $lang] ?? '';
        $body = $options['notify_template_body_' . $lang] ?? '';

        echo '<div style="max-width:640px;">';
        echo '<p style="margin:0 0 6px;"><strong>' . esc_html(wzsi_t('ntf_tpl_subject')) . '</strong></p>';
        echo '<input type="text" class="large-text" name="wzsi_settings[notify_template_subject_' . esc_attr($lang) . ']" value="' . esc_attr($subj) . '" placeholder="' . esc_attr($defaults[$lang]['subject']) . '">';
        echo '<p style="margin:10px 0 6px;"><strong>' . esc_html(wzsi_t('ntf_tpl_body')) . '</strong></p>';
        echo '<textarea class="large-text" rows="9" name="wzsi_settings[notify_template_body_' . esc_attr($lang) . ']" placeholder="' . esc_attr($defaults[$lang]['body']) . '">' . esc_textarea($body) . '</textarea>';
        if ('' === $subj && '' === $body) {
            echo '<p class="description">' . esc_html(wzsi_t('ntf_tpl_default_hint')) . '</p>';
        }
        echo '</div>';
    }

    public function render_notify_templates_ar()
    {
        $this->render_template_pair('ar');
    }

    public function render_notify_templates_en()
    {
        $this->render_template_pair('en');
    }

    /**
     * F-4: اختيار سلوك الربط في واجهة المتجر
     */
    public function render_mapping_behavior_field()
    {
        $options = get_option('wzsi_settings', self::get_defaults());
        $current = $options['mapping_behavior'] ?? 'none';
        echo '<select name="wzsi_settings[mapping_behavior]">';
        printf('<option value="none" %s>%s</option>', selected($current, 'none', false), esc_html(wzsi_t('map_behavior_none')));
        printf('<option value="add_result" %s>%s</option>', selected($current, 'add_result', false), esc_html(wzsi_t('map_behavior_add_result')));
        printf('<option value="redirect" %s>%s</option>', selected($current, 'redirect', false), esc_html(wzsi_t('map_behavior_redirect')));
        echo '</select>';
        echo '<p class="description">' . esc_html(wzsi_t('desc_mapping_options')) . '</p>';
    }

    /**
     * TD-2: أزرار تصدير/استيراد الإعدادات
     */
    public function render_backup_buttons()
    {
        $nonce = wp_nonce_field(WZSI_NONCE_ACTION, 'wzsi_nonce', true, false);
        echo '<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start;">';

        // تصدير
        printf(
            '<form method="post" style="display:inline;">%s<input type="hidden" name="wzsi_action" value="export_settings" /><button type="submit" class="button button-secondary">%s %s</button></form>',
            $nonce,
            '&#11015;',
            esc_html(wzsi_t('btn_export_settings'))
        );

        // استيراد
        printf(
            '<form method="post" enctype="multipart/form-data" style="display:inline;">%s<input type="hidden" name="wzsi_action" value="import_settings" />',
            $nonce
        );
        printf('<input type="file" name="wzsi_settings_file" accept=".json,application/json" /> ');
        printf('<button type="submit" class="button button-secondary">%s %s</button></form>', '&#11014;', esc_html(wzsi_t('btn_import_settings')));

        echo '</div>';
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
        $current = isset($options['ui_lang']) ? $options['ui_lang'] : (function_exists('wzsi_default_ui_lang') ? wzsi_default_ui_lang() : 'ar');
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

        // تنبيهات نتائج أزرار التجربة والاستيراد (تعود عبر redirect)
        $test = isset($_GET['wzsi_test']) ? sanitize_key(wp_unslash($_GET['wzsi_test'])) : '';
        if ($test && 0 === strpos($test, 'ok_')) {
            echo '<div class="notice notice-success is-dismissible"><p>&#9989; ' . esc_html(wzsi_t('msg_test_ok')) . '</p></div>';
        } elseif ($test && 0 === strpos($test, 'fail_')) {
            echo '<div class="notice notice-error is-dismissible"><p>&#10060; ' . esc_html(wzsi_t('msg_test_fail')) . '</p></div>';
        }

        $digest_test = isset($_GET['wzsi_digest_test']) ? sanitize_key(wp_unslash($_GET['wzsi_digest_test'])) : '';
        if ('ok' === $digest_test) {
            echo '<div class="notice notice-success is-dismissible"><p>&#9989; ' . esc_html(wzsi_t('digest_test_ok')) . '</p></div>';
        } elseif ('fail' === $digest_test) {
            echo '<div class="notice notice-error is-dismissible"><p>&#10060; ' . esc_html(wzsi_t('digest_test_fail')) . '</p></div>';
        }

        $import = isset($_GET['wzsi_import']) ? sanitize_key(wp_unslash($_GET['wzsi_import'])) : '';
        if ('ok' === $import) {
            echo '<div class="notice notice-success is-dismissible"><p>&#9989; ' . esc_html(wzsi_t('msg_import_ok')) . '</p></div>';
        } elseif ('bad' === $import) {
            echo '<div class="notice notice-error is-dismissible"><p>&#10060; ' . esc_html(wzsi_t('msg_import_bad')) . '</p></div>';
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
