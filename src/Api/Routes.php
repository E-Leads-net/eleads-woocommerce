<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

final class Routes
{
    private const DEFAULT_DASHBOARD_BASE = 'https://dashboard.e-leads.net';
    private const DEFAULT_WIDGETS_BASE = 'https://api.e-leads.net';

    public static function dashboard_url(): string
    {
        return self::dashboard_base() . '/';
    }

    public static function token_status_url(): string
    {
        return self::dashboard_base() . '/api/ecommerce/token/status';
    }

    public static function ecommerce_items_url(): string
    {
        return self::dashboard_base() . '/api/ecommerce/items';
    }

    public static function ecommerce_item_url(string $external_id): string
    {
        return self::ecommerce_items_url() . '/' . rawurlencode($external_id);
    }

    public static function seo_slugs_url(): string
    {
        return self::dashboard_base() . '/api/seo/slugs';
    }

    public static function seo_pages_url(): string
    {
        return self::dashboard_base() . '/api/seo/pages';
    }

    public static function seo_page_url(string $slug, string $language): string
    {
        return add_query_arg('lang', $language, self::seo_pages_url() . '/' . rawurlencode($slug));
    }

    public static function widgets_loader_tag_url(): string
    {
        return self::widgets_base() . '/v1/widgets-loader-tag';
    }

    private static function dashboard_base(): string
    {
        if (defined('ELEADS_WOOCOMMERCE_DASHBOARD_BASE')) {
            return rtrim((string) ELEADS_WOOCOMMERCE_DASHBOARD_BASE, '/');
        }

        return self::DEFAULT_DASHBOARD_BASE;
    }

    private static function widgets_base(): string
    {
        if (defined('ELEADS_WOOCOMMERCE_WIDGETS_BASE')) {
            return rtrim((string) ELEADS_WOOCOMMERCE_WIDGETS_BASE, '/');
        }

        return self::DEFAULT_WIDGETS_BASE;
    }
}
