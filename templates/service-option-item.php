<?php
/**
 * Template for a single service option item - Redesigned
 *
 * @package MoBooking
 */

if (!defined('ABSPATH')) exit;

global $option, $option_index, $option_types, $price_impact_types;

// Set default values
$option_id = $option['option_id'] ?? '';
$name = $option['name'] ?? 'New Option';
$description = $option['description'] ?? '';
$type = $option['type'] ?? 'checkbox';
$is_required = $option['is_required'] ?? 0;
$price_impact_type = $option['price_impact_type'] ?? 'fixed';
$price_impact_value = $option['price_impact_value'] ?? '';
$sort_order = $option['sort_order'] ?? ($option_index + 1);
$choices = $option['choices'] ?? [];
if (is_string($choices)) {
    $choices = json_decode($choices, true) ?: [];
}
$choices_visible = in_array($type, ['select', 'radio', 'checkbox']);
?>

<div class="service-edit-card option-item" data-option-index="<?php echo esc_attr($option_index); ?>">
    <div class="service-edit-card-header option-header">
        <div class="drag-handle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/>
                <circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>
            </svg>
        </div>
        <div class="option-summary">
            <h4 class="option-name"><?php echo esc_html($name); ?></h4>
        </div>
        <div class="option-actions">
            <button type="button" class="btn btn-icon toggle-option">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>
            <button type="button" class="btn btn-icon btn-destructive delete-option">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="option-content" style="display: none;">
        <div class="service-edit-card-content">
            <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][option_id]" value="<?php echo esc_attr($option_id); ?>">
            <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][sort_order]" value="<?php echo esc_attr($sort_order); ?>" class="option-sort-order">

            <div class="form-grid form-grid-cols-3">
                <div class="form-group form-col-span-2">
                    <label class="form-label">Option Name</label>
                    <input type="text" name="options[<?php echo esc_attr($option_index); ?>][name]" class="form-input option-name-input" value="<?php echo esc_attr($name); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Required</label>
                    <input type="checkbox" name="options[<?php echo esc_attr($option_index); ?>][is_required]" value="1" <?php checked($is_required, 1); ?>>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="options[<?php echo esc_attr($option_index); ?>][description]" class="form-textarea" rows="2"><?php echo esc_textarea($description); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Option Type</label>
                <select name="options[<?php echo esc_attr($option_index); ?>][type]" class="form-input option-type-select">
                    <?php foreach ($option_types as $key => $details): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($type, $key); ?>><?php echo esc_html($details['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="choices-container" style="<?php echo $choices_visible ? '' : 'display: none;'; ?>">
                <label class="form-label">Choices</label>
                <div class="choices-list">
                    <?php if (!empty($choices)): ?>
                        <?php foreach ($choices as $c_index => $choice): ?>
                            <div class="choice-item">
                                <input type="text" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $c_index; ?>][label]" class="form-input" value="<?php echo esc_attr($choice['label'] ?? ''); ?>" placeholder="Label">
                                <input type="number" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $c_index; ?>][price]" class="form-input" value="<?php echo esc_attr($choice['price'] ?? ''); ?>" placeholder="Price">
                                <button type="button" class="btn btn-icon btn-destructive remove-choice-btn">X</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline btn-sm add-choice-btn">Add Choice</button>
            </div>
        </div>
    </div>
</div>