<?php
/**
 * Uninstall cleanup for E-Leads for WooCommerce.
 *
 * @package EleadsWooCommerce
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Uninstall runs in the global scope by WordPress design.

delete_option('eleads_woocommerce_settings');
delete_option('eleads_woocommerce_seo_sitemap_slugs');
delete_option('eleads_woocommerce_rewrite_version');

$eleads_upload_dir = wp_upload_dir();
$eleads_directory = trailingslashit((string) $eleads_upload_dir['basedir']) . 'eleads/feeds/';

if (is_dir($eleads_directory)) {
    foreach (glob($eleads_directory . 'feed-*.{xml,json}', GLOB_BRACE) ?: [] as $eleads_file) {
        if (is_file($eleads_file)) {
            wp_delete_file($eleads_file);
        }
    }
}
