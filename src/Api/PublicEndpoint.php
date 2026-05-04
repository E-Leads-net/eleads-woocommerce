<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Api;

use Eleads\WooCommerce\Feed\Endpoint;
use Eleads\WooCommerce\Feed\Generator;
use Eleads\WooCommerce\Feed\Language;
use Eleads\WooCommerce\Feed\StatusRepository;
use Eleads\WooCommerce\Settings\SettingsRepository;

final class PublicEndpoint
{
    private SettingsRepository $settings;

    private Auth $auth;

    private Language $language;

    private Generator $generator;

    private StatusRepository $statuses;

    private Endpoint $feed_endpoint;

    private SeoSitemap $sitemap;

    public function __construct(
        SettingsRepository $settings,
        Auth $auth,
        Language $language,
        Generator $generator,
        StatusRepository $statuses,
        Endpoint $feed_endpoint,
        SeoSitemap $sitemap
    ) {
        $this->settings      = $settings;
        $this->auth          = $auth;
        $this->language      = $language;
        $this->generator     = $generator;
        $this->statuses      = $statuses;
        $this->feed_endpoint = $feed_endpoint;
        $this->sitemap       = $sitemap;
    }

    public static function register_rewrite_rules(): void
    {
        add_rewrite_rule('^eleads-yml/api/generate/?$', 'index.php?eleads_api=generate', 'top');
        add_rewrite_rule('^eleads-yml/api/status/?$', 'index.php?eleads_api=status', 'top');
        add_rewrite_rule('^eleads-yml/api/feeds/?$', 'index.php?eleads_api=feeds', 'top');
        add_rewrite_rule('^e-search/api/languages/?$', 'index.php?eleads_api=languages', 'top');
        add_rewrite_rule('^e-search/api/sitemap-sync/?$', 'index.php?eleads_api=sitemap-sync', 'top');
        add_rewrite_rule('^e-search/([0-9A-Za-z\-_]+)/?$', 'index.php?eleads_seo_slug=$matches[1]', 'top');
    }

    /**
     * @param array<int|string, string> $vars
     * @return array<int|string, string>
     */
    public function query_vars(array $vars): array
    {
        $vars[] = 'eleads_api';
        $vars[] = 'eleads_seo_slug';

        return $vars;
    }

    public function serve(): void
    {
        $api = (string) get_query_var('eleads_api');
        if ($api !== '') {
            $this->serve_api($api);
        }

        $slug = (string) get_query_var('eleads_seo_slug');
        if ($slug !== '') {
            $this->serve_seo_page($slug);
        }

        $path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        $path_map = [
            'eleads-yml/api/generate'   => 'generate',
            'eleads-yml/api/status'     => 'status',
            'eleads-yml/api/feeds'      => 'feeds',
            'e-search/api/languages'    => 'languages',
            'e-search/api/sitemap-sync' => 'sitemap-sync',
        ];

        if (isset($path_map[$path])) {
            $this->serve_api($path_map[$path]);
        }

        if (preg_match('#^e-search/([0-9A-Za-z\-_]+)$#', $path, $matches)) {
            $this->serve_seo_page((string) $matches[1]);
        }
    }

    private function serve_api(string $api): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($api === 'generate') {
            $this->require_method($method, 'POST');
            $this->require_auth();
            $payload = $this->payload();
            $language = $this->language->normalize((string) ($_GET['lang'] ?? $payload['lang'] ?? $payload['language'] ?? ''));
            $job = $this->generator->start($language);
            $this->json(['status' => 'accepted', 'lang' => $language, 'job' => $this->okay_job($job)]);
        }

        if ($api === 'status') {
            $this->require_method($method, 'GET');
            $this->require_auth();
            $language = $this->language->normalize((string) ($_GET['lang'] ?? ''));
            $state = $this->generator->process_next_batch($language);
            $this->json($this->okay_job($state));
        }

        if ($api === 'feeds') {
            $this->require_method($method, 'GET');
            $this->require_auth();
            $items = [];
            foreach ($this->language->supported() as $language => $label) {
                $items[$language] = $this->feed_endpoint->url($language);
            }
            $this->json(['status' => 'ok', 'count' => count($items), 'items' => $items]);
        }

        if ($api === 'languages') {
            $this->require_method($method, 'GET');
            $this->require_auth();
            $items = [];
            $id = 1;
            foreach ($this->language->supported() as $language => $label) {
                $items[] = [
                    'id'        => $id++,
                    'label'     => $language,
                    'code'      => $language,
                    'href_lang' => $language,
                    'enabled'   => true,
                    'name'      => $label,
                ];
            }
            $this->json(['status' => 'ok', 'count' => count($items), 'items' => $items]);
        }

        if ($api === 'sitemap-sync') {
            $this->require_method($method, 'POST');
            $this->require_auth();
            $payload = $this->payload();
            $action = sanitize_key((string) ($payload['action'] ?? ''));
            $slug = (string) ($payload['slug'] ?? '');
            $new_slug = (string) ($payload['new_slug'] ?? '');
            $language = sanitize_key((string) ($_GET['lang'] ?? $payload['lang'] ?? $payload['language'] ?? ''));
            $new_language = sanitize_key((string) ($payload['new_lang'] ?? $payload['new_language'] ?? $language));

            if ($action === '' || trim($slug) === '') {
                $this->json(['error' => 'invalid_payload'], 400);
            }

            $ok = match ($action) {
                'create' => $this->sitemap->add_slug($slug, $language),
                'delete' => $this->sitemap->remove_slug($slug, $language),
                'update' => trim($new_slug) !== '' && $this->sitemap->update_slug($slug, $new_slug, $language, $new_language),
                default => null,
            };

            if ($ok === null) {
                $this->json(['error' => 'invalid_action'], 400);
            }

            if (! $ok) {
                $this->json(['error' => 'sitemap_update_failed'], 500);
            }

            $this->json([
                'status' => 'ok',
                'url'    => $this->sitemap->slug_url($action === 'update' ? $new_slug : $slug, $action === 'update' ? $new_language : $language),
            ]);
        }

        $this->json(['error' => 'not_found'], 404);
    }

    private function serve_seo_page(string $slug): void
    {
        if (! (bool) $this->settings->get('seo_pages_enabled')) {
            status_header(404);
            return;
        }

        status_header(501);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'SEO pages are not implemented for WordPress yet.';
        exit;
    }

    private function require_method(string $actual, string $expected): void
    {
        if ($actual !== $expected) {
            $this->json(['error' => 'method_not_allowed'], 405);
        }
    }

    private function require_auth(): void
    {
        $error = $this->auth->validate();
        if ($error !== null) {
            $this->json(['error' => $error], 401);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $payload = $_POST;
        if ($payload !== []) {
            return is_array($payload) ? $payload : [];
        }

        $raw = file_get_contents('php://input');
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function okay_job(array $state): array
    {
        $language = (string) ($state['language'] ?? $state['lang'] ?? '');

        return [
            'status'          => (string) ($state['status'] ?? 'idle'),
            'lang'            => $language,
            'language'        => $language,
            'processed'       => (int) ($state['processed'] ?? 0),
            'total'           => (int) ($state['total'] ?? 0),
            'offers'          => (int) ($state['offers'] ?? 0),
            'batch_size'      => (int) ($state['batch_size'] ?? 300),
            'last_product_id' => (int) ($state['last_product_id'] ?? 0),
            'updated_at'      => (string) ($state['updated_at'] ?? ''),
            'finished_at'     => (string) ($state['finished_at'] ?? ''),
            'size'            => (int) ($state['size'] ?? 0),
            'error'           => (string) ($state['error'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): void
    {
        status_header($status);
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
