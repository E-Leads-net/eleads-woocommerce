<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

if (! defined('ABSPATH')) {
    exit;
}

final class StatusRepository
{
    private PathResolver $paths;

    public function __construct(PathResolver $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $language): array
    {
        $status_path = $this->paths->status_path($language);

        if (is_file($status_path)) {
            $status = json_decode((string) file_get_contents($status_path), true);
            if (is_array($status)) {
                return $this->normalize($language, $status);
            }
        }

        $feed_path = $this->paths->final_path($language);
        if (is_file($feed_path)) {
            return $this->normalize($language, [
                'status'      => 'ready',
                'language'    => $language,
                'updated_at'  => gmdate('Y-m-d H:i:s', (int) filemtime($feed_path)),
                'finished_at' => gmdate('Y-m-d H:i:s', (int) filemtime($feed_path)),
                'size'        => (int) filesize($feed_path),
                'offers'      => 0,
                'error'       => '',
            ]);
        }

        return $this->normalize($language, ['status' => 'idle', 'language' => $language]);
    }

    public function running(string $language, int $total = 0, int $batch_size = 300): void
    {
        $this->write($language, [
            'status'      => 'running',
            'language'    => $language,
            'updated_at'  => gmdate('Y-m-d H:i:s'),
            'finished_at' => '',
            'size'        => 0,
            'offers'      => 0,
            'processed'   => 0,
            'total'       => $total,
            'batch_size'  => $batch_size,
            'last_product_id' => 0,
            'error'       => '',
        ]);
    }

    public function progress(string $language, int $processed, int $total, int $last_product_id, int $offers): void
    {
        $current = $this->get($language);
        $this->write($language, array_merge($current, [
            'status'          => 'running',
            'updated_at'      => gmdate('Y-m-d H:i:s'),
            'finished_at'     => '',
            'processed'       => $processed,
            'total'           => $total,
            'last_product_id' => $last_product_id,
            'offers'          => $offers,
            'size'            => 0,
            'error'           => '',
        ]));
    }

    public function ready(string $language, int $size, int $offers): void
    {
        $this->write($language, [
            'status'      => 'ready',
            'language'    => $language,
            'updated_at'  => gmdate('Y-m-d H:i:s'),
            'finished_at' => gmdate('Y-m-d H:i:s'),
            'size'        => $size,
            'offers'      => $offers,
            'processed'   => (int) ($this->get($language)['processed'] ?? 0),
            'total'       => (int) ($this->get($language)['total'] ?? 0),
            'batch_size'  => (int) ($this->get($language)['batch_size'] ?? 300),
            'last_product_id' => (int) ($this->get($language)['last_product_id'] ?? 0),
            'error'       => '',
        ]);
    }

    public function failed(string $language, string $error): void
    {
        $this->write($language, [
            'status'      => 'failed',
            'language'    => $language,
            'updated_at'  => gmdate('Y-m-d H:i:s'),
            'finished_at' => gmdate('Y-m-d H:i:s'),
            'size'        => 0,
            'offers'      => 0,
            'processed'   => (int) ($this->get($language)['processed'] ?? 0),
            'total'       => (int) ($this->get($language)['total'] ?? 0),
            'batch_size'  => (int) ($this->get($language)['batch_size'] ?? 300),
            'last_product_id' => (int) ($this->get($language)['last_product_id'] ?? 0),
            'error'       => $error,
        ]);
    }

    /**
     * @param array<string, mixed> $status
     */
    private function write(string $language, array $status): void
    {
        $path = $this->paths->status_path($language);
        $tmp = $path . '.tmp';
        file_put_contents($tmp, wp_json_encode($this->normalize($language, $status), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        $this->move($tmp, $path);
    }

    /**
     * @param array<string, mixed> $status
     * @return array<string, mixed>
     */
    private function normalize(string $language, array $status): array
    {
        return [
            'status'      => (string) ($status['status'] ?? 'idle'),
            'language'    => (string) ($status['language'] ?? $language),
            'updated_at'  => (string) ($status['updated_at'] ?? ''),
            'finished_at' => (string) ($status['finished_at'] ?? ''),
            'size'        => (int) ($status['size'] ?? 0),
            'offers'      => (int) ($status['offers'] ?? 0),
            'processed'   => (int) ($status['processed'] ?? 0),
            'total'       => (int) ($status['total'] ?? 0),
            'batch_size'  => (int) ($status['batch_size'] ?? 300),
            'last_product_id' => (int) ($status['last_product_id'] ?? 0),
            'error'       => (string) ($status['error'] ?? ''),
        ];
    }

    private function move(string $source, string $destination): void
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();

        global $wp_filesystem;

        if ($wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->move($source, $destination, true)) {
            return;
        }

        throw new \RuntimeException('move_failed');
    }
}
