<?php
/**
 * Template for the Modern MoBooking Public Booking Form
 */

get_header('booking');

// Get tenant ID from slug
$tenant_slug = get_query_var('mobooking_slug');
$tenant_user_id = \MoBooking\Classes\Routes\BookingFormRouter::get_user_id_by_slug($tenant_slug);

if (!$tenant_user_id) {
    echo '<div class="mobooking-error"><p>Booking form not found.</p></div>';
    get_footer('booking');
    return;
}

// Get form settings
$settings_manager = new \MoBooking\Classes\Settings();
$form_settings = $settings_manager->get_booking_form_settings($tenant_user_id);
$biz_settings = $settings_manager->get_business_settings($tenant_user_id);

// Script and style enqueueing is now handled by the mobooking_scripts function in functions/theme-setup.php

?>

<div class="mobooking-bf-wrapper">
    <h1 class="mobooking-bf-main-title"><?php echo esc_html($form_settings['bf_header_text'] ?? 'Book Our Services'); ?></h1>

    <div class="mobooking-progress-wrapper" <?php echo ($form_settings['bf_show_progress_bar'] ?? '1') === '0' ? 'style="display:none;"' : ''; ?>>
        <div class="mobooking-progress-bar-bg">
            <div class="mobooking-progress-bar" style="width: 0%;"></div>
        </div>
        <div class="mobooking-progress-text">Step 1 of 8</div>
    </div>

    <div class="mobooking-bf__layout-container">
        <div class="mobooking-bf__main-content">

            <!-- Step 1: Area/Location Check -->
            <div id="mobooking-bf-step-1-location" class="mobooking-bf__step" data-step="1" <?php echo ($form_settings['bf_enable_location_check'] ?? '1') === '0' ? 'data-disabled="true"' : ''; ?>>
                <h2 class="mobooking-bf__step-title">Step 1: Check Availability in Your Area</h2>
                <form id="mobooking-bf-location-form">
                    <div class="mobooking-bf__form-group">
                        <label for="mobooking-bf-zip" class="mobooking-bf__label">ZIP / Postal Code <span class="mobooking-bf__required-indicator">*</span></label>
                        <input type="text" id="mobooking-bf-zip" class="mobooking-bf__input" required>
                    </div>
                    <div class="mobooking-bf__form-group">
                        <label for="mobooking-bf-country" class="mobooking-bf__label">Country <span class="mobooking-bf__required-indicator">*</span></label>
                        <input type="text" id="mobooking-bf-country" class="mobooking-bf__input" required>
                    </div>
                    <div id="mobooking-bf-feedback" class="mobooking-bf__feedback" style="display:none;"></div>
                    <div class="mobooking-bf__button-group">
                        <button type="submit" class="mobooking-bf__button mobooking-bf__button--primary">Check Area</button>
                    </div>
                </form>
            </div>

            <!-- Step 2: Service Selection -->
            <div id="mobooking-bf-step-2-services" class="mobooking-bf__step" data-step="2" style="display:none;">
                <h2 class="mobooking-bf__step-title">Step 2: Select Your Service(s)</h2>
                <div id="mobooking-bf-services-list" class="mobooking-bf-items-list">
                    <p>Loading services...</p>
                </div>
                <div id="mobooking-bf-step-2-feedback" class="mobooking-bf__feedback" style="display:none;"></div>
                <div class="mobooking-bf__button-group">
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--secondary" data-nav="back">Back</button>
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--primary" data-nav="next">Next</button>
                </div>
            </div>

            <!-- Step 3: Service Options -->
            <div id="mobooking-bf-step-3-options" class="mobooking-bf__step" data-step="3" style="display:none;">
                <h2 class="mobooking-bf__step-title">Step 3: Configure Options</h2>
                <div id="mobooking-bf-service-options-display"></div>
                <div id="mobooking-bf-step-3-feedback" class="mobooking-bf__feedback" style="display:none;"></div>
                <div class="mobooking-bf__button-group">
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--secondary" data-nav="back">Back</button>
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--primary" data-nav="next">Next</button>
                </div>
            </div>

            <!-- Step 4: Pet Information -->
            <div id="mobooking-bf-step-4-pets" class="mobooking-bf__step" data-step="4" style="display:none;" <?php echo ($form_settings['bf_enable_pet_information'] ?? '1') === '0' ? 'data-disabled="true"' : ''; ?>>
                <h2 class="mobooking-bf__step-title">Step 4: Pet Information</h2>
                <div class="mobooking-bf__form-group">
                    <label class="mobooking-bf__label">Do you have pets?</label>
                    <label class="mobooking-bf__label--radio"><input type="radio" name="has_pets" value="yes" class="mobooking-bf__radio"> Yes</label>
                    <label class="mobooking-bf__label--radio"><input type="radio" name="has_pets" value="no" class="mobooking-bf__radio" checked> No</label>
                </div>
                <div id="mobooking-bf-pet-details-group" class="mobooking-bf__form-group" style="display:none;">
                    <label for="mobooking-bf-pet-details" class="mobooking-bf__label">Please provide details about your pet(s)</label>
                    <textarea id="mobooking-bf-pet-details" class="mobooking-bf__textarea" placeholder="e.g., 1 friendly Golden Retriever, 2 cats that will hide."></textarea>
                </div>
                <div class="mobooking-bf__button-group">
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--secondary" data-nav="back">Back</button>
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--primary" data-nav="next">Next</button>
                </div>
            </div>

            <!-- Step 5: Service Frequency -->
            <div id="mobooking-bf-step-5-frequency" class="mobooking-bf__step" data-step="5" style="display:none;" <?php echo ($form_settings['bf_enable_service_frequency'] ?? '1') === '0' ? 'data-disabled="true"' : ''; ?>>
                <h2 class="mobooking-bf__step-title">Step 5: Service Frequency</h2>
                <div class="mobooking-bf__form-group">
                    <label class="mobooking-bf__label--radio"><input type="radio" name="service_frequency" value="one-time" class="mobooking-bf__radio" checked> One-time</label>
                    <label class="mobooking-bf__label--radio"><input type="radio" name="service_frequency" value="daily" class="mobooking-bf__radio"> Daily</label>
                    <label class="mobooking-bf__label--radio"><input type="radio" name="service_frequency" value="weekly" class="mobooking-bf__radio"> Weekly</label>
                    <label class="mobooking-bf__label--radio"><input type="radio" name="service_frequency" value="monthly" class="mobooking-bf__radio"> Monthly</label>
                </div>
                <div class="mobooking-bf__button-group">
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--secondary" data-nav="back">Back</button>
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--primary" data-nav="next">Next</button>
                </div>
            </div>

            <!-- Step 6: Date & Time Selection -->
            <div id="mobooking-bf-step-6-datetime" class="mobooking-bf__step" data-step="6" style="display:none;" <?php echo ($form_settings['bf_enable_datetime_selection'] ?? '1') === '0' ? 'data-disabled="true"' : ''; ?>>
                <h2 class="mobooking-bf__step-title">Step 6: Select Date & Time</h2>
                <div class="mobooking-bf__form-group">
                    <label for="mobooking-bf-preferred-date" class="mobooking-bf__label">Preferred Date</label>
                    <input type="text" id="mobooking-bf-preferred-date" class="mobooking-bf__input mobooking-datepicker">
                </div>
                <div class="mobooking-bf__form-group">
                    <label class="mobooking-bf__label">Available Times</label>
                    <div id="mobooking-bf-time-slots" class="mobooking-time-slots-grid">
                        <p>Please select a date to see available times.</p>
                    </div>
                </div>
                <div class="mobooking-bf__button-group">
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--secondary" data-nav="back">Back</button>
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--primary" data-nav="next">Next</button>
                </div>
            </div>

            <!-- Step 7: Property Access & Contact Details -->
            <div id="mobooking-bf-step-7-contact" class="mobooking-bf__step" data-step="7" style="display:none;">
                <h2 class="mobooking-bf__step-title">Step 7: Contact & Access Details</h2>
                <div class="mobooking-section" <?php echo ($form_settings['bf_enable_property_access'] ?? '1') === '0' ? 'style="display:none;"' : ''; ?>>
                    <h3 class="mobooking-section-title">Property Access</h3>
                    <div class="mobooking-bf__form-group">
                        <select id="mobooking-bf-property-access" class="mobooking-bf__select">
                            <option value="im_home">I'll be home</option>
                            <option value="key_mat">Key under the mat</option>
                            <option value="lockbox">Lockbox</option>
                            <option value="concierge">Building concierge</option>
                            <option value="other">Other (please specify)</option>
                        </select>
                    </div>
                    <div id="mobooking-bf-property-access-other-group" class="mobooking-bf__form-group" style="display:none;">
                        <label for="mobooking-bf-property-access-other" class="mobooking-bf__label">Other Access Instructions</label>
                        <textarea id="mobooking-bf-property-access-other" class="mobooking-bf__textarea"></textarea>
                    </div>
                </div>
                <div class="mobooking-section">
                    <h3 class="mobooking-section-title">Contact Details</h3>
                    <form id="mobooking-bf-details-form">
                        <div class="mobooking-bf__form-group">
                            <label for="mobooking-bf-customer-name" class="mobooking-bf__label">Full Name <span class="mobooking-bf__required-indicator">*</span></label>
                            <input type="text" id="mobooking-bf-customer-name" class="mobooking-bf__input" required>
                        </div>
                        <div class="mobooking-bf__form-group">
                            <label for="mobooking-bf-customer-email" class="mobooking-bf__label">Email <span class="mobooking-bf__required-indicator">*</span></label>
                            <input type="email" id="mobooking-bf-customer-email" class="mobooking-bf__input" required>
                        </div>
                        <div class="mobooking-bf__form-group">
                            <label for="mobooking-bf-customer-phone" class="mobooking-bf__label">Phone</label>
                            <input type="tel" id="mobooking-bf-customer-phone" class="mobooking-bf__input">
                        </div>
                        <div class="mobooking-bf__form-group">
                            <label for="mobooking-bf-service-address" class="mobooking-bf__label">Service Address <span class="mobooking-bf__required-indicator">*</span></label>
                            <textarea id="mobooking-bf-service-address" class="mobooking-bf__textarea" required></textarea>
                        </div>
                        <div class="mobooking-bf__form-group">
                            <label for="mobooking-bf-special-instructions" class="mobooking-bf__label">Special Instructions</label>
                            <textarea id="mobooking-bf-special-instructions" class="mobooking-bf__textarea" placeholder="e.g., focus on the kitchen, allergy to certain chemicals."></textarea>
                        </div>
                    </form>
                </div>
                <div id="mobooking-bf-step-7-feedback" class="mobooking-bf__feedback" style="display:none;"></div>
                <div class="mobooking-bf__button-group">
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--secondary" data-nav="back">Back</button>
                    <button type="button" class="mobooking-bf__button mobooking-bf__button--primary" data-nav="next">Review & Book</button>
                </div>
            </div>

            <!-- Step 8: Success/Confirmation -->
            <div id="mobooking-bf-step-8-success" class="mobooking-bf__step" data-step="8" style="display:none;">
                <div id="mobooking-bf-confirmation-message">
                    <h2 class="mobooking-success-title">Booking Confirmed!</h2>
                    <p><?php echo esc_html($form_settings['bf_success_message']); ?></p>
                    <div class="mobooking-booking-details">
                        <p><strong>Booking Reference:</strong> <span id="mobooking-conf-ref"></span></p>
                        <p><strong>Service:</strong> <span id="mobooking-conf-service"></span></p>
                        <p><strong>Date & Time:</strong> <span id="mobooking-conf-datetime"></span></p>
                        <p><strong>Total:</strong> <span id="mobooking-conf-total"></span></p>
                    </div>
                    <div class="mobooking-success-actions">
                        <button class="mobooking-bf__button mobooking-bf__button--secondary" onclick="window.print();">Print Confirmation</button>
                    </div>
                </div>
            </div>

        </div>

        <div class="mobooking-bf__sidebar">
            <h3 class="mobooking-bf__sidebar-title">Booking Summary</h3>
            <div id="mobooking-bf-sidebar-summary">
                <p class="mobooking-bf__sidebar-empty">Your selections will appear here.</p>
            </div>
            <div class="mobooking-bf__sidebar-pricing">
                 <div class="mobooking-bf__sidebar-price-item">
                    <span>Subtotal</span>
                    <span id="mobooking-bf-sidebar-subtotal">$0.00</span>
                </div>
                <div class="mobooking-bf__sidebar-price-item mobooking-bf__hidden" id="mobooking-discount-display-sidebar">
                    <span>Discount</span>
                    <span id="mobooking-bf-sidebar-discount-amount">-$0.00</span>
                </div>
                <hr>
                <div class="mobooking-bf__sidebar-price-item mobooking-bf__sidebar-price-item--total">
                    <span>Total</span>
                    <span id="mobooking-bf-sidebar-total">$0.00</span>
                </div>
            </div>
            <div class="mobooking-bf__discount-section" <?php echo ($form_settings['bf_allow_discount_codes'] ?? '1') === '0' ? 'style="display:none;"' : ''; ?>>
                <div class="mobooking-bf__form-group">
                    <label for="mobooking-bf-discount-code" class="mobooking-bf__label">Discount Code</label>
                    <input type="text" id="mobooking-bf-discount-code" class="mobooking-bf__input">
                    <button type="button" id="mobooking-bf-apply-discount-btn" class="mobooking-bf__button">Apply</button>
                </div>
                <div id="mobooking-bf-discount-feedback" class="mobooking-bf__feedback" style="display:none;"></div>
            </div>
             <div class="mobooking-bf__button-group" id="mobooking-final-submit-button-container" style="display: none;">
                <button type="button" id="mobooking-bf-final-submit-btn" class="mobooking-bf__button mobooking-bf__button--primary" style="width: 100%;">Confirm Booking</button>
            </div>
        </div>
    </div>
</div>

<?php
get_footer('booking');
?>
