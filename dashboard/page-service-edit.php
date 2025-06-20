<?php
/**
 * Dashboard Page: Add/Edit Service - Refactored with Shadcn UI
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// 1. Determine Page Mode (Add vs. Edit) and Set Title
$edit_mode = false;
$service_id = 0;
if ( isset( $_GET['service_id'] ) && ! empty( $_GET['service_id'] ) ) {
    $edit_mode = true;
    $service_id = intval( $_GET['service_id'] );
    $page_title = __( 'Edit Service', 'mobooking' );
} else {
    $page_title = __( 'Add New Service', 'mobooking' );
}

// 2. Initialize Variables
$service_name = '';
$service_description = '';
$service_price = '';
$service_duration = '';
$service_icon = '';
$service_image_url = '';
$service_status = 'active';
$service_options_data = [];
$error_message = '';

// 3. Fetch Service Data in Edit Mode
$user_id = get_current_user_id();
// Fetch business settings for currency display
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'];
$currency_pos = $biz_settings['biz_currency_position'];

if ( $edit_mode && $service_id > 0 ) {
    if ( class_exists('\MoBooking\Classes\Services') ) {
        $services_manager = new \MoBooking\Classes\Services();
        $service_data = $services_manager->get_service( $service_id, $user_id );

        if ( $service_data && ! is_wp_error( $service_data ) ) {
            $service_name = $service_data['name'];
            $service_description = $service_data['description'];
            $service_price = $service_data['price'];
            $service_duration = $service_data['duration'];
            $service_icon = $service_data['icon'];
            $service_image_url = $service_data['image_url'];
            $service_status = $service_data['status'];
            $service_options_data = isset($service_data['options']) && is_array($service_data['options']) ? $service_data['options'] : [];
        } else {
            $error_message = __( 'Service not found or you do not have permission to edit it.', 'mobooking' );
        }
    } else {
        $error_message = __( 'Error: Services manager class not found.', 'mobooking' );
    }
}

// Nonce for JS operations
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');

// Helper functions for rendering form elements
if (!function_exists('mobooking_display_form_field')) {
    function mobooking_display_form_field($args) {
        $defaults = [
            'id' => '',
            'name' => '',
            'label' => '',
            'type' => 'text',
            'value' => '',
            'placeholder' => '',
            'required' => false,
            'options' => [],
            'help_text' => '',
            'input_class' => 'form-input w-full',
            'label_class' => 'form-label',
            'wrapper_class' => 'form-group mb-4',
            'currency_symbol' => '$',
            'currency_pos' => 'before',
            'rows' => 3,
            'min' => null,
            'step' => null,
            'data_attributes' => [], // For data-* attributes
        ];
        $args = wp_parse_args($args, $defaults);

        $html = '<div class="' . esc_attr($args['wrapper_class']) . '">';
        if ($args['label']) {
            $html .= '<label for="' . esc_attr($args['id']) . '" class="' . esc_attr($args['label_class']) . '">' . esc_html($args['label']);
            if ($args['required']) {
                $html .= ' <span class="text-destructive">*</span>';
            }
            $html .= '</label>';
        }

        $input_attrs = 'id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '" class="' . esc_attr($args['input_class']) . '"';
        if ($args['placeholder']) {
            $input_attrs .= ' placeholder="' . esc_attr($args['placeholder']) . '"';
        }
        if ($args['required']) {
            $input_attrs .= ' required';
        }
        if (!is_null($args['min'])) {
            $input_attrs .= ' min="' . esc_attr($args['min']) . '"';
        }
        if (!is_null($args['step'])) {
            $input_attrs .= ' step="' . esc_attr($args['step']) . '"';
        }
        foreach($args['data_attributes'] as $data_key => $data_value) {
            $input_attrs .= ' data-' . esc_attr($data_key) . '="' . esc_attr($data_value) . '"';
        }


        switch ($args['type']) {
            case 'textarea':
                $html .= '<textarea ' . $input_attrs . ' rows="' . esc_attr($args['rows']) . '">' . esc_textarea($args['value']) . '</textarea>';
                break;
            case 'select':
                $html .= '<select ' . $input_attrs . '>';
                foreach ($args['options'] as $value => $label) {
                    $html .= '<option value="' . esc_attr($value) . '" ' . selected($args['value'], $value, false) . '>' . esc_html($label) . '</option>';
                }
                $html .= '</select>';
                break;
            case 'price':
                $html .= '<div class="flex">';
                $price_input_class = $args['input_class'];
                if ($args['currency_pos'] === 'before') {
                    $html .= '<span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-muted text-gray-500 sm:text-sm">' . esc_html($args['currency_symbol']) . '</span>';
                    $price_input_class = str_replace('rounded-md', 'rounded-none rounded-r-md', $price_input_class);
                     $price_input_class = str_replace('w-full', 'flex-1', $price_input_class);
                }
                $html .= '<input type="number" ' . $input_attrs . ' class="' . esc_attr($price_input_class) . '" value="' . esc_attr($args['value']) . '" step="0.01" min="0">';
                if ($args['currency_pos'] === 'after') {
                    $html .= '<span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-muted text-gray-500 sm:text-sm">' . esc_html($args['currency_symbol']) . '</span>';
                     $price_input_class = str_replace('rounded-md', 'rounded-none rounded-l-md', $price_input_class);
                     $price_input_class = str_replace('w-full', 'flex-1', $price_input_class);
                     // Re-apply class if changed
                     $html = str_replace('class="' . esc_attr($args['input_class']) . '"', 'class="' . esc_attr($price_input_class) . '"', $html);
                }
                $html .= '</div>';
                break;
            case 'text':
            case 'number':
            default:
                $html .= '<input type="' . esc_attr($args['type']) . '" ' . $input_attrs . ' value="' . esc_attr($args['value']) . '">';
                break;
        }

        if ($args['help_text']) {
            $html .= '<p class="text-xs text-muted-foreground mt-1">' . esc_html($args['help_text']) . '</p>';
        }
        $html .= '</div>';
        echo $html;
    }
}

if (!function_exists('mobooking_render_service_option_accordion_item')) {
    function mobooking_render_service_option_accordion_item($title, $content_html, $is_open = false, $accordion_content_classes = ['space-y-3']) {
        $html = '<div class="accordion">';
        $html .= '<div class="accordion-item">';
        $html .= '<button type="button" class="accordion-trigger" aria-expanded="' . ($is_open ? 'true' : 'false') . '">';
        $html .= '<span>' . esc_html($title) . '</span>';
        $html .= '<svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
        $html .= '</button>';
        $html .= '<div class="accordion-content' . ($accordion_content_classes ? ' ' . implode(' ', array_map('esc_attr', $accordion_content_classes)) : '') . '" ' . ($is_open ? '' : 'aria-hidden="true"') . '>';
        $html .= $content_html; // Content is already HTML, generated by other helpers
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('mobooking_render_service_option_choice_item_template')) {
    function mobooking_render_service_option_choice_item_template($option_idx_placeholder = '{option_idx}', $choice_idx_placeholder = '{choice_idx}') {
        ob_start();
        ?>
        <div class="choice-item mobooking-choice-item">
            <div class="flex items-center gap-2">
                <span class="drag-handle mobooking-choice-drag-handle p-1 rounded hover:bg-muted">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </span>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 flex-1 items-center">
                    <input type="text" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][choices][<?php echo esc_attr($choice_idx_placeholder); ?>][label]" class="form-input form-input-sm mobooking-choice-label" placeholder="<?php esc_attr_e('Label', 'mobooking'); ?>">
                    <input type="text" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][choices][<?php echo esc_attr($choice_idx_placeholder); ?>][value]" class="form-input form-input-sm mobooking-choice-value" placeholder="<?php esc_attr_e('Value', 'mobooking'); ?>">
                    <input type="number" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][choices][<?php echo esc_attr($choice_idx_placeholder); ?>][price_adjust]" class="form-input form-input-sm mobooking-choice-price-adjust" placeholder="0.00" step="0.01" title="<?php esc_attr_e('Price adjustment (+/-)', 'mobooking'); ?>">
                    <button type="button" class="remove-btn mobooking-remove-choice-btn flex justify-end" title="<?php esc_attr_e('Remove choice', 'mobooking'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('mobooking_render_service_option_template')) {
    function mobooking_render_service_option_template($option_idx_placeholder = '{option_idx}', $option_data = []) {
        // Default structure for a new option, can be overridden by $option_data
        $defaults = [
            'option_id' => '',
            'name' => '',
            'type' => 'checkbox',
            'description' => '',
            'is_required' => false,
            'price_impact_type' => 'none',
            'price_impact_value' => '',
            'choices_display_style' => 'display:none;', // Default for new, non-choice types
        ];

        // If we are rendering an existing option, $option_data will have values
        // If it's for the template, $option_data is empty, uses defaults
        $current_option = wp_parse_args($option_data, $defaults);

        if (in_array($current_option['type'], ['select', 'radio', 'checkbox'])) {
            $current_option['choices_display_style'] = '';
        }

        ob_start();
        ?>
        <div class="option-row mobooking-service-option-row p-4" <?php echo $option_idx_placeholder === '{option_idx}' ? '' : 'data-option-index="' . esc_attr($option_idx_placeholder) . '"'; ?>>
            <div class="option-header flex items-center justify-between pb-2 mb-4 border-b">
                <div class="flex items-center gap-2">
                    <span class="drag-handle mobooking-option-drag-handle p-1 rounded hover:bg-muted">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                    </span>
                    <h4 class="font-semibold text-foreground mobooking-option-title"><?php echo esc_html( $current_option['name'] ?: __('New Option', 'mobooking') ); ?></h4>
                </div>
                <button type="button" class="remove-btn mobooking-remove-option-btn" title="<?php esc_attr_e('Remove option', 'mobooking'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="mobooking-service-option-row-content space-y-4">
                <input type="hidden" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][option_id]" value="<?php echo esc_attr($current_option['option_id']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php
                    mobooking_display_form_field([
                        'id' => 'option_name_' . $option_idx_placeholder,
                        'name' => 'options[' . $option_idx_placeholder . '][name]',
                        'label' => __('Option Name', 'mobooking'),
                        'type' => 'text',
                        'value' => $current_option['name'],
                        'placeholder' => __('e.g., Room Size', 'mobooking'),
                        'required' => true,
                    ]);

                    mobooking_display_form_field([
                        'id' => 'option_type_' . $option_idx_placeholder,
                        'name' => 'options[' . $option_idx_placeholder . '][type]',
                        'label' => __('Type', 'mobooking'),
                        'type' => 'select',
                        'value' => $current_option['type'],
                        'input_class' => 'form-input form-select mobooking-option-type w-full',
                        'options' => [
                            'checkbox' => __('Checkbox', 'mobooking'),
                            'text' => __('Text Input', 'mobooking'),
                            'number' => __('Number Input', 'mobooking'),
                            'select' => __('Dropdown', 'mobooking'),
                            'radio' => __('Radio Buttons', 'mobooking'),
                            'textarea' => __('Text Area', 'mobooking'),
                            'quantity' => __('Quantity', 'mobooking'),
                        ],
                    ]);
                    ?>
                </div>

                <?php
                mobooking_display_form_field([
                    'id' => 'option_description_' . $option_idx_placeholder,
                    'name' => 'options[' . $option_idx_placeholder . '][description]',
                    'label' => __('Description', 'mobooking'),
                    'type' => 'textarea',
                    'value' => $current_option['description'],
                    'placeholder' => __('Helpful description for customers...', 'mobooking'),
                    'rows' => 2,
                ]);

                // Choices Accordion
                $choices_content_html = '<div class="space-y-3">';
                $choices_content_html .= '<div class="mobooking-choices-ui-container">';
                $choices_content_html .= '<div class="mobooking-choices-list space-y-2">';
                // In template mode, choices are added by JS. For existing options, they would be looped here.
                if ($option_idx_placeholder !== '{option_idx}' && !empty($current_option['choices']) && is_array($current_option['choices'])) {
                    foreach($current_option['choices'] as $choice_idx => $choice) {
                        // This part is tricky because mobooking_render_service_option_choice_item_template generates a template string.
                        // For existing choices, we'd ideally populate them directly or have JS handle it from data.
                        // For now, this indicates where existing choices would be handled.
                         $choices_content_html .= '<!-- Existing choice item for ' . esc_attr($choice['label']) . ' would be here -->';
                    }
                }
                $choices_content_html .= '</div>'; // end mobooking-choices-list
                $choices_content_html .= '<button type="button" class="btn btn-outline btn-sm mobooking-add-choice-btn mt-3"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>' . __('Add Choice', 'mobooking') . '</button>';
                $choices_content_html .= '</div>'; // end mobooking-choices-ui-container
                $choices_content_html .= '<p class="text-xs text-muted-foreground">' . __('Manage the individual choices for this option. Each choice can have its own label, value, and price adjustment.', 'mobooking') . '</p>';
                $choices_content_html .= '</div>'; // end space-y-3

                $choices_accordion_html = '<div class="mobooking-option-values-field" style="' . esc_attr($current_option['choices_display_style']) . '">';
                $choices_accordion_html .= mobooking_render_service_option_accordion_item(__('Option Choices', 'mobooking'), $choices_content_html, false, ['p-4', 'space-y-3']);
                $choices_accordion_html .= '</div>';
                echo $choices_accordion_html;


                // Pricing Accordion
                $pricing_content_html = '<div class="space-y-4">'; // Outer space-y-4 for pricing content
                $pricing_content_html .= '<div class="form-group mb-4"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="options[' . esc_attr($option_idx_placeholder) . '][is_required_cb]" value="1" class="w-4 h-4 text-primary border border-gray-300 rounded focus:ring-primary" ' . checked($current_option['is_required'], true, false) . '> <span class="text-sm font-medium">' . __('Required field', 'mobooking') . '</span></label></div>';

                $pricing_content_html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                ob_start(); // Capture output of mobooking_display_form_field
                mobooking_display_form_field([
                    'id' => 'option_price_impact_type_' . $option_idx_placeholder,
                    'name' => 'options[' . $option_idx_placeholder . '][price_impact_type]',
                    'label' => __('Price Impact Type', 'mobooking'),
                    'type' => 'select',
                    'value' => $current_option['price_impact_type'],
                    'options' => [
                        'none' => __('No Price Change', 'mobooking'),
                        'fixed' => __('Fixed Amount', 'mobooking'),
                        'percentage' => __('Percentage', 'mobooking'),
                        'multiply_value' => __('Multiply by Value', 'mobooking'),
                    ],
                ]);
                mobooking_display_form_field([
                    'id' => 'option_price_impact_value_' . $option_idx_placeholder,
                    'name' => 'options[' . $option_idx_placeholder . '][price_impact_value]',
                    'label' => __('Price Impact Value', 'mobooking'),
                    'type' => 'number',
                    'value' => $current_option['price_impact_value'],
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ]);
                $pricing_content_html .= ob_get_clean();
                $pricing_content_html .= '</div>'; // end grid

                $pricing_content_html .= '<p class="text-xs text-muted-foreground">' . __('Configure how this option affects the total service price.', 'mobooking') . '</p>';
                $pricing_content_html .= '</div>'; // end space-y-4 for pricing

                echo mobooking_render_service_option_accordion_item(__('Pricing & Requirements', 'mobooking'), $pricing_content_html, false, ['p-4', 'space-y-4']);
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

?>

<style>
/* Shadcn-inspired utility classes */
.shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
.shadow-md { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
.rounded-lg { border-radius: 0.5rem; }
.rounded-md { border-radius: 0.375rem; }
.rounded-sm { border-radius: 0.125rem; }
.border { border: 1px solid hsl(214.3 31.8% 91.4%); }
.border-b { border-bottom: 1px solid hsl(214.3 31.8% 91.4%); }
.bg-card { background-color: hsl(0 0% 100%); }
.bg-muted { background-color: hsl(210 40% 96%); }
.bg-primary { background-color: hsl(221.2 83.2% 53.3%); }
.bg-secondary { background-color: hsl(210 40% 96%); }
.text-primary { color: hsl(221.2 83.2% 53.3%); }
.text-muted-foreground { color: hsl(215.4 16.3% 46.9%); }
.text-foreground { color: hsl(222.2 84% 4.9%); }
.text-destructive { color: hsl(0 84.2% 60.2%); }
.text-sm { font-size: 0.875rem; line-height: 1.25rem; }
.text-xs { font-size: 0.75rem; line-height: 1rem; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mr-2 { margin-right: 0.5rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.space-y-4 > * + * { margin-top: 1rem; }
.space-y-6 > * + * { margin-top: 1.5rem; }
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-row { flex-direction: row; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 0.5rem; }
.gap-4 { gap: 1rem; }
.w-full { width: 100%; }
.hidden { display: none; }
.block { display: block; }
.grid {
    display: grid;
}
/* Custom components */
.card {
    background-color: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.card-header {
    padding: 1rem 1rem 0; /* p-4 pb-0 */
}

.card-content {
    padding: 1rem; /* p-4 */
}

.card-title {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1;
    letter-spacing: -0.025em;
    color: hsl(222.2 84% 4.9%);
}

.card-description {
    font-size: 0.875rem;
    color: hsl(215.4 16.3% 46.9%);
    margin-top: 0.375rem;
}

.accordion {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    overflow: hidden;
}

.accordion-item {
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 1rem 1.5rem;
    font-weight: 500;
    text-align: left;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.875rem;
}

.accordion-trigger:hover {
    background-color: hsl(210 40% 96%);
}

.accordion-trigger[aria-expanded="true"] {
    background-color: hsl(210 40% 96%);
}

.accordion-trigger .chevron {
    transition: transform 0.15s ease;
}

.accordion-trigger[aria-expanded="true"] .chevron {
    transform: rotate(180deg);
}

.accordion-content {
    padding: 1rem; /* p-4, adjusted for consistency */
    overflow: hidden;
    transition: all 0.15s ease;
}

.accordion-content[aria-hidden="true"] {
    display: none;
}

.form-group {
    margin-bottom: 1rem; /* mb-4 */
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
    margin-bottom: 0.375rem;
}

.form-input {
    display: flex;
    height: 2.5rem;
    width: 100%;
    max-width: 100% !important;
    border-radius: 0.375rem;
    border: 1px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition: all 0.15s ease;
    font-family: inherit;
}

.form-input:focus {
    outline: 2px solid hsl(221.2 83.2% 53.3%);
    outline-offset: 2px;
    border-color: hsl(221.2 83.2% 53.3%);
}

.form-textarea {
    min-height: 5rem;
    resize: vertical;
}

.form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
    padding: 0.5rem 1rem;
    height: 2.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
}

.btn-primary {
    background-color: hsl(221.2 83.2% 53.3%);
    color: hsl(210 40% 98%);
}

.btn-primary:hover {
    background-color: hsl(221.2 83.2% 48%);
}

.btn-secondary {
    background-color: hsl(210 40% 96%);
    color: hsl(222.2 84% 4.9%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
}

.btn-secondary:hover {
    background-color: hsl(210 40% 91%);
}

.btn-outline {
    border: 1px solid hsl(214.3 31.8% 91.4%);
    background-color: hsl(0 0% 100%);
    color: hsl(222.2 84% 4.9%);
}

.btn-outline:hover {
    background-color: hsl(210 40% 96%);
}

.btn-sm {
    height: 2rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
}

.option-row {
    background-color: hsl(0 0% 100%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.15s ease;
}

.option-row:hover {
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.option-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
}

.drag-handle {
    cursor: move;
    color: hsl(215.4 16.3% 46.9%);
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
}

.drag-handle:hover {
    background-color: hsl(210 40% 96%);
    color: hsl(222.2 84% 4.9%);
}

.switch {
    position: relative;
    display: inline-flex;
    height: 1.5rem;
    width: 2.75rem;
    cursor: pointer;
    align-items: center;
    border-radius: 9999px;
    border: 2px solid transparent;
    transition: all 0.15s ease;
    background-color: hsl(214.3 31.8% 91.4%);
}

.switch.active {
    background-color: hsl(221.2 83.2% 53.3%);
}

.switch-thumb {
    pointer-events: none;
    display: block;
    height: 1.25rem;
    width: 1.25rem;
    border-radius: 50%;
    background-color: hsl(0 0% 100%);
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    transition: transform 0.15s ease;
    transform: translateX(0);
}

.switch.active .switch-thumb {
    transform: translateX(1.25rem);
}

.choice-item {
    background-color: hsl(210 40% 96%);
    border: 1px solid hsl(214.3 31.8% 91.4%);
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
}

.choice-item:last-child {
    margin-bottom: 0;
}

.remove-btn {
    color: hsl(0 84.2% 60.2%);
    background: transparent;
    border: none;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
}

.remove-btn:hover {
    background-color: hsl(0 84.2% 95%);
}

@media (max-width: 768px) {
    /* .p-6 { padding: 1rem; } */ /* Adjusted by direct class changes */
    /* .card-content { padding: 1rem; } */ /* Adjusted by direct class changes */
    /* .card-header { padding: 1rem 1rem 0; } */ /* Adjusted by direct class changes */
}
</style>

<div class="mobooking-service-edit-page p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-foreground"><?php echo esc_html( $page_title ); ?></h1>
        <p class="text-muted-foreground mt-2"><?php echo $edit_mode ? esc_html__('Update your service details and options.', 'mobooking') : esc_html__('Create a new service for your customers.', 'mobooking'); ?></p>
    </div>

    <?php if ( ! empty( $error_message ) ) : ?>
        <div class="card mb-6 border-destructive bg-red-50">
            <div class="card-content p-4">
                <p class="text-destructive font-medium"><?php echo esc_html( $error_message ); ?></p>
            </div>
        </div>
        <?php
        if ( $edit_mode && ! $service_data ) {
            echo '</div>';
            return;
        }
        ?>
    <?php endif; ?>

    <div class="space-y-6">
        <form id="mobooking-service-form" class="space-y-6">
            <input type="hidden" id="mobooking-service-id" name="service_id" value="<?php echo esc_attr( $edit_mode ? $service_id : '' ); ?>">
            
            <!-- Basic Information Card -->
            <div class="card">
                <div class="card-header p-4 pb-0">
                    <h3 class="card-title"><?php esc_html_e('Basic Information', 'mobooking'); ?></h3>
                    <p class="card-description"><?php esc_html_e('Enter the core details for your service.', 'mobooking'); ?></p>
                </div>
                <div class="card-content p-4 space-y-4">
                    <?php
                    mobooking_display_form_field([
                        'id' => 'mobooking-service-name',
                        'name' => 'name',
                        'label' => __('Service Name', 'mobooking'),
                        'value' => $service_name,
                        'placeholder' => __('e.g., Deep House Cleaning', 'mobooking'),
                        'required' => true,
                    ]);
                    mobooking_display_form_field([
                        'id' => 'mobooking-service-description',
                        'name' => 'description',
                        'label' => __('Description', 'mobooking'),
                        'type' => 'textarea',
                        'value' => $service_description,
                        'placeholder' => __('Describe what this service includes...', 'mobooking'),
                        'input_class' => 'form-input form-textarea w-full',
                    ]);
                    ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        mobooking_display_form_field([
                            'id' => 'mobooking-service-price',
                            'name' => 'price',
                            'label' => __('Price', 'mobooking'),
                            'type' => 'price',
                            'value' => $service_price,
                            'required' => true,
                            'currency_symbol' => $currency_symbol,
                            'currency_pos' => $currency_pos,
                            'wrapper_class' => 'form-group mb-4 flex-1',
                        ]);
                        mobooking_display_form_field([
                            'id' => 'mobooking-service-duration',
                            'name' => 'duration',
                            'label' => __('Duration (minutes)', 'mobooking'),
                            'type' => 'number',
                            'value' => $service_duration,
                            'required' => true,
                            'min' => 1,
                            'placeholder' => '120',
                            'wrapper_class' => 'form-group mb-4 flex-1',
                        ]);
                        ?>
                    </div>
                </div>
            </div>

            <!-- Status & Display Card -->
            <div class="card">
                <div class="card-header p-4 pb-0">
                    <h3 class="card-title"><?php esc_html_e('Status & Display', 'mobooking'); ?></h3>
                    <p class="card-description"><?php esc_html_e('Control how your service appears to customers.', 'mobooking'); ?></p>
                </div>
                <div class="card-content p-4 space-y-4">
                    <div class="form-group mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="form-label"><?php esc_html_e('Service Status', 'mobooking'); ?></label>
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Only active services are visible to customers.', 'mobooking'); ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground"><?php esc_html_e('Inactive', 'mobooking'); ?></span>
                                <button type="button" id="mobooking-service-status-toggle" class="switch <?php echo ($service_status === 'active') ? 'active' : ''; ?>" aria-pressed="<?php echo ($service_status === 'active') ? 'true' : 'false'; ?>">
                                    <span class="switch-thumb"></span>
                                </button>
                                <span class="text-sm text-muted-foreground"><?php esc_html_e('Active', 'mobooking'); ?></span>
                                <input type="hidden" id="mobooking-service-status" name="status" value="<?php echo esc_attr($service_status); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Icon Section -->
                    <div class="form-group mb-4">
                        <label class="form-label"><?php esc_html_e('Service Icon', 'mobooking'); ?></label>
                        <div class="flex items-start gap-4">
                            <div id="mobooking-service-icon-preview" class="w-16 h-16 border-2 border-dashed rounded-md flex items-center justify-center bg-muted">
                                <?php if ( $service_icon ) : ?>
                                    <span class="dashicons <?php echo esc_attr($service_icon); ?>" style="font-size: 24px; color: #666;"></span>
                                <?php else : ?>
                                    <span class="mobooking-no-icon-text text-xs text-muted-foreground"><?php esc_html_e('No Icon', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="hidden" id="mobooking-service-icon-value" name="icon" value="<?php echo esc_attr($service_icon); ?>">
                                <div class="flex gap-2 mb-2">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('mobooking-service-icon-upload').click();">
                                        <?php esc_html_e('Upload Custom', 'mobooking'); ?>
                                    </button>
                                    <button type="button" id="mobooking-remove-service-icon-btn" class="btn btn-outline btn-sm">
                                        <?php esc_html_e('Remove', 'mobooking'); ?>
                                    </button>
                                </div>
                                <input type="file" id="mobooking-service-icon-upload" accept="image/*" style="display: none;">
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Choose from presets below or upload a custom icon.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Preset Icons Grid -->
                        <div id="mobooking-preset-icons-wrapper" class="mt-4">
                            <div class="grid grid-cols-6 gap-2 max-w-xs">
                                <?php
                                $preset_icons = [
                                    'dashicons-admin-home', 'dashicons-building', 'dashicons-admin-tools',
                                    'dashicons-hammer', 'dashicons-admin-appearance', 'dashicons-camera',
                                    'dashicons-chart-line', 'dashicons-money', 'dashicons-calendar-alt',
                                    'dashicons-clock', 'dashicons-star-filled', 'dashicons-awards'
                                ];
                                foreach ( $preset_icons as $icon ) :
                                ?>
                                    <button type="button" class="mobooking-preset-icon-item w-12 h-12 border rounded-md flex items-center justify-center hover:border-primary transition-colors" data-icon="<?php echo esc_attr($icon); ?>">
                                        <span class="dashicons <?php echo esc_attr($icon); ?>" style="font-size: 20px; color: #666;"></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Image Section -->
                    <div class="form-group mb-4">
                        <label class="form-label"><?php esc_html_e('Service Image', 'mobooking'); ?></label>
                        <div class="flex items-start gap-4">
                            <img id="mobooking-service-image-preview" src="<?php echo esc_url($service_image_url ?: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2296%22%20height%3D%2296%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2096%2096%22%3E%3Crect%20width%3D%2296%22%20height%3D%2296%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Ctext%20x%3D%2248%22%20y%3D%2252%22%20text-anchor%3D%22middle%22%20font-size%3D%2210%22%20fill%3D%22%23AAAAAA%22%3E96x96%3C%2Ftext%3E%3C%2Fsvg%3E'); ?>" alt="Service preview" class="w-24 h-24 object-cover border rounded-md bg-muted">
                            <div class="flex-1">
                                <input type="hidden" id="mobooking-service-image-url-value" name="image_url" value="<?php echo esc_attr($service_image_url); ?>">
                                <div class="flex gap-2 mb-2">
                                    <button type="button" id="mobooking-trigger-service-image-upload-btn" class="btn btn-outline btn-sm">
                                        <?php esc_html_e('Upload Image', 'mobooking'); ?>
                                    </button>
                                    <button type="button" id="mobooking-remove-service-image-btn" class="btn btn-outline btn-sm">
                                        <?php esc_html_e('Remove', 'mobooking'); ?>
                                    </button>
                                </div>
                                <input type="file" id="mobooking-service-image-upload" accept="image/*" style="display: none;">
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Recommended size: 300x300px. Max file size: 2MB.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Options Card -->
            <div class="card">
                <div class="card-header p-4 pb-0">
                    <h3 class="card-title"><?php esc_html_e('Service Options', 'mobooking'); ?></h3>
                    <p class="card-description"><?php esc_html_e('Add customizable options that customers can select when booking this service.', 'mobooking'); ?></p>
                </div>
                <div class="card-content p-4">
                    <div id="mobooking-service-options-list" class="space-y-4">
                        <?php
                        if ( $edit_mode && ! empty( $service_options_data ) ) {
                            foreach ( $service_options_data as $option_idx => $option_data_from_db ) {
                                $current_option_args = [
                                    'option_id' => $option_data_from_db['option_id'] ?? '',
                                    'name' => $option_data_from_db['name'] ?? '',
                                    'type' => $option_data_from_db['type'] ?? 'checkbox',
                                    'description' => $option_data_from_db['description'] ?? '',
                                    'is_required' => !empty($option_data_from_db['is_required']), // Directly use 'is_required' if it's set
                                    'price_impact_type' => $option_data_from_db['price_impact_type'] ?? 'none',
                                    'price_impact_value' => $option_data_from_db['price_impact_value'] ?? '',
                                    // 'choices' are not directly rendered by mobooking_render_service_option_template for existing options; JS handles it.
                                    // However, 'choices_display_style' depends on type.
                                ];
                                if (in_array($current_option_args['type'], ['select', 'radio', 'checkbox'])) {
                                    $current_option_args['choices_display_style'] = '';
                                } else {
                                    $current_option_args['choices_display_style'] = 'display:none;';
                                }
                                echo mobooking_render_service_option_template((string)$option_idx, $current_option_args);
                            }
                        } else {
                        ?>
                            <div class="text-center py-8 text-muted-foreground">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 opacity-50">
                                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                                    <path d="M9 9h6v6H9z"/>
                                </svg>
                                <p class="font-medium"><?php esc_html_e('No options created yet', 'mobooking'); ?></p>
                                <p class="text-sm"><?php esc_html_e('Click "Add Option" to create customizable choices for your service.', 'mobooking'); ?></p>
                            </div>
                        <?php } // Closing the else ?>
                    </div>

                    <div class="mt-6 pt-4 border-t">
                        <button type="button" id="mobooking-add-service-option-btn" class="btn btn-outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M5 12h14"/>
                                <path d="M12 5v14"/>
                            </svg>
                            <?php esc_html_e('Add Option', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card">
                <div class="card-content p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="hidden" id="mobooking-service-form-feedback">
                            <!-- Feedback messages will be shown here -->
                        </div>
                        <div class="flex items-center gap-2 ml-auto"> <!-- Adjusted gap to gap-2 -->
                            <button type="button" id="mobooking-cancel-service-edit-btn" class="btn btn-secondary">
                                <?php esc_html_e('Cancel', 'mobooking'); ?>
                            </button>
                            <button type="submit" id="mobooking-save-service-btn" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17,21 17,13 7,13 7,21"/>
                                    <polyline points="7,3 7,8 15,8"/>
                                </svg>
                                <?php echo $edit_mode ? esc_html__('Update Service', 'mobooking') : esc_html__('Create Service', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Script Templates for Dynamic Content -->
<script type="text/template" id="mobooking-service-option-template">
<?php echo mobooking_render_service_option_template(); ?>
</script>

<script type="text/template" id="mobooking-choice-item-template">
<?php echo mobooking_render_service_option_choice_item_template(); ?>
</script>

<script>
// Enhanced JavaScript for Shadcn UI interactions
document.addEventListener('DOMContentLoaded', function() {
    // Status toggle functionality
    const statusToggle = document.getElementById('mobooking-service-status-toggle');
    const statusInput = document.getElementById('mobooking-service-status');
    
    if (statusToggle && statusInput) {
        statusToggle.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            if (isActive) {
                this.classList.remove('active');
                this.setAttribute('aria-pressed', 'false');
                statusInput.value = 'inactive';
            } else {
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');
                statusInput.value = 'active';
            }
        });
    }

    // Accordion functionality
    function initAccordions() {
        document.querySelectorAll('.accordion-trigger').forEach(trigger => {
            trigger.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                const content = this.nextElementSibling;
                
                if (isExpanded) {
                    this.setAttribute('aria-expanded', 'false');
                    content.setAttribute('aria-hidden', 'true');
                } else {
                    this.setAttribute('aria-expanded', 'true');
                    content.setAttribute('aria-hidden', 'false');
                }
            });
        });
    }

    // Icon selection functionality
    document.querySelectorAll('.mobooking-preset-icon-item').forEach(item => {
        item.addEventListener('click', function() {
            const icon = this.dataset.icon;
            const preview = document.getElementById('mobooking-service-icon-preview');
            const input = document.getElementById('mobooking-service-icon-value');
            
            // Remove selected class from all items
            document.querySelectorAll('.mobooking-preset-icon-item').forEach(i => i.classList.remove('selected'));
            
            // Add selected class to clicked item
            this.classList.add('selected');
            
            // Update preview and input
            if (preview && input) {
                preview.innerHTML = `<span class="dashicons ${icon}" style="font-size: 24px; color: #666;"></span>`;
                input.value = icon;
            }
        });
    });

    // Remove icon functionality
    const removeIconBtn = document.getElementById('mobooking-remove-service-icon-btn');
    if (removeIconBtn) {
        removeIconBtn.addEventListener('click', function() {
            const preview = document.getElementById('mobooking-service-icon-preview');
            const input = document.getElementById('mobooking-service-icon-value');
            
            if (preview && input) {
                preview.innerHTML = '<span class="mobooking-no-icon-text text-xs text-muted-foreground"><?php esc_html_e('No Icon', 'mobooking'); ?></span>';
                input.value = '';
                
                // Remove selected class from all preset icons
                document.querySelectorAll('.mobooking-preset-icon-item').forEach(i => i.classList.remove('selected'));
            }
        });
    }

    // Initialize accordions
    initAccordions();

    const optionsListContainer = document.getElementById('mobooking-service-options-list');
    if (optionsListContainer) {
        // Re-initialize accordions when new options are added (delegated)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.matches('.mobooking-service-option-row')) {
                            // Initialize accordions within the new option row
                            node.querySelectorAll('.accordion-trigger').forEach(trigger => {
                                // Simplified init: if already has listener, skip (though ideally, have a cleanup)
                                if (!trigger.dataset.accordionInitialized) {
                                     trigger.addEventListener('click', function() {
                                        const isExpanded = this.getAttribute('aria-expanded') === 'true';
                                        const content = this.nextElementSibling;
                                        if (isExpanded) {
                                            this.setAttribute('aria-expanded', 'false');
                                            content.setAttribute('aria-hidden', 'true');
                                        } else {
                                            this.setAttribute('aria-expanded', 'true');
                                            content.setAttribute('aria-hidden', 'false');
                                        }
                                    });
                                    trigger.dataset.accordionInitialized = 'true';
                                }
                            });
                        }
                    });
                }
            });
        });
        observer.observe(optionsListContainer, { childList: true, subtree: true }); // subtree true if new rows also contain new accordions immediately

        // Event Delegation for Remove Option and Remove Choice
        optionsListContainer.addEventListener('click', function(e) {
            // Remove Option Button
            const removeOptionBtn = e.target.closest('.mobooking-remove-option-btn');
            if (removeOptionBtn) {
                const optionRow = removeOptionBtn.closest('.mobooking-service-option-row');
                if (optionRow) {
                    if (confirm('<?php esc_html_e('Are you sure you want to remove this option?', 'mobooking'); ?>')) {
                        optionRow.remove();
                        // If it's the last option, show the "No options" message (handled by checking child count or specific class)
                         if (optionsListContainer.querySelectorAll('.mobooking-service-option-row').length === 0) {
                            optionsListContainer.innerHTML = `
                                <div class="text-center py-8 text-muted-foreground">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 opacity-50">
                                        <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 9h6v6H9z"/>
                                    </svg>
                                    <p class="font-medium"><?php esc_html_e('No options created yet', 'mobooking'); ?></p>
                                    <p class="text-sm"><?php esc_html_e('Click "Add Option" to create customizable choices for your service.', 'mobooking'); ?></p>
                                </div>`;
                        }
                    }
                }
            }

            // Remove Choice Button
            const removeChoiceBtn = e.target.closest('.mobooking-remove-choice-btn');
            if (removeChoiceBtn) {
                const choiceItem = removeChoiceBtn.closest('.mobooking-choice-item');
                if (choiceItem) {
                    choiceItem.remove();
                }
            }
        });
    }


    // Form validation enhancement
    const form = document.getElementById('mobooking-service-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Clear previous global feedback and individual field errors
            showFeedback('', 'clear'); // Clear global feedback
            form.querySelectorAll('.field-error-message').forEach(msg => msg.remove());

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(field => {
                let fieldWrapper = field.closest('.form-group') || field.parentNode;
                // Clear previous error state for the field
                field.classList.remove('border-destructive');

                // Remove existing error message for this specific field
                const existingError = fieldWrapper.querySelector('.field-error-message.for-' + field.id);
                if (existingError) {
                    existingError.remove();
                }

                // Check if the field is part of a hidden template or option row
                if (field.closest('.mobooking-service-option-row-content') && field.closest('.mobooking-service-option-row[style*="display: none"]')) {
                    return; // Skip validation for fields in hidden option templates
                }
                if (field.closest('#mobooking-service-option-template') || field.closest('#mobooking-choice-item-template')) {
                    return; // Skip fields within templates
                }


                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-destructive');

                    // Create and insert error message
                    const errorMessage = document.createElement('span');
                    errorMessage.className = 'text-xs text-destructive mt-1 block field-error-message for-' + field.id;
                    errorMessage.textContent = field.dataset.errorMessage || '<?php esc_html_e('This field is required.', 'mobooking'); ?>';

                    let targetElement = field;
                     // If the field is part of a flex container (like price input), insert after the container.
                    if (targetElement.parentNode.classList.contains('flex')) {
                        targetElement = targetElement.parentNode;
                    }
                    targetElement.parentNode.insertBefore(errorMessage, targetElement.nextSibling);


                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showFeedback('<?php esc_html_e('Please fill in all required fields. Check the highlighted fields below.', 'mobooking'); ?>', 'error');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
        });
    }

    // Feedback display function
    function showFeedback(message, type = 'info') {
        const feedback = document.getElementById('mobooking-service-form-feedback');
        if (feedback) {
            if (type === 'clear' || !message) {
                feedback.classList.add('hidden');
                feedback.textContent = '';
                feedback.className = 'hidden'; // Reset classes
                return;
            }
            feedback.className = `p-3 rounded-md text-sm font-medium ${
                type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                'bg-blue-50 text-blue-800 border border-blue-200'
            }`;
            feedback.textContent = message;
            feedback.classList.remove('hidden');
            
            // Auto-hide after 5 seconds for success/info messages, not for errors
            if (type === 'success' || (type ==='info' && message)) {
                setTimeout(() => {
                    feedback.classList.add('hidden');
                }, 5000);
            }
        }
    }

    // Option name update handler
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[name]"]') && e.target.closest('.mobooking-service-option-row')) {
            const optionRow = e.target.closest('.mobooking-service-option-row');
            const titleElement = optionRow.querySelector('.mobooking-option-title');
            if (titleElement) {
                titleElement.textContent = e.target.value || '<?php esc_html_e('Untitled Option', 'mobooking'); ?>';
            }
        }
    });

    // Option type change handler
    document.addEventListener('change', function(e) {
        if (e.target.matches('.mobooking-option-type')) {
            const optionRow = e.target.closest('.mobooking-service-option-row');
            const valuesField = optionRow.querySelector('.mobooking-option-values-field');
            const selectedType = e.target.value;
            
            if (valuesField) {
                if (['select', 'radio', 'checkbox'].includes(selectedType)) {
                    valuesField.style.display = 'block';
                } else {
                    valuesField.style.display = 'none';
                }
            }
        }
    });

    // Image upload handling
    const imageUploadBtn = document.getElementById('mobooking-trigger-service-image-upload-btn');
    const imageUploadInput = document.getElementById('mobooking-service-image-upload');
    const imagePreview = document.getElementById('mobooking-service-image-preview');
    const imageUrlInput = document.getElementById('mobooking-service-image-url-value');
    
    if (imageUploadBtn && imageUploadInput) {
        imageUploadBtn.addEventListener('click', function() {
            imageUploadInput.click();
        });
        
        imageUploadInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                    }
                    if (imageUrlInput) {
                        imageUrlInput.value = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove image functionality
    const removeImageBtn = document.getElementById('mobooking-remove-service-image-btn');
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            if (imagePreview && imageUrlInput) {
                imagePreview.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Ctext%20x%3D%2275%22%20y%3D%2280%22%20text-anchor%3D%22middle%22%20font-size%3D%2212%22%20fill%3D%22%23AAAAAA%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';
                imageUrlInput.value = '';
            }
        });
    }

    // Cancel button functionality
    const cancelBtn = document.getElementById('mobooking-cancel-service-edit-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (confirm('<?php esc_html_e('Are you sure you want to cancel? Any unsaved changes will be lost.', 'mobooking'); ?>')) {
                window.location.href = '<?php echo esc_url(home_url('/dashboard/services/')); ?>';
            }
        });
    }

    // Auto-resize textareas
    document.addEventListener('input', function(e) {
        if (e.target.matches('textarea.form-textarea')) {
            e.target.style.height = 'auto';
            e.target.style.height = e.target.scrollHeight + 'px';
        }
    });

    // Initialize existing textareas
    document.querySelectorAll('textarea.form-textarea').forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });
});

// CSS for form input small variant
const style = document.createElement('style');
style.textContent = `
    .form-input-sm {
        height: 2rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .border-destructive {
        border-color: hsl(0 84.2% 60.2%) !important;
    }
    .choice-item .grid {
        align-items: center;
    }
    .option-row {
        position: relative; /* For potential future absolute positioned elements inside */
    }
    /* .option-row:hover { transform: translateY(-1px); } */ /* Handled by existing hover effect on option-row */

    @media (max-width: 768px) { /* Target md breakpoint for choice item grid specifically */
        #mobooking-service-options-list .grid-cols-1.md\\:grid-cols-2,
        #mobooking-service-option-template .grid-cols-1.md\\:grid-cols-2 {
            grid-template-columns: 1fr; /* Stack option name/type on smaller screens */
        }
    }

    @media (max-width: 640px) { /* sm breakpoint */
        .choice-item .grid {
            grid-template-columns: 1fr; /* Stack choice item inputs */
            gap: 0.5rem;
        }
        .choice-item .grid > * {
            width: 100%;
        }
        .choice-item .remove-btn {
            justify-content: flex-start; /* Align remove button to left on smallest screens */
            padding-left: 0;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php
// The existing JavaScript file will still be loaded and will handle the core AJAX functionality
// This inline script enhances the UI interactions specific to the Shadcn design
?>