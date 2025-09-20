<?php
/**
 * Template for a single service option item.
 * FIXED VERSION - Properly displays choices and handles existing data
 *
 * @package NORDBOOKING
 * @var array $option
 * @var int $option_index
 * @var array $option_types
 * @var array $price_impact_types
 */

if (!defined('ABSPATH')) exit;

// Set default values for the option
$option_id = $option['option_id'] ?? '';
$name = $option['name'] ?? 'New Option';
$description = $option['description'] ?? '';
$type = $option['type'] ?? 'checkbox';
$is_required = $option['is_required'] ?? 0;
$price_impact_type = $option['price_impact_type'] ?? 'fixed';
$price_impact_value = $option['price_impact_value'] ?? '';
$sort_order = $option['sort_order'] ?? (is_numeric($option_index) ? $option_index + 1 : 0);

// Handle choices - decode if JSON string, otherwise use as array
$choices = [];
if (isset($option['choices'])) {
    if (is_string($option['choices'])) {
        $decoded = json_decode($option['choices'], true);
        $choices = is_array($decoded) ? $decoded : [];
    } elseif (is_array($option['choices'])) {
        $choices = $option['choices'];
    }
} elseif (isset($option['option_values'])) {
    // Fallback to option_values if choices not set
    if (is_string($option['option_values'])) {
        $decoded = json_decode($option['option_values'], true);
        $choices = is_array($decoded) ? $decoded : [];
    } elseif (is_array($option['option_values'])) {
        $choices = $option['option_values'];
    }
}



// Clean up choices array to ensure proper structure
$cleaned_choices = [];
if (!empty($choices)) {
    foreach ($choices as $choice) {
        if (is_array($choice)) {
            $cleaned_choices[] = [
                'label' => $choice['label'] ?? '',
                'price' => $choice['price'] ?? '0'
            ];
        } elseif (is_string($choice)) {
            $cleaned_choices[] = [
                'label' => $choice,
                'price' => '0'
            ];
        }
    }
}
$choices = $cleaned_choices;



// Determine if choices container should be visible
$choices_visible = in_array($type, ['select', 'radio', 'checkbox']);

?>
<div class="NORDBOOKING-option-item option-item" data-option-index="<?php echo esc_attr($option_index); ?>">
    <div class="NORDBOOKING-option-header">
        <div class="NORDBOOKING-option-drag-handle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/>
                <circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>
            </svg>
        </div>
        <div class="NORDBOOKING-option-summary">
            <h4 class="NORDBOOKING-option-name option-name"><?php echo esc_html($name); ?></h4>
            <div class="NORDBOOKING-option-badges">
                <span class="badge badge-outline"><?php echo esc_html($option_types[$type]['label'] ?? 'Unknown'); ?></span>
                <?php if (!empty($price_impact_type) && $price_impact_type !== ''): ?>
                    <span class="badge badge-accent">
                        <?php echo esc_html($price_impact_types[$price_impact_type]['label'] ?? 'Fixed'); ?>
                    </span>
                <?php endif; ?>
                <?php if ($is_required): ?>
                    <span class="badge badge-destructive"><?php esc_html_e('Required', 'NORDBOOKING'); ?></span>
                <?php endif; ?>
                <?php if (!empty($choices)): ?>
                    <span class="badge badge-secondary"><?php echo count($choices); ?> <?php esc_html_e('choices', 'NORDBOOKING'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="NORDBOOKING-option-actions">
            <button type="button" class="btn-icon toggle-option" title="<?php esc_attr_e('Toggle option', 'NORDBOOKING'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <button type="button" class="btn-icon delete-option" title="<?php esc_attr_e('Delete option', 'NORDBOOKING'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="NORDBOOKING-option-content option-content" style="display: none;">
        <div class="NORDBOOKING-option-form">
            <!-- Hidden fields -->
            <?php if ($option_id): ?>
                <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][option_id]" value="<?php echo esc_attr($option_id); ?>">
            <?php endif; ?>
            <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][sort_order]" value="<?php echo esc_attr($sort_order); ?>">
            <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][price_impact_type]" value="<?php echo esc_attr($price_impact_type); ?>" class="price-impact-type-input">

            <div>
                <label class="nordbooking-filter-item label" for="option-name-<?php echo esc_attr($option_index); ?>">
                    <?php esc_html_e('Option Name', 'NORDBOOKING'); ?>
                </label>
                <input
                    type="text"
                    id="option-name-<?php echo esc_attr($option_index); ?>"
                    name="options[<?php echo esc_attr($option_index); ?>][name]"
                    class="regular-text option-name-input"
                    placeholder="<?php esc_attr_e('e.g., Room Size, Add-ons', 'NORDBOOKING'); ?>"
                    value="<?php echo esc_attr($name); ?>"
                    required
                >
            </div>

            <div>
                <label class="nordbooking-filter-item label">
                    <?php esc_html_e('Description', 'NORDBOOKING'); ?>
                </label>
                <textarea
                    name="options[<?php echo esc_attr($option_index); ?>][description]"
                    class="regular-text"
                    rows="2"
                    placeholder="<?php esc_attr_e('Helpful description for customers...', 'NORDBOOKING'); ?>"
                ><?php echo esc_textarea($description); ?></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label class="nordbooking-filter-item label">
                        <?php esc_html_e('Settings', 'NORDBOOKING'); ?>
                    </label>
                    <div class="flex items-center gap-3">
                        <button type="button" class="switch <?php echo $is_required ? 'switch-checked' : ''; ?>" data-switch="required">
                            <span class="switch-thumb"></span>
                        </button>
                        <span class="text-sm"><?php echo $is_required ? esc_html__('Required option', 'NORDBOOKING') : esc_html__('Optional', 'NORDBOOKING'); ?></span>
                        <input type="hidden" name="options[<?php echo esc_attr($option_index); ?>][is_required]" value="<?php echo esc_attr($is_required); ?>" class="option-required-input">
                    </div>
                </div>
            </div>

            <hr>

            <div>
                <label class="nordbooking-filter-item label price-impact-label">
                    <?php if ($type === 'sqm'): ?>
                        <?php esc_html_e('Price per Square Meter', 'NORDBOOKING'); ?>
                    <?php elseif ($type === 'kilometers'): ?>
                        <?php esc_html_e('Price per Kilometer', 'NORDBOOKING'); ?>
                    <?php else: ?>
                        <?php esc_html_e('Price Impact', 'NORDBOOKING'); ?>
                    <?php endif; ?>
                </label>
                <p class="form-description text-xs mb-2 price-impact-description" style="<?php echo in_array($type, ['sqm', 'kilometers']) ? 'display:none;' : ''; ?>">
                    <?php esc_html_e('Set a price for this option itself, independent of choices.', 'NORDBOOKING'); ?>
                </p>

                <div class="price-types-grid" style="<?php echo in_array($type, ['sqm', 'kilometers']) ? 'display:none;' : ''; ?>">
                     <?php foreach ($price_impact_types as $impact_type_key => $impact_type_data): ?>
                        <label class="price-type-card <?php echo $price_impact_type === $impact_type_key ? 'selected' : ''; ?>">
                            <input type="radio" name="price_impact_type_radio_<?php echo esc_attr($option_index); ?>" value="<?php echo esc_attr($impact_type_key); ?>" class="sr-only price-impact-type-radio" <?php checked($price_impact_type, $impact_type_key); ?>>
                            <div class="price-type-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="price-type-icon">
                                    <?php echo get_simple_icon_svg($impact_type_data['icon']); ?>
                                </svg>
                                <div class="price-type-content">
                                    <span class="price-type-title"><?php echo esc_html($impact_type_data['label']); ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="price-impact-value-container mt-4" style="display: <?php echo (!empty($price_impact_type) && $price_impact_type !== '') || in_array($type, ['sqm', 'kilometers']) ? 'block' : 'none'; ?>;">
                    <label class="nordbooking-filter-item label price-impact-value-label" for="price-impact-value-<?php echo esc_attr($option_index); ?>">
                        <?php if ($type === 'sqm'): ?>
                            <?php esc_html_e('Price per Square Meter', 'NORDBOOKING'); ?>
                        <?php elseif ($type === 'kilometers'): ?>
                            <?php esc_html_e('Price per Kilometer', 'NORDBOOKING'); ?>
                        <?php else: ?>
                            <?php esc_html_e('Price Value', 'NORDBOOKING'); ?>
                        <?php endif; ?>
                    </label>
                    <input
                        type="number"
                        id="price-impact-value-<?php echo esc_attr($option_index); ?>"
                        name="options[<?php echo esc_attr($option_index); ?>][price_impact_value]"
                        class="regular-text"
                        placeholder="<?php esc_attr_e('e.g., 10.00', 'NORDBOOKING'); ?>"
                        value="<?php echo esc_attr($price_impact_value); ?>"
                        step="0.01"
                        min="0"
                    >
                </div>
            </div>

            <hr>

            <div>
                <label class="nordbooking-filter-item label"><?php esc_html_e('Option Type', 'NORDBOOKING'); ?></label>
                <p class="form-description text-xs mb-2"><?php esc_html_e('Select how the user will interact with this option.', 'NORDBOOKING'); ?></p>
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

            <div class="choices-container" style="display: <?php echo $choices_visible ? 'block' : 'none'; ?>;">
                <hr>
                <div class="mt-4">
                    <label class="nordbooking-filter-item label">
                        <?php esc_html_e('Choices', 'NORDBOOKING'); ?>
                    </label>
                    <p class="form-description text-xs mb-2">
                        <?php esc_html_e('Add choices for this option.', 'NORDBOOKING'); ?>
                    </p>
                    <div class="choices-list">
                        <?php if (!empty($choices)): ?>
                            <?php foreach ($choices as $choice_index => $choice): ?>
                                <div class="choice-item">
                                    <input type="text" 
                                           name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][label]" 
                                           placeholder="<?php esc_attr_e('Choice Label', 'NORDBOOKING'); ?>" 
                                           value="<?php echo esc_attr($choice['label'] ?? ''); ?>"
                                           required>
                                    <input type="number" 
                                           name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][price]" 
                                           placeholder="<?php esc_attr_e('Price', 'NORDBOOKING'); ?>" 
                                           value="<?php echo esc_attr($choice['price'] ?? '0'); ?>" 
                                           step="0.01">
                                    <button type="button" class="remove-choice-btn" title="<?php esc_attr_e('Remove choice', 'NORDBOOKING'); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            <path d="m19 6-1 14H6L5 6"/>
                                        </svg>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm mt-2 add-choice-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        <?php esc_html_e('Add Choice', 'NORDBOOKING'); ?>
                    </button>
                    <div class="option-feedback text-destructive text-sm mt-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>