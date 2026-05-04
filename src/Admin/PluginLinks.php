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
}
