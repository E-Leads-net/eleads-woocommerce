<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

final class PluginLinks
{
    /**
     * @param array<int|string, string> $links
     * @return array<int|string, string>
     */
    public function add_settings_link(array $links): array
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=eleads-woocommerce')),
            esc_html__('Settings', 'eleads-woocommerce')
        );

        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * @param array<int|string, string> $links
     * @return array<int|string, string>
     */
    public function add_meta_links(array $links): array
    {
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/docs/'),
            esc_html__('Docs', 'eleads-woocommerce')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/privacy-policy/'),
            esc_html__('Privacy Policy', 'eleads-woocommerce')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/terms-of-service/'),
            esc_html__('Terms', 'eleads-woocommerce')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/security/'),
            esc_html__('Security', 'eleads-woocommerce')
        );

        return $links;
    }
}
