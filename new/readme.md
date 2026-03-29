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

## Установка

1. Скачайте плагин.
2. Загрузите папку `darktech-captcha` в `/wp-content/plugins/`.
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

## Отладка

Чтобы включить логирование, добавьте в `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

После этого включите `Debug logging` в настройках плагина.

## Структура

- `darktech-yandex-captcha.php` — bootstrap и подключение модулей
- `includes/class-darktech-yandex-smartcaptcha.php` — основной класс и общие helpers
- `includes/trait-darktech-ysc-admin.php` — настройки и админская страница
- `includes/trait-darktech-ysc-rendering.php` — рендеринг шорткода, CF7 tag и подключение ассетов
- `includes/trait-darktech-ysc-validation.php` — серверная проверка Elementor и Contact Form 7
- `ysc-frontend.js` — фронтенд-инициализация виджета и синхронизация токена

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

