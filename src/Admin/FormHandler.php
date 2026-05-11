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

    public function save_api_key_action(): void
    {
        $this->guard();
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
        $this->redirect($status['ok'] ? 'export' : 'api-key', $status['ok'] ? '1' : '0');
    }

    public function save_export_settings_action(): void
    {
        $this->guard();
        check_admin_referer('eleads_save_export_settings', 'eleads_export_nonce');

        $this->settings->update($this->sanitizer->export_settings(wp_unslash($_POST)));
        $this->redirect('export');
    }

    public function save_seo_settings_action(): void
    {
        $this->guard();
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

    private function guard(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to save these settings.', 'e-leads-for-woocommerce'));
        }
    }

    private function redirect(string $tab, string $saved = '1'): void
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'          => 'e-leads-for-woocommerce',
                    'tab'           => $tab,
                    'eleads_saved'  => $saved,
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
