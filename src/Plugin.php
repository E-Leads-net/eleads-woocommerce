<?php

declare(strict_types=1);

namespace Eleads\WooCommerce;

use Eleads\WooCommerce\Admin\Notice;
use Eleads\WooCommerce\Admin\Assets;
use Eleads\WooCommerce\Admin\Menu;
use Eleads\WooCommerce\Admin\Page;
use Eleads\WooCommerce\Admin\PluginLinks;
use Eleads\WooCommerce\Admin\View;
use Eleads\WooCommerce\Admin\FormHandler;
use Eleads\WooCommerce\Admin\FeedActionHandler;
use Eleads\WooCommerce\Api\Auth;
use Eleads\WooCommerce\Api\DashboardTokenValidator;
use Eleads\WooCommerce\Api\PublicEndpoint;
use Eleads\WooCommerce\Api\SeoSitemap;
use Eleads\WooCommerce\Catalog\ProductAttributes;
use Eleads\WooCommerce\Catalog\ProductCategoryTree;
use Eleads\WooCommerce\Dependencies\WooCommerceDependency;
use Eleads\WooCommerce\Feed\CategoryExporter;
use Eleads\WooCommerce\Feed\Endpoint;
use Eleads\WooCommerce\Feed\FeedWriter;
use Eleads\WooCommerce\Feed\Generator;
use Eleads\WooCommerce\Feed\Language;
use Eleads\WooCommerce\Feed\OfferBuilder;
use Eleads\WooCommerce\Feed\PathResolver;
use Eleads\WooCommerce\Feed\ProductQuery;
use Eleads\WooCommerce\Feed\StatusRepository;
use Eleads\WooCommerce\Settings\Sanitizer;
use Eleads\WooCommerce\Settings\SettingsRepository;

final class Plugin
{
    private static ?self $instance = null;

    private WooCommerceDependency $woocommerce_dependency;

    private function __construct()
    {
        $this->woocommerce_dependency = new WooCommerceDependency();
    }

    public static function instance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        if (! $this->woocommerce_dependency->is_active()) {
            add_action('admin_notices', [Notice::class, 'missing_woocommerce']);
            return;
        }

        add_action('init', [$this, 'load_textdomain']);
        $this->register_hooks();
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'eleads-woocommerce',
            false,
            dirname(plugin_basename(ELEADS_WOOCOMMERCE_FILE)) . '/languages'
        );
    }

    private function register_hooks(): void
    {
        $settings = new SettingsRepository();
        $language = new Language();
        $paths = new PathResolver();
        $statuses = new StatusRepository($paths);
        $endpoint = new Endpoint($settings, $language, $paths);
        $generator = new Generator(
            $settings,
            $language,
            new CategoryExporter(),
            new ProductQuery(),
            new OfferBuilder(),
            new FeedWriter($paths),
            $statuses
        );
        $api_endpoint = new PublicEndpoint(
            $settings,
            new Auth($settings),
            $language,
            $generator,
            $statuses,
            $endpoint,
            new SeoSitemap()
        );

        add_action('init', [Endpoint::class, 'register_rewrite_rules']);
        add_action('init', [PublicEndpoint::class, 'register_rewrite_rules']);
        add_filter('query_vars', [$endpoint, 'query_vars']);
        add_filter('query_vars', [$api_endpoint, 'query_vars']);
        add_action('template_redirect', [$endpoint, 'serve']);
        add_action('template_redirect', [$api_endpoint, 'serve']);

        if (is_admin()) {
            $page         = new Page(new View(), $settings, new ProductCategoryTree(), new ProductAttributes(), $language, $statuses, $endpoint);
            $menu         = new Menu($page);
            $assets       = new Assets();
            $plugin_links = new PluginLinks();
            $form_handler = new FormHandler($settings, new Sanitizer(), new DashboardTokenValidator());
            $feed_actions = new FeedActionHandler($generator, $language);

            add_action('admin_init', [$form_handler, 'handle']);
            add_action('admin_notices', [Notice::class, 'plain_permalinks']);
            add_action('admin_post_eleads_generate_feed', [$feed_actions, 'generate']);
            add_action('wp_ajax_eleads_feed_start', [$feed_actions, 'ajax_start']);
            add_action('wp_ajax_eleads_feed_process', [$feed_actions, 'ajax_process']);
            add_action('admin_menu', [$menu, 'register']);
            add_action('admin_enqueue_scripts', [$assets, 'enqueue']);
            add_filter(
                'plugin_action_links_' . plugin_basename(ELEADS_WOOCOMMERCE_FILE),
                [$plugin_links, 'add_settings_link']
            );
        }
    }
}
