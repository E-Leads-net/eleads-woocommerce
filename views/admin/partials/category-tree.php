<?php
/**
 * Category tree partial.
 *
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, int> $selected_category_ids
 */

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this view file.
?>

<ul class="eleads-category-tree">
    <?php foreach ($categories as $category) : ?>
        <?php
        $category_id = (int) $category['id'];
        $children = is_array($category['children']) ? $category['children'] : [];
        ?>
        <li class="eleads-category-tree__item">
            <label class="eleads-check">
                <input
                    type="checkbox"
                    name="category_ids[]"
                    value="<?php echo esc_attr((string) $category_id); ?>"
                    <?php checked(in_array($category_id, $selected_category_ids, true)); ?>
                >
                <span class="eleads-check__box" aria-hidden="true"></span>
                <span class="eleads-check__label">
                    <?php echo esc_html((string) $category['name']); ?>
                    <span class="eleads-check__count"><?php echo esc_html(sprintf('(%d)', (int) $category['count'])); ?></span>
                </span>
            </label>

            <?php if ($children !== []) : ?>
                <?php
                $view = new \Eleads\WooCommerce\Admin\View();
                $view->render('admin/partials/category-tree', [
                    'categories'            => $children,
                    'selected_category_ids' => $selected_category_ids,
                ]);
                ?>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
