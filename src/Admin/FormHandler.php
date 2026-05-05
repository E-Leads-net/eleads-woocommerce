<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

if (! defined('ABSPATH')) {
    exit;
}

use Eleads\WooCommerce\Settings\Sanitizer;
use Eleads\WooCommerce\Settings\SettingsRepository;
use Eleads\WooCommerce\Api\DashboardTokenValidator;
use Eleads\WooCommerce\Api\SeoSitemap;

final class FormHandler
{
    private SettingsRepository $settings;

    private Sanitizer $sanitizer;

    private DashboardTokenValidator $token_validator;

    private SeoSitemap $seo_sitemap;

    public function __construct(
        SettingsRepository $settings,
        Sanitizer $sanitizer,
        DashboardTokenValidator $token_validator,
        SeoSitemap $seo_sitemap
    )
    {
        $this->settings        = $settings;
        $this->sanitizer       = $sanitizer;
        $this->token_validator = $token_validator;
        $this->seo_sitemap     = $seo_sitemap;
    }

    public function handle(): void
    {
        if (! $this->is_settings_page_post()) {
            return;
        }

        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to save these settings.', 'eleads-for-woocommerce'));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is verified by each action-specific save handler.
        $action = isset($_POST['eleads_action']) ? sanitize_key((string) wp_unslash($_POST['eleads_action'])) : '';

        match ($action) {
            'save_api_key' => $this->save_api_key(),
            'save_export_settings' => $this->save_export_settings(),
            'save_seo_settings' => $this->save_seo_settings(),
            default => null,
        };
    }

    private function save_api_key(): void
    {
        check_admin_referer('eleads_save_api_key', 'eleads_api_key_nonce');

        $settings = $this->sanitizer->api_key(wp_unslash($_POST));
        $status = $this->token_validator->validate((string) $settings['api_key']);

        $settings['api_key_valid'] = $status['ok'];
        $settings['seo_pages_allowed'] = $status['seo_status'];
        if (! $status['seo_status']) {
            $settings['seo_pages_enabled'] = false;
            $this->seo_sitemap->remove();
        }

        $this->settings->update($settings);
        $this->redirect('api-key');
    }

    private function save_export_settings(): void
    {
        check_admin_referer('eleads_save_export_settings', 'eleads_export_nonce');

        $this->settings->update($this->sanitizer->export_settings(wp_unslash($_POST)));
        $this->redirect('export');
    }

    private function save_seo_settings(): void
    {
        check_admin_referer('eleads_save_seo_settings', 'eleads_seo_nonce');

        $current = $this->settings->all();
        $enabled = ! empty($current['api_key_valid'])
            && ! empty($current['seo_pages_allowed'])
            && isset(wp_unslash($_POST)['seo_pages_enabled']);

        $this->settings->update(['seo_pages_enabled' => $enabled]);

        if ($enabled) {
            $this->seo_sitemap->create_from_dashboard((string) $current['api_key']);
        } else {
            $this->seo_sitemap->remove();
        }

        $this->redirect('seo');
    }

    private function is_settings_page_post(): bool
    {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_key((string) wp_unslash($_SERVER['REQUEST_METHOD'])) : '';

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This only checks whether the request should be routed to a nonce-protected handler.
        return is_admin() && $request_method === 'POST' && isset($_POST['eleads_action']);
    }

    private function redirect(string $tab): void
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'          => 'eleads-for-woocommerce',
                    'tab'           => $tab,
                    'eleads_saved'  => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
