<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Widget_Renderer
{
    /**
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    /**
     * @var DarkTech_YSC_Assets
     */
    private $assets;

    public function __construct(
        DarkTech_YSC_Options_Repository $options,
        DarkTech_YSC_Assets $assets
    ) {
        $this->options = $options;
        $this->assets = $assets;
    }

    public function renderShortcode($atts = []): string
    {
        unset($atts);

        if (! $this->options->hasClientKey()) {
            return $this->renderMissingKeyNotice();
        }

        $this->assets->enqueueFrontend();

        return $this->buildWidgetMarkup($this->options->getDefaultTokenFieldName(), 'elementor');
    }

    public function renderCf7Field(string $field_name, string $validation_error): string
    {
        if (! $this->options->hasClientKey()) {
            return $this->renderMissingKeyNotice();
        }

        $this->assets->enqueueFrontend();

        $aria_invalid = '' !== $validation_error ? 'true' : 'false';
        $markup = $this->buildWidgetMarkup(
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
    private function buildWidgetMarkup(string $field_name, string $context, array $options = []): string
    {
        $language = $this->options->getWidgetLanguage();
        $hidden_input_class = isset($options['hidden_input_class']) && is_string($options['hidden_input_class'])
            ? trim($options['hidden_input_class'])
            : 'darktech-ysc-hidden-input';
        $hidden_input_attributes = isset($options['hidden_input_attributes']) && is_array($options['hidden_input_attributes'])
            ? $options['hidden_input_attributes']
            : [];

        $hidden_input_attributes_string = $this->buildHtmlAttributes(
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
            'data-sitekey' => $this->options->getClientKey(),
            'data-context' => $context,
        ];

        if ('' !== $language) {
            $container_attributes['data-language'] = $language;
        }

        return sprintf(
            '<div class="darktech-ysc-wrapper darktech-ysc-wrapper--%1$s" data-darktech-ysc-wrapper="1"><div %2$s></div><input %3$s /></div>',
            esc_attr(sanitize_html_class($context)),
            $this->buildHtmlAttributes($container_attributes),
            $hidden_input_attributes_string
        );
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function buildHtmlAttributes(array $attributes): string
    {
        $compiled = [];

        foreach ($attributes as $name => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $compiled[] = sprintf('%1$s="%2$s"', esc_attr($name), esc_attr((string) $value));
        }

        return implode(' ', $compiled);
    }

    private function renderMissingKeyNotice(): string
    {
        if (! current_user_can('manage_options')) {
            return '';
        }

        return sprintf(
            '<p class="darktech-ysc-missing-config">%s</p>',
            esc_html__(
                'Yandex SmartCaptcha не настроена: сначала сохраните Client key в настройках плагина.',
                DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
            )
        );
    }
}

