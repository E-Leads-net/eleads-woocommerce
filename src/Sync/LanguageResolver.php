<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Sync;

if (! defined('ABSPATH')) {
    exit;
}

use Eleads\WooCommerce\Feed\Language;

final class LanguageResolver
{
    private Language $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function resolve_for_product(int $product_id): string
    {
        if (function_exists('pll_get_post_language')) {
            $language = pll_get_post_language($product_id, 'slug');
            if (is_string($language) && $language !== '') {
                return $this->normalize($language);
            }
        }

        $languages = $this->language->supported();
        $language = (string) array_key_first($languages);

        return $this->normalize($language);
    }

    public function default_language(): string
    {
        $languages = $this->language->supported();
        $language = (string) array_key_first($languages);

        return $this->normalize($language);
    }

    private function normalize(string $language): string
    {
        $language = strtolower(trim($language));

        return $language === 'ua' ? 'uk' : $language;
    }
}
