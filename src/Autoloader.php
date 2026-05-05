<?php

declare(strict_types=1);

namespace Eleads\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const PREFIX = 'Eleads\\WooCommerce\\';

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    private static function load(string $class): void
    {
        if (! str_starts_with($class, self::PREFIX)) {
            return;
        }

        $relative_class = substr($class, strlen(self::PREFIX));
        $relative_path  = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
        $file           = ELEADS_WOOCOMMERCE_PATH . 'src/' . $relative_path;

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
