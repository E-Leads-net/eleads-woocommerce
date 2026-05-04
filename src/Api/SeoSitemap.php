<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

use Eleads\WooCommerce\Feed\Language;

final class SeoSitemap
{
    private const DIR = 'e-search';
    private const FILE = 'sitemap.xml';

    public function path(): string
    {
        return ABSPATH . self::DIR . '/' . self::FILE;
    }

    public function url(): string
    {
        return home_url('/' . self::DIR . '/' . self::FILE);
    }

    public function create_from_dashboard(string $api_key): void
    {
        $path = $this->path();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        file_put_contents($path, $this->build($this->fetch_slugs($api_key)), LOCK_EX);
    }

    public function remove(): void
    {
        $path = $this->path();
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function add_slug(string $slug, string $language = ''): bool
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            return false;
        }

        $path = $this->path();
        $this->ensure_exists($path);

        if ($this->has_slug($slug, $language)) {
            return true;
        }

        $content = (string) file_get_contents($path);
        if (! str_contains($content, '</urlset>')) {
            $content = $this->skeleton();
        }

        $loc = $this->slug_url($slug, $language);
        $entry = '  <url><loc>' . esc_xml($loc) . '</loc></url>' . PHP_EOL;

        return file_put_contents($path, str_replace('</urlset>', $entry . '</urlset>', $content), LOCK_EX) !== false;
    }

    public function remove_slug(string $slug, string $language = ''): bool
    {
        $slug = $this->sanitize_slug($slug);
        $path = $this->path();
        if ($slug === '' || ! is_file($path)) {
            return false;
        }

        $content = (string) file_get_contents($path);
        if (trim($language) !== '') {
            $loc = $this->slug_url($slug, $language);
            $pattern = '~\s*<url>\s*<loc>' . preg_quote($loc, '~') . '</loc>\s*</url>\s*~i';
        } else {
            $pattern = '~\s*<url>\s*<loc>[^<]*/' . preg_quote(self::DIR, '~') . '/' . preg_quote(rawurlencode($slug), '~') . '</loc>\s*</url>\s*~i';
        }

        $updated = preg_replace($pattern, PHP_EOL, $content);

        return is_string($updated) && file_put_contents($path, $updated, LOCK_EX) !== false;
    }

    public function update_slug(string $old_slug, string $new_slug, string $old_language = '', string $new_language = ''): bool
    {
        $this->remove_slug($old_slug, $old_language);

        return $this->add_slug($new_slug, $new_language !== '' ? $new_language : $old_language);
    }

    public function has_slug(string $slug, string $language = ''): bool
    {
        $slug = $this->sanitize_slug($slug);
        $path = $this->path();
        if ($slug === '' || ! is_file($path)) {
            return false;
        }

        $content = (string) file_get_contents($path);
        if (trim($language) !== '') {
            return str_contains($content, '<loc>' . $this->slug_url($slug, $language) . '</loc>');
        }

        return (bool) preg_match('~<loc>[^<]*/' . preg_quote(self::DIR, '~') . '/' . preg_quote(rawurlencode($slug), '~') . '</loc>~i', $content);
    }

    public function slug_url(string $slug, string $language = ''): string
    {
        $slug = $this->sanitize_slug($slug);
        $language = $this->sanitize_language($language);
        if ($language === $this->default_language()) {
            $language = '';
        }

        $prefix = $language !== '' ? trim($language, '/') . '/' : '';

        return user_trailingslashit(home_url('/' . $prefix . self::DIR . '/' . rawurlencode($slug)));
    }

    private function ensure_exists(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        if (! is_file($path)) {
            file_put_contents($path, $this->skeleton(), LOCK_EX);
        }
    }

    private function skeleton(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL
            . '</urlset>' . PHP_EOL;
    }

    /**
     * @return array<int, array{slug: string, lang: string}>
     */
    private function fetch_slugs(string $api_key): array
    {
        if (trim($api_key) === '') {
            return [];
        }

        $response = wp_remote_get(Routes::seo_slugs_url(), [
            'timeout' => 6,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return [];
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        $items = $data['slugs'] ?? $data['data']['slugs'] ?? null;
        if (! is_array($data) || ! is_array($items) || $items === []) {
            return [];
        }

        $slugs = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $slug = $this->sanitize_slug((string) ($item['slug'] ?? ''));
                $language = $this->sanitize_language((string) ($item['lang'] ?? ''));
            } else {
                $slug = $this->sanitize_slug((string) $item);
                $language = '';
            }

            if ($slug !== '') {
                $slugs[] = ['slug' => $slug, 'lang' => $language];
            }
        }

        return $slugs;
    }

    /**
     * @param array<int, array{slug: string, lang: string}> $slugs
     */
    private function build(array $slugs): string
    {
        if ($slugs === []) {
            return $this->skeleton();
        }

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        $seen = [];

        foreach ($slugs as $item) {
            $loc = $this->slug_url($item['slug'], $item['lang']);
            if (isset($seen[$loc])) {
                continue;
            }

            $seen[$loc] = true;
            $lines[] = '  <url><loc>' . esc_xml($loc) . '</loc></url>';
        }

        $lines[] = '</urlset>';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function sanitize_slug(string $slug): string
    {
        return preg_replace('/[^0-9A-Za-z\-_]/', '', trim($slug)) ?? '';
    }

    private function sanitize_language(string $language): string
    {
        $language = sanitize_key($language);

        return $language === 'ua' ? 'uk' : $language;
    }

    private function default_language(): string
    {
        if (class_exists(Language::class)) {
            return (new Language())->default();
        }

        $locale = get_locale();
        $language = strtolower(substr($locale, 0, 2));

        return $language !== '' ? $this->sanitize_language($language) : 'en';
    }
}
