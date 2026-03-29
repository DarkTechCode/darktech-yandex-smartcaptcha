<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

trait DarkTech_YSC_Admin
{
    public function admin_menu(): void
    {
        add_options_page(
            esc_html__('Yandex SmartCaptcha', 'darktech-yandex-smartcaptcha'),
            esc_html__('Yandex SmartCaptcha', 'darktech-yandex-smartcaptcha'),
            'manage_options',
            self::SETTINGS_SLUG,
            [$this, 'settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default' => [],
            ]
        );

        add_settings_section(
            'darktech_ysc_main',
            esc_html__('Yandex SmartCaptcha settings', 'darktech-yandex-smartcaptcha'),
            [$this, 'render_settings_section'],
            self::SETTINGS_SLUG
        );

        add_settings_field(
            'client_key',
            esc_html__('Client (site) key', 'darktech-yandex-smartcaptcha'),
            [$this, 'render_client_key_field'],
            self::SETTINGS_SLUG,
            'darktech_ysc_main'
        );

        add_settings_field(
            'server_key',
            esc_html__('Server (secret) key', 'darktech-yandex-smartcaptcha'),
            [$this, 'render_server_key_field'],
            self::SETTINGS_SLUG,
            'darktech_ysc_main'
        );

        add_settings_field(
            'token_field_name',
            esc_html__('Shortcode token field name', 'darktech-yandex-smartcaptcha'),
            [$this, 'render_token_field_name_field'],
            self::SETTINGS_SLUG,
            'darktech_ysc_main'
        );

        add_settings_field(
            'debug',
            esc_html__('Debug logging', 'darktech-yandex-smartcaptcha'),
            [$this, 'render_debug_field'],
            self::SETTINGS_SLUG,
            'darktech_ysc_main'
        );
    }

    /**
     * @param mixed $input
     * @return array<string, string>
     */
    public function sanitize_options($input): array
    {
        $input = is_array($input) ? $input : [];

        return [
            'client_key' => isset($input['client_key'])
                ? sanitize_text_field((string) $input['client_key'])
                : '',
            'server_key' => isset($input['server_key'])
                ? sanitize_text_field((string) $input['server_key'])
                : '',
            'token_field_name' => isset($input['token_field_name'])
                ? $this->sanitize_token_field_name((string) $input['token_field_name'])
                : 'yandex_smart_token',
            'debug' => ! empty($input['debug']) ? '1' : '0',
        ];
    }

    public function render_settings_section(): void
    {
        echo '<p>' . esc_html__(
            'Укажите ключи SmartCaptcha и используйте плагин в Elementor или Contact Form 7.',
            'darktech-yandex-smartcaptcha'
        ) . '</p>';
    }

    public function render_client_key_field(): void
    {
        printf(
            '<input type="text" name="%1$s[client_key]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($this->get_client_key())
        );

        echo '<p class="description">' . esc_html__(
            'Публичный ключ виджета из Yandex Cloud.',
            'darktech-yandex-smartcaptcha'
        ) . '</p>';
    }

    public function render_server_key_field(): void
    {
        printf(
            '<input type="password" name="%1$s[server_key]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($this->get_server_key())
        );

        echo '<p class="description">' . esc_html__(
            'Секретный ключ для серверной проверки токена.',
            'darktech-yandex-smartcaptcha'
        ) . '</p>';
    }

    public function render_token_field_name_field(): void
    {
        printf(
            '<input type="text" name="%1$s[token_field_name]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($this->get_default_token_field_name())
        );

        echo '<p class="description">' . esc_html__(
            'Используется шорткодом и интеграцией с Elementor. Для Contact Form 7 имя поля задаётся самим form-tag.',
            'darktech-yandex-smartcaptcha'
        ) . '</p>';
    }

    public function render_debug_field(): void
    {
        printf(
            '<label><input type="checkbox" name="%1$s[debug]" value="1" %2$s /> %3$s</label>',
            esc_attr(self::OPTION_KEY),
            checked($this->is_debug_enabled(), true, false),
            esc_html__(
                'Логировать события в wp-content/debug.log (нужны WP_DEBUG и WP_DEBUG_LOG).',
                'darktech-yandex-smartcaptcha'
            )
        );
    }

    public function settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html__('DarkTech Yandex SmartCaptcha', 'darktech-yandex-smartcaptcha'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::SETTINGS_SLUG);
                submit_button();
                ?>
            </form>

            <h2><?php echo esc_html__('Использование', 'darktech-yandex-smartcaptcha'); ?></h2>
            <p><strong><?php echo esc_html__('Elementor:', 'darktech-yandex-smartcaptcha'); ?></strong> <code>[darktech-captcha]</code></p>
            <p><?php echo esc_html__('Добавьте в Elementor форму HTML поле и вставьте туда шорткод.', 'darktech-yandex-smartcaptcha'); ?></p>

            <p><strong><?php echo esc_html__('Contact Form 7:', 'darktech-yandex-smartcaptcha'); ?></strong> <code>[darktech_captcha*]</code></p>
            <p><?php echo esc_html__('Добавьте form-tag в шаблон формы Contact Form 7 в том месте, где должен появиться виджет. Имя поля можно не задавать вручную.', 'darktech-yandex-smartcaptcha'); ?></p>

            <p><strong><?php echo esc_html__('Важно:', 'darktech-yandex-smartcaptcha'); ?></strong> <?php echo esc_html__('Если ключи не настроены, виджет не будет рендериться, а серверная проверка не сможет отработать.', 'darktech-yandex-smartcaptcha'); ?></p>
        </div>
<?php
    }
}
