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

// Add these AJAX hooks
add_action('wp_ajax_mobooking_submit_booking', [$mobooking_bookings_manager, 'handle_enhanced_booking_submission']);
add_action('wp_ajax_nopriv_mobooking_submit_booking', [$mobooking_bookings_manager, 'handle_enhanced_booking_submission']);

add_action('wp_ajax_mobooking_get_available_times', [$mobooking_availability_manager, 'get_available_time_slots']);
add_action('wp_ajax_nopriv_mobooking_get_available_times', [$mobooking_availability_manager, 'get_available_time_slots']);






?>




<?php
/**
 * Database Table Verification Script
 * Add this temporarily to your functions.php or create a separate debug page
 */

function debug_mobooking_service_areas_table() {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    
    echo '<div style="background: #f1f1f1; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
    echo '<h3>🔍 MoBooking Service Areas Database Debug</h3>';
    
    // Check table name generation
    echo '<h4>Table Name Check:</h4>';
    if (class_exists('MoBooking\Classes\Database')) {
        $table_name = MoBooking\Classes\Database::get_table_name('service_areas');
        echo '✅ Table name: ' . esc_html($table_name) . '<br>';
    } else {
        echo '❌ Database class not found<br>';
        // Fallback - try direct table name
        $table_name = $wpdb->prefix . 'mobooking_service_areas';
        echo '⚠️ Using fallback table name: ' . esc_html($table_name) . '<br>';
    }
    
    // Check if table exists
    echo '<h4>Table Existence:</h4>';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    if ($table_exists) {
        echo '✅ Table exists<br>';
        
        // Show table structure
        echo '<h4>Table Structure:</h4>';
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        if ($columns) {
            echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
            echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . esc_html($column->Field) . '</td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . esc_html($column->Default) . '</td>';
                echo '<td>' . esc_html($column->Extra) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // Show sample data
        echo '<h4>Sample Data:</h4>';
        $sample_data = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5", ARRAY_A);
        if ($sample_data) {
            echo '<p>Found ' . count($sample_data) . ' sample records:</p>';
            echo '<pre>' . esc_html(print_r($sample_data, true)) . '</pre>';
        } else {
            echo '<p>No data found in table</p>';
        }
        
        // Count by user
        echo '<h4>Data Count by User:</h4>';
        $user_counts = $wpdb->get_results("
            SELECT user_id, COUNT(*) as count 
            FROM $table_name 
            GROUP BY user_id 
            ORDER BY count DESC
        ");
        if ($user_counts) {
            foreach ($user_counts as $count) {
                $user = get_user_by('ID', $count->user_id);
                $username = $user ? $user->user_login : 'Unknown';
                echo "User ID {$count->user_id} ({$username}): {$count->count} areas<br>";
            }
        } else {
            echo 'No areas found<br>';
        }
        
    } else {
        echo '❌ Table does not exist<br>';
        echo '<p>Expected table name: ' . esc_html($table_name) . '</p>';
        
        // Show all tables starting with mobooking
        echo '<h4>Available MoBooking Tables:</h4>';
        $mobooking_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mobooking%'");
        if ($mobooking_tables) {
            foreach ($mobooking_tables as $table) {
                $table_name_val = array_values((array)$table)[0];
                echo '📋 ' . esc_html($table_name_val) . '<br>';
            }
        } else {
            echo 'No MoBooking tables found<br>';
        }
    }
    
    // Test Areas class methods
    echo '<h4>Areas Class Test:</h4>';
    if (class_exists('MoBooking\Classes\Areas')) {
        echo '✅ Areas class exists<br>';
        
        try {
            $areas = new MoBooking\Classes\Areas();
            echo '✅ Areas class instantiated<br>';
            
            // Test get_countries method
            $countries = $areas->get_countries();
            if (is_wp_error($countries)) {
                echo '❌ get_countries() error: ' . esc_html($countries->get_error_message()) . '<br>';
            } else {
                echo '✅ get_countries() returned ' . count($countries) . ' countries<br>';
            }
            
        } catch (Exception $e) {
            echo '❌ Error testing Areas class: ' . esc_html($e->getMessage()) . '<br>';
        }
    } else {
        echo '❌ Areas class not found<br>';
    }
    
    // Test current user
    echo '<h4>Current User:</h4>';
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        echo "✅ Logged in as: {$user->user_login} (ID: {$user_id})<br>";
        
        // Check user's areas
        if ($table_exists) {
            $user_areas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d", 
                $user_id
            ));
            echo "📊 Current user has {$user_areas} service areas<br>";
        }
    } else {
        echo '❌ User not logged in<br>';
    }
    
    echo '</div>';
}

// Hook to admin_notices to show in admin area
add_action('admin_notices', 'debug_mobooking_service_areas_table');

// Also create a shortcode for testing on frontend
add_shortcode('debug_areas_table', 'debug_mobooking_service_areas_table');

// Create a test endpoint to manually check table creation
add_action('wp_ajax_create_service_areas_table', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_service_areas';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        area_id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        area_type varchar(50) NOT NULL DEFAULT 'zip_code',
        area_name varchar(255) NOT NULL,
        area_value varchar(100) NOT NULL,
        country_code varchar(10) NOT NULL,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (area_id),
        KEY idx_user_id (user_id),
        KEY idx_user_country (user_id, country_code),
        KEY idx_user_status (user_id, status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    echo "Table creation attempted. Check the debug output above.";
    wp_die();
});

?>