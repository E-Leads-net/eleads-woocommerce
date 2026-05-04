<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Catalog;

final class ProductAttributes
{
    /**
     * @return array<int, array<string, string>>
     */
    public function all(): array
    {
        if (! function_exists('wc_get_attribute_taxonomies')) {
            return [];
        }

        $attributes = wc_get_attribute_taxonomies();

        if (! is_array($attributes)) {
            return [];
        }

        return array_values(array_map(static function (object $attribute): array {
            $name = (string) ($attribute->attribute_label ?: $attribute->attribute_name);
            $slug = wc_attribute_taxonomy_name((string) $attribute->attribute_name);

            return [
                'name' => $name,
                'slug' => $slug,
            ];
        }, $attributes));
    }
}
