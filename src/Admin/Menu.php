<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Menu
{
    private Page $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    public function register(): void
    {
        add_menu_page(
            __('E-Leads', 'e-leads-for-woocommerce'),
            __('E-Leads', 'e-leads-for-woocommerce'),
            'manage_woocommerce',
            'e-leads-for-woocommerce',
            [$this->page, 'render'],
            'dashicons-chart-line',
            56
        );
    }
}
