<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Options_Sanitizer
{
    /**
     * @var DarkTech_YSC_Token_Field_Name_Sanitizer
     */
    private $token_field_name_sanitizer;

    public function __construct(DarkTech_YSC_Token_Field_Name_Sanitizer $token_field_name_sanitizer)
    {
        $this->token_field_name_sanitizer = $token_field_name_sanitizer;
    }

    /**
     * @param mixed $input
     * @return array<string, string>
     */
    public function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];

        return [
            'client_key' => isset($input['client_key'])
                ? sanitize_text_field((string) $input['client_key'])
                : '',
            'server_key' => isset($input['server_key'])
                ? sanitize_text_field((string) $input['server_key'])
                : '',
            'token_field_name' => isset($input['token_field_name'])
                ? $this->token_field_name_sanitizer->sanitize((string) $input['token_field_name'])
                : DarkTech_YSC_Plugin_Config::DEFAULT_TOKEN_FIELD_NAME,
            'debug' => ! empty($input['debug']) ? '1' : '0',
        ];
    }
}

