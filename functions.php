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
            $GLOBALS['mobooking_bookings_manager'] = new \MoBooking\Classes\Bookings();
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
// Hook to initialize managers early
add_action('init', 'mobooking_initialize_managers', 1);


/**
 * Enhanced error logging for debugging
 * Add this to your theme's functions.php
 */
function mobooking_log_error($message, $context = []) {
    if (WP_DEBUG && WP_DEBUG_LOG) {
        $log_message = '[MoBooking Error] ' . $message;
        if (!empty($context)) {
            $log_message .= ' Context: ' . print_r($context, true);
        }
        error_log($log_message);
    }
}

/**
 * Check if required database tables exist
 * Add this to your theme's functions.php
 */
function mobooking_check_database_tables() {
    global $wpdb;
    
    $required_tables = [
        'services' => Database::get_table_name('services'),
        'bookings' => Database::get_table_name('bookings'),
        'customers' => Database::get_table_name('mob_customers')
    ];
    
    $missing_tables = [];
    
    foreach ($required_tables as $table_key => $table_name) {
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if (!$table_exists) {
            $missing_tables[] = $table_key;
        }
    }
    
    if (!empty($missing_tables)) {
        mobooking_log_error('Missing database tables: ' . implode(', ', $missing_tables));
        return false;
    }
    
    return true;
}

/**
 * AJAX handler to check system health
 * Add this to your theme's functions.php
 */
add_action('wp_ajax_mobooking_system_health', 'mobooking_ajax_system_health');
function mobooking_ajax_system_health() {
    $health_check = [
        'database_tables' => mobooking_check_database_tables(),
        'classes_loaded' => [
            'Services' => class_exists('MoBooking\Classes\Services'),
            'Bookings' => class_exists('MoBooking\Classes\Bookings'),
            'Customers' => class_exists('MoBooking\Classes\Customers'),
            'Auth' => class_exists('MoBooking\Classes\Auth'),
            'Database' => class_exists('MoBooking\Classes\Database'),
        ],
        'globals_initialized' => [
            'services_manager' => isset($GLOBALS['mobooking_services_manager']),
            'bookings_manager' => isset($GLOBALS['mobooking_bookings_manager']),
            'customers_manager' => isset($GLOBALS['mobooking_customers_manager']),
        ],
        'user_authenticated' => get_current_user_id() > 0,
        'wp_debug' => WP_DEBUG,
        'wp_debug_log' => WP_DEBUG_LOG,
    ];
    
    wp_send_json_success($health_check);
}


// ========================================
// FALLBACK ERROR HANDLERS
// ========================================

/**
 * Fallback AJAX error handler
 * Add this to catch any uncaught errors
 */
add_action('wp_ajax_nopriv_mobooking_fallback_error', 'mobooking_fallback_error_handler');
add_action('wp_ajax_mobooking_fallback_error', 'mobooking_fallback_error_handler');
function mobooking_fallback_error_handler() {
    mobooking_log_error('Fallback error handler called', $_POST);
    wp_send_json_error(['message' => 'An unexpected error occurred. Please check the server logs.'], 500);
}

/**
 * PHP error handler for AJAX requests
 */
function mobooking_ajax_error_handler($errno, $errstr, $errfile, $errline) {
    if (wp_doing_ajax()) {
        mobooking_log_error("PHP Error in AJAX: $errstr in $errfile on line $errline");
        
        // Don't let PHP errors break AJAX responses
        if (error_reporting() & $errno) {
            return false; // Let WordPress handle it
        }
    }
    return false;
}

set_error_handler('mobooking_ajax_error_handler');

error_log('MoBooking Debug: ' . print_r(get_defined_vars(), true));


// Add these AJAX hooks
add_action('wp_ajax_mobooking_submit_booking', [$mobooking_bookings_manager, 'handle_enhanced_booking_submission']);
add_action('wp_ajax_nopriv_mobooking_submit_booking', [$mobooking_bookings_manager, 'handle_enhanced_booking_submission']);

add_action('wp_ajax_mobooking_get_available_times', [$mobooking_availability_manager, 'get_available_time_slots']);
add_action('wp_ajax_nopriv_mobooking_get_available_times', [$mobooking_availability_manager, 'get_available_time_slots']);






?>




<?php
// Add this temporary debug function to your functions.php or ajax-fixes.php

function mobooking_debug_create_booking() {
    // Log all incoming data
    error_log('MoBooking Debug - POST data: ' . print_r($_POST, true));
    
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        error_log('MoBooking Debug - Nonce verification failed');
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    error_log("MoBooking Debug - tenant_id: $tenant_id, service_id: $service_id");

    if (!$tenant_id || !$service_id) {
        error_log('MoBooking Debug - Missing tenant_id or service_id');
        wp_send_json_error(['message' => 'Missing required information.'], 400);
        return;
    }

    // Check if it's JSON format (new format)
    $booking_data_json = isset($_POST['booking_data']) ? stripslashes($_POST['booking_data']) : '';
    if (!empty($booking_data_json)) {
        error_log('MoBooking Debug - Found JSON booking_data');
        $booking_data = json_decode($booking_data_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('MoBooking Debug - JSON decode error: ' . json_last_error_msg());
            wp_send_json_error(['message' => 'Invalid booking data format.'], 400);
            return;
        }
        error_log('MoBooking Debug - Parsed booking data: ' . print_r($booking_data, true));
    }

    // Log what fields we're checking
    $required_fields = ['booking_date', 'booking_time', 'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone'];
    foreach ($required_fields as $field) {
        $value = $_POST[$field] ?? '';
        error_log("MoBooking Debug - Field $field: '$value'");
        if (empty($value)) {
            error_log("MoBooking Debug - Missing required field: $field");
            wp_send_json_error(['message' => "Missing required field: $field"], 400);
            return;
        }
    }

    wp_send_json_success(['message' => 'Debug successful - all validations passed']);
}

// Replace the regular handler temporarily
add_action('wp_ajax_mobooking_create_booking', 'mobooking_debug_create_booking');
add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_debug_create_booking');
?>


<?php
// Temporary debug handler - add this to your functions.php or create a separate file
// This will help us see exactly what data is being sent and why it's failing

function mobooking_debug_services_issue() {
    // Enable detailed error logging
    error_log('=== MoBooking Debug Handler Called ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        error_log('MoBooking Debug - Nonce verification failed');
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    error_log("Tenant ID: $tenant_id");

    // Check each expected field
    $expected_fields = [
        'customer_details',
        'selected_services', 
        'service_options',
        'pet_information',
        'property_access',
        'service_frequency'
    ];

    foreach ($expected_fields as $field) {
        $value = $_POST[$field] ?? 'NOT_SET';
        error_log("Field '$field': " . (is_string($value) ? $value : print_r($value, true)));
        
        // If it's supposed to be JSON, try to decode it
        if (in_array($field, ['customer_details', 'selected_services', 'service_options', 'pet_information', 'property_access'])) {
            if ($value !== 'NOT_SET') {
                $decoded = json_decode($value, true);
                $json_error = json_last_error();
                error_log("JSON decode for '$field' - Error: " . $json_error . " (" . json_last_error_msg() . ")");
                error_log("Decoded '$field': " . print_r($decoded, true));
                
                // Special focus on selected_services since that's failing
                if ($field === 'selected_services') {
                    error_log("Selected services detailed analysis:");
                    error_log("- Raw value: '$value'");
                    error_log("- Value length: " . strlen($value));
                    error_log("- First 100 chars: " . substr($value, 0, 100));
                    error_log("- Last 100 chars: " . substr($value, -100));
                    
                    if ($decoded !== null) {
                        error_log("- Decoded type: " . gettype($decoded));
                        if (is_array($decoded)) {
                            error_log("- Array length: " . count($decoded));
                            foreach ($decoded as $index => $service) {
                                error_log("- Service $index: " . print_r($service, true));
                            }
                        }
                    }
                }
            }
        }
    }

    // Test the safe_json_decode function specifically with selected_services
    $selected_services_json = $_POST['selected_services'] ?? '';
    if (!empty($selected_services_json)) {
        error_log("Testing safe_json_decode equivalent for selected_services...");
        
        $decoded = json_decode($selected_services_json, true);
        $json_error = json_last_error();
        
        if ($json_error !== JSON_ERROR_NONE) {
            $error_msg = "JSON decode error for selected_services: " . json_last_error_msg();
            error_log($error_msg);
            wp_send_json_error(['message' => 'Invalid services data. JSON Error: ' . json_last_error_msg()], 400);
            return;
        }

        if (!is_array($decoded)) {
            error_log("selected_services decoded but is not an array: " . gettype($decoded));
            wp_send_json_error(['message' => 'Services data must be an array.'], 400);
            return;
        }

        if (empty($decoded)) {
            error_log("selected_services is an empty array");
            wp_send_json_error(['message' => 'No services selected.'], 400);
            return;
        }

        // Check each service in the array
        foreach ($decoded as $index => $service) {
            error_log("Validating service $index: " . print_r($service, true));
            
            if (!is_array($service)) {
                error_log("Service $index is not an array: " . gettype($service));
                wp_send_json_error(['message' => "Service $index is not properly formatted."], 400);
                return;
            }
            
            if (!isset($service['service_id'])) {
                error_log("Service $index missing service_id");
                wp_send_json_error(['message' => "Service $index missing service_id."], 400);
                return;
            }
            
            $service_id = intval($service['service_id']);
            if ($service_id <= 0) {
                error_log("Service $index has invalid service_id: " . $service['service_id']);
                wp_send_json_error(['message' => "Service $index has invalid service_id."], 400);
                return;
            }
        }
        
        error_log("All services validation passed!");
    }

    wp_send_json_success([
        'message' => 'Debug completed successfully!',
        'received_fields' => array_keys($_POST),
        'services_count' => !empty($decoded) ? count($decoded) : 0
    ]);
}

// Temporarily replace the booking handler with our debug version
remove_action('wp_ajax_mobooking_create_booking', 'handle_create_enhanced_booking'); // Remove existing handler if present
remove_action('wp_ajax_nopriv_mobooking_create_booking', 'handle_create_enhanced_booking');

add_action('wp_ajax_mobooking_create_booking', 'mobooking_debug_services_issue');
add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_debug_services_issue');

error_log('MoBooking debug handler registered');
?>




<?php
/**
 * Debug 500 Error Handler
 * Add this to your functions.php to debug the backend 500 error
 */

// 1. ENABLE DETAILED ERROR LOGGING
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// 2. CREATE A SAFE DEBUG BOOKING HANDLER
function mobooking_debug_500_handler() {
    // Log everything about the request
    error_log('=== MoBooking 500 Debug Handler ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('Action: ' . ($_POST['action'] ?? 'NOT_SET'));
    error_log('Nonce: ' . ($_POST['nonce'] ?? 'NOT_SET'));
    error_log('User ID: ' . get_current_user_id());
    error_log('Current user can: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
    
    try {
        // Check nonce first
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            error_log('MoBooking Debug - Nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed.'], 403);
            return;
        }
        
        error_log('MoBooking Debug - Nonce verification passed');
        
        // Check required POST fields for the Bookings.php format
        $required_fields = [
            'tenant_id',
            'customer_details', 
            'selected_services',
            'service_options',
            'pet_information',
            'property_access',
            'service_frequency'
        ];
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log('MoBooking Debug - Missing POST fields: ' . implode(', ', $missing_fields));
            wp_send_json_error(['message' => 'Missing required fields: ' . implode(', ', $missing_fields)], 400);
            return;
        }
        
        error_log('MoBooking Debug - All required fields present');
        
        // Test JSON decoding
        $customer_details_json = $_POST['customer_details'] ?? '';
        $customer_details = json_decode($customer_details_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('MoBooking Debug - Customer details JSON error: ' . json_last_error_msg());
            wp_send_json_error(['message' => 'Invalid customer details format'], 400);
            return;
        }
        
        $selected_services_json = $_POST['selected_services'] ?? '';
        $selected_services = json_decode($selected_services_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('MoBooking Debug - Selected services JSON error: ' . json_last_error_msg());
            wp_send_json_error(['message' => 'Invalid services format'], 400);
            return;
        }
        
        error_log('MoBooking Debug - JSON decoding successful');
        error_log('Customer details: ' . print_r($customer_details, true));
        error_log('Selected services: ' . print_r($selected_services, true));
        
        // Check if classes exist
        $class_checks = [
            'MoBooking\Classes\Bookings' => class_exists('MoBooking\Classes\Bookings'),
            'MoBooking\Classes\Database' => class_exists('MoBooking\Classes\Database'),
            'MoBooking\Classes\Services' => class_exists('MoBooking\Classes\Services'),
        ];
        
        error_log('MoBooking Debug - Class availability: ' . print_r($class_checks, true));
        
        // Check global managers
        global $mobooking_bookings_manager, $mobooking_services_manager;
        error_log('MoBooking Debug - Global managers:');
        error_log('$mobooking_bookings_manager exists: ' . (isset($mobooking_bookings_manager) ? 'YES' : 'NO'));
        error_log('$mobooking_services_manager exists: ' . (isset($mobooking_services_manager) ? 'YES' : 'NO'));
        
        // Check database tables
        global $wpdb;
        $tables_to_check = [
            $wpdb->prefix . 'mobooking_bookings',
            $wpdb->prefix . 'mobooking_services',
            $wpdb->prefix . 'mobooking_customers'
        ];
        
        foreach ($tables_to_check as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            error_log("Table $table exists: " . ($exists ? 'YES' : 'NO'));
        }
        
        // Try to instantiate the Bookings class if it doesn't exist globally
        if (!isset($mobooking_bookings_manager) && class_exists('MoBooking\Classes\Bookings')) {
            try {
                error_log('MoBooking Debug - Attempting to create Bookings instance');
                $bookings_instance = new MoBooking\Classes\Bookings();
                error_log('MoBooking Debug - Bookings instance created successfully');
                
                // Check if handle_create_enhanced_booking method exists
                if (method_exists($bookings_instance, 'handle_create_enhanced_booking')) {
                    error_log('MoBooking Debug - handle_create_enhanced_booking method exists');
                } else {
                    error_log('MoBooking Debug - handle_create_enhanced_booking method NOT found');
                    $available_methods = get_class_methods($bookings_instance);
                    error_log('Available methods: ' . implode(', ', $available_methods));
                }
                
            } catch (Exception $e) {
                error_log('MoBooking Debug - Error creating Bookings instance: ' . $e->getMessage());
                error_log('MoBooking Debug - Exception trace: ' . $e->getTraceAsString());
            }
        }
        
        // If we get here, basic validation passed
        wp_send_json_success([
            'message' => 'Debug completed successfully - no 500 error found in validation',
            'customer_count' => count($customer_details),
            'services_count' => count($selected_services),
            'debug_complete' => true
        ]);
        
    } catch (Throwable $e) {
        error_log('MoBooking Debug - Critical error: ' . $e->getMessage());
        error_log('MoBooking Debug - Error file: ' . $e->getFile() . ':' . $e->getLine());
        error_log('MoBooking Debug - Stack trace: ' . $e->getTraceAsString());
        
        wp_send_json_error([
            'message' => 'Critical error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
}

// 3. REPLACE THE REGULAR HANDLER TEMPORARILY
// Remove any existing handlers first
remove_action('wp_ajax_mobooking_create_booking', 'mobooking_create_booking_handler');
remove_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_create_booking_handler');

// Add our debug handler
add_action('wp_ajax_mobooking_create_booking', 'mobooking_debug_500_handler');
add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_debug_500_handler');

// 4. ALSO CREATE A FALLBACK ERROR HANDLER
function mobooking_catch_fatal_error() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (wp_doing_ajax() && isset($_POST['action']) && $_POST['action'] === 'mobooking_create_booking') {
            error_log('MoBooking Fatal Error: ' . print_r($error, true));
            
            // Try to send a JSON response
            if (!headers_sent()) {
                status_header(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'data' => [
                        'message' => 'Fatal PHP error: ' . $error['message'],
                        'file' => $error['file'],
                        'line' => $error['line']
                    ]
                ]);
            }
            exit;
        }
    }
}

register_shutdown_function('mobooking_catch_fatal_error');

error_log('MoBooking: Debug 500 handler installed');
?>