<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

if (! defined('ABSPATH')) {
    exit;
}

final class ProductQuery
{
    /**
     * @param array<int, int> $category_ids
     * @return array<int, \WC_Product>
     */
    public function products(array $category_ids): array
    {
        $args = [
            'status' => 'publish',
            'limit'  => -1,
            'return' => 'objects',
        ];

        if ($category_ids !== []) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Category filtering is required for export settings.
            $args['tax_query'] = [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $category_ids,
                    'include_children' => false,
                ],
            ];
        }

        $products = wc_get_products($args);

        return is_array($products) ? $products : [];
    }

    /**
     * @param array<int, int> $category_ids
     */
    public function count(array $category_ids): int
    {
        $query = new \WP_Query(array_merge($this->query_args($category_ids), [
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
        ]));

        return (int) $query->found_posts;
    }

    /**
     * @param array<int, int> $category_ids
     */
    public function export_item_count(array $category_ids, bool $grouped_products): int
    {
        if ($grouped_products) {
            return $this->count($category_ids);
        }

        $total = 0;
        $page = 1;

        do {
            $query = new \WP_Query(array_merge($this->query_args($category_ids), [
                'posts_per_page' => 500,
                'paged'          => $page,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]));

            foreach ($query->posts as $product_id) {
                $product = wc_get_product((int) $product_id);
                if ($product instanceof \WC_Product_Variable) {
                    $children = $product->get_children();
                    $total += $children !== [] ? count($children) : 1;
                    continue;
                }

                if ($product instanceof \WC_Product) {
                    $total++;
                }
            }

            $page++;
        } while ($query->posts !== []);

        return $total;
    }

    /**
     * @param array<int, int> $category_ids
     * @return array<int, \WC_Product>
     */
    public function batch(array $category_ids, int $last_product_id, int $limit): array
    {
            $query = new \WP_Query(array_merge($this->query_args($category_ids), [
                'posts_per_page' => max(1, $limit),
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Empty placeholders keep query args stable for filters.
                'post__not_in'   => [],
                'date_query'     => [],
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Empty placeholders keep query args stable for filters.
                'meta_query'     => [],
        ], [
            'eleads_last_product_id' => $last_product_id,
        ]));

        $products = [];
        foreach ($query->posts as $product_id) {
            $product = wc_get_product((int) $product_id);
            if ($product instanceof \WC_Product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    public function register_id_filter(): void
    {
        add_filter('posts_where', [$this, 'where_after_id'], 10, 2);
    }

    public function unregister_id_filter(): void
    {
        remove_filter('posts_where', [$this, 'where_after_id'], 10);
    }

    public function where_after_id(string $where, \WP_Query $query): string
    {
        global $wpdb;

        $last_product_id = (int) $query->get('eleads_last_product_id');
        if ($last_product_id <= 0) {
            return $where;
        }

        return $where . $wpdb->prepare(" AND {$wpdb->posts}.ID > %d", $last_product_id);
    }

    /**
     * @param array<int, int> $category_ids
     * @return array<string, mixed>
     */
    private function query_args(array $category_ids): array
    {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'posts_per_page' => -1,
        ];

        if ($category_ids !== []) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Category filtering is required for export settings.
            $args['tax_query'] = [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $category_ids,
                    'include_children' => false,
                ],
            ];
        }

        return $args;
    }
}
