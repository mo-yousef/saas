<?php
/**
 * Template Name: Public Booking Form
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Consider using a more minimal header/footer for a focused booking experience
get_header();
?>
<div id="mobooking-public-booking-form-wrapper" class="mobooking-wrapper" style="max-width: 700px; margin: 20px auto; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <h1 style="text-align:center;"><?php esc_html_e('Book Our Services', 'mobooking'); ?></h1>

    <!-- Step 1: Location -->
    <div id="mobooking-bf-step-1-location" class="mobooking-bf-step">
        <h2 style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 1: Check Service Availability', 'mobooking'); ?></h2>
        <form id="mobooking-bf-location-form">
            <p>
                <label for="mobooking-bf-country-code"><strong><?php esc_html_e('Country Code:', 'mobooking'); ?></strong></label><br>
                <input type="text" id="mobooking-bf-country-code" name="country_code" value="US" required style="width:100%; padding:8px; margin-top:5px;">
                <small><?php esc_html_e('E.g., US, CA, GB', 'mobooking'); ?></small>
            </p>
            <p>
                <label for="mobooking-bf-zip-code"><strong><?php esc_html_e('Your ZIP / Postal Code:', 'mobooking'); ?></strong></label><br>
                <input type="text" id="mobooking-bf-zip-code" name="zip_code" required style="width:100%; padding:8px; margin-top:5px;">
            </p>
            <?php // Tenant ID will be populated by JS from URL param 'tid' or via wp_localize_script ?>
            <input type="hidden" id="mobooking-bf-tenant-id" name="tenant_id" value="">
            <button type="submit" class="button button-primary" style="padding:10px 15px; font-size:16px;"><?php esc_html_e('Check Availability', 'mobooking'); ?></button>
        </form>
        <div id="mobooking-bf-feedback" style="margin-top:15px; padding:10px; border-radius:3px;"></div>
    </div>

    <!-- Step 2: Services -->
    <div id="mobooking-bf-step-2-services" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-2-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 2: Select Services', 'mobooking'); ?></h2>
        <div id="mobooking-bf-services-list" class="mobooking-bf-items-list" style="margin-bottom: 20px;">
            <!-- Services will be loaded here by AJAX -->
            <p><?php esc_html_e('Loading services...', 'mobooking'); ?></p>
        </div>
        <div id="mobooking-bf-step-2-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
        <button type="button" id="mobooking-bf-services-back-btn" class="button" style="margin-right:10px;"><?php esc_html_e('Back', 'mobooking'); ?></button>
        <button type="button" id="mobooking-bf-services-next-btn" class="button button-primary"><?php esc_html_e('Next to Options', 'mobooking'); ?></button>
    </div>

    <!-- Step 3: Options (Placeholder) -->
    <div id="mobooking-bf-step-3-options" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-3-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 3: Configure Options', 'mobooking'); ?></h2>
        <div id="mobooking-bf-service-options-display" style="margin-bottom: 20px;">
             <!-- Selected service options will be configured here -->
             <p><?php esc_html_e('Service options configuration will appear here.', 'mobooking'); ?></p>
        </div>
        <div id="mobooking-bf-step-3-feedback" style="margin-top:10px; padding:8px; border-radius:3px;"></div>
        <button type="button" id="mobooking-bf-options-back-btn" class="button" style="margin-right:10px;"><?php esc_html_e('Back to Services', 'mobooking'); ?></button>
        <button type="button" id="mobooking-bf-options-next-btn" class="button button-primary"><?php esc_html_e('Next to Details', 'mobooking'); ?></button>
    </div>

    <?php // Step 4: Your Details (Placeholder) - formerly Step 3 Schedule ?>
    <div id="mobooking-bf-step-4-details" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-4-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 4: Your Details & Schedule', 'mobooking'); ?></h2>
        <p><?php esc_html_e('Customer information fields and scheduling options will appear here.', 'mobooking'); ?></p>
        <button type="button" id="mobooking-bf-details-back-btn"><?php esc_html_e('Back to Options', 'mobooking'); ?></button>
    </div>

</div>

<script type="text/template" id="mobooking-bf-service-item-template">
    <div class="mobooking-bf-service-item" style="padding:10px; border:1px solid #f0f0f0; margin-bottom:10px; border-radius:3px;">
        <label style="display:block; font-weight:bold;">
            <input type="checkbox" name="selected_services[]" value="<%= service_id %>" data-service-id="<%= service_id %>">
            <%= name %> - <span class="service-price"><%= price_formatted %></span>
            (<span class="service-duration"><%= duration %></span> <?php esc_html_e('min', 'mobooking'); ?>)
        </label>
        <% if (description) { %><p class="service-description" style="font-size:0.9em; margin-left:25px;"><%= description %></p><% } %>
    </div>
</script>

<?php
get_footer(); // Or a custom minimal footer
?>
