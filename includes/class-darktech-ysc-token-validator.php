<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Token_Validator
{
    /**
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    /**
     * @var DarkTech_YSC_Request_Data
     */
    private $request_data;

    /**
     * @var DarkTech_YSC_Logger
     */
    private $logger;

    public function __construct(
        DarkTech_YSC_Options_Repository $options,
        DarkTech_YSC_Request_Data $request_data,
        DarkTech_YSC_Logger $logger
    ) {
        $this->options = $options;
        $this->request_data = $request_data;
        $this->logger = $logger;
    }

    /**
     * @return array{is_valid: bool, message: string}
     */
    public function validateSubmissionToken(string $field_name, string $context): array
    {
        if (! $this->options->hasServerKey()) {
            $this->logger->log(sprintf('[YSC] %s validation skipped because server key is missing.', $context));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Проверка капчи временно недоступна. Попробуйте позже.',
                    DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
                ),
            ];
        }

        $token = $this->request_data->getFirstPostedValue([$field_name, 'smart-token']);

        if ('' === $token) {
            $this->logger->log(sprintf('[YSC] %s token is missing.', $context));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Пожалуйста, пройдите проверку капчей.',
                    DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
                ),
            ];
        }

        return $this->validateRemoteToken($token, $context);
    }

    /**
     * @return array{is_valid: bool, message: string}
     */
    private function validateRemoteToken(string $token, string $context): array
    {
        $response = wp_remote_post(
            DarkTech_YSC_Plugin_Config::VALIDATE_ENDPOINT,
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'secret' => $this->options->getServerKey(),
                    'token' => $token,
                    'ip' => $this->request_data->getRemoteIp(),
                ],
            ]
        );

        if (is_wp_error($response)) {
            $this->logger->log(sprintf(
                '[YSC] %s remote validation error: %s',
                $context,
                $response->get_error_message()
            ));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Ошибка проверки капчи. Попробуйте ещё раз немного позже.',
                    DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
                ),
            ];
        }

        $response_code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $this->logger->log(sprintf('[YSC] %s validate response (%d): %s', $context, $response_code, $body));

        if ($response_code < 200 || $response_code >= 300) {
            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Сервис капчи временно недоступен. Попробуйте ещё раз позже.',
                    DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
                ),
            ];
        }

        if (! is_array($data) || ! isset($data['status'])) {
            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Не удалось проверить капчу. Попробуйте ещё раз.',
                    DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
                ),
            ];
        }

        if ('ok' !== (string) $data['status']) {
            $message = isset($data['message']) ? (string) $data['message'] : '';
            $this->logger->log(sprintf('[YSC] %s validation failed: %s', $context, $message));

            return [
                'is_valid' => false,
                'message' => esc_html__(
                    'Проверка капчи не пройдена. Обновите проверку и попробуйте снова.',
                    DarkTech_YSC_Plugin_Config::TEXT_DOMAIN
                ),
            ];
        }

        return [
            'is_valid' => true,
            'message' => '',
        ];
    }
}

