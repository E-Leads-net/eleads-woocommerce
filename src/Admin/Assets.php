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
        if ($hook_suffix !== 'toplevel_page_eleads-for-woocommerce') {
            return;
        }

        wp_enqueue_style(
            'eleads-for-woocommerce-admin',
            ELEADS_WOOCOMMERCE_URL . 'assets/admin.css',
            [],
            $this->asset_version('assets/admin.css')
        );

        wp_enqueue_script(
            'eleads-for-woocommerce-admin',
            ELEADS_WOOCOMMERCE_URL . 'assets/admin.js',
            [],
            $this->asset_version('assets/admin.js'),
            true
        );

        wp_localize_script('eleads-for-woocommerce-admin', 'eleadsWooCommerce', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('eleads_feed_generation'),
            'i18n'    => [
                'generating' => __('Генерація...', 'eleads-for-woocommerce'),
                'ready'      => __('Фід готовий', 'eleads-for-woocommerce'),
                'failed'     => __('Помилка генерації', 'eleads-for-woocommerce'),
                'copy'       => __('Копіювати URL', 'eleads-for-woocommerce'),
                'copied'     => __('Скопійовано', 'eleads-for-woocommerce'),
            ],
        ]);
    }

    private function asset_version(string $relative_path): string
    {
        $path = ELEADS_WOOCOMMERCE_PATH . $relative_path;

        return ELEADS_WOOCOMMERCE_VERSION . '-' . (file_exists($path) ? (string) filemtime($path) : '0');
    }
}
