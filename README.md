# E-Leads WooCommerce

WooCommerce plugin for E-Leads product export, synchronization and feed settings.

## External Services

This plugin connects to E-Leads services when the store owner enters an API key and enables the relevant features.

- API key validation, feed discovery, product synchronization, and SEO/sitemap sync use `https://dashboard.e-leads.net/`.
- The optional E-Leads widget is disabled by default. When enabled by the store owner, public pages load the widget script from `https://api.e-leads.net/v1/widgets-loader.js`.

Service information and policies:

- Documentation: https://e-leads.net/docs/
- Website: https://e-leads.net/
- Security: https://e-leads.net/security/
- Cookie Policy: https://e-leads.net/cookie-policy/
- Terms of Service: https://e-leads.net/terms-of-service/
- Privacy Policy: https://e-leads.net/privacy-policy/

## Planned Architecture

- `eleads-woocommerce.php` - plugin entrypoint.
- `src/Plugin.php` - main application bootstrap.
- `src/Autoloader.php` - lightweight class autoloader.
- `src/Dependencies/` - dependency checks such as WooCommerce availability.
- `src/Admin/` - admin menu, notices, pages and settings screens.
- `src/Settings/` - options model and validation.
- `src/Feed/` - feed query, generation, storage and download endpoints.
- `src/Sync/` - WooCommerce product create, update and delete synchronization.

The plugin does not boot its main functionality when WooCommerce is missing.
