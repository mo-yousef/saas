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

// Localize the script
wp_localize_script('mobooking-booking-form-public', 'MOBOOKING_CONFIG', $script_data);
?>

<div class="mobooking-public-form-container">
    <style>
        .mobooking-public-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .mobooking-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .mobooking-header h1 {
            color: <?php echo esc_attr($form_config['theme_color']); ?>;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .mobooking-progress-container {
            margin-bottom: 30px;
        }

        .mobooking-progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .mobooking-step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .mobooking-step-indicator.active {
            background: <?php echo esc_attr($form_config['theme_color']); ?>;
            color: white;
        }

        .mobooking-step-indicator.completed {
            background: #10b981;
            color: white;
        }

        .mobooking-progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .mobooking-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, <?php echo esc_attr($form_config['theme_color']); ?>, #10b981);
            transition: width 0.3s ease;
            width: 12.5%;
        }

        .mobooking-form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .mobooking-step-content {
            display: none;
        }

        .mobooking-step-content.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mobooking-step-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 20px;
        }

        .mobooking-form-group {
            margin-bottom: 20px;
        }

        .mobooking-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .mobooking-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .mobooking-input:focus {
            outline: none;
            border-color: <?php echo esc_attr($form_config['theme_color']); ?>;
        }

        .mobooking-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }

        .mobooking-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            resize: vertical;
            min-height: 100px;
        }

        .mobooking-grid {
            display: grid;
            gap: 20px;
        }

        .mobooking-grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .mobooking-service-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .mobooking-service-card:hover {
            border-color: <?php echo esc_attr($form_config['theme_color']); ?>;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .mobooking-service-card.selected {
            border-color: <?php echo esc_attr($form_config['theme_color']); ?>;
            background: rgba(26, 188, 156, 0.05);
        }

        .mobooking-service-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .mobooking-service-description {
            color: #6b7280;
            margin-bottom: 12px;
        }

        .mobooking-service-price {
            font-weight: 600;
            color: <?php echo esc_attr($form_config['theme_color']); ?>;
        }

        .mobooking-radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .mobooking-radio-option {
            display: flex;
            align-items: center;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mobooking-radio-option:hover {
            border-color: <?php echo esc_attr($form_config['theme_color']); ?>;
            background: rgba(26, 188, 156, 0.05);
        }

        .mobooking-radio-option input[type="radio"] {
            margin-right: 12px;
        }

        .mobooking-time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .mobooking-time-slot {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .mobooking-time-slot:hover {
            border-color: <?php echo esc_attr($form_config['theme_color']); ?>;
            background: rgba(26, 188, 156, 0.05);
        }

        .mobooking-time-slot.selected {
            border-color: <?php echo esc_attr($form_config['theme_color']); ?>;
            background: <?php echo esc_attr($form_config['theme_color']); ?>;
            color: white;
        }

        .mobooking-button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }

        .mobooking-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .mobooking-btn-primary {
            background: <?php echo esc_attr($form_config['theme_color']); ?>;
            color: white;
        }

        .mobooking-btn-primary:hover {
            background: #16a085;
        }

        .mobooking-btn-secondary {
            background: #6b7280;
            color: white;
        }

        .mobooking-btn-secondary:hover {
            background: #4b5563;
        }

        .mobooking-feedback {
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        .mobooking-feedback.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .mobooking-feedback.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .mobooking-feedback.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .mobooking-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid <?php echo esc_attr($form_config['theme_color']); ?>;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .mobooking-summary-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .mobooking-success-icon {
            width: 60px;
            height: 60px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .mobooking-debug {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            .mobooking-progress-steps {
                justify-content: center;
            }
            
            .mobooking-step-indicator {
                width: 35px;
                height: 35px;
                font-size: 12px;
            }
            
            .mobooking-grid-2 {
                grid-template-columns: 1fr;
            }
            
            .mobooking-button-group {
                flex-direction: column;
            }
        }
    </style>

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
                    <div class="mobooking-form-group">
                        <label for="mobooking-zip" class="mobooking-label"><?php _e('ZIP/Postal Code', 'mobooking'); ?> *</label>
                        <input type="text" id="mobooking-zip" class="mobooking-input" placeholder="<?php esc_attr_e('Enter your ZIP code', 'mobooking'); ?>" required>
                    </div>
                    <div class="mobooking-form-group">
                        <label for="mobooking-country" class="mobooking-label"><?php _e('Country', 'mobooking'); ?> *</label>
                        <select id="mobooking-country" class="mobooking-select" required>
                            <option value=""><?php _e('Select Country', 'mobooking'); ?></option>
                            <option value="US"><?php _e('United States', 'mobooking'); ?></option>
                            <option value="CA"><?php _e('Canada', 'mobooking'); ?></option>
                            <option value="UK"><?php _e('United Kingdom', 'mobooking'); ?></option>
                        </select>
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
                <label class="mobooking-label"><?php _e('Available Time Slots', 'mobooking'); ?> *</label>
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

<script>
jQuery(document).ready(function($) {
    // Global variables
    let currentStep = MOBOOKING_CONFIG.form_config.enable_area_check ? 1 : 2;
    let maxCompletedStep = currentStep;
    let formData = {
        location: {},
        services: [],
        options: {},
        pets: {},
        frequency: 'one-time',
        datetime: {},
        customer: {},
        access: {}
    };
    let debugResponses = [];

    console.log('Form initialized with config:', MOBOOKING_CONFIG);
    console.log('Starting step:', currentStep);

    // Initialize form
    moBookingInitializeForm();

    // Form event handlers
    $('#mobooking-area-check-form').on('submit', function(e) {
        e.preventDefault();
        moBookingCheckServiceArea();
    });

    // Pet question toggle
    $('input[name="has_pets"]').on('change', function() {
        if (this.value === 'yes') {
            $('#mobooking-pet-details-container').removeClass('hidden');
        } else {
            $('#mobooking-pet-details-container').addClass('hidden');
        }
    });

    // Property access toggle
    $('input[name="property_access"]').on('change', function() {
        if (this.value === 'other') {
            $('#mobooking-custom-access-details').removeClass('hidden');
        } else {
            $('#mobooking-custom-access-details').addClass('hidden');
        }
    });

    // Date picker initialization
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#mobooking-service-date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr) {
                if (dateStr) {
                    moBookingLoadTimeSlots(dateStr);
                }
            }
        });
    }

    // Functions
    function moBookingInitializeForm() {
        console.log('Initializing form...');
        moBookingUpdateProgressBar();
        moBookingUpdateLiveSummary();
        moBookingUpdateDebugInfo();
        
        // Load services if we're starting on step 2
        if (currentStep === 2) {
            console.log('Loading services for step 2');
            moBookingLoadServices();
        }
    }

    function moBookingShowStep(step) {
        // Skip disabled steps
        if (step === 4 && !MOBOOKING_CONFIG.form_config.enable_pet_information) step++;
        if (step === 5 && !MOBOOKING_CONFIG.form_config.enable_service_frequency) step++;
        if (step === 6 && !MOBOOKING_CONFIG.form_config.enable_datetime_selection) step++;
        if (step === 7 && !MOBOOKING_CONFIG.form_config.enable_property_access) step++;

        // Hide all steps
        $('.mobooking-step-content').removeClass('active');
        
        // Show target step
        $('#mobooking-step-' + step).addClass('active');
        
        currentStep = step;
        maxCompletedStep = Math.max(maxCompletedStep, step);
        
        moBookingUpdateProgressBar();
        moBookingUpdateLiveSummary();
        moBookingUpdateDebugInfo();

        // Load step-specific data
        if (step === 2) {
            moBookingLoadServices();
        } else if (step === 3) {
            moBookingLoadServiceOptions();
        }
    }

    function moBookingUpdateProgressBar() {
        if (!MOBOOKING_CONFIG.form_config.show_progress_bar) return;
        
        const totalSteps = 8;
        const progress = (currentStep / totalSteps) * 100;
        $('#mobooking-progress-fill').css('width', progress + '%');

        // Update step indicators
        $('.mobooking-step-indicator').each(function() {
            const stepNum = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            
            if (stepNum === currentStep) {
                $(this).addClass('active');
            } else if (stepNum < currentStep) {
                $(this).addClass('completed');
            }
        });
    }

    function moBookingCheckServiceArea() {
        const zipCode = $('#mobooking-zip').val().trim();
        const countryCode = $('#mobooking-country').val();
        const $feedback = $('#mobooking-location-feedback');
        const $submitBtn = $('#mobooking-area-check-form button[type="submit"]');
        const originalBtnHtml = $submitBtn.html();

        if (!zipCode) {
            moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.zip_required);
            return;
        }

        if (!countryCode) {
            moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.country_required);
            return;
        }

        $submitBtn.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + MOBOOKING_CONFIG.i18n.checking_availability);
        moBookingShowFeedback($feedback, 'info', MOBOOKING_CONFIG.i18n.checking_availability);

        $.ajax({
            url: MOBOOKING_CONFIG.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_check_service_area',
                nonce: MOBOOKING_CONFIG.nonce,
                zip_code: zipCode,
                country_code: countryCode,
                tenant_id: MOBOOKING_CONFIG.tenant_id,
            },
            success: function(response) {
                debugResponses.push({action: 'check_service_area', response: response});
                moBookingUpdateDebugInfo();

                if (response.success && response.data && response.data.serviced) {
                    formData.location = {zip_code: zipCode, country_code: countryCode};
                    moBookingShowFeedback($feedback, 'success', response.data.message || MOBOOKING_CONFIG.i18n.service_available);
                    setTimeout(function() {
                        moBookingShowStep(2);
                    }, 1500);
                } else {
                    moBookingShowFeedback($feedback, 'error', response.data?.message || MOBOOKING_CONFIG.i18n.service_not_available);
                }
            },
            error: function() {
                moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.booking_error);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    }

    function moBookingLoadServices() {
        const $container = $('#mobooking-services-container');
        $container.html('<div style="text-align: center; padding: 40px 0;"><div class="mobooking-spinner"></div><span>' + MOBOOKING_CONFIG.i18n.loading_services + '</span></div>');

        // Debug: Log the AJAX request
        console.log('Loading services with config:', {
            url: MOBOOKING_CONFIG.ajax_url,
            action: 'mobooking_get_public_services',
            nonce: MOBOOKING_CONFIG.nonce,
            tenant_id: MOBOOKING_CONFIG.tenant_id
        });

        $.ajax({
            url: MOBOOKING_CONFIG.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_public_services',
                nonce: MOBOOKING_CONFIG.nonce,
                tenant_id: MOBOOKING_CONFIG.tenant_id,
            },
            success: function(response) {
                console.log('Services AJAX Response:', response);
                debugResponses.push({action: 'get_public_services', response: response});
                moBookingUpdateDebugInfo();

                if (response.success && response.data && Array.isArray(response.data)) {
                    moBookingRenderServices(response.data);
                } else {
                    console.error('Invalid service response:', response);
                    $container.html('<p style="text-align: center; color: #6b7280;">No services available at the moment.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Services AJAX Error:', {xhr, status, error, responseText: xhr.responseText});
                debugResponses.push({action: 'get_public_services_error', error: {xhr: xhr.responseText, status, error}});
                moBookingUpdateDebugInfo();
                $container.html('<p style="text-align: center; color: #ef4444;">Error loading services. Please try again. Check console for details.</p>');
            }
        });
    }

    function moBookingRenderServices(services) {
        const $container = $('#mobooking-services-container');
        let html = '<div class="mobooking-grid mobooking-grid-2">';

        services.forEach(function(service) {
            html += `
                <div class="mobooking-service-card" data-service-id="${service.id}" onclick="moBookingToggleService(${service.id})">
                    <input type="checkbox" name="selected_services[]" value="${service.id}" style="display: none;">
                    <div class="mobooking-service-title">${service.name}</div>
                    <div class="mobooking-service-description">${service.description || ''}</div>
                    <div class="mobooking-service-price">${service.price || '0'}</div>
                </div>
            `;
        });

        html += '</div>';
        $container.html(html);
    }

    function moBookingLoadServiceOptions() {
        const selectedServices = formData.services;
        const $container = $('#mobooking-service-options-container');

        if (selectedServices.length === 0) {
            $container.html('<p style="color: #6b7280;">' + MOBOOKING_CONFIG.i18n.select_service + '</p>');
            return;
        }

        $container.html('<div style="text-align: center; padding: 20px 0;"><div class="mobooking-spinner"></div><span>Loading service options...</span></div>');

        $.ajax({
            url: MOBOOKING_CONFIG.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_service_options',
                nonce: MOBOOKING_CONFIG.nonce,
                service_ids: selectedServices,
                tenant_id: MOBOOKING_CONFIG.tenant_id,
            },
            success: function(response) {
                debugResponses.push({action: 'get_service_options', response: response});
                moBookingUpdateDebugInfo();

                if (response.success && response.data) {
                    moBookingRenderServiceOptions(response.data);
                } else {
                    $container.html('<p style="color: #6b7280;">No additional options available for selected services.</p>');
                }
            },
            error: function() {
                $container.html('<p style="color: #ef4444;">Error loading service options. Please try again.</p>');
            }
        });
    }

    function moBookingRenderServiceOptions(options) {
        const $container = $('#mobooking-service-options-container');
        let html = '';

        options.forEach(function(option) {
            html += '<div class="mobooking-form-group">';
            html += `<label for="option-${option.id}" class="mobooking-label">${option.name}${option.required ? ' *' : ''}</label>`;

            if (option.type === 'text') {
                html += `<input type="text" id="option-${option.id}" class="mobooking-input" name="service_options[${option.id}]" ${option.required ? 'required' : ''}>`;
            } else if (option.type === 'number') {
                html += `<input type="number" id="option-${option.id}" class="mobooking-input" name="service_options[${option.id}]" ${option.required ? 'required' : ''}>`;
            } else if (option.type === 'select') {
                html += `<select id="option-${option.id}" class="mobooking-select" name="service_options[${option.id}]" ${option.required ? 'required' : ''}>`;
                if (option.option_values) {
                    option.option_values.forEach(function(value) {
                        html += `<option value="${value}">${value}</option>`;
                    });
                }
                html += '</select>';
            } else if (option.type === 'checkbox') {
                html += `<label><input type="checkbox" id="option-${option.id}" name="service_options[${option.id}]" value="1"> ${option.description || option.name}</label>`;
            }

            if (option.description && option.type !== 'checkbox') {
                html += `<p style="font-size: 14px; color: #6b7280; margin-top: 5px;">${option.description}</p>`;
            }

            html += '</div>';
        });

        $container.html(html || '<p style="color: #6b7280;">No additional options available for selected services.</p>');
    }

    function moBookingLoadTimeSlots(date) {
        const $container = $('#mobooking-time-slots-container');
        const $slotsGrid = $('#mobooking-time-slots');

        $container.removeClass('hidden');
        $slotsGrid.html('<div style="text-align: center; padding: 20px;"><div class="mobooking-spinner"></div><span>Loading available times...</span></div>');

        $.ajax({
            url: MOBOOKING_CONFIG.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_available_time_slots',
                nonce: MOBOOKING_CONFIG.nonce,
                date: date,
                tenant_id: MOBOOKING_CONFIG.tenant_id,
            },
            success: function(response) {
                debugResponses.push({action: 'get_available_time_slots', response: response});
                moBookingUpdateDebugInfo();

                if (response.success && response.data) {
                    moBookingRenderTimeSlots(response.data);
                } else {
                    $slotsGrid.html('<p style="text-align: center; color: #6b7280;">No available time slots for this date.</p>');
                }
            },
            error: function() {
                $slotsGrid.html('<p style="text-align: center; color: #ef4444;">Error loading time slots. Please try again.</p>');
            }
        });
    }

    function moBookingRenderTimeSlots(slots) {
        const $slotsGrid = $('#mobooking-time-slots');
        let html = '';

        slots.forEach(function(slot) {
            html += `
                <div class="mobooking-time-slot" data-time="${slot.time}" onclick="moBookingSelectTimeSlot('${slot.time}')">
                    ${slot.display}
                </div>
            `;
        });

        $slotsGrid.html(html);
    }

    function moBookingSubmitForm() {
        if (!moBookingValidateCurrentStep()) {
            return;
        }

        // Collect all form data
        const submitData = {
            action: 'mobooking_submit_booking',
            nonce: MOBOOKING_CONFIG.nonce,
            tenant_id: MOBOOKING_CONFIG.tenant_id,
            customer_details: JSON.stringify({
                name: $('#mobooking-customer-name').val(),
                email: $('#mobooking-customer-email').val(),
                phone: $('#mobooking-customer-phone').val(),
                address: $('#mobooking-service-address').val(),
                instructions: $('#mobooking-special-instructions').val(),
                date: formData.datetime.date,
                time: formData.datetime.time
            }),
            selected_services: JSON.stringify(formData.services),
            service_options: JSON.stringify(formData.options),
            pet_information: JSON.stringify(formData.pets),
            service_frequency: formData.frequency,
            property_access: JSON.stringify(formData.access),
            location_data: JSON.stringify(formData.location),
            pricing_data: JSON.stringify({})
        };

        const $feedback = $('#mobooking-contact-feedback');
        const $submitBtn = $('.mobooking-btn-primary:contains("Submit")');
        const originalBtnHtml = $submitBtn.html();

        $submitBtn.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + MOBOOKING_CONFIG.i18n.submitting_booking);

        $.ajax({
            url: MOBOOKING_CONFIG.ajax_url,
            type: 'POST',
            data: submitData,
            success: function(response) {
                debugResponses.push({action: 'submit_booking', response: response});
                moBookingUpdateDebugInfo();

                if (response.success) {
                    // Populate success message and summary
                    moBookingPopulateBookingSummary(response.data);
                    moBookingShowStep(8);
                } else {
                    moBookingShowFeedback($feedback, 'error', response.data?.message || MOBOOKING_CONFIG.i18n.booking_error);
                }
            },
            error: function() {
                moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.booking_error);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    }

    function moBookingValidateCurrentStep() {
        const $feedback = $('#mobooking-' + 
            (currentStep === 1 ? 'location' :
             currentStep === 2 ? 'service' :
             currentStep === 3 ? 'options' :
             currentStep === 4 ? 'pet' :
             currentStep === 6 ? 'datetime' :
             currentStep === 7 ? 'contact' : 'general') + '-feedback');

        switch (currentStep) {
            case 2:
                if (formData.services.length === 0) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.select_service);
                    return false;
                }
                break;
            case 4:
                if ($('input[name="has_pets"]:checked').val() === 'yes' && !$('#mobooking-pet-details').val().trim()) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.pet_details_required);
                    return false;
                }
                break;
            case 6:
                if (!formData.datetime.date) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.select_date);
                    return false;
                }
                if (!formData.datetime.time) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.select_time);
                    return false;
                }
                break;
            case 7:
                if (!$('#mobooking-customer-name').val().trim()) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.name_required);
                    return false;
                }
                if (!$('#mobooking-customer-email').val().trim() || !moBookingValidateEmail($('#mobooking-customer-email').val())) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.email_required);
                    return false;
                }
                if (!$('#mobooking-customer-phone').val().trim()) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.phone_required);
                    return false;
                }
                if (!$('#mobooking-service-address').val().trim()) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.address_required);
                    return false;
                }
                if ($('input[name="property_access"]:checked').val() === 'other' && !$('#mobooking-access-instructions').val().trim()) {
                    moBookingShowFeedback($feedback, 'error', MOBOOKING_CONFIG.i18n.access_details_required);
                    return false;
                }
                break;
        }
        return true;
    }

    function moBookingValidateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function moBookingShowFeedback($element, type, message) {
        $element.removeClass('success error info').addClass(type).html(message).show();
        setTimeout(function() {
            $element.hide();
        }, 5000);
    }

    function moBookingUpdateLiveSummary() {
        const $content = $('#mobooking-summary-content');
        let summary = [];

        if (formData.location.zip_code) {
            summary.push(`<strong>Location:</strong> ${formData.location.zip_code}, ${formData.location.country_code}`);
        }

        if (formData.services.length > 0) {
            summary.push(`<strong>Services:</strong> ${formData.services.length} selected`);
        }

        if (formData.frequency) {
            summary.push(`<strong>Frequency:</strong> ${formData.frequency}`);
        }

        if (formData.datetime.date) {
            summary.push(`<strong>Date:</strong> ${formData.datetime.date}`);
        }

        if (formData.datetime.time) {
            summary.push(`<strong>Time:</strong> ${formData.datetime.time}`);
        }

        if (summary.length > 0) {
            $content.html(summary.join('<br>'));
        } else {
            $content.html('<p>Complete the form to see your booking summary</p>');
        }
    }

    function moBookingUpdateDebugInfo() {
        if ($('#mobooking-debug-section').length) {
            $('#mobooking-debug-config').text(JSON.stringify(MOBOOKING_CONFIG, null, 2));
            $('#mobooking-debug-data').text(JSON.stringify(formData, null, 2));
            $('#mobooking-debug-responses').text(JSON.stringify(debugResponses, null, 2));
        }
    }

    function moBookingPopulateBookingSummary(bookingData) {
        const $summary = $('#mobooking-booking-summary');
        let summaryHtml = '';

        if (bookingData.booking_reference) {
            summaryHtml += `<p><strong>Booking Reference:</strong> ${bookingData.booking_reference}</p>`;
        }

        if (formData.location.zip_code) {
            summaryHtml += `<p><strong>Service Area:</strong> ${formData.location.zip_code}, ${formData.location.country_code}</p>`;
        }

        if (formData.services.length > 0) {
            summaryHtml += `<p><strong>Services:</strong> ${formData.services.length} service(s) selected</p>`;
        }

        if (formData.datetime.date && formData.datetime.time) {
            summaryHtml += `<p><strong>Scheduled:</strong> ${formData.datetime.date} at ${formData.datetime.time}</p>`;
        }

        if (formData.frequency) {
            summaryHtml += `<p><strong>Frequency:</strong> ${formData.frequency}</p>`;
        }

        if ($('#mobooking-customer-name').val()) {
            summaryHtml += `<p><strong>Contact:</strong> ${$('#mobooking-customer-name').val()} (${$('#mobooking-customer-email').val()})</p>`;
        }

        if (bookingData.total_amount) {
            summaryHtml += `<p><strong>Total Amount:</strong> ${bookingData.total_amount}</p>`;
        }

        $summary.html(summaryHtml);
    }

    // Debug: Test AJAX endpoint directly
    window.testServicesAjax = function() {
        console.log('Testing services AJAX endpoint...');
        $.ajax({
            url: MOBOOKING_CONFIG.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_public_services',
                nonce: MOBOOKING_CONFIG.nonce,
                tenant_id: MOBOOKING_CONFIG.tenant_id,
            },
            success: function(response) {
                console.log('Test AJAX Success:', response);
            },
            error: function(xhr, status, error) {
                console.error('Test AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
            }
        });
    };

    // Auto-run the test if in debug mode
    if (typeof MOBOOKING_CONFIG.form_config !== 'undefined' && MOBOOKING_CONFIG.form_config.debug_mode) {
        setTimeout(testServicesAjax, 1000);
    }

    // Global function declarations for onclick handlers
    window.moBookingToggleService = function(serviceId) {
        const $card = $(`.mobooking-service-card[data-service-id="${serviceId}"]`);
        const $checkbox = $card.find('input[type="checkbox"]');
        
        if ($card.hasClass('selected')) {
            $card.removeClass('selected');
            $checkbox.prop('checked', false);
            formData.services = formData.services.filter(id => id !== serviceId);
        } else {
            $card.addClass('selected');
            $checkbox.prop('checked', true);
            if (!formData.services.includes(serviceId)) {
                formData.services.push(serviceId);
            }
        }
        
        moBookingUpdateLiveSummary();
        moBookingUpdateDebugInfo();
    };

    window.moBookingSelectTimeSlot = function(time) {
        $('.mobooking-time-slot').removeClass('selected');
        $(`.mobooking-time-slot[data-time="${time}"]`).addClass('selected');
        formData.datetime.time = time;
        moBookingUpdateLiveSummary();
        moBookingUpdateDebugInfo();
    };

    window.moBookingNextStep = function() {
        if (moBookingValidateCurrentStep()) {
            // Collect data from current step before moving
            moBookingCollectStepData();
            moBookingShowStep(currentStep + 1);
        }
    };

    window.moBookingPreviousStep = function() {
        if (currentStep > 1) {
            moBookingShowStep(currentStep - 1);
        }
    };

    window.moBookingSubmitForm = function() {
        moBookingCollectStepData();
        moBookingSubmitForm();
    };

    window.moBookingResetForm = function() {
        // Reset form data
        formData = {
            location: {},
            services: [],
            options: {},
            pets: {},
            frequency: 'one-time',
            datetime: {},
            customer: {},
            access: {}
        };
        
        // Reset form fields
        $('#mobooking-zip, #mobooking-customer-name, #mobooking-customer-email, #mobooking-customer-phone, #mobooking-service-address, #mobooking-pet-details, #mobooking-access-instructions, #mobooking-special-instructions').val('');
        $('#mobooking-country').val('');
        $('#mobooking-service-date').val('');
        $('input[name="has_pets"][value="no"]').prop('checked', true);
        $('input[name="frequency"][value="one-time"]').prop('checked', true);
        $('input[name="property_access"][value="home"]').prop('checked', true);
        $('.mobooking-service-card').removeClass('selected');
        $('.mobooking-time-slot').removeClass('selected');
        $('#mobooking-pet-details-container, #mobooking-custom-access-details, #mobooking-time-slots-container').addClass('hidden');
        
        // Reset to first step
        currentStep = MOBOOKING_CONFIG.form_config.enable_area_check ? 1 : 2;
        maxCompletedStep = currentStep;
        moBookingShowStep(currentStep);
    };

    function moBookingCollectStepData() {
        switch (currentStep) {
            case 1:
                formData.location = {
                    zip_code: $('#mobooking-zip').val(),
                    country_code: $('#mobooking-country').val()
                };
                break;
            case 3:
                // Collect service options
                const options = {};
                $('[name^="service_options"]').each(function() {
                    const name = $(this).attr('name');
                    const match = name.match(/service_options\[(\d+)\]/);
                    if (match) {
                        const optionId = match[1];
                        if ($(this).attr('type') === 'checkbox') {
                            options[optionId] = $(this).is(':checked') ? 1 : 0;
                        } else {
                            options[optionId] = $(this).val();
                        }
                    }
                });
                formData.options = options;
                break;
            case 4:
                formData.pets = {
                    has_pets: $('input[name="has_pets"]:checked').val() === 'yes',
                    details: $('#mobooking-pet-details').val()
                };
                break;
            case 5:
                formData.frequency = $('input[name="frequency"]:checked').val();
                break;
            case 6:
                formData.datetime = {
                    date: $('#mobooking-service-date').val(),
                    time: formData.datetime.time || null
                };
                break;
            case 7:
                formData.customer = {
                    name: $('#mobooking-customer-name').val(),
                    email: $('#mobooking-customer-email').val(),
                    phone: $('#mobooking-customer-phone').val(),
                    address: $('#mobooking-service-address').val(),
                    instructions: $('#mobooking-special-instructions').val()
                };
                formData.access = {
                    method: $('input[name="property_access"]:checked').val(),
                    details: $('#mobooking-access-instructions').val()
                };
                break;
        }
        
        moBookingUpdateLiveSummary();
        moBookingUpdateDebugInfo();
    }

    // Initialize the form
    moBookingInitializeForm();
});
</script>

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