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
$service_icon = ''; // Stores the key of the selected SVG icon
$service_image_url = '';
$service_status = 'active';
$service_options_data = []; // This will hold data for existing options
$error_message = '';

// 3. Fetch Service Data in Edit Mode
$user_id = get_current_user_id();
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
            $service_icon = $service_data['icon']; // Should be an icon key
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

// Define Preset SVG Icons for Service Icon selection
$mobooking_preset_svg_icons = [
    'default' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-gray-400"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
    'home' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
    'tool' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>',
    'time' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>',
    'service' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M20 8h-3V6c0-1.1-.9-2-2-2H9c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v10h20V10c0-1.1-.9-2-2-2zM9 6h6v2H9V6zm11 12H4v-3h2v1h2v-1h8v1h2v-1h2v3zm0-5h-2v-1H6v1H4v-1c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v1z"/></svg>',
    'payment' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>',
    'star' => '<svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2l-2.81 6.63L2 9.24l5.46 4.73L5.82 21z"/></svg>',
];
$default_svg_icon_key = 'default';

// Define Option Type SVGs & Labels
$mobooking_option_type_icons = [
    'checkbox' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
    'text' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828zM4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>',
    'number' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M9.49 2.544c.28-.594.94-.825 1.534-.544L11 2l.003.001c.28.133.68.057.943-.192a.75.75 0 011.061 1.06L13 3l-.001.003c-.133.28-.057.68.192.943a.75.75 0 01-1.06 1.061L12 5l.001-.003c-.28-.133-.68-.057-.943.192a.75.75 0 01-1.06-1.06L10 4l-.003-.001a1.07 1.07 0 00-.192-.943L9.49 2.544zM5 8a1 1 0 000 2h10a1 1 0 100-2H5zm1 4a1 1 0 100 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>',
    'select' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>',
    'radio' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0 2a10 10 0 100-20 10 10 0 000 20zm0-5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>',
    'textarea' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm11 3a1 1 0 10-2 0v6a1 1 0 102 0V7zm-4 0a1 1 0 10-2 0v6a1 1 0 102 0V7z" clip-rule="evenodd" /></svg>',
    'quantity' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>',
];
$mobooking_option_types_available = [
    'checkbox' => __('Checkbox', 'mobooking'),
    'text' => __('Text Input', 'mobooking'),
    'number' => __('Number Input', 'mobooking'),
    'select' => __('Dropdown', 'mobooking'),
    'radio' => __('Radio Buttons', 'mobooking'),
    'textarea' => __('Text Area', 'mobooking'),
    'quantity' => __('Quantity', 'mobooking'),
];

// Define Price Impact Type SVGs & Labels
$mobooking_price_impact_type_icons = [
    'none' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 101.414-1.414L11.414 10l1.293-1.293a1 1 0 10-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
    'fixed' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" /></svg>',
    'percentage' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M17.5 15a2.5 2.5 0 110-5 2.5 2.5 0 010 5zM5.5 5a2.5 2.5 0 110-5 2.5 2.5 0 010 5zM16.03 6.03a.75.75 0 00-1.06-1.06L4.97 15.03a.75.75 0 001.06 1.06L16.03 6.03z"/></svg>',
    'multiply_value' => '<svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>',
];
$mobooking_price_impact_types_available = [
    'none' => __('No Price Change', 'mobooking'),
    'fixed' => __('Fixed Amount', 'mobooking'),
    'percentage' => __('Percentage', 'mobooking'),
    'multiply_value' => __('Multiply by Value', 'mobooking'),
];


// Helper functions for rendering form elements (Assumed to be defined above this point)
// ... [All helper functions as previously defined: mobooking_display_form_field, mobooking_render_service_option_choice_item_template, etc.] ...
// The key change is within mobooking_render_service_option_template
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

// Removed mobooking_render_service_option_accordion_item as it's no longer used.

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
        global $mobooking_option_type_icons, $mobooking_option_types_available, $mobooking_price_impact_type_icons, $mobooking_price_impact_types_available;

        $defaults = [
            'option_id' => '',
            'name' => '',
            'type' => 'checkbox',
            'description' => '',
            'is_required' => false,
            'price_impact_type' => 'none',
            'price_impact_value' => '',
            'choices_display_style' => 'display:none;',
        ];
        $current_option = wp_parse_args($option_data, $defaults);

        if (in_array($current_option['type'], ['select', 'radio', 'checkbox'])) {
            $current_option['choices_display_style'] = '';
        }

        $content_id = 'option-content-' . $option_idx_placeholder;
        $is_template_placeholder = ($option_idx_placeholder === '{option_idx}');
        // For new template rows, default to expanded. For existing, JS will handle.
        $is_expanded_default = $is_template_placeholder ? true : true;
        $initial_chevron_rotation = $is_expanded_default ? 'rotate-180' : ''; // Pointing down initially
        $initial_header_border_class = $is_expanded_default && !$is_template_placeholder ? 'border-b' : '';


        ob_start();
        ?>
        <div class="option-row mobooking-service-option-row border rounded-md bg-card" <?php echo $is_template_placeholder ? '' : 'data-option-index="' . esc_attr($option_idx_placeholder) . '"'; ?>>
            <button type="button" class="mobooking-option-row-trigger flex items-center justify-between w-full text-left p-4 <?php echo esc_attr($initial_header_border_class); ?>" aria-expanded="<?php echo $is_expanded_default ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($content_id); ?>">
                <div class="flex items-center gap-3">
                    <span class="drag-handle mobooking-option-drag-handle p-1 rounded hover:bg-muted cursor-move">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                    </span>
                    <h4 class="font-semibold text-foreground mobooking-option-title"><?php echo esc_html( $current_option['name'] ?: __('New Option', 'mobooking') ); ?></h4>
                </div>
                <div class="flex items-center">
                    <button type="button" class="remove-btn mobooking-remove-option-btn mr-2" title="<?php esc_attr_e('Remove option', 'mobooking'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>
                    </button>
                    <span class="mobooking-option-chevron text-gray-500 transform transition-transform duration-150 <?php echo esc_attr($initial_chevron_rotation); ?>">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </span>
                </div>
            </button>

            <div id="<?php echo esc_attr($content_id); ?>" class="mobooking-service-option-row-content p-4 space-y-4 <?php echo !$is_expanded_default && !$is_template_placeholder ? 'hidden-by-collapse' : ''; ?>">
                <input type="hidden" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][option_id]" value="<?php echo esc_attr($current_option['option_id']); ?>">

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
                ?>

                <div class="form-group mb-4">
                    <label class="form-label"><?php esc_html_e('Type', 'mobooking'); ?></label>
                    <div class="flex flex-wrap gap-2 mobooking-option-type-radio-group">
                        <?php
                        foreach ($mobooking_option_types_available as $type_key => $type_label) :
                            $svg_icon = $mobooking_option_type_icons[$type_key] ?? '<svg class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path d="M12.732 3.732a1 1 0 011.268.175l2.268 2.914a1 1 0 01-.29 1.62l-2.4.96a1 1 0 01-1.106-.273l-1.4-1.8a1 1 0 01.175-1.268l1.485-1.333zM4.732 6.732a1 1 0 011.268.175l2.268 2.914a1 1 0 01-.29 1.62l-2.4.96a1 1 0 01-1.106-.273l-1.4-1.8a1 1 0 01.175-1.268l1.485-1.333zM7 13a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z"/></svg>';
                            $is_checked = ($current_option['type'] === $type_key);
                        ?>
                            <label class="styled-radio-btn flex flex-col items-center justify-center p-3 border rounded-md cursor-pointer hover:border-primary focus-within:ring-2 focus-within:ring-primary <?php echo ($is_checked ? 'border-primary ring-2 ring-primary bg-primary-50' : 'border-gray-300'); ?>">
                                <input type="radio" id="option_type_<?php echo esc_attr($option_idx_placeholder) . '_' . esc_attr($type_key); ?>" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][type]" value="<?php echo esc_attr($type_key); ?>" class="sr-only mobooking-option-type" <?php checked($current_option['type'], $type_key); ?>>
                                <span class="icon-wrapper text-gray-600 group-hover:text-primary mb-1"><?php echo $svg_icon; ?></span>
                                <span class="text-label text-xs font-medium text-gray-700 group-hover:text-primary"><?php echo esc_html($type_label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
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
                ?>

                <div class="mobooking-option-values-field form-group mb-4" style="<?php echo esc_attr($current_option['choices_display_style']); ?>">
                    <label class="form-label"><?php esc_html_e('Option Choices', 'mobooking'); ?></label>
                    <div class="space-y-3 p-4 border rounded-md bg-slate-50">
                        <div class="mobooking-choices-ui-container">
                            <div class="mobooking-choices-list space-y-2">
                                <!-- JS will populate choices here for existing options -->
                            </div>
                            <button type="button" class="btn btn-outline btn-sm mobooking-add-choice-btn mt-3">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                <?php esc_html_e('Add Choice', 'mobooking'); ?>
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground"><?php esc_html_e('Manage the individual choices for this option. Each choice can have its own label, value, and price adjustment.', 'mobooking'); ?></p>
                    </div>
                </div>

                <div class="form-group mb-4">
                     <label class="form-label"><?php esc_html_e('Pricing & Requirements', 'mobooking'); ?></label>
                    <div class="space-y-4 p-4 border rounded-md bg-slate-50">
                        <div class="form-group mb-0"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][is_required_cb]" value="1" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" <?php checked($current_option['is_required'], true, false); ?>> <span class="text-sm font-medium"><?php esc_html_e('Required field', 'mobooking'); ?></span></label></div>

                        <div class="form-group mb-4">
                             <label class="form-label text-sm"><?php esc_html_e('Price Impact Type', 'mobooking'); ?></label>
                             <div class="flex flex-wrap gap-2 mobooking-price-impact-type-radio-group">
                                <?php
                                foreach ($mobooking_price_impact_types_available as $type_key => $type_label) :
                                    $svg_icon = $mobooking_price_impact_type_icons[$type_key] ?? '';
                                    $is_checked = ($current_option['price_impact_type'] === $type_key);
                                ?>
                                    <label class="styled-radio-btn flex flex-col items-center justify-center p-3 border rounded-md cursor-pointer hover:border-primary focus-within:ring-2 focus-within:ring-primary <?php echo ($is_checked ? 'border-primary ring-2 ring-primary bg-primary-50' : 'border-gray-300'); ?>">
                                        <input type="radio" id="option_price_impact_type_<?php echo esc_attr($option_idx_placeholder) . '_' . esc_attr($type_key); ?>" name="options[<?php echo esc_attr($option_idx_placeholder); ?>][price_impact_type]" value="<?php echo esc_attr($type_key); ?>" class="sr-only mobooking-price-impact-type-radio" <?php checked($current_option['price_impact_type'], $type_key); ?>>
                                        <span class="icon-wrapper text-gray-600 group-hover:text-primary mb-1"><?php echo $svg_icon; ?></span>
                                        <span class="text-label text-xs font-medium text-gray-700 group-hover:text-primary"><?php echo esc_html($type_label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php
                        mobooking_display_form_field([
                            'id' => 'option_price_impact_value_' . $option_idx_placeholder,
                            'name' => 'options[' . $option_idx_placeholder . '][price_impact_value]',
                            'label' => __('Price Impact Value', 'mobooking'),
                            'label_class' => 'form-label text-sm',
                            'type' => 'number',
                            'value' => $current_option['price_impact_value'],
                            'placeholder' => '0.00',
                            'step' => '0.01',
                            'wrapper_class' => 'form-group mb-0',
                        ]);
                        ?>
                        <p class="text-xs text-muted-foreground"><?php esc_html_e('Configure how this option affects the total service price.', 'mobooking'); ?></p>
                    </div>
                </div>
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
.sr-only { /* Screen Reader Only */
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    border: 0;
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

/* Accordion (Original - can be removed if no other accordions use this exact structure) */
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

.option-row { /* Service Option Row container */
    /* background-color: hsl(0 0% 100%); */ /* Handled by .bg-card if needed, or direct */
    /* border: 1px solid hsl(214.3 31.8% 91.4%); */ /* Handled by .border */
    /* border-radius: 0.5rem; */ /* Handled by .rounded-md */
    /* padding: 1rem; */ /* Removed, p-4 is on content, header has its own */
    margin-bottom: 0.75rem; /* space-y-3 or mb-3 on parent */
    transition: all 0.15s ease;
}

.option-row:hover { /* Optional: if you want a hover effect on the whole row */
    /* box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1); */
}

/* .option-header is now part of .mobooking-option-row-trigger or replaced by it */


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

/* Styles for Collapsible Option Rows */
.option-row {
    padding: 0; /* Service option row itself has no padding */
}
.mobooking-option-row-trigger {
    background: none;
    /* border: none; border-b is applied by default or when expanded */
    width: 100%;
    text-align: left;
    cursor: pointer;
    /* padding is p-4, defined on the button in PHP */
}
.mobooking-option-chevron {
    transition: transform 0.15s ease-in-out;
}
/* Chevron pointing up when collapsed (trigger's aria-expanded="false") */
.mobooking-option-row-trigger[aria-expanded="false"] .mobooking-option-chevron {
    transform: rotate(0deg);
}
/* Chevron pointing down when expanded (trigger's aria-expanded="true") */
.mobooking-option-row-trigger[aria-expanded="true"] .mobooking-option-chevron {
    transform: rotate(180deg);
}
.mobooking-service-option-row-content.hidden-by-collapse {
    display: none;
}

/* Styled Radio Buttons for Option Type & Price Impact Type */
.styled-radio-btn {
    transition: all 0.15s ease-in-out;
    min-width: 80px;
    text-align: center;
}
.styled-radio-btn:has(input:checked) { /* Modern CSS to style based on hidden input state */
    border-color: hsl(221.2 83.2% 53.3%); /* primary */
    background-color: hsl(221.2 83.2% 53.3% / 0.05); /* primary-50 equivalent */
    box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3%); /* ring-2 ring-primary */
}
.styled-radio-btn.selected-radio-label { /* Fallback for JS-driven selection, if :has is not fully relied upon */
    border-color: hsl(221.2 83.2% 53.3%);
    background-color: hsl(221.2 83.2% 53.3% / 0.05);
    box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3%);
}
.styled-radio-btn:hover {
        border-color: hsl(221.2 83.2% 48%); /* primary-hover */
}
.styled-radio-btn .icon-wrapper svg {
    width: 1.5rem; /* w-6 */
    height: 1.5rem; /* h-6 */
    margin-bottom: 0.25rem; /* mb-1 */
}
.styled-radio-btn .text-label {
    font-size: 0.75rem; /* text-xs */
    font-weight: 500; /* font-medium */
}
.styled-radio-btn:focus-within { /* Accessibility: highlight container when hidden radio has focus */
        border-color: hsl(221.2 83.2% 53.3%);
        box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3%);
}


@media (max-width: 768px) {
    /* .p-6 { padding: 1rem; } */
    /* .card-content { padding: 1rem; } */
    /* .card-header { padding: 1rem 1rem 0; } */
    #mobooking-service-options-list .grid-cols-1.md\\:grid-cols-2,
    #mobooking-service-option-template .grid-cols-1.md\\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
    .mobooking-option-type-radio-group,
    .mobooking-price-impact-type-radio-group {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); /* Allow more aggressive wrapping */
    }
}

@media (max-width: 640px) {
    .choice-item .grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    .choice-item .grid > * {
        width: 100%;
    }
    .choice-item .remove-btn {
        justify-content: flex-start;
        padding-left: 0;
    }
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
                            <div id="mobooking-service-icon-preview" class="w-16 h-16 p-1 border-2 border-dashed rounded-md flex items-center justify-center bg-muted text-gray-600">
                                <?php
                                $current_icon_key = $service_icon ?: $default_svg_icon_key;
                                $preview_svg = isset($mobooking_preset_svg_icons[$current_icon_key]) ? $mobooking_preset_svg_icons[$current_icon_key] : $mobooking_preset_svg_icons[$default_svg_icon_key];
                                echo $preview_svg;
                                ?>
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
                                <input type="file" id="mobooking-service-icon-upload" accept="image/*, .svg" style="display: none;">
                                <p class="text-xs text-muted-foreground"><?php esc_html_e('Choose from presets below or upload a custom SVG/image icon.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Preset Icons Grid -->
                        <div id="mobooking-preset-icons-wrapper" class="mt-4">
                            <div class="grid grid-cols-6 gap-2 max-w-xs">
                                <?php
                                foreach ( $mobooking_preset_svg_icons as $key => $svg_content ) :
                                    if ($key === $default_svg_icon_key && $service_icon !== $default_svg_icon_key) continue;
                                    if ($key === 'default' && $service_icon === '') continue;
                                ?>
                                    <button type="button" class="mobooking-preset-icon-item w-12 h-12 p-2 border rounded-md flex items-center justify-center hover:border-primary transition-colors <?php echo ($service_icon === $key ? 'border-primary ring-2 ring-primary' : ''); ?>" data-icon-key="<?php echo esc_attr($key); ?>" title="<?php echo esc_attr(ucfirst($key)); ?>">
                                        <?php echo $svg_content; ?>
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
                                    'is_required' => !empty($option_data_from_db['is_required']),
                                    'price_impact_type' => $option_data_from_db['price_impact_type'] ?? 'none',
                                    'price_impact_value' => $option_data_from_db['price_impact_value'] ?? '',
                                    'choices' => isset($option_data_from_db['choices']) && is_array($option_data_from_db['choices']) ? $option_data_from_db['choices'] : [],
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
                                    <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 9h6v6H9z"/>
                                </svg>
                                <p class="font-medium"><?php esc_html_e('No options created yet', 'mobooking'); ?></p>
                                <p class="text-sm"><?php esc_html_e('Click "Add Option" to create customizable choices for your service.', 'mobooking'); ?></p>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="mt-6 pt-4 border-t">
                        <button type="button" id="mobooking-add-service-option-btn" class="btn btn-outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M5 12h14"/><path d="M12 5v14"/>
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
                        <div class="flex items-center gap-2 ml-auto">
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
<?php echo mobooking_render_service_option_template('{option_idx}', []); // Pass empty array for template defaults ?>
</script>

<script type="text/template" id="mobooking-choice-item-template">
<?php echo mobooking_render_service_option_choice_item_template(); ?>
</script>

<script>
// Enhanced JavaScript for Shadcn UI interactions
document.addEventListener('DOMContentLoaded', function() {
    const presetSvgIcons = <?php echo json_encode($mobooking_preset_svg_icons); ?>;
    const defaultSvgIconKey = '<?php echo esc_js($default_svg_icon_key); ?>';

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

    // Icon selection functionality
    const iconPreviewElement = document.getElementById('mobooking-service-icon-preview');
    const iconInputElement = document.getElementById('mobooking-service-icon-value');
    const presetIconItems = document.querySelectorAll('.mobooking-preset-icon-item');

    presetIconItems.forEach(item => {
        item.addEventListener('click', function() {
            const iconKey = this.dataset.iconKey;
            const selectedSvg = presetSvgIcons[iconKey] || presetSvgIcons[defaultSvgIconKey];
            
            if (iconPreviewElement && iconInputElement) {
                iconPreviewElement.innerHTML = selectedSvg;
                iconInputElement.value = iconKey;
            }

            presetIconItems.forEach(i => i.classList.remove('border-primary', 'ring-2', 'ring-primary'));
            this.classList.add('border-primary', 'ring-2', 'ring-primary');
        });
    });

    // Remove icon functionality
    const removeIconBtn = document.getElementById('mobooking-remove-service-icon-btn');
    if (removeIconBtn && iconPreviewElement && iconInputElement) {
        removeIconBtn.addEventListener('click', function() {
            iconPreviewElement.innerHTML = presetSvgIcons[defaultSvgIconKey];
            iconInputElement.value = '';
            presetIconItems.forEach(i => i.classList.remove('border-primary', 'ring-2', 'ring-primary'));
        });
    }

    // Set initial selected state for preset icon
    if (iconInputElement && iconInputElement.value) {
        const currentSelectedKey = iconInputElement.value;
        presetIconItems.forEach(item => {
            if(item.dataset.iconKey === currentSelectedKey) {
                item.classList.add('border-primary', 'ring-2', 'ring-primary');
            }
        });
    }

    const optionsListContainer = document.getElementById('mobooking-service-options-list');
    if (optionsListContainer) {
        // Event Delegation for Remove Option, Remove Choice, and Option Row Toggle
        optionsListContainer.addEventListener('click', function(e) {
            // Remove Option Button
            const removeOptionBtn = e.target.closest('.mobooking-remove-option-btn');
            if (removeOptionBtn) {
                const optionRow = removeOptionBtn.closest('.mobooking-service-option-row');
                if (optionRow) {
                    if (confirm('<?php esc_html_e('Are you sure you want to remove this option?', 'mobooking'); ?>')) {
                        optionRow.remove();
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
                return;
            }

            // Remove Choice Button
            const removeChoiceBtn = e.target.closest('.mobooking-remove-choice-btn');
            if (removeChoiceBtn) {
                const choiceItem = removeChoiceBtn.closest('.mobooking-choice-item');
                if (choiceItem) {
                    choiceItem.remove();
                }
                return;
            }

            // Option Row Trigger for Collapse/Expand
            const optionRowTrigger = e.target.closest('.mobooking-option-row-trigger');
            if (optionRowTrigger) {
                const contentId = optionRowTrigger.getAttribute('aria-controls');
                const contentElement = document.getElementById(contentId);
                const chevron = optionRowTrigger.querySelector('.mobooking-option-chevron');

                if (contentElement) {
                    const isExpanded = optionRowTrigger.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        contentElement.classList.add('hidden-by-collapse');
                        optionRowTrigger.setAttribute('aria-expanded', 'false');
                        if(chevron) chevron.classList.remove('rotate-180');
                        optionRowTrigger.classList.remove('border-b');
                    } else {
                        contentElement.classList.remove('hidden-by-collapse');
                        optionRowTrigger.setAttribute('aria-expanded', 'true');
                        if(chevron) chevron.classList.add('rotate-180');
                        optionRowTrigger.classList.add('border-b');
                    }
                }
            }
        });

        // MutationObserver for newly added option rows
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.matches('.mobooking-service-option-row')) {
                            const trigger = node.querySelector('.mobooking-option-row-trigger');
                            const content = node.querySelector('.mobooking-service-option-row-content');
                            const chevron = node.querySelector('.mobooking-option-chevron');
                            if (trigger && content && chevron) {
                                trigger.setAttribute('aria-expanded', 'true'); // New rows default to expanded
                                content.classList.remove('hidden-by-collapse');
                                chevron.classList.add('rotate-180');
                                trigger.classList.add('border-b');
                            }
                        }
                    });
                }
            });
        });
        observer.observe(optionsListContainer, { childList: true });
    }


    // Form validation enhancement
    const form = document.getElementById('mobooking-service-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            showFeedback('', 'clear');
            form.querySelectorAll('.field-error-message').forEach(msg => msg.remove());

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(field => {
                let fieldWrapper = field.closest('.form-group') || field.closest('.styled-radio-btn')?.parentNode.closest('.form-group') || field.parentNode;
                field.classList.remove('border-destructive');
                const fieldId = field.id || field.name.replace(/\[\]/g, '').replace(/\[/g, '_').replace(/\]/g, ''); // Create a usable ID for radios

                const existingError = fieldWrapper.querySelector('.field-error-message.for-' + fieldId);
                if (existingError) existingError.remove();

                if (field.closest('.mobooking-service-option-row-content.hidden-by-collapse')) return; // Skip hidden by collapse
                if (field.closest('#mobooking-service-option-template') || field.closest('#mobooking-choice-item-template')) return;

                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-destructive');
                    const errorMessage = document.createElement('span');
                    errorMessage.className = 'text-xs text-destructive mt-1 block field-error-message for-' + fieldId;
                    errorMessage.textContent = field.dataset.errorMessage || '<?php esc_html_e('This field is required.', 'mobooking'); ?>';

                    let targetElement = field.type === 'radio' ? field.closest('.flex-wrap') || field.parentNode : field;
                     if (targetElement.parentNode.classList.contains('flex') && !targetElement.classList.contains('styled-radio-btn')) { // For currency input
                         targetElement = targetElement.parentNode;
                    }
                    targetElement.parentNode.insertBefore(errorMessage, targetElement.nextSibling);
                    if (!firstInvalidField) firstInvalidField = field;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showFeedback('<?php esc_html_e('Please fill in all required fields. Check the highlighted fields below.', 'mobooking'); ?>', 'error');
                if (firstInvalidField) firstInvalidField.focus();
            }
        });
    }

    function showFeedback(message, type = 'info') {
        const feedback = document.getElementById('mobooking-service-form-feedback');
        if (feedback) {
            if (type === 'clear' || !message) {
                feedback.classList.add('hidden');
                feedback.textContent = '';
                feedback.className = 'hidden';
                return;
            }
            feedback.className = `p-3 rounded-md text-sm font-medium ${
                type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                'bg-blue-50 text-blue-800 border border-blue-200'
            }`;
            feedback.textContent = message;
            feedback.classList.remove('hidden');
            
            if (type === 'success' || (type ==='info' && message)) {
                setTimeout(() => { feedback.classList.add('hidden'); }, 5000);
            }
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[name]"]') && e.target.closest('.mobooking-service-option-row')) {
            const optionRow = e.target.closest('.mobooking-service-option-row');
            const titleElement = optionRow.querySelector('.mobooking-option-title');
            if (titleElement) titleElement.textContent = e.target.value || '<?php esc_html_e('Untitled Option', 'mobooking'); ?>';
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('.mobooking-price-impact-type-radio')) {
            const selectedRadio = e.target;
            const radioGroup = selectedRadio.closest('.mobooking-price-impact-type-radio-group');
            if (radioGroup) {
                radioGroup.querySelectorAll('label.styled-radio-btn').forEach(label => {
                    label.classList.remove('border-primary', 'ring-2', 'ring-primary', 'bg-primary-50');
                    label.classList.add('border-gray-300');
                    if (label.contains(selectedRadio)) {
                        label.classList.add('border-primary', 'ring-2', 'ring-primary', 'bg-primary-50');
                        label.classList.remove('border-gray-300');
                    }
                });
            }
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('.mobooking-option-type')) {
            const selectedRadio = e.target;
            const optionRow = selectedRadio.closest('.mobooking-service-option-row');
            const valuesField = optionRow.querySelector('.mobooking-option-values-field');
            const selectedType = selectedRadio.value;
            const radioGroup = selectedRadio.closest('.mobooking-option-type-radio-group');

            if (radioGroup) {
                radioGroup.querySelectorAll('label.styled-radio-btn').forEach(label => {
                    label.classList.remove('border-primary', 'ring-2', 'ring-primary', 'bg-primary-50');
                    label.classList.add('border-gray-300');
                    if (label.contains(selectedRadio)) {
                        label.classList.add('border-primary', 'ring-2', 'ring-primary', 'bg-primary-50');
                        label.classList.remove('border-gray-300');
                    }
                });
            }
            
            if (valuesField) {
                valuesField.style.display = ['select', 'radio', 'checkbox'].includes(selectedType) ? 'block' : 'none';
            }
        }
    });

    const imageUploadBtn = document.getElementById('mobooking-trigger-service-image-upload-btn');
    const imageUploadInput = document.getElementById('mobooking-service-image-upload');
    const imagePreview = document.getElementById('mobooking-service-image-preview');
    const imageUrlInput = document.getElementById('mobooking-service-image-url-value');
    
    if (imageUploadBtn && imageUploadInput) {
        imageUploadBtn.addEventListener('click', () => imageUploadInput.click());
        imageUploadInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    if (imagePreview) imagePreview.src = ev.target.result;
                    if (imageUrlInput) imageUrlInput.value = ev.target.result; // For actual upload, this would be an AJAX call
                };
                reader.readAsDataURL(file);
            }
        });
    }

    const removeImageBtn = document.getElementById('mobooking-remove-service-image-btn');
    if (removeImageBtn && imagePreview && imageUrlInput) {
        removeImageBtn.addEventListener('click', () => {
            imagePreview.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Ctext%20x%3D%2275%22%20y%3D%2280%22%20text-anchor%3D%22middle%22%20font-size%3D%2212%22%20fill%3D%22%23AAAAAA%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';
            imageUrlInput.value = '';
        });
    }

    const cancelBtn = document.getElementById('mobooking-cancel-service-edit-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (confirm('<?php esc_html_e('Are you sure you want to cancel? Any unsaved changes will be lost.', 'mobooking'); ?>')) {
                window.location.href = '<?php echo esc_url(home_url('/dashboard/services/')); ?>';
            }
        });
    }

    document.addEventListener('input', function(e) {
        if (e.target.matches('textarea.form-textarea')) {
            e.target.style.height = 'auto';
            e.target.style.height = e.target.scrollHeight + 'px';
        }
    });
    document.querySelectorAll('textarea.form-textarea').forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    });

    // Initial setup for existing option rows to be collapsible
    document.querySelectorAll('.mobooking-service-option-row').forEach(optionRow => {
        const trigger = optionRow.querySelector('.mobooking-option-row-trigger');
        const content = optionRow.querySelector('.mobooking-service-option-row-content');
        const chevron = optionRow.querySelector('.mobooking-option-chevron');
        if (trigger && content && chevron) { // Ensure all parts are there
            // Default to expanded for existing rows as well, matching new rows
            trigger.setAttribute('aria-expanded', 'true');
            content.classList.remove('hidden-by-collapse');
            chevron.classList.add('rotate-180');
            // Ensure border is present if expanded
             if (!trigger.classList.contains('border-b') && trigger.getAttribute('aria-expanded') === 'true') {
                trigger.classList.add('border-b');
            }
        }
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
    .option-row { /* Ensure option rows themselves don't have extra padding if header is now the button */
      padding: 0;
    }
    /* .option-header is now part of .mobooking-option-row-trigger or replaced by it */

     .mobooking-option-row-trigger {
        background: none;
        /* border: none; Replaced by border-b on trigger itself for expand/collapse state */
        /* padding: 0; p-4 is applied directly */
        width: 100%;
        text-align: left;
        cursor: pointer;
    }
    .mobooking-option-chevron { /* Default state: pointing down means content visible */
        transition: transform 0.15s ease-in-out;
    }
    /* Chevron pointing up when collapsed (trigger's aria-expanded="false") */
    .mobooking-option-row-trigger[aria-expanded="false"] .mobooking-option-chevron {
        transform: rotate(0deg);
    }
    /* Chevron pointing down when expanded (trigger's aria-expanded="true") */
    .mobooking-option-row-trigger[aria-expanded="true"] .mobooking-option-chevron {
        transform: rotate(180deg);
    }


    .mobooking-service-option-row-content.hidden-by-collapse {
        display: none;
    }

    .styled-radio-btn { /* Container label */
        transition: all 0.15s ease-in-out;
        min-width: 80px; /* Ensure some minimum width */
        text-align: center;
    }
    .styled-radio-btn:has(input:checked) { /* Modern CSS to style based on hidden input state */
        border-color: hsl(221.2 83.2% 53.3%); /* primary */
        background-color: hsl(221.2 83.2% 53.3% / 0.05); /* primary-50 equivalent */
        box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3%); /* ring-2 ring-primary */
    }
    .styled-radio-btn.selected-radio-label { /* Fallback for JS-driven selection, if :has is not fully relied upon */
        border-color: hsl(221.2 83.2% 53.3%);
        background-color: hsl(221.2 83.2% 53.3% / 0.05);
        box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3%);
    }
    .styled-radio-btn:hover {
            border-color: hsl(221.2 83.2% 48%); /* primary-hover */
    }
    .styled-radio-btn .icon-wrapper svg {
        width: 1.5rem; /* w-6 */
        height: 1.5rem; /* h-6 */
        margin-bottom: 0.25rem; /* mb-1 */
    }
    .styled-radio-btn .text-label {
        font-size: 0.75rem; /* text-xs */
        font-weight: 500; /* font-medium */
    }
    .styled-radio-btn:focus-within { /* Accessibility: highlight container when hidden radio has focus */
            border-color: hsl(221.2 83.2% 53.3%);
            box-shadow: 0 0 0 2px hsl(221.2 83.2% 53.3%);
    }


    @media (max-width: 768px) { /* Target md breakpoint for choice item grid specifically */
        #mobooking-service-options-list .grid-cols-1.md\\:grid-cols-2,
        #mobooking-service-option-template .grid-cols-1.md\\:grid-cols-2 {
            grid-template-columns: 1fr; /* Stack option name/type on smaller screens */
        }
        .mobooking-option-type-radio-group,
        .mobooking-price-impact-type-radio-group { /* Make radio buttons wrap more aggressively */
            /* Using flex-wrap, so grid-template-columns is not needed here for wrapping */
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