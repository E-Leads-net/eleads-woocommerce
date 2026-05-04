<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Admin;

use Eleads\WooCommerce\Feed\Generator;
use Eleads\WooCommerce\Feed\Language;

final class FeedActionHandler
{
    private Generator $generator;

    private Language $language;

    public function __construct(Generator $generator, Language $language)
    {
        $this->generator = $generator;
        $this->language  = $language;
    }

    public function generate(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to generate feeds.', 'eleads-woocommerce'));
        }

        check_admin_referer('eleads_generate_feed');

        $language = $this->language->normalize(isset($_GET['lang']) ? sanitize_key((string) wp_unslash($_GET['lang'])) : 'uk');

        try {
            $this->generator->generate($language);
            $generated = '1';
        } catch (\Throwable $e) {
            $generated = '0';
        }

        wp_safe_redirect(add_query_arg([
            'page'              => 'eleads-woocommerce',
            'tab'               => 'export',
            'eleads_generated'  => $generated,
        ], admin_url('admin.php')));
        exit;
    }

    public function ajax_start(): void
    {
        $this->guard_ajax();
        $language = $this->request_language();

        try {
            wp_send_json_success($this->generator->start($language));
        } catch (\Throwable $e) {
            wp_send_json_error(['error' => 'generation_failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function ajax_process(): void
    {
        $this->guard_ajax();
        $language = $this->request_language();

        try {
            wp_send_json_success($this->generator->process_next_batch($language));
        } catch (\Throwable $e) {
            wp_send_json_error(['error' => 'generation_failed', 'message' => $e->getMessage()], 500);
        }
    }

    private function guard_ajax(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_send_json_error(['error' => 'forbidden'], 403);
        }

        check_ajax_referer('eleads_feed_generation', 'nonce');
    }

    private function request_language(): string
    {
        return $this->language->normalize(isset($_POST['language']) ? sanitize_key((string) wp_unslash($_POST['language'])) : '');
    }
}
