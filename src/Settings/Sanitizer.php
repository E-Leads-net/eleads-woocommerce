<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Settings;

if (! defined('ABSPATH')) {
    exit;
}

final class Sanitizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function api_key(array $input): array
    {
        return [
            'api_key' => isset($input['api_key']) ? sanitize_text_field((string) wp_unslash($input['api_key'])) : '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function export_settings(array $input): array
    {
        $image_size = isset($input['image_size']) ? sanitize_key((string) wp_unslash($input['image_size'])) : 'thumbnail';
        $short_description_source = isset($input['short_description_source'])
            ? sanitize_key((string) wp_unslash($input['short_description_source']))
            : 'short_description';

        return [
            'sync_enabled'             => isset($input['sync_enabled']),
            'widgets_enabled'          => isset($input['widgets_enabled']),
            'image_size'               => $this->image_size($image_size),
            'feed_key'                 => isset($input['feed_key']) ? sanitize_text_field((string) wp_unslash($input['feed_key'])) : '',
            'store_name'               => isset($input['store_name']) ? sanitize_text_field((string) wp_unslash($input['store_name'])) : '',
            'email'                    => isset($input['email']) ? sanitize_email((string) wp_unslash($input['email'])) : '',
            'store_url'                => isset($input['store_url']) ? esc_url_raw((string) wp_unslash($input['store_url'])) : '',
            'currency'                 => isset($input['currency']) ? strtoupper(sanitize_text_field((string) wp_unslash($input['currency']))) : 'UAH',
            'picture_limit'            => $this->picture_limit($input['picture_limit'] ?? 5),
            'short_description_source' => $this->allowed_value(
                $short_description_source,
                ['short_description', 'meta_description', 'description'],
                'short_description'
            ),
            'category_ids'             => $this->category_ids($input['category_ids'] ?? []),
            'category_selection_initialized' => true,
            'grouped_products'         => isset($input['grouped_products']),
            'attribute_filters_enabled' => isset($input['attribute_filters_enabled']),
            'attribute_filter_slugs'    => $this->slugs($input['attribute_filter_slugs'] ?? []),
            'option_filters_enabled'    => isset($input['option_filters_enabled']),
            'option_filter_slugs'       => $this->slugs($input['option_filter_slugs'] ?? []),
        ];
    }

    /**
     * @param array<int, string> $allowed
     */
    private function allowed_value(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function picture_limit(mixed $value): int
    {
        $limit = absint($value);

        if ($limit > 20) {
            return 20;
        }

        return $limit;
    }

    private function image_size(string $value): string
    {
        if ($value === 'full') {
            return 'full';
        }

        return array_key_exists($value, wp_get_registered_image_subsizes()) ? $value : 'thumbnail';
    }

    /**
     * @return array<int, int>
     */
    private function category_ids(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('absint', $value))));
    }

    /**
     * @return array<int, string>
     */
    private function slugs(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static function (mixed $slug): string {
            $slug = sanitize_key((string) wp_unslash($slug));

            return str_starts_with($slug, 'pa_') ? $slug : '';
        }, $value))));
    }
}
