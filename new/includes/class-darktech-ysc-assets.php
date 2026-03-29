<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Assets
{
    /**
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    /**
     * @var bool
     */
    private $assets_registered = false;

    /**
     * @var bool
     */
    private $assets_enqueued = false;

    public function __construct(DarkTech_YSC_Options_Repository $options)
    {
        $this->options = $options;
    }

    public function enqueueFrontend(): void
    {
        if (is_admin() || $this->assets_enqueued) {
            return;
        }

        $this->registerScripts();

        wp_enqueue_script(DarkTech_YSC_Plugin_Config::API_HANDLE);
        wp_enqueue_script(DarkTech_YSC_Plugin_Config::FRONTEND_HANDLE);

        $this->assets_enqueued = true;
    }

    private function registerScripts(): void
    {
        if ($this->assets_registered) {
            return;
        }

        wp_register_script(
            DarkTech_YSC_Plugin_Config::API_HANDLE,
            'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=onload&onload=darktechYandexSmartCaptchaOnload',
            [],
            null,
            true
        );

        wp_add_inline_script(
            DarkTech_YSC_Plugin_Config::API_HANDLE,
            'window.darktechYandexSmartCaptchaLoaded = false; window.darktechYandexSmartCaptchaOnload = function () { window.darktechYandexSmartCaptchaLoaded = true; window.dtYandexSmartCaptchaLoaded = true; if (window.DarkTechYandexSmartCaptcha && typeof window.DarkTechYandexSmartCaptcha.init === "function") { window.DarkTechYandexSmartCaptcha.init(document); } document.dispatchEvent(new Event("darktech-yandex-smartcaptcha-loaded")); document.dispatchEvent(new Event("dt-yandex-smartcaptcha-loaded")); };',
            'before'
        );

        wp_register_script(
            DarkTech_YSC_Plugin_Config::FRONTEND_HANDLE,
            plugins_url('ysc-frontend.js', DARKTECH_YSC_PLUGIN_FILE),
            [DarkTech_YSC_Plugin_Config::API_HANDLE],
            DarkTech_YSC_Plugin_Config::VERSION,
            true
        );

        wp_localize_script(
            DarkTech_YSC_Plugin_Config::FRONTEND_HANDLE,
            'DarkTechYandexSmartCaptchaConfig',
            [
                'debug' => $this->options->isDebugEnabled(),
            ]
        );

        $this->assets_registered = true;
    }
}

