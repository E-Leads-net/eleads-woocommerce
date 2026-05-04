<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Widgets;

use Eleads\WooCommerce\Api\Routes;
use Eleads\WooCommerce\Settings\SettingsRepository;

final class Loader
{
    private const TRANSIENT_KEY = 'eleads_widgets_loader_src';
    private const CACHE_TTL = 12 * HOUR_IN_SECONDS;

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

        $src = $this->loader_src();
        if ($src === '') {
            return;
        }

        printf(
            '<script src="%s" async></script>' . PHP_EOL,
            esc_url($src)
        );
    }

    private function loader_src(): string
    {
        $cached = get_transient(self::TRANSIENT_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = wp_remote_get(Routes::widgets_loader_tag_url(), [
            'timeout' => 5,
            'headers' => [
                'Accept' => 'text/html, application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return '';
        }

        $src = $this->extract_src((string) wp_remote_retrieve_body($response));
        if ($src === '') {
            return '';
        }

        set_transient(self::TRANSIENT_KEY, $src, self::CACHE_TTL);

        return $src;
    }

    private function extract_src(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        $json = json_decode($body, true);
        if (is_array($json)) {
            foreach (['src', 'url', 'script_url'] as $key) {
                if (! empty($json[$key]) && is_string($json[$key])) {
                    return $this->allowed_src($json[$key]);
                }
            }

            if (! empty($json['tag']) && is_string($json['tag'])) {
                return $this->extract_src($json['tag']);
            }
        }

        if (preg_match('/<script\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/i', $body, $matches)) {
            return $this->allowed_src((string) $matches[1]);
        }

        return '';
    }

    private function allowed_src(string $src): string
    {
        $src = esc_url_raw(trim($src));
        if ($src === '') {
            return '';
        }

        $host = strtolower((string) wp_parse_url($src, PHP_URL_HOST));
        if (! in_array($host, ['api.e-leads.net', 'dashboard.e-leads.net'], true)) {
            return '';
        }

        return $src;
    }
}
