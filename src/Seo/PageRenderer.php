<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Seo;

use Eleads\WooCommerce\Api\SeoSitemap;
use Eleads\WooCommerce\Feed\Language;
use Eleads\WooCommerce\Settings\SettingsRepository;
use WC_Product;

final class PageRenderer
{
    private SettingsRepository $settings;

    private Language $language;

    private SeoSitemap $sitemap;

    private PageApiClient $client;

    /**
     * @var array<string, bool>
     */
    private array $allowed_alternate_urls = [];

    public function __construct(SettingsRepository $settings, Language $language, SeoSitemap $sitemap, PageApiClient $client)
    {
        $this->settings = $settings;
        $this->language = $language;
        $this->sitemap  = $sitemap;
        $this->client   = $client;
    }

    public function serve(string $slug, string $language = ''): void
    {
        $settings = $this->settings->all();
        $slug = $this->sanitize_slug($slug);
        $requested_language = trim($language);
        $language = $this->language->normalize($language);

        if ($requested_language !== '' && $language === $this->language->default()) {
            wp_safe_redirect($this->sitemap->slug_url($slug, $language), 301);
            exit;
        }

        if ($slug === '' || empty($settings['api_key_valid']) || empty($settings['seo_pages_enabled'])) {
            $this->not_found();
        }

        if (! $this->sitemap->has_slug($slug, $language)) {
            $this->not_found();
        }

        $page = $this->client->get((string) $settings['api_key'], $slug, $language);
        if ($page === null) {
            $this->not_found();
        }

        $previous_language = $this->language->switch_to($language);
        $this->register_head($page, $slug, $language);

        $this->mark_as_found();
        status_header(200);
        ob_start([$this, 'filter_alternate_links']);
        get_header();
        $this->render_content($page);
        get_footer();
        $this->language->restore($previous_language);
        exit;
    }

    /**
     * @param array<string, mixed> $page
     */
    private function register_head(array $page, string $slug, string $language): void
    {
        $title = $this->text($page, ['meta_title', 'title', 'h1']);
        $description = $this->text($page, ['meta_description', 'description']);
        $keywords = $this->text($page, ['meta_keywords', 'keywords']);
        $canonical = $this->sitemap->slug_url($slug, $language);
        $alternates = $this->alternates($page['alternate'] ?? [], $language, $canonical);
        $this->allowed_alternate_urls = $this->allowed_url_map(array_values($alternates));

        if ($title !== '') {
            add_filter('pre_get_document_title', static fn (): string => $title);
            add_filter('wpseo_title', static fn (): string => $title);
            add_filter('rank_math/frontend/title', static fn (): string => $title);
            add_filter('aioseo_title', static fn (): string => $title);
        }

        if ($description !== '') {
            add_filter('wpseo_metadesc', static fn (): string => wp_strip_all_tags($description));
            add_filter('rank_math/frontend/description', static fn (): string => wp_strip_all_tags($description));
            add_filter('aioseo_description', static fn (): string => wp_strip_all_tags($description));
        }

        add_filter('wpseo_canonical', static fn (): string => $canonical);
        add_filter('rank_math/frontend/canonical', static fn (): string => $canonical);
        add_filter('aioseo_canonical_url', static fn (): string => $canonical);
        add_filter('wpseo_robots', static fn (): string => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');

        add_action('wp_head', function () use ($description, $keywords, $canonical, $alternates): void {
            if ($description !== '' && ! $this->has_seo_plugin()) {
                echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . PHP_EOL;
            }

            if ($keywords !== '') {
                echo '<meta name="keywords" content="' . esc_attr(wp_strip_all_tags($keywords)) . '">' . PHP_EOL;
            }

            if (! $this->has_seo_plugin()) {
                echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . PHP_EOL;
            }

            foreach ($alternates as $code => $url) {
                echo '<link rel="alternate" hreflang="' . esc_attr($code) . '" href="' . esc_url($url) . '">' . PHP_EOL;
            }
        }, 1);
    }

    /**
     * @param array<string, mixed> $page
     */
    private function render_content(array $page): void
    {
        $h1 = $this->text($page, ['h1', 'title', 'meta_title']);
        $short_description = $this->html($page, ['short_description', 'annotation']);
        $description = $this->html($page, ['description', 'content']);
        $products = $this->products($page['product_ids'] ?? $page['products'] ?? []);

        echo '<main class="eleads-seo-page">';
        echo '<style>.eleads-seo-page{width:100%}.eleads-seo-page__inner{margin:0 auto;max-width:1200px;padding:40px 20px}.eleads-seo-products{margin:32px 0}</style>';
        echo '<div class="eleads-seo-page__inner">';

        if ($h1 !== '') {
            echo '<h1>' . esc_html($h1) . '</h1>';
        }

        if ($short_description !== '') {
            echo '<div class="eleads-seo-page__lead">' . wp_kses_post($short_description) . '</div>';
        }

        if ($products !== []) {
            echo '<div class="eleads-seo-products">';
            $this->render_products_loop($products);
            echo '</div>';
        }

        if ($description !== '') {
            echo '<div class="eleads-seo-page__description">' . wp_kses_post($description) . '</div>';
        }

        echo '</div>';
        echo '</main>';
    }

    /**
     * @param array<int, WC_Product> $products
     */
    private function render_products_loop(array $products): void
    {
        if (! function_exists('woocommerce_product_loop_start') || ! function_exists('wc_get_template_part')) {
            return;
        }

        wc_set_loop_prop('name', 'eleads_seo');
        wc_set_loop_prop('is_paginated', false);
        wc_set_loop_prop('total', count($products));
        wc_set_loop_prop('total_pages', 1);
        wc_set_loop_prop('per_page', count($products));
        wc_set_loop_prop('current_page', 1);
        wc_set_loop_prop('columns', function_exists('wc_get_default_products_per_row') ? wc_get_default_products_per_row() : 4);

        woocommerce_product_loop_start();

        foreach ($products as $product_item) {
            global $post, $product;

            $post = get_post($product_item->get_id());
            if ($post instanceof \WP_Post) {
                setup_postdata($post);
                wc_setup_product_data($post);
            }

            $product = $product_item;
            wc_get_template_part('content', 'product');
        }

        woocommerce_product_loop_end();
        wc_reset_loop();
        wp_reset_postdata();
    }

    /**
     * @return array<int, WC_Product>
     */
    private function products(mixed $value): array
    {
        $ids = $this->product_ids($value);
        if ($ids === [] || ! function_exists('wc_get_products')) {
            return [];
        }

        $products = wc_get_products([
            'include' => $ids,
            'limit'   => -1,
            'status'  => 'publish',
            'orderby' => 'include',
        ]);

        return array_values(array_filter($products, static fn (mixed $product): bool => $product instanceof WC_Product));
    }

    /**
     * @return array<int, int>
     */
    private function product_ids(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $ids[] = absint($item['id'] ?? $item['product_id'] ?? 0);
            } else {
                $ids[] = absint($item);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @param array<string, mixed> $page
     * @param array<int, string> $keys
     */
    private function text(array $page, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($page[$key]) && is_scalar($page[$key])) {
                $value = trim((string) $page[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $page
     * @param array<int, string> $keys
     */
    private function html(array $page, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($page[$key]) && is_scalar($page[$key])) {
                $value = trim((string) $page[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function not_found(): void
    {
        global $wp_query;

        if (is_object($wp_query)) {
            $wp_query->set_404();
        }

        status_header(404);
        nocache_headers();
        $template = get_query_template('404');
        if ($template !== '') {
            include $template;
        } else {
            echo esc_html__('Not found', 'eleads-woocommerce');
        }
        exit;
    }

    private function mark_as_found(): void
    {
        global $post, $wp_query;

        if (! is_object($wp_query)) {
            return;
        }

        $post = null;
        $wp_query->post = null;
        $wp_query->posts = [];
        $wp_query->post_count = 0;
        $wp_query->found_posts = 0;
        $wp_query->is_404 = false;
        $wp_query->is_page = false;
        $wp_query->is_single = false;
        $wp_query->is_singular = false;
        $wp_query->is_archive = false;
        $wp_query->is_home = false;
        $wp_query->is_search = false;
        $wp_query->is_category = false;
        $wp_query->is_tag = false;
        $wp_query->is_author = false;
        $wp_query->is_date = false;
        $wp_query->queried_object = null;
        $wp_query->queried_object_id = 0;
    }

    private function has_seo_plugin(): bool
    {
        return defined('WPSEO_VERSION')
            || defined('RANK_MATH_VERSION')
            || defined('AIOSEO_VERSION')
            || class_exists('AIOSEO\\Plugin\\AIOSEO');
    }

    /**
     * @return array<string, string>
     */
    private function alternates(mixed $value, string $language, string $canonical): array
    {
        $items = [
            $language => $canonical,
        ];

        if (is_array($value)) {
            foreach ($value as $alternate) {
                if (! is_array($alternate)) {
                    continue;
                }

                $code = sanitize_key((string) ($alternate['lang'] ?? ''));
                $url = esc_url_raw((string) ($alternate['url'] ?? ''));
                if ($code === 'ua') {
                    $code = 'uk';
                }

                if ($code !== '' && $url !== '') {
                    $items[$code] = $url;
                }
            }
        }

        return $items;
    }

    public function filter_alternate_links(string $html): string
    {
        $html = preg_replace_callback(
            '/<link\b(?=[^>]*\brel=(["\'])alternate\1)(?=[^>]*\bhreflang=)[^>]*>\s*/i',
            function (array $matches): string {
                $href = $this->link_attribute((string) $matches[0], 'href');

                return $href !== '' && isset($this->allowed_alternate_urls[$href]) ? (string) $matches[0] : '';
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/<li\b(?=[^>]*\btrp-language-switcher-container\b)[\s\S]*?<\/li>/i',
            function (array $matches): string {
                return $this->contains_blocked_seo_url((string) $matches[0]) ? '' : (string) $matches[0];
            },
            $html
        ) ?? $html;

        return preg_replace_callback(
            '/<a\b[^>]*\bhref=(["\'])(.*?)\1[^>]*>[\s\S]*?<\/a>/i',
            function (array $matches): string {
                $href = $this->normalize_url((string) $matches[2]);

                return $this->is_blocked_seo_url($href) ? '' : (string) $matches[0];
            },
            $html
        ) ?? $html;
    }

    /**
     * @param array<int, string> $urls
     * @return array<string, bool>
     */
    private function allowed_url_map(array $urls): array
    {
        $items = [];
        foreach ($urls as $url) {
            $normalized = $this->normalize_url($url);
            if ($normalized !== '') {
                $items[$normalized] = true;
            }
        }

        return $items;
    }

    private function contains_blocked_seo_url(string $html): bool
    {
        if (! preg_match_all('/\bhref=(["\'])(.*?)\1/i', $html, $matches)) {
            return false;
        }

        foreach ($matches[2] as $href) {
            if ($this->is_blocked_seo_url($this->normalize_url((string) $href))) {
                return true;
            }
        }

        return false;
    }

    private function is_blocked_seo_url(string $url): bool
    {
        if ($url === '' || isset($this->allowed_alternate_urls[$url])) {
            return false;
        }

        $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');

        return (bool) preg_match('#^(?:[a-z]{2}/)?e-search/[0-9A-Za-z\-_]+/?$#', $path);
    }

    private function normalize_url(string $url): string
    {
        $url = esc_url_raw(html_entity_decode($url, ENT_QUOTES));
        if ($url === '') {
            return '';
        }

        $parts = wp_parse_url($url);
        if (! is_array($parts)) {
            return untrailingslashit($url);
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) . '://' : '';
        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $port = isset($parts['port']) ? ':' . (string) $parts['port'] : '';
        $path = isset($parts['path']) ? untrailingslashit((string) $parts['path']) : '';
        $query = isset($parts['query']) ? '?' . (string) $parts['query'] : '';

        return $scheme . $host . $port . $path . $query;
    }

    private function link_attribute(string $link, string $attribute): string
    {
        if (! preg_match('/\b' . preg_quote($attribute, '/') . '=(["\'])(.*?)\1/i', $link, $matches)) {
            return '';
        }

        return $this->normalize_url((string) $matches[2]);
    }

    private function sanitize_slug(string $slug): string
    {
        return preg_replace('/[^0-9A-Za-z\-_]/', '', trim($slug)) ?? '';
    }
}
