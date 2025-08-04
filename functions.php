<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.11' );
}
if ( ! defined( 'MOBOOKING_THEME_DIR' ) ) {
    define( 'MOBOOKING_THEME_DIR', trailingslashit( get_template_directory() ) );
}
if ( ! defined( 'MOBOOKING_THEME_URI' ) ) {
    define( 'MOBOOKING_THEME_URI', trailingslashit( get_template_directory_uri() ) );
}

// Include the separated functional files
require_once MOBOOKING_THEME_DIR . 'classes/Database.php';
require_once MOBOOKING_THEME_DIR . 'classes/Services.php';
require_once MOBOOKING_THEME_DIR . 'classes/Bookings.php';
require_once MOBOOKING_THEME_DIR . 'classes/Customers.php';
require_once MOBOOKING_THEME_DIR . 'classes/Payments/Manager.php';
require_once MOBOOKING_THEME_DIR . 'functions/ajax.php';
require_once MOBOOKING_THEME_DIR . 'functions/theme-setup.php';
require_once MOBOOKING_THEME_DIR . 'functions/autoloader.php';
require_once MOBOOKING_THEME_DIR . 'functions/routing.php';
require_once MOBOOKING_THEME_DIR . 'functions/initialization.php';
require_once MOBOOKING_THEME_DIR . 'functions/utilities.php';
require_once MOBOOKING_THEME_DIR . 'functions/debug.php';
require_once MOBOOKING_THEME_DIR . 'functions/email.php';


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







