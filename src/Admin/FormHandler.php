<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

use Eleads\WooCommerce\Settings\Sanitizer;
use Eleads\WooCommerce\Settings\SettingsRepository;
use Eleads\WooCommerce\Api\DashboardTokenValidator;

final class FormHandler
{
    private SettingsRepository $settings;

    private Sanitizer $sanitizer;

    private DashboardTokenValidator $token_validator;

    public function __construct(SettingsRepository $settings, Sanitizer $sanitizer, DashboardTokenValidator $token_validator)
    {
        $this->settings        = $settings;
        $this->sanitizer       = $sanitizer;
        $this->token_validator = $token_validator;
    }

    public function handle(): void
    {
        if (! $this->is_settings_page_post()) {
            return;
        }

        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to save these settings.', 'eleads-woocommerce'));
        }

        $action = isset($_POST['eleads_action']) ? sanitize_key((string) wp_unslash($_POST['eleads_action'])) : '';

        match ($action) {
            'save_api_key' => $this->save_api_key(),
            'save_export_settings' => $this->save_export_settings(),
            default => null,
        };
    }

    private function save_api_key(): void
    {
        check_admin_referer('eleads_save_api_key', 'eleads_api_key_nonce');

        $settings = $this->sanitizer->api_key($_POST);
        $status = $this->token_validator->validate((string) $settings['api_key']);

        $settings['api_key_valid'] = $status['ok'];
        $settings['seo_pages_allowed'] = $status['seo_status'];
        if (! $status['seo_status']) {
            $settings['seo_pages_enabled'] = false;
        }

        $this->settings->update($settings);
        $this->redirect('api-key');
    }

    private function save_export_settings(): void
    {
        check_admin_referer('eleads_save_export_settings', 'eleads_export_nonce');

        $this->settings->update($this->sanitizer->export_settings($_POST));
        $this->redirect('export');
    }

    private function is_settings_page_post(): bool
    {
        return is_admin()
            && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && isset($_POST['eleads_action']);
    }

    private function redirect(string $tab): void
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'          => 'eleads-woocommerce',
                    'tab'           => $tab,
                    'eleads_saved'  => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
