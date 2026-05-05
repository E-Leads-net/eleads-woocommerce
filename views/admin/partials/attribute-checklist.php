<?php
/**
 * Attribute checklist partial.
 *
 * @var array<int, array<string, string>> $attributes
 * @var array<int, string> $selected_slugs
 * @var string $field_name
 */

if (! defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this view file.
?>

<ul class="eleads-category-tree">
    <?php foreach ($attributes as $attribute) : ?>
        <?php $slug = (string) $attribute['slug']; ?>
        <li class="eleads-category-tree__item">
            <label class="eleads-check">
                <input
                    type="checkbox"
                    name="<?php echo esc_attr($field_name); ?>[]"
                    value="<?php echo esc_attr($slug); ?>"
                    <?php checked(in_array($slug, $selected_slugs, true)); ?>
                >
                <span class="eleads-check__box" aria-hidden="true"></span>
                <span class="eleads-check__label"><?php echo esc_html((string) $attribute['name']); ?></span>
            </label>
        </li>
    <?php endforeach; ?>
</ul>
