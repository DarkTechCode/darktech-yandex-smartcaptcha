<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

trait DarkTech_YSC_Validation
{
    /**
     * @param mixed $record
     * @param mixed $ajax_handler
     */
    public function validate_elementor_form($record, $ajax_handler): void
    {
        $field_name = $this->get_default_token_field_name();

        if (! $this->is_elementor_captcha_submission($record, $field_name)) {
            return;
        }

        $result = $this->validate_submission_token($field_name, 'elementor');

        if ($result['is_valid']) {
            return;
        }

        if (is_object($ajax_handler) && method_exists($ajax_handler, 'add_error')) {
            $ajax_handler->add_error(null, $result['message']);
        }
    }

    /**
     * @param mixed $result
     * @param mixed $tag
     * @return mixed
     */
    public function validate_cf7_tag($result, $tag)
    {
        if (! class_exists('WPCF7_FormTag')) {
            return $result;
        }

        if (! $tag instanceof WPCF7_FormTag) {
            $tag = new WPCF7_FormTag($tag);
        }

        $field_name = $tag->name ?: 'darktech-captcha';

        if (! $this->is_field_present_in_request($field_name)) {
            return $result;
        }

        if (is_object($result) && method_exists($result, 'is_valid') && ! $result->is_valid()) {
            return $result;
        }

        $validation = $this->validate_submission_token($field_name, 'cf7');

        if (! $validation['is_valid'] && is_object($result) && method_exists($result, 'invalidate')) {
            $result->invalidate($tag, $validation['message']);
        }

        return $result;
    }

    /**
     * @param mixed $record
     */
    private function is_elementor_captcha_submission($record, string $field_name): bool
    {
        if (
            $this->is_field_present_in_request($field_name) ||
            $this->is_field_present_in_request('smart-token')
        ) {
            return true;
        }

        if (! is_object($record) || ! method_exists($record, 'get')) {
            return false;
        }

        $raw_fields = $record->get('fields');

        if (! is_array($raw_fields)) {
            return false;
        }

        foreach ($raw_fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $current_name = isset($field['name']) ? (string) $field['name'] : '';
            $current_id = isset($field['id']) ? (string) $field['id'] : '';

            if ($field_name === $current_name || $field_name === $current_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{is_valid: bool, message: string}
     */
    private function validate_submission_token(string $field_name, string $context): array
    {
        if (! $this->has_server_key()) {
            $this->log(sprintf(
                '[YSC] %s validation skipped because server key is missing.',
                $context
            ));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Проверка капчи временно недоступна. Попробуйте позже.',
                    'darktech-yandex-smartcaptcha'
                ),
            ];
        }

        $token = $this->extract_request_token($field_name);

        if ('' === $token) {
            $this->log(sprintf('[YSC] %s token is missing.', $context));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Пожалуйста, пройдите проверку капчей.',
                    'darktech-yandex-smartcaptcha'
                ),
            ];
        }

        return $this->validate_remote_token($token, $context);
    }

    private function extract_request_token(string $field_name): string
    {
        foreach ([$field_name, 'smart-token'] as $candidate_name) {
            if (! isset($_POST[$candidate_name])) {
                continue;
            }

            $value = sanitize_text_field(
                wp_unslash((string) $_POST[$candidate_name])
            );

            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array{is_valid: bool, message: string}
     */
    private function validate_remote_token(string $token, string $context): array
    {
        $response = wp_remote_post(
            self::VALIDATE_ENDPOINT,
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'secret' => $this->get_server_key(),
                    'token' => $token,
                    'ip' => $this->get_request_ip(),
                ],
            ]
        );

        if (is_wp_error($response)) {
            $this->log(sprintf(
                '[YSC] %s remote validation error: %s',
                $context,
                $response->get_error_message()
            ));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Ошибка проверки капчи. Попробуйте ещё раз немного позже.',
                    'darktech-yandex-smartcaptcha'
                ),
            ];
        }

        $response_code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $this->log(sprintf(
            '[YSC] %s validate response (%d): %s',
            $context,
            $response_code,
            $body
        ));

        if ($response_code < 200 || $response_code >= 300) {
            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Сервис капчи временно недоступен. Попробуйте ещё раз позже.',
                    'darktech-yandex-smartcaptcha'
                ),
            ];
        }

        if (! is_array($data) || ! isset($data['status'])) {
            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Не удалось проверить капчу. Попробуйте ещё раз.',
                    'darktech-yandex-smartcaptcha'
                ),
            ];
        }

        if ('ok' !== (string) $data['status']) {
            $message = isset($data['message']) ? (string) $data['message'] : '';
            $this->log(sprintf('[YSC] %s validation failed: %s', $context, $message));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Проверка капчи не пройдена. Обновите проверку и попробуйте снова.',
                    'darktech-yandex-smartcaptcha'
                ),
            ];
        }

        return [
            'is_valid' => true,
            'message' => '',
        ];
    }

    private function get_request_ip(): string
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            return '';
        }

        return sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']));
    }
}
