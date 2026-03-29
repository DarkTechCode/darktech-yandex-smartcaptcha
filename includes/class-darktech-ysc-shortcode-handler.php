<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class DarkTech_YSC_Shortcode_Handler
{
    /**
     * @var DarkTech_YSC_Widget_Renderer
     */
    private $widget_renderer;

    public function __construct(DarkTech_YSC_Widget_Renderer $widget_renderer)
    {
        $this->widget_renderer = $widget_renderer;
    }

    public function register(): void
    {
        add_shortcode(DarkTech_YSC_Plugin_Config::SHORTCODE, [$this, 'render']);
        add_shortcode(DarkTech_YSC_Plugin_Config::LEGACY_SHORTCODE, [$this, 'render']);
    }

    public function render($atts = []): string
    {
        return $this->widget_renderer->renderShortcode($atts);
    }
}

