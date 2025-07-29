<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.9' );
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
require_once MOBOOKING_THEME_DIR . 'functions/enqueue.php';


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

function mobooking_enqueue_public_booking_form_assets() {
    if (is_page_template('templates/booking-form-public.php') || is_singular('booking')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');

        wp_enqueue_script('mobooking-booking-form', get_template_directory_uri() . '/assets/js/booking-form-public.js', ['jquery', 'jquery-ui-datepicker'], MOBOOKING_VERSION, true);
        wp_enqueue_style('mobooking-booking-form', get_template_directory_uri() . '/assets/css/booking-form.css', [], MOBOOKING_VERSION);
    }
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_public_booking_form_assets');

?>



