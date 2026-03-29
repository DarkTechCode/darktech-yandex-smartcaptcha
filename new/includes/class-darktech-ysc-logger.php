<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Logger
{
    /**
     * @var DarkTech_YSC_Options_Repository
     */
    private $options;

    public function __construct(DarkTech_YSC_Options_Repository $options)
    {
        $this->options = $options;
    }

    public function log(string $message): void
    {
        if ($this->options->isDebugEnabled() && defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
}

