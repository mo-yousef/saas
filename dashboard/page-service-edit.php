<?php
/**
 * Dashboard Page: Add/Edit Service
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
$service_price = ''; // Keep as string for input field, validation will handle numeric
$service_duration = ''; // Keep as string
$service_icon = '';
$service_image_url = '';
$service_status = 'active'; // Default status
$service_options_data = []; // Array to hold option data

$error_message = '';

// 3. Fetch Service Data in Edit Mode
$user_id = get_current_user_id(); // Defined early for settings
// Fetch business settings for currency display
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'];
$currency_pos = $biz_settings['biz_currency_position'];

if ( $edit_mode && $service_id > 0 ) {
    // $user_id is already defined
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
            // Ensure options is an array, default to empty if not set or not array
            $service_options_data = isset($service_data['options']) && is_array($service_data['options']) ? $service_data['options'] : [];
        } else {
            $error_message = __( 'Service not found or you do not have permission to edit it.', 'mobooking' );
            // If service not found, probably don't want to proceed with form display
        }
    } else {
        $error_message = __( 'Error: Services manager class not found.', 'mobooking' );
    }
}

// Nonce for JS operations (services and options) - Placed after initial data load
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');
?>

<div class="wrap mobooking-service-edit-page">
    <h1 id="mobooking-service-edit-page-title"><?php echo esc_html( $page_title ); ?></h1>

    <?php if ( ! empty( $error_message ) ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error_message ); ?></p></div>
        <?php
        // If there was a critical error (e.g. service not found in edit mode),
        // we might want to stop rendering the rest of the form.
        if ( $edit_mode && ! $service_data ) { // $service_data would be false/null if not found
            echo '</div>'; // Close .wrap
            return; // Stop further rendering
        }
        ?>
    <?php endif; ?>

    <!-- Add/Edit Service Form -->
    <div id="mobooking-service-form-container" style="margin-top:20px; padding:20px; background:#fff; border:1px solid #ccd0d4; max-width: 700px;">
        <form id="mobooking-service-form">
            <input type="hidden" id="mobooking-service-id" name="service_id" value="<?php echo esc_attr( $edit_mode ? $service_id : '' ); ?>">
            <p>
                <label for="mobooking-service-name"><?php esc_html_e('Service Name:', 'mobooking'); ?></label><br>
                <input type="text" id="mobooking-service-name" name="name" value="<?php echo esc_attr( $service_name ); ?>" required class="widefat">
            </p>
            <p>
                <label for="mobooking-service-description"><?php esc_html_e('Description:', 'mobooking'); ?></label><br>
                <textarea id="mobooking-service-description" name="description" class="widefat" rows="4"><?php echo esc_textarea( $service_description ); ?></textarea>
            </p>
            <p>
                <label for="mobooking-service-price"><?php esc_html_e('Price:', 'mobooking'); ?></label><br>
                <div class="mobooking-price-input-wrapper" style="display: flex; align-items: center;">
                    <?php if ($currency_pos === 'before') : ?>
                        <span class="mobooking-currency-symbol" style="margin-right: 5px;"><?php echo esc_html($currency_symbol); ?></span>
                    <?php endif; ?>
                    <input type="number" id="mobooking-service-price" name="price" value="<?php echo esc_attr( $service_price ); ?>" step="0.01" required class="widefat" style="flex-grow: 1;">
                    <?php if ($currency_pos === 'after') : ?>
                        <span class="mobooking-currency-symbol" style="margin-left: 5px;"><?php echo esc_html($currency_symbol); ?></span>
                    <?php endif; ?>
                </div>
            </p>
            <p>
                <label for="mobooking-service-duration"><?php esc_html_e('Duration (minutes):', 'mobooking'); ?></label><br>
                <input type="number" id="mobooking-service-duration" name="duration" value="<?php echo esc_attr( $service_duration ); ?>" step="1" required class="widefat">
            </p>
            <p>
                <label for="mobooking-service-icon"><?php esc_html_e('Icon (Dashicon class e.g., dashicons-admin-tools):', 'mobooking'); ?></label><br>
                <input type="text" id="mobooking-service-icon" name="icon" value="<?php echo esc_attr( $service_icon ); ?>" class="widefat">
            </p>
            <p>
                <label><?php esc_html_e('Service Icon:', 'mobooking'); ?></label><br>
                <div id="mobooking-service-icon-preview" style="width: 64px; height: 64px; border: 1px dashed #ccc; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;">
                    <!-- Preview will be populated by JS -->
                    <span class="mobooking-no-icon-text"><?php esc_html_e('None', 'mobooking'); ?></span>
                </div>
                <input type="hidden" id="mobooking-service-icon-value" name="icon" value="<?php echo esc_attr( $service_icon ); ?>">

                <button type="button" id="mobooking-remove-service-icon-btn" class="button" style="<?php echo empty($service_icon) ? 'display:none;' : ''; ?>"><?php esc_html_e('Remove Icon', 'mobooking'); ?></button>

                <div style="margin-top: 15px;">
                    <strong><?php esc_html_e('Preset Icons:', 'mobooking'); ?></strong>
                    <div id="mobooking-preset-icons-wrapper" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px; margin-bottom: 15px;">
                        <?php
                        // Ensure Services class is available. It should be due to earlier usage.
                        if (class_exists('\MoBooking\Classes\Services')) {
                            $services_manager_for_icons = new \MoBooking\Classes\Services();
                            $presets = $services_manager_for_icons->get_all_preset_icons();
                            foreach ($presets as $key => $svg_content) {
                                echo '<div class="mobooking-preset-icon-item" data-preset-key="preset:' . esc_attr($key) . '" title="' . esc_attr(ucfirst($key)) . '" style="width: 48px; height: 48px; border: 1px solid #eee; cursor: pointer; padding: 5px; box-sizing: border-box;">' . $svg_content . '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div>
                    <strong><?php esc_html_e('Upload Custom SVG Icon:', 'mobooking'); ?></strong><br>
                    <input type="file" id="mobooking-service-icon-upload" accept=".svg, image/svg+xml" style="margin-top: 5px;">
                    <small><?php esc_html_e('Upload an SVG file. Max size: 100KB. Ensure it is sanitized.', 'mobooking'); ?></small>
                </div>
            </p>
            <p>
                <?php
                // Define a placeholder image URL (e.g., from plugin assets or a generic one)
                // Since direct file creation isn't possible, using the data URI fallback directly.
                $actual_placeholder_url = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_17ea872690d%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_17ea872690d%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2250.00303268432617%22%20y%3D%2279.5%22%3E150x150%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
                $current_image_to_display = !empty($service_image_url) ? esc_url($service_image_url) : $actual_placeholder_url;
                ?>
                <label for="mobooking-service-image-upload"><?php esc_html_e('Service Image:', 'mobooking'); ?></label><br>
                <img id="mobooking-service-image-preview" src="<?php echo $current_image_to_display; ?>" alt="<?php esc_attr_e('Service Image Preview', 'mobooking'); ?>" style="width: 150px; height: 150px; border: 1px solid #ccc; margin-bottom: 10px; object-fit: cover; background-color: #f9f9f9;">
                <input type="hidden" id="mobooking-service-image-url-value" name="image_url" value="<?php echo esc_attr( $service_image_url ); ?>">

                <div>
                    <input type="file" id="mobooking-service-image-upload" accept="image/jpeg, image/png, image/gif, image/webp" style="display: none;">
                    <button type="button" id="mobooking-trigger-service-image-upload-btn" class="button"><?php esc_html_e('Upload Image', 'mobooking'); ?></button>
                    <button type="button" id="mobooking-remove-service-image-btn" class="button button-link-delete" style="<?php echo empty($service_image_url) ? 'display:none;' : ''; ?>"><?php esc_html_e('Remove Image', 'mobooking'); ?></button>
                </div>
                <small><?php esc_html_e('Recommended size: 800x600px. Max file size: 2MB.', 'mobooking'); ?></small>
            </p>
            <p>
                <label for="mobooking-service-status-toggle"><?php esc_html_e('Status:', 'mobooking'); ?></label><br>
                <div class="mobooking-toggle-switch <?php echo ($service_status === 'active' ? 'active' : ''); ?>" id="mobooking-service-status-toggle" tabindex="0" role="switch" aria-checked="<?php echo ($service_status === 'active' ? 'true' : 'false'); ?>">
                    <div class="mobooking-toggle-knob"></div>
                </div>
                <input type="hidden" id="mobooking-service-status" name="status" value="<?php echo esc_attr( $service_status ); ?>">
                <span id="mobooking-service-status-text" style="margin-left: 10px; vertical-align: middle;">
                    <?php echo ($service_status === 'active' ? esc_html__('Active', 'mobooking') : esc_html__('Inactive', 'mobooking')); ?>
                </span>
            </p>

            <!-- Service Options Section -->
            <div id="mobooking-service-options-section">
                 <h3><?php esc_html_e('Service Options', 'mobooking'); ?></h3>
                 <div id="mobooking-service-options-list">
                    <?php if ( $edit_mode && ! empty( $service_options_data ) ) : ?>
                        <?php foreach ( $service_options_data as $option_idx => $option ) : ?>
                            <div class="mobooking-service-option-row">
                                <span class="mobooking-option-drag-handle">&#x2630;</span>
                                <div class="mobooking-service-option-row-content">
                                    <input type="hidden" name="options[<?php echo $option_idx; ?>][option_id]" value="<?php echo esc_attr( $option['option_id'] ); ?>">
                                    <p>
                                        <label style="font-weight:bold;"><?php esc_html_e('Option Name:', 'mobooking'); ?></label><br>
                                    <input type="text" name="options[<?php echo $option_idx; ?>][name]" value="<?php echo esc_attr( $option['name'] ); ?>" class="widefat" required>
                                </p>
                                <p>
                                    <label><?php esc_html_e('Description:', 'mobooking'); ?></label><br>
                                    <textarea name="options[<?php echo $option_idx; ?>][description]" class="widefat" rows="2"><?php echo esc_textarea( $option['description'] ); ?></textarea>
                                </p>
                                <p>
                                    <label><?php esc_html_e('Type:', 'mobooking'); ?></label><br>
                                    <select name="options[<?php echo $option_idx; ?>][type]" class="mobooking-option-type widefat">
                                        <option value="checkbox" <?php selected( $option['type'], 'checkbox' ); ?>><?php esc_html_e('Checkbox (Single)', 'mobooking'); ?></option>
                                        <option value="text" <?php selected( $option['type'], 'text' ); ?>><?php esc_html_e('Text Input', 'mobooking'); ?></option>
                                        <option value="number" <?php selected( $option['type'], 'number' ); ?>><?php esc_html_e('Number Input', 'mobooking'); ?></option>
                                        <option value="select" <?php selected( $option['type'], 'select' ); ?>><?php esc_html_e('Dropdown Select', 'mobooking'); ?></option>
                                        <option value="radio" <?php selected( $option['type'], 'radio' ); ?>><?php esc_html_e('Radio Buttons', 'mobooking'); ?></option>
                                        <option value="textarea" <?php selected( $option['type'], 'textarea' ); ?>><?php esc_html_e('Text Area', 'mobooking'); ?></option>
                                        <option value="quantity" <?php selected( $option['type'], 'quantity' ); ?>><?php esc_html_e('Quantity Input', 'mobooking'); ?></option>
                                    </select>
                                    <div class="mobooking-custom-radio-group-placeholder"></div> <!-- JS will populate this based on select -->
                                </p>
                                <div class="mobooking-option-values-field" style="<?php echo ( in_array( $option['type'], ['select', 'radio'] ) ? '' : 'display:none;' ); ?> margin-bottom:10px;">
                                    <label><?php esc_html_e('Option Choices:', 'mobooking'); ?></label>
                                    <div class="mobooking-choices-ui-container">
                                        <div class="mobooking-choices-list">
                                            <!-- JS will render choices here based on the textarea content -->
                                        </div>
                                        <button type="button" class="button mobooking-add-choice-btn"><?php esc_html_e('Add Choice', 'mobooking'); ?></button>
                                    </div>
                                    <textarea name="options[<?php echo $option_idx; ?>][option_values]" class="widefat" rows="2" placeholder='[{"value":"opt1","label":"Choice 1"}]'><?php
                                        $ov_json = is_array($option['option_values']) ? wp_json_encode($option['option_values']) : $option['option_values'];
                                        echo esc_textarea( $ov_json );
                                    ?></textarea>
                                    <small style="margin-top: 5px; display: block;"><?php esc_html_e('This data is auto-generated. Manage choices using the UI above. Example: [{"value":"red","label":"Red Color"}, {"value":"blue","label":"Blue Color","price_adjust":5.00}] (price_adjust is optional per choice)', 'mobooking'); ?></small>
                                </div>
                                <p style="margin-bottom: 5px;">
                                    <label><input type="checkbox" name="options[<?php echo $option_idx; ?>][is_required_cb]" value="1" <?php checked( $option['is_required'], '1' ); ?>> <?php esc_html_e('Required?', 'mobooking'); ?></label>
                                    <input type="hidden" name="options[<?php echo $option_idx; ?>][is_required]" value="<?php echo esc_attr( $option['is_required'] ); ?>"> <!-- JS updates this -->
                                </p>
                                <p>
                                    <label><?php esc_html_e('Price Impact Type:', 'mobooking'); ?></label><br>
                                    <select name="options[<?php echo $option_idx; ?>][price_impact_type]" class="mobooking-option-price-type widefat">
                                        <option value="" <?php selected( $option['price_impact_type'], '' ); ?>><?php esc_html_e('None', 'mobooking'); ?></option>
                                        <option value="fixed" <?php selected( $option['price_impact_type'], 'fixed' ); ?>><?php esc_html_e('Fixed Amount', 'mobooking'); ?></option>
                                        <option value="percentage" <?php selected( $option['price_impact_type'], 'percentage' ); ?>><?php esc_html_e('Percentage of Base Price', 'mobooking'); ?></option>
                                        <option value="multiply_value" <?php selected( $option['price_impact_type'], 'multiply_value' ); ?>><?php esc_html_e('Multiply by Quantity/Value (for Type=Quantity/Number)', 'mobooking'); ?></option>
                                    </select>
                                </p>
                                <div class="mobooking-option-price-value-field" style="<?php echo ( !empty( $option['price_impact_type'] ) ? '' : 'display:none;' ); ?> margin-bottom:10px;">
                                    <label><?php esc_html_e('Price Impact Value:', 'mobooking'); ?></label><br>
                                    <input type="number" name="options[<?php echo $option_idx; ?>][price_impact_value]" value="<?php echo esc_attr( $option['price_impact_value'] ); ?>" step="0.01" class="widefat">
                                </div> <!-- This closes .mobooking-option-price-value-field -->
                                <input type="hidden" name="options[<?php echo $option_idx; ?>][sort_order]" value="<?php echo esc_attr( $option['sort_order'] ); ?>">
                                <button type="button" class="button mobooking-remove-service-option-btn button-link-delete"><?php esc_html_e('Remove Option', 'mobooking'); ?></button>
                                </div> <!-- close mobooking-service-option-row-content -->
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <!-- Message for when there are no options (e.g., Add mode or Edit mode with no options yet) -->
                         <p class="mobooking-no-options-yet"><?php esc_html_e('No options added yet. Click "Add Option" to create one.', 'mobooking'); ?></p>
                    <?php endif; ?>
                 </div>
                 <button type="button" id="mobooking-add-service-option-btn" class="button">
                     <?php esc_html_e('Add Option', 'mobooking'); ?>
                 </button>
            </div>

            <!-- Script Templates for Options (these are for JS to use when adding NEW options) -->
            <script type="text/template" id="mobooking-service-option-template">
                <div class="mobooking-service-option-row">
                    <span class="mobooking-option-drag-handle">&#x2630;</span>
                    <div class="mobooking-service-option-row-content">
                        <input type="hidden" name="options[][option_id]" value=""> <!-- New options won't have an ID yet -->
                        <p>
                            <label style="font-weight:bold;"><?php esc_html_e('Option Name:', 'mobooking'); ?></label><br>
                        <input type="text" name="options[][name]" class="widefat" required>
                    </p>
                    <p>
                        <label><?php esc_html_e('Description:', 'mobooking'); ?></label><br>
                        <textarea name="options[][description]" class="widefat" rows="2"></textarea>
                    </p>
                    <p>
                        <label><?php esc_html_e('Type:', 'mobooking'); ?></label><br>
                        <select name="options[][type]" class="mobooking-option-type widefat">
                            <option value="checkbox"><?php esc_html_e('Checkbox (Single)', 'mobooking'); ?></option>
                            <option value="text"><?php esc_html_e('Text Input', 'mobooking'); ?></option>
                            <option value="number"><?php esc_html_e('Number Input', 'mobooking'); ?></option>
                            <option value="select"><?php esc_html_e('Dropdown Select', 'mobooking'); ?></option>
                            <option value="radio"><?php esc_html_e('Radio Buttons', 'mobooking'); ?></option>
                            <option value="textarea"><?php esc_html_e('Text Area', 'mobooking'); ?></option>
                            <option value="quantity"><?php esc_html_e('Quantity Input', 'mobooking'); ?></option>
                        </select>
                        <div class="mobooking-custom-radio-group-placeholder"></div>
                    </p>
                    <div class="mobooking-option-values-field" style="display:none; margin-bottom:10px;">
                        <label><?php esc_html_e('Option Choices:', 'mobooking'); ?></label>
                        <div class="mobooking-choices-ui-container">
                            <div class="mobooking-choices-list"></div>
                            <button type="button" class="button mobooking-add-choice-btn"><?php esc_html_e('Add Choice', 'mobooking'); ?></button>
                        </div>
                        <textarea name="options[][option_values]" class="widefat" rows="2" placeholder='[{"value":"opt1","label":"Choice 1"},{"value":"opt2","label":"Choice 2"}]'></textarea>
                        <small style="margin-top: 5px; display: block;"><?php esc_html_e('This data is auto-generated. Manage choices using the UI above. Example: [{"value":"red","label":"Red Color"}, {"value":"blue","label":"Blue Color","price_adjust":5.00}] (price_adjust is optional per choice)', 'mobooking'); ?></small>
                    </div>
                    <p style="margin-bottom: 5px;">
                        <label><input type="checkbox" name="options[][is_required_cb]" value="1"> <?php esc_html_e('Required?', 'mobooking'); ?></label>
                        <input type="hidden" name="options[][is_required]" value="0"> <!-- JS updates this based on checkbox -->
                    </p>
                    <p>
                        <label><?php esc_html_e('Price Impact Type:', 'mobooking'); ?></label><br>
                        <select name="options[][price_impact_type]" class="mobooking-option-price-type widefat">
                            <option value=""><?php esc_html_e('None', 'mobooking'); ?></option>
                            <option value="fixed"><?php esc_html_e('Fixed Amount', 'mobooking'); ?></option>
                            <option value="percentage"><?php esc_html_e('Percentage of Base Price', 'mobooking'); ?></option>
                            <option value="multiply_value"><?php esc_html_e('Multiply by Quantity/Value (for Type=Quantity/Number)', 'mobooking'); ?></option>
                        </select>
                    </p>
                    <div class="mobooking-option-price-value-field" style="display:none; margin-bottom:10px;">
                        <label><?php esc_html_e('Price Impact Value:', 'mobooking'); ?></label><br>
                        <input type="number" name="options[][price_impact_value]" step="0.01" class="widefat">
                    </div> <!-- This closes .mobooking-option-price-value-field -->
                    <input type="hidden" name="options[][sort_order]" value="0"> <!-- JS might need to manage this for new items if order matters on creation -->
                    <button type="button" class="button mobooking-remove-service-option-btn button-link-delete"><?php esc_html_e('Remove Option', 'mobooking'); ?></button>
                    </div> <!-- close mobooking-service-option-row-content -->
                </div>
            </script>

            <script type="text/template" id="mobooking-choice-item-template">
                <div class="mobooking-choice-item">
                    <span class="mobooking-choice-drag-handle">&#x2630;</span>
                    <input type="text" class="mobooking-choice-label" placeholder="<?php esc_attr_e('Label', 'mobooking'); ?>">
                    <input type="text" class="mobooking-choice-value" placeholder="<?php esc_attr_e('Value', 'mobooking'); ?>">
                    <input type="number" step="0.01" class="mobooking-choice-price-adjust" placeholder="<?php esc_attr_e('Price Adj.', 'mobooking'); ?>">
                    <button type="button" class="button-link mobooking-remove-choice-btn">&times;</button>
                </div>
            </script>
            <!-- End Script Templates -->

            <hr style="margin-top:20px;">
            <button type="submit" id="mobooking-save-service-btn" class="button button-primary"><?php esc_html_e('Save Service', 'mobooking'); ?></button>
            <button type="button" id="mobooking-cancel-service-edit-btn" class="button"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
            <div id="mobooking-service-form-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
        </form>
    </div>
</div>
<?php
// JavaScript for handling form submission, options dynamic rendering, etc.,
// is expected to be enqueued via functions.php (e.g., dashboard-services.js).
// That script will need to correctly initialize for the PHP-rendered options,
// particularly for the choices UI and custom radio buttons.
?>
