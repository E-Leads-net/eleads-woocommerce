<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

final class DashboardTokenValidator
{
    /**
     * @return array{ok: bool, seo_status: bool}
     */
    public function validate(string $api_key): array
    {
        $api_key = trim($api_key);
        if ($api_key === '') {
            return ['ok' => false, 'seo_status' => false];
        }

        $response = wp_remote_get(Routes::token_status_url(), [
            'timeout' => 6,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'seo_status' => false];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'seo_status' => false];
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($data)) {
            return ['ok' => false, 'seo_status' => false];
        }

        return [
            'ok'         => ! empty($data['ok']),
            'seo_status' => ! empty($data['seo_status']),
        ];
    }
}
