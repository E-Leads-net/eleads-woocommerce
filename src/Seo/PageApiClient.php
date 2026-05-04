<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Seo;

use Eleads\WooCommerce\Api\Routes;

final class PageApiClient
{
    /**
     * @return array<string, mixed>|null
     */
    public function get(string $api_key, string $slug, string $language): ?array
    {
        if (trim($api_key) === '' || trim($slug) === '') {
            return null;
        }

        $response = wp_remote_get(Routes::seo_page_url($slug, $language), [
            'timeout' => 8,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return null;
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($data)) {
            return null;
        }

        if (isset($data['page']) && is_array($data['page'])) {
            return $data['page'];
        }

        if (isset($data['data']['page']) && is_array($data['data']['page'])) {
            return $data['data']['page'];
        }

        return $data;
    }
}
