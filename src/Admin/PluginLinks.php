<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

if (! defined('ABSPATH')) {
    exit;
}

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
            esc_url(admin_url('admin.php?page=e-leads-for-woocommerce')),
            esc_html__('Settings', 'e-leads-for-woocommerce')
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
            esc_html__('Docs', 'e-leads-for-woocommerce')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/privacy-policy/'),
            esc_html__('Privacy Policy', 'e-leads-for-woocommerce')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/terms-of-service/'),
            esc_html__('Terms', 'e-leads-for-woocommerce')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://e-leads.net/security/'),
            esc_html__('Security', 'e-leads-for-woocommerce')
        );

        return $links;
    }
}
