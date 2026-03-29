<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Plugin_Config
{
    public const TEXT_DOMAIN = 'darktech-yandex-smartcaptcha';
    public const OPTION_KEY = 'darktech_ysc_options';
    public const LEGACY_OPTION_KEY = 'dt_ysc_options';
    public const OPTION_GROUP = 'darktech_ysc_group';
    public const SETTINGS_SLUG = 'darktech-ysc-settings';
    public const SHORTCODE = 'darktech-captcha';
    public const LEGACY_SHORTCODE = 'yandex_smartcaptcha';
    public const API_HANDLE = 'darktech-ysc-api';
    public const FRONTEND_HANDLE = 'darktech-ysc-frontend';
    public const VERSION = '1.1.0';
    public const VALIDATE_ENDPOINT = 'https://smartcaptcha.cloud.yandex.ru/validate';
    public const DEFAULT_TOKEN_FIELD_NAME = 'yandex_smart_token';
    public const DEFAULT_CF7_FIELD_NAME = 'darktech-captcha';
}

