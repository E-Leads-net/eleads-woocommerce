<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

use Eleads\WooCommerce\Feed\Language;

final class SeoSitemap
{
    private const DIR = 'e-search';
    private const FILE = 'sitemap.xml';
    private const OPTION = 'eleads_woocommerce_seo_sitemap_slugs';

    public function url(): string
    {
        return home_url('/' . self::DIR . '/' . self::FILE);
    }

    public function create_from_dashboard(string $api_key): void
    {
        $this->save($this->fetch_slugs($api_key));
    }

    public function remove(): void
    {
        delete_option(self::OPTION);
    }

    public function add_slug(string $slug, string $language = ''): bool
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            return false;
        }

        $items = $this->slugs();
        $items[] = [
            'slug' => $slug,
            'lang' => $this->sanitize_language($language),
        ];

        $this->save($items);

        return true;
    }

    public function remove_slug(string $slug, string $language = ''): bool
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            return false;
        }

        $language = $this->sanitize_language($language);
        $items = array_values(array_filter(
            $this->slugs(),
            static function (array $item) use ($slug, $language): bool {
                if ((string) $item['slug'] !== $slug) {
                    return true;
                }

                return $language !== '' && (string) $item['lang'] !== $language;
            }
        ));

        $this->save($items);

        return true;
    }

    public function update_slug(string $old_slug, string $new_slug, string $old_language = '', string $new_language = ''): bool
    {
        $this->remove_slug($old_slug, $old_language);

        return $this->add_slug($new_slug, $new_language !== '' ? $new_language : $old_language);
    }

    public function has_slug(string $slug, string $language = ''): bool
    {
        $slug = $this->sanitize_slug($slug);
        if ($slug === '') {
            return false;
        }

        $language = $this->sanitize_language($language);
        foreach ($this->slugs() as $item) {
            if ((string) $item['slug'] === $slug && (string) $item['lang'] === $language) {
                return true;
            }
        }

        return false;
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

    public function render(): void
    {
        status_header(200);
        header('Content-Type: application/xml; charset=utf-8');
        echo $this->build($this->slugs()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * @return array<int, array{slug: string, lang: string}>
     */
    private function slugs(): array
    {
        $items = get_option(self::OPTION, []);
        if (! is_array($items)) {
            return [];
        }

        $slugs = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $slug = $this->sanitize_slug((string) ($item['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $slugs[] = [
                'slug' => $slug,
                'lang' => $this->sanitize_language((string) ($item['lang'] ?? '')),
            ];
        }

        return $this->unique($slugs);
    }

    /**
     * @param array<int, array{slug: string, lang: string}> $slugs
     */
    private function save(array $slugs): void
    {
        update_option(self::OPTION, $this->unique($slugs), false);
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

        return $this->unique($slugs);
    }

    /**
     * @param array<int, array{slug: string, lang: string}> $slugs
     */
    private function build(array $slugs): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->unique($slugs) as $item) {
            $lines[] = '  <url><loc>' . esc_xml($this->slug_url($item['slug'], $item['lang'])) . '</loc></url>';
        }

        $lines[] = '</urlset>';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<int, array{slug: string, lang: string}> $slugs
     * @return array<int, array{slug: string, lang: string}>
     */
    private function unique(array $slugs): array
    {
        $items = [];
        $seen = [];

        foreach ($slugs as $item) {
            $slug = $this->sanitize_slug((string) ($item['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $language = $this->sanitize_language((string) ($item['lang'] ?? ''));
            $key = $language . ':' . $slug;
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $items[] = ['slug' => $slug, 'lang' => $language];
        }

        return $items;
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
        $locale = get_locale();
        $language = strtolower(substr($locale, 0, 2));

        return $language !== '' ? $this->sanitize_language($language) : (new Language())->default();
    }
}
