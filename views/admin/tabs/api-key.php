<?php
/**
 * API key tab.
 *
 * @var array<string, mixed> $settings
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<form class="eleads-form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=eleads-woocommerce&tab=api-key')); ?>">
    <?php wp_nonce_field('eleads_save_api_key', 'eleads_api_key_nonce'); ?>
    <input type="hidden" name="eleads_action" value="save_api_key">

    <section class="eleads-section eleads-section--compact" aria-labelledby="eleads-api-key-title">
        <div class="eleads-card eleads-card--api">
            <h2 id="eleads-api-key-title"><?php echo esc_html__('API ключ проекту', 'eleads-woocommerce'); ?></h2>

            <label class="eleads-field eleads-field--wide">
                <span class="screen-reader-text"><?php echo esc_html__('API ключ проекту', 'eleads-woocommerce'); ?></span>
                <input type="text" name="api_key" value="<?php echo esc_attr((string) $settings['api_key']); ?>" autocomplete="off" placeholder="eleads_...">
            </label>

            <button type="submit" class="button button-primary"><?php echo esc_html__('Зберегти ключ', 'eleads-woocommerce'); ?></button>

            <?php if ((string) $settings['api_key'] !== '') : ?>
                <p class="eleads-api-status <?php echo ! empty($settings['api_key_valid']) ? 'is-valid' : 'is-invalid'; ?>">
                    <?php
                    echo ! empty($settings['api_key_valid'])
                        ? esc_html__('Ключ перевірено. Налаштування модуля відкриті.', 'eleads-woocommerce')
                        : esc_html__('Ключ не пройшов перевірку. Налаштування модуля закриті.', 'eleads-woocommerce');
                    ?>
                </p>
            <?php endif; ?>

            <p class="description">
                <?php
                printf(
                    wp_kses(
                        __('Введіть API ключ проекту E-Leads для доступу до налаштувань модуля. Ключ можна отримати в <a href="%s" target="_blank" rel="noopener noreferrer">дашборді E-Leads</a>.', 'eleads-woocommerce'),
                        [
                            'a' => [
                                'href'   => [],
                                'target' => [],
                                'rel'    => [],
                            ],
                        ]
                    ),
                    esc_url(\Eleads\WooCommerce\Api\Routes::dashboard_url())
                );
                ?>
            </p>
        </div>
    </section>
</form>
