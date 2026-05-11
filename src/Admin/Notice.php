<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Notice
{
    public static function missing_woocommerce(): void
    {
        if (! current_user_can('activate_plugins') || ! self::is_plugins_screen()) {
            return;
        }

        $message = sprintf(
            /* translators: %s: WooCommerce plugin name. */
            __('E-Leads for WooCommerce requires %s to be installed and active. The module is currently disabled.', 'e-leads-for-woocommerce'),
            '<strong>WooCommerce</strong>'
        );

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            wp_kses_post($message)
        );
    }

    public static function plain_permalinks(): void
    {
        if (! current_user_can('manage_options') || get_option('permalink_structure') !== '' || ! self::is_plugin_settings_screen()) {
            return;
        }

        $settings_url = admin_url('options-permalink.php');
        $message = sprintf(
            /* translators: %s: permalinks settings URL. */
            __('API endpoint-и E-Leads for WooCommerce потребують красивих постійних посилань. Увімкніть їх у <a href="%s">налаштуваннях постійних посилань</a>.', 'e-leads-for-woocommerce'),
            esc_url($settings_url)
        );

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            wp_kses_post($message)
        );
    }

    private static function is_plugins_screen(): bool
    {
        if (! function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return is_object($screen) && isset($screen->id) && $screen->id === 'plugins';
    }

    private static function is_plugin_settings_screen(): bool
    {
        if (! function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return is_object($screen) && isset($screen->id) && $screen->id === 'toplevel_page_e-leads-for-woocommerce';
    }
}
