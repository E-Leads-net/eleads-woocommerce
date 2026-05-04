<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

final class SeoSitemap
{
    private const DIR = 'e-search';
    private const FILE = 'sitemap.xml';

    public function path(): string
    {
        return ABSPATH . self::DIR . '/' . self::FILE;
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
        $loc = $this->slug_url($slug, $language);
        $pattern = '~\s*<url>\s*<loc>' . preg_quote($loc, '~') . '</loc>\s*</url>\s*~i';
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

        return str_contains((string) file_get_contents($path), '<loc>' . $this->slug_url($slug, $language) . '</loc>');
    }

    public function slug_url(string $slug, string $language = ''): string
    {
        $slug = $this->sanitize_slug($slug);
        $language = sanitize_key($language);
        $prefix = $language !== '' ? trim($language, '/') . '/' : '';

        return home_url('/' . $prefix . self::DIR . '/' . rawurlencode($slug));
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

    private function sanitize_slug(string $slug): string
    {
        return preg_replace('/[^0-9A-Za-z\-_]/', '', trim($slug)) ?? '';
    }
}
