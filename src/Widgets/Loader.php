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
    private const TRANSIENT_KEY = 'eleads_woocommerce_widgets_script_url';

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

        $script = $this->script_data();
        if ($script['url'] === '') {
            return;
        }

        wp_enqueue_script(
            'e-leads-for-woocommerce-widgets',
            $script['url'],
            [],
            ELEADS_WOOCOMMERCE_VERSION,
            true
        );

        if ($script['strategy'] !== '') {
            wp_script_add_data('e-leads-for-woocommerce-widgets', 'strategy', $script['strategy']);
        }
    }

    /**
     * @return array{url: string, strategy: string}
     */
    private function script_data(): array
    {
        $cached = get_transient(self::TRANSIENT_KEY);
        if (is_array($cached) && isset($cached['url'], $cached['strategy']) && is_string($cached['url']) && is_string($cached['strategy'])) {
            return $cached;
        }

        $response = wp_remote_get(Routes::widgets_loader_tag_url(), [
            'timeout' => 5,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [
                'url'      => '',
                'strategy' => '',
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $script = $this->extract_script_data($body);
        if ($script['url'] === '') {
            return $script;
        }

        set_transient(self::TRANSIENT_KEY, $script, DAY_IN_SECONDS);

        return $script;
    }

    /**
     * @return array{url: string, strategy: string}
     */
    private function extract_script_data(string $tag): array
    {
        if (! preg_match('/<script\b[^>]*\bsrc=(["\'])(.*?)\1[^>]*>/i', $tag, $matches)) {
            return [
                'url'      => '',
                'strategy' => '',
            ];
        }

        $script_tag = (string) $matches[0];
        $strategy = '';
        if (preg_match('/\bdefer\b/i', $script_tag)) {
            $strategy = 'defer';
        } elseif (preg_match('/\basync\b/i', $script_tag)) {
            $strategy = 'async';
        }

        return [
            'url'      => esc_url_raw((string) $matches[2]),
            'strategy' => $strategy,
        ];
    }
}
