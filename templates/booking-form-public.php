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

    <!-- Step 3: Options -->
    <div id="mobooking-bf-step-3-options" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-3-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 3: Configure Service Options', 'mobooking'); ?></h2>
        <div id="mobooking-bf-service-options-display" style="margin-bottom: 20px;">
             <!-- Selected service options will be dynamically rendered here -->
        </div>
        <div id="mobooking-bf-step-3-feedback" style="margin-top:10px; padding:8px; border-radius:3px; color:red;"></div>
        <button type="button" id="mobooking-bf-options-back-btn" class="button" style="margin-right:10px;"><?php esc_html_e('Back to Services', 'mobooking'); ?></button>
        <button type="button" id="mobooking-bf-options-next-btn" class="button button-primary"><?php esc_html_e('Next to Your Details', 'mobooking'); ?></button>
    </div>

    <script type="text/template" id="mobooking-bf-option-checkbox-template">
        <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <label>
                <input type="checkbox" name="service_option[<%= service_id %>][<%= option_id %>]" value="1" <% if (is_required == 1) { %>required<% } %>>
                <strong><%= name %></strong> <% if (price_impact_value_formatted) { %>(<%= price_impact_value_formatted %>)<% } %>
            </label>
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-left:20px; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-text-template">
        <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <label for="option_<%= service_id %>_<%= option_id %>" style="font-weight:bold;">
                <%= name %> <% if (price_impact_value_formatted) { %>(<%= price_impact_value_formatted %>)<% } %>
                <% if (is_required == 1) { %> <span class="required" style="color:red;">*</span><% } %>
            </label>
            <input type="text" id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf-input" <% if (is_required == 1) { %>required<% } %>>
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-number-template">
        <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <label for="option_<%= service_id %>_<%= option_id %>" style="font-weight:bold;">
                <%= name %> <% if (price_impact_value_formatted && option.price_impact_type !== 'multiply_value') { %>(<%= price_impact_value_formatted %>)<% } %>
                <% if (is_required == 1) { %> <span class="required" style="color:red;">*</span><% } %>
            </label>
            <input type="number" id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf-input" <% if (is_required == 1) { %>required<% } %> min="0">
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-quantity-template">
        <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <label for="option_<%= service_id %>_<%= option_id %>_qty" style="font-weight:bold;">
                <%= name %> <% if (price_impact_value_formatted && option.price_impact_type === 'fixed') { %>(Per item: <%= price_impact_value_formatted %>)<% } %>
                <% if (is_required == 1) { %> <span class="required" style="color:red;">*</span><% } %>
            </label>
            <input type="number" id="option_<%= service_id %>_<%= option_id %>_qty" name="service_option[<%= service_id %>][<%= option_id %>][quantity]" value="<% if (is_required == 1) { %>1<% } else { %>0<% } %>" min="0" class="mobooking-bf-input mobooking-bf-option-quantity-input">
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-select-template">
        <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <label for="option_<%= service_id %>_<%= option_id %>" style="font-weight:bold;">
                <%= name %> <% if (price_impact_value_formatted && option.price_impact_type !== 'multiply_value' && option.price_impact_type !== 'fixed' /* fixed per choice below */) { %>(Base Impact: <%= price_impact_value_formatted %>)<% } %>
                <% if (is_required == 1) { %> <span class="required" style="color:red;">*</span><% } %>
            </label>
            <select id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf-input" <% if (is_required == 1) { %>required<% } %>>
                <% if (is_required != 1) { %><option value=""><?php esc_html_e('-- Select (optional) --', 'mobooking'); ?></option><% } %>
                <% if (parsed_option_values && parsed_option_values.length) { %>
                    <% parsed_option_values.forEach(function(val_opt) { %>
                        <option value="<%= val_opt.value %>" data-price-adjust="<%= val_opt.price_adjust || 0 %>">
                            <%= val_opt.label %> <% if (val_opt.price_adjust) { %>(<%= parseFloat(val_opt.price_adjust) > 0 ? '+' : '' %><%= parseFloat(val_opt.price_adjust).toFixed(2) %>)<% } %>
                        </option>
                    <% }); %>
                <% } %>
            </select>
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-radio-template">
         <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <p style="font-weight:bold;"><%= name %> <% if (price_impact_value_formatted && option.price_impact_type !== 'multiply_value' && option.price_impact_type !== 'fixed') { %>(Base Impact: <%= price_impact_value_formatted %>)<% } %> <% if (is_required == 1) { %><span class="required" style="color:red;">*</span><% } %></p>
            <% if (parsed_option_values && parsed_option_values.length) { %>
                <% parsed_option_values.forEach(function(val_opt, index) { %>
                    <label style="display:block; margin-left:10px;">
                        <input type="radio" name="service_option[<%= service_id %>][<%= option_id %>]" value="<%= val_opt.value %>" data-price-adjust="<%= val_opt.price_adjust || 0 %>" <% if (is_required == 1 && index === 0) { %>checked<% } %>>
                        <%= val_opt.label %> <% if (val_opt.price_adjust) { %>(<%= parseFloat(val_opt.price_adjust) > 0 ? '+' : '' %><%= parseFloat(val_opt.price_adjust).toFixed(2) %>)<% } %>
                    </label>
                <% }); %>
            <% } %>
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-textarea-template">
        <div class="mobooking-bf-option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #eee;">
            <label for="option_<%= service_id %>_<%= option_id %>" style="font-weight:bold;">
                <%= name %> <% if (price_impact_value_formatted) { %>(<%= price_impact_value_formatted %>)<% } %>
                <% if (is_required == 1) { %> <span class="required" style="color:red;">*</span><% } %>
            </label>
            <textarea id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf-input" rows="3" <% if (is_required == 1) { %>required<% } %>></textarea>
            <% if (description) { %><p class="option-description" style="font-size:0.9em; margin-top:2px;"><%= description %></p><% } %>
        </div>
    </script>

    <!-- Step 4: Customer Details & Scheduling -->
    <div id="mobooking-bf-step-4-details" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-4-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 4: Your Details & Preferred Time', 'mobooking'); ?></h2>
        <form id="mobooking-bf-details-form">
            <p>
                <label for="mobooking-bf-customer-name"><?php esc_html_e('Full Name:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label><br>
                <input type="text" id="mobooking-bf-customer-name" name="customer_name" required class="mobooking-bf-input">
            </p>
            <p>
                <label for="mobooking-bf-customer-email"><?php esc_html_e('Email Address:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label><br>
                <input type="email" id="mobooking-bf-customer-email" name="customer_email" required class="mobooking-bf-input">
            </p>
            <p>
                <label for="mobooking-bf-customer-phone"><?php esc_html_e('Phone Number:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label><br>
                <input type="tel" id="mobooking-bf-customer-phone" name="customer_phone" required class="mobooking-bf-input">
            </p>
            <p>
                <label for="mobooking-bf-service-address"><?php esc_html_e('Service Address:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label><br>
                <textarea id="mobooking-bf-service-address" name="service_address" rows="3" required class="mobooking-bf-input"></textarea>
            </p>
            <p>
                <label for="mobooking-bf-booking-date"><?php esc_html_e('Preferred Date:', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label><br>
                <input type="text" id="mobooking-bf-booking-date" name="booking_date" required autocomplete="off" class="mobooking-bf-input">
            </p>
            <p>
                <label for="mobooking-bf-booking-time"><?php esc_html_e('Preferred Time (e.g., 10:00 AM):', 'mobooking'); ?> <span class="required" style="color:red;">*</span></label><br>
                <input type="text" id="mobooking-bf-booking-time" name="booking_time" required placeholder="e.g., 10:00 AM or 14:30" class="mobooking-bf-input">
            </p>
            <p>
                <label for="mobooking-bf-special-instructions"><?php esc_html_e('Special Instructions (optional):', 'mobooking'); ?></label><br>
                <textarea id="mobooking-bf-special-instructions" name="special_instructions" rows="3" class="mobooking-bf-input"></textarea>
            </p>
        </form>
        <div id="mobooking-bf-step-4-feedback" style="margin-top:10px; color:red; padding:8px; border-radius:3px;"></div>
        <button type="button" id="mobooking-bf-details-back-btn" class="button" style="margin-right:10px;"><?php esc_html_e('Back to Options', 'mobooking'); ?></button>
        <button type="button" id="mobooking-bf-details-next-btn" class="button button-primary"><?php esc_html_e('Next to Review', 'mobooking'); ?></button>
    </div>

    <!-- Step 5: Review & Confirm (Placeholder) -->
    <div id="mobooking-bf-step-5-review" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-5-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 5: Review & Confirm Booking', 'mobooking'); ?></h2>
        <div id="mobooking-bf-review-summary" style="margin-bottom:20px;">
            <!-- Booking summary will be displayed here -->
            <p><?php esc_html_e('Booking summary will appear here.', 'mobooking'); ?></p>
        </div>

        <!-- Discount Code Section -->
        <div id="mobooking-bf-discount-section" style="margin-top:15px; margin-bottom:15px; padding-bottom:15px; border-bottom:1px dashed #eee;">
            <p>
                <label for="mobooking-bf-discount-code"><strong><?php esc_html_e('Discount Code:', 'mobooking'); ?></strong></label><br>
                <input type="text" id="mobooking-bf-discount-code" name="discount_code" class="mobooking-bf-input" style="width:calc(100% - 100px); margin-right:5px;">
                <button type="button" id="mobooking-bf-apply-discount-btn" class="button button-secondary" style="padding:8px 12px;"><?php esc_html_e('Apply', 'mobooking'); ?></button>
            </p>
            <div id="mobooking-bf-discount-feedback" style="margin-top:5px; padding:8px; border-radius:3px;"></div>
        </div>
        <!-- End Discount Code Section -->

        <!-- Pricing Summary Section -->
        <div id="mobooking-bf-pricing-summary-section" style="margin-top:20px; padding-top:15px; border-top:1px solid #eee;">
            <h4><?php esc_html_e('Total Summary', 'mobooking'); ?></h4>
            <p><?php esc_html_e('Subtotal:', 'mobooking'); ?> <span id="mobooking-bf-subtotal"></span></p>
            <p><?php esc_html_e('Discount Applied:', 'mobooking'); ?> <span id="mobooking-bf-discount-applied"></span></p>
            <p><strong><?php esc_html_e('Final Total:', 'mobooking'); ?> <span id="mobooking-bf-final-total" style="font-size:1.2em;"></span></strong></p>
        </div>
        <!-- End Pricing Summary Section -->

        <div id="mobooking-bf-step-5-feedback" style="margin-top:10px; color:red; padding:8px; border-radius:3px;"></div>
        <button type="button" id="mobooking-bf-review-back-btn" class="button" style="margin-right:10px;"><?php esc_html_e('Back to Details', 'mobooking'); ?></button>
        <button type="button" id="mobooking-bf-review-confirm-btn" class="button button-primary"><?php esc_html_e('Confirm Booking', 'mobooking'); ?></button>
    </div>
</div>

<script type="text/template" id="mobooking-bf-service-item-template">
    <div class="mobooking-bf-service-item" style="padding:10px; border:1px solid #f0f0f0; margin-bottom:10px; border-radius:3px;">
        <label style="display:block; font-weight:bold;">
            <input type="checkbox" name="selected_services[]" value="<%= service_id %>" data-service-id="<%= service_id %>">
            <%= name %>
            <% if (typeof mobookingShouldShowPricing !== 'undefined' && mobookingShouldShowPricing()) { %>
                - <span class="service-price"><%= price_formatted %></span>
            <% } %>
            (<span class="service-duration"><%= duration %></span> <?php esc_html_e('min', 'mobooking'); ?>)
        </label>
        <% if (description) { %><p class="service-description" style="font-size:0.9em; margin-left:25px;"><%= description %></p><% } %>
    </div>
</script>

<?php
// Add the discount section wrapper to Step 5 if it's not already perfectly structured.
// The JS expects #mobooking-bf-discount-section to exist.
// Assuming the discount code input, button, and feedback div are already in the HTML for Step 5,
// ensure they are wrapped by a div with id="mobooking-bf-discount-section".
// The previous JS change added this div ID to the example HTML, so ensure the template matches.
// The existing HTML for Step 5 in templates/booking-form-public.php (as of last view)
// did not have the explicit discount section. It should be added if not present.
// For now, I'll assume the structure seen in the JS diff for the discount section exists or will be added.
// The primary change here is the service item template.

// The original Step 5 structure from `templates/booking-form-public.php` was:
/*
    <div id="mobooking-bf-step-5-review" class="mobooking-bf-step" style="display:none;">
        <h2 id="mobooking-bf-step-5-title" style="border-bottom: 1px solid #eee; padding-bottom:10px; margin-bottom:20px;"><?php esc_html_e('Step 5: Review & Confirm Booking', 'mobooking'); ?></h2>
        <div id="mobooking-bf-review-summary" style="margin-bottom:20px;">
            <p><?php esc_html_e('Booking summary will appear here.', 'mobooking'); ?></p>
        </div>
        <div id="mobooking-bf-pricing-summary-section" style="margin-top:20px; padding-top:15px; border-top:1px solid #eee;"> ...totals... </div>
        <div id="mobooking-bf-step-5-feedback" style="margin-top:10px; color:red; padding:8px; border-radius:3px;"></div>
        <button type="button" id="mobooking-bf-review-back-btn" class="button" style="margin-right:10px;"><?php esc_html_e('Back to Details', 'mobooking'); ?></button>
        <button type="button" id="mobooking-bf-review-confirm-btn" class="button button-primary"><?php esc_html_e('Confirm Booking', 'mobooking'); ?></button>
    </div>
*/
// We need to ensure the discount section is part of this.
// Let's add it similar to how it's structured in the JS expectations for hiding/showing.
// The previous diff added the pricing summary section. Now, ensure discount section is explicitly there.

// The JS `$('#mobooking-bf-discount-section').show();` requires this div:
// I will ensure it's in the correct place in Step 5.
// The previous diff for `templates/booking-form-public.php` already added the pricing summary.
// The discount section should be before the pricing summary or integrated if part of it.
// Let's assume it's placed before the totals summary.

// Corrected structure for Step 5 should be:
// Review Summary
// Discount Section (conditionally shown by JS)
// Pricing Totals (conditionally shown by JS)
// Feedback & Buttons

// The service item template change is the main part of *this* diff.
// The structural change for discount section will be handled in a separate diff if required,
// but the JS already targets `#mobooking-bf-discount-section`.

get_footer(); // Or a custom minimal footer
?>
