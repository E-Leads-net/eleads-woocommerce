<?php
/**
 * Plugin Name: E-Leads WooCommerce
 * Plugin URI: https://e-leads.com.ua
 * Description: WooCommerce export and synchronization module for E-Leads.
 * Version: 0.1.0
 * Author: E-Leads
 * Text Domain: eleads-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 *
 * @package EleadsWooCommerce
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('ELEADS_WOOCOMMERCE_FILE', __FILE__);
define('ELEADS_WOOCOMMERCE_PATH', plugin_dir_path(__FILE__));
define('ELEADS_WOOCOMMERCE_URL', plugin_dir_url(__FILE__));
define('ELEADS_WOOCOMMERCE_VERSION', '0.1.0');

require_once ELEADS_WOOCOMMERCE_PATH . 'src/Autoloader.php';

\Eleads\WooCommerce\Autoloader::register();

register_activation_hook(__FILE__, static function (): void {
    \Eleads\WooCommerce\Feed\Endpoint::register_rewrite_rules();
    \Eleads\WooCommerce\Api\PublicEndpoint::register_rewrite_rules();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});

add_action('plugins_loaded', static function (): void {
    \Eleads\WooCommerce\Plugin::instance()->boot();
});
