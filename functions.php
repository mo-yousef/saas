<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.4' );
}
if ( ! defined( 'MOBOOKING_DB_VERSION' ) ) {
    define( 'MOBOOKING_DB_VERSION', '2.1' );
}
if ( ! defined( 'MOBOOKING_THEME_DIR' ) ) {
    define( 'MOBOOKING_THEME_DIR', trailingslashit( get_template_directory() ) );
}
if ( ! defined( 'MOBOOKING_THEME_URI' ) ) {
    define( 'MOBOOKING_THEME_URI', trailingslashit( get_template_directory_uri() ) );
}


// Include the separated functional files
require_once MOBOOKING_THEME_DIR . 'functions/ajax.php';
require_once MOBOOKING_THEME_DIR . 'functions/theme-setup.php';
require_once MOBOOKING_THEME_DIR . 'functions/autoloader.php';
require_once MOBOOKING_THEME_DIR . 'functions/routing.php';
require_once MOBOOKING_THEME_DIR . 'functions/initialization.php';
require_once MOBOOKING_THEME_DIR . 'functions/utilities.php';
require_once MOBOOKING_THEME_DIR . 'functions/debug.php';
require_once MOBOOKING_THEME_DIR . 'functions/email.php';
require_once MOBOOKING_THEME_DIR . 'functions/ajax-fixes.php';
require_once MOBOOKING_THEME_DIR . 'functions/debug-utils.php';
require_once MOBOOKING_THEME_DIR . 'functions/migration.php';


/**
 * Initialize MoBooking managers globally
 * Add this to your theme's functions.php
 */
function mobooking_initialize_managers() {
    if (!isset($GLOBALS['mobooking_services_manager'])) {
        try {
            $GLOBALS['mobooking_services_manager'] = new \MoBooking\Classes\Services();
        } catch (Exception $e) {
            error_log('MoBooking: Failed to initialize Services manager: ' . $e->getMessage());
        }
    }

    if (!isset($GLOBALS['mobooking_bookings_manager'])) {
        try {
            $GLOBALS['mobooking_bookings_manager'] = new \MoBooking\Classes\Bookings(
                $GLOBALS['mobooking_discounts_manager'],
                $GLOBALS['mobooking_notifications_manager'],
                $GLOBALS['mobooking_services_manager']
            );
        } catch (Exception $e) {
            error_log('MoBooking: Failed to initialize Bookings manager: ' . $e->getMessage());
        }
    }

    if (!isset($GLOBALS['mobooking_customers_manager'])) {
        try {
            $GLOBALS['mobooking_customers_manager'] = new \MoBooking\Classes\Customers();
        } catch (Exception $e) {
            error_log('MoBooking: Failed to initialize Customers manager: ' . $e->getMessage());
        }
    }
}
// =============================================================================
// INITIALIZATION
// =============================================================================

// Hook to initialize managers early
add_action('init', 'mobooking_initialize_managers', 1);


// =============================================================================
// BOOKING FORM AJAX HANDLERS & FIXES
// =============================================================================

/**
 * Enhanced PHP Diagnostic and Booking Handler Fix
 * Add this to your functions.php or create as a separate plugin
 */

// First, let's add some diagnostic logging
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking Diagnostic - Plugin loaded at init');
    }
});

// Add a diagnostic AJAX handler to test if our hooks are working
add_action('wp_ajax_mobooking_diagnostic_test', 'mobooking_diagnostic_test_handler');
add_action('wp_ajax_nopriv_mobooking_diagnostic_test', 'mobooking_diagnostic_test_handler');

function mobooking_diagnostic_test_handler() {
    wp_send_json_success([
        'message' => 'Diagnostic handler is working',
        'timestamp' => current_time('mysql'),
        'hooks_registered' => [
            'mobooking_create_booking' => has_action('wp_ajax_mobooking_create_booking'),
            'mobooking_create_booking_nopriv' => has_action('wp_ajax_nopriv_mobooking_create_booking')
        ]
    ]);
}

// Remove any existing handlers first (with higher priority)
add_action('wp_loaded', function() {
    // Remove existing handlers
    remove_all_actions('wp_ajax_mobooking_create_booking');
    remove_all_actions('wp_ajax_nopriv_mobooking_create_booking');

    // Add our enhanced handler
    add_action('wp_ajax_mobooking_create_booking', 'mobooking_enhanced_create_booking_handler');
    add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_enhanced_create_booking_handler');

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking Enhanced Handler - Handlers registered at wp_loaded');
    }
}, 999); // Very high priority

function mobooking_enhanced_create_booking_handler() {
    // Log that our handler is being called
    error_log('MoBooking Enhanced Handler - Handler called at ' . current_time('mysql'));
    error_log('MoBooking Enhanced Handler - POST data: ' . print_r($_POST, true));

    // Enable error reporting for this request
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_reporting(E_ALL);
        ini_set('log_errors', 1);
    }

    try {
        // 1. Security Check with detailed logging
        error_log('MoBooking Enhanced Handler - Checking nonce...');
        if (!isset($_POST['nonce'])) {
            error_log('MoBooking Enhanced Handler - No nonce in POST data');
            wp_send_json_error(['message' => 'No security token provided'], 403);
            return;
        }

        $nonce_check = wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce');
        error_log('MoBooking Enhanced Handler - Nonce check result: ' . ($nonce_check ? 'PASS' : 'FAIL'));

        if (!$nonce_check) {
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.'], 403);
            return;
        }

        // 2. Validate Tenant ID
        error_log('MoBooking Enhanced Handler - Validating tenant ID...');
        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (!$tenant_id) {
            error_log('MoBooking Enhanced Handler - Invalid tenant ID: ' . $tenant_id);
            wp_send_json_error(['message' => 'Invalid business information.'], 400);
            return;
        }

        // Verify tenant exists
        $tenant_user = get_userdata($tenant_id);
        if (!$tenant_user) {
            error_log('MoBooking Enhanced Handler - Tenant user not found: ' . $tenant_id);
            wp_send_json_error(['message' => 'Business not found.'], 400);
            return;
        }

        error_log('MoBooking Enhanced Handler - Tenant validated: ' . $tenant_user->user_login);

        // 3. Parse JSON Data with enhanced error handling
        error_log('MoBooking Enhanced Handler - Parsing JSON data...');

        $customer_details = mobooking_enhanced_json_decode($_POST['customer_details'] ?? '', 'customer_details');
        $selected_services = mobooking_enhanced_json_decode($_POST['selected_services'] ?? '', 'selected_services');
        $service_options = mobooking_enhanced_json_decode($_POST['service_options'] ?? '{}', 'service_options');
        $pet_information = mobooking_enhanced_json_decode($_POST['pet_information'] ?? '{}', 'pet_information');
        $property_access = mobooking_enhanced_json_decode($_POST['property_access'] ?? '{}', 'property_access');
        $service_frequency = sanitize_text_field($_POST['service_frequency'] ?? 'one-time');

        error_log('MoBooking Enhanced Handler - JSON parsing completed');

        // 4. Validate Required Data
        if (!$customer_details || !is_array($customer_details)) {
            error_log('MoBooking Enhanced Handler - Invalid customer details');
            wp_send_json_error(['message' => 'Invalid customer information provided.'], 400);
            return;
        }

        if (!$selected_services || !is_array($selected_services) || empty($selected_services)) {
            error_log('MoBooking Enhanced Handler - Invalid or empty services');
            wp_send_json_error(['message' => 'Please select at least one service.'], 400);
            return;
        }

        // 5. Validate Required Customer Fields
        $required_fields = ['name', 'email', 'phone', 'date', 'time'];
        foreach ($required_fields as $field) {
            if (empty($customer_details[$field])) {
                error_log("MoBooking Enhanced Handler - Missing required field: {$field}");
                wp_send_json_error(['message' => "Missing required field: {$field}"], 400);
                return;
            }
        }

        // 6. Validate Email
        if (!is_email($customer_details['email'])) {
            error_log('MoBooking Enhanced Handler - Invalid email: ' . $customer_details['email']);
            wp_send_json_error(['message' => 'Please provide a valid email address.'], 400);
            return;
        }

        // 7. Process Services
        error_log('MoBooking Enhanced Handler - Processing services...');
        $total_amount = 0;
        $valid_services = [];

        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';

        // Check if services table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") != $services_table) {
            error_log('MoBooking Enhanced Handler - Services table does not exist: ' . $services_table);
            wp_send_json_error(['message' => 'Database configuration error. Please contact support.'], 500);
            return;
        }

        foreach ($selected_services as $service_item) {
            if (!isset($service_item['service_id'])) {
                continue;
            }

            $service_id = intval($service_item['service_id']);

            // Get service details from database
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$services_table} WHERE service_id = %d AND user_id = %d AND status = 'active'",
                $service_id,
                $tenant_id
            ), ARRAY_A);

            if ($service) {
                $valid_services[] = [
                    'service_id' => $service_id,
                    'name' => $service['name'],
                    'price' => floatval($service['price']),
                    'duration' => intval($service['duration']),
                    'options' => $service_item['configured_options'] ?? []
                ];
                $total_amount += floatval($service['price']);
                error_log("MoBooking Enhanced Handler - Valid service found: {$service['name']} (\${$service['price']})");
            }
        }

        if (empty($valid_services)) {
            error_log('MoBooking Enhanced Handler - No valid services found');
            wp_send_json_error(['message' => 'No valid services selected.'], 400);
            return;
        }

        // 8. Check Database Tables
        error_log('MoBooking Enhanced Handler - Checking database tables...');
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$bookings_table}'") != $bookings_table) {
            error_log('MoBooking Enhanced Handler - Bookings table does not exist: ' . $bookings_table);
            wp_send_json_error(['message' => 'Database not properly configured. Please contact support.'], 500);
            return;
        }

        // 9. Create Booking Record
        error_log('MoBooking Enhanced Handler - Creating booking record...');
        $booking_reference = 'MB-' . date('Ymd') . '-' . rand(1000, 9999);

        $booking_data = [
            'user_id' => $tenant_id,
            'booking_reference' => $booking_reference,
            'customer_name' => sanitize_text_field($customer_details['name']),
            'customer_email' => sanitize_email($customer_details['email']),
            'customer_phone' => sanitize_text_field($customer_details['phone']),
            'customer_address' => sanitize_textarea_field($customer_details['address'] ?? ''),
            'booking_date' => sanitize_text_field($customer_details['date']),
            'booking_time' => sanitize_text_field($customer_details['time']),
            'total_amount' => $total_amount,
            'status' => 'pending',
            'special_instructions' => sanitize_textarea_field($customer_details['instructions'] ?? ''),
            'service_frequency' => $service_frequency,
            'selected_services' => wp_json_encode($valid_services),
            'pet_information' => wp_json_encode($pet_information ?: []),
            'property_access' => wp_json_encode($property_access ?: []),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $insert_result = $wpdb->insert(
            $bookings_table,
            $booking_data,
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ]
        );

        if ($insert_result === false) {
            error_log('MoBooking Enhanced Handler - Database insert failed: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Failed to create booking. Database error.'], 500);
            return;
        }

        $booking_id = $wpdb->insert_id;
        error_log('MoBooking Enhanced Handler - Booking created successfully: ' . $booking_id);

        // 10. Send Emails (don't fail if this doesn't work)
        try {
            mobooking_enhanced_send_emails($booking_id, $booking_data, $valid_services);
        } catch (Exception $e) {
            error_log('MoBooking Enhanced Handler - Email sending failed: ' . $e->getMessage());
        }

        // 11. Success Response
        wp_send_json_success([
            'message' => 'Booking submitted successfully! We will contact you soon to confirm.',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'total_amount' => $total_amount,
            'booking_data' => [
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
                'customer_name' => $customer_details['name'],
                'customer_email' => $customer_details['email'],
                'booking_date' => $customer_details['date'],
                'booking_time' => $customer_details['time'],
                'total_amount' => $total_amount
            ]
        ]);

    } catch (Exception $e) {
        error_log('MoBooking Enhanced Handler - Exception: ' . $e->getMessage());
        error_log('MoBooking Enhanced Handler - Exception trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'An unexpected error occurred. Please try again.'], 500);
    } catch (Error $e) {
        error_log('MoBooking Enhanced Handler - Fatal Error: ' . $e->getMessage());
        error_log('MoBooking Enhanced Handler - Error trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'A system error occurred. Please contact support.'], 500);
    }
}

/**
 * Enhanced JSON decoder with detailed logging
 */
function mobooking_enhanced_json_decode($json_string, $context = 'data') {
    if (empty($json_string)) {
        return null;
    }

    error_log("MoBooking Enhanced Handler - Decoding {$context}: " . substr($json_string, 0, 100) . "...");

    // Clean the input
    $json_string = trim($json_string);
    $json_string = preg_replace('/^\xEF\xBB\xBF/', '', $json_string);

    // Try direct decode first
    $decoded = json_decode($json_string, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        error_log("MoBooking Enhanced Handler - {$context} decoded successfully");
        return $decoded;
    }

    // Try with stripslashes
    $decoded = json_decode(stripslashes($json_string), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        error_log("MoBooking Enhanced Handler - {$context} decoded with stripslashes");
        return $decoded;
    }

    // Try with wp_unslash
    $decoded = json_decode(wp_unslash($json_string), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        error_log("MoBooking Enhanced Handler - {$context} decoded with wp_unslash");
        return $decoded;
    }

    // Log the error
    error_log("MoBooking Enhanced Handler - JSON decode failed for {$context}: " . json_last_error_msg());
    error_log("MoBooking Enhanced Handler - Raw JSON: " . $json_string);

    return null;
}

/**
 * Send booking emails
 */
function mobooking_enhanced_send_emails($booking_id, $booking_data, $service_details) {
    $site_name = get_bloginfo('name');
    $admin_email = get_option('admin_email');

    // Customer email
    $customer_subject = sprintf('[%s] Booking Confirmation - %s', $site_name, $booking_data['booking_reference']);
    $customer_message = sprintf(
        "Dear %s,\n\nThank you for your booking!\n\nReference: %s\nDate: %s at %s\nServices: %s\nTotal: $%.2f\n\nWe'll contact you soon!\n\n%s",
        $booking_data['customer_name'],
        $booking_data['booking_reference'],
        $booking_data['booking_date'],
        $booking_data['booking_time'],
        implode(', ', array_column($service_details, 'name')),
        $booking_data['total_amount'],
        $site_name
    );

    wp_mail($booking_data['customer_email'], $customer_subject, $customer_message);

    // Admin email
    $admin_subject = sprintf('[%s] New Booking - %s', $site_name, $booking_data['booking_reference']);
    $admin_message = sprintf(
        "New booking:\n\nRef: %s\nCustomer: %s (%s)\nDate: %s at %s\nServices: %s\nTotal: $%.2f",
        $booking_data['booking_reference'],
        $booking_data['customer_name'],
        $booking_data['customer_email'],
        $booking_data['booking_date'],
        $booking_data['booking_time'],
        implode(', ', array_column($service_details, 'name')),
        $booking_data['total_amount']
    );

    wp_mail($admin_email, $admin_subject, $admin_message);
}

?>

<?php
// =============================================================================
// DATABASE UTILITIES
// =============================================================================
/**
 * Database Table Fix and Diagnostic for MoBooking
 * Add this to your functions.php or create as a separate plugin
 */

// The diagnostic page has been moved to classes/Database.php
// The action is now added in functions/initialization.php

// Enhanced booking handler that matches the actual table structure
add_action('wp_loaded', function() {
    remove_all_actions('wp_ajax_mobooking_create_booking');
    remove_all_actions('wp_ajax_nopriv_mobooking_create_booking');

    add_action('wp_ajax_mobooking_create_booking', 'mobooking_fixed_table_booking_handler');
    add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_fixed_table_booking_handler');
}, 9999);

function mobooking_fixed_table_booking_handler() {
    error_log('MoBooking Fixed Table Handler - Starting');

    try {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce')) {
            wp_send_json_error(['message' => 'Security check failed'], 403);
            return;
        }

        // Get and validate data
        $tenant_id = intval($_POST['tenant_id']);
        if (!$tenant_id || !get_userdata($tenant_id)) {
            wp_send_json_error(['message' => 'Invalid business information'], 400);
            return;
        }

        // Parse JSON data
        $customer_details = json_decode(stripslashes($_POST['customer_details']), true);
        $selected_services = json_decode(stripslashes($_POST['selected_services']), true);
        $service_options = json_decode(stripslashes($_POST['service_options'] ?? '{}'), true);
        $pet_information = json_decode(stripslashes($_POST['pet_information'] ?? '{}'), true);
        $property_access = json_decode(stripslashes($_POST['property_access'] ?? '{}'), true);
        $service_frequency = sanitize_text_field($_POST['service_frequency'] ?? 'one-time');

        // Validate required data
        if (!$customer_details || !$selected_services) {
            wp_send_json_error(['message' => 'Invalid form data'], 400);
            return;
        }

        $required_fields = ['name', 'email', 'phone', 'date', 'time'];
        foreach ($required_fields as $field) {
            if (empty($customer_details[$field])) {
                wp_send_json_error(['message' => "Missing required field: {$field}"], 400);
                return;
            }
        }

        // Calculate total from services
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $total_amount = 0;
        $valid_services = [];

        foreach ($selected_services as $service_item) {
            if (!isset($service_item['service_id'])) continue;

            $service_id = intval($service_item['service_id']);
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$services_table} WHERE service_id = %d AND user_id = %d",
                $service_id, $tenant_id
            ), ARRAY_A);

            if ($service) {
                $valid_services[] = [
                    'service_id' => $service_id,
                    'name' => $service['name'],
                    'price' => floatval($service['price']),
                    'options' => $service_item['configured_options'] ?? []
                ];
                $total_amount += floatval($service['price']);
            }
        }

        if (empty($valid_services)) {
            wp_send_json_error(['message' => 'No valid services selected'], 400);
            return;
        }

        // Prepare booking data to match actual table structure
        $booking_reference = 'MB-' . date('Ymd') . '-' . rand(1000, 9999);
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';

        $booking_data = [
            'user_id' => $tenant_id,
            'booking_reference' => $booking_reference,
            'customer_name' => sanitize_text_field($customer_details['name']),
            'customer_email' => sanitize_email($customer_details['email']),
            'customer_phone' => sanitize_text_field($customer_details['phone']),
            'customer_address' => sanitize_textarea_field($customer_details['address'] ?? ''),
            'booking_date' => sanitize_text_field($customer_details['date']),
            'booking_time' => sanitize_text_field($customer_details['time']),
            'total_amount' => $total_amount,
            'status' => 'pending',
            'special_instructions' => sanitize_textarea_field($customer_details['instructions'] ?? ''),
            'service_frequency' => $service_frequency,
            'selected_services' => wp_json_encode($valid_services),
            'pet_information' => wp_json_encode($pet_information ?: []),
            'property_access' => wp_json_encode($property_access ?: []),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        error_log('MoBooking Fixed Table Handler - Attempting insert with data: ' . print_r($booking_data, true));

        // Insert booking
        $insert_result = $wpdb->insert(
            $bookings_table,
            $booking_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($insert_result === false) {
            error_log('MoBooking Fixed Table Handler - Insert failed: ' . $wpdb->last_error);
            error_log('MoBooking Fixed Table Handler - Query: ' . $wpdb->last_query);
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error], 500);
            return;
        }

        $booking_id = $wpdb->insert_id;
        error_log('MoBooking Fixed Table Handler - Booking created: ' . $booking_id);

        // Send emails
        try {
            wp_mail(
                $customer_details['email'],
                'Booking Confirmation - ' . $booking_reference,
                "Thank you for your booking!\n\nReference: {$booking_reference}\nDate: {$customer_details['date']} at {$customer_details['time']}\nTotal: \${$total_amount}"
            );
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
        }

        // Success response
        wp_send_json_success([
            'message' => 'Booking submitted successfully!',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'total_amount' => $total_amount,
            'booking_data' => [
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
                'customer_name' => $customer_details['name'],
                'customer_email' => $customer_details['email'],
                'booking_date' => $customer_details['date'],
                'booking_time' => $customer_details['time'],
                'total_amount' => $total_amount
            ]
        ]);

    } catch (Exception $e) {
        error_log('MoBooking Fixed Table Handler - Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'System error occurred'], 500);
    }
}
?>

<?php
/**
 * Quick Fix for Column Name Issue
 * Add this to your functions.php - it replaces the previous booking handler
 */

// Remove previous handler and add the corrected one
add_action('wp_loaded', function() {
    remove_all_actions('wp_ajax_mobooking_create_booking');
    remove_all_actions('wp_ajax_nopriv_mobooking_create_booking');

    add_action('wp_ajax_mobooking_create_booking', 'mobooking_corrected_column_booking_handler');
    add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_corrected_column_booking_handler');
}, 9999);

function mobooking_corrected_column_booking_handler() {
    error_log('MoBooking Corrected Handler - Starting');

    try {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce')) {
            wp_send_json_error(['message' => 'Security check failed'], 403);
            return;
        }

        // Get and validate data
        $tenant_id = intval($_POST['tenant_id']);
        if (!$tenant_id || !get_userdata($tenant_id)) {
            wp_send_json_error(['message' => 'Invalid business information'], 400);
            return;
        }

        // Parse JSON data with error handling
        $customer_details = json_decode(stripslashes($_POST['customer_details']), true);
        $selected_services = json_decode(stripslashes($_POST['selected_services']), true);
        $service_options = json_decode(stripslashes($_POST['service_options'] ?? '{}'), true);
        $pet_information = json_decode(stripslashes($_POST['pet_information'] ?? '{}'), true);
        $property_access = json_decode(stripslashes($_POST['property_access'] ?? '{}'), true);
        $service_frequency = sanitize_text_field($_POST['service_frequency'] ?? 'one-time');

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('MoBooking Corrected Handler - JSON decode error: ' . json_last_error_msg());
            wp_send_json_error(['message' => 'Invalid form data format'], 400);
            return;
        }

        // Validate required data
        if (!$customer_details || !$selected_services) {
            wp_send_json_error(['message' => 'Invalid form data'], 400);
            return;
        }

        $required_fields = ['name', 'email', 'phone', 'date', 'time'];
        foreach ($required_fields as $field) {
            if (empty($customer_details[$field])) {
                wp_send_json_error(['message' => "Missing required field: {$field}"], 400);
                return;
            }
        }

        // Validate email
        if (!is_email($customer_details['email'])) {
            wp_send_json_error(['message' => 'Invalid email address'], 400);
            return;
        }

        // Calculate total from services
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $total_amount = 0;
        $valid_services = [];

        foreach ($selected_services as $service_item) {
            if (!isset($service_item['service_id'])) continue;

            $service_id = intval($service_item['service_id']);
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$services_table} WHERE service_id = %d AND user_id = %d",
                $service_id, $tenant_id
            ), ARRAY_A);

            if ($service) {
                $valid_services[] = [
                    'service_id' => $service_id,
                    'name' => $service['name'],
                    'price' => floatval($service['price']),
                    'options' => $service_item['configured_options'] ?? []
                ];
                $total_amount += floatval($service['price']);
            }
        }

        if (empty($valid_services)) {
            wp_send_json_error(['message' => 'No valid services selected'], 400);
            return;
        }

        // Generate booking reference
        $booking_reference = 'MB-' . date('Ymd') . '-' . rand(1000, 9999);
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';

        // Prepare booking data using CORRECT column names from your table structure
        $booking_data = [
            'user_id' => $tenant_id,
            'booking_reference' => $booking_reference,
            'customer_name' => sanitize_text_field($customer_details['name']),
            'customer_email' => sanitize_email($customer_details['email']),
            'customer_phone' => sanitize_text_field($customer_details['phone']),
            'service_address' => sanitize_textarea_field($customer_details['address'] ?? ''), // CORRECTED: service_address not customer_address
            'booking_date' => sanitize_text_field($customer_details['date']),
            'booking_time' => sanitize_text_field($customer_details['time']),
            'total_price' => $total_amount, // CORRECTED: total_price not total_amount
            'status' => 'pending',
            'special_instructions' => sanitize_textarea_field($customer_details['instructions'] ?? ''),
            'service_frequency' => $service_frequency,
            'has_pets' => $pet_information['has_pets'] ?? false,
            'pet_details' => sanitize_textarea_field($pet_information['details'] ?? ''),
            'property_access_method' => $property_access['method'] ?? 'home',
            'property_access_details' => sanitize_textarea_field($property_access['details'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        error_log('MoBooking Corrected Handler - Booking data prepared: ' . print_r($booking_data, true));

        // Insert booking with correct column format
        $insert_result = $wpdb->insert(
            $bookings_table,
            $booking_data,
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
            ]
        );

        if ($insert_result === false) {
            error_log('MoBooking Corrected Handler - Insert failed: ' . $wpdb->last_error);
            error_log('MoBooking Corrected Handler - Last query: ' . $wpdb->last_query);
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error], 500);
            return;
        }

        $booking_id = $wpdb->insert_id;
        error_log('MoBooking Corrected Handler - Booking created successfully: ' . $booking_id);

        // Send emails (don't fail booking if this fails)
        try {
            $site_name = get_bloginfo('name');

            // Customer email
            $customer_subject = "[{$site_name}] Booking Confirmation - {$booking_reference}";
            $customer_message = "Dear {$customer_details['name']},\n\n";
            $customer_message .= "Thank you for your booking!\n\n";
            $customer_message .= "Reference: {$booking_reference}\n";
            $customer_message .= "Date: {$customer_details['date']} at {$customer_details['time']}\n";
            $customer_message .= "Services: " . implode(', ', array_column($valid_services, 'name')) . "\n";
            $customer_message .= "Total: $" . number_format($total_amount, 2) . "\n\n";
            $customer_message .= "We'll contact you soon to confirm!\n\n";
            $customer_message .= "Best regards,\n{$site_name}";

            wp_mail($customer_details['email'], $customer_subject, $customer_message);

            // Admin email
            $admin_email = get_option('admin_email');
            $admin_subject = "[{$site_name}] New Booking - {$booking_reference}";
            $admin_message = "New booking received:\n\n";
            $admin_message .= "Reference: {$booking_reference}\n";
            $admin_message .= "Customer: {$customer_details['name']} ({$customer_details['email']})\n";
            $admin_message .= "Phone: {$customer_details['phone']}\n";
            $admin_message .= "Date: {$customer_details['date']} at {$customer_details['time']}\n";
            $admin_message .= "Services: " . implode(', ', array_column($valid_services, 'name')) . "\n";
            $admin_message .= "Total: $" . number_format($total_amount, 2) . "\n";
            $admin_message .= "Address: {$customer_details['address']}\n";
            if (!empty($customer_details['instructions'])) {
                $admin_message .= "Instructions: {$customer_details['instructions']}\n";
            }

            wp_mail($admin_email, $admin_subject, $admin_message);

        } catch (Exception $e) {
            error_log('MoBooking Corrected Handler - Email sending failed: ' . $e->getMessage());
        }

        // Success response
        wp_send_json_success([
            'message' => 'Booking submitted successfully! We will contact you soon to confirm.',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'total_amount' => $total_amount,
            'booking_data' => [
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
                'customer_name' => $customer_details['name'],
                'customer_email' => $customer_details['email'],
                'booking_date' => $customer_details['date'],
                'booking_time' => $customer_details['time'],
                'total_amount' => $total_amount
            ]
        ]);

    } catch (Exception $e) {
        error_log('MoBooking Corrected Handler - Exception: ' . $e->getMessage());
        error_log('MoBooking Corrected Handler - Exception trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'System error occurred'], 500);
    }
}

// Quick database column checker (for debugging)
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && isset($_GET['check_mobooking_columns'])) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';
        $columns = $wpdb->get_results("DESCRIBE $bookings_table");

        echo '<div class="notice notice-info">';
        echo '<h3>MoBooking Bookings Table Columns:</h3>';
        echo '<ul>';
        foreach ($columns as $column) {
            echo '<li><strong>' . esc_html($column->Field) . '</strong> - ' . esc_html($column->Type) . '</li>';
        }
        echo '</ul>';
        echo '<p>Add <code>?check_mobooking_columns=1</code> to any admin page URL to see this.</p>';
        echo '</div>';
    }
});
?>