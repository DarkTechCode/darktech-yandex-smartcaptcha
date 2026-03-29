<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Token_Field_Name_Sanitizer
{
    public function sanitize(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);

        return is_string($value) && '' !== $value
            ? $value
            : DarkTech_YSC_Plugin_Config::DEFAULT_TOKEN_FIELD_NAME;
    }
}

