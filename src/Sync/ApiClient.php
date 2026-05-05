<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Sync;

if (! defined('ABSPATH')) {
    exit;
}

final class ApiClient
{
    /**
     * @param array<string, mixed> $payload
     */
    public function send(string $url, string $method, array $payload, string $api_key): void
    {
        $body = wp_json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (! is_string($body)) {
            return;
        }

        wp_remote_request($url, [
            'method'  => strtoupper($method),
            'timeout' => 2,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'body'    => $body,
        ]);
    }
}
