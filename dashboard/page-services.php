<?php
/**
 * Dashboard Page: Services
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate the Services class
$services_manager = new \MoBooking\Classes\Services();
$user_id = get_current_user_id();

$default_args = [
    'number' => 20, // Items per page
    'offset' => 0,  // Start from the first page
    'status' => null, // Get all statuses by default
    'orderby' => 'name',
    'order' => 'ASC',
    // category_filter and search_query will be empty for initial load
];
$services_result = $services_manager->get_services_by_user($user_id, $default_args);

$services_list = $services_result['services'];
$total_services = $services_result['total_count'];
$per_page = $services_result['per_page'];
$current_page = $services_result['current_page'];
$total_pages = ceil($total_services / $per_page);

// Nonce for JS operations (services and options)
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');
?>
<div style="display: flex; justify-content: space-between; align-items: center;">
    <h1><?php esc_html_e('Manage Your Services', 'mobooking'); ?></h1>
    <button id="mobooking-add-new-service-btn" class="button button-primary"><?php esc_html_e('Add New Service', 'mobooking'); ?></button>
</div>

<!-- Modal Backdrop -->
<div id="mobooking-service-form-modal-backdrop"></div> <!-- style attribute removed -->

<!-- Add/Edit Service Form (Modal) -->
<div id="mobooking-service-form-container" style="display:none; margin-top:20px; padding:20px; background:#fff; border:1px solid #ccd0d4; max-width: 600px;"> <!-- Reverted to original style, CSS will handle modal positioning and appearance -->
    <h2 id="mobooking-service-form-title"><?php esc_html_e('Add New Service', 'mobooking'); ?></h2>
    <form id="mobooking-service-form">
        <input type="hidden" id="mobooking-service-id" name="service_id" value="">
        <p>
            <label for="mobooking-service-name"><?php esc_html_e('Service Name:', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-service-name" name="name" required class="widefat">
        </p>
        <p>
            <label for="mobooking-service-description"><?php esc_html_e('Description:', 'mobooking'); ?></label><br>
            <textarea id="mobooking-service-description" name="description" class="widefat" rows="4"></textarea>
        </p>
        <p>
            <label for="mobooking-service-price"><?php esc_html_e('Price:', 'mobooking'); ?></label><br>
            <input type="number" id="mobooking-service-price" name="price" step="0.01" required class="widefat">
        </p>
        <p>
            <label for="mobooking-service-duration"><?php esc_html_e('Duration (minutes):', 'mobooking'); ?></label><br>
            <input type="number" id="mobooking-service-duration" name="duration" step="1" required class="widefat">
        </p>
        <p>
            <label for="mobooking-service-category"><?php esc_html_e('Category:', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-service-category" name="category" class="widefat">
        </p>
        <p>
            <label for="mobooking-service-icon"><?php esc_html_e('Icon (Dashicon class e.g., dashicons-admin-tools):', 'mobooking'); ?></label><br>
            <input type="text" id="mobooking-service-icon" name="icon" class="widefat">
        </p>
        <p>
            <label for="mobooking-service-image-url"><?php esc_html_e('Image URL:', 'mobooking'); ?></label><br>
            <input type="url" id="mobooking-service-image-url" name="image_url" class="widefat">
        </p>
        <p>
            <label for="mobooking-service-status"><?php esc_html_e('Status:', 'mobooking'); ?></label><br>
            <select id="mobooking-service-status" name="status" class="widefat">
                <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
                <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
            </select>
        </p>

        <div id="mobooking-service-options-section" style="margin-top: 20px; padding-top:15px; border-top:1px dashed #ccc;">
             <h3><?php esc_html_e('Service Options', 'mobooking'); ?></h3>
             <!-- Options UI will go here in a later sub-step -->
             <div id="mobooking-service-options-list">
                <p><em><?php esc_html_e('Service options management will be enabled after saving the service.', 'mobooking'); ?></em></p>
             </div>
             <button type="button" id="mobooking-add-service-option-btn" class="button" style="margin-top:10px;" disabled><?php esc_html_e('Add Option', 'mobooking'); ?></button>
        </div>

        <script type="text/template" id="mobooking-service-option-template">
            <div class="mobooking-service-option-row" style="border: 1px dashed #ddd; padding: 10px; margin-top: 10px; border-radius:3px;">
                <input type="hidden" name="options[][option_id]" value=""> <!-- For existing option ID during edit -->
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
                </p>
                <div class="mobooking-option-values-field" style="display:none; margin-bottom:10px;">
                    <label><?php esc_html_e('Option Choices (JSON format):', 'mobooking'); ?></label><br>
                    <textarea name="options[][option_values]" class="widefat" rows="3" placeholder='[{"value":"opt1","label":"Choice 1"},{"value":"opt2","label":"Choice 2"}]'></textarea>
                    <small><?php esc_html_e('Example: [{"value":"red","label":"Red Color"}, {"value":"blue","label":"Blue Color","price_adjust":5.00}] (price_adjust is optional per choice)', 'mobooking'); ?></small>
                </div>
                <p style="margin-bottom: 5px;">
                    <label><input type="checkbox" name="options[][is_required_cb]" value="1"> <?php esc_html_e('Required?', 'mobooking'); ?></label>
                    <input type="hidden" name="options[][is_required]" value="0"> <!-- Actual value set by JS based on checkbox -->
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
                </p>
                <input type="hidden" name="options[][sort_order]" value="0">
                <button type="button" class="button mobooking-remove-service-option-btn button-link-delete"><?php esc_html_e('Remove Option', 'mobooking'); ?></button>
            </div>
        </script>

        <hr style="margin-top:20px;">
        <button type="submit" class="button button-primary"><?php esc_html_e('Save Service', 'mobooking'); ?></button>
        <button type="button" id="mobooking-cancel-service-form" class="button"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
        <div id="mobooking-service-form-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
    </form>
</div>

<h2 style="margin-top:30px;"><?php esc_html_e('Your Services', 'mobooking'); ?></h2>
<!-- Add filter controls here later if needed -->
<div id="mobooking-services-list-container">
    <?php if ( ! empty( $services_list ) ) : ?>
        <?php foreach ( $services_list as $service ) : ?>
            <?php if (is_array($service)) : // Ensure $service is an array ?>
            <div class="mobooking-service-item" data-service-id="<?php echo esc_attr( $service['service_id'] ); ?>" style="border:1px solid #ccd0d4; padding:15px; margin-bottom:10px; background:#fff; border-radius:4px;">
                <h3 style="margin-top:0;"><?php echo esc_html( $service['name'] ); ?></h3>
                <p><strong><?php esc_html_e('Price:', 'mobooking'); ?></strong> <span class="service-price"><?php echo esc_html( \MoBooking\Classes\Utils::format_currency( $service['price'] ) ); ?></span></p>
                <p><strong><?php esc_html_e('Duration:', 'mobooking'); ?></strong> <span class="service-duration"><?php echo esc_html( $service['duration'] ); ?></span> <?php esc_html_e('min', 'mobooking'); ?></p>
                <p><strong><?php esc_html_e('Status:', 'mobooking'); ?></strong> <span class="service-status"><?php echo esc_html( ucfirst( $service['status'] ) ); ?></span></p>
                <p style="margin-top:15px;">
                    <button class="button mobooking-edit-service-btn" data-id="<?php echo esc_attr( $service['service_id'] ); ?>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
                    <button class="button button-link-delete mobooking-delete-service-btn" data-id="<?php echo esc_attr( $service['service_id'] ); ?>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
                </p>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else : ?>
        <p><?php esc_html_e('No services found. Click "Add New Service" to create your first service.', 'mobooking'); ?></p>
    <?php endif; ?>
</div>

<div id="mobooking-services-pagination-container" style="margin-top: 20px; text-align: center;">
    <?php
    if ($total_pages > 1) {
        echo paginate_links(array(
            'base' => '#%#%', // Base for the links, not a real URL as JS will handle it.
            'format' => '?paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => __('&laquo; Prev'),
            'next_text' => __('Next &raquo;'),
            'add_fragment' => '', // Avoids adding # HASH to the URL
            'type' => 'list' // Outputs an unordered list.
        ));
    }
    ?>
</div>

<script type="text/template" id="mobooking-service-item-template">
    <div class="mobooking-service-item" data-service-id="<%= service_id %>" style="border:1px solid #ccd0d4; padding:15px; margin-bottom:10px; background:#fff; border-radius:4px;">
        <h3 style="margin-top:0;"><%= name %></h3>
        <p><strong><?php esc_html_e('Price:', 'mobooking'); ?></strong> <span class="service-price"><%= formatted_price %></span></p>
        <p><strong><?php esc_html_e('Duration:', 'mobooking'); ?></strong> <span class="service-duration"><%= duration %></span> <?php esc_html_e('min', 'mobooking'); ?></p>
        <p><strong><?php esc_html_e('Status:', 'mobooking'); ?></strong> <span class="service-status"><%= display_status %></span></p>
        <p style="margin-top:15px;">
            <button class="button mobooking-edit-service-btn" data-id="<%= service_id %>"><?php esc_html_e('Edit', 'mobooking'); ?></button>
            <button class="button button-link-delete mobooking-delete-service-btn" data-id="<%= service_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </p>
    </div>
</script>

<script type="text/javascript">
    // Pass initial services data (including options) to JavaScript for caching
    // This ensures the edit form can be populated without an immediate AJAX call for initially loaded items.
    var mobooking_initial_services_list_for_cache = <?php echo wp_json_encode($services_list); ?>;
</script>