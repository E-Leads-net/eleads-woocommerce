<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Sync;

use Eleads\WooCommerce\Settings\SettingsRepository;

final class PayloadBuilder
{
    private SettingsRepository $settings;

    private LanguageResolver $language_resolver;

    public function __construct(SettingsRepository $settings, LanguageResolver $language_resolver)
    {
        $this->settings          = $settings;
        $this->language_resolver = $language_resolver;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(int $product_id): ?array
    {
        $product = wc_get_product($product_id);
        if (! $product instanceof \WC_Product || $product instanceof \WC_Product_Variation) {
            return null;
        }

        if ($product->get_status() !== 'publish') {
            return null;
        }

        $settings = $this->settings->all();
        $language = $this->language_resolver->resolve_for_product($product_id);
        $price_source = $this->price_source($product);
        if (! $price_source instanceof \WC_Product) {
            return null;
        }

        $category = $this->category_payload($product);
        $stock = $this->stock_payload($product);

        return [
            'language'    => $language,
            'external_id' => (string) $product->get_id(),
            'payload'     => [
                'source'   => [
                    'offer_id' => (string) $product->get_id(),
                    'language' => $language,
                    'url'      => get_permalink($product->get_id()) ?: '',
                    'group_id' => (string) $product->get_id(),
                ],
                'product'  => [
                    'title'             => $product->get_name(),
                    'description'       => (string) $product->get_description(),
                    'short_description' => $this->short_description($product, (string) $settings['short_description_source']),
                    'price'             => (float) wc_get_price_to_display($price_source),
                    'old_price'         => $this->old_price($price_source),
                    'currency'          => (string) ($settings['currency'] ?: get_woocommerce_currency()),
                    'quantity'          => $stock['quantity'],
                    'stock_status'      => $stock['stock_status'],
                    'vendor'            => $this->vendor($product),
                    'sku'               => $price_source->get_sku() ?: $product->get_sku(),
                    'label'             => '',
                    'sort_order'        => $product->get_menu_order(),
                    'attributes'        => (object) $this->attributes($product, $settings),
                    'attribute_filters' => $this->attribute_filters($product, $settings),
                    'images'            => $this->images($product, (string) $settings['image_size']),
                ],
                'category' => $category,
            ],
        ];
    }

    private function price_source(\WC_Product $product): ?\WC_Product
    {
        if (! $product instanceof \WC_Product_Variable) {
            return $product;
        }

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation instanceof \WC_Product_Variation && $variation->is_in_stock()) {
                return $variation;
            }
        }

        $children = $product->get_children();
        if ($children === []) {
            return $product;
        }

        $variation = wc_get_product((int) reset($children));

        return $variation instanceof \WC_Product ? $variation : $product;
    }

    /**
     * @return array{quantity: int, stock_status: string}
     */
    private function stock_payload(\WC_Product $product): array
    {
        if (! $product instanceof \WC_Product_Variable) {
            $available = $product->is_in_stock();
            $quantity = $product->managing_stock() ? max(0, (int) $product->get_stock_quantity()) : ($available ? 1 : 0);

            return [
                'quantity'     => $quantity,
                'stock_status' => $available ? 'in_stock' : 'out_of_stock',
            ];
        }

        $quantity = 0;
        $available = false;

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (! $variation instanceof \WC_Product_Variation || ! $variation->is_in_stock()) {
                continue;
            }

            $available = true;
            if (! $variation->managing_stock()) {
                return ['quantity' => 1, 'stock_status' => 'in_stock'];
            }

            $quantity += max(0, (int) $variation->get_stock_quantity());
        }

        return [
            'quantity'     => $quantity > 0 ? $quantity : ($available ? 1 : 0),
            'stock_status' => $available ? 'in_stock' : 'out_of_stock',
        ];
    }

    private function old_price(\WC_Product $product): float
    {
        $regular = (float) $product->get_regular_price();
        $sale = (float) $product->get_sale_price();

        return $sale > 0 && $regular > $sale ? $regular : 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function category_payload(\WC_Product $product): array
    {
        $category_ids = $product->get_category_ids();
        $term = $category_ids !== [] ? get_term((int) reset($category_ids), 'product_cat') : null;
        $parent = null;

        if ($term instanceof \WP_Term && (int) $term->parent > 0) {
            $parent = get_term((int) $term->parent, 'product_cat');
        }

        $path = $term instanceof \WP_Term ? $this->category_path($term) : '';

        return [
            'external_id'         => $term instanceof \WP_Term ? (string) $term->term_id : '',
            'external_url'        => $term instanceof \WP_Term ? $this->term_url($term) : '',
            'external_parent_id'  => $parent instanceof \WP_Term ? (string) $parent->term_id : '',
            'external_name'       => $term instanceof \WP_Term ? $term->name : '',
            'external_parent_name'=> $parent instanceof \WP_Term ? $parent->name : '',
            'external_parent_url' => $parent instanceof \WP_Term ? $this->term_url($parent) : '',
            'position'            => $term instanceof \WP_Term ? (int) get_term_meta($term->term_id, 'order', true) : 0,
            'parent_position'     => $parent instanceof \WP_Term ? (int) get_term_meta($parent->term_id, 'order', true) : 0,
            'full_path'           => $path,
            'path'                => $path,
        ];
    }

    private function term_url(\WP_Term $term): string
    {
        $url = get_term_link($term);

        return is_wp_error($url) ? '' : (string) $url;
    }

    private function category_path(\WP_Term $term): string
    {
        $names = [$term->name];
        $parent_id = (int) $term->parent;

        while ($parent_id > 0) {
            $parent = get_term($parent_id, 'product_cat');
            if (! $parent instanceof \WP_Term) {
                break;
            }

            array_unshift($names, $parent->name);
            $parent_id = (int) $parent->parent;
        }

        return implode(' / ', $names);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, string>
     */
    private function attributes(\WC_Product $product, array $settings): array
    {
        $attributes = [];

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible()) {
                continue;
            }

            $values = $this->attribute_values($product, $attribute);
            if ($values === []) {
                continue;
            }

            $attributes[wc_attribute_label($attribute->get_name())] = implode('; ', $values);
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<int, string>
     */
    private function attribute_filters(\WC_Product $product, array $settings): array
    {
        $filters = [];
        $filter_slugs = array_flip(array_merge(
            (bool) $settings['attribute_filters_enabled'] ? (array) $settings['attribute_filter_slugs'] : [],
            (bool) $settings['option_filters_enabled'] ? (array) $settings['option_filter_slugs'] : []
        ));

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible()) {
                continue;
            }

            if (isset($filter_slugs[$attribute->get_name()])) {
                $filters[] = wc_attribute_label($attribute->get_name());
            }
        }

        return array_values(array_unique($filters));
    }

    /**
     * @return array<int, string>
     */
    private function attribute_values(\WC_Product $product, \WC_Product_Attribute $attribute): array
    {
        if ($attribute->is_taxonomy()) {
            return array_values(array_filter(array_map('strval', wc_get_product_terms(
                $product->get_id(),
                $attribute->get_name(),
                ['fields' => 'names']
            ))));
        }

        return array_values(array_filter(array_map('strval', $attribute->get_options())));
    }

    /**
     * @return array<int, string>
     */
    private function images(\WC_Product $product, string $image_size): array
    {
        $image_ids = array_values(array_filter(array_merge(
            [$product->get_image_id()],
            $product->get_gallery_image_ids()
        )));

        $urls = [];
        foreach ($image_ids as $image_id) {
            $url = wp_get_attachment_image_url((int) $image_id, $this->image_size($image_size));
            if ($url !== false) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function image_size(string $image_size): string
    {
        if ($image_size === 'full') {
            return 'full';
        }

        return array_key_exists($image_size, wp_get_registered_image_subsizes()) ? $image_size : 'thumbnail';
    }

    private function short_description(\WC_Product $product, string $source): string
    {
        if ($source === 'meta_description') {
            foreach (['_yoast_wpseo_metadesc', 'rank_math_description', '_aioseo_description'] as $meta_key) {
                $value = get_post_meta($product->get_id(), $meta_key, true);
                if (is_string($value) && trim($value) !== '') {
                    return wp_strip_all_tags($value);
                }
            }
        }

        if ($source === 'description') {
            return wp_strip_all_tags($product->get_description());
        }

        return wp_strip_all_tags($product->get_short_description());
    }

    private function vendor(\WC_Product $product): string
    {
        foreach (['product_brand', 'pa_brand', 'pa_brend', 'pa_vendor'] as $taxonomy) {
            if (! taxonomy_exists($taxonomy)) {
                continue;
            }

            $terms = wc_get_product_terms($product->get_id(), $taxonomy, ['fields' => 'names']);
            if (is_array($terms) && $terms !== []) {
                return (string) reset($terms);
            }
        }

        return '';
    }
}
