<?php
/**
 * Template Name: Public Booking Form
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Conditionally load header/footer for public vs embed view
if (get_query_var('mobooking_page_type') !== 'embed') { // Check for 'embed'
    get_header();
    echo '<body class="mobooking-form-active">'; // Add class to body
} else {
    // For embed, we might want a minimal body tag or ensure JS adds a class to a wrapper
    echo '<body class="mobooking-form-active mobooking-form-embed-active">';
}
?>
<div id="mobooking-public-booking-form-wrapper" class="mobooking-bf-wrapper <?php if (get_query_var('mobooking_page_type') === 'embed') { echo 'mobooking-bf-wrapper--embed'; } ?>">

    <!-- This div will contain the main content and the sidebar -->
    <div class="mobooking-bf__layout-container">

        <!-- Main content area for steps -->
        <div class="mobooking-bf__main-content">
            <?php if (get_query_var('mobooking_page_type') !== 'embed'): ?>
            <h1 class="mobooking-bf-main-title"><?php esc_html_e('Book Our Services', 'mobooking'); ?></h1>
            <?php endif; ?>

            <!-- Step 1: Location -->
            <div id="mobooking-bf-step-1-location" class="mobooking-bf__step">
                <h2 class="mobooking-bf__step-title"><?php esc_html_e('Step 1: Check Service Availability', 'mobooking'); ?></h2>
                <form id="mobooking-bf-location-form">
                    <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-country-code" class="mobooking-bf__label"><?php esc_html_e('Country Code:', 'mobooking'); ?></label>
                <input type="text" id="mobooking-bf-country-code" name="country_code" value="US" required class="mobooking-bf__input">
                <small class="mobooking-bf__small-text"><?php esc_html_e('E.g., US, CA, GB', 'mobooking'); ?></small>
            </div>
            <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-zip-code" class="mobooking-bf__label"><?php esc_html_e('Your ZIP / Postal Code:', 'mobooking'); ?></label>
                <input type="text" id="mobooking-bf-zip-code" name="zip_code" required class="mobooking-bf__input">
            </div>
            <input type="hidden" id="mobooking-bf-tenant-id" name="tenant_id" value="">
            <div class="mobooking-bf__button-group">
                <button type="submit" class="mobooking-bf__button mobooking-bf__button--primary"><?php esc_html_e('Check Availability', 'mobooking'); ?></button>
            </div>
        </form>
        <div id="mobooking-bf-feedback" class="mobooking-bf__feedback mobooking-bf__hidden"></div>
    </div>

    <!-- Step 2: Services -->
    <div id="mobooking-bf-step-2-services" class="mobooking-bf__step">
        <h2 id="mobooking-bf-step-2-title" class="mobooking-bf__step-title"><?php esc_html_e('Step 2: Select Services', 'mobooking'); ?></h2>
        <div id="mobooking-bf-services-list" class="mobooking-bf-items-list">
            <p><?php esc_html_e('Loading services...', 'mobooking'); ?></p>
        </div>
        <div id="mobooking-bf-step-2-feedback" class="mobooking-bf__feedback mobooking-bf__hidden"></div>
        <div class="mobooking-bf__button-group">
            <button type="button" id="mobooking-bf-services-back-btn" class="mobooking-bf__button mobooking-bf__button--secondary"><?php esc_html_e('Back', 'mobooking'); ?></button>
            <button type="button" id="mobooking-bf-services-next-btn" class="mobooking-bf__button mobooking-bf__button--primary"><?php esc_html_e('Next to Options', 'mobooking'); ?></button>
        </div>
    </div>

    <!-- Step 3: Options -->
    <div id="mobooking-bf-step-3-options" class="mobooking-bf__step">
        <h2 id="mobooking-bf-step-3-title" class="mobooking-bf__step-title"><?php esc_html_e('Step 3: Configure Service Options', 'mobooking'); ?></h2>
        <div id="mobooking-bf-service-options-display">
             <!-- Selected service options will be dynamically rendered here -->
        </div>
        <div id="mobooking-bf-step-3-feedback" class="mobooking-bf__feedback mobooking-bf__hidden"></div>
        <div class="mobooking-bf__button-group">
            <button type="button" id="mobooking-bf-options-back-btn" class="mobooking-bf__button mobooking-bf__button--secondary"><?php esc_html_e('Back to Services', 'mobooking'); ?></button>
            <button type="button" id="mobooking-bf-options-next-btn" class="mobooking-bf__button mobooking-bf__button--primary"><?php esc_html_e('Next to Your Details', 'mobooking'); ?></button>
        </div>
    </div>

    <script type="text/template" id="mobooking-bf-option-checkbox-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="checkbox">
            <label class="mobooking-bf__label mobooking-bf__label--checkbox">
                <input type="checkbox" class="mobooking-bf__checkbox" name="service_option[<%= service_id %>][<%= option_id %>]" value="1" <%= required_attr %>>
                <span class="mobooking-bf__option-name"><%= name %></span> <!-- price_impact_placeholder -->
            </label>
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-text-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="text">
            <label for="option_<%= service_id %>_<%= option_id %>" class="mobooking-bf__label">
                <%= name %> <!-- price_impact_placeholder --> <!-- required_indicator_placeholder -->
            </label>
            <input type="text" id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__input" <%= required_attr %>>
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-number-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="number">
            <label for="option_<%= service_id %>_<%= option_id %>" class="mobooking-bf__label">
                <%= name %> <!-- price_impact_placeholder --> <!-- required_indicator_placeholder -->
            </label>
            <div class="mobooking-bf__number-input-wrapper">
                <button type="button" class="mobooking-bf__number-btn mobooking-bf__number-btn--minus" aria-label="<?php esc_attr_e('Decrease quantity', 'mobooking'); ?>">&ndash;</button>
                <input type="number" id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__input mobooking-bf__input--number" <%= required_attr %> min="0" value="0">
                <button type="button" class="mobooking-bf__number-btn mobooking-bf__number-btn--plus" aria-label="<?php esc_attr_e('Increase quantity', 'mobooking'); ?>">+</button>
            </div>
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-quantity-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="quantity">
            <label for="option_<%= service_id %>_<%= option_id %>_qty" class="mobooking-bf__label">
                <%= name %> <!-- price_impact_placeholder --> <!-- required_indicator_placeholder -->
            </label>
            <div class="mobooking-bf__number-input-wrapper">
                <button type="button" class="mobooking-bf__number-btn mobooking-bf__number-btn--minus" aria-label="<?php esc_attr_e('Decrease quantity', 'mobooking'); ?>">&ndash;</button>
                <input type="number" id="option_<%= service_id %>_<%= option_id %>_qty" name="service_option[<%= service_id %>][<%= option_id %>][quantity]" value="<%= quantity_default_value %>" min="0" class="mobooking-bf__input mobooking-bf__input--number mobooking-bf-option-quantity-input" <%= required_attr %>>
                <button type="button" class="mobooking-bf__number-btn mobooking-bf__number-btn--plus" aria-label="<?php esc_attr_e('Increase quantity', 'mobooking'); ?>">+</button>
            </div>
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-select-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="select">
            <label for="option_<%= service_id %>_<%= option_id %>" class="mobooking-bf__label">
                <%= name %> <!-- price_impact_placeholder --> <!-- required_indicator_placeholder -->
            </label>
            <select id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__select" <%= required_attr %>>
                <!-- options_loop_placeholder -->
            </select>
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-radio-template">
         <div class="mobooking-bf__option-item mobooking-bf__label--radio-group" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="radio">
            <p class="mobooking-bf__label"><%= name %> <!-- price_impact_placeholder --> <!-- required_indicator_placeholder --></p>
            <!-- options_loop_placeholder -->
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-textarea-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="textarea">
            <label for="option_<%= service_id %>_<%= option_id %>" class="mobooking-bf__label">
                <%= name %> <!-- price_impact_placeholder --> <!-- required_indicator_placeholder -->
            </label>
            <textarea id="option_<%= service_id %>_<%= option_id %>" name="service_option[<%= service_id %>][<%= option_id %>]" class="mobooking-bf__textarea" rows="3" <%= required_attr %>></textarea>
            <!-- description_placeholder -->
        </div>
    </script>

    <script type="text/template" id="mobooking-bf-option-sqm-template">
        <div class="mobooking-bf__option-item" data-service-id="<%= service_id %>" data-option-id="<%= option_id %>" data-option-type="sqm">
            <label for="option_<%= service_id %>_<%= option_id %>_sqm_total" class="mobooking-bf__label">
                <%= name %> <!-- required_indicator_placeholder -->
            </label>
            <div class="mobooking-bf__sqm-input-group">
                <input type="range" id="option_<%= service_id %>_<%= option_id %>_sqm_slider"
                       class="mobooking-bf__slider mobooking-bf-sqm-slider"
                       min="0" max="500" step="1" value="0"> {/* Adjust max/step as needed */}
                <input type="number" id="option_<%= service_id %>_<%= option_id %>_sqm_total"
                       name="service_option[<%= service_id %>][<%= option_id %>][total_sqm]"
                       class="mobooking-bf__input mobooking-bf__input--number mobooking-bf-sqm-total-input"
                       placeholder="<?php esc_attr_e('SQM', 'mobooking'); ?>"
                       min="0" step="any" <%= required_attr %> value="0">
            </div>
            <div class="mobooking-bf__sqm-price-display" id="sqm_price_display_<%= service_id %>_<%= option_id %>">
                <!-- Price will be shown here by JS -->
            </div>
            <!-- description_placeholder -->
            <!-- Store ranges in a hidden way for JS to access -->
            <script type="application/json" class="mobooking-bf-sqm-ranges-data">
                <%= option_values_json_string %>
            </script>
        </div>
    </script>

    <!-- Step 4: Customer Details & Scheduling -->
    <div id="mobooking-bf-step-4-details" class="mobooking-bf__step">
        <h2 id="mobooking-bf-step-4-title" class="mobooking-bf__step-title"><?php esc_html_e('Step 4: Your Details & Preferred Time', 'mobooking'); ?></h2>
        <form id="mobooking-bf-details-form">
            <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-customer-name" class="mobooking-bf__label"><?php esc_html_e('Full Name:', 'mobooking'); ?> <span class="mobooking-bf__required-indicator">*</span></label>
                <input type="text" id="mobooking-bf-customer-name" name="customer_name" required class="mobooking-bf__input">
            </div>
            <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-customer-email" class="mobooking-bf__label"><?php esc_html_e('Email Address:', 'mobooking'); ?> <span class="mobooking-bf__required-indicator">*</span></label>
                <input type="email" id="mobooking-bf-customer-email" name="customer_email" required class="mobooking-bf__input">
            </div>
            <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-customer-phone" class="mobooking-bf__label"><?php esc_html_e('Phone Number:', 'mobooking'); ?> <span class="mobooking-bf__required-indicator">*</span></label>
                <input type="tel" id="mobooking-bf-customer-phone" name="customer_phone" required class="mobooking-bf__input">
            </div>
            <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-service-address" class="mobooking-bf__label"><?php esc_html_e('Service Address:', 'mobooking'); ?> <span class="mobooking-bf__required-indicator">*</span></label>
                <textarea id="mobooking-bf-service-address" name="service_address" rows="3" required class="mobooking-bf__textarea"></textarea>
            </div>
            <div class="mobooking-bf__form-group" id="mobooking-bf-booking-date-group">
                <label for="mobooking-bf-booking-date" class="mobooking-bf__label"><?php esc_html_e('Preferred Date:', 'mobooking'); ?> <span class="mobooking-bf__required-indicator">*</span></label>
                <input type="text" id="mobooking-bf-booking-date" name="booking_date" required autocomplete="off" class="mobooking-bf__input">
            </div>
            <div class="mobooking-bf__form-group" id="mobooking-bf-booking-time-group">
                <label for="mobooking-bf-booking-time" class="mobooking-bf__label"><?php esc_html_e('Preferred Time (e.g., 10:00 AM):', 'mobooking'); ?> <span class="mobooking-bf__required-indicator">*</span></label>
                <input type="text" id="mobooking-bf-booking-time" name="booking_time" required placeholder="<?php esc_attr_e('e.g., 10:00 AM or 14:30', 'mobooking'); ?>" class="mobooking-bf__input">
            </div>
            <div class="mobooking-bf__form-group" id="mobooking-bf-special-instructions-group">
                <label for="mobooking-bf-special-instructions" class="mobooking-bf__label"><?php esc_html_e('Special Instructions (optional):', 'mobooking'); ?></label>
                <textarea id="mobooking-bf-special-instructions" name="special_instructions" rows="3" class="mobooking-bf__textarea"></textarea>
            </div>
        </form>
        <div id="mobooking-bf-step-4-feedback" class="mobooking-bf__feedback mobooking-bf__hidden"></div>
        <div class="mobooking-bf__button-group">
            <button type="button" id="mobooking-bf-details-back-btn" class="mobooking-bf__button mobooking-bf__button--secondary"><?php esc_html_e('Back to Options', 'mobooking'); ?></button>
            <button type="button" id="mobooking-bf-details-next-btn" class="mobooking-bf__button mobooking-bf__button--primary"><?php esc_html_e('Next to Review', 'mobooking'); ?></button>
        </div>
    </div>

    <!-- Step 5: Review & Confirm -->
    <div id="mobooking-bf-step-5-review" class="mobooking-bf__step">
        <h2 id="mobooking-bf-step-5-title" class="mobooking-bf__step-title"><?php esc_html_e('Step 5: Review & Confirm Booking', 'mobooking'); ?></h2>
        <div id="mobooking-bf-review-summary" class="mobooking-bf__review-summary">
            <p><?php esc_html_e('Booking summary will appear here.', 'mobooking'); ?></p>
        </div>

        <!-- Discount Code Section -->
        <div id="mobooking-bf-discount-section" class="mobooking-bf__discount-section">
             <div class="mobooking-bf__form-group">
                <label for="mobooking-bf-discount-code" class="mobooking-bf__label"><?php esc_html_e('Discount Code:', 'mobooking'); ?></label>
                <input type="text" id="mobooking-bf-discount-code" name="discount_code" class="mobooking-bf__input">
                <button type="button" id="mobooking-bf-apply-discount-btn" class="mobooking-bf__button mobooking-bf__button--secondary"><?php esc_html_e('Apply', 'mobooking'); ?></button>
            </div>
            <div id="mobooking-bf-discount-feedback" class="mobooking-bf__feedback mobooking-bf__hidden"></div>
        </div>
        <!-- End Discount Code Section -->

        <!-- Pricing Summary Section -->
        <div id="mobooking-bf-pricing-summary-section" class="mobooking-bf__pricing-summary-section">
            <h4><?php esc_html_e('Total Summary', 'mobooking'); ?></h4>
            <p><?php esc_html_e('Subtotal:', 'mobooking'); ?> <span id="mobooking-bf-subtotal"></span></p>
            <p><?php esc_html_e('Discount Applied:', 'mobooking'); ?> <span id="mobooking-bf-discount-applied"></span></p>
            <p><strong><?php esc_html_e('Final Total:', 'mobooking'); ?> <span id="mobooking-bf-final-total"></span></strong></p>
        </div>
        <!-- End Pricing Summary Section -->

        <!-- Terms and Conditions Section -->
        <div id="mobooking-bf-terms-conditions-section" class="mobooking-bf__terms-section mobooking-bf__hidden" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
            <div class="mobooking-bf__form-group">
                <label class="mobooking-bf__label mobooking-bf__label--checkbox">
                    <input type="checkbox" id="mobooking-bf-terms-agree" name="terms_agree" value="1" class="mobooking-bf__checkbox">
                    <span id="mobooking-bf-terms-text"><?php esc_html_e('I agree to the', 'mobooking'); ?> <a href="#" id="mobooking-bf-terms-link" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Terms & Conditions', 'mobooking'); ?></a></span>
                     <span class="mobooking-bf__required-indicator">*</span>
                </label>
            </div>
        </div>
        <!-- End Terms and Conditions Section -->

        <div id="mobooking-bf-step-5-feedback" class="mobooking-bf__feedback mobooking-bf__hidden"></div>
        <div class="mobooking-bf__button-group">
            <button type="button" id="mobooking-bf-review-back-btn" class="mobooking-bf__button mobooking-bf__button--secondary"><?php esc_html_e('Back to Details', 'mobooking'); ?></button>
            <button type="button" id="mobooking-bf-review-confirm-btn" class="mobooking-bf__button mobooking-bf__button--primary"><?php esc_html_e('Confirm Booking', 'mobooking'); ?></button>
        </div>
    </div>

    <!-- Step 6: Confirmation -->
    <div id="mobooking-bf-step-6-confirmation" class="mobooking-bf__step mobooking-bf__hidden">
        <!-- Title will be part of the message or you can add one if needed -->
        <div id="mobooking-bf-confirmation-message">
            <!-- Confirmation message will be injected by JS -->
        </div>
        <!-- Optionally, a button to go back to the start or to the main site -->
        <!-- <div class="mobooking-bf__button-group">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="mobooking-bf__button mobooking-bf__button--secondary"><?php esc_html_e('Back to Homepage', 'mobooking'); ?></a>
        </div> -->
    </div>
        </div> <!-- End .mobooking-bf__main-content -->

        <!-- Sidebar for Summary -->
        <div id="mobooking-bf-sidebar-summary" class="mobooking-bf__sidebar mobooking-bf__hidden">
            <h3 class="mobooking-bf__sidebar-title"><?php esc_html_e('Your Booking Summary', 'mobooking'); ?></h3>
            <div id="mobooking-bf-sidebar-content" class="mobooking-bf__sidebar-content">
                <!-- Summary content will be injected by JS -->
                <p><?php esc_html_e('Select options to see summary.', 'mobooking'); ?></p>
            </div>
            <div id="mobooking-bf-sidebar-pricing" class="mobooking-bf__sidebar-pricing">
                <div class="mobooking-bf__sidebar-price-item">
                    <span><?php esc_html_e('Subtotal:', 'mobooking'); ?></span>
                    <span id="mobooking-bf-sidebar-subtotal">--</span>
                </div>
                <div class="mobooking-bf__sidebar-price-item" id="mobooking-bf-sidebar-discount-item" style="display: none;">
                    <span><?php esc_html_e('Discount:', 'mobooking'); ?></span>
                    <span id="mobooking-bf-sidebar-discount-applied">--</span>
                </div>
                <div class="mobooking-bf__sidebar-price-item mobooking-bf__sidebar-price-item--total">
                    <strong><?php esc_html_e('Total:', 'mobooking'); ?></strong>
                    <strong id="mobooking-bf-sidebar-final-total">--</strong>
                </div>
            </div>
        </div> <!-- End .mobooking-bf__sidebar -->

    </div> <!-- End .mobooking-bf__layout-container -->
</div> <!-- End .mobooking-bf-wrapper -->

<script type="text/template" id="mobooking-bf-service-item-template">
    <div class="mobooking-bf__service-item" data-service-id="<%= service_id %>">
        <!-- image_placeholder -->
        <div class="mobooking-bf__service-item-content">
            <label class="mobooking-bf__label mobooking-bf__label--radio">
                <input type="radio" class="mobooking-bf__radio" name="selected_service" value="<%= service_id %>" data-service-id="<%= service_id %>">
                <span class="mobooking-bf__service-name"><%= name %></span>
                <!-- price_placeholder -->
            </label>
            <span class="mobooking-bf__service-duration">(<%= duration %> <?php esc_html_e('min', 'mobooking'); ?>)</span>
            <!-- description_placeholder -->
            <!-- icon_placeholder -->
        </div>
    </div>
</script>

<?php
// Comments about discount section structure have been addressed by moving it into the main Step 5 div.

if (get_query_var('mobooking_page_type') !== 'embed') { // Check for 'embed'
    get_footer(); // Or a custom minimal footer
    echo '</body>'; // Close body tag opened earlier
} else {
    echo '</body>'; // Close body tag for embed
}
?>
