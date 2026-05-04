<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Widgets;

use Eleads\WooCommerce\Api\Routes;
use Eleads\WooCommerce\Settings\SettingsRepository;

final class Loader
{
    private SettingsRepository $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_action('wp_footer', [$this, 'render'], 20);
    }

    public function render(): void
    {
        if (is_admin() || ! (bool) $this->settings->get('widgets_enabled')) {
            return;
        }

        printf(
            '<script src="%s" async></script>' . PHP_EOL,
            esc_url(Routes::widgets_script_url())
        );
    }
}
