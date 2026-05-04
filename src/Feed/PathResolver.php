<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

final class PathResolver
{
    public function directory(): string
    {
        $upload_dir = wp_upload_dir();
        $directory = trailingslashit((string) $upload_dir['basedir']) . 'eleads/feeds/';

        if (! is_dir($directory)) {
            wp_mkdir_p($directory);
        }

        return $directory;
    }

    public function final_path(string $language): string
    {
        return $this->directory() . 'feed-' . $this->sanitize_language($language) . '.xml';
    }

    public function temp_path(string $language): string
    {
        return $this->directory() . 'feed-' . $this->sanitize_language($language) . '.tmp.xml';
    }

    public function status_path(string $language): string
    {
        return $this->directory() . 'feed-' . $this->sanitize_language($language) . '.json';
    }

    private function sanitize_language(string $language): string
    {
        $language = preg_replace('/[^a-z]/', '', strtolower($language));

        return $language !== '' ? $language : 'uk';
    }
}
