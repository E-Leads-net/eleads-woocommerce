# E-Leads WooCommerce

WooCommerce plugin for E-Leads product export, synchronization and feed settings.

## Planned Architecture

- `eleads-woocommerce.php` - plugin entrypoint.
- `src/Plugin.php` - main application bootstrap.
- `src/Autoloader.php` - lightweight class autoloader.
- `src/Dependencies/` - dependency checks such as WooCommerce availability.
- `src/Admin/` - admin menu, notices, pages and settings screens.
- `src/Settings/` - options model and validation.
- `src/Feed/` - feed query, generation, storage and download endpoints.
- `src/Cron/` - scheduled synchronization.

The plugin does not boot its main functionality when WooCommerce is missing.
