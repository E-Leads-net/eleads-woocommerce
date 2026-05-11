<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Assets
{
    public function enqueue(string $hook_suffix): void
    {
        if ($hook_suffix !== 'toplevel_page_e-leads-for-woocommerce') {
            return;
        }

        wp_enqueue_style(
            'e-leads-for-woocommerce-admin',
            ELEADS_WOOCOMMERCE_URL . 'assets/admin.css',
            [],
            $this->asset_version('assets/admin.css')
        );

        wp_enqueue_script(
            'e-leads-for-woocommerce-admin',
            ELEADS_WOOCOMMERCE_URL . 'assets/admin.js',
            [],
            $this->asset_version('assets/admin.js'),
            true
        );

        wp_localize_script('e-leads-for-woocommerce-admin', 'eleadsWooCommerce', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('eleads_feed_generation'),
            'i18n'    => [
                'generating' => __('Генерація...', 'e-leads-for-woocommerce'),
                'ready'      => __('Фід готовий', 'e-leads-for-woocommerce'),
                'failed'     => __('Помилка генерації', 'e-leads-for-woocommerce'),
                'copy'       => __('Копіювати URL', 'e-leads-for-woocommerce'),
                'copied'     => __('Скопійовано', 'e-leads-for-woocommerce'),
            ],
        ]);
    }

    private function asset_version(string $relative_path): string
    {
        $path = ELEADS_WOOCOMMERCE_PATH . $relative_path;

        return ELEADS_WOOCOMMERCE_VERSION . '-' . (file_exists($path) ? (string) filemtime($path) : '0');
    }
}
