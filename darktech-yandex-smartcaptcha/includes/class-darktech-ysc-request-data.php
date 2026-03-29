<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Request_Data
{
    public function hasField(string $field_name): bool
    {
        return isset($_POST[$field_name]) || isset($_REQUEST[$field_name]);
    }

    /**
     * @param array<int, string> $field_names
     */
    public function getFirstPostedValue(array $field_names): string
    {
        foreach ($field_names as $field_name) {
            if ('' === $field_name || ! isset($_POST[$field_name])) {
                continue;
            }

            $value = sanitize_text_field(wp_unslash((string) $_POST[$field_name]));

            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }

    public function getRemoteIp(): string
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            return '';
        }

        return sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']));
    }
}

