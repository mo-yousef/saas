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
$service_category = '';
$service_icon = '';
$service_image_url = '';
$service_status = 'active'; // Default status
$service_options_data = []; // Array to hold option data

$error_message = '';

// 3. Fetch Service Data in Edit Mode
if ( $edit_mode && $service_id > 0 ) {
    $user_id = get_current_user_id();
    if ( class_exists('\MoBooking\Classes\Services') ) {
        $services_manager = new \MoBooking\Classes\Services();
        $service_data = $services_manager->get_service( $service_id, $user_id );

        if ( $service_data && ! is_wp_error( $service_data ) ) {
            $service_name = $service_data['name'];
            $service_description = $service_data['description'];
            $service_price = $service_data['price'];
            $service_duration = $service_data['duration'];
            $service_category = $service_data['category'];
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
                <input type="number" id="mobooking-service-price" name="price" value="<?php echo esc_attr( $service_price ); ?>" step="0.01" required class="widefat">
            </p>
            <p>
                <label for="mobooking-service-duration"><?php esc_html_e('Duration (minutes):', 'mobooking'); ?></label><br>
                <input type="number" id="mobooking-service-duration" name="duration" value="<?php echo esc_attr( $service_duration ); ?>" step="1" required class="widefat">
            </p>
            <p>
                <label for="mobooking-service-category"><?php esc_html_e('Category:', 'mobooking'); ?></label><br>
                <input type="text" id="mobooking-service-category" name="category" value="<?php echo esc_attr( $service_category ); ?>" class="widefat">
            </p>
            <p>
                <label for="mobooking-service-icon"><?php esc_html_e('Icon (Dashicon class e.g., dashicons-admin-tools):', 'mobooking'); ?></label><br>
                <input type="text" id="mobooking-service-icon" name="icon" value="<?php echo esc_attr( $service_icon ); ?>" class="widefat">
            </p>
            <p>
                <label for="mobooking-service-image-url"><?php esc_html_e('Image URL:', 'mobooking'); ?></label><br>
                <input type="url" id="mobooking-service-image-url" name="image_url" value="<?php echo esc_attr( $service_image_url ); ?>" class="widefat">
            </p>
            <p>
                <label for="mobooking-service-status"><?php esc_html_e('Status:', 'mobooking'); ?></label><br>
                <select id="mobooking-service-status" name="status" class="widefat">
                    <option value="active" <?php selected( $service_status, 'active' ); ?>><?php esc_html_e('Active', 'mobooking'); ?></option>
                    <option value="inactive" <?php selected( $service_status, 'inactive' ); ?>><?php esc_html_e('Inactive', 'mobooking'); ?></option>
                </select>
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
