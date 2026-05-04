# E-Leads — Plugin for WordPress/WooCommerce

## Overview
E-Leads adds a product export feed (YML/XML), optional product synchronization, SEO pages, and optional widget loading for WooCommerce stores.

The plugin provides:
- A configurable export feed: categories, attributes, options, shop info, images, descriptions.
- A public feed URL for each supported language.
- Incremental feed generation with admin progress and public API polling.
- Optional synchronization of product create/update/delete events to E-Leads via API.
- An API Key gate that hides export/SEO settings until a valid key is provided.
- Optional SEO pages under `/e-search/{slug}` with sitemap support.
- Optional widget script loading on the storefront.

## Compatibility
- WordPress: 6.0+
- WooCommerce: 7.0+
- PHP: 8.0+
- Multilingual support:
  - Polylang, when available
  - WPML, when available
  - otherwise the current WordPress locale is used

## Installation
1. In WordPress admin, go to **Plugins -> Add New -> Upload Plugin**.
2. Upload release archive: `eleads-woocommerce.zip`.
3. Activate **E-Leads WooCommerce**.
4. Open **E-Leads** in the admin menu.
5. Enter and save a valid E-Leads API key in the **API Key** tab.

The plugin does not boot its main functionality when WooCommerce is missing. If WooCommerce is not active, the plugin shows an admin warning and keeps the module inactive.

## Feed URL
The feed is available at:

```text
/eleads-yml/{lang}.xml
```

Examples:
- `/eleads-yml/en.xml`
- `/eleads-yml/ru.xml`
- `/eleads-yml/uk.xml`

If an access key is configured in export settings:

```text
/eleads-yml/uk.xml?key=YOUR_FEED_ACCESS_KEY
```

When pretty permalinks are disabled, WordPress query-string fallback is used internally:

```text
/?eleads_yml_lang=uk
```

Pretty permalinks are recommended and required for SEO page routes.

## Feed Generation Workflow
Feed generation is not performed synchronously inside the public feed request.

Current behavior:
- `GET /eleads-yml/{lang}.xml` serves only an already generated XML file.
- Feed generation is started explicitly:
  - from the **Generate** button in plugin admin
  - or from the public plugin API endpoint
- Generation is incremental:
  - one `status` request processes one products batch
  - the client repeats polling until the feed becomes `ready`

This design avoids:
- PHP memory exhaustion on large catalogs
- server/proxy timeouts on long-running feed requests
- partially generated XML being returned to integrations

### Internal Feed Files
For every language the plugin stores:
- final file: `feed-{lang}.xml`
- temp file during generation: `feed-{lang}.tmp.xml`
- job metadata: `feed-{lang}.json`

These files are stored under WordPress uploads:

```text
wp-content/uploads/eleads/feeds/
```

### Feed Job States
Possible feed generation states:
- `idle` — no generated file and no active job
- `running` — generation is in progress
- `ready` — feed file is fully generated and can be downloaded
- `failed` — generation stopped with an error

### Admin Button Behavior
In **Export Settings -> Feed URL**, each feed row has a **Generate** button.

The admin UI uses the same generation engine as external integrations:
1. Start generation for selected language.
2. Process batches until status becomes `ready`.
3. Enable the existing public feed URL for download.

Successful admin-side generation is also a valid end-to-end test of the feed generation flow.

## Feed Generation API
All API endpoints require:

```http
Authorization: Bearer <API_KEY>
Accept: application/json
```

The token must match the saved E-Leads API key in plugin settings.

### 1. Start Feed Generation
Starts or reuses an incremental feed generation job for a target language.

Endpoint:

```http
POST /eleads-yml/api/generate?lang=uk
Authorization: Bearer <API_KEY>
Accept: application/json
```

Language input:
- query parameter: `?lang=uk`
- or form/JSON body:

```json
{"lang":"uk"}
```

If language is missing, the plugin uses the default language.

Successful response:

```json
{
  "status": "accepted",
  "lang": "uk",
  "job": {
    "status": "running",
    "lang": "uk",
    "language": "uk",
    "processed": 0,
    "total": 1248,
    "offers": 0,
    "batch_size": 300,
    "last_product_id": 0,
    "updated_at": "2026-05-05 12:00:00",
    "finished_at": "",
    "size": 0,
    "error": ""
  }
}
```

Curl example:

```bash
curl -X POST "https://example.com/eleads-yml/api/generate?lang=uk" \
  -H "Authorization: Bearer <API_KEY>" \
  -H "Accept: application/json"
```

### 2. Feed Generation Status
Reads current feed status and advances generation by one batch when the job is running.

Endpoint:

```http
GET /eleads-yml/api/status?lang=uk
Authorization: Bearer <API_KEY>
Accept: application/json
```

Behavior:
- if feed is `ready`, returns `ready`
- if feed is `failed`, returns `failed`
- if feed is `running`, this request processes the next batch of products
- when the final batch is processed, the plugin writes closing XML tags and atomically replaces the final feed file

Running response example:

```json
{
  "status": "running",
  "lang": "uk",
  "language": "uk",
  "processed": 300,
  "total": 1248,
  "offers": 300,
  "batch_size": 300,
  "last_product_id": 531,
  "updated_at": "2026-05-05 12:00:15",
  "finished_at": "",
  "size": 0,
  "error": ""
}
```

Ready response example:

```json
{
  "status": "ready",
  "lang": "uk",
  "language": "uk",
  "processed": 1248,
  "total": 1248,
  "offers": 1248,
  "batch_size": 300,
  "last_product_id": 2104,
  "updated_at": "2026-05-05 12:01:03",
  "finished_at": "2026-05-05 12:01:03",
  "size": 5821943,
  "error": ""
}
```

Failed response example:

```json
{
  "status": "failed",
  "lang": "uk",
  "language": "uk",
  "processed": 700,
  "total": 1248,
  "offers": 700,
  "batch_size": 300,
  "last_product_id": 1031,
  "updated_at": "2026-05-05 12:00:40",
  "finished_at": "2026-05-05 12:00:40",
  "size": 0,
  "error": "write_failed"
}
```

Curl example:

```bash
curl -X GET "https://example.com/eleads-yml/api/status?lang=uk" \
  -H "Authorization: Bearer <API_KEY>" \
  -H "Accept: application/json"
```

### 3. Download Ready Feed
The public feed URL serves only a ready XML file.

Endpoint:

```http
GET /eleads-yml/{lang}.xml
```

Rules:
- if feed access key is configured, append `?key=<access_key>`
- if the feed file does not exist yet, the endpoint returns `404`
- if the feed access key is missing or invalid, the endpoint returns `401`
- the endpoint does not start generation

Example:

```bash
curl -L "https://example.com/eleads-yml/uk.xml?key=<FEED_ACCESS_KEY>"
```

### 4. Feeds Endpoint
Returns all available feed URLs in `language -> feed_url` format.

Endpoint:

```http
GET /eleads-yml/api/feeds
Authorization: Bearer <API_KEY>
Accept: application/json
```

Success response example:

```json
{
  "status": "ok",
  "count": 3,
  "items": {
    "uk": "https://example.com/eleads-yml/uk.xml?key=abc",
    "ru": "https://example.com/eleads-yml/ru.xml?key=abc",
    "en": "https://example.com/eleads-yml/en.xml?key=abc"
  }
}
```

Curl example:

```bash
curl -X GET "https://example.com/eleads-yml/api/feeds" \
  -H "Authorization: Bearer <API_KEY>" \
  -H "Accept: application/json"
```

### Error Responses
Common API errors:
- `401`: `{"error":"api_key_missing"}` or `{"error":"unauthorized"}`
- `405`: `{"error":"method_not_allowed"}`
- `404`: `{"error":"not_found"}`
- `500`: `{"error":"sitemap_update_failed"}` or internal generation failure

## Recommended Integration Flow
This is the intended algorithm for external projects that need a fresh feed before download.

### Step-by-step
1. Start generation:

```bash
curl -X POST "https://example.com/eleads-yml/api/generate?lang=uk" \
  -H "Authorization: Bearer <API_KEY>" \
  -H "Accept: application/json"
```

2. Poll status every `1-3` seconds:

```bash
curl -X GET "https://example.com/eleads-yml/api/status?lang=uk" \
  -H "Authorization: Bearer <API_KEY>" \
  -H "Accept: application/json"
```

3. While response is:
- `running` -> continue polling
- `failed` -> stop and handle error
- `ready` -> download the final feed file

4. Download final feed:

```bash
curl -L "https://example.com/eleads-yml/uk.xml?key=<FEED_ACCESS_KEY>"
```

### Pseudocode
```text
POST /eleads-yml/api/generate?lang=uk
repeat:
  GET /eleads-yml/api/status?lang=uk
  if status == ready:
    GET /eleads-yml/uk.xml
    stop
  if status == failed:
    stop with error
  sleep 2 seconds
```

### Why the Protocol Works This Way
- no cron is required
- no background worker process is required
- each status poll advances generation by one safe batch
- large feeds can be built on shared hosting without long blocking requests
- final feed consumers still download a normal XML file from the usual URL

## SEO Pages
SEO pages require:
- a valid E-Leads API key
- SEO pages permission enabled for the project in E-Leads
- WordPress pretty permalinks
- **SEO** enabled in the plugin settings

### Sitemap
- URL: `/e-search/sitemap.xml`
- Generated virtually by the plugin.
- Contains links in the form:
  - default language: `https://your-site.com/e-search/{slug}`
  - non-default language: `https://your-site.com/{lang}/e-search/{slug}`

### SEO Page Route
- URL: `/e-search/{slug}` and `/{lang}/e-search/{slug}`
- The plugin requests page data from the E-Leads API and renders products through WooCommerce loop templates.
- Products on SEO pages can be opened and added to cart using the active WooCommerce theme behavior.
- Canonical and alternate links are generated from API data.
- Invalid/missing alternates are filtered to avoid links to untranslated 404 pages.

### Sitemap Sync Endpoint
The plugin exposes a protected endpoint to keep the sitemap in sync with external updates:

```http
POST /e-search/api/sitemap-sync
Authorization: Bearer <API_KEY>
Content-Type: application/json
```

Optional query parameter:
- `?lang=<language>`

Payload examples:

```json
{"action":"create","slug":"komp-belyy"}
{"action":"delete","slug":"komp-belyy"}
{"action":"update","slug":"old-slug","new_slug":"new-slug"}
{"action":"create","slug":"komp-belyy","lang":"uk"}
{"action":"delete","slug":"komp-belyy","language":"ru"}
{"action":"update","slug":"old-slug","new_slug":"new-slug","lang":"uk","new_lang":"ru"}
```

Rules:
- `action` is required: `create` | `update` | `delete`
- `slug` is required for all actions
- `new_slug` is required for `update`
- source language can be passed as `lang` or `language`
- target language for `update` can be passed as `new_lang` or `new_language`
- if `?lang=` is provided, it has priority over payload language
- `Authorization` must match the plugin API key

Success response:

```json
{"status":"ok","url":"https://example.com/e-search/komp-belyy"}
```

Error responses:
- `401`: `{"error":"unauthorized"}` or `{"error":"api_key_missing"}`
- `405`: `{"error":"method_not_allowed"}`
- `400`: `{"error":"invalid_payload"}` or `{"error":"invalid_action"}`
- `500`: `{"error":"sitemap_update_failed"}`

### Languages Endpoint
Returns enabled/available store languages for integrations:

```http
GET /e-search/api/languages
Authorization: Bearer <API_KEY>
Accept: application/json
```

Success response:

```json
{
  "status": "ok",
  "count": 3,
  "items": [
    {
      "id": 1,
      "label": "uk",
      "code": "uk",
      "href_lang": "uk",
      "enabled": true,
      "name": "Українська"
    }
  ]
}
```

Errors:
- `401`: `{"error":"unauthorized"}` or `{"error":"api_key_missing"}`
- `405`: `{"error":"method_not_allowed"}`

## Product Sync Behavior
Product synchronization is optional and disabled by default.

When enabled:
- product create sends `POST` to E-Leads ecommerce items API
- product update sends `PUT` to the matching E-Leads ecommerce item
- product delete/trash sends `DELETE` to the matching E-Leads ecommerce item
- variation updates trigger sync for the parent product
- sync does nothing when the API key is invalid or sync is disabled

Product payloads are built from WooCommerce product data and plugin export settings.

## Admin Tabs
### 1. Export Settings
- **Feed URL** per language: generate, copy, download, progress status.
- **Synchronization** toggle: enables/disables API sync of product changes.
- **E-Leads Widget** toggle: enables/disables public widget script loading.
- **Categories and subcategories**: only selected categories are exported.
- **Attribute filters** (optional): selected attributes are marked with `filter="true"`.
- **Option filters** (optional): selected options are marked with `filter="true"`.
- **Group products**:
  - enabled: one `<offer>` per product, variations/options are aggregated
  - disabled: variations can be exported as separate offers
- **Image size**: choose any registered WordPress image size, including `thumbnail`, WooCommerce sizes, or `full`.
- **Shop name / Email / Shop URL / Currency**: used in `<shop>`.
- **Picture limit**: max number of `<picture>` tags per offer.
- **Short description source**: defines which field is used for `<short_description>`.

### 2. SEO
- Enable/disable E-Leads SEO pages.
- Shows sitemap URL.
- Allows copying and opening `/e-search/sitemap.xml`.

The SEO tab is shown only when the API key is valid and SEO pages are allowed for the project.

### 3. API Key
- Enter and validate the E-Leads API key.
- Without a valid key, export and SEO settings are hidden.
- The key can be obtained in the E-Leads dashboard.

There is no built-in plugin update tab. Plugin updates are expected to be handled through the normal WordPress plugin update flow.

## Feed Structure (Excerpt)
```xml
<yml_catalog date="YYYY-MM-DD HH:MM">
  <shop>
    <shopName>...</shopName>
    <email>...</email>
    <url>...</url>
    <language>...</language>
    <categories>
      <category id="..." parentId="..." position="..." url="...">...</category>
    </categories>
    <offers>
      <offer id="..." group_id="..." available="true|false">
        <url>...</url>
        <name>...</name>
        <price>...</price>
        <old_price>...</old_price>
        <currency>...</currency>
        <categoryId>...</categoryId>
        <quantity>...</quantity>
        <stock_status>...</stock_status>
        <picture>...</picture>
        <vendor>...</vendor>
        <sku>...</sku>
        <label/>
        <order>...</order>
        <description>...</description>
        <short_description>...</short_description>
        <param name="...">...</param>
        <param filter="true" name="...">...</param>
      </offer>
    </offers>
  </shop>
</yml_catalog>
```

## Widget Loader
The widget is disabled by default.

When enabled by the store owner, public pages load:

```text
https://api.e-leads.net/v1/widgets-loader.js
```

The script is added through WordPress `wp_footer`. The plugin does not modify theme files.

## External Services
This plugin connects to E-Leads services when the store owner enters an API key and enables the relevant features.

- API key validation, SEO page data, sitemap sync, feed discovery, and product synchronization use `https://dashboard.e-leads.net/`.
- The optional E-Leads widget loads its script from `https://api.e-leads.net/v1/widgets-loader.js`.

Service information and policies:

- Documentation: https://e-leads.net/docs/
- Website: https://e-leads.net/
- Security: https://e-leads.net/security/
- Cookie Policy: https://e-leads.net/cookie-policy/
- Terms of Service: https://e-leads.net/terms-of-service/
- Privacy Policy: https://e-leads.net/privacy-policy/

## Plugin Structure
```text
eleads-woocommerce/
├─ eleads-woocommerce.php
├─ readme.txt
├─ uninstall.php
├─ assets/
│  ├─ admin.css
│  └─ admin.js
├─ languages/
│  ├─ eleads-woocommerce.pot
│  ├─ eleads-woocommerce-en_US.po/.mo
│  ├─ eleads-woocommerce-ru_RU.po/.mo
│  └─ eleads-woocommerce-uk_UA.po/.mo
├─ src/
│  ├─ Admin/
│  ├─ Api/
│  ├─ Catalog/
│  ├─ Dependencies/
│  ├─ Feed/
│  ├─ Seo/
│  ├─ Settings/
│  ├─ Sync/
│  ├─ Widgets/
│  ├─ Autoloader.php
│  └─ Plugin.php
└─ views/
   └─ admin/
      ├─ page.php
      ├─ tabs/
      └─ partials/
```

## Repository & Release
- Repository: `https://github.com/E-Leads-net/eleads-woocommerce`
- Release archive: `eleads-woocommerce.zip`

## Notes for WordPress.org Review
- The plugin does not modify WordPress, WooCommerce, or theme files.
- The optional widget script is loaded only after explicit store-owner opt-in.
- External services are documented in `readme.txt` and this README.
- Feed files are stored in WordPress uploads under `wp-content/uploads/eleads/feeds/`.
- WooCommerce dependency is checked before plugin functionality boots.
- Plugin settings are hidden until a valid API key is provided.
