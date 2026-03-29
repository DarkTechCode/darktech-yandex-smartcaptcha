<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Plugin
{
    /**
     * @var DarkTech_YSC_Settings_Page
     */
    private $settings_page;

    /**
     * @var DarkTech_YSC_Settings_Registrar
     */
    private $settings_registrar;

    /**
     * @var DarkTech_YSC_Shortcode_Handler
     */
    private $shortcode_handler;

    /**
     * @var DarkTech_YSC_Elementor_Integration
     */
    private $elementor_integration;

    /**
     * @var DarkTech_YSC_CF7_Integration
     */
    private $cf7_integration;

    public function __construct()
    {
        $token_field_name_sanitizer = new DarkTech_YSC_Token_Field_Name_Sanitizer();
        $request_data = new DarkTech_YSC_Request_Data();
        $options = new DarkTech_YSC_Options_Repository($token_field_name_sanitizer);
        $logger = new DarkTech_YSC_Logger($options);
        $assets = new DarkTech_YSC_Assets($options);
        $widget_renderer = new DarkTech_YSC_Widget_Renderer($options, $assets);
        $options_sanitizer = new DarkTech_YSC_Options_Sanitizer($token_field_name_sanitizer);
        $token_validator = new DarkTech_YSC_Token_Validator($options, $request_data, $logger);

        $this->settings_page = new DarkTech_YSC_Settings_Page($options);
        $this->settings_registrar = new DarkTech_YSC_Settings_Registrar(
            $options_sanitizer,
            $this->settings_page
        );
        $this->shortcode_handler = new DarkTech_YSC_Shortcode_Handler($widget_renderer);
        $this->elementor_integration = new DarkTech_YSC_Elementor_Integration(
            $token_validator,
            $options,
            $request_data
        );
        $this->cf7_integration = new DarkTech_YSC_CF7_Integration(
            $widget_renderer,
            $token_validator,
            $request_data
        );
    }

    public function boot(): void
    {
        add_action('init', [$this->shortcode_handler, 'register']);
        add_action('admin_menu', [$this->settings_page, 'addMenu']);
        add_action('admin_init', [$this->settings_registrar, 'register']);
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
        add_action('elementor_pro/forms/process', [$this->elementor_integration, 'validateForm'], 10, 2);
        add_action('wpcf7_init', [$this->cf7_integration, 'registerFormTag']);

        add_filter(
            'plugin_action_links_' . plugin_basename(DARKTECH_YSC_PLUGIN_FILE),
            [$this, 'addSettingsLink']
        );
        add_filter('wpcf7_validate_darktech_captcha', [$this->cf7_integration, 'validateTag'], 10, 2);
        add_filter('wpcf7_validate_darktech_captcha*', [$this->cf7_integration, 'validateTag'], 10, 2);
        add_filter('wpcf7_validate_darktech_yandexcaptcha', [$this->cf7_integration, 'validateTag'], 10, 2);
        add_filter('wpcf7_validate_darktech_yandexcaptcha*', [$this->cf7_integration, 'validateTag'], 10, 2);
        add_filter('wpcf7_validate_dt_yandexcaptcha', [$this->cf7_integration, 'validateTag'], 10, 2);
        add_filter('wpcf7_validate_dt_yandexcaptcha*', [$this->cf7_integration, 'validateTag'], 10, 2);
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            DarkTech_YSC_Plugin_Config::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(DARKTECH_YSC_PLUGIN_FILE)) . '/languages'
        );
    }

    public function addSettingsLink(array $links): array
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=' . DarkTech_YSC_Plugin_Config::SETTINGS_SLUG)),
            esc_html__('Settings', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN)
        );

        array_unshift($links, $settings_link);

        return $links;
    }
}

