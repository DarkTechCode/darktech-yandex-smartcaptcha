<?php

declare(strict_types=1);

/**
 * Plugin Name: DarkTech Yandex SmartCaptcha
 * Plugin URI: https://github.com/DarkTechCode/darktech-yandex-smartcaptcha
 * Description: Интеграция Yandex SmartCaptcha для WordPress с поддержкой Elementor и Contact Form 7.
 * Version: 1.1.0
 * Author: Dark Wizard
 * Author URI: https://darktech.ru
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: darktech-yandex-smartcaptcha
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('DARKTECH_YSC_PLUGIN_FILE')) {
    define('DARKTECH_YSC_PLUGIN_FILE', __FILE__);
}

if (! defined('DARKTECH_YSC_PLUGIN_DIR')) {
    define('DARKTECH_YSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-plugin-config.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-token-field-name-sanitizer.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-request-data.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-options-repository.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-logger.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-assets.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-widget-renderer.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-options-sanitizer.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-settings-page.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-settings-registrar.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-shortcode-handler.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-token-validator.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-elementor-integration.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-cf7-integration.php';
require_once DARKTECH_YSC_PLUGIN_DIR . 'includes/class-darktech-ysc-plugin.php';

(new DarkTech_YSC_Plugin())->boot();

