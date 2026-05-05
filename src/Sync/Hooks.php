<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Sync;

if (! defined('ABSPATH')) {
    exit;
}

final class Hooks
{
    private Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function register(): void
    {
        add_action('woocommerce_new_product', [$this, 'product_created'], 20, 1);
        add_action('woocommerce_update_product', [$this, 'product_updated'], 20, 1);
        add_action('woocommerce_update_product_variation', [$this, 'variation_updated'], 20, 1);
        add_action('before_delete_post', [$this, 'product_deleted'], 10, 2);
        add_action('wp_trash_post', [$this, 'product_trashed'], 10, 1);
    }

    public function product_created(int $product_id): void
    {
        if (! $this->should_sync_product($product_id)) {
            return;
        }

        $this->service->sync_created($product_id);
    }

    public function product_updated(int $product_id): void
    {
        if (! $this->should_sync_product($product_id)) {
            return;
        }

        $this->service->sync_updated($product_id);
    }

    public function variation_updated(int $variation_id): void
    {
        $variation = wc_get_product($variation_id);
        if (! $variation instanceof \WC_Product_Variation) {
            return;
        }

        $parent_id = $variation->get_parent_id();
        if ($parent_id <= 0 || ! $this->should_sync_product($parent_id)) {
            return;
        }

        $this->service->sync_updated($parent_id);
    }

    public function product_deleted(int $post_id, \WP_Post $post): void
    {
        if ($post->post_type !== 'product') {
            return;
        }

        if ((string) get_post_meta($post_id, '_eleads_sync_deleted', true) === '1') {
            return;
        }

        $this->service->sync_deleted($post_id);
    }

    public function product_trashed(int $post_id): void
    {
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $this->service->sync_deleted($post_id);
        update_post_meta($post_id, '_eleads_sync_deleted', '1');
    }

    private function should_sync_product(int $product_id): bool
    {
        if ($product_id <= 0 || wp_is_post_revision($product_id) || wp_is_post_autosave($product_id)) {
            return false;
        }

        $product = wc_get_product($product_id);

        return $product instanceof \WC_Product && ! $product instanceof \WC_Product_Variation;
    }
}
