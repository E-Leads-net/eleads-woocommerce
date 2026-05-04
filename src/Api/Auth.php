<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

use Eleads\WooCommerce\Settings\SettingsRepository;

final class Auth
{
    private SettingsRepository $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function validate(): ?string
    {
        $api_key = trim((string) $this->settings->get('api_key'));
        if ($api_key === '') {
            return 'api_key_missing';
        }

        $authorization = $this->authorization_header();
        if ($authorization === null || stripos($authorization, 'Bearer ') !== 0) {
            return 'unauthorized';
        }

        $token = trim(substr($authorization, 7));
        if (! hash_equals($api_key, $token)) {
            return 'unauthorized';
        }

        return null;
    }

    private function authorization_header(): ?string
    {
        if (! empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (! empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp((string) $name, 'Authorization') === 0) {
                    return (string) $value;
                }
            }
        }

        return null;
    }
}
