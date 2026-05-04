<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

final class OfferBuilder
{
    /**
     * @param array<string, mixed> $settings
     * @param array<int, int> $selected_category_ids
     * @return array<int, array<string, mixed>>
     */
    public function build(\WC_Product $product, array $settings, array $selected_category_ids, string $language): array
    {
        $category_id = $this->category_id($product, $selected_category_ids);
        if ($category_id === 0) {
            return [];
        }

        if ($product instanceof \WC_Product_Variable) {
            if ((bool) ($settings['grouped_products'] ?? true)) {
                return [$this->grouped_variable_offer($product, $settings, $category_id, $language)];
            }

            return $this->variable_offers($product, $settings, $category_id, $language);
        }

        return [$this->offer($product, $settings, $category_id, $language, null, $product->get_name())];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function variable_offers(\WC_Product_Variable $product, array $settings, int $category_id, string $language): array
    {
        $offers = [];
        $children = $product->get_children();

        if ($children === []) {
            return [$this->offer($product, $settings, $category_id, $language, null, $product->get_name())];
        }

        foreach ($children as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (! $variation instanceof \WC_Product_Variation) {
                continue;
            }

            $name = $product->get_name();
            $suffix = wc_get_formatted_variation($variation, true, false, true);
            if ($suffix !== '') {
                $name .= ' ' . wp_strip_all_tags($suffix);
            }

            $offers[] = $this->offer($variation, $settings, $category_id, $language, $product, $name);
        }

        return $offers;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function grouped_variable_offer(\WC_Product_Variable $product, array $settings, int $category_id, string $language): array
    {
        $variations = $this->variation_products($product);
        $price_source = $this->first_available_variation($variations) ?? $product;
        $regular_price = (float) ($price_source instanceof \WC_Product_Variation
            ? $price_source->get_regular_price()
            : $product->get_variation_regular_price('min', true));
        $sale_price = (float) ($price_source instanceof \WC_Product_Variation
            ? $price_source->get_sale_price()
            : $product->get_variation_sale_price('min', true));
        $price = (float) ($price_source instanceof \WC_Product_Variation
            ? wc_get_price_to_display($price_source)
            : $product->get_variation_price('min', true));
        $old_price = $sale_price > 0 && $regular_price > $sale_price ? $regular_price : null;
        $available = $product->is_in_stock() || $this->has_available_variation($variations);

        return [
            'id'                => $product->get_id(),
            'group_id'          => null,
            'available'         => $available,
            'url'               => get_permalink($product->get_id()) ?: '',
            'name'              => $product->get_name(),
            'price'             => wc_format_decimal($price, wc_get_price_decimals()),
            'old_price'         => $old_price !== null ? wc_format_decimal($old_price, wc_get_price_decimals()) : null,
            'currency'          => (string) ($settings['currency'] ?: get_woocommerce_currency()),
            'category_id'       => $category_id,
            'quantity'          => $this->grouped_quantity($variations, $available),
            'stock_status'      => $this->stock_status($available, $language),
            'pictures'          => $this->pictures($product, (int) $settings['picture_limit'], (string) $settings['image_size']),
            'vendor'            => $this->vendor($product),
            'sku'               => $price_source->get_sku() ?: $product->get_sku(),
            'order'             => $product->get_menu_order(),
            'description'       => $product->get_description(),
            'short_description' => $this->short_description($product, (string) $settings['short_description_source']),
            'params'            => $this->grouped_params($product, $variations, $settings),
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function offer(\WC_Product $product, array $settings, int $category_id, string $language, ?\WC_Product $parent, string $name): array
    {
        $source = $parent ?? $product;
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();
        $price = (float) wc_get_price_to_display($product);
        $old_price = $sale_price > 0 && $regular_price > $sale_price ? $regular_price : null;
        $quantity = $product->managing_stock() ? max(0, (int) $product->get_stock_quantity()) : ($product->is_in_stock() ? 1 : 0);
        $available = $product->is_in_stock();

        return [
            'id'                => $product->get_id(),
            'group_id'          => $parent instanceof \WC_Product ? $parent->get_id() : null,
            'available'         => $available,
            'url'               => get_permalink($source->get_id()) ?: '',
            'name'              => $name,
            'price'             => wc_format_decimal($price, wc_get_price_decimals()),
            'old_price'         => $old_price !== null ? wc_format_decimal($old_price, wc_get_price_decimals()) : null,
            'currency'          => (string) ($settings['currency'] ?: get_woocommerce_currency()),
            'category_id'       => $category_id,
            'quantity'          => $quantity,
            'stock_status'      => $this->stock_status($available, $language),
            'pictures'          => $this->pictures($source, (int) $settings['picture_limit'], (string) $settings['image_size']),
            'vendor'            => $this->vendor($source),
            'sku'               => $product->get_sku(),
            'order'             => $source->get_menu_order(),
            'description'       => $source->get_description(),
            'short_description' => $this->short_description($source, (string) $settings['short_description_source']),
            'params'            => $this->params($source, $product, $settings),
        ];
    }

    /**
     * @param array<int, int> $selected_category_ids
     */
    private function category_id(\WC_Product $product, array $selected_category_ids): int
    {
        $category_ids = $product->get_category_ids();

        if ($category_ids === [] && $product instanceof \WC_Product_Variation) {
            $parent = wc_get_product($product->get_parent_id());
            $category_ids = $parent instanceof \WC_Product ? $parent->get_category_ids() : [];
        }

        foreach ($category_ids as $category_id) {
            if (in_array((int) $category_id, $selected_category_ids, true)) {
                return (int) $category_id;
            }
        }

        return (int) reset($category_ids);
    }

    /**
     * @return array<int, string>
     */
    private function pictures(\WC_Product $product, int $limit, string $image_size): array
    {
        $image_ids = array_values(array_filter(array_merge(
            [$product->get_image_id()],
            $product->get_gallery_image_ids()
        )));

        if ($limit > 0) {
            $image_ids = array_slice($image_ids, 0, $limit);
        }

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
            return $this->meta_description($product);
        }

        if ($source === 'description') {
            return wp_strip_all_tags($product->get_description());
        }

        return wp_strip_all_tags($product->get_short_description());
    }

    private function meta_description(\WC_Product $product): string
    {
        foreach (['_yoast_wpseo_metadesc', 'rank_math_description', '_aioseo_description'] as $meta_key) {
            $value = get_post_meta($product->get_id(), $meta_key, true);
            if (is_string($value) && trim($value) !== '') {
                return wp_strip_all_tags($value);
            }
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

    /**
     * @param array<string, mixed> $settings
     * @return array<int, array<string, mixed>>
     */
    private function params(\WC_Product $source, \WC_Product $product, array $settings): array
    {
        $params = [];
        $filter_slugs = array_flip(array_merge(
            (bool) $settings['attribute_filters_enabled'] ? (array) $settings['attribute_filter_slugs'] : [],
            (bool) $settings['option_filters_enabled'] ? (array) $settings['option_filter_slugs'] : []
        ));

        foreach ($source->get_attributes() as $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible()) {
                continue;
            }

            $slug = $attribute->get_name();
            $values = [];

            if ($attribute->is_taxonomy()) {
                foreach (wc_get_product_terms($source->get_id(), $slug, ['fields' => 'names']) as $term_name) {
                    $values[] = (string) $term_name;
                }
            } else {
                $values = array_map('strval', $attribute->get_options());
            }

            $values = array_values(array_unique(array_filter($values)));
            if ($values === []) {
                continue;
            }

            $label = wc_attribute_label($slug);
            $params[] = [
                'name'   => $label,
                'value'  => implode('; ', $values),
                'filter' => isset($filter_slugs[$slug]),
            ];
        }

        if ($product instanceof \WC_Product_Variation) {
            foreach ($product->get_attributes() as $slug => $value) {
                if ($value === '') {
                    continue;
                }
                $taxonomy = str_starts_with((string) $slug, 'pa_') ? (string) $slug : 'pa_' . (string) $slug;
                $term = get_term_by('slug', (string) $value, $taxonomy);
                $params[] = [
                    'name'   => wc_attribute_label($taxonomy),
                    'value'  => $term instanceof \WP_Term ? $term->name : (string) $value,
                    'filter' => isset($filter_slugs[$taxonomy]),
                ];
            }
        }

        return $params;
    }

    /**
     * @return array<int, \WC_Product_Variation>
     */
    private function variation_products(\WC_Product_Variable $product): array
    {
        $variations = [];

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation instanceof \WC_Product_Variation) {
                $variations[] = $variation;
            }
        }

        return $variations;
    }

    /**
     * @param array<int, \WC_Product_Variation> $variations
     */
    private function first_available_variation(array $variations): ?\WC_Product_Variation
    {
        foreach ($variations as $variation) {
            if ($variation->is_in_stock()) {
                return $variation;
            }
        }

        return $variations[0] ?? null;
    }

    /**
     * @param array<int, \WC_Product_Variation> $variations
     */
    private function has_available_variation(array $variations): bool
    {
        foreach ($variations as $variation) {
            if ($variation->is_in_stock()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, \WC_Product_Variation> $variations
     */
    private function grouped_quantity(array $variations, bool $available): int
    {
        $quantity = 0;

        foreach ($variations as $variation) {
            if (! $variation->is_in_stock()) {
                continue;
            }

            if (! $variation->managing_stock()) {
                return $available ? 1 : 0;
            }

            $quantity += max(0, (int) $variation->get_stock_quantity());
        }

        return $quantity > 0 ? $quantity : ($available ? 1 : 0);
    }

    /**
     * @param array<int, \WC_Product_Variation> $variations
     * @param array<string, mixed> $settings
     * @return array<int, array<string, mixed>>
     */
    private function grouped_params(\WC_Product_Variable $product, array $variations, array $settings): array
    {
        $params = $this->params($product, $product, $settings);
        $options = [];

        foreach ($variations as $variation) {
            $option = wp_strip_all_tags(wc_get_formatted_variation($variation, true, false, true));
            if ($option !== '') {
                $options[] = $option;
            }
        }

        $options = array_values(array_unique($options));
        if ($options !== []) {
            $params[] = [
                'name'   => __('Опції', 'eleads-woocommerce'),
                'value'  => implode(' | ', $options),
                'filter' => false,
            ];
        }

        return $params;
    }

    private function stock_status(bool $available, string $language): string
    {
        if ($language === 'uk' || $language === 'ua') {
            return $available ? 'В наявності' : 'Немає в наявності';
        }

        if ($language === 'en') {
            return $available ? 'In stock' : 'Out of stock';
        }

        return $available ? 'На складе' : 'Нет в наличии';
    }
}
