<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Sync;

if (! defined('ABSPATH')) {
    exit;
}

use Eleads\WooCommerce\Api\Routes;
use Eleads\WooCommerce\Settings\SettingsRepository;

final class Service
{
    private SettingsRepository $settings;

    private PayloadBuilder $payload_builder;

    private ApiClient $api_client;

    private LanguageResolver $language_resolver;

    public function __construct(
        SettingsRepository $settings,
        PayloadBuilder $payload_builder,
        ApiClient $api_client,
        LanguageResolver $language_resolver
    ) {
        $this->settings          = $settings;
        $this->payload_builder   = $payload_builder;
        $this->api_client        = $api_client;
        $this->language_resolver = $language_resolver;
    }

    public function sync_created(int $product_id): void
    {
        $api_key = $this->api_key();
        if ($api_key === '') {
            return;
        }

        $payload = $this->payload($product_id);
        if ($payload === null) {
            return;
        }

        $this->api_client->send(Routes::ecommerce_items_url(), 'POST', $payload, $api_key);
    }

    public function sync_updated(int $product_id): void
    {
        $api_key = $this->api_key();
        if ($api_key === '') {
            return;
        }

        $payload = $this->payload($product_id);
        if ($payload === null) {
            return;
        }

        $this->api_client->send(Routes::ecommerce_item_url((string) $product_id), 'PUT', $payload, $api_key);
    }

    public function sync_deleted(int $product_id): void
    {
        $api_key = $this->api_key();
        if ($api_key === '') {
            return;
        }

        $payload = [
            'language' => $this->language_resolver->default_language(),
        ];

        $this->api_client->send(Routes::ecommerce_item_url((string) $product_id), 'DELETE', $payload, $api_key);
    }

    private function api_key(): string
    {
        $settings = $this->settings->all();
        if (empty($settings['sync_enabled']) || empty($settings['api_key_valid'])) {
            return '';
        }

        return trim((string) $settings['api_key']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payload(int $product_id): ?array
    {
        try {
            return $this->payload_builder->build($product_id);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
