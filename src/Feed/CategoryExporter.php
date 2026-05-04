<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

use WP_Term;

final class CategoryExporter
{
    /**
     * @param array<int, int> $selected_category_ids
     * @return array<int, WP_Term>
     */
    public function export_categories(array $selected_category_ids): array
    {
        if ($selected_category_ids === []) {
            return [];
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || ! is_array($terms)) {
            return [];
        }

        $terms_by_id = [];
        foreach ($terms as $term) {
            $terms_by_id[(int) $term->term_id] = $term;
        }

        $include = [];
        foreach ($selected_category_ids as $category_id) {
            $current = (int) $category_id;
            while ($current > 0 && isset($terms_by_id[$current])) {
                $include[$current] = true;
                $current = (int) $terms_by_id[$current]->parent;
            }
        }

        return array_values(array_filter($terms, static function (WP_Term $term) use ($include): bool {
            return isset($include[(int) $term->term_id]);
        }));
    }
}
