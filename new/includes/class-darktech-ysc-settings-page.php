<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Settings_Page
{
    /**
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    public function __construct(DarkTech_YSC_Options_Repository $options)
    {
        $this->options = $options;
    }

    public function addMenu(): void
    {
        add_options_page(
            esc_html__('Yandex SmartCaptcha', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            esc_html__('Yandex SmartCaptcha', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            'manage_options',
            DarkTech_YSC_Plugin_Config::SETTINGS_SLUG,
            [$this, 'renderPage']
        );
    }

    public function renderSettingsSection(): void
    {
        echo '<p>' . esc_html__(
            'Укажите ключи SmartCaptcha и используйте плагин в Elementor или Contact Form 7.',
            DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
        ) . '</p>';
    }

    public function renderClientKeyField(): void
    {
        printf(
            '<input type="text" name="%1$s[client_key]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(DarkTech_YSC_Plugin_Config::OPTION_KEY),
            esc_attr($this->options->getClientKey())
        );

        echo '<p class="description">' . esc_html__(
            'Публичный ключ виджета из Yandex Cloud.',
            DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
        ) . '</p>';
    }

    public function renderServerKeyField(): void
    {
        printf(
            '<input type="password" name="%1$s[server_key]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(DarkTech_YSC_Plugin_Config::OPTION_KEY),
            esc_attr($this->options->getServerKey())
        );

        echo '<p class="description">' . esc_html__(
            'Секретный ключ для серверной проверки токена.',
            DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
        ) . '</p>';
    }

    public function renderTokenFieldNameField(): void
    {
        printf(
            '<input type="text" name="%1$s[token_field_name]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(DarkTech_YSC_Plugin_Config::OPTION_KEY),
            esc_attr($this->options->getDefaultTokenFieldName())
        );

        echo '<p class="description">' . esc_html__(
            'Используется шорткодом и интеграцией с Elementor. Для Contact Form 7 имя поля задаётся самим form-tag.',
            DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
        ) . '</p>';
    }

    public function renderDebugField(): void
    {
        printf(
            '<label><input type="checkbox" name="%1$s[debug]" value="1" %2$s /> %3$s</label>',
            esc_attr(DarkTech_YSC_Plugin_Config::OPTION_KEY),
            checked($this->options->isDebugEnabled(), true, false),
            esc_html__(
                'Логировать события в wp-content/debug.log (нужны WP_DEBUG и WP_DEBUG_LOG).',
                DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
            )
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html__('DarkTech Yandex SmartCaptcha', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields(DarkTech_YSC_Plugin_Config::OPTION_GROUP);
                do_settings_sections(DarkTech_YSC_Plugin_Config::SETTINGS_SLUG);
                submit_button();
                ?>
            </form>

            <h2><?php echo esc_html__('Использование', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></h2>
            <p><strong><?php echo esc_html__('Elementor:', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></strong> <code>[darktech-captcha]</code></p>
            <p><?php echo esc_html__('Добавьте в Elementor форму HTML поле и вставьте туда шорткод.', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></p>

            <p><strong><?php echo esc_html__('Contact Form 7:', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></strong> <code>[darktech_captcha*]</code></p>
            <p><?php echo esc_html__('Добавьте form-tag в шаблон формы Contact Form 7 в том месте, где должен появиться виджет. Имя поля можно не задавать вручную.', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></p>

            <p><strong><?php echo esc_html__('Важно:', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></strong> <?php echo esc_html__('Если ключи не настроены, виджет не будет рендериться, а серверная проверка не сможет отработать.', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN); ?></p>
        </div>
<?php
    }
}

