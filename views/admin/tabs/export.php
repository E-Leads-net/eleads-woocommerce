<?php
/**
 * Export settings tab.
 *
 * @var array<string, mixed> $settings
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, int> $selected_category_ids
 * @var array<int, array<string, string>> $attributes
 * @var array<int, array<string, mixed>> $feed_rows
 * @var array<string, string> $image_sizes
 */

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this view file.
?>

<form class="eleads-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('eleads_save_export_settings', 'eleads_export_nonce'); ?>
    <input type="hidden" name="action" value="eleads_save_export_settings">

    <?php if (empty($settings['api_key_valid'])) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php
                printf(
                    wp_kses(
                        /* translators: %s: URL to the API Key settings tab. */
                        __('Введіть і збережіть дійсний API ключ E-Leads, щоб керувати налаштуваннями експорту. <a href="%s">Перейти до вкладки API Key</a>.', 'e-leads-for-woocommerce'),
                        [
                            'a' => [
                                'href' => [],
                            ],
                        ]
                    ),
                    esc_url(admin_url('admin.php?page=e-leads-for-woocommerce&tab=api-key'))
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <fieldset <?php disabled(empty($settings['api_key_valid'])); ?>>
    <section class="eleads-section" aria-labelledby="eleads-feed-url-title">
        <h2 id="eleads-feed-url-title"><?php echo esc_html__('URL фіду', 'e-leads-for-woocommerce'); ?></h2>

        <div class="eleads-feed-list eleads-card">
            <?php foreach ($feed_rows as $eleads_feed_row) : ?>
                <?php
                $eleads_status = is_array($eleads_feed_row['status']) ? $eleads_feed_row['status'] : [];
                $eleads_is_ready = ($eleads_status['status'] ?? '') === 'ready';
                $eleads_total = (int) ($eleads_status['total'] ?? 0);
                $eleads_processed = (int) ($eleads_status['processed'] ?? 0);
                $eleads_progress = $eleads_total > 0 ? min(100, (int) floor(($eleads_processed / $eleads_total) * 100)) : ($eleads_is_ready ? 100 : 0);
                $eleads_ready_message = sprintf(
                    /* translators: %d: Number of generated feed offers. */
                    __('Фід готовий, пропозицій: %d', 'e-leads-for-woocommerce'),
                    (int) ($eleads_status['offers'] ?? 0)
                );
                $eleads_progress_message = sprintf(
                    /* translators: 1: Number of processed products, 2: Total products count. */
                    __('Товарів: %1$d / %2$d', 'e-leads-for-woocommerce'),
                    $eleads_processed,
                    $eleads_total
                );
                ?>
                <div class="eleads-feed-row" data-eleads-feed-row="<?php echo esc_attr((string) $eleads_feed_row['language']); ?>">
                    <div class="eleads-feed-row__language"><?php echo esc_html((string) $eleads_feed_row['label']); ?></div>
                    <button
                        type="button"
                        class="button button-primary"
                        data-eleads-generate-feed="<?php echo esc_attr((string) $eleads_feed_row['language']); ?>"
                    >
                        <?php echo esc_html__('Перегенерувати фід', 'e-leads-for-woocommerce'); ?>
                    </button>
                    <button type="button" class="button" data-eleads-copy="<?php echo esc_attr((string) $eleads_feed_row['url']); ?>">
                        <?php echo esc_html__('Копіювати URL', 'e-leads-for-woocommerce'); ?>
                    </button>
                    <a
                        href="<?php echo esc_url((string) $eleads_feed_row['url']); ?>"
                        class="button button-secondary <?php echo esc_attr($eleads_is_ready ? '' : 'is-disabled'); ?>"
                        <?php if (! $eleads_is_ready) : ?>
                            aria-disabled="true"
                            tabindex="-1"
                        <?php endif; ?>
                        target="_blank"
                    >
                        <?php echo esc_html__('Завантажити', 'e-leads-for-woocommerce'); ?>
                    </a>
                    <span class="eleads-status <?php echo esc_attr($eleads_is_ready ? 'eleads-status--ready' : 'eleads-status--muted'); ?>">
                        <?php
                        echo $eleads_is_ready
                            ? esc_html($eleads_ready_message)
                            : esc_html__('Фід ще не створено', 'e-leads-for-woocommerce');
                        ?>
                    </span>
                    <div class="eleads-progress" data-eleads-progress-wrap>
                        <div class="eleads-progress__bar" style="width: <?php echo esc_attr((string) $eleads_progress); ?>%;" data-eleads-progress-bar></div>
                    </div>
                    <div class="eleads-progress__meta" data-eleads-progress-meta>
                        <?php echo esc_html($eleads_progress_message); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-sync-title">
        <h2 id="eleads-sync-title"><?php echo esc_html__('Синхронізація', 'e-leads-for-woocommerce'); ?></h2>

        <label class="eleads-toggle">
            <input type="checkbox" name="sync_enabled" value="1" <?php checked((bool) $settings['sync_enabled']); ?>>
            <span class="eleads-toggle__control" aria-hidden="true"></span>
            <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути синхронізацію', 'e-leads-for-woocommerce'); ?></span>
        </label>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-widgets-title">
        <h2 id="eleads-widgets-title"><?php echo esc_html__('Віджет E-Leads', 'e-leads-for-woocommerce'); ?></h2>

        <label class="eleads-toggle">
            <input type="checkbox" name="widgets_enabled" value="1" <?php checked((bool) $settings['widgets_enabled']); ?>>
            <span class="eleads-toggle__control" aria-hidden="true"></span>
            <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути віджет E-Leads на сайті', 'e-leads-for-woocommerce'); ?></span>
        </label>

        <p class="description">
            <?php
            echo wp_kses(
                sprintf(
                    /* translators: 1: service domain, 2: docs URL, 3: landing URL, 4: privacy policy URL, 5: terms URL, 6: cookie policy URL, 7: security URL. */
                    __('Після увімкнення сайт буде завантажувати скрипт віджета E-Leads із зовнішнього сервісу <code>%1$s</code>. Увімкнення цієї опції означає згоду на підключення сервісу E-Leads на публічних сторінках сайту. Докладніше: <a href="%2$s" target="_blank" rel="noopener noreferrer">документація</a>, <a href="%3$s" target="_blank" rel="noopener noreferrer">про E-Leads</a>, <a href="%4$s" target="_blank" rel="noopener noreferrer">політика конфіденційності</a>, <a href="%5$s" target="_blank" rel="noopener noreferrer">умови використання</a>, <a href="%6$s" target="_blank" rel="noopener noreferrer">cookie policy</a>, <a href="%7$s" target="_blank" rel="noopener noreferrer">security</a>.', 'e-leads-for-woocommerce'),
                    'api.e-leads.net',
                    esc_url('https://e-leads.net/docs/'),
                    esc_url('https://e-leads.net/'),
                    esc_url('https://e-leads.net/privacy-policy/'),
                    esc_url('https://e-leads.net/terms-of-service/'),
                    esc_url('https://e-leads.net/cookie-policy/'),
                    esc_url('https://e-leads.net/security/')
                ),
                [
                    'a' => [
                        'href'   => [],
                        'target' => [],
                        'rel'    => [],
                    ],
                    'code' => [],
                ]
            );
            ?>
        </p>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-categories-title">
        <div class="eleads-section__heading">
            <h2 id="eleads-categories-title"><?php echo esc_html__('Категорії', 'e-leads-for-woocommerce'); ?></h2>
            <?php if ($categories !== []) : ?>
                <div class="eleads-inline-actions">
                    <button type="button" class="eleads-link-button" data-eleads-categories-action="select">
                        <?php echo esc_html__('Позначити всі', 'e-leads-for-woocommerce'); ?>
                    </button>
                    <span aria-hidden="true">|</span>
                    <button type="button" class="eleads-link-button" data-eleads-categories-action="clear">
                        <?php echo esc_html__('Зняти позначення', 'e-leads-for-woocommerce'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($categories === []) : ?>
            <div class="eleads-placeholder eleads-card">
                <div class="eleads-placeholder__icon">+</div>
                <div>
                    <strong><?php echo esc_html__('Категорії не знайдено', 'e-leads-for-woocommerce'); ?></strong>
                    <p><?php echo esc_html__('Створіть категорії товарів WooCommerce, щоб вибрати їх для вивантаження.', 'e-leads-for-woocommerce'); ?></p>
                </div>
            </div>
        <?php else : ?>
            <div class="eleads-category-panel eleads-card" data-eleads-category-panel>
                <?php
                $view = new \Eleads\WooCommerce\Admin\View();
                $view->render('admin/partials/category-tree', [
                    'categories'            => $categories,
                    'selected_category_ids' => $selected_category_ids,
                ]);
                ?>
            </div>
        <?php endif; ?>

        <p class="description">
            <?php echo esc_html__('Позначте категорії для вивантаження. Якщо нічого не вибрано — товари не вивантажуються.', 'e-leads-for-woocommerce'); ?>
        </p>
    </section>

    <section class="eleads-section" aria-labelledby="eleads-attribute-filters-title">
        <div class="eleads-filter-header">
            <h2 id="eleads-attribute-filters-title"><?php echo esc_html__('Атрибути для фільтрації', 'e-leads-for-woocommerce'); ?></h2>
            <div class="eleads-filter-header__controls">
                <?php if ($attributes !== []) : ?>
                    <div class="eleads-filter-actions <?php echo esc_attr(empty($settings['attribute_filters_enabled']) ? 'is-hidden' : ''); ?>" data-eleads-filter-actions="attribute-filters">
                        <div class="eleads-inline-actions">
                            <button type="button" class="eleads-link-button" data-eleads-filter-action="select" data-eleads-filter-target="attribute-filters">
                                <?php echo esc_html__('Позначити всі', 'e-leads-for-woocommerce'); ?>
                            </button>
                            <span aria-hidden="true">|</span>
                            <button type="button" class="eleads-link-button" data-eleads-filter-action="clear" data-eleads-filter-target="attribute-filters">
                                <?php echo esc_html__('Зняти позначення', 'e-leads-for-woocommerce'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <label class="eleads-toggle">
                    <input
                        type="checkbox"
                        name="attribute_filters_enabled"
                        value="1"
                        data-eleads-filter-toggle="attribute-filters"
                        <?php checked((bool) $settings['attribute_filters_enabled']); ?>
                    >
                    <span class="eleads-toggle__control" aria-hidden="true"></span>
                    <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути вибір атрибутів для фільтрації', 'e-leads-for-woocommerce'); ?></span>
                </label>
            </div>
        </div>

        <div
            class="eleads-filter-panel eleads-card <?php echo esc_attr(empty($settings['attribute_filters_enabled']) ? 'is-hidden' : ''); ?>"
            data-eleads-filter-panel="attribute-filters"
        >
            <?php if ($attributes === []) : ?>
                <p class="eleads-empty-text">
                    <?php echo esc_html__('Атрибути WooCommerce ще не створені.', 'e-leads-for-woocommerce'); ?>
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
            <h2 id="eleads-option-filters-title"><?php echo esc_html__('Опції для фільтрації', 'e-leads-for-woocommerce'); ?></h2>
            <div class="eleads-filter-header__controls">
                <?php if ($attributes !== []) : ?>
                    <div class="eleads-filter-actions <?php echo esc_attr(empty($settings['option_filters_enabled']) ? 'is-hidden' : ''); ?>" data-eleads-filter-actions="option-filters">
                        <div class="eleads-inline-actions">
                            <button type="button" class="eleads-link-button" data-eleads-filter-action="select" data-eleads-filter-target="option-filters">
                                <?php echo esc_html__('Позначити всі', 'e-leads-for-woocommerce'); ?>
                            </button>
                            <span aria-hidden="true">|</span>
                            <button type="button" class="eleads-link-button" data-eleads-filter-action="clear" data-eleads-filter-target="option-filters">
                                <?php echo esc_html__('Зняти позначення', 'e-leads-for-woocommerce'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <label class="eleads-toggle">
                    <input
                        type="checkbox"
                        name="option_filters_enabled"
                        value="1"
                        data-eleads-filter-toggle="option-filters"
                        <?php checked((bool) $settings['option_filters_enabled']); ?>
                    >
                    <span class="eleads-toggle__control" aria-hidden="true"></span>
                    <span class="eleads-toggle__label"><?php echo esc_html__('Увімкнути вибір опцій для фільтрації', 'e-leads-for-woocommerce'); ?></span>
                </label>
            </div>
        </div>

        <div
            class="eleads-filter-panel eleads-card <?php echo esc_attr(empty($settings['option_filters_enabled']) ? 'is-hidden' : ''); ?>"
            data-eleads-filter-panel="option-filters"
        >
            <?php if ($attributes === []) : ?>
                <p class="eleads-empty-text">
                    <?php echo esc_html__('Опції будуть доступні після створення атрибутів WooCommerce.', 'e-leads-for-woocommerce'); ?>
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
        <h2 id="eleads-grouping-title"><?php echo esc_html__('Групування товарів', 'e-leads-for-woocommerce'); ?></h2>

        <label class="eleads-toggle">
            <input type="checkbox" name="grouped_products" value="1" <?php checked((bool) $settings['grouped_products']); ?>>
            <span class="eleads-toggle__control" aria-hidden="true"></span>
            <span class="eleads-toggle__label"><?php echo esc_html__('Групувати варіації товару в одну пропозицію', 'e-leads-for-woocommerce'); ?></span>
        </label>
    </section>

    <section class="eleads-section eleads-section--grid" aria-labelledby="eleads-options-title">
        <h2 id="eleads-options-title"><?php echo esc_html__('Параметри експорту', 'e-leads-for-woocommerce'); ?></h2>

        <label class="eleads-field">
            <span><?php echo esc_html__('Розмір зображень', 'e-leads-for-woocommerce'); ?></span>
            <select name="image_size">
                <?php foreach ($image_sizes as $image_size => $label) : ?>
                    <option value="<?php echo esc_attr($image_size); ?>" <?php selected($settings['image_size'], $image_size); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__("Ключ доступу (необов'язково)", 'e-leads-for-woocommerce'); ?></span>
            <input type="text" name="feed_key" value="<?php echo esc_attr((string) $settings['feed_key']); ?>" autocomplete="off">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Назва магазину', 'e-leads-for-woocommerce'); ?></span>
            <input type="text" name="store_name" value="<?php echo esc_attr((string) $settings['store_name']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Email', 'e-leads-for-woocommerce'); ?></span>
            <input type="email" name="email" value="<?php echo esc_attr((string) $settings['email']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('URL магазину', 'e-leads-for-woocommerce'); ?></span>
            <input type="url" name="store_url" value="<?php echo esc_attr((string) $settings['store_url']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Валюта', 'e-leads-for-woocommerce'); ?></span>
            <input type="text" name="currency" value="<?php echo esc_attr((string) $settings['currency']); ?>">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Ліміт зображень (picture)', 'e-leads-for-woocommerce'); ?></span>
            <input type="number" name="picture_limit" value="<?php echo esc_attr((string) $settings['picture_limit']); ?>" min="0" max="20">
        </label>

        <label class="eleads-field">
            <span><?php echo esc_html__('Джерело short_description', 'e-leads-for-woocommerce'); ?></span>
            <select name="short_description_source">
                <option value="short_description" <?php selected($settings['short_description_source'], 'short_description'); ?>><?php echo esc_html__('Короткий опис (анотація)', 'e-leads-for-woocommerce'); ?></option>
                <option value="meta_description" <?php selected($settings['short_description_source'], 'meta_description'); ?>><?php echo esc_html__('Meta description', 'e-leads-for-woocommerce'); ?></option>
                <option value="description" <?php selected($settings['short_description_source'], 'description'); ?>><?php echo esc_html__('Повний опис', 'e-leads-for-woocommerce'); ?></option>
            </select>
        </label>
    </section>
    </fieldset>

    <p class="submit eleads-submit">
        <button type="submit" class="button button-primary" <?php disabled(empty($settings['api_key_valid'])); ?>><?php echo esc_html__('Застосувати', 'e-leads-for-woocommerce'); ?></button>
    </p>
</form>
