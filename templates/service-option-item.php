<!-- Enhanced Service Option Item Template -->
<div class="service-option-item" data-option-index="<?php echo esc_attr($option_index); ?>">
    <div class="option-header">
        <div class="flex justify-between items-center mb-3">
            <h4 class="text-lg font-semibold">
                Option #<?php echo $option_index + 1; ?>
            </h4>
            <button type="button" class="remove-option-btn text-red-600 hover:text-red-800">
                <i class="fas fa-trash-alt"></i> Remove
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Option Name -->
        <div>
            <label class="form-label">
                Option Name <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="options[<?php echo esc_attr($option_index); ?>][name]"
                   class="form-input"
                   placeholder="e.g., Size, Color, Add-ons"
                   value="<?php echo esc_attr($name ?? ''); ?>"
                   required>
        </div>

        <!-- Option Type -->
        <div>
            <label class="form-label">
                Option Type <span class="text-red-500">*</span>
            </label>
            <select name="options[<?php echo esc_attr($option_index); ?>][type]"
                    class="form-input option-type-select"
                    required>
                <option value="">Select Type</option>
                <option value="text" <?php selected($type ?? '', 'text'); ?>>Text Input</option>
                <option value="textarea" <?php selected($type ?? '', 'textarea'); ?>>Text Area</option>
                <option value="select" <?php selected($type ?? '', 'select'); ?>>Dropdown</option>
                <option value="radio" <?php selected($type ?? '', 'radio'); ?>>Radio Buttons</option>
                <option value="checkbox" <?php selected($type ?? '', 'checkbox'); ?>>Checkboxes</option>
                <option value="sqm" <?php selected($type ?? '', 'sqm'); ?>>Square Meters</option>
                <option value="kilometers" <?php selected($type ?? '', 'kilometers'); ?>>Kilometers</option>
            </select>
        </div>
    </div>

    <!-- Description -->
    <div class="mb-4">
        <label class="form-label">Description</label>
        <textarea name="options[<?php echo esc_attr($option_index); ?>][description]"
                  class="form-input"
                  rows="2"
                  placeholder="Optional description for this option"><?php echo esc_textarea($description ?? ''); ?></textarea>
    </div>

    <!-- Required Checkbox -->
    <div class="mb-4">
        <label class="flex items-center">
            <input type="checkbox"
                   name="options[<?php echo esc_attr($option_index); ?>][is_required]"
                   value="1"
                   class="form-checkbox mr-2"
                   <?php checked($is_required ?? false, true); ?>>
            <span>This option is required</span>
        </label>
    </div>

    <!-- Price Configuration Section -->
    <div class="price-config-section bg-gray-50 p-4 rounded-lg mb-4"
         style="display: <?php echo (!empty($type) && !in_array($type, ['sqm', 'kilometers'])) ? 'block' : 'none'; ?>;">

        <h5 class="text-md font-semibold mb-3 flex items-center">
            <i class="fas fa-dollar-sign mr-2 text-green-600"></i>
            Price Configuration
        </h5>

        <!-- Price Type Selector -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="form-label">Price Type</label>
                <select name="options[<?php echo esc_attr($option_index); ?>][price_type]"
                        class="form-input price-type-select">
                    <option value="no_price" <?php selected($price_type ?? 'fixed', 'no_price'); ?>>
                        No Price Impact
                    </option>
                    <option value="fixed" <?php selected($price_type ?? 'fixed', 'fixed'); ?>>
                        Fixed Amount (+/- $X)
                    </option>
                    <option value="percentage" <?php selected($price_type ?? 'fixed', 'percentage'); ?>>
                        Percentage (+/- X%)
                    </option>
                    <option value="multiplication" <?php selected($price_type ?? 'fixed', 'multiplication'); ?>>
                        Multiplication (× X)
                    </option>
                </select>
            </div>

            <!-- Price Value -->
            <div class="price-value-container"
                 style="display: <?php echo (($price_type ?? 'fixed') !== 'no_price') ? 'block' : 'none'; ?>;">
                <label class="form-label">
                    <span class="price-value-label">
                        <?php
                        switch ($price_type ?? 'fixed') {
                            case 'percentage':
                                echo 'Percentage (%)';
                                break;
                            case 'multiplication':
                                echo 'Multiplication Factor';
                                break;
                            default:
                                echo 'Price Amount ($)';
                        }
                        ?>
                    </span>
                </label>
                <input type="number"
                       name="options[<?php echo esc_attr($option_index); ?>][price_value]"
                       class="form-input"
                       step="0.01"
                       placeholder="<?php
                       switch ($price_type ?? 'fixed') {
                           case 'percentage':
                               echo '0.00';
                               break;
                           case 'multiplication':
                               echo '1.00';
                               break;
                           default:
                               echo '0.00';
                       }
                       ?>"
                       value="<?php echo esc_attr($price_value ?? ''); ?>">
                <small class="text-sm text-gray-600 price-help-text">
                    <?php
                    switch ($price_type ?? 'fixed') {
                        case 'percentage':
                            echo 'Positive values increase price, negative values decrease it';
                            break;
                        case 'multiplication':
                            echo 'Must be positive. 1.0 = no change, 2.0 = double price, 0.5 = half price';
                            break;
                        default:
                            echo 'Positive values increase price, negative values decrease it';
                    }
                    ?>
                </small>
            </div>
        </div>

        <!-- Price Preview -->
        <div class="price-preview bg-blue-50 p-3 rounded border-l-4 border-blue-400">
            <small class="text-blue-800">
                <strong>Preview:</strong>
                <span class="price-preview-text">
                    <?php echo $this->generatePricePreview($price_type ?? 'fixed', $price_value ?? 0); ?>
                </span>
            </small>
        </div>
    </div>

    <!-- Choices Section (for dropdown, radio, checkbox) -->
    <div class="choices-section"
         style="display: <?php echo in_array($type ?? '', ['select', 'radio', 'checkbox']) ? 'block' : 'none'; ?>;">
        <hr class="my-4">
        <div class="mt-4">
            <div class="flex justify-between items-center mb-2">
                <label class="form-label">Choices</label>
                <button type="button" class="add-choice-btn bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-plus mr-1"></i> Add Choice
                </button>
            </div>
            <p class="form-description text-xs mb-2">
                Add choices for this option. Each choice can have its own pricing.
            </p>

            <div class="choices-list">
                <?php if (!empty($choices)): ?>
                    <?php foreach ($choices as $choice_index => $choice): ?>
                        <div class="choice-item bg-white border rounded p-3 mb-2">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="text"
                                       name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][label]"
                                       class="form-input flex-1"
                                       placeholder="Choice label"
                                       value="<?php echo esc_attr($choice['label'] ?? $choice); ?>">
                                <input type="text"
                                       name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][value]"
                                       class="form-input flex-1"
                                       placeholder="Choice value (optional)"
                                       value="<?php echo esc_attr($choice['value'] ?? $choice['label'] ?? $choice); ?>">
                                <button type="button" class="remove-choice-btn text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>

                            <!-- Individual Choice Pricing -->
                            <div class="choice-pricing bg-gray-50 p-2 rounded">
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][price_type]"
                                            class="form-input text-sm choice-price-type">
                                        <option value="no_price" <?php selected($choice['price_type'] ?? 'no_price', 'no_price'); ?>>No Price</option>
                                        <option value="fixed" <?php selected($choice['price_type'] ?? 'no_price', 'fixed'); ?>>Fixed</option>
                                        <option value="percentage" <?php selected($choice['price_type'] ?? 'no_price', 'percentage'); ?>>Percentage</option>
                                        <option value="multiplication" <?php selected($choice['price_type'] ?? 'no_price', 'multiplication'); ?>>Multiply</option>
                                    </select>
                                    <input type="number"
                                           name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][price_value]"
                                           class="form-input text-sm"
                                           step="0.01"
                                           placeholder="Price value"
                                           value="<?php echo esc_attr($choice['price_value'] ?? '0'); ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Range Section (for SQM and Kilometers) -->
    <div class="ranges-section"
         style="display: <?php echo in_array($type ?? '', ['sqm', 'kilometers']) ? 'block' : 'none'; ?>;">
        <hr class="my-4">
        <div class="mt-4">
            <div class="flex justify-between items-center mb-2">
                <label class="form-label">
                    <span class="range-label">
                        <?php echo ($type ?? '') === 'sqm' ? 'Square Meter Ranges' : 'Kilometer Ranges'; ?>
                    </span>
                </label>
                <button type="button" class="add-range-btn bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                    <i class="fas fa-plus mr-1"></i> Add Range
                </button>
            </div>
            <p class="form-description text-xs mb-2 range-description">
                <?php echo ($type ?? '') === 'sqm' ? 'Define pricing ranges for different square meter values.' : 'Define pricing ranges for different kilometer distances.'; ?>
            </p>

            <div class="ranges-list">
                <?php if (!empty($choices)): ?>
                    <?php foreach ($choices as $choice_index => $choice): ?>
                        <div class="range-item bg-white border rounded p-3 mb-2">
                            <div class="grid grid-cols-5 gap-2 items-center">
                                <input type="number"
                                       name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][from_<?php echo ($type ?? '') === 'sqm' ? 'sqm' : 'km'; ?>]"
                                       class="form-input"
                                       placeholder="From"
                                       value="<?php echo esc_attr($choice['from_sqm'] ?? $choice['from_km'] ?? $choice['from'] ?? ''); ?>"
                                       step="0.01"
                                       min="0">
                                <span class="text-center text-gray-500">to</span>
                                <input type="number"
                                       name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][to_<?php echo ($type ?? '') === 'sqm' ? 'sqm' : 'km'; ?>]"
                                       class="form-input"
                                       placeholder="To (∞ for unlimited)"
                                       value="<?php echo esc_attr($choice['to_sqm'] ?? $choice['to_km'] ?? $choice['to'] ?? ''); ?>"
                                       step="0.01"
                                       min="0">
                                <input type="number"
                                       name="options[<?php echo esc_attr($option_index); ?>][choices][<?php echo $choice_index; ?>][price_per_<?php echo ($type ?? '') === 'sqm' ? 'sqm' : 'km'; ?>]"
                                       class="form-input"
                                       placeholder="Price per <?php echo ($type ?? '') === 'sqm' ? 'SQM' : 'KM'; ?>"
                                       value="<?php echo esc_attr($choice['price_per_sqm'] ?? $choice['price_per_km'] ?? $choice['price'] ?? ''); ?>"
                                       step="0.01"
                                       min="0">
                                <button type="button" class="remove-range-btn text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sort Order -->
    <div class="mt-4">
        <label class="form-label">Sort Order</label>
        <input type="number"
               name="options[<?php echo esc_attr($option_index); ?>][sort_order]"
               class="form-input w-24"
               value="<?php echo esc_attr($sort_order ?? $option_index); ?>"
               min="0">
        <small class="text-gray-600 ml-2">Lower numbers appear first</small>
    </div>
</div>

<script>
// Enhanced JavaScript for the new price type functionality
document.addEventListener('DOMContentLoaded', function() {

    // Price type change handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('price-type-select')) {
            updatePriceValueField(e.target);
            updatePricePreview(e.target);
        }

        if (e.target.classList.contains('option-type-select')) {
            updateSectionsVisibility(e.target);
        }

        if (e.target.classList.contains('choice-price-type')) {
            updateChoicePriceField(e.target);
        }
    });

    // Price value input handler for live preview
    document.addEventListener('input', function(e) {
        if (e.target.name && e.target.name.includes('[price_value]')) {
            const optionContainer = e.target.closest('.service-option-item');
            const priceTypeSelect = optionContainer.querySelector('.price-type-select');
            updatePricePreview(priceTypeSelect);
        }
    });

    function updatePriceValueField(priceTypeSelect) {
        const container = priceTypeSelect.closest('.price-config-section');
        const valueContainer = container.querySelector('.price-value-container');
        const valueInput = container.querySelector('input[name*="[price_value]"]');
        const label = container.querySelector('.price-value-label');
        const helpText = container.querySelector('.price-help-text');

        const priceType = priceTypeSelect.value;

        if (priceType === 'no_price') {
            valueContainer.style.display = 'none';
            valueInput.value = '';
        } else {
            valueContainer.style.display = 'block';

            // Update labels and placeholders
            switch (priceType) {
                case 'percentage':
                    label.textContent = 'Percentage (%)';
                    valueInput.placeholder = '0.00';
                    helpText.textContent = 'Positive values increase price, negative values decrease it';
                    break;
                case 'multiplication':
                    label.textContent = 'Multiplication Factor';
                    valueInput.placeholder = '1.00';
                    helpText.textContent = 'Must be positive. 1.0 = no change, 2.0 = double price, 0.5 = half price';
                    break;
                default: // fixed
                    label.textContent = 'Price Amount ($)';
                    valueInput.placeholder = '0.00';
                    helpText.textContent = 'Positive values increase price, negative values decrease it';
            }
        }
    }

    function updatePricePreview(priceTypeSelect) {
        const container = priceTypeSelect.closest('.price-config-section');
        const previewText = container.querySelector('.price-preview-text');
        const valueInput = container.querySelector('input[name*="[price_value]"]');

        const priceType = priceTypeSelect.value;
        const priceValue = parseFloat(valueInput.value) || 0;
        const basePrice = 100; // Example base price for preview

        let preview = '';

        switch (priceType) {
            case 'no_price':
                preview = 'No price impact';
                break;
            case 'fixed':
                if (priceValue > 0) {
                    preview = `Base price ($${basePrice}) + $${priceValue} = $${(basePrice + priceValue).toFixed(2)}`;
                } else if (priceValue < 0) {
                    preview = `Base price ($${basePrice}) - $${Math.abs(priceValue)} = $${(basePrice + priceValue).toFixed(2)}`;
                } else {
                    preview = `No change to base price ($${basePrice})`;
                }
                break;
            case 'percentage':
                const newPrice = basePrice + (basePrice * priceValue / 100);
                if (priceValue > 0) {
                    preview = `Base price ($${basePrice}) + ${priceValue}% = $${newPrice.toFixed(2)}`;
                } else if (priceValue < 0) {
                    preview = `Base price ($${basePrice}) ${priceValue}% = $${newPrice.toFixed(2)}`;
                } else {
                    preview = `No change to base price ($${basePrice})`;
                }
                break;
            case 'multiplication':
                const multipliedPrice = basePrice * priceValue;
                preview = `Base price ($${basePrice}) × ${priceValue} = $${multipliedPrice.toFixed(2)}`;
                break;
        }

        previewText.textContent = preview;
    }

    function updateSectionsVisibility(typeSelect) {
        const container = typeSelect.closest('.service-option-item');
        const priceSection = container.querySelector('.price-config-section');
        const choicesSection = container.querySelector('.choices-section');
        const rangesSection = container.querySelector('.ranges-section');

        const optionType = typeSelect.value;

        // Show/hide price configuration
        if (['sqm', 'kilometers'].includes(optionType)) {
            priceSection.style.display = 'none';
        } else {
            priceSection.style.display = 'block';
        }

        // Show/hide choices section
        if (['select', 'radio', 'checkbox'].includes(optionType)) {
            choicesSection.style.display = 'block';
        } else {
            choicesSection.style.display = 'none';
        }

        // Show/hide ranges section
        if (['sqm', 'kilometers'].includes(optionType)) {
            rangesSection.style.display = 'block';
            updateRangeLabels(container, optionType);
        } else {
            rangesSection.style.display = 'none';
        }
    }

    function updateRangeLabels(container, optionType) {
        const rangeLabel = container.querySelector('.range-label');
        const rangeDescription = container.querySelector('.range-description');

        if (optionType === 'sqm') {
            rangeLabel.textContent = 'Square Meter Ranges';
            rangeDescription.textContent = 'Define pricing ranges for different square meter values.';
        } else {
            rangeLabel.textContent = 'Kilometer Ranges';
            rangeDescription.textContent = 'Define pricing ranges for different kilometer distances.';
        }
    }

    function updateChoicePriceField(choicePriceSelect) {
        const choiceItem = choicePriceSelect.closest('.choice-item');
        const priceInput = choiceItem.querySelector('input[name*="[price_value]"]');

        if (choicePriceSelect.value === 'no_price') {
            priceInput.style.display = 'none';
            priceInput.value = '0';
        } else {
            priceInput.style.display = 'block';
        }
    }

    // Initialize existing forms
    document.querySelectorAll('.price-type-select').forEach(updatePriceValueField);
    document.querySelectorAll('.option-type-select').forEach(updateSectionsVisibility);
});
</script>
