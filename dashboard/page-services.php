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

<style type="text/css">
    #mobooking-service-form-container input[type="text"],
    #mobooking-service-form-container input[type="number"],
    #mobooking-service-form-container input[type="url"],
    #mobooking-service-form-container textarea,
    #mobooking-service-form-container select {
        border: 1px solid #ccc;
        padding: 8px 10px;
        border-radius: 4px;
        box-sizing: border-box; /* Important for width calculations */
        margin-bottom: 10px; /* Add some space below inputs */
        font-family: sans-serif; /* Basic clean font */
    }

    #mobooking-service-form-container input[type="text"]:focus,
    #mobooking-service-form-container input[type="number"]:focus,
    #mobooking-service-form-container input[type="url"]:focus,
    #mobooking-service-form-container textarea:focus,
    #mobooking-service-form-container select:focus {
        border-color: #888;
        box-shadow: 0 0 3px rgba(0, 123, 255, 0.25);
        outline: none; /* Remove default outline */
    }

    /* The .widefat class in WordPress usually handles width, but we can ensure it's effective */
    #mobooking-service-form-container .widefat {
        width: 100%;
    }

    /* Make Service Options section more compact */
    #mobooking-service-options-section {
        margin-top: 15px !important; /* Reduced from 20px inline style */
        padding-top: 10px !important; /* Reduced from 15px inline style */
        /* border-top is already defined inline, but can be overridden if needed */
    }

    #mobooking-service-options-section h3 {
        margin-bottom: 10px; /* Reduce space below the section title */
    }

    /* Reduce spacing within each option row */
    /* These styles will apply to rows created from the template */
    #mobooking-service-form-container .mobooking-service-option-row {
        padding: 8px !important; /* Reduced from 10px inline style */
        margin-top: 8px !important; /* Reduced from 10px inline style */
    }

    #mobooking-service-form-container .mobooking-service-option-row p {
        margin-top: 5px;
        margin-bottom: 8px; /* Reduce space between paragraph elements */
    }

    #mobooking-service-form-container .mobooking-service-option-row p label {
        margin-bottom: 3px; /* Reduce space below labels */
        display: inline-block; /* Allows margin-bottom to take effect properly */
    }

    #mobooking-add-service-option-btn {
        margin-top: 15px !important; /* Ensure button has adequate spacing, overriding inline 10px */
    }

    /* Custom Radio Button Styles for Option Type */
    .mobooking-option-type { /* This is the original select element */
        display: none !important;
    }
    .mobooking-custom-radio-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px; /* Slightly reduced gap */
        margin-bottom: 10px;
        margin-top: 5px; /* Add some space above the radio group */
    }
    .mobooking-custom-radio-label {
        padding: 6px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.3s, border-color 0.3s;
        background-color: #f9f9f9; /* Default light background */
    }
    .mobooking-custom-radio-label:hover {
        background-color: #f0f0f0;
        border-color: #bbb;
    }
    .mobooking-custom-radio-label.selected {
        background-color: #0073aa; /* WordPress blue */
        color: white;
        border-color: #0073aa;
    }

    /* Styles for Option Choices UI */
    .mobooking-option-values-field textarea { /* The original textarea for JSON */
        display: none !important;
    }
    .mobooking-choices-ui-container {
        margin-top: 5px;
        padding: 8px;
        background-color: #fdfdfd; /* Light background for the UI container */
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    .mobooking-choices-list {
        margin-bottom: 10px;
        min-height: 20px; /* Placeholder space when empty */
        /* background-color: #fff; */ /* Optional: if list needs different bg */
    }
    .mobooking-add-choice-btn {
        margin-top: 5px;
    }

    .mobooking-choice-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        margin-bottom: 8px;
        background-color: #fff;
    }
    /* Inputs within a choice item should not have global bottom margin */
    .mobooking-choice-item input[type="text"],
    .mobooking-choice-item input[type="number"] {
        margin-bottom: 0 !important; 
    }
    .mobooking-choice-label { flex-grow: 1; }
    .mobooking-choice-value { flex-basis: 120px; }
    .mobooking-choice-price-adjust { flex-basis: 100px; }
    .mobooking-choice-drag-handle {
        cursor: move;
        font-size: 16px;
        color: #777;
        padding: 0 3px; /* Some padding around handle */
    }
    .mobooking-remove-choice-btn {
        color: #a00;
        text-decoration: none;
        font-size: 22px; /* Slightly larger for easier click */
        line-height: 1;
        background: none;
        border: none;
        padding: 0 5px;
    }
    .mobooking-remove-choice-btn:hover {
        color: #d00;
    }

    /* jQuery UI Sortable Placeholder for Choices */
    .mobooking-choice-item-placeholder {
        height: 40px; /* Should match item's approximate height */
        background-color: #f0f8ff; /* AliceBlue or a light yellow */
        border: 1px dashed #aaa;
        margin-bottom: 8px;
        border-radius: 4px;
        box-sizing: border-box;
    }
</style>

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
                    <div class="mobooking-custom-radio-group-placeholder"></div> <!-- Placeholder for custom radios -->
                </p>
                <div class="mobooking-option-values-field" style="display:none; margin-bottom:10px;">
                    <label><?php esc_html_e('Option Choices:', 'mobooking'); ?></label> 
                    <!-- New UI Container -->
                    <div class="mobooking-choices-ui-container">
                        <div class="mobooking-choices-list">
                            <!-- Choices will be dynamically added here by JavaScript -->
                        </div>
                        <button type="button" class="button mobooking-add-choice-btn"><?php esc_html_e('Add Choice', 'mobooking'); ?></button>
                    </div>
                    <!-- Hidden original textarea for storing JSON data -->
                    <textarea name="options[][option_values]" class="widefat" rows="2" placeholder='[{"value":"opt1","label":"Choice 1"},{"value":"opt2","label":"Choice 2"}]'></textarea>
                    <small style="margin-top: 5px; display: block;"><?php esc_html_e('This data is auto-generated. Manage choices using the UI above. Example: [{"value":"red","label":"Red Color"}, {"value":"blue","label":"Blue Color","price_adjust":5.00}] (price_adjust is optional per choice)', 'mobooking'); ?></small>
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

        <script type="text/template" id="mobooking-choice-item-template">
            <div class="mobooking-choice-item" style="display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid #e0e0e0; border-radius: 4px; margin-bottom: 8px;">
                <span class="mobooking-choice-drag-handle" style="cursor: move; font-size: 16px;">&#x2630;</span> <!-- Simple drag handle -->
                <input type="text" class="mobooking-choice-label" placeholder="<?php esc_attr_e('Label', 'mobooking'); ?>" style="flex-grow: 1;">
                <input type="text" class="mobooking-choice-value" placeholder="<?php esc_attr_e('Value', 'mobooking'); ?>" style="flex-basis: 100px;">
                <input type="number" step="0.01" class="mobooking-choice-price-adjust" placeholder="<?php esc_attr_e('Price Adj.', 'mobooking'); ?>" style="flex-basis: 100px;">
                <button type="button" class="button-link mobooking-remove-choice-btn">&times;</button>
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Cache the choice item template HTML
    const choiceTemplateHTML = $('#mobooking-choice-item-template').html();
    if (!choiceTemplateHTML) {
        console.error("MoBooking: Choice item template not found! Choices UI may not work correctly.");
    }

    /**
     * Creates custom radio button-like spans for a given select element.
     * @param {HTMLSelectElement} selectElement The original select element.
     * @param {HTMLElement} targetContainer The container where custom radios will be placed.
     */
    function createCustomRadioButtons(selectElement, targetContainer) {
        if (!selectElement || !targetContainer) return;

        $(targetContainer).empty(); // Clear previous radios if any

        const options = selectElement.options;
        const currentSelectValue = selectElement.value;

        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            const radioLabel = $('<span class="mobooking-custom-radio-label"></span>')
                .attr('data-value', option.value)
                .text(option.text);

            if (option.value === currentSelectValue) {
                radioLabel.addClass('selected');
            }

            radioLabel.on('click', function() {
                const $this = $(this);
                selectElement.value = $this.attr('data-value');

                // Update selected class for this group
                $this.siblings('.mobooking-custom-radio-label').removeClass('selected');
                $this.addClass('selected');

                // Trigger change event on the original select for compatibility
                var event = new Event('change', { bubbles: true });
                selectElement.dispatchEvent(event);
            });
            $(targetContainer).append(radioLabel);
        }
    }

    // Function to initialize custom radios for a given row
    function initializeCustomRadiosForRow($row) {
        if ($row.data('custom-radios-initialized')) {
            return;
        }
        const $selectElement = $row.find('.mobooking-option-type'); // Original select
        const $placeholder = $row.find('.mobooking-custom-radio-group-placeholder'); // Placeholder div

        // Check if placeholder exists and actual group does not (to prevent re-init)
        if ($selectElement.length && $placeholder.length && $row.find('.mobooking-custom-radio-group').length === 0) {
            const $radioGroupDiv = $('<div class="mobooking-custom-radio-group"></div>');
            $placeholder.replaceWith($radioGroupDiv); 
            createCustomRadioButtons($selectElement.get(0), $radioGroupDiv.get(0));
            $row.data('custom-radios-initialized', true);
        } else if ($selectElement.length && $row.find('.mobooking-custom-radio-group').length > 0) {
            // If radios are there but perhaps event on select was missed for some reason.
            // Re-ensure the original select value matches the active radio.
             const currentRadioValue = $row.find('.mobooking-custom-radio-label.selected').data('value');
             if (currentRadioValue && $selectElement.val() !== currentRadioValue) {
                 $selectElement.val(currentRadioValue).trigger('change');
             }
            $row.data('custom-radios-initialized', true); // Mark as processed
        }
    }

    /**
     * Syncs the UI choices back to the hidden textarea for a given option row.
     * @param {jQuery} $optionRow The jQuery object for the .mobooking-service-option-row.
     */
    function syncTextarea($optionRow) {
        const $choicesList = $optionRow.find('.mobooking-choices-list');
        const $textarea = $optionRow.find('textarea[name$="[option_values]"]'); // More specific selector
        let choicesData = [];

        $choicesList.find('.mobooking-choice-item').each(function() {
            const $item = $(this);
            choicesData.push({
                label: $item.find('.mobooking-choice-label').val(),
                value: $item.find('.mobooking-choice-value').val(),
                price_adjust: parseFloat($item.find('.mobooking-choice-price-adjust').val()) || 0
            });
        });

        try {
            $textarea.val(JSON.stringify(choicesData));
        } catch (e) {
            console.error("Error stringifying choices: ", e);
            $textarea.val("[]"); // Fallback to empty array
        }
    }

    /**
     * Renders choice items in the UI from the JSON in the hidden textarea.
     * @param {jQuery} $optionRow The jQuery object for the .mobooking-service-option-row.
     */
    function renderChoices($optionRow) {
        const $choicesList = $optionRow.find('.mobooking-choices-list');
        const $textarea = $optionRow.find('textarea[name$="[option_values]"]');
        // const choiceTemplate = $('#mobooking-choice-item-template').html(); // Now using cached choiceTemplateHTML

        $choicesList.empty();
        let choicesData = [];

        try {
            const jsonData = $textarea.val();
            if (jsonData) {
                choicesData = JSON.parse(jsonData);
            }
        } catch (e) {
            console.error("Error parsing choices JSON: ", e);
            // choicesData remains an empty array
        }

        if (!Array.isArray(choicesData)) {
            choicesData = [];
        }

        choicesData.forEach(function(choice) {
            if (!choiceTemplateHTML) return; // Do not proceed if template is missing
            const $newItem = $(choiceTemplateHTML);
            $newItem.find('.mobooking-choice-label').val(choice.label || '');
            $newItem.find('.mobooking-choice-value').val(choice.value || '');
            $newItem.find('.mobooking-choice-price-adjust').val(choice.price_adjust || '');
            $choicesList.append($newItem);
        });
    }

/**
 * Sets up all event listeners for a given option row related to choice management.
 * @param {jQuery} $row The jQuery object for the .mobooking-service-option-row.
 */
function initializeChoiceManagementForRow($row) {
    // Check if already initialized for basic event handlers to avoid redundant work if logic gets complex
    // Note: renderChoices and sortable have their own internal guards or idempotent behaviors.
    // The .off().on() pattern for events already handles re-binding safely.
    if ($row.data('choice-management-fully-initialized')) {
         // If sortable needs re-check or other specific parts, do it here.
         // For now, if events are bound and sortable is on, we assume it's mostly fine.
         // Re-running renderChoices can be an option if data might get stale and not re-rendered by type change.
         // renderChoices($row); // Optional: uncomment if state could be desynced and needs refresh
        return;
    }

    // Initial rendering of choices from textarea
    renderChoices($row);

    // Get the choices list once and reuse it
    const $choicesList = $row.find('.mobooking-choices-list');

    // Event listener for "Add Choice" button specific to this row
    $row.find('.mobooking-add-choice-btn').off('click.mobooking').on('click.mobooking', function() {
        const $optionRow = $(this).closest('.mobooking-service-option-row');
        // const choiceTemplate = $('#mobooking-choice-item-template').html(); // Now using cached choiceTemplateHTML
        if (!choiceTemplateHTML) return; // Do not proceed if template is missing
        const $newItem = $(choiceTemplateHTML);
        
        $newItem.find('input').val(''); // Clear all inputs in the new choice item
        $optionRow.find('.mobooking-choices-list').append($newItem);
        syncTextarea($optionRow);
    });

    // Delegated event listeners for inputs and remove button within this row's choices list
    $choicesList.off('click.mobooking', '.mobooking-remove-choice-btn').on('click.mobooking', '.mobooking-remove-choice-btn', function() {
        const $optionRow = $(this).closest('.mobooking-service-option-row');
        $(this).closest('.mobooking-choice-item').remove();
        syncTextarea($optionRow);
    });

    $choicesList.off('change.mobooking input.mobooking', '.mobooking-choice-label, .mobooking-choice-value, .mobooking-choice-price-adjust')
        .on('change.mobooking input.mobooking', '.mobooking-choice-label, .mobooking-choice-value, .mobooking-choice-price-adjust', function() {
        const $optionRow = $(this).closest('.mobooking-service-option-row');
        syncTextarea($optionRow);
    });
    
    // When the option type changes (original select for the row)
    $row.find('.mobooking-option-type').off('change.mobookingChoices').on('change.mobookingChoices', function() {
        const $select = $(this);
        const $optionRow = $select.closest('.mobooking-service-option-row');
        const $valuesField = $optionRow.find('.mobooking-option-values-field');
        const type = $select.val();

        if ($valuesField.is(':visible') && (type === 'select' || type === 'radio')) {
             renderChoices($optionRow); // Re-render choices if field becomes visible
        }
    });

    // Make the choices list sortable (reusing the $choicesList variable)
    if ($.fn.sortable && !$choicesList.hasClass('ui-sortable')) { // Check if jQuery UI sortable is loaded and not already initialized
        $choicesList.sortable({
            items: '.mobooking-choice-item',
            handle: '.mobooking-choice-drag-handle',
            axis: 'y',
            placeholder: 'mobooking-choice-item-placeholder',
            tolerance: 'pointer',
            containment: 'parent', // Constrain dragging to the list itself
            stop: function(event, ui) {
                // 'this' is the $choicesList DOM element
                var $optionRow = $(this).closest('.mobooking-service-option-row');
                syncTextarea($optionRow);
            }
        }).disableSelection(); // Optional: prevent text selection during drag
    } else {
        console.warn('jQuery UI Sortable is not loaded. Drag-and-drop for choices will not be available.');
    }
    $row.data('choice-management-fully-initialized', true); 
}


    // Observe the options list for newly added rows
    const optionsList = document.getElementById('mobooking-service-options-list');
    if (optionsList) {
        const observer = new MutationObserver(function(mutationsList, observer) {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && $(node).hasClass('mobooking-service-option-row')) {
                            const $newNode = $(node);
                            initializeCustomRadiosForRow($newNode); // For the select -> custom radio
                            initializeChoiceManagementForRow($newNode); // For the choices UI
                        }
                    });
                }
            }
        });
        observer.observe(optionsList, { childList: true });
    }

    // Initial setup for any options already present on page load (e.g., during "Edit Service")
    // This requires that option rows loaded via PHP also have the placeholder.
    // The current PHP doesn't render options directly, but relies on JS to build them from `mobooking_initial_services_list_for_cache`
    // or similar mechanisms. The logic that renders these initial options should call `initializeCustomRadiosForRow`.

    // If options are rendered by another script from `mobooking_initial_services_list_for_cache`,
    // that script needs to be modified to call `initializeCustomRadiosForRow` for each option row it creates.
    // For now, this script will handle dynamically added rows via "Add Option".
    // A more complete solution would integrate this with the "edit" loading mechanism too.

    // Example: if your existing code has a function like `displayServiceOptions(options)`:
    // function displayServiceOptions(options) {
    // ... (rest of the example remains the same) ...
    // }

    // Initialize UI for any existing option rows present on page load
    // This targets rows that are part of the initially loaded DOM (e.g. when editing a service)
    $('#mobooking-service-options-list .mobooking-service-option-row').each(function() {
        var $currentRow = $(this);
        initializeCustomRadiosForRow($currentRow); 
        initializeChoiceManagementForRow($currentRow);
    });
    // Note: The line "$row.data('choice-management-fully-initialized', true);" was identified as incorrect here and is now correctly placed inside initializeChoiceManagementForRow
});
</script>