# DarkTech Yandex SmartCaptcha для WordPress

Плагин добавляет Yandex SmartCaptcha в WordPress и умеет работать с:

- Elementor через шорткод
- Contact Form 7 через собственный form-tag

## Что умеет плагин

- рендерит виджет Yandex SmartCaptcha на фронтенде
- выполняет серверную проверку токена через Yandex API
- поддерживает динамические формы Elementor
- поддерживает Contact Form 7
- пишет логи в `wp-content/debug.log` при включённом debug-режиме
- позволяет менять имя скрытого поля для шорткода
- автоматически подставляет язык виджета на основе locale WordPress
- сохраняет совместимость со старыми shortcode, form-tag и option key

## Установка

1. Скачайте плагин.
2. Загрузите папку `darktech-yandex-smartcaptcha` в `/wp-content/plugins/`.
3. Активируйте плагин в WordPress.
4. Перейдите в `Настройки > Yandex SmartCaptcha`.
5. Сохраните `Client key` и `Server key`.

## Настройка ключей

1. Откройте [Yandex Cloud Console](https://console.cloud.yandex.ru/).
2. Создайте или выберите SmartCaptcha-виджет.
3. Скопируйте `Client key` и `Server key`.
4. Вставьте их в настройки плагина.

## Использование в Elementor

1. Откройте форму в Elementor.
2. Добавьте в форму html поле.
3. Вставьте в него шорткод:

```text
[darktech-captcha]
```

Шорткод выведет контейнер SmartCaptcha и скрытое поле, которое плагин использует для серверной проверки.
Старый шорткод `[yandex_smartcaptcha]` тоже продолжает работать для совместимости.
Имя скрытого поля для этой интеграции задаётся в настройке `Shortcode token field name`.

## Использование в Contact Form 7

Добавьте в шаблон формы такой form-tag:

```text
[darktech_captcha*]
```

Пример полной формы:

```text
[text* your-name placeholder "Ваше имя"]
[email* your-email placeholder "Email"]
[textarea your-message placeholder "Сообщение"]
[darktech_captcha*]
[submit "Отправить"]
```

Старые form-tag имена `[darktech_yandexcaptcha* ...]` и `[dt_yandexcaptcha* ...]` тоже оставлены для совместимости.
Если имя поля не указано явно, используется имя по умолчанию `darktech-captcha`.

## Отладка

Чтобы включить логирование, добавьте в `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

После этого включите `Debug logging` в настройках плагина.

## Структура

- `darktech-yandex-captcha.php` — bootstrap плагина, загрузка классов и запуск `DarkTech_YSC_Plugin`
- `includes/class-darktech-ysc-plugin.php` — композиция зависимостей и регистрация WordPress hooks
- `includes/class-darktech-ysc-plugin-config.php` — общие константы: shortcode, option key, endpoint, script handles, defaults
- `includes/class-darktech-ysc-options-repository.php` — чтение настроек плагина и fallback на legacy option key
- `includes/class-darktech-ysc-options-sanitizer.php` — санитизация значений настроек перед сохранением
- `includes/class-darktech-ysc-token-field-name-sanitizer.php` — нормализация имени скрытого поля токена
- `includes/class-darktech-ysc-settings-page.php` — вывод страницы настроек в `Настройки > Yandex SmartCaptcha`
- `includes/class-darktech-ysc-settings-registrar.php` — регистрация settings section и settings fields
- `includes/class-darktech-ysc-assets.php` — регистрация и подключение Yandex API script и `ysc-frontend.js`
- `includes/class-darktech-ysc-widget-renderer.php` — HTML-разметка SmartCaptcha для shortcode и Contact Form 7
- `includes/class-darktech-ysc-shortcode-handler.php` — регистрация shortcode `[darktech-captcha]` и legacy shortcode
- `includes/class-darktech-ysc-elementor-integration.php` — валидация токена в отправках Elementor Pro Forms
- `includes/class-darktech-ysc-cf7-integration.php` — регистрация form-tag'ов Contact Form 7 и серверная валидация
- `includes/class-darktech-ysc-token-validator.php` — запрос к Yandex SmartCaptcha Validate API и разбор ответа
- `includes/class-darktech-ysc-request-data.php` — безопасное чтение `$_POST`, `$_REQUEST` и IP-адреса
- `includes/class-darktech-ysc-logger.php` — логирование в `debug.log`, если включены debug-настройки
- `ysc-frontend.js` — рендер виджета, синхронизация токена, повторная инициализация для динамического DOM, Elementor и CF7

## Совместимость

- WordPress 6.2+
- PHP 7.4+
- Elementor Pro
- Contact Form 7

## Changelog

### 1.1.0

- плагин переработан под общий сценарий, а не только под Elementor
- обновлены адреса Yandex SmartCaptcha API и скрипта виджета
- добавлена поддержка Contact Form 7 через form-tag `[darktech_captcha*]`
- улучшена инициализация виджета для динамических форм
- убран небезопасный принудительный `form.submit()` из JavaScript
- README приведён в соответствие с реальной функциональностью

### 1.0.0

- первый релиз

## Лицензия

GPL-2.0-or-later

