<?php
/**
 * Export settings tab.
 *
 * @var array<string, mixed> $settings
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, string>> $attributes
 * @var array<int, array<string, mixed>> $feed_rows
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<form class="eleads-form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=eleads-woocommerce&tab=export')); ?>">
    <?php wp_nonce_field('eleads_save_export_settings', 'eleads_export_nonce'); ?>
    <input type="hidden" name="eleads_action" value="save_export_settings">

    <?php if (empty($settings['api_key_valid'])) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php
                printf(
                    wp_kses(
                        __('Введіть і збережіть дійсний API ключ E-Leads, щоб керувати налаштуваннями експорту. <a href="%s">Перейти до вкладки API Key</a>.', 'eleads-woocommerce'),
                        [
                            'a' => [
                                'href' => [],
                            ],
                        ]
                    ),
                    esc_url(admin_url('admin.php?page=eleads-woocommerce&tab=api-key'))
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <fieldset <?php disabled(empty($settings['api_key_valid'])); ?>>
    <section class="eleads-section" aria-labelledby="eleads-feed-url-title">
        <h2 id="eleads-feed-url-title"><?php echo esc_html__('URL фіду', 'eleads-woocommerce'); ?></h2>

        <div class="eleads-feed-list eleads-card">
            <?php foreach ($feed_rows as $feed_row) : ?>
                <?php
                $status = is_array($feed_row['status']) ? $feed_row['status'] : [];
                $is_ready = ($status['status'] ?? '') === 'ready';
                $total = (int) ($status['total'] ?? 0);
                $processed = (int) ($status['processed'] ?? 0);
                $progress = $total > 0 ? min(100, (int) floor(($processed / $total) * 100)) : ($is_ready ? 100 : 0);
                ?>
                <div class="eleads-feed-row" data-eleads-feed-row="<?php echo esc_attr((string) $feed_row['language']); ?>">
                    <div class="eleads-feed-row__language"><?php echo esc_html((string) $feed_row['label']); ?></div>
                    <button
                        type="button"
                        class="button button-primary"
                        data-eleads-generate-feed="<?php echo esc_attr((string) $feed_row['language']); ?>"
                    >
                        <?php echo esc_html__('Перегенерувати фід', 'eleads-woocommerce'); ?>
                    </button>
                    <button type="button" class="button" data-eleads-copy="<?php echo esc_attr((string) $feed_row['url']); ?>">
                        <?php echo esc_html__('Копіювати URL', 'eleads-woocommerce'); ?>
                    </button>
                    <a
                        href="<?php echo esc_url((string) $feed_row['url']); ?>"
                        class="button button-secondary <?php echo $is_ready ? '' : 'is-disabled'; ?>"
                        <?php echo $is_ready ? '' : 'aria-disabled="true" tabindex="-1"'; ?>
                        target="_blank"
                    >
                        <?php echo esc_html__('Завантажити', 'eleads-woocommerce'); ?>
                    </a>
                    <span class="eleads-status <?php echo $is_ready ? 'eleads-status--ready' : 'eleads-status--muted'; ?>">
                        <?php
                        echo $is_ready
                            ? esc_html(sprintf(__('Фід готовий, пропозицій: %d', 'eleads-woocommerce'), (int) ($status['offers'] ?? 0)))
                            : esc_html__('Фід ще не створено', 'eleads-woocommerce');
                        ?>
                    </span>
                    <div class="eleads-progress" data-eleads-progress-wrap>
                        <div class="eleads-progress__bar" style="width: <?php echo esc_attr((string) $progress); ?>%;" data-eleads-progress-bar></div>
                    </div>
                    <div class="eleads-progress__meta" data-eleads-progress-meta>
                        <?php echo esc_html(sprintf(__('Товарів: %1$d / %2$d', 'eleads-woocommerce'), $processed, $total)); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-sync-title">
        <h2 id="eleads-sync-title"><?php echo esc_html__('Синхронізація', 'eleads-woocommerce'); ?></h2>

        <label class="eleads-toggle">
            <input type="checkbox" name="sync_enabled" value="1" <?php checked((bool) $settings['sync_enabled']); ?>>
            <span class="eleads-toggle__control" aria-hidden="true"></span>
            <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути синхронізацію', 'eleads-woocommerce'); ?></span>
        </label>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-categories-title">
        <div class="eleads-section__heading">
            <h2 id="eleads-categories-title"><?php echo esc_html__('Категорії', 'eleads-woocommerce'); ?></h2>
            <?php if ($categories !== []) : ?>
                <div class="eleads-inline-actions">
                    <button type="button" class="eleads-link-button" data-eleads-categories-action="select">
                        <?php echo esc_html__('Позначити всі', 'eleads-woocommerce'); ?>
                    </button>
                    <span aria-hidden="true">|</span>
                    <button type="button" class="eleads-link-button" data-eleads-categories-action="clear">
                        <?php echo esc_html__('Зняти позначення', 'eleads-woocommerce'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($categories === []) : ?>
            <div class="eleads-placeholder eleads-card">
                <div class="eleads-placeholder__icon">+</div>
                <div>
                    <strong><?php echo esc_html__('Категорії не знайдено', 'eleads-woocommerce'); ?></strong>
                    <p><?php echo esc_html__('Створіть категорії товарів WooCommerce, щоб вибрати їх для вивантаження.', 'eleads-woocommerce'); ?></p>
                </div>
            </div>
        <?php else : ?>
            <div class="eleads-category-panel eleads-card" data-eleads-category-panel>
                <?php
                $view = new \Eleads\WooCommerce\Admin\View();
                $view->render('admin/partials/category-tree', [
                    'categories'            => $categories,
                    'selected_category_ids' => array_map('absint', (array) $settings['category_ids']),
                ]);
                ?>
            </div>
        <?php endif; ?>

        <p class="description">
            <?php echo esc_html__('Позначте категорії для вивантаження. Якщо нічого не вибрано — товари не вивантажуються.', 'eleads-woocommerce'); ?>
        </p>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-attribute-filters-title">
        <div class="eleads-filter-header">
            <h2 id="eleads-attribute-filters-title"><?php echo esc_html__('Атрибути для фільтрації', 'eleads-woocommerce'); ?></h2>
            <label class="eleads-toggle">
                <input
                    type="checkbox"
                    name="attribute_filters_enabled"
                    value="1"
                    data-eleads-filter-toggle="attribute-filters"
                    <?php checked((bool) $settings['attribute_filters_enabled']); ?>
                >
                <span class="eleads-toggle__control" aria-hidden="true"></span>
                <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути вибір атрибутів для фільтрації', 'eleads-woocommerce'); ?></span>
            </label>
        </div>

        <div class="eleads-filter-panel eleads-card" data-eleads-filter-panel="attribute-filters">
            <?php if ($attributes === []) : ?>
                <p class="eleads-empty-text">
                    <?php echo esc_html__('Атрибути WooCommerce ще не створені.', 'eleads-woocommerce'); ?>
                </p>
            <?php else : ?>
                <?php
                $view = new \Eleads\WooCommerce\Admin\View();
                $view->render('admin/partials/attribute-checklist', [
                    'attributes'      => $attributes,
                    'selected_slugs'  => array_map('sanitize_key', (array) $settings['attribute_filter_slugs']),
                    'field_name'      => 'attribute_filter_slugs',
                ]);
                ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-option-filters-title">
        <div class="eleads-filter-header">
            <h2 id="eleads-option-filters-title"><?php echo esc_html__('Опції для фільтрації', 'eleads-woocommerce'); ?></h2>
            <label class="eleads-toggle">
                <input
                    type="checkbox"
                    name="option_filters_enabled"
                    value="1"
                    data-eleads-filter-toggle="option-filters"
                    <?php checked((bool) $settings['option_filters_enabled']); ?>
                >
                <span class="eleads-toggle__control" aria-hidden="true"></span>
                <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути вибір опцій для фільтрації', 'eleads-woocommerce'); ?></span>
            </label>
        </div>

        <div class="eleads-filter-panel eleads-card" data-eleads-filter-panel="option-filters">
            <?php if ($attributes === []) : ?>
                <p class="eleads-empty-text">
                    <?php echo esc_html__('Опції будуть доступні після створення атрибутів WooCommerce.', 'eleads-woocommerce'); ?>
                </p>
            <?php else : ?>
                <?php
                $view = new \Eleads\WooCommerce\Admin\View();
                $view->render('admin/partials/attribute-checklist', [
                    'attributes'      => $attributes,
                    'selected_slugs'  => array_map('sanitize_key', (array) $settings['option_filter_slugs']),
                    'field_name'      => 'option_filter_slugs',
                ]);
                ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-grouping-title">
        <h2 id="eleads-grouping-title"><?php echo esc_html__('Групування товарів', 'eleads-woocommerce'); ?></h2>

        <label class="eleads-toggle">
            <input type="checkbox" name="grouped_products" value="1" <?php checked((bool) $settings['grouped_products']); ?>>
            <span class="eleads-toggle__control" aria-hidden="true"></span>
            <span class="eleads-toggle__label"><?php echo esc_html__('Групувати варіації товару в одну пропозицію', 'eleads-woocommerce'); ?></span>
        </label>
    </section>

    <section class="eleads-section eleads-section--grid" aria-labelledby="eleads-options-title">
        <h2 id="eleads-options-title"><?php echo esc_html__('Параметри експорту', 'eleads-woocommerce'); ?></h2>

        <label class="eleads-field">
            <span><?php echo esc_html__('Розмір зображень', 'eleads-woocommerce'); ?></span>
            <select name="image_size">
                <option value="200x200" <?php selected($settings['image_size'], '200x200'); ?>>200x200</option>
                <option value="400x400" <?php selected($settings['image_size'], '400x400'); ?>>400x400</option>
                <option value="full" <?php selected($settings['image_size'], 'full'); ?>><?php echo esc_html__('Оригінал', 'eleads-woocommerce'); ?></option>
            </select>
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__("Ключ доступу (необов'язково)", 'eleads-woocommerce'); ?></span>
            <input type="text" name="feed_key" value="<?php echo esc_attr((string) $settings['feed_key']); ?>" autocomplete="off">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Назва магазину', 'eleads-woocommerce'); ?></span>
            <input type="text" name="store_name" value="<?php echo esc_attr((string) $settings['store_name']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Email', 'eleads-woocommerce'); ?></span>
            <input type="email" name="email" value="<?php echo esc_attr((string) $settings['email']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('URL магазину', 'eleads-woocommerce'); ?></span>
            <input type="url" name="store_url" value="<?php echo esc_attr((string) $settings['store_url']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Валюта', 'eleads-woocommerce'); ?></span>
            <input type="text" name="currency" value="<?php echo esc_attr((string) $settings['currency']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Ліміт зображень (picture)', 'eleads-woocommerce'); ?></span>
            <input type="number" name="picture_limit" value="<?php echo esc_attr((string) $settings['picture_limit']); ?>" min="0" max="20">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Джерело short_description', 'eleads-woocommerce'); ?></span>
            <select name="short_description_source">
                <option value="short_description" <?php selected($settings['short_description_source'], 'short_description'); ?>><?php echo esc_html__('Короткий опис (анотація)', 'eleads-woocommerce'); ?></option>
                <option value="meta_description" <?php selected($settings['short_description_source'], 'meta_description'); ?>><?php echo esc_html__('Meta description', 'eleads-woocommerce'); ?></option>
                <option value="description" <?php selected($settings['short_description_source'], 'description'); ?>><?php echo esc_html__('Повний опис', 'eleads-woocommerce'); ?></option>
            </select>
        </label>
    </section>
    </fieldset>

    <p class="submit eleads-submit">
        <button type="submit" class="button button-primary" <?php disabled(empty($settings['api_key_valid'])); ?>><?php echo esc_html__('Застосувати', 'eleads-woocommerce'); ?></button>
    </p>
</form>
