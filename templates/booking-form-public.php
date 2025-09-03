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
    'success_message' => $bf_settings['bf_success_message'] ?? 'Thank you for your booking! We will contact you soon to confirm the details.',
    'service_card_display' => $bf_settings['bf_service_card_display'] ?? 'image',
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
    <?php $progress_style = $bf_settings['bf_progress_display_style'] ?? 'bar'; ?>
    <?php if ($progress_style !== 'none'): ?>
    <div class="mobooking-progress-container progress-style-<?php echo esc_attr($progress_style); ?>" id="mobooking-progress-container">
        <div class="mobooking-progress-bar">
            <div class="mobooking-progress-fill" id="mobooking-progress-fill"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Layout Container -->
    <div class="mobooking-layout">
        <!-- Form Container -->
        <div class="mobooking-form-card">
            <!-- Step 1: Area Check -->
            <?php if ($form_config['enable_area_check']): ?>
            <div class="mobooking-step-content active" id="mobooking-step-1">
                <div class="mobooking-step-content-wrap">
                <!-- <h2 class="mobooking-step-title"><?php //echo esc_html($bf_settings['bf_step_1_title'] ?? 'Check Service Area'); ?></h2> -->
                <form id="mobooking-area-check-form">
                    <div class="mobooking-form-group">
                        <label for="mobooking-zip" class="mobooking-label"><?php _e('Enter your postal code', 'mobooking'); ?></label>
                        <div class="mobooking-input-group">
                            <input type="text" id="mobooking-zip" class="mobooking-input" placeholder="<?php esc_attr_e('000 00', 'mobooking'); ?>" required maxlength="5">
                            <div class="area-name-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span id="mobooking-area-name" class="mobooking-area-name"></span>
                                
                            </div>
                        </div>
                    </div>
                    <div id="mobooking-location-feedback" class="mobooking-feedback"></div>
                    <div class="mobooking-button-group">
                        <div></div>
                        <button type="submit" class="mobooking-btn-primary">
                            <?php _e('Choose service', 'mobooking'); ?>
                        </button>
                    </div>
                </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 2: Service Selection -->
            <div class="mobooking-step-content <?php echo !$form_config['enable_area_check'] ? 'active' : ''; ?>" id="mobooking-step-2">
                <div class="mobooking-step-content-wrap">
                    <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M40,64H88V208H40a8,8,0,0,1-8-8V72A8,8,0,0,1,40,64Zm176,0H168V208h48a8,8,0,0,0,8-8V72A8,8,0,0,0,216,64Z" opacity="0.2"></path><path d="M216,56H176V48a24,24,0,0,0-24-24H104A24,24,0,0,0,80,48v8H40A16,16,0,0,0,24,72V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V72A16,16,0,0,0,216,56ZM96,48a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm64,24V200H96V72ZM40,72H80V200H40ZM216,200H176V72h40V200Z"></path></svg><?php echo esc_html($bf_settings['bf_step_2_title'] ?? 'Select service'); ?></h2>
                    <div id="mobooking-services-container">
                        <div style="text-align: center; padding: 40px 0;">
                            <div class="mobooking-spinner"></div>
                            <span><?php _e('Loading available services...', 'mobooking'); ?></span>
                        </div>
                    </div>
                    <div class="mobooking-button-group">
                        <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                            
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        </button>
                        <button type="button" class="mobooking-btn-primary" onclick="moBookingNextStep()"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </button>
                    </div>
                    <div id="mobooking-service-feedback" class="mobooking-feedback"></div>
                </div>
            </div>

            <!-- Step 3: Service Options / Add-ons -->
            <div class="mobooking-step-content" id="mobooking-step-3">
                <div class="mobooking-step-content-wrap">
                <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M224,128a96,96,0,1,1-96-96A96,96,0,0,1,224,128Z" opacity="0.2"></path><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm48-88a8,8,0,0,1-8,8H136v32a8,8,0,0,1-16,0V136H88a8,8,0,0,1,0-16h32V88a8,8,0,0,1,16,0v32h32A8,8,0,0,1,176,128Z"></path></svg><?php echo esc_html($bf_settings['bf_step_3_title'] ?? 'Select Add-ons'); ?></h2>
                <div id="mobooking-service-options-container">
                    <p><?php _e('Select your service first to see available options.', 'mobooking'); ?></p>
                </div>
                <div id="mobooking-options-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button type="button" class="mobooking-btn-primary" onclick="moBookingNextStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
                </div>
            </div>

            <!-- Step 4: Pet Information -->
            <div class="mobooking-step-content" id="mobooking-step-4">
            <div class="mobooking-step-content-wrap">
                <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M224,48v88c0,48.6-43,88-96,88s-96-39.4-96-88V48a8,8,0,0,1,13.66-5.66L67.6,67.6h0a102.87,102.87,0,0,1,120.8,0h0l21.94-25.24A8,8,0,0,1,224,48Z" opacity="0.2"></path><path d="M96,140a12,12,0,1,1-12-12A12,12,0,0,1,96,140Zm76-12a12,12,0,1,0,12,12A12,12,0,0,0,172,128Zm60-80v88c0,52.93-46.65,96-104,96S24,188.93,24,136V48A16,16,0,0,1,51.31,36.69c.14.14.26.27.38.41L69,57a111.22,111.22,0,0,1,118.1,0L204.31,37.1c.12-.14.24-.27.38-.41A16,16,0,0,1,232,48Zm-16,0-21.56,24.8A8,8,0,0,1,183.63,74,88.86,88.86,0,0,0,168,64.75V88a8,8,0,1,1-16,0V59.05a97.43,97.43,0,0,0-16-2.72V88a8,8,0,1,1-16,0V56.33a97.43,97.43,0,0,0-16,2.72V88a8,8,0,1,1-16,0V64.75A88.86,88.86,0,0,0,72.37,74a8,8,0,0,1-10.81-1.17L40,48v88c0,41.66,35.21,76,80,79.67V195.31l-13.66-13.66a8,8,0,0,1,11.32-11.31L128,180.68l10.34-10.34a8,8,0,0,1,11.32,11.31L136,195.31v20.36c44.79-3.69,80-38,80-79.67Z"></path></svg><?php echo esc_html($bf_settings['bf_step_4_title'] ?? 'Pet Information'); ?></h2>
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
                    <textarea id="mobooking-pet-details" class="mobooking-textarea" placeholder="<?php esc_attr_e('Please describe your pets (type, size, temperament, etc.)', 'mobooking'); ?>"></textarea>
                </div>
                <div id="mobooking-pet-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button type="button" class="mobooking-btn-primary" onclick="moBookingNextStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
            </div>
            </div>

            <!-- Step 5: Service Frequency -->
            <div class="mobooking-step-content" id="mobooking-step-5">
                <div class="mobooking-step-content-wrap">
                <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M216,48V88H40V48a8,8,0,0,1,8-8H208A8,8,0,0,1,216,48Z" opacity="0.2"></path><path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-48-56a8,8,0,0,1-8,8H136v16a8,8,0,0,1-16,0V160H104a8,8,0,0,1,0-16h16V128a8,8,0,0,1,16,0v16h16A8,8,0,0,1,160,152Z"></path></svg><?php echo esc_html($bf_settings['bf_step_5_title'] ?? 'Service Frequency'); ?></h2>
                <div class="mobooking-form-group">
                    <p class="mobooking-label"><?php _e('How often would you like this service?', 'mobooking'); ?></p>
                    <div class="mobooking-radio-group grid-group frequency-radio-group">
                        <label class="mobooking-radio-option">
                            <input type="radio" name="frequency" value="one-time" checked>
                            <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="10"/>
  <polyline points="12,6 12,12 16,14"/>
</svg><?php _e('One-time', 'mobooking'); ?></span>
                        </label>
                        <label class="mobooking-radio-option">
                            <input type="radio" name="frequency" value="weekly">
                            <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
  <line x1="16" y1="2" x2="16" y2="6"/>
  <line x1="8" y1="2" x2="8" y2="6"/>
  <line x1="3" y1="10" x2="21" y2="10"/>
  <path d="m9 16 2 2 4-4"/>
</svg><?php _e('Weekly', 'mobooking'); ?></span>
                        </label>
                        <label class="mobooking-radio-option">
                            <input type="radio" name="frequency" value="monthly">
                            <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
  <line x1="16" y1="2" x2="16" y2="6"/>
  <line x1="8" y1="2" x2="8" y2="6"/>
  <line x1="3" y1="10" x2="21" y2="10"/>
  <rect x="14" y="14" width="4" height="4"/>
</svg><?php _e('Monthly', 'mobooking'); ?></span>
                        </label>
                        <label class="mobooking-radio-option">
                            <input type="radio" name="frequency" value="daily">
                            <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="5"/>
  <line x1="12" y1="1" x2="12" y2="3"/>
  <line x1="12" y1="21" x2="12" y2="23"/>
  <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
  <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
  <line x1="1" y1="12" x2="3" y2="12"/>
  <line x1="21" y1="12" x2="23" y2="12"/>
  <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
  <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
</svg><?php _e('Daily', 'mobooking'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button type="button" class="mobooking-btn-primary" onclick="moBookingNextStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
                </div>
            </div>

            <!-- Step 6: Date & Time Selection -->
            <?php if ($form_config['enable_datetime_selection']): ?>
            <div class="mobooking-step-content" id="mobooking-step-6">
                <div class="mobooking-step-content-wrap">
                <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M216,48V88H40V48a8,8,0,0,1,8-8H208A8,8,0,0,1,216,48Z" opacity="0.2"></path><path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-38.34-85.66a8,8,0,0,1,0,11.32l-48,48a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L116,164.69l42.34-42.35A8,8,0,0,1,169.66,122.34Z"></path></svg><?php echo esc_html($bf_settings['bf_step_6_title'] ?? 'Select Date & Time'); ?></h2>
                <div class="mobooking-form-group">
                    <label for="mobooking-service-date" class="mobooking-label"><?php _e('Preferred Date', 'mobooking'); ?> *</label>
                    <input type="text" id="mobooking-service-date" class="mobooking-input" placeholder="<?php esc_attr_e('Select a date', 'mobooking'); ?>" readonly>
                </div>
                <div class="mobooking-form-group hidden" id="mobooking-time-slots-container">
                    <label class="mobooking-label"><?php _e('Available Time Slots', 'mobooking'); ?> *</label>
                    <div id="mobooking-time-slots" class="mobooking-time-slots"></div>
                </div>
                <div id="mobooking-datetime-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button type="button" class="mobooking-btn-primary" onclick="moBookingNextStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 7: Customer Details -->
            <div class="mobooking-step-content" id="mobooking-step-7">
                <div class="mobooking-step-content-wrap">
                <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M224,128a95.76,95.76,0,0,1-31.8,71.37A72,72,0,0,0,128,160a40,40,0,1,0-40-40,40,40,0,0,0,40,40,72,72,0,0,0-64.2,39.37h0A96,96,0,1,1,224,128Z" opacity="0.2"></path><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24ZM74.08,197.5a64,64,0,0,1,107.84,0,87.83,87.83,0,0,1-107.84,0ZM96,120a32,32,0,1,1,32,32A32,32,0,0,1,96,120Zm97.76,66.41a79.66,79.66,0,0,0-36.06-28.75,48,48,0,1,0-59.4,0,79.66,79.66,0,0,0-36.06,28.75,88,88,0,1,1,131.52,0Z"></path></svg><?php echo esc_html($bf_settings['bf_step_7_title'] ?? 'Your Details'); ?></h2>
                <div class="mobooking-grid grid-group">
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
                        <label for="mobooking-zip-readonly" class="mobooking-label"><?php _e('ZIP/Postal Code', 'mobooking'); ?></label>
                        <input type="text" id="mobooking-zip-readonly" class="mobooking-input" readonly>
                    </div>
                </div>
                <div class="mobooking-form-group">
                    <label for="mobooking-service-address" class="mobooking-label"><?php _e('Service Address', 'mobooking'); ?> *</label>
                    <input type="text" id="mobooking-service-address" class="mobooking-input" required>
                    <p class="description"><?php _e('Start typing your address and select from the suggestions.', 'mobooking'); ?></p>
                </div>

                <!-- Property Access -->
                <div class="mobooking-form-group">
                <h2 class="mobooking-step-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M200,40V224H168V32h24A8,8,0,0,1,200,40Z" opacity="0.2"></path><path d="M232,216H208V40a16,16,0,0,0-16-16H64A16,16,0,0,0,48,40V216H24a8,8,0,0,0,0,16H232a8,8,0,0,0,0-16Zm-40,0H176V40h16ZM64,40h96V216H64Zm80,92a12,12,0,1,1-12-12A12,12,0,0,1,144,132Z"></path></svg><?php _e('How can our service provider access your property?', 'mobooking'); ?></h2>

                    <div class="mobooking-radio-group grid-group">
                        <label class="mobooking-radio-option">
                            <input type="radio" name="property_access" value="home" checked>
                            <span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M104,216V152h48v64h64V120a8,8,0,0,0-2.34-5.66l-80-80a8,8,0,0,0-11.32,0l-80,80A8,8,0,0,0,40,120v96Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg><?php _e('I\'ll be at the property.', 'mobooking'); ?></span>
                        </label>
                        <label class="mobooking-radio-option">
                            <input type="radio" name="property_access" value="key">
                            <span><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 256 256"><path d="M216.57,39.43A80,80,0,0,0,83.91,120.78L28.69,176A15.86,15.86,0,0,0,24,187.31V216a16,16,0,0,0,16,16H72a8,8,0,0,0,8-8V208H96a8,8,0,0,0,8-8V184h16a8,8,0,0,0,5.66-2.34l9.56-9.57A79.73,79.73,0,0,0,160,176h.1A80,80,0,0,0,216.57,39.43ZM224,98.1c-1.09,34.09-29.75,61.86-63.89,61.9H160a63.7,63.7,0,0,1-23.65-4.51,8,8,0,0,0-8.84,1.68L116.69,168H96a8,8,0,0,0-8,8v16H72a8,8,0,0,0-8,8v16H40V187.31l58.83-58.82a8,8,0,0,0,1.68-8.84A63.72,63.72,0,0,1,96,95.92c0-34.14,27.81-62.8,61.9-63.89A64,64,0,0,1,224,98.1ZM192,76a12,12,0,1,1-12-12A12,12,0,0,1,192,76Z"></path></svg><?php _e('Key will be provided', 'mobooking'); ?></span>
                        </label>
                        <label class="mobooking-radio-option">
                            <input type="radio" name="property_access" value="lockbox">
                            <span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M112,139.72a32,32,0,1,1,32,0L160,176H96Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg><?php _e('Key lockbox available', 'mobooking'); ?></span>
                        </label>
                        <label class="mobooking-radio-option">
                            <input type="radio" name="property_access" value="other">
                            <span><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 256 256"><path d="M140,128a12,12,0,1,1-12-12A12,12,0,0,1,140,128ZM84,116a12,12,0,1,0,12,12A12,12,0,0,0,84,116Zm88,0a12,12,0,1,0,12,12A12,12,0,0,0,172,116Zm60,12A104,104,0,0,1,79.12,219.82L45.07,231.17a16,16,0,0,1-20.24-20.24l11.35-34.05A104,104,0,1,1,232,128Zm-16,0A88,88,0,1,0,51.81,172.06a8,8,0,0,1,.66,6.54L40,216,77.4,203.53a7.85,7.85,0,0,1,2.53-.42,8,8,0,0,1,4,1.08A88,88,0,0,0,216,128Z"></path></svg><?php _e('Other (please specify)', 'mobooking'); ?></span>
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
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button type="button" class="mobooking-btn-primary" onclick="moBookingNextStep()">
                        <?php _e('Review Booking', 'mobooking'); ?>
                    </button>
                </div>
            </div>
            </div>

            <!-- Step 8: Confirmation -->
            <div class="mobooking-step-content" id="mobooking-step-8">
                <h2 class="mobooking-step-title"><?php echo esc_html($bf_settings['bf_step_8_title'] ?? 'Confirm Your Booking'); ?></h2>
                <div class="mobooking-confirmation-summary" id="mobooking-confirmation-details">
                    <!-- Full summary will be dynamically injected here by JS -->
                </div>
                <div id="mobooking-confirmation-feedback" class="mobooking-feedback"></div>
                <div class="mobooking-button-group">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="moBookingPreviousStep()">
                        
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button type="button" class="mobooking-btn-primary" onclick="moBookingSubmitForm()">
                        <?php _e('Confirm Booking', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 9: Success Message -->
            <div class="mobooking-step-content" id="mobooking-step-9">
                <div class="mobooking-confirmation-container">
                    <div class="mobooking-success-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 256 256"><path d="M225.86,102.82c-3.77-3.94-7.67-8-9.14-11.57-1.36-3.27-1.44-8.69-1.52-13.94-.15-9.76-.31-20.82-8-28.51s-18.75-7.85-28.51-8c-5.25-.08-10.67-.16-13.94-1.52-3.56-1.47-7.63-5.37-11.57-9.14C146.28,23.51,138.44,16,128,16s-18.27,7.51-25.18,14.14c-3.94,3.77-8,7.67-11.57,9.14C88,40.64,82.56,40.72,77.31,40.8c-9.76.15-20.82.31-28.51,8S41,67.55,40.8,77.31c-.08,5.25-.16,10.67-1.52,13.94-1.47,3.56-5.37,7.63-9.14,11.57C23.51,109.72,16,117.56,16,128s7.51,18.27,14.14,25.18c3.77,3.94,7.67,8,9.14,11.57,1.36,3.27,1.44,8.69,1.52,13.94.15,9.76.31,20.82,8,28.51s18.75,7.85,28.51,8c5.25.08,10.67.16,13.94,1.52,3.56,1.47,7.63,5.37,11.57,9.14C109.72,232.49,117.56,240,128,240s18.27-7.51,25.18-14.14c3.94-3.77,8-7.67,11.57-9.14,3.27-1.36,8.69-1.44,13.94-1.52,9.76-.15,20.82-.31,28.51-8s7.85-18.75,8-28.51c.08-5.25.16-10.67,1.52-13.94,1.47-3.56,5.37-7.63,9.14-11.57C232.49,146.28,240,138.44,240,128S232.49,109.73,225.86,102.82Zm-11.55,39.29c-4.79,5-9.75,10.17-12.38,16.52-2.52,6.1-2.63,13.07-2.73,19.82-.1,7-.21,14.33-3.32,17.43s-10.39,3.22-17.43,3.32c-6.75.1-13.72.21-19.82,2.73-6.35,2.63-11.52,7.59-16.52,12.38S132,224,128,224s-9.15-4.92-14.11-9.69-10.17-9.75-16.52-12.38c-6.1-2.52-13.07-2.63-19.82-2.73-7-.1-14.33-.21-17.43-3.32s-3.22-10.39-3.32-17.43c-.1-6.75-.21-13.72-2.73-19.82-2.63-6.35-7.59-11.52-12.38-16.52S32,132,32,128s4.92-9.15,9.69-14.11,9.75-10.17,12.38-16.52c2.52-6.1,2.63-13.07,2.73-19.82.1-7,.21-14.33,3.32-17.43S70.51,56.9,77.55,56.8c6.75-.1,13.72-.21,19.82-2.73,6.35-2.63,11.52-7.59,16.52-12.38S124,32,128,32s9.15,4.92,14.11,9.69,10.17,9.75,16.52,12.38c6.1,2.52,13.07,2.63,19.82,2.73,7,.1,14.33.21,17.43,3.32s3.22,10.39,3.32,17.43c.1,6.75.21,13.72,2.73,19.82,2.63,6.35,7.59,11.52,12.38,16.52S224,124,224,128,219.08,137.15,214.31,142.11ZM173.66,98.34a8,8,0,0,1,0,11.32l-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35A8,8,0,0,1,173.66,98.34Z"></path></svg>
                    </div>
                    <h2 class="mobooking-step-confirmed-title">
                        <?php echo esc_html($bf_settings['bf_success_title'] ?? 'Booking Confirmed!'); ?>
                    </h2>
                    <p id="mobooking-success-message">
                        <?php echo esc_html($form_config['success_message']); ?>
                    </p>

                    <button type="button" class="mobooking-btn-primary" onclick="moBookingResetForm()">
                        <?php _e('Book Another Service', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Live Summary Sidebar -->
            <div class="mobooking-summary-card" id="mobooking-live-summary">
                <h3 class="summary-title"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2563eb" viewBox="0 0 256 256"><path d="M208,40V200a24,24,0,0,1-24,24H72a24,24,0,0,1-24-24V40Z" opacity="0.2"></path><path d="M168,128a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,128Zm-8,24H96a8,8,0,0,0,0,16h64a8,8,0,0,0,0-16ZM216,40V200a32,32,0,0,1-32,32H72a32,32,0,0,1-32-32V40a8,8,0,0,1,8-8H72V24a8,8,0,0,1,16,0v8h32V24a8,8,0,0,1,16,0v8h32V24a8,8,0,0,1,16,0v8h24A8,8,0,0,1,216,40Zm-16,8H184v8a8,8,0,0,1-16,0V48H136v8a8,8,0,0,1-16,0V48H88v8a8,8,0,0,1-16,0V48H56V200a16,16,0,0,0,16,16H184a16,16,0,0,0,16-16Z"></path></svg><?php _e('Summary', 'mobooking'); ?></h3>
                <div id="mobooking-summary-content">
                    <p><?php _e('Your selections will appear here.', 'mobooking'); ?></p>
                </div>
            </div>
        </div>


    </div>

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
get_footer('booking');
?>