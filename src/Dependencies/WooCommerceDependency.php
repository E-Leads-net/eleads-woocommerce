<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Dependencies;

final class WooCommerceDependency
{
    public function is_active(): bool
    {
        return class_exists('WooCommerce');
    }
}
