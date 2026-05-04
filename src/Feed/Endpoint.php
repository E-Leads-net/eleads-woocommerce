<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

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

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
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

        if ($access_key !== '' && (string) ($_GET['key'] ?? '') !== $access_key) {
            status_header(401);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $feed_path = $this->paths->final_path($language);
        if (! is_file($feed_path)) {
            status_header(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not Found';
            exit;
        }

        status_header(200);
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Length: ' . (string) filesize($feed_path));
        readfile($feed_path);
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
