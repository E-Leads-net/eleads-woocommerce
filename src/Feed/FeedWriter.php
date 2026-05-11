<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

if (! defined('ABSPATH')) {
    exit;
}

use WP_Term;

final class FeedWriter
{
    private PathResolver $paths;

    public function __construct(PathResolver $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<int, WP_Term> $categories
     */
    public function start(string $language, array $meta, array $categories): void
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<yml_catalog date="' . $this->escape((string) $meta['feed_date']) . '">';
        $lines[] = '<shop>';
        $lines[] = '<shopName>' . $this->escape((string) $meta['shop_name']) . '</shopName>';
        $lines[] = '<email>' . $this->escape((string) $meta['email']) . '</email>';
        $lines[] = '<url>' . $this->escape((string) $meta['shop_url']) . '</url>';
        $lines[] = '<language>' . $this->escape((string) $meta['language']) . '</language>';
        $lines[] = '<categories>';

        foreach ($categories as $category) {
            $attributes = ['id="' . (int) $category->term_id . '"'];
            if ((int) $category->parent > 0) {
                $attributes[] = 'parentId="' . (int) $category->parent . '"';
            }
            $attributes[] = 'position="0"';
            $url = get_term_link($category);
            $attributes[] = 'url="' . $this->escape(is_wp_error($url) ? '' : (string) $url) . '"';
            $lines[] = '<category ' . implode(' ', $attributes) . '>' . $this->escape($category->name) . '</category>';
        }

        $lines[] = '</categories>';
        $lines[] = '<offers>';

        file_put_contents($this->paths->temp_path($language), implode('', $lines), LOCK_EX); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Feed XML is generated as a file for large catalogs.
    }

    /**
     * @param array<int, array<string, mixed>> $offers
     */
    public function append_offers(string $language, array $offers): void
    {
        $chunks = [];

        foreach ($offers as $offer) {
            $attributes = ['id="' . (int) $offer['id'] . '"'];
            if (! empty($offer['group_id'])) {
                $attributes[] = 'group_id="' . (int) $offer['group_id'] . '"';
            }
            $attributes[] = 'available="' . (! empty($offer['available']) ? 'true' : 'false') . '"';

            $chunks[] = '<offer ' . implode(' ', $attributes) . '>';
            $chunks[] = '<url>' . $this->escape((string) $offer['url']) . '</url>';
            $chunks[] = '<name>' . $this->escape((string) $offer['name']) . '</name>';
            $chunks[] = '<price>' . $this->escape((string) $offer['price']) . '</price>';
            $chunks[] = '<old_price>' . ($offer['old_price'] !== null ? $this->escape((string) $offer['old_price']) : '') . '</old_price>';
            $chunks[] = '<currency>' . $this->escape((string) $offer['currency']) . '</currency>';
            $chunks[] = '<categoryId>' . (int) $offer['category_id'] . '</categoryId>';
            $chunks[] = '<quantity>' . (int) $offer['quantity'] . '</quantity>';
            $chunks[] = '<stock_status>' . $this->escape((string) $offer['stock_status']) . '</stock_status>';

            foreach ((array) $offer['pictures'] as $picture) {
                $chunks[] = '<picture>' . $this->escape((string) $picture) . '</picture>';
            }

            $chunks[] = '<vendor>' . $this->escape((string) $offer['vendor']) . '</vendor>';
            $chunks[] = '<sku>' . $this->escape((string) $offer['sku']) . '</sku>';
            $chunks[] = '<label/>';
            $chunks[] = '<order>' . (int) $offer['order'] . '</order>';
            $chunks[] = '<description>' . $this->escape((string) $offer['description']) . '</description>';
            $chunks[] = '<short_description>' . $this->escape((string) $offer['short_description']) . '</short_description>';

            foreach ((array) $offer['params'] as $param) {
                $chunks[] = '<param' . (! empty($param['filter']) ? ' filter="true"' : '') . ' name="' . $this->escape((string) $param['name']) . '">' . $this->escape((string) $param['value']) . '</param>';
            }

            $chunks[] = '</offer>';
        }

        if ($chunks !== []) {
            file_put_contents($this->paths->temp_path($language), implode('', $chunks), FILE_APPEND | LOCK_EX); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Batch append avoids holding large feeds in memory.
        }
    }

    public function finalize(string $language): int
    {
        $temp = $this->paths->temp_path($language);
        $final = $this->paths->final_path($language);
        file_put_contents($temp, '</offers></shop></yml_catalog>', FILE_APPEND | LOCK_EX); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Final XML closing tags are appended to the generated feed file.
        $this->move($temp, $final);

        return (int) filesize($final);
    }

    private function move(string $source, string $destination): void
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();

        global $wp_filesystem;

        if ($wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->move($source, $destination, true)) {
            return;
        }

        throw new \RuntimeException('move_failed');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
