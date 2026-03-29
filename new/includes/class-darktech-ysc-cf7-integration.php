<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_CF7_Integration
{
    /**
     * @var DarkTech_YSC_Widget_Renderer
     */
    private $widget_renderer;

    /**
     * @var DarkTech_YSC_Token_Validator
     */
    private $token_validator;

    /**
     * @var DarkTech_YSC_Request_Data
     */
    private $request_data;

    public function __construct(
        DarkTech_YSC_Widget_Renderer $widget_renderer,
        DarkTech_YSC_Token_Validator $token_validator,
        DarkTech_YSC_Request_Data $request_data
    ) {
        $this->widget_renderer = $widget_renderer;
        $this->token_validator = $token_validator;
        $this->request_data = $request_data;
    }

    public function registerFormTag(): void
    {
        if (! function_exists('wpcf7_add_form_tag')) {
            return;
        }

        wpcf7_add_form_tag(
            ['darktech_captcha', 'darktech_captcha*'],
            [$this, 'renderFormTag'],
            [
                'display-block' => true,
                'name-attr' => true,
            ]
        );

        wpcf7_add_form_tag(
            ['darktech_yandexcaptcha', 'darktech_yandexcaptcha*'],
            [$this, 'renderFormTag'],
            [
                'display-block' => true,
                'name-attr' => true,
            ]
        );

        wpcf7_add_form_tag(
            ['dt_yandexcaptcha', 'dt_yandexcaptcha*'],
            [$this, 'renderFormTag'],
            [
                'display-block' => true,
                'name-attr' => true,
            ]
        );
    }

    public function renderFormTag($tag): string
    {
        if (! class_exists('WPCF7_FormTag')) {
            return '';
        }

        if (! $tag instanceof WPCF7_FormTag) {
            $tag = new WPCF7_FormTag($tag);
        }

        $field_name = $tag->name ?: DarkTech_YSC_Plugin_Config::DEFAULT_CF7_FIELD_NAME;
        $validation_error = function_exists('wpcf7_get_validation_error')
            ? wpcf7_get_validation_error($field_name)
            : '';

        return $this->widget_renderer->renderCf7Field($field_name, $validation_error);
    }

    /**
     * @param mixed $result
     * @param mixed $tag
     * @return mixed
     */
    public function validateTag($result, $tag)
    {
        if (! class_exists('WPCF7_FormTag')) {
            return $result;
        }

        if (! $tag instanceof WPCF7_FormTag) {
            $tag = new WPCF7_FormTag($tag);
        }

        $field_name = $tag->name ?: DarkTech_YSC_Plugin_Config::DEFAULT_CF7_FIELD_NAME;

        if (! $this->request_data->hasField($field_name)) {
            return $result;
        }

        if (is_object($result) && method_exists($result, 'is_valid') && ! $result->is_valid()) {
            return $result;
        }

        $validation = $this->token_validator->validateSubmissionToken($field_name, 'cf7');

        if (! $validation['is_valid'] && is_object($result) && method_exists($result, 'invalidate')) {
            $result->invalidate($tag, $validation['message']);
        }

        return $result;
    }
}

