<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Dependencies;

if (! defined('ABSPATH')) {
    exit;
}

final class WooCommerceDependency
{
    public function is_active(): bool
    {
        return class_exists('WooCommerce');
    }
}
