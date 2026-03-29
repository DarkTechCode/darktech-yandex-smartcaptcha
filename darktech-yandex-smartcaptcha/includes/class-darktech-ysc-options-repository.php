<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Options_Repository
{
    /**
     * @var array<string, mixed>
     */
    private $options = [];

    /**
     * @var DarkTech_YSC_Token_Field_Name_Sanitizer
     */
    private $token_field_name_sanitizer;

    public function __construct(DarkTech_YSC_Token_Field_Name_Sanitizer $token_field_name_sanitizer)
    {
        $this->token_field_name_sanitizer = $token_field_name_sanitizer;
        $this->options = $this->loadOptions();
    }

    public function isDebugEnabled(): bool
    {
        return '1' === $this->getOption('debug', '0');
    }

    public function getClientKey(): string
    {
        $value = trim($this->getOption('client_key'));

        return $this->applyStringFilters('dt_ysc_client_key', 'darktech_ysc_client_key', $value);
    }

    public function getServerKey(): string
    {
        $value = trim($this->getOption('server_key'));

        return $this->applyStringFilters('dt_ysc_server_key', 'darktech_ysc_server_key', $value);
    }

    public function getDefaultTokenFieldName(): string
    {
        $value = $this->token_field_name_sanitizer->sanitize(
            $this->getOption('token_field_name', DarkTech_YSC_Plugin_Config::DEFAULT_TOKEN_FIELD_NAME)
        );

        return $this->applyStringFilters('dt_ysc_token_field_name', 'darktech_ysc_token_field_name', $value);
    }

    public function getWidgetLanguage(): string
    {
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $language = strtolower(substr((string) $locale, 0, 2));
        $language = $this->applyStringFilters('dt_ysc_language', 'darktech_ysc_language', $language);
        $allowed_languages = ['ru', 'en', 'be', 'kk', 'tt', 'uk', 'uz', 'tr'];

        return in_array($language, $allowed_languages, true) ? $language : '';
    }

    public function hasClientKey(): bool
    {
        return '' !== $this->getClientKey();
    }

    public function hasServerKey(): bool
    {
        return '' !== $this->getServerKey();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOptions(): array
    {
        $options = get_option(DarkTech_YSC_Plugin_Config::OPTION_KEY, null);

        if (null === $options) {
            $options = get_option(DarkTech_YSC_Plugin_Config::LEGACY_OPTION_KEY, []);
        }

        return (array) $options;
    }

    private function getOption(string $key, string $default = ''): string
    {
        $value = $this->options[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    private function applyStringFilters(string $legacy_hook, string $hook, string $value): string
    {
        $value = (string) apply_filters($legacy_hook, $value);

        return (string) apply_filters($hook, $value);
    }
}

