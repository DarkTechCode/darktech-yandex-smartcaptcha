<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

trait DarkTech_YSC_Rendering
{
    public function render_shortcode($atts = []): string
    {
        unset($atts);

        if (! $this->has_client_key()) {
            return $this->render_missing_key_notice();
        }

        $this->enqueue_frontend_assets();

        return $this->build_widget_markup(
            $this->get_default_token_field_name(),
            'elementor'
        );
    }

    public function register_cf7_form_tag(): void
    {
        if (! function_exists('wpcf7_add_form_tag')) {
            return;
        }

        wpcf7_add_form_tag(
            ['darktech_captcha', 'darktech_captcha*'],
            [$this, 'render_cf7_form_tag'],
            [
                'display-block' => true,
                'name-attr' => true,
            ]
        );

        wpcf7_add_form_tag(
            ['darktech_yandexcaptcha', 'darktech_yandexcaptcha*'],
            [$this, 'render_cf7_form_tag'],
            [
                'display-block' => true,
                'name-attr' => true,
            ]
        );

        wpcf7_add_form_tag(
            ['dt_yandexcaptcha', 'dt_yandexcaptcha*'],
            [$this, 'render_cf7_form_tag'],
            [
                'display-block' => true,
                'name-attr' => true,
            ]
        );
    }

    public function render_cf7_form_tag($tag): string
    {
        if (! class_exists('WPCF7_FormTag')) {
            return '';
        }

        if (! $tag instanceof WPCF7_FormTag) {
            $tag = new WPCF7_FormTag($tag);
        }

        $field_name = $tag->name ?: 'darktech-captcha';

        if (! $this->has_client_key()) {
            return $this->render_missing_key_notice();
        }

        $this->enqueue_frontend_assets();

        $validation_error = function_exists('wpcf7_get_validation_error')
            ? wpcf7_get_validation_error($field_name)
            : '';
        $aria_invalid = '' !== $validation_error ? 'true' : 'false';

        $markup = $this->build_widget_markup(
            $field_name,
            'cf7',
            [
                'hidden_input_class' => 'wpcf7-form-control wpcf7-hidden darktech-ysc-hidden-input',
                'hidden_input_attributes' => [
                    'aria-invalid' => $aria_invalid,
                ],
            ]
        );

        return sprintf(
            '<span class="wpcf7-form-control-wrap %1$s" data-name="%2$s">%3$s%4$s</span>',
            esc_attr(sanitize_html_class($field_name)),
            esc_attr($field_name),
            $markup,
            $validation_error
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function build_widget_markup(
        string $field_name,
        string $context,
        array $options = []
    ): string {
        $language = $this->get_widget_language();
        $hidden_input_class = isset($options['hidden_input_class']) && is_string($options['hidden_input_class'])
            ? trim($options['hidden_input_class'])
            : 'darktech-ysc-hidden-input';
        $hidden_input_attributes = isset($options['hidden_input_attributes']) && is_array($options['hidden_input_attributes'])
            ? $options['hidden_input_attributes']
            : [];

        $hidden_input_attributes_string = $this->build_html_attributes(
            array_merge(
                [
                    'type' => 'hidden',
                    'name' => $field_name,
                    'value' => '',
                    'class' => $hidden_input_class,
                    'data-darktech-ysc-hidden-input' => '1',
                ],
                $hidden_input_attributes
            )
        );

        $container_attributes = [
            'class' => 'darktech-ysc-widget',
            'data-darktech-ysc-container' => '1',
            'data-sitekey' => $this->get_client_key(),
            'data-context' => $context,
        ];

        if ('' !== $language) {
            $container_attributes['data-language'] = $language;
        }

        return sprintf(
            '<div class="darktech-ysc-wrapper darktech-ysc-wrapper--%1$s" data-darktech-ysc-wrapper="1"><div %2$s></div><input %3$s /></div>',
            esc_attr(sanitize_html_class($context)),
            $this->build_html_attributes($container_attributes),
            $hidden_input_attributes_string
        );
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function build_html_attributes(array $attributes): string
    {
        $compiled = [];

        foreach ($attributes as $name => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $compiled[] = sprintf(
                '%1$s="%2$s"',
                esc_attr($name),
                esc_attr((string) $value)
            );
        }

        return implode(' ', $compiled);
    }

    private function render_missing_key_notice(): string
    {
        if (! current_user_can('manage_options')) {
            return '';
        }

        return sprintf(
            '<p class="darktech-ysc-missing-config">%s</p>',
            esc_html__(
                'Yandex SmartCaptcha не настроена: сначала сохраните Client key в настройках плагина.',
                'darktech-yandex-smartcaptcha'
            )
        );
    }

    private function register_assets(): void
    {
        if ($this->assets_registered) {
            return;
        }

        wp_register_script(
            self::API_HANDLE,
            'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=onload&onload=darktechYandexSmartCaptchaOnload',
            [],
            null,
            true
        );

        wp_add_inline_script(
            self::API_HANDLE,
            'window.darktechYandexSmartCaptchaLoaded = false; window.darktechYandexSmartCaptchaOnload = function () { window.darktechYandexSmartCaptchaLoaded = true; window.dtYandexSmartCaptchaLoaded = true; if (window.DarkTechYandexSmartCaptcha && typeof window.DarkTechYandexSmartCaptcha.init === "function") { window.DarkTechYandexSmartCaptcha.init(document); } document.dispatchEvent(new Event("darktech-yandex-smartcaptcha-loaded")); document.dispatchEvent(new Event("dt-yandex-smartcaptcha-loaded")); };',
            'before'
        );

        wp_register_script(
            self::FRONTEND_HANDLE,
            plugins_url('ysc-frontend.js', DARKTECH_YSC_PLUGIN_FILE),
            [self::API_HANDLE],
            self::VERSION,
            true
        );

        wp_localize_script(
            self::FRONTEND_HANDLE,
            'DarkTechYandexSmartCaptchaConfig',
            [
                'debug' => $this->is_debug_enabled(),
            ]
        );

        $this->assets_registered = true;
    }

    private function enqueue_frontend_assets(): void
    {
        if (is_admin() || $this->assets_enqueued) {
            return;
        }

        $this->register_assets();

        wp_enqueue_script(self::API_HANDLE);
        wp_enqueue_script(self::FRONTEND_HANDLE);

        $this->assets_enqueued = true;
    }
}
