<?php

declare(strict_types=1);

namespace Eleads\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

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
use Eleads\WooCommerce\Seo\PageApiClient as SeoPageApiClient;
use Eleads\WooCommerce\Seo\PageRenderer as SeoPageRenderer;
use Eleads\WooCommerce\Sync\ApiClient;
use Eleads\WooCommerce\Sync\Hooks as SyncHooks;
use Eleads\WooCommerce\Sync\LanguageResolver as SyncLanguageResolver;
use Eleads\WooCommerce\Sync\PayloadBuilder as SyncPayloadBuilder;
use Eleads\WooCommerce\Sync\Service as SyncService;
use Eleads\WooCommerce\Widgets\Loader as WidgetsLoader;

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

        $this->register_hooks();
    }

    private function register_hooks(): void
    {
        $settings = new SettingsRepository();
        $language = new Language();
        $paths = new PathResolver();
        $statuses = new StatusRepository($paths);
        $endpoint = new Endpoint($settings, $language, $paths);
        $seo_sitemap = new SeoSitemap();
        $seo_renderer = new SeoPageRenderer($settings, $language, $seo_sitemap, new SeoPageApiClient());
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
            new Auth($settings),
            $language,
            $generator,
            $statuses,
            $endpoint,
            $seo_sitemap,
            $seo_renderer
        );
        $sync_language = new SyncLanguageResolver($language);
        $sync_hooks = new SyncHooks(new SyncService(
            $settings,
            new SyncPayloadBuilder($settings, $sync_language),
            new ApiClient(),
            $sync_language
        ));

        add_action('init', [Endpoint::class, 'register_rewrite_rules']);
        add_action('init', [PublicEndpoint::class, 'register_rewrite_rules']);
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 20);
        add_filter('query_vars', [$endpoint, 'query_vars']);
        add_filter('query_vars', [$api_endpoint, 'query_vars']);
        add_action('template_redirect', [$endpoint, 'serve']);
        add_action('template_redirect', [$api_endpoint, 'serve']);
        $sync_hooks->register();
        (new WidgetsLoader($settings))->register();

        if (is_admin()) {
            $page         = new Page(new View(), $settings, new ProductCategoryTree(), new ProductAttributes(), $language, $statuses, $endpoint, $seo_sitemap);
            $menu         = new Menu($page);
            $assets       = new Assets();
            $plugin_links = new PluginLinks();
            $form_handler = new FormHandler($settings, new Sanitizer(), new DashboardTokenValidator(), $seo_sitemap);
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
            add_filter(
                'plugin_row_meta',
                static function (array $links, string $file) use ($plugin_links): array {
                    if ($file !== plugin_basename(ELEADS_WOOCOMMERCE_FILE)) {
                        return $links;
                    }

                    return $plugin_links->add_meta_links($links);
                },
                10,
                2
            );
        }
    }

    public function maybe_flush_rewrite_rules(): void
    {
        $option = 'eleads_woocommerce_rewrite_version';
        if ((string) get_option($option, '') === ELEADS_WOOCOMMERCE_VERSION) {
            return;
        }

        $legacy_sitemap = ABSPATH . 'e-search/sitemap.xml';
        if (is_file($legacy_sitemap)) {
            wp_delete_file($legacy_sitemap);
        }

        flush_rewrite_rules(false);
        update_option($option, ELEADS_WOOCOMMERCE_VERSION, false);
    }
}
