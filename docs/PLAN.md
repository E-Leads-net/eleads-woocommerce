# E-Leads WooCommerce Plan

## Goal

Build an OOP WordPress plugin for WooCommerce product export, feed generation and E-Leads integration.

## Step 1: Plugin Foundation

- Main plugin entrypoint.
- Lightweight autoloader.
- Main `Plugin` bootstrap class.
- WooCommerce dependency check.
- Admin notice when WooCommerce is not installed or inactive.

Status: done.

## Step 2: Admin Area Shell

- Add `E-Leads` admin menu page.
- Add tabs:
  - Export settings
  - API Key
- Add page controller classes.
- Add isolated view templates.
- Add admin CSS/JS entrypoints.

Status: in progress. SEO and update tabs are intentionally excluded for now.

## Step 3: Settings Layer

- Create typed settings repository.
- Store settings with WordPress Options API.
- Add nonce verification.
- Add capability checks.
- Add sanitization and validation classes.
- Define default values.

## Step 4: Export Settings

- Product category tree selection.
- Select all / unselect all actions.
- Attribute filter toggle.
- Option filter toggle.
- Product grouping toggle.
- Image size selector.
- Access key field.
- Store name, email, store URL, currency and image limit fields.
- Short description source selector.

## Step 5: Feed Generation

- Product query service.
- Product mapper.
- Feed XML builder.
- Feed file writer.
- Feed status tracker.
- Manual feed regeneration action.
- Separate feeds for supported languages, for example `ua` and `ru`.

## Step 6: Feed Endpoints

- Public feed URLs.
- Optional `key` parameter protection.
- Download action.
- HTTP headers for XML/file responses.
- Permission-safe error responses.

## Step 7: Synchronization

- Enable/disable synchronization.
- WP-Cron schedule.
- Background regeneration.
- Admin status messages.

## Step 8: SEO

- SEO settings tab.
- Product/category SEO mapping rules.
- Feed-specific SEO fields if needed by E-Leads format.

## Step 9: Updates

- Version display.
- Update status tab.
- Changelog or remote version check if E-Leads provides an update API.

## Step 10: QA

- Activation/deactivation checks.
- Behavior without WooCommerce.
- Behavior with WooCommerce active.
- Feed output validation.
- Basic PHP linting.
