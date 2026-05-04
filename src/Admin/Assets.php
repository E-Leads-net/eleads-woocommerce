<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

final class Assets
{
    public function enqueue(string $hook_suffix): void
    {
        if ($hook_suffix !== 'toplevel_page_eleads-woocommerce') {
            return;
        }

        wp_enqueue_style(
            'eleads-woocommerce-admin',
            ELEADS_WOOCOMMERCE_URL . 'assets/admin.css',
            [],
            ELEADS_WOOCOMMERCE_VERSION
        );

        wp_enqueue_script(
            'eleads-woocommerce-admin',
            ELEADS_WOOCOMMERCE_URL . 'assets/admin.js',
            [],
            ELEADS_WOOCOMMERCE_VERSION,
            true
        );

        wp_localize_script('eleads-woocommerce-admin', 'eleadsWooCommerce', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('eleads_feed_generation'),
            'i18n'    => [
                'generating' => __('Генерація...', 'eleads-woocommerce'),
                'ready'      => __('Фід готовий', 'eleads-woocommerce'),
                'failed'     => __('Помилка генерації', 'eleads-woocommerce'),
                'copy'       => __('Копіювати URL', 'eleads-woocommerce'),
                'copied'     => __('Скопійовано', 'eleads-woocommerce'),
            ],
        ]);
    }
}
