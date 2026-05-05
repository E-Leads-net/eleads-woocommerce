<?php
/**
 * SEO settings tab.
 *
 * @var array<string, mixed> $settings
 * @var string $seo_sitemap_url
 */

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this view file.

$is_available = ! empty($settings['api_key_valid']) && ! empty($settings['seo_pages_allowed']);
?>

<form class="eleads-form eleads-form--seo" method="post" action="<?php echo esc_url(admin_url('admin.php?page=eleads-for-woocommerce&tab=seo')); ?>">
    <?php wp_nonce_field('eleads_save_seo_settings', 'eleads_seo_nonce'); ?>
    <input type="hidden" name="eleads_action" value="save_seo_settings">

    <section class="eleads-section" aria-labelledby="eleads-seo-title">
        <h2 id="eleads-seo-title"><?php echo esc_html__('SEO сторінки', 'eleads-for-woocommerce'); ?></h2>

        <?php if (! $is_available) : ?>
            <div class="notice notice-warning inline">
                <p>
                    <?php echo esc_html__('SEO сторінки недоступні для поточного API ключа E-Leads.', 'eleads-for-woocommerce'); ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="eleads-seo-settings eleads-card">
            <div class="eleads-seo-settings__header">
                <label class="eleads-toggle">
                    <input
                        type="checkbox"
                        name="seo_pages_enabled"
                        value="1"
                        <?php checked((bool) $settings['seo_pages_enabled']); ?>
                        <?php disabled(! $is_available); ?>
                    >
                    <span class="eleads-toggle__control" aria-hidden="true"></span>
                    <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути SEO сторінки E-Leads', 'eleads-for-woocommerce'); ?></span>
                </label>
            </div>

            <div class="eleads-copy-row">
                <label class="eleads-field eleads-field--wide">
                    <span><?php echo esc_html__('URL sitemap', 'eleads-for-woocommerce'); ?></span>
                    <input type="url" value="<?php echo esc_attr($seo_sitemap_url); ?>" readonly>
                </label>
                <div class="eleads-copy-row__actions">
                    <button type="button" class="button" data-eleads-copy="<?php echo esc_attr($seo_sitemap_url); ?>">
                        <?php echo esc_html__('Копіювати URL', 'eleads-for-woocommerce'); ?>
                    </button>
                    <a class="button button-secondary" href="<?php echo esc_url($seo_sitemap_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html__('Відкрити', 'eleads-for-woocommerce'); ?>
                    </a>
                </div>
            </div>

            <p class="description">
                <?php echo esc_html__('Після увімкнення модуль створить sitemap і відкриє сторінки /e-search/{slug} для SEO контенту з E-Leads.', 'eleads-for-woocommerce'); ?>
            </p>
        </div>
    </section>

    <p class="submit eleads-submit eleads-submit--seo">
        <button type="submit" class="button button-primary" <?php disabled(! $is_available); ?>>
            <?php echo esc_html__('Застосувати', 'eleads-for-woocommerce'); ?>
        </button>
    </p>
</form>
