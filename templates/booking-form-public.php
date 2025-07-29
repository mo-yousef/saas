<?php
/**
 * Complete Refactored MoBooking Booking Form
 * File: templates/booking-form-public.php
 * 
 * This template provides a complete multi-step booking form with proper database connections,
 * service data loading, service options handling, availability checking, and form submission.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get tenant information - Multiple lookup methods
$tenant_id = '';
$tenant_user_id = 0;

// Method 1: Query variable from rewrite rules
$tenant_id = get_query_var('tenant_id', '');

// Method 2: GET/POST parameter
if (empty($tenant_id)) {
    $tenant_id = sanitize_text_field($_GET['tenant'] ?? $_POST['tenant'] ?? '');
}

// Method 3: URL path parsing
if (empty($tenant_id)) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\/booking\/([^\/\?]+)/', $request_uri, $matches)) {
        $tenant_id = sanitize_text_field($matches[1]);
    }
}

// Method 4: Check if we're on a specific page with tenant info
if (empty($tenant_id)) {
    global $post;
    if ($post) {
        // Check for tenant in post meta or custom fields
        $tenant_id = get_post_meta($post->ID, 'mobooking_tenant_slug', true);
    }
}

// Debug logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[MoBooking Debug] Tenant ID found: ' . $tenant_id);
    error_log('[MoBooking Debug] Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
}

// Look up tenant user ID
if (!empty($tenant_id)) {
    global $wpdb;
    
    // Try multiple lookup methods
    $table_name = MoBooking\Classes\Database::get_table_name('tenant_settings');
    
    // Method 1: Look for business slug setting
    $tenant_user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $table_name WHERE setting_name = 'bf_business_slug' AND setting_value = %s LIMIT 1",
        $tenant_id
    ));
    
    // Method 2: If not found, try business name
    if (!$tenant_user_id) {
        $tenant_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE setting_name = 'biz_name' AND setting_value = %s LIMIT 1",
            $tenant_id
        ));
    }
    
    // Method 3: Try direct user lookup by login or nicename
    if (!$tenant_user_id) {
        $user = get_user_by('login', $tenant_id);
        if (!$user) {
            $user = get_user_by('slug', $tenant_id);
        }
        if ($user && user_can($user->ID, 'manage_options')) {
            $tenant_user_id = $user->ID;
        }
    }
    
    // Method 4: Check if tenant_id is actually a user ID
    if (!$tenant_user_id && is_numeric($tenant_id)) {
        $user = get_user_by('ID', intval($tenant_id));
        if ($user && user_can($user->ID, 'manage_options')) {
            $tenant_user_id = intval($tenant_id);
        }
    }
    
    // Debug database lookup
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[MoBooking Debug] Database table: ' . $table_name);
        error_log('[MoBooking Debug] Tenant user ID found: ' . ($tenant_user_id ?: 'None'));
    }
}

// Convert to integer
$tenant_user_id = intval($tenant_user_id);

// Enhanced error handling with debugging info
if (!$tenant_user_id) {
    $error_details = '';
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $error_details = '<br><small>Debug Info: tenant_id="' . esc_html($tenant_id) . '", REQUEST_URI="' . esc_html($_SERVER['REQUEST_URI'] ?? 'N/A') . '"</small>';
        
        // Check if table exists
        global $wpdb;
        $table_name = MoBooking\Classes\Database::get_table_name('tenant_settings');
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $error_details .= '<br><small>Table exists: ' . ($table_exists ? 'Yes' : 'No') . '</small>';
        
        if ($table_exists) {
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $error_details .= '<br><small>Table rows: ' . $row_count . '</small>';
        }
    }
    
    echo '<div class="mobooking-error">';
    echo '<h3>Booking Unavailable</h3>';
    echo '<p>The requested booking form could not be found.</p>';
    if (!empty($tenant_id)) {
        echo '<p>Tenant "' . esc_html($tenant_id) . '" does not exist or is not properly configured.</p>';
    } else {
        echo '<p>No tenant identifier provided in the URL.</p>';
        echo '<p>Expected URL format: <code>/booking/your-business-name/</code> or <code>?tenant=your-business-name</code></p>';
    }
    echo $error_details;
    echo '</div>';
    return;
}

// Initialize managers
global $mobooking_services_manager, $mobooking_settings_manager, $mobooking_areas_manager, $mobooking_availability_manager;

if (!$mobooking_services_manager || !$mobooking_settings_manager) {
    echo '<div class="mobooking-error"><p>System error: Required components not loaded.</p></div>';
    return;
}

// Get settings
$bf_settings = $mobooking_settings_manager->get_booking_form_settings($tenant_user_id);
$biz_settings = $mobooking_settings_manager->get_business_settings($tenant_user_id);

// Form configuration
$form_config = [
    'form_enabled' => ($bf_settings['bf_form_enabled'] ?? '1') === '1',
    'form_header' => $bf_settings['bf_header_text'] ?? 'Book Our Services',
    'show_pricing' => ($bf_settings['bf_show_pricing'] ?? '1') === '1',
    'enable_location_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
    'allow_discount_codes' => ($bf_settings['bf_allow_discount_codes'] ?? '1') === '1',
    'theme_color' => $bf_settings['bf_theme_color'] ?? '#1abc9c',
    'secondary_color' => $bf_settings['bf_secondary_color'] ?? '#34495e',
    'background_color' => $bf_settings['bf_background_color'] ?? '#ffffff',
    'border_radius' => $bf_settings['bf_border_radius'] ?? '8',
    'font_family' => $bf_settings['bf_font_family'] ?? 'system-ui',
    'maintenance_message' => $bf_settings['bf_maintenance_message'] ?? 'Booking form is currently unavailable.',
    'show_progress_bar' => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
    'step_1_title' => $bf_settings['bf_step_1_title'] ?? 'Check Service Area',
    'step_2_title' => $bf_settings['bf_step_2_title'] ?? 'Select Services',
    'step_3_title' => $bf_settings['bf_step_3_title'] ?? 'Configure Options',
    'step_4_title' => $bf_settings['bf_step_4_title'] ?? 'Pet Information',
    'step_5_title' => $bf_settings['bf_step_5_title'] ?? 'Service Frequency',
    'step_6_title' => $bf_settings['bf_step_6_title'] ?? 'Date & Time',
    'step_7_title' => $bf_settings['bf_step_7_title'] ?? 'Contact & Access Details',
    'step_8_title' => $bf_settings['bf_step_8_title'] ?? 'Booking Confirmed!',
    'success_message' => $bf_settings['bf_success_message'] ?? 'Thank you for your booking! We will contact you soon.',
    'terms_conditions_url' => $bf_settings['bf_terms_conditions_url'] ?? '',
    'custom_css' => $bf_settings['bf_custom_css'] ?? '',
    'bf_enable_pet_information'     => ($bf_settings['bf_enable_pet_information'] ?? '1') === '1',
    'bf_enable_service_frequency'   => ($bf_settings['bf_enable_service_frequency'] ?? '1') === '1',
    'bf_enable_datetime_selection'  => ($bf_settings['bf_enable_datetime_selection'] ?? '1') === '1',
    'bf_enable_property_access'     => ($bf_settings['bf_enable_property_access'] ?? '1') === '1',
];

// Currency settings
$currency = [
    'symbol' => $biz_settings['biz_currency_symbol'] ?? '$',
    'code' => $biz_settings['biz_currency_code'] ?? 'USD',
];

// Business information
$business_info = [
    'name' => $biz_settings['biz_name'] ?? 'Our Business',
    'phone' => $biz_settings['biz_phone'] ?? '',
    'email' => $biz_settings['biz_email'] ?? '',
    'address' => $biz_settings['biz_address'] ?? '',
];

// Check if form is disabled
if (!$form_config['form_enabled']) {
    echo '<div class="mobooking-maintenance"><h3>Booking Unavailable</h3><p>' . esc_html($form_config['maintenance_message']) . '</p></div>';
    return;
}

// The wp_localize_script call is now in functions.php, but we need to pass the data to it.
// We'll define a global variable that the function in functions.php can access.

add_action('wp_head', function() use ($tenant_id, $tenant_user_id, $currency, $business_info, $form_config) {
    $params = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_booking_nonce'),
        'tenant_id' => $tenant_id,
        'tenant_user_id' => $tenant_user_id,
        'currency' => $currency,
        'business_info' => $business_info,
        'form_config' => $form_config,
        'i18n' => [
            'loading' => __('Loading...', 'mobooking'),
            'error_generic' => __('An error occurred. Please try again.', 'mobooking'),
            'error_location' => __('Please enter a valid location.', 'mobooking'),
            'error_services' => __('Please select at least one service.', 'mobooking'),
            'error_required_option' => __('This option is required.', 'mobooking'),
            'error_invalid_email' => __('Please enter a valid email address.', 'mobooking'),
            'success_booking' => __('Booking submitted successfully!', 'mobooking'),
            'step_location' => __('Location', 'mobooking'),
            'step_services' => __('Services', 'mobooking'),
            'step_options' => __('Options', 'mobooking'),
            'step_details' => __('Details', 'mobooking'),
            'step_review' => __('Review', 'mobooking'),
            'continue' => __('Continue', 'mobooking'),
            'back' => __('Back', 'mobooking'),
            'submit' => __('Submit Booking', 'mobooking'),
            'apply_discount' => __('Apply Discount', 'mobooking'),
            'discount_applied' => __('Discount applied successfully!', 'mobooking'),
            'discount_invalid' => __('Invalid discount code.', 'mobooking'),
        ]
    ];
    wp_localize_script('mobooking-booking-form', 'moBookingParams', $params);
}, 20);
?>



<div class="mobooking-booking-form-container" id="mobooking-booking-form-container">
    <!-- Progress Bar -->
    <?php if ($form_config['show_progress_bar']): ?>
    <div class="mobooking-progress-wrapper">
        <div class="mobooking-progress-bar-bg">
            <div class="mobooking-progress-bar" id="mobooking-progress-bar" style="width: 12.5%; background-color: <?php echo esc_attr($form_config['theme_color']); ?>"></div>
        </div>
        <div class="mobooking-progress-text" id="mobooking-progress-text">Step 1 of 8</div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="mobooking-form-header">
        <h1 class="mobooking-form-title"><?php echo esc_html($form_config['form_header']); ?></h1>
        <div class="mobooking-business-info">
            <h2><?php echo esc_html($business_info['name']); ?></h2>
            <?php if ($business_info['phone']): ?>
                <p class="mobooking-business-phone">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?php echo esc_attr($business_info['phone']); ?>"><?php echo esc_html($business_info['phone']); ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Messages -->
    <div class="mobooking-feedback" id="mobooking-feedback" style="display: none;" role="alert"></div>

    <!-- Step 1: Location Check -->
<?php if ($form_config['enable_location_check']): ?>
    <div class="mobooking-step" id="mobooking-step-1" data-step="1">
        <div class="mobooking-step-content">
            <h2 class="mobooking-step-title">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo esc_html($form_config['step_1_title']); ?>
            </h2>
            <p class="mobooking-step-description">
                <?php esc_html_e('Enter your location to check if we service your area.', 'mobooking'); ?>
            </p>

            <form id="mobooking-location-form">
                <div class="mobooking-form-group">
                    <label for="mobooking-location-input"><?php esc_html_e('ZIP Code or City:', 'mobooking'); ?></label>
                    <input type="text" id="mobooking-location-input" name="location" class="mobooking-input" placeholder="<?php esc_attr_e('Enter ZIP code or city', 'mobooking'); ?>" required>
                </div>
                <div class="mobooking-form-actions">
                    <button type="submit" class="mobooking-btn mobooking-btn-primary">
                        <i class="fas fa-search"></i>
                        <?php esc_html_e('Check Availability', 'mobooking'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

    <!-- Step 2: Service Selection -->
    <div class="mobooking-step" id="mobooking-step-2" data-step="2" style="display: none;">
        <div class="mobooking-step-content">
            <h2 class="mobooking-step-title">
                <i class="fas fa-broom"></i>
                <?php echo esc_html($form_config['step_2_title']); ?>
            </h2>
            <p class="mobooking-step-description">
                <?php esc_html_e('Select the services you would like to book.', 'mobooking'); ?>
            </p>

            <div class="mobooking-services-loading" id="mobooking-services-loading">
                <div class="mobooking-spinner"></div>
                <p><?php esc_html_e('Loading services...', 'mobooking'); ?></p>
            </div>

            <div class="mobooking-services-list" id="mobooking-services-list" style="display: none;">
                <!-- Services will be loaded here via AJAX -->
            </div>

            <div class="mobooking-form-actions">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-step-2-back">
                    <i class="fas fa-arrow-left"></i>
                    <?php esc_html_e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" id="mobooking-step-2-continue">
                    <?php esc_html_e('Continue', 'mobooking'); ?>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Service Options -->
    <div class="mobooking-step" id="mobooking-step-3" data-step="3" style="display: none;">
        <div class="mobooking-step-with-sidebar">
            <div class="mobooking-step-main">
                <h2 class="mobooking-step-title">
                    <i class="fas fa-cog"></i>
                    <?php echo esc_html($form_config['step_3_title']); ?>
                </h2>
                <p class="mobooking-step-description">
                    <?php esc_html_e('Configure options for your selected services.', 'mobooking'); ?>
                </p>

                <div class="mobooking-service-options" id="mobooking-service-options">
                    <!-- Service options will be loaded here via AJAX -->
                </div>

                <div class="mobooking-form-actions">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-step-3-back">
                        <i class="fas fa-arrow-left"></i>
                        <?php esc_html_e('Back', 'mobooking'); ?>
                    </button>
                    <button type="button" class="mobooking-btn mobooking-btn-primary" id="mobooking-step-3-continue">
                        <?php esc_html_e('Continue', 'mobooking'); ?>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="mobooking-sidebar">
                <h3 class="mobooking-sidebar-title">
                    <i class="fas fa-shopping-cart"></i>
                    <?php esc_html_e('Summary', 'mobooking'); ?>
                </h3>
                <div class="mobooking-summary-content" id="mobooking-summary-content">
                    <p><?php esc_html_e('Configure options to see pricing', 'mobooking'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Pet Information -->
    <?php if ($form_config['bf_enable_pet_information']): ?>
    <div class="mobooking-step" id="mobooking-step-4" data-step="4" style="display: none;">
        <div class="mobooking-step-content">
            <h2 class="mobooking-step-title">
                <i class="fas fa-paw"></i>
                <?php echo esc_html($form_config['step_4_title']); ?>
            </h2>
            <p class="mobooking-step-description">
                <?php esc_html_e('Do you have any pets? This helps us prepare for your service.', 'mobooking'); ?>
            </p>

            <div class="mobooking-form-group">
                <label class="mobooking-radio-label">
                    <input type="radio" name="has_pets" value="no" id="pets-no" checked>
                    <span class="mobooking-radio-custom"></span>
                    <?php esc_html_e('No, I don\'t have pets', 'mobooking'); ?>
                </label>
                <label class="mobooking-radio-label">
                    <input type="radio" name="has_pets" value="yes" id="pets-yes">
                    <span class="mobooking-radio-custom"></span>
                    <?php esc_html_e('Yes, I have pets', 'mobooking'); ?>
                </label>
            </div>

            <div class="mobooking-form-group" id="pet-details-section" style="display: none;">
                <label for="pet-details"><?php esc_html_e('Pet Details:', 'mobooking'); ?></label>
                <textarea id="pet-details" name="pet_details" class="mobooking-textarea" rows="3" placeholder="<?php esc_attr_e('Please describe your pets (type, number, any special considerations)...', 'mobooking'); ?>"></textarea>
                <p class="mobooking-field-description"><?php esc_html_e('Example: 2 cats, 1 dog (friendly), bird in living room', 'mobooking'); ?></p>
            </div>

            <div class="mobooking-form-actions">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-step-4-back">
                    <i class="fas fa-arrow-left"></i>
                    <?php esc_html_e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" id="mobooking-step-4-continue">
                    <?php esc_html_e('Continue', 'mobooking'); ?>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Step 5: Service Frequency -->
    <?php if ($form_config['bf_enable_service_frequency']): ?>
    <div class="mobooking-step" id="mobooking-step-5" data-step="5" style="display: none;">
        <div class="mobooking-step-content">
            <h2 class="mobooking-step-title">
                <i class="fas fa-calendar-alt"></i>
                <?php echo esc_html($form_config['step_5_title']); ?>
            </h2>
            <p class="mobooking-step-description">
                <?php esc_html_e('How often would you like this service?', 'mobooking'); ?>
            </p>

            <div class="mobooking-frequency-options">
                <label class="mobooking-frequency-card">
                    <input type="radio" name="service_frequency" value="one-time" id="freq-onetime" checked>
                    <div class="mobooking-frequency-content">
                        <i class="fas fa-calendar-day"></i>
                        <h3><?php esc_html_e('One-time', 'mobooking'); ?></h3>
                        <p><?php esc_html_e('Single service visit', 'mobooking'); ?></p>
                    </div>
                </label>

                <label class="mobooking-frequency-card">
                    <input type="radio" name="service_frequency" value="weekly" id="freq-weekly">
                    <div class="mobooking-frequency-content">
                        <i class="fas fa-calendar-week"></i>
                        <h3><?php esc_html_e('Weekly', 'mobooking'); ?></h3>
                        <p><?php esc_html_e('Every week', 'mobooking'); ?></p>
                        <span class="mobooking-frequency-discount"><?php esc_html_e('Save 10%', 'mobooking'); ?></span>
                    </div>
                </label>

                <label class="mobooking-frequency-card">
                    <input type="radio" name="service_frequency" value="monthly" id="freq-monthly">
                    <div class="mobooking-frequency-content">
                        <i class="fas fa-calendar"></i>
                        <h3><?php esc_html_e('Monthly', 'mobooking'); ?></h3>
                        <p><?php esc_html_e('Once per month', 'mobooking'); ?></p>
                        <span class="mobooking-frequency-discount"><?php esc_html_e('Save 5%', 'mobooking'); ?></span>
                    </div>
                </label>
            </div>

            <div class="mobooking-form-actions">
                <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-step-5-back">
                    <i class="fas fa-arrow-left"></i>
                    <?php esc_html_e('Back', 'mobooking'); ?>
                </button>
                <button type="button" class="mobooking-btn mobooking-btn-primary" id="mobooking-step-5-continue">
                    <?php esc_html_e('Continue', 'mobooking'); ?>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Step 6: Date & Time Selection -->
    <?php if ($form_config['bf_enable_datetime_selection']): ?>
    <div class="mobooking-step" id="mobooking-step-6" data-step="6" style="display: none;">
        <div class="mobooking-step-with-sidebar">
            <div class="mobooking-step-main">
                <h2 class="mobooking-step-title">
                    <i class="fas fa-clock"></i>
                    <?php echo esc_html($form_config['step_6_title']); ?>
                </h2>
                <p class="mobooking-step-description">
                    <?php esc_html_e('When would you like your service?', 'mobooking'); ?>
                </p>

                <div class="mobooking-datetime-selection">
                    <div class="mobooking-form-group">
                        <label for="preferred-date"><?php esc_html_e('Preferred Date:', 'mobooking'); ?> <span class="required">*</span></label>
                        <input type="date" id="preferred-date" name="preferred_date" class="mobooking-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mobooking-form-group">
                        <label for="preferred-time"><?php esc_html_e('Preferred Time:', 'mobooking'); ?> <span class="required">*</span></label>
                        <select id="preferred-time" name="preferred_time" class="mobooking-select" required>
                            <option value=""><?php esc_html_e('Select time', 'mobooking'); ?></option>
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>

                    <div class="mobooking-time-slots" id="available-time-slots" style="display: none;">
                        <!-- Available time slots will be loaded here -->
                    </div>
                </div>

                <div class="mobooking-form-actions">
                    <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-step-6-back">
                        <i class="fas fa-arrow-left"></i>
                        <?php esc_html_e('Back', 'mobooking'); ?>
                    </button>
                    <button type="button" class="mobooking-btn mobooking-btn-primary" id="mobooking-step-6-continue">
                        <?php esc_html_e('Continue', 'mobooking'); ?>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="mobooking-sidebar">
                <div class="mobooking-summary-content" id="mobooking-datetime-summary">
                    <!-- Summary will be updated here -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Step 7: Contact & Property Access Details -->
    <?php if ($form_config['bf_enable_property_access']): ?>
    <div class="mobooking-step" id="mobooking-step-7" data-step="7" style="display: none;">
        <div class="mobooking-step-with-sidebar">
            <div class="mobooking-step-main">
                <h2 class="mobooking-step-title">
                    <i class="fas fa-user-edit"></i>
                    <?php echo esc_html($form_config['step_7_title']); ?>
                </h2>
                <p class="mobooking-step-description">
                    <?php esc_html_e('Please provide your contact information and let us know how we can access your property.', 'mobooking'); ?>
                </p>

                <form id="mobooking-details-form">
                    <!-- Contact Information -->
                    <div class="mobooking-section">
                        <h3 class="mobooking-section-title"><?php esc_html_e('Contact Information', 'mobooking'); ?></h3>

                        <div class="mobooking-form-row">
                            <div class="mobooking-form-group">
                                <label for="customer-name"><?php esc_html_e('Full Name:', 'mobooking'); ?> <span class="required">*</span></label>
                                <input type="text" id="customer-name" name="customer_name" class="mobooking-input" required>
                            </div>
                            <div class="mobooking-form-group">
                                <label for="customer-email"><?php esc_html_e('Email Address:', 'mobooking'); ?> <span class="required">*</span></label>
                                <input type="email" id="customer-email" name="customer_email" class="mobooking-input" required>
                            </div>
                        </div>

                        <div class="mobooking-form-group">
                            <label for="customer-phone"><?php esc_html_e('Phone Number:', 'mobooking'); ?> <span class="required">*</span></label>
                            <input type="tel" id="customer-phone" name="customer_phone" class="mobooking-input" required>
                        </div>
                    </div>

                    <!-- Service Address -->
                    <div class="mobooking-section">
                        <h3 class="mobooking-section-title"><?php esc_html_e('Service Address', 'mobooking'); ?></h3>

                        <div class="mobooking-form-group">
                            <label for="street-address"><?php esc_html_e('Street Address:', 'mobooking'); ?> <span class="required">*</span></label>
                            <input type="text" id="street-address" name="street_address" class="mobooking-input" placeholder="<?php esc_attr_e('123 Main Street', 'mobooking'); ?>" required>
                        </div>

                        <div class="mobooking-form-row">
                            <div class="mobooking-form-group">
                                <label for="apartment"><?php esc_html_e('Apartment/Suite:', 'mobooking'); ?></label>
                                <input type="text" id="apartment" name="apartment" class="mobooking-input" placeholder="<?php esc_attr_e('Apt 4B (optional)', 'mobooking'); ?>">
                            </div>
                            <div class="mobooking-form-group">
                                <label for="service-city"><?php esc_html_e('City:', 'mobooking'); ?></label>
                                <input type="text" id="service-city" name="service_city" class="mobooking-input" readonly>
                            </div>
                        </div>

                        <div class="mobooking-form-row">
                            <div class="mobooking-form-group">
                                <label for="service-state"><?php esc_html_e('State:', 'mobooking'); ?></label>
                                <input type="text" id="service-state" name="service_state" class="mobooking-input" readonly>
                            </div>
                            <div class="mobooking-form-group">
                                <label for="service-zipcode"><?php esc_html_e('ZIP Code:', 'mobooking'); ?></label>
                                <input type="text" id="service-zipcode" name="service_zipcode" class="mobooking-input" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Property Access -->
                    <div class="mobooking-section">
                        <h3 class="mobooking-section-title"><?php esc_html_e('Property Access', 'mobooking'); ?></h3>
                        <p class="mobooking-section-description"><?php esc_html_e('How will our team access your property?', 'mobooking'); ?></p>

                        <div class="mobooking-access-options">
                            <label class="mobooking-access-option">
                                <input type="radio" name="property_access" value="home" id="access-home" checked>
                                <div class="mobooking-access-content">
                                    <i class="fas fa-home"></i>
                                    <span><?php esc_html_e('I\'ll be home', 'mobooking'); ?></span>
                                </div>
                            </label>

                            <label class="mobooking-access-option">
                                <input type="radio" name="property_access" value="key_mat" id="access-key">
                                <div class="mobooking-access-content">
                                    <i class="fas fa-key"></i>
                                    <span><?php esc_html_e('Key under mat', 'mobooking'); ?></span>
                                </div>
                            </label>

                            <label class="mobooking-access-option">
                                <input type="radio" name="property_access" value="lockbox" id="access-lockbox">
                                <div class="mobooking-access-content">
                                    <i class="fas fa-lock"></i>
                                    <span><?php esc_html_e('Lockbox', 'mobooking'); ?></span>
                                </div>
                            </label>

                            <label class="mobooking-access-option">
                                <input type="radio" name="property_access" value="concierge" id="access-concierge">
                                <div class="mobooking-access-content">
                                    <i class="fas fa-concierge-bell"></i>
                                    <span><?php esc_html_e('Building concierge', 'mobooking'); ?></span>
                                </div>
                            </label>

                            <label class="mobooking-access-option">
                                <input type="radio" name="property_access" value="other" id="access-other">
                                <div class="mobooking-access-content">
                                    <i class="fas fa-edit"></i>
                                    <span><?php esc_html_e('Other', 'mobooking'); ?></span>
                                </div>
                            </label>
                        </div>

                        <div class="mobooking-form-group" id="custom-access-details" style="display: none;">
                            <label for="access-details"><?php esc_html_e('Access Details:', 'mobooking'); ?></label>
                            <textarea id="access-details" name="access_details" class="mobooking-textarea" rows="3" placeholder="<?php esc_attr_e('Please describe how our team can access your property...', 'mobooking'); ?>"></textarea>
                        </div>
                    </div>

                    <!-- Special Instructions -->
                    <div class="mobooking-section">
                        <h3 class="mobooking-section-title"><?php esc_html_e('Special Instructions', 'mobooking'); ?></h3>
                        <div class="mobooking-form-group">
                            <label for="special-instructions"><?php esc_html_e('Additional Notes:', 'mobooking'); ?></label>
                            <textarea id="special-instructions" name="special_instructions" class="mobooking-textarea" rows="4" placeholder="<?php esc_attr_e('Any special requests, areas of focus, or important information...', 'mobooking'); ?>"></textarea>
                        </div>
                    </div>

                    <div class="mobooking-form-actions">
                        <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-step-7-back">
                            <i class="fas fa-arrow-left"></i>
                            <?php esc_html_e('Back', 'mobooking'); ?>
                        </button>
                        <button type="button" class="mobooking-btn mobooking-btn-primary" id="mobooking-step-7-continue">
                            <?php esc_html_e('Continue', 'mobooking'); ?>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="mobooking-sidebar">
                <div class="mobooking-summary-content" id="mobooking-contact-summary">
                    <!-- Summary will be updated here -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Step 8: Booking Confirmation -->
    <div class="mobooking-step" id="mobooking-step-8" data-step="8" style="display: none;">
        <div class="mobooking-step-content">
            <div class="mobooking-success-container">
                <div class="mobooking-success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <h2 class="mobooking-success-title">
                    <?php echo esc_html($form_config['step_8_title']); ?>
                </h2>

                <p class="mobooking-success-message">
                    <?php echo esc_html($form_config['success_message']); ?>
                </p>

                <div class="mobooking-booking-details" id="mobooking-final-booking-details">
                    <!-- Booking details will be populated here via JavaScript -->
                </div>

                <div class="mobooking-success-actions">
                    <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        <?php esc_html_e('Print Confirmation', 'mobooking'); ?>
                    </button>

                    <button type="button" class="mobooking-btn mobooking-btn-secondary" id="mobooking-new-booking">
                        <i class="fas fa-plus"></i>
                        <?php esc_html_e('Book Another Service', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form data -->
    <input type="hidden" id="mobooking-tenant-id" value="<?php echo esc_attr($tenant_id); ?>">
    <input type="hidden" id="mobooking-tenant-user-id" value="<?php echo esc_attr($tenant_user_id); ?>">
    <input type="hidden" id="mobooking-selected-services" value="">
    <input type="hidden" id="mobooking-service-options" value="">
    <input type="hidden" id="mobooking-discount-applied" value="">
</div>

<!-- Custom CSS -->
<?php if ($form_config['custom_css']): ?>
<style type="text/css">
<?php echo wp_kses_post($form_config['custom_css']); ?>
</style>
<?php endif; ?>

<style type="text/css">
:root {
    --mobooking-primary-color: <?php echo esc_attr($form_config['theme_color']); ?>;
    --mobooking-secondary-color: <?php echo esc_attr($form_config['secondary_color']); ?>;
    --mobooking-background-color: <?php echo esc_attr($form_config['background_color']); ?>;
    --mobooking-border-radius: <?php echo esc_attr($form_config['border_radius']); ?>px;
    --mobooking-font-family: <?php echo esc_attr($form_config['font_family']); ?>;
}

.mobooking-booking-form-container {
    font-family: var(--mobooking-font-family);
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: var(--mobooking-background-color);
    border-radius: var(--mobooking-border-radius);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.mobooking-progress-wrapper {
    margin-bottom: 30px;
}

.mobooking-progress-bar-bg {
    width: 100%;
    height: 8px;
    background-color: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.mobooking-progress-bar {
    height: 100%;
    background-color: var(--mobooking-primary-color);
    transition: width 0.3s ease;
}

.mobooking-progress-text {
    text-align: center;
    margin-top: 10px;
    font-size: 14px;
    color: #666;
}

.mobooking-form-header {
    text-align: center;
    margin-bottom: 30px;
}

.mobooking-form-title {
    font-size: 2em;
    color: var(--mobooking-primary-color);
    margin-bottom: 10px;
}

.mobooking-business-info h2 {
    font-size: 1.5em;
    color: var(--mobooking-secondary-color);
    margin-bottom: 5px;
}

.mobooking-business-phone {
    color: #666;
}

.mobooking-business-phone a {
    color: var(--mobooking-primary-color);
    text-decoration: none;
}

.mobooking-step {
    margin-bottom: 30px;
}

.mobooking-step-title {
    font-size: 1.5em;
    color: var(--mobooking-secondary-color);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mobooking-step-title i {
    color: var(--mobooking-primary-color);
}

.mobooking-step-description {
    color: #666;
    margin-bottom: 20px;
}

.mobooking-step-with-sidebar {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

@media (max-width: 768px) {
    .mobooking-step-with-sidebar {
        grid-template-columns: 1fr;
    }
}

.mobooking-sidebar {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: var(--mobooking-border-radius);
    border-left: 4px solid var(--mobooking-primary-color);
}

.mobooking-sidebar-title {
    font-size: 1.2em;
    color: var(--mobooking-secondary-color);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mobooking-form-group {
    margin-bottom: 20px;
}

.mobooking-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 600px) {
    .mobooking-form-row {
        grid-template-columns: 1fr;
    }
}

.mobooking-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: var(--mobooking-secondary-color);
}

.required {
    color: #e74c3c;
}

.mobooking-input,
.mobooking-textarea,
.mobooking-select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: var(--mobooking-border-radius);
    font-size: 16px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.mobooking-input:focus,
.mobooking-textarea:focus,
.mobooking-select:focus {
    outline: none;
    border-color: var(--mobooking-primary-color);
}

.mobooking-btn {
    padding: 12px 24px;
    border: none;
    border-radius: var(--mobooking-border-radius);
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    box-sizing: border-box;
}

.mobooking-btn-primary {
    background-color: var(--mobooking-primary-color);
    color: white;
}

.mobooking-btn-primary:hover {
    background-color: #16a085;
    transform: translateY(-2px);
}

.mobooking-btn-secondary {
    background-color: var(--mobooking-secondary-color);
    color: white;
}

.mobooking-btn-secondary:hover {
    background-color: #2c3e50;
}

.mobooking-btn-lg {
    padding: 16px 32px;
    font-size: 18px;
    font-weight: 600;
}

.mobooking-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.mobooking-form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.mobooking-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--mobooking-primary-color);
    border-radius: 50%;
    animation: mobooking-spin 1s linear infinite;
    margin: 0 auto 10px;
}

@keyframes mobooking-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.mobooking-services-loading,
.mobooking-service-options-loading {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.mobooking-service-card {
    border: 2px solid #e0e0e0;
    border-radius: var(--mobooking-border-radius);
    padding: 20px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.mobooking-service-card:hover {
    border-color: var(--mobooking-primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.mobooking-service-card.selected {
    border-color: var(--mobooking-primary-color);
    background-color: rgba(26, 188, 156, 0.1);
}

.mobooking-service-card input[type="checkbox"] {
    position: absolute;
    top: 15px;
    right: 15px;
}

.mobooking-service-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.mobooking-service-icon {
    font-size: 24px;
    color: var(--mobooking-primary-color);
    width: 40px;
    text-align: center;
}

.mobooking-service-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--mobooking-secondary-color);
    flex: 1;
}

.mobooking-service-price {
    font-size: 16px;
    font-weight: 600;
    color: var(--mobooking-primary-color);
}

.mobooking-service-description {
    color: #666;
    margin-bottom: 10px;
}

.mobooking-service-duration {
    font-size: 14px;
    color: #888;
}

.mobooking-service-option {
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: var(--mobooking-border-radius);
    border-left: 4px solid var(--mobooking-primary-color);
}

.mobooking-option-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mobooking-option-name {
    font-weight: 600;
    color: var(--mobooking-secondary-color);
}

.mobooking-option-required {
    color: #e74c3c;
    font-size: 12px;
    background-color: #ffe6e6;
    padding: 2px 6px;
    border-radius: 10px;
}

.mobooking-option-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.mobooking-option-input {
    width: 100%;
}

.mobooking-option-checkbox-group,
.mobooking-option-radio-group {
    display: grid;
    gap: 10px;
}

.mobooking-option-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: var(--mobooking-border-radius);
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.mobooking-option-item:hover {
    background-color: rgba(26, 188, 156, 0.1);
}

.mobooking-option-item input {
    margin: 0;
}

.mobooking-option-item-label {
    flex: 1;
    cursor: pointer;
}

.mobooking-option-item-price {
    color: var(--mobooking-primary-color);
    font-weight: 600;
}

.mobooking-feedback {
    padding: 15px;
    border-radius: var(--mobooking-border-radius);
    margin-bottom: 20px;
    text-align: center;
}

.mobooking-feedback.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.mobooking-feedback.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.mobooking-feedback.warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.mobooking-booking-summary {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: var(--mobooking-border-radius);
    margin-bottom: 20px;
}

.mobooking-summary-section {
    margin-bottom: 20px;
}

.mobooking-summary-section h3 {
    color: var(--mobooking-secondary-color);
    margin-bottom: 10px;
    font-size: 18px;
}

.mobooking-summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e0e0e0;
}

.mobooking-summary-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.mobooking-summary-label {
    color: #666;
}

.mobooking-summary-value {
    font-weight: 600;
    color: var(--mobooking-secondary-color);
}

.mobooking-pricing-summary {
    background-color: var(--mobooking-primary-color);
    color: white;
    padding: 20px;
    border-radius: var(--mobooking-border-radius);
    margin-bottom: 20px;
}

.mobooking-pricing-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.mobooking-pricing-row.total {
    font-size: 18px;
    font-weight: 600;
    border-top: 1px solid rgba(255, 255, 255, 0.3);
    padding-top: 10px;
    margin-top: 10px;
}

.mobooking-discount-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: var(--mobooking-border-radius);
    margin-bottom: 20px;
}

.mobooking-discount-section h3 {
    color: var(--mobooking-secondary-color);
    margin-bottom: 15px;
}

.mobooking-discount-feedback {
    margin-top: 10px;
    padding: 10px;
    border-radius: var(--mobooking-border-radius);
    text-align: center;
}

.mobooking-discount-feedback.success {
    background-color: #d4edda;
    color: #155724;
}

.mobooking-discount-feedback.error {
    background-color: #f8d7da;
    color: #721c24;
}

.mobooking-terms-section {
    margin-bottom: 20px;
}

.mobooking-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    color: #666;
}

.mobooking-checkbox-label input[type="checkbox"] {
    margin: 0;
    margin-top: 2px;
}

.mobooking-success-content {
    text-align: center;
    padding: 40px 20px;
}

.mobooking-success-icon {
    font-size: 80px;
    color: var(--mobooking-primary-color);
    margin-bottom: 20px;
}

.mobooking-success-title {
    font-size: 2em;
    color: var(--mobooking-secondary-color);
    margin-bottom: 20px;
}

.mobooking-success-message {
    font-size: 16px;
    color: #666;
    margin-bottom: 20px;
}

.mobooking-booking-reference {
    background-color: var(--mobooking-primary-color);
    color: white;
    padding: 15px;
    border-radius: var(--mobooking-border-radius);
    font-size: 18px;
}

.mobooking-error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 20px;
    border-radius: var(--mobooking-border-radius);
    text-align: center;
    margin: 20px auto;
    max-width: 600px;
}

.mobooking-maintenance {
    background-color: #fff3cd;
    color: #856404;
    padding: 20px;
    border-radius: var(--mobooking-border-radius);
    text-align: center;
    margin: 20px auto;
    max-width: 600px;
}

/* Responsive design */
@media (max-width: 600px) {
    .mobooking-booking-form-container {
        padding: 15px;
    }
    
    .mobooking-form-title {
        font-size: 1.5em;
    }
    
    .mobooking-service-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .mobooking-form-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .mobooking-btn {
        width: 100%;
        justify-content: center;
    }
    
    .mobooking-summary-item,
    .mobooking-pricing-row {
        flex-direction: column;
        gap: 5px;
    }
}
</style>
