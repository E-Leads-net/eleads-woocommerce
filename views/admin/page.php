<?php
/**
 * Admin page shell.
 *
 * @var string $active_tab
 * @var array<string, string> $tabs
 * @var callable $tab_url
 * @var string $tab_view
 * @var array<string, mixed> $settings
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, string>> $attributes
 * @var array<int, array<string, mixed>> $feed_rows
 * @var array<string, string> $image_sizes
 * @var string $seo_sitemap_url
 * @var bool $saved
 * @var string $generated
 */

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this view file.
?>

<div class="wrap eleads-admin">
    <div class="eleads-admin__header">
        <div>
            <h1 class="eleads-admin__title"><?php echo esc_html__('E-Leads', 'eleads-for-woocommerce'); ?></h1>
            <p class="eleads-admin__subtitle">
                <?php echo esc_html__('WooCommerce export and synchronization module', 'eleads-for-woocommerce'); ?>
            </p>
        </div>
        <div class="eleads-admin__meta">
            <span class="eleads-version">
                <?php echo esc_html(sprintf('Version %s', ELEADS_WOOCOMMERCE_VERSION)); ?>
            </span>
            <span class="eleads-health">
                <?php echo esc_html__('WooCommerce active', 'eleads-for-woocommerce'); ?>
            </span>
        </div>
    </div>

    <div class="eleads-admin__panel">
        <?php if ($saved) : ?>
            <div class="eleads-notice">
                <?php echo esc_html__('Налаштування збережено.', 'eleads-for-woocommerce'); ?>
            </div>
        <?php endif; ?>
        <?php if ($generated === '1') : ?>
            <div class="eleads-notice">
                <?php echo esc_html__('Фід згенеровано.', 'eleads-for-woocommerce'); ?>
            </div>
        <?php elseif ($generated === '0') : ?>
            <div class="eleads-notice eleads-notice--error">
                <?php echo esc_html__('Не вдалося згенерувати фід.', 'eleads-for-woocommerce'); ?>
            </div>
        <?php endif; ?>

        <nav class="eleads-admin__tabs" aria-label="<?php echo esc_attr__('E-Leads tabs', 'eleads-for-woocommerce'); ?>">
            <?php foreach ($tabs as $tab => $label) : ?>
                <a
                    href="<?php echo esc_url($tab_url($tab)); ?>"
                    class="eleads-admin__tab <?php echo esc_attr($active_tab === $tab ? 'is-active' : ''); ?>"
                >
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="eleads-admin__content">
            <?php
            $view = new \Eleads\WooCommerce\Admin\View();
            $view->render($tab_view, [
                'settings'   => $settings,
                'categories' => $categories,
                'attributes' => $attributes,
                'image_sizes' => $image_sizes,
                'feed_rows'  => $feed_rows,
                'seo_sitemap_url' => $seo_sitemap_url,
            ]);
            ?>
        </div>
    </div>
</div>
