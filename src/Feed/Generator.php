<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

use Eleads\WooCommerce\Settings\SettingsRepository;

final class Generator
{
    private const BATCH_SIZE = 300;

    private SettingsRepository $settings;

    private Language $language;

    private CategoryExporter $categories;

    private ProductQuery $products;

    private OfferBuilder $offers;

    private FeedWriter $writer;

    private StatusRepository $statuses;

    public function __construct(
        SettingsRepository $settings,
        Language $language,
        CategoryExporter $categories,
        ProductQuery $products,
        OfferBuilder $offers,
        FeedWriter $writer,
        StatusRepository $statuses
    ) {
        $this->settings   = $settings;
        $this->language   = $language;
        $this->categories = $categories;
        $this->products   = $products;
        $this->offers     = $offers;
        $this->writer     = $writer;
        $this->statuses   = $statuses;
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(string $language): array
    {
        $state = $this->start($language);

        while ($state['status'] === 'running') {
            $state = $this->process_next_batch($language);
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function start(string $language): array
    {
        $language = $this->language->normalize($language);
        $settings = $this->settings->all();
        $selected_category_ids = array_map('absint', (array) $settings['category_ids']);
        $previous_language = $this->language->switch_to($language);

        try {
            $total = $selected_category_ids !== []
                ? $this->products->export_item_count($selected_category_ids, (bool) ($settings['grouped_products'] ?? true))
                : 0;
            $this->statuses->running($language, $total, self::BATCH_SIZE);

            $this->writer->start($language, [
                'feed_date' => date('Y-m-d H:i'),
                'shop_name' => (string) $settings['store_name'],
                'email'     => (string) $settings['email'],
                'shop_url'  => (string) $settings['store_url'],
                'language'  => $this->language->label($language),
            ], $this->categories->export_categories($selected_category_ids));

            if ($selected_category_ids === [] || $total === 0) {
                $size = $this->writer->finalize($language);
                $this->statuses->ready($language, $size, 0);
            }

            $this->language->restore($previous_language);
            return $this->statuses->get($language);
        } catch (\Throwable $e) {
            $this->statuses->failed($language, $e->getMessage());
            $this->language->restore($previous_language);
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function process_next_batch(string $language): array
    {
        $language = $this->language->normalize($language);
        $state = $this->statuses->get($language);

        if ($state['status'] !== 'running') {
            return $state;
        }

        $settings = $this->settings->all();
        $selected_category_ids = array_map('absint', (array) $settings['category_ids']);
        $previous_language = $this->language->switch_to($language);

        try {
            $this->products->register_id_filter();
            $products = $this->products->batch(
                $selected_category_ids,
                (int) $state['last_product_id'],
                (int) $state['batch_size']
            );
            $this->products->unregister_id_filter();

            if ($products === []) {
                $size = $this->writer->finalize($language);
                $this->statuses->ready($language, $size, (int) $state['offers']);
                $this->language->restore($previous_language);
                return $this->statuses->get($language);
            }

            $offer_count = (int) $state['offers'];
            $last_product_id = (int) $state['last_product_id'];

            foreach ($products as $product) {
                $offers = $this->offers->build($product, $settings, $selected_category_ids, $language);
                $offer_count += count($offers);
                $last_product_id = max($last_product_id, $product->get_id());
                $this->writer->append_offers($language, $offers);
            }

            $processed = (bool) ($settings['grouped_products'] ?? true)
                ? (int) $state['processed'] + count($products)
                : $offer_count;
            $processed = min((int) $state['total'], $processed);
            $this->statuses->progress($language, $processed, (int) $state['total'], $last_product_id, $offer_count);

            $this->language->restore($previous_language);
            return $this->statuses->get($language);
        } catch (\Throwable $e) {
            $this->products->unregister_id_filter();
            $this->statuses->failed($language, $e->getMessage());
            $this->language->restore($previous_language);
            throw $e;
        }
    }
}
