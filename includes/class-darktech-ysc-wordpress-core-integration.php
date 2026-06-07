<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_WordPress_Core_Integration
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
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    /**
     * @var DarkTech_YSC_Assets
     */
    private $assets;

    public function __construct(
        DarkTech_YSC_Widget_Renderer $widget_renderer,
        DarkTech_YSC_Token_Validator $token_validator,
        DarkTech_YSC_Options_Repository $options,
        DarkTech_YSC_Assets $assets
    ) {
        $this->widget_renderer = $widget_renderer;
        $this->token_validator = $token_validator;
        $this->options = $options;
        $this->assets = $assets;
    }

    public function enqueueLoginAssets(): void
    {
        if (! $this->hasEnabledLoginPageIntegration()) {
            return;
        }

        $this->assets->enqueueFrontend();
    }

    public function renderLoginCaptcha(): void
    {
        if (! $this->options->isLoginEnabled()) {
            return;
        }

        echo $this->widget_renderer->renderWordPressCoreField(
            DarkTech_YSC_Plugin_Config::DEFAULT_LOGIN_FIELD_NAME,
            'login'
        );
    }

    public function renderRegistrationCaptcha(): void
    {
        if (! $this->options->isRegistrationEnabled()) {
            return;
        }

        echo $this->widget_renderer->renderWordPressCoreField(
            DarkTech_YSC_Plugin_Config::DEFAULT_REGISTRATION_FIELD_NAME,
            'registration'
        );
    }

    public function renderLostPasswordCaptcha(): void
    {
        if (! $this->options->isLostPasswordEnabled()) {
            return;
        }

        echo $this->widget_renderer->renderWordPressCoreField(
            DarkTech_YSC_Plugin_Config::DEFAULT_LOST_PASSWORD_FIELD_NAME,
            'lost-password'
        );
    }

    /**
     * @param mixed $submit_field
     * @return mixed
     */
    public function renderCommentCaptchaBeforeSubmit($submit_field)
    {
        if (! $this->options->isCommentsEnabled()) {
            return $submit_field;
        }

        return $this->widget_renderer->renderWordPressCoreField(
            DarkTech_YSC_Plugin_Config::DEFAULT_COMMENT_FIELD_NAME,
            'comment'
        ) . (string) $submit_field;
    }

    /**
     * @param mixed $user
     * @param mixed $username
     * @param mixed $password
     * @return mixed
     */
    public function validateLogin($user, $username, $password)
    {
        if (! $this->options->isLoginEnabled() || ! $this->isPostRequest()) {
            return $user;
        }

        if ($user instanceof WP_Error || '' === trim((string) $username) || '' === (string) $password) {
            return $user;
        }

        $validation = $this->token_validator->validateSubmissionToken(
            DarkTech_YSC_Plugin_Config::DEFAULT_LOGIN_FIELD_NAME,
            'login'
        );

        if ($validation['is_valid']) {
            return $user;
        }

        return new WP_Error('darktech_ysc_login_failed', $validation['message']);
    }

    /**
     * @param mixed $errors
     * @param mixed $sanitized_user_login
     * @param mixed $user_email
     * @return mixed
     */
    public function validateRegistration($errors, $sanitized_user_login, $user_email)
    {
        unset($sanitized_user_login, $user_email);

        if (! $this->options->isRegistrationEnabled() || ! $this->isPostRequest()) {
            return $errors;
        }

        $errors = $errors instanceof WP_Error ? $errors : new WP_Error();
        $validation = $this->token_validator->validateSubmissionToken(
            DarkTech_YSC_Plugin_Config::DEFAULT_REGISTRATION_FIELD_NAME,
            'registration'
        );

        if (! $validation['is_valid']) {
            $errors->add('darktech_ysc_registration_failed', $validation['message']);
        }

        return $errors;
    }

    /**
     * @param mixed $errors
     * @param mixed $user_data
     * @return mixed
     */
    public function validateLostPassword($errors, $user_data)
    {
        unset($user_data);

        if (! $this->options->isLostPasswordEnabled() || ! $this->isPostRequest()) {
            return $errors;
        }

        $errors = $errors instanceof WP_Error ? $errors : new WP_Error();
        $validation = $this->token_validator->validateSubmissionToken(
            DarkTech_YSC_Plugin_Config::DEFAULT_LOST_PASSWORD_FIELD_NAME,
            'lost-password'
        );

        if (! $validation['is_valid']) {
            $errors->add('darktech_ysc_lost_password_failed', $validation['message']);
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $comment_data
     * @return array<string, mixed>
     */
    public function validateComment(array $comment_data): array
    {
        if (! $this->options->isCommentsEnabled() || is_admin() || ! $this->isStandardComment($comment_data)) {
            return $comment_data;
        }

        $validation = $this->token_validator->validateSubmissionToken(
            DarkTech_YSC_Plugin_Config::DEFAULT_COMMENT_FIELD_NAME,
            'comment'
        );

        if ($validation['is_valid']) {
            return $comment_data;
        }

        wp_die(
            esc_html($validation['message']),
            esc_html__('Comment Submission Failure', DarkTech_YSC_Plugin_Config::TEXT_DOMAIN),
            [
                'response' => 403,
            ]
        );

        return $comment_data;
    }

    private function hasEnabledLoginPageIntegration(): bool
    {
        return $this->options->isLoginEnabled()
            || $this->options->isRegistrationEnabled()
            || $this->options->isLostPasswordEnabled();
    }

    private function isPostRequest(): bool
    {
        $request_method = isset($_SERVER['REQUEST_METHOD'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD']))
            : '';

        return 'POST' === strtoupper($request_method);
    }

    /**
     * @param array<string, mixed> $comment_data
     */
    private function isStandardComment(array $comment_data): bool
    {
        $comment_type = isset($comment_data['comment_type']) ? (string) $comment_data['comment_type'] : '';

        return '' === $comment_type || 'comment' === $comment_type;
    }
}
