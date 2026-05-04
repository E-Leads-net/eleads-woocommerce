<?php

declare(strict_types=1);

namespace Eleads\WooCommerce\Feed;

final class Language
{
    /**
     * @return array<string, string>
     */
    public function supported(): array
    {
        $polylang_languages = $this->polylang_languages();
        if ($polylang_languages !== []) {
            return $polylang_languages;
        }

        $wpml_languages = $this->wpml_languages();
        if ($wpml_languages !== []) {
            return $wpml_languages;
        }

        $locale = get_locale();
        $code = strtolower(substr($locale, 0, 2));

        return [
            $this->normalize_code($code !== '' ? $code : 'en') => $locale !== '' ? $locale : 'English',
        ];
    }

    public function normalize(string $language): string
    {
        if (trim($language) === '') {
            return $this->default();
        }

        $language = $this->normalize_code($language);

        if (array_key_exists($language, $this->supported())) {
            return $language;
        }

        $first_language = array_key_first($this->supported());

        return is_string($first_language) ? $first_language : 'en';
    }

    public function default(): string
    {
        if (function_exists('pll_default_language')) {
            return $this->normalize_code((string) pll_default_language('slug'));
        }

        if (has_filter('wpml_default_language')) {
            return $this->normalize_code((string) apply_filters('wpml_default_language', null));
        }

        $first_language = array_key_first($this->supported());

        return is_string($first_language) ? $first_language : 'en';
    }

    public function label(string $language): string
    {
        return $this->normalize($language);
    }

    public function switch_to(string $language): ?string
    {
        $language = $this->normalize($language);

        if (function_exists('pll_current_language') && function_exists('pll_switch_language')) {
            $previous = (string) pll_current_language('slug');
            pll_switch_language($language);

            return $previous;
        }

        if (has_action('wpml_switch_language')) {
            $previous = apply_filters('wpml_current_language', null);
            do_action('wpml_switch_language', $language);

            return is_string($previous) ? $previous : null;
        }

        return null;
    }

    public function restore(?string $language): void
    {
        if ($language === null || $language === '') {
            return;
        }

        if (function_exists('pll_switch_language')) {
            pll_switch_language($language);
            return;
        }

        if (has_action('wpml_switch_language')) {
            do_action('wpml_switch_language', $language);
        }
    }

    private function normalize_code(string $language): string
    {
        $language = strtolower(trim($language));
        $language = str_replace('_', '-', $language);
        $language = preg_replace('/[^a-z-]/', '', $language) ?: '';

        if (str_starts_with($language, 'ua')) {
            return 'uk';
        }

        if (str_contains($language, '-')) {
            $language = substr($language, 0, 2);
        }

        return $language !== '' ? $language : 'en';
    }

    /**
     * @return array<string, string>
     */
    private function polylang_languages(): array
    {
        if (! function_exists('pll_the_languages')) {
            return [];
        }

        $languages = pll_the_languages([
            'raw'           => 1,
            'hide_if_empty' => 0,
        ]);

        if (! is_array($languages)) {
            return [];
        }

        $result = [];
        foreach ($languages as $language) {
            if (! is_array($language)) {
                continue;
            }

            $code = $this->normalize_code((string) ($language['slug'] ?? $language['locale'] ?? ''));
            $name = (string) ($language['name'] ?? $language['slug'] ?? $code);
            $result[$code] = $name;
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function wpml_languages(): array
    {
        if (! has_filter('wpml_active_languages')) {
            return [];
        }

        $languages = apply_filters('wpml_active_languages', null, [
            'skip_missing' => 0,
        ]);

        if (! is_array($languages)) {
            return [];
        }

        $result = [];
        foreach ($languages as $language) {
            if (! is_array($language)) {
                continue;
            }

            $code = $this->normalize_code((string) ($language['language_code'] ?? $language['code'] ?? ''));
            $name = (string) ($language['native_name'] ?? $language['translated_name'] ?? $code);
            $result[$code] = $name;
        }

        return $result;
    }
}
