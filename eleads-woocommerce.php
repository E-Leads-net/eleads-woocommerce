<?php
/**
 * Plugin Name: E-Leads WooCommerce
 * Plugin URI: https://e-leads.net/
 * Description: WooCommerce export and synchronization module for E-Leads.
 * Version: 0.2.0
 * Author: E-Leads
 * Author URI: https://e-leads.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: eleads-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
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
define('ELEADS_WOOCOMMERCE_VERSION', '0.2.0');

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
    load_plugin_textdomain(
        'eleads-woocommerce',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    \Eleads\WooCommerce\Plugin::instance()->boot();
});
