<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Widgets;

if (! defined('ABSPATH')) {
    exit;
}

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
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        if (is_admin() || ! (bool) $this->settings->get('widgets_enabled')) {
            return;
        }

        wp_enqueue_script(
            'eleads-for-woocommerce-widgets',
            Routes::widgets_script_url(),
            [],
            ELEADS_WOOCOMMERCE_VERSION,
            true
        );
        wp_script_add_data('eleads-for-woocommerce-widgets', 'strategy', 'async');
    }
}
