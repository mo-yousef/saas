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

    <!-- Main Content: Form Steps -->
    <main class="mobooking-form-card">
        <div class="mobooking-header">
            <h1><?php echo esc_html($form_config['header_text']); ?></h1>
            <p><?php _e('Complete the steps below to schedule your service.', 'mobooking'); ?></p>
        </div>

        <?php if ($form_config['show_progress_bar']): ?>
        <div class="mobooking-progress-container" id="mobooking-progress-container">
            <div class="mobooking-progress-bar">
                <div class="mobooking-progress-fill" id="mobooking-progress-fill"></div>
            </div>
            <div class="mobooking-progress-steps">
                <!-- Progress steps will be dynamically generated by JS -->
            </div>
        </div>
        <?php endif; ?>

        <form id="mobooking-public-form">

            <!-- Step 1: Service Selection -->
            <div class="mobooking-step-content active" id="mobooking-step-1">
                <h2 class="mobooking-step-title"><?php _e('1. Choose Your Services', 'mobooking'); ?></h2>
                <div id="mobooking-services-container" class="mobooking-grid mobooking-grid-1">
                    <!-- Services will be loaded here by JS -->
                    <div class="mobooking-spinner-container">
                        <div class="mobooking-spinner"></div>
                        <span><?php _e('Loading services...', 'mobooking'); ?></span>
                    </div>
                </div>
                <div id="mobooking-service-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()">
                        <?php _e('Continue', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 2: Date & Time -->
            <div class="mobooking-step-content" id="mobooking-step-2">
                <h2 class="mobooking-step-title"><?php _e('2. Select Date & Time', 'mobooking'); ?></h2>
                <div class="mobooking-grid mobooking-grid-2">
                    <div class="mobooking-form-group">
                        <label for="mobooking-service-date" class="mobooking-label"><?php _e('Preferred Date', 'mobooking'); ?> *</label>
                        <input type="text" id="mobooking-service-date" class="mobooking-input" placeholder="<?php esc_attr_e('Select a date', 'mobooking'); ?>" readonly>
                    </div>
                    <div class="mobooking-form-group">
                        <label class="mobooking-label"><?php _e('Available Time Slots', 'mobooking'); ?> *</label>
                        <div id="mobooking-time-slots" class="mobooking-time-slots-grid">
                            <!-- Time slots will be populated here -->
                        </div>
                    </div>
                </div>
                <div id="mobooking-datetime-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()"><?php _e('Back', 'mobooking'); ?></button>
                    <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingNextStep()"><?php _e('Continue', 'mobooking'); ?></button>
                </div>
            </div>
            
            <!-- Step 3: Your Information -->
            <div class="mobooking-step-content" id="mobooking-step-3">
                <h2 class="mobooking-step-title"><?php _e('3. Your Information', 'mobooking'); ?></h2>
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
                <div class="mobooking-form-group">
                    <label for="mobooking-special-instructions" class="mobooking-label"><?php _e('Special Instructions', 'mobooking'); ?></label>
                    <textarea id="mobooking-special-instructions" class="mobooking-textarea" placeholder="<?php esc_attr_e('Any notes for our team?', 'mobooking'); ?>"></textarea>
                </div>
                <div id="mobooking-contact-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()"><?php _e('Back', 'mobooking'); ?></button>
                    <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingSubmitForm()"><?php _e('Submit Booking', 'mobooking'); ?></button>
                </div>
            </div>

            <!-- Step 4: Success Message -->
            <div class="mobooking-step-content" id="mobooking-step-4">
                <div class="mobooking-success-container">
                    <div class="mobooking-success-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h2 class="mobooking-step-title"><?php _e('Booking Submitted!', 'mobooking'); ?></h2>
                    <p id="mobooking-success-message"><?php echo esc_html($form_config['success_message']); ?></p>
                    <div id="mobooking-booking-summary" class="mobooking-summary-card compact">
                        <!-- Final summary will be populated here -->
                    </div>
                    <div class="mobooking-button-group">
                        <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="moBookingResetForm()"><?php _e('Book Another Service', 'mobooking'); ?></button>
                    </div>
                </div>
            </div>

        </form>
    </main>

    <!-- Sidebar: Live Summary -->
    <aside class="mobooking-summary-card" id="mobooking-live-summary">
        <h3><?php _e('Booking Summary', 'mobooking'); ?></h3>
        <div id="mobooking-summary-content">
            <p><?php _e('Your selections will appear here.', 'mobooking'); ?></p>
        </div>
    </aside>

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