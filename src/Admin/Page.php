<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

use Eleads\WooCommerce\Catalog\ProductCategoryTree;
use Eleads\WooCommerce\Catalog\ProductAttributes;
use Eleads\WooCommerce\Settings\SettingsRepository;
use Eleads\WooCommerce\Feed\Endpoint;
use Eleads\WooCommerce\Feed\Language;
use Eleads\WooCommerce\Feed\StatusRepository;
use Eleads\WooCommerce\Api\SeoSitemap;

final class Page
{
    private const TAB_EXPORT = 'export';
    private const TAB_SEO = 'seo';
    private const TAB_API_KEY = 'api-key';

    private View $view;

    private SettingsRepository $settings;

    private ProductCategoryTree $category_tree;

    private ProductAttributes $attributes;

    private Language $language;

    private StatusRepository $feed_statuses;

    private Endpoint $feed_endpoint;

    private SeoSitemap $seo_sitemap;

    public function __construct(
        View $view,
        SettingsRepository $settings,
        ProductCategoryTree $category_tree,
        ProductAttributes $attributes,
        Language $language,
        StatusRepository $feed_statuses,
        Endpoint $feed_endpoint,
        SeoSitemap $seo_sitemap
    )
    {
        $this->view          = $view;
        $this->settings      = $settings;
        $this->category_tree = $category_tree;
        $this->attributes    = $attributes;
        $this->language      = $language;
        $this->feed_statuses = $feed_statuses;
        $this->feed_endpoint = $feed_endpoint;
        $this->seo_sitemap   = $seo_sitemap;
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'eleads-woocommerce'));
        }

        $settings = $this->settings->all();
        $active_tab = $this->get_active_tab($settings);

        $this->view->render('admin/page', [
            'active_tab' => $active_tab,
            'tabs'       => $this->tabs($settings),
            'tab_url'    => [$this, 'tab_url'],
            'tab_view'   => $this->tab_view($active_tab),
            'settings'   => $settings,
            'categories' => $active_tab === self::TAB_EXPORT ? $this->category_tree->all() : [],
            'attributes' => $active_tab === self::TAB_EXPORT ? $this->attributes->all() : [],
            'image_sizes' => $active_tab === self::TAB_EXPORT ? $this->image_sizes() : [],
            'feed_rows'  => $active_tab === self::TAB_EXPORT ? $this->feed_rows() : [],
            'seo_sitemap_url' => $this->seo_sitemap->url(),
            'saved'      => isset($_GET['eleads_saved']) && $_GET['eleads_saved'] === '1',
            'generated'  => isset($_GET['eleads_generated']) ? sanitize_key((string) wp_unslash($_GET['eleads_generated'])) : '',
        ]);
    }

    public function tab_url(string $tab): string
    {
        return add_query_arg(
            [
                'page' => 'eleads-woocommerce',
                'tab'  => $tab,
            ],
            admin_url('admin.php')
        );
    }

    private function get_active_tab(array $settings): string
    {
        $default_tab = empty($settings['api_key_valid']) ? self::TAB_API_KEY : self::TAB_EXPORT;
        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : $default_tab;

        if (! array_key_exists($tab, $this->tabs($settings))) {
            return $default_tab;
        }

        return $tab;
    }

    /**
     * @return array<string, string>
     */
    private function tabs(?array $settings = null): array
    {
        $settings = $settings ?? $this->settings->all();

        if (empty($settings['api_key_valid'])) {
            return [
                self::TAB_API_KEY => __('API Key', 'eleads-woocommerce'),
            ];
        }

        $tabs = [
            self::TAB_EXPORT  => __('Налаштування експорту', 'eleads-woocommerce'),
        ];

        if (! empty($settings['seo_pages_allowed'])) {
            $tabs[self::TAB_SEO] = __('SEO', 'eleads-woocommerce');
        }

        $tabs[self::TAB_API_KEY] = __('API Key', 'eleads-woocommerce');

        return $tabs;
    }

    private function tab_view(string $tab): string
    {
        return match ($tab) {
            self::TAB_SEO => 'admin/tabs/seo',
            self::TAB_API_KEY => 'admin/tabs/api-key',
            default => 'admin/tabs/export',
        };
    }

    /**
     * @return array<string, string>
     */
    private function image_sizes(): array
    {
        $items = [];
        foreach (wp_get_registered_image_subsizes() as $name => $size) {
            $width = (int) ($size['width'] ?? 0);
            $height = (int) ($size['height'] ?? 0);
            $label = $width > 0 && $height > 0
                ? sprintf('%s (%dx%d)', $name, $width, $height)
                : (string) $name;

            $items[(string) $name] = $label;
        }

        if ($items === []) {
            $items['thumbnail'] = __('Thumbnail', 'eleads-woocommerce');
        }

        $items['full'] = __('Original image', 'eleads-woocommerce');

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function feed_rows(): array
    {
        $rows = [];

        foreach ($this->language->supported() as $language => $label) {
            $status = $this->feed_statuses->get($language);
            $rows[] = [
                'language'     => $language,
                'label'        => $label,
                'status'       => $status,
                'url'          => $this->feed_endpoint->url($language),
                'generate_url' => wp_nonce_url(
                    add_query_arg([
                        'action' => 'eleads_generate_feed',
                        'lang'   => $language,
                    ], admin_url('admin-post.php')),
                    'eleads_generate_feed'
                ),
            ];
        }

        return $rows;
    }
}
