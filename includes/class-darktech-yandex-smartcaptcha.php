<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_Yandex_SmartCaptcha
{
    use DarkTech_YSC_Admin;
    use DarkTech_YSC_Rendering;
    use DarkTech_YSC_Validation;

    private const OPTION_KEY = 'darktech_ysc_options';
    private const LEGACY_OPTION_KEY = 'dt_ysc_options';
    private const OPTION_GROUP = 'darktech_ysc_group';
    private const SETTINGS_SLUG = 'darktech-ysc-settings';
    private const SHORTCODE = 'darktech-captcha';
    private const LEGACY_SHORTCODE = 'yandex_smartcaptcha';
    private const API_HANDLE = 'darktech-ysc-api';
    private const FRONTEND_HANDLE = 'darktech-ysc-frontend';
    private const VERSION = '1.1.0';
    private const VALIDATE_ENDPOINT = 'https://smartcaptcha.cloud.yandex.ru/validate';

    /**
     * @var array<string, mixed>
     */
    private $options = [];

    /**
     * @var bool
     */
    private $assets_registered = false;

    /**
     * @var bool
     */
    private $assets_enqueued = false;

    public function __construct()
    {
        $options = get_option(self::OPTION_KEY, null);

        if (null === $options) {
            $options = get_option(self::LEGACY_OPTION_KEY, []);
        }

        $this->options = (array) $options;

        add_action('init', [$this, 'register_shortcode']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action(
            'elementor_pro/forms/process',
            [$this, 'validate_elementor_form'],
            10,
            2
        );
        add_action('wpcf7_init', [$this, 'register_cf7_form_tag']);

        add_filter(
            'plugin_action_links_' . plugin_basename(DARKTECH_YSC_PLUGIN_FILE),
            [$this, 'add_settings_link']
        );
        add_filter('wpcf7_validate_darktech_captcha', [$this, 'validate_cf7_tag'], 10, 2);
        add_filter('wpcf7_validate_darktech_captcha*', [$this, 'validate_cf7_tag'], 10, 2);
        add_filter('wpcf7_validate_darktech_yandexcaptcha', [$this, 'validate_cf7_tag'], 10, 2);
        add_filter('wpcf7_validate_darktech_yandexcaptcha*', [$this, 'validate_cf7_tag'], 10, 2);
        add_filter('wpcf7_validate_dt_yandexcaptcha', [$this, 'validate_cf7_tag'], 10, 2);
        add_filter('wpcf7_validate_dt_yandexcaptcha*', [$this, 'validate_cf7_tag'], 10, 2);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'darktech-yandex-smartcaptcha',
            false,
            dirname(plugin_basename(DARKTECH_YSC_PLUGIN_FILE)) . '/languages'
        );
    }

    public function add_settings_link(array $links): array
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=' . self::SETTINGS_SLUG)),
            esc_html__('Settings', 'darktech-yandex-smartcaptcha')
        );

        array_unshift($links, $settings_link);

        return $links;
    }

    public function register_shortcode(): void
    {
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
        add_shortcode(self::LEGACY_SHORTCODE, [$this, 'render_shortcode']);
    }

    private function get_option(string $key, string $default = ''): string
    {
        $value = $this->options[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    private function is_debug_enabled(): bool
    {
        return '1' === $this->get_option('debug', '0');
    }

    private function get_client_key(): string
    {
        $value = trim($this->get_option('client_key'));

        return $this->apply_string_filters(
            'dt_ysc_client_key',
            'darktech_ysc_client_key',
            $value
        );
    }

    private function get_server_key(): string
    {
        $value = trim($this->get_option('server_key'));

        return $this->apply_string_filters(
            'dt_ysc_server_key',
            'darktech_ysc_server_key',
            $value
        );
    }

    private function get_default_token_field_name(): string
    {
        $value = $this->sanitize_token_field_name(
            $this->get_option('token_field_name', 'yandex_smart_token')
        );

        return $this->apply_string_filters(
            'dt_ysc_token_field_name',
            'darktech_ysc_token_field_name',
            $value
        );
    }

    private function sanitize_token_field_name(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);

        return is_string($value) && '' !== $value ? $value : 'yandex_smart_token';
    }

    private function get_widget_language(): string
    {
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $language = strtolower(substr((string) $locale, 0, 2));
        $language = $this->apply_string_filters(
            'dt_ysc_language',
            'darktech_ysc_language',
            $language
        );
        $allowed_languages = ['ru', 'en', 'be', 'kk', 'tt', 'uk', 'uz', 'tr'];

        return in_array($language, $allowed_languages, true) ? $language : '';
    }

    private function has_client_key(): bool
    {
        return '' !== $this->get_client_key();
    }

    private function has_server_key(): bool
    {
        return '' !== $this->get_server_key();
    }

    private function is_field_present_in_request(string $field_name): bool
    {
        return isset($_POST[$field_name]) || isset($_REQUEST[$field_name]);
    }

    private function apply_string_filters(
        string $legacy_hook,
        string $hook,
        string $value
    ): string {
        $value = (string) apply_filters($legacy_hook, $value);

        return (string) apply_filters($hook, $value);
    }

    private function log(string $message): void
    {
        if ($this->is_debug_enabled() && defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
}
