<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Elementor_Integration
{
    /**
     * @var DarkTech_YSC_Token_Validator
     */
    private $token_validator;

    /**
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    /**
     * @var DarkTech_YSC_Request_Data
     */
    private $request_data;

    public function __construct(
        DarkTech_YSC_Token_Validator $token_validator,
        DarkTech_YSC_Options_Repository $options,
        DarkTech_YSC_Request_Data $request_data
    ) {
        $this->token_validator = $token_validator;
        $this->options = $options;
        $this->request_data = $request_data;
    }

    /**
     * @param mixed $record
     * @param mixed $ajax_handler
     */
    public function validateForm($record, $ajax_handler): void
    {
        $field_name = $this->options->getDefaultTokenFieldName();

        if (! $this->isCaptchaSubmission($record, $field_name)) {
            return;
        }

        $result = $this->token_validator->validateSubmissionToken($field_name, 'elementor');

        if ($result['is_valid']) {
            return;
        }

        if (is_object($ajax_handler) && method_exists($ajax_handler, 'add_error')) {
            $ajax_handler->add_error(null, $result['message']);
        }
    }

    /**
     * @param mixed $record
     */
    private function isCaptchaSubmission($record, string $field_name): bool
    {
        if ($this->request_data->hasField($field_name) || $this->request_data->hasField('smart-token')) {
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
}

