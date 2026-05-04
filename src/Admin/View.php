<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): void
    {
        $file = ELEADS_WOOCOMMERCE_PATH . 'views/' . $template . '.php';

        if (! is_readable($file)) {
            return;
        }

        extract($data, EXTR_SKIP);
        include $file;
    }
}
