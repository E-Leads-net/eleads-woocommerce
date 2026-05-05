<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

if (! defined('ABSPATH')) {
    exit;
}

use Eleads\WooCommerce\Settings\SettingsRepository;

final class Endpoint
{
    private SettingsRepository $settings;

    private Language $language;

    private PathResolver $paths;

    public function __construct(SettingsRepository $settings, Language $language, PathResolver $paths)
    {
        $this->settings = $settings;
        $this->language = $language;
        $this->paths    = $paths;
    }

    public static function register_rewrite_rules(): void
    {
        add_rewrite_rule('^eleads-yml/([a-z]{2})\.xml$', 'index.php?eleads_yml_lang=$matches[1]', 'top');
    }

    /**
     * @param array<int|string, string> $vars
     * @return array<int|string, string>
     */
    public function query_vars(array $vars): array
    {
        $vars[] = 'eleads_yml_lang';

        return $vars;
    }

    public function serve(): void
    {
        $query_language = (string) get_query_var('eleads_yml_lang');
        if ($query_language !== '') {
            $this->serve_language($query_language);
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw((string) wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
        if (! preg_match('#^/eleads-yml/([a-z]{2})\.xml$#', $path, $matches)) {
            return;
        }

        $this->serve_language((string) $matches[1]);
    }

    private function serve_language(string $requested_language): void
    {
        $language = $this->language->normalize($requested_language);
        $settings = $this->settings->all();
        $access_key = (string) $settings['feed_key'];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public feed access is protected by the configured feed key.
        $request_key = isset($_GET['key']) ? sanitize_text_field((string) wp_unslash($_GET['key'])) : '';
        if ($access_key !== '' && $request_key !== $access_key) {
            status_header(401);
            header('Content-Type: text/plain; charset=utf-8');
            echo esc_html__('Forbidden', 'eleads-for-woocommerce');
            exit;
        }

        $feed_path = $this->paths->final_path($language);
        if (! is_file($feed_path)) {
            status_header(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo esc_html__('Not Found', 'eleads-for-woocommerce');
            exit;
        }

        status_header(200);
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Length: ' . (string) filesize($feed_path));
        readfile($feed_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        exit;
    }

    public function url(string $language): string
    {
        $language = $this->language->normalize($language);
        $settings = $this->settings->all();
        $url = get_option('permalink_structure') !== ''
            ? home_url('/eleads-yml/' . $language . '.xml')
            : add_query_arg('eleads_yml_lang', $language, home_url('/'));

        if ((string) $settings['feed_key'] !== '') {
            $url = add_query_arg('key', (string) $settings['feed_key'], $url);
        }

        return $url;
    }
}
