<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Catalog;

use WP_Term;

final class ProductCategoryTree
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || ! is_array($terms)) {
            return [];
        }

        return $this->build_tree($terms);
    }

    /**
     * @param array<int, WP_Term> $terms
     * @return array<int, array<string, mixed>>
     */
    private function build_tree(array $terms, int $parent = 0): array
    {
        $tree = [];

        foreach ($terms as $term) {
            if ((int) $term->parent !== $parent) {
                continue;
            }

            $tree[] = [
                'id'       => (int) $term->term_id,
                'name'     => $term->name,
                'count'    => (int) $term->count,
                'children' => $this->build_tree($terms, (int) $term->term_id),
            ];
        }

        return $tree;
    }
}
