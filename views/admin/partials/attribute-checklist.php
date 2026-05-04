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
?>

<div class="eleads-filter-list">
    <?php foreach ($attributes as $attribute) : ?>
        <?php $slug = (string) $attribute['slug']; ?>
        <label class="eleads-check eleads-check--pill">
            <input
                type="checkbox"
                name="<?php echo esc_attr($field_name); ?>[]"
                value="<?php echo esc_attr($slug); ?>"
                <?php checked(in_array($slug, $selected_slugs, true)); ?>
            >
            <span class="eleads-check__box" aria-hidden="true"></span>
            <span class="eleads-check__label"><?php echo esc_html((string) $attribute['name']); ?></span>
        </label>
    <?php endforeach; ?>
</div>
