<?php
/**
 * Template for a single service option item.
 *
 * @package MoBooking
 * @var array $option
 * @var int $option_index
 * @var array $option_types
 * @var array $price_types
 */

if (!defined('ABSPATH')) exit;

// Set default values for the option
$option_id = $option['option_id'] ?? '';
$name = $option['name'] ?? 'New Option';
$description = $option['description'] ?? '';
$type = $option['type'] ?? 'checkbox';
$is_required = $option['is_required'] ?? 0;
$price_type = $option['price_type'] ?? '';
$price_change = $option['price_change'] ?? '';
$choices = $option['choices'] ?? [];
$sort_order = $option['sort_order'] ?? (is_numeric($option_index) ? $option_index + 1 : 0);

?>
<div class="option-item" data-option-index="<?php echo esc_attr($option_index); ?>">
    <div class="option-header">
        <div class="drag-handle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/>
                <circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>
            </svg>
        </div>
        <div class="option-summary">
            <h4 class="option-name"><?php echo esc_html($name); ?></h4>
            <div class="option-badges">
                <span class="badge badge-outline"><?php echo esc_html($option_types[$type]['label'] ?? 'Unknown'); ?></span>
                <?php if (!empty($price_type) && $price_type !== ''): ?>
                    <span class="badge badge-accent">
                        <?php echo esc_html($price_types[$price_type]['label'] ?? 'Price'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="option-actions">
            <button type="button" class="btn-icon toggle-option">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>
            <button type="button" class="btn-icon delete-option">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="option-content" style="display: none;">
        <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][option_id]" value="<?php echo esc_attr($option_id); ?>">
        <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][sort_order]" value="<?php echo esc_attr($sort_order); ?>" class="option-sort-order">

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label">
                        Option Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        type="text"
                        name="options[<?php echo esc_attr($option_index); ?>][name]"
                        class="form-input option-name-input"
                        placeholder="e.g., Room Size"
                        value="<?php echo esc_attr($name); ?>"
                        required
                    >
                </div>
                <div>
                    <label class="form-label">Required</label>
                    <div class="flex items-center space-x-2 mt-2">
                        <button type="button" class="switch <?php echo $is_required ? 'switch-checked' : ''; ?>" data-switch="required">
                            <span class="switch-thumb"></span>
                        </button>
                        <span class="text-sm">Required option</span>
                        <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][is_required]" value="<?php echo esc_attr($is_required); ?>" class="option-required-input">
                    </div>
                </div>
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea
                    name="options[<?php echo esc_attr($option_index); ?>][description]"
                    class="form-textarea"
                    rows="2"
                    placeholder="Helpful description for customers..."
                ><?php echo esc_textarea($description); ?></textarea>
            </div>

            <hr>

            <div>
                <label class="form-label">Option Type</label>
                <p class="form-description text-xs mb-2">Select how the user will interact with this option.</p>
                <div class="option-types-grid">
                    <?php foreach ($option_types as $type_key => $type_data): ?>
                        <label class="option-type-card <?php echo $type === $type_key ? 'selected' : ''; ?>">
                            <input type="radio" name="options[<?php echo esc_attr($option_index); ?>][type]" value="<?php echo esc_attr($type_key); ?>" class="sr-only option-type-radio" <?php checked($type, $type_key); ?>>
                            <div class="option-type-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="option-type-icon">
                                    <?php echo get_simple_icon_svg($type_data['icon']); ?>
                                </svg>
                                <div class="option-type-content">
                                    <span class="option-type-title"><?php echo esc_html($type_data['label']); ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="choices-container" style="display: <?php echo in_array($type, ['select', 'radio', 'checkbox', 'sqm']) ? 'block' : 'none'; ?>;">
                <hr>
                <div class="mt-4">
                    <label class="form-label">Choices</label>
                    <p class="form-description text-xs mb-2">Add choices for this option. For Square Meter, these are ranges.</p>
                    <div class="choices-list space-y-2" data-option-type="<?php echo esc_attr($type); ?>">
                        <?php if (!empty($choices)): ?>
                            <?php foreach ($choices as $choice_index => $choice): ?>
                                <?php if ($type === 'sqm'): ?>
                                    <div class="choice-item flex items-center gap-2">
                                        <input type="number" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][from_sqm]" class="form-input w-24" placeholder="From" value="<?php echo esc_attr($choice['from_sqm'] ?? ''); ?>" step="0.01">
                                        <span class="text-muted-foreground">-</span>
                                        <input type="number" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][to_sqm]" class="form-input w-24" placeholder="To" value="<?php echo esc_attr($choice['to_sqm'] ?? ''); ?>" step="0.01">
                                        <input type="number" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][price_per_sqm]" class="form-input flex-1" placeholder="Price per SQM" value="<?php echo esc_attr($choice['price_per_sqm'] ?? ''); ?>" step="0.01">
                                        <button type="button" class="btn-icon remove-choice-btn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="choice-item flex items-center gap-2">
                                        <input type="text" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][label]" class="form-input flex-1" placeholder="Choice Label" value="<?php echo esc_attr($choice['label'] ?? ''); ?>">
                                        <input type="number" name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][price]" class="form-input w-24" placeholder="Price" value="<?php echo esc_attr($choice['price'] ?? ''); ?>" step="0.01">
                                        <button type="button" class="btn-icon remove-choice-btn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm mt-2 add-choice-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Add Choice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
