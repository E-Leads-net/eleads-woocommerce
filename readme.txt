=== E-Leads WooCommerce ===
Contributors: eleads
Tags: woocommerce, product feed, ecommerce, synchronization, seo
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export WooCommerce products to E-Leads, synchronize catalog updates, and serve E-Leads SEO pages.

== Description ==

E-Leads WooCommerce connects a WooCommerce store to the E-Leads service. The plugin can generate product feeds, expose authenticated feed endpoints for the E-Leads dashboard, synchronize WooCommerce product changes, and render E-Leads SEO pages when enabled for the connected API key.

The plugin requires WooCommerce. If WooCommerce is not active, the plugin does not boot its main functionality and shows an admin notice.

= External services =

This plugin connects to E-Leads services only when the store owner enters an E-Leads API key or explicitly enables the optional widget feature.

The plugin sends requests to `https://dashboard.e-leads.net/` for:

* API key validation.
* Product synchronization.
* Feed discovery from the E-Leads dashboard.
* SEO sitemap and SEO page data when SEO pages are enabled.

When the optional E-Leads widget setting is enabled, public site pages load the widget script from `https://api.e-leads.net/v1/widgets-loader.js`. This setting is disabled by default and must be enabled by a store administrator.

Service documentation and policies:

* Documentation: https://e-leads.net/docs/
* Website: https://e-leads.net/
* Security: https://e-leads.net/security/
* Cookie Policy: https://e-leads.net/cookie-policy/
* Terms of Service: https://e-leads.net/terms-of-service/
* Privacy Policy: https://e-leads.net/privacy-policy/

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/eleads-woocommerce/` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Make sure WooCommerce is installed and active.
4. Go to E-Leads in the WordPress admin menu and enter your E-Leads API key.
5. Configure feed, synchronization, widget, and SEO settings as needed.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. The plugin is built for WooCommerce stores and does not run its main functionality without WooCommerce.

= Does the plugin contact external services automatically? =

The plugin contacts the E-Leads dashboard after an administrator enters and saves an API key. The optional public widget script is loaded only after an administrator enables the widget setting.

= Where are generated feeds stored? =

Generated feed files are stored in the WordPress uploads directory under `eleads/feeds/`.

== Changelog ==

= 0.2.0 =
* Added product feed generation with progress status.
* Added authenticated dashboard endpoints.
* Added product synchronization hooks.
* Added optional E-Leads widget loading with admin opt-in.
* Added E-Leads SEO pages and sitemap endpoint.
* Added localization files.

== Upgrade Notice ==

= 0.2.0 =
Initial WordPress.org-ready release.
