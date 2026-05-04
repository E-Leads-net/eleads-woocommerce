<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

use Eleads\WooCommerce\Catalog\ProductCategoryTree;
use Eleads\WooCommerce\Catalog\ProductAttributes;
use Eleads\WooCommerce\Settings\SettingsRepository;
use Eleads\WooCommerce\Feed\Endpoint;
use Eleads\WooCommerce\Feed\Language;
use Eleads\WooCommerce\Feed\StatusRepository;

final class Page
{
    private const TAB_EXPORT = 'export';
    private const TAB_API_KEY = 'api-key';

    private View $view;

    private SettingsRepository $settings;

    private ProductCategoryTree $category_tree;

    private ProductAttributes $attributes;

    private Language $language;

    private StatusRepository $feed_statuses;

    private Endpoint $feed_endpoint;

    public function __construct(
        View $view,
        SettingsRepository $settings,
        ProductCategoryTree $category_tree,
        ProductAttributes $attributes,
        Language $language,
        StatusRepository $feed_statuses,
        Endpoint $feed_endpoint
    )
    {
        $this->view          = $view;
        $this->settings      = $settings;
        $this->category_tree = $category_tree;
        $this->attributes    = $attributes;
        $this->language      = $language;
        $this->feed_statuses = $feed_statuses;
        $this->feed_endpoint = $feed_endpoint;
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'eleads-woocommerce'));
        }

        $active_tab = $this->get_active_tab();

        $this->view->render('admin/page', [
            'active_tab' => $active_tab,
            'tabs'       => $this->tabs(),
            'tab_url'    => [$this, 'tab_url'],
            'tab_view'   => $this->tab_view($active_tab),
            'settings'   => $this->settings->all(),
            'categories' => $active_tab === self::TAB_EXPORT ? $this->category_tree->all() : [],
            'attributes' => $active_tab === self::TAB_EXPORT ? $this->attributes->all() : [],
            'feed_rows'  => $active_tab === self::TAB_EXPORT ? $this->feed_rows() : [],
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

    private function get_active_tab(): string
    {
        $settings = $this->settings->all();
        $default_tab = empty($settings['api_key_valid']) ? self::TAB_API_KEY : self::TAB_EXPORT;
        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : $default_tab;

        if (! array_key_exists($tab, $this->tabs())) {
            return $default_tab;
        }

        return $tab;
    }

    /**
     * @return array<string, string>
     */
    private function tabs(): array
    {
        return [
            self::TAB_EXPORT  => __('Налаштування експорту', 'eleads-woocommerce'),
            self::TAB_API_KEY => __('API Key', 'eleads-woocommerce'),
        ];
    }

    private function tab_view(string $tab): string
    {
        return match ($tab) {
            self::TAB_API_KEY => 'admin/tabs/api-key',
            default => 'admin/tabs/export',
        };
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
