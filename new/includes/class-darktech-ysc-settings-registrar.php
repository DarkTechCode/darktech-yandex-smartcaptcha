<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Settings_Registrar
{
    /**
     * @var DarkTech_YSC_Options_Sanitizer
     */
    private $sanitizer;

    /**
     * @var DarkTech_YSC_Settings_Page
     */
    private $settings_page;

    public function __construct(
        DarkTech_YSC_Options_Sanitizer $sanitizer,
        DarkTech_YSC_Settings_Page $settings_page
    ) {
        $this->sanitizer = $sanitizer;
        $this->settings_page = $settings_page;
    }

    public function register(): void
    {
        register_setting(
            DarkTech_YSC_Plugin_Config::OPTION_GROUP,
            DarkTech_YSC_Plugin_Config::OPTION_KEY,
            [
                'sanitize_callback' => [$this->sanitizer, 'sanitize'],
                'default' => [],
            ]
        );

        add_settings_section(
            'darktech_ysc_main',
            esc_html__('Yandex SmartCaptcha settings', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            [$this->settings_page, 'renderSettingsSection'],
            DarkTech_YSC_Plugin_Config::SETTINGS_SLUG
        );

        add_settings_field(
            'client_key',
            esc_html__('Client (site) key', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            [$this->settings_page, 'renderClientKeyField'],
            DarkTech_YSC_Plugin_Config::SETTINGS_SLUG,
            'darktech_ysc_main'
        );

        add_settings_field(
            'server_key',
            esc_html__('Server (secret) key', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            [$this->settings_page, 'renderServerKeyField'],
            DarkTech_YSC_Plugin_Config::SETTINGS_SLUG,
            'darktech_ysc_main'
        );

        add_settings_field(
            'token_field_name',
            esc_html__('Shortcode token field name', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            [$this->settings_page, 'renderTokenFieldNameField'],
            DarkTech_YSC_Plugin_Config::SETTINGS_SLUG,
            'darktech_ysc_main'
        );

        add_settings_field(
            'debug',
            esc_html__('Debug logging', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            [$this->settings_page, 'renderDebugField'],
            DarkTech_YSC_Plugin_Config::SETTINGS_SLUG,
            'darktech_ysc_main'
        );
    }
}

