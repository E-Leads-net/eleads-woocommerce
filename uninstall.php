<?php
/**
 * Uninstall cleanup for E-Leads WooCommerce.
 *
 * @package EleadsWooCommerce
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('eleads_woocommerce_settings');
delete_option('eleads_woocommerce_seo_sitemap_slugs');
delete_option('eleads_woocommerce_rewrite_version');

$upload_dir = wp_upload_dir();
$directory = trailingslashit((string) $upload_dir['basedir']) . 'eleads/feeds/';

if (is_dir($directory)) {
    foreach (glob($directory . 'feed-*.{xml,json}', GLOB_BRACE) ?: [] as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
    }
}
