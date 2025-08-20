<?php
/**
 * MoBooking Public Booking Form
 * @package MoBooking
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load WordPress header
get_header('booking');

// Get tenant ID from URL or current user
$tenant_id = get_current_user_id() ?: 1; // Fallback for demo

// Check if we have a tenant slug in the URL
$tenant_slug = get_query_var('tenant_id', '');
if (empty($tenant_slug) && isset($_GET['tenant'])) {
    $tenant_slug = sanitize_text_field($_GET['tenant']);
}

// If we have a tenant slug, find the corresponding user ID
if (!empty($tenant_slug)) {
    global $wpdb;
    $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
    $tenant_from_slug = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $settings_table WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
        $tenant_slug
    ));
    
    if ($tenant_from_slug) {
        $tenant_id = intval($tenant_from_slug);
    } else {
        // Fallback: try to find user by slug
        $user = get_user_by('slug', $tenant_slug);
        if ($user) {
            $tenant_id = $user->ID;
        }
    }
}

// Initialize settings manager
$settings = new \MoBooking\Classes\Settings();
$bf_settings = $settings->get_booking_form_settings($tenant_id);
$biz_settings = $settings->get_business_settings($tenant_id);

// Form configuration from settings
$form_config = [
    'enable_area_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
    'enable_pet_information' => ($bf_settings['bf_enable_pet_information'] ?? '1') === '1',
    'enable_service_frequency' => ($bf_settings['bf_enable_service_frequency'] ?? '1') === '1',
    'enable_datetime_selection' => ($bf_settings['bf_enable_datetime_selection'] ?? '1') === '1',
    'enable_property_access' => ($bf_settings['bf_enable_property_access'] ?? '1') === '1',
    'form_enabled' => ($bf_settings['bf_form_enabled'] ?? '1') === '1',
    'theme_color' => $bf_settings['bf_theme_color'] ?? '#1abc9c',
    'header_text' => $bf_settings['bf_header_text'] ?? 'Book Our Services Online',
    'show_progress_bar' => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
    'success_message' => $bf_settings['bf_success_message'] ?? 'Thank you for your booking! We will contact you soon to confirm the details.',
];

// Check if form is disabled
if (!$form_config['form_enabled']) {
    $maintenance_message = $bf_settings['bf_maintenance_message'] ?? 'We are temporarily not accepting new bookings. Please check back later.';
    echo '<div class="mobooking-maintenance-notice">' . esc_html($maintenance_message) . '</div>';
    get_footer();
    return;
}

// Enqueue necessary styles and scripts
wp_enqueue_script('jquery');
wp_enqueue_script('flatpickr', 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js', ['jquery'], '4.6.13', true);
wp_enqueue_style('flatpickr', 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css', [], '4.6.13');

// Prepare localized script data
$script_data = [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
    'tenant_id' => $tenant_id,
    'form_config' => $form_config,
    'i18n' => [
        'zip_required' => __('Please enter your ZIP code.', 'mobooking'),
        'country_required' => __('Please select your country.', 'mobooking'),
        'checking_availability' => __('Checking availability...', 'mobooking'),
        'service_available' => __('Service is available in your area!', 'mobooking'),
        'service_not_available' => __('Service is not available in your area.', 'mobooking'),
        'loading_services' => __('Loading services...', 'mobooking'),
        'select_service' => __('Please select at least one service.', 'mobooking'),
        'pet_details_required' => __('Please provide details about your pets.', 'mobooking'),
        'select_date' => __('Please select a date.', 'mobooking'),
        'select_time' => __('Please select a time slot.', 'mobooking'),
        'name_required' => __('Please enter your name.', 'mobooking'),
        'email_required' => __('Please enter a valid email address.', 'mobooking'),
        'phone_required' => __('Please enter your phone number.', 'mobooking'),
        'address_required' => __('Please enter the service address.', 'mobooking'),
        'access_details_required' => __('Please provide access details.', 'mobooking'),
        'submitting_booking' => __('Submitting booking...', 'mobooking'),
        'booking_success' => __('Booking submitted successfully!', 'mobooking'),
        'booking_error' => __('There was an error submitting your booking. Please try again.', 'mobooking'),
    ]
];

?>

<div class="mobooking-public-form-container">
    <!-- Header -->
    <div class="mobooking-header">
        <h1><?php echo esc_html($form_config['header_text']); ?></h1>
        <p><?php _e('Complete the steps below to schedule your service', 'mobooking'); ?></p>
    </div>

    <!-- Progress Bar -->
    <?php if ($form_config['show_progress_bar']): ?>
    <div class="mobooking-progress-container" id="mobooking-progress-container">
        <div class="mobooking-progress-steps">
            <?php
            $total_steps = 8;
            $visible_steps = [];

            // Calculate visible steps based on enabled features
            $step_counter = 1;
            if ($form_config['enable_area_check']) $visible_steps[] = $step_counter++;
            $visible_steps[] = $step_counter++; // Service selection (always enabled)
            $visible_steps[] = $step_counter++; // Service options (always enabled)
            if ($form_config['enable_pet_information']) $visible_steps[] = $step_counter++;
            if ($form_config['enable_service_frequency']) $visible_steps[] = $step_counter++;
            if ($form_config['enable_datetime_selection']) $visible_steps[] = $step_counter++;
            if ($form_config['enable_property_access']) $visible_steps[] = $step_counter++;
            $visible_steps[] = $step_counter++; // Success (always enabled)

            foreach ($visible_steps as $i => $step):
            ?>
            <div class="mobooking-step-indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-step="<?php echo $step; ?>">
                <?php echo $step; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mobooking-progress-bar">
            <div class="mobooking-progress-fill" id="mobooking-progress-fill"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Form Container -->
    <div class="mobooking-form-card">
        <!-- Step 1: Area Check -->
        <?php if ($form_config['enable_area_check']): ?>
        <div class="mobooking-step-content active" id="mobooking-step-1">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_1_title'] ?? 'Step 1: Check Service Area'); ?></h2>
            <form id="mobooking-area-check-form">
                <div class="mobooking-grid mobooking-grid-2">
                    <div class="mobooking-form-group" style="grid-column: 1 / -1;">
                        <label for="mobooking-zip" class="mobooking-label"><?php _e('ZIP/Postal Code', 'mobooking'); ?> *</label>
                        <input type="text" id="mobooking-zip" class="mobooking-input" placeholder="<?php esc_attr_e('Enter your ZIP code', 'mobooking'); ?>" required>
                    </div>
                </div>
                <div id="mobooking-location-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <div></div>
                    <button type="submit" class="mobooking-btn mobooking-btn-primary">
                        <?php _e('Check Availability', 'mobooking'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Step 2: Service Selection -->
        <div class="mobooking-step-content <?php echo !$form_config['enable_area_check'] ? 'active' : ''; ?>" id="mobooking-step-2">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_2_title'] ?? 'Step 2: Choose Services'); ?></h2>
            <div id="mobooking-services-container">
                <div style="text-align: center; padding: 40px 0;">
                    <div class="mobooking-spinner"></div>
                    <span><?php _e('Loading available services...', 'mobooking'); ?></span>
                </div>
            </div>
            <div id="mobooking-service-feedback" class="mobooking-feedback"></div>
            <div class="mobooking-button-group">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                    <?php _e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()">
                    <?php _e('Continue', 'mobooking'); ?>
                </button>
            </div>
        </div>

        <!-- Step 3: Service Options -->
        <div class="mobooking-step-content" id="mobooking-step-3">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_3_title'] ?? 'Step 3: Service Options'); ?></h2>
            <div id="mobooking-service-options-container">
                <p class="text-gray-600"><?php _e('Select your service first to see available options.', 'mobooking'); ?></p>
            </div>
            <div id="mobooking-options-feedback" class="mobooking-feedback"></div>
            <div class="mobooking-button-group">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                    <?php _e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()">
                    <?php _e('Continue', 'mobooking'); ?>
                </button>
            </div>
        </div>

        <!-- Step 4: Pet Information -->
        <?php if ($form_config['enable_pet_information']): ?>
        <div class="mobooking-step-content" id="mobooking-step-4">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_4_title'] ?? 'Step 4: Pet Information'); ?></h2>
            <div class="mobooking-form-group">
                <p class="mobooking-label"><?php _e('Do you have pets at the service location?', 'mobooking'); ?></p>
                <div class="mobooking-radio-group">
                    <label class="mobooking-radio-option">
                        <input type="radio" name="has_pets" value="no" checked>
                        <span><?php _e('No, I don\'t have pets', 'mobooking'); ?></span>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="has_pets" value="yes">
                        <span><?php _e('Yes, I have pets', 'mobooking'); ?></span>
                    </label>
                </div>
            </div>
            <div class="mobooking-form-group hidden" id="mobooking-pet-details-container">
                <label for="mobooking-pet-details" class="mobooking-label"><?php _e('Pet Details', 'mobooking'); ?> *</label>
                <textarea id="mobooking-pet-details" class="mobooking-textarea" placeholder="<?php esc_attr_e('Please describe your pets (type, size, temperament, special instructions)', 'mobooking'); ?>"></textarea>
            </div>
            <div id="mobooking-pet-feedback" class="mobooking-feedback"></div>
            <div class="mobooking-button-group">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                    <?php _e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()">
                    <?php _e('Continue', 'mobooking'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 5: Service Frequency -->
        <?php if ($form_config['enable_service_frequency']): ?>
        <div class="mobooking-step-content" id="mobooking-step-5">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_5_title'] ?? 'Step 5: Service Frequency'); ?></h2>
            <div class="mobooking-form-group">
                <p class="mobooking-label"><?php _e('How often would you like this service?', 'mobooking'); ?></p>
                <div class="mobooking-grid mobooking-grid-2">
                    <label class="mobooking-radio-option">
                        <input type="radio" name="frequency" value="one-time" checked>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;"><?php _e('One-time', 'mobooking'); ?></div>
                            <div style="font-size: 14px; color: #6b7280;"><?php _e('Schedule a single service', 'mobooking'); ?></div>
                        </div>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="frequency" value="weekly">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;"><?php _e('Weekly', 'mobooking'); ?></div>
                            <div style="font-size: 14px; color: #6b7280;"><?php _e('Recurring weekly service', 'mobooking'); ?></div>
                        </div>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="frequency" value="monthly">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;"><?php _e('Monthly', 'mobooking'); ?></div>
                            <div style="font-size: 14px; color: #6b7280;"><?php _e('Recurring monthly service', 'mobooking'); ?></div>
                        </div>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="frequency" value="daily">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;"><?php _e('Daily', 'mobooking'); ?></div>
                            <div style="font-size: 14px; color: #6b7280;"><?php _e('Daily recurring service', 'mobooking'); ?></div>
                        </div>
                    </label>
                </div>
            </div>
            <div class="mobooking-button-group">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                    <?php _e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()">
                    <?php _e('Continue', 'mobooking'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 6: Date & Time Selection -->
        <?php if ($form_config['enable_datetime_selection']): ?>
        <div class="mobooking-step-content" id="mobooking-step-6">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_6_title'] ?? 'Step 6: Select Date & Time'); ?></h2>
            <div class="mobooking-form-group">
                <label for="mobooking-service-date" class="mobooking-label"><?php _e('Preferred Date', 'mobooking'); ?> *</label>
                <input type="text" id="mobooking-service-date" class="mobooking-input" placeholder="<?php esc_attr_e('Select a date', 'mobooking'); ?>" readonly>
            </div>
            <div class="mobooking-form-group hidden" id="mobooking-time-slots-container">
                <label for="mobooking-service-date" class="mobooking-label"><?php _e('Available Time Slots', 'mobooking'); ?> *</label>
                <div id="mobooking-time-slots" class="mobooking-time-slots">
                    <!-- Time slots will be populated here -->
                </div>
            </div>
            <div id="mobooking-datetime-feedback" class="mobooking-feedback"></div>
            <div class="mobooking-button-group">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                    <?php _e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()">
                    <?php _e('Continue', 'mobooking'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 7: Contact & Property Access -->
        <?php if ($form_config['enable_property_access']): ?>
        <div class="mobooking-step-content" id="mobooking-step-7">
            <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_7_title'] ?? 'Step 7: Contact & Property Access'); ?></h2>

            <!-- Customer Details -->
            <div class="mobooking-grid mobooking-grid-2">
                <div class="mobooking-form-group">
                    <label for="mobooking-customer-name" class="mobooking-label"><?php _e('Full Name', 'mobooking'); ?> *</label>
                    <input type="text" id="mobooking-customer-name" class="mobooking-input" required>
                </div>
                <div class="mobooking-form-group">
                    <label for="mobooking-customer-email" class="mobooking-label"><?php _e('Email Address', 'mobooking'); ?> *</label>
                    <input type="email" id="mobooking-customer-email" class="mobooking-input" required>
                </div>
                <div class="mobooking-form-group">
                    <label for="mobooking-customer-phone" class="mobooking-label"><?php _e('Phone Number', 'mobooking'); ?> *</label>
                    <input type="tel" id="mobooking-customer-phone" class="mobooking-input" required>
                </div>
                <div class="mobooking-form-group">
                    <label for="mobooking-service-address" class="mobooking-label"><?php _e('Service Address', 'mobooking'); ?> *</label>
                    <input type="text" id="mobooking-service-address" class="mobooking-input" required>
                </div>
            </div>

            <!-- Property Access -->
            <div class="mobooking-form-group">
                <p class="mobooking-label"><?php _e('How can our service provider access your property?', 'mobooking'); ?></p>
                <div class="mobooking-radio-group">
                    <label class="mobooking-radio-option">
                        <input type="radio" name="property_access" value="home" checked>
                        <span><?php _e('I\'ll be home during service', 'mobooking'); ?></span>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="property_access" value="key">
                        <span><?php _e('Key will be provided', 'mobooking'); ?></span>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="property_access" value="lockbox">
                        <span><?php _e('Key lockbox available', 'mobooking'); ?></span>
                    </label>
                    <label class="mobooking-radio-option">
                        <input type="radio" name="property_access" value="other">
                        <span><?php _e('Other (please specify)', 'mobooking'); ?></span>
                    </label>
                </div>
            </div>

            <div class="mobooking-form-group hidden" id="mobooking-custom-access-details">
                <label for="mobooking-access-instructions" class="mobooking-label"><?php _e('Access Instructions', 'mobooking'); ?> *</label>
                <textarea id="mobooking-access-instructions" class="mobooking-textarea" placeholder="<?php esc_attr_e('Please provide detailed access instructions', 'mobooking'); ?>"></textarea>
            </div>

            <!-- Special Instructions -->
            <div class="mobooking-form-group">
                <label for="mobooking-special-instructions" class="mobooking-label"><?php _e('Special Instructions', 'mobooking'); ?> (<?php _e('Optional', 'mobooking'); ?>)</label>
                <textarea id="mobooking-special-instructions" class="mobooking-textarea" placeholder="<?php esc_attr_e('Any special instructions or notes for our team', 'mobooking'); ?>"></textarea>
            </div>

            <div id="mobooking-contact-feedback" class="mobooking-feedback"></div>
            <div class="mobooking-button-group">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                    <?php _e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingSubmitForm()">
                    <?php _e('Submit Booking', 'mobooking'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 8: Success Message -->
        <div class="mobooking-step-content" id="mobooking-step-8">
            <div style="text-align: center; padding: 40px 0;">
                <div class="mobooking-success-icon">
                    <svg width="30" height="30" fill="none" stroke="#10b981" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="mobooking-step-title" style="text-align: center; color: #10b981;">
                    <?php echo esc_html($bf_settings['bf_step_8_title'] ?? 'Booking Confirmed!'); ?>
                </h2>
                <p style="color: #6b7280; margin-bottom: 30px;" id="mobooking-success-message">
                    <?php echo esc_html($form_config['success_message']); ?>
                </p>

                <!-- Booking Summary -->
                <div class="mobooking-summary-card" style="text-align: left;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; color: #374151;">
                        <?php _e('Booking Summary', 'mobooking'); ?>
                    </h3>
                    <div id="mobooking-booking-summary" style="font-size: 14px; color: #6b7280;">
                        <!-- Summary will be populated here -->
                    </div>
                </div>

                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingResetForm()">
                    <?php _e('Book Another Service', 'mobooking'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Live Summary -->
    <div class="mobooking-summary-card" id="mobooking-live-summary">
        <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; color: #374151;">
            <?php _e('Booking Summary', 'mobooking'); ?>
        </h3>
        <div id="mobooking-summary-content" style="font-size: 14px; color: #6b7280;">
            <p><?php _e('Complete the form to see your booking summary', 'mobooking'); ?></p>
        </div>
    </div>

    <!-- Debug Section (Development Only) -->
    <?php //if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <div class="mobooking-debug" id="mobooking-debug-section">
        <details>
            <summary style="cursor: pointer; font-weight: bold; margin-bottom: 10px;">
                🔧 <?php _e('Debug Information (Development)', 'mobooking'); ?>
            </summary>
            <div style="display: grid; gap: 15px;">
                <div>
                    <h4 style="font-weight: bold;"><?php _e('Form Configuration:', 'mobooking'); ?></h4>
                    <pre id="mobooking-debug-config" style="font-size: 11px; background: white; padding: 10px; border-radius: 4px; border: 1px solid #ccc; overflow: auto; max-height: 200px;"></pre>
                </div>
                <div>
                    <h4 style="font-weight: bold;"><?php _e('Form Data:', 'mobooking'); ?></h4>
                    <pre id="mobooking-debug-data" style="font-size: 11px; background: white; padding: 10px; border-radius: 4px; border: 1px solid #ccc; overflow: auto; max-height: 200px;"></pre>
                </div>
                <div>
                    <h4 style="font-weight: bold;"><?php _e('API Responses:', 'mobooking'); ?></h4>
                    <pre id="mobooking-debug-responses" style="font-size: 11px; background: white; padding: 10px; border-radius: 4px; border: 1px solid #ccc; overflow: auto; max-height: 200px;"></pre>
                </div>
            </div>
        </details>
    </div>
    <?php //endif; ?>
</div>



<?php
/**
 * Output any additional PHP processing or hooks here
 */

// Hook for additional form customization
do_action('mobooking_after_public_form_render', $tenant_id, $form_config);

// Add any custom CSS from settings
if (!empty($bf_settings['bf_custom_css'])) {
    echo '<style>' . wp_kses_post($bf_settings['bf_custom_css']) . '</style>';
}

// Load WordPress footer
get_footer();
?>