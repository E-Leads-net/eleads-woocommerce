<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class SettingsRepository
{
    private const OPTION_NAME = 'eleads_woocommerce_settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = get_option(self::OPTION_NAME, []);

        if (! is_array($settings)) {
            $settings = [];
        }

        return array_merge($this->defaults(), $settings);
    }

    public function get(string $key): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? null;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function update(array $settings): void
    {
        update_option(
            self::OPTION_NAME,
            array_merge($this->all(), $settings),
            false
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'api_key'                  => '',
            'api_key_valid'            => false,
            'seo_pages_allowed'        => false,
            'seo_pages_enabled'        => false,
            'sync_enabled'             => false,
            'widgets_enabled'          => false,
            'image_size'               => 'thumbnail',
            'feed_key'                 => '',
            'store_name'               => get_bloginfo('name'),
            'email'                    => get_option('admin_email'),
            'store_url'                => home_url('/'),
            'currency'                 => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'UAH',
            'picture_limit'            => 5,
            'short_description_source' => 'short_description',
            'category_ids'             => [],
            'grouped_products'         => true,
            'attribute_filters_enabled' => false,
            'attribute_filter_slugs'    => [],
            'option_filters_enabled'    => false,
            'option_filter_slugs'       => [],
        ];
    }
}
