<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.4' );
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

        wp_enqueue_script('mobooking-booking-form-public', get_template_directory_uri() . '/assets/js/booking-form-public.js', ['jquery', 'jquery-ui-datepicker'], MOBOOKING_VERSION, true);
        wp_enqueue_style('mobooking-booking-form-public', get_template_directory_uri() . '/assets/css/booking-form.css', [], MOBOOKING_VERSION);
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

<?php
/**
 * MoBooking Public Booking Form AJAX Fixes
 * Add this to your functions.php file
 */

// Fix 1: Register the missing AJAX handlers with correct names and parameters
add_action('wp_ajax_mobooking_get_available_slots', 'mobooking_handle_get_available_slots');
add_action('wp_ajax_nopriv_mobooking_get_available_slots', 'mobooking_handle_get_available_slots');

function mobooking_handle_get_available_slots() {
    // Verify nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }
    
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    
    if (!$tenant_id || !$date) {
        wp_send_json_error(['message' => 'Missing required parameters.'], 400);
        return;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(['message' => 'Invalid date format.'], 400);
        return;
    }
    
    try {
        $day_of_week = date('w', strtotime($date));
        
        // Check if we have the availability manager
        global $mobooking_availability_manager;
        if ($mobooking_availability_manager) {
            $schedule = $mobooking_availability_manager->get_recurring_schedule($tenant_id);
        } else {
            // Fallback: Create basic time slots
            $schedule = mobooking_get_default_availability_schedule();
        }
        
        $slots = [];
        foreach ($schedule as $day) {
            if ($day['day_of_week'] == $day_of_week && !empty($day['is_enabled'])) {
                if (isset($day['slots']) && is_array($day['slots'])) {
                    foreach ($day['slots'] as $slot) {
                        $slots[] = [
                            'time' => $slot['start_time'],
                            'display' => date('g:i A', strtotime($slot['start_time']))
                        ];
                    }
                }
            }
        }
        
        // If no slots found, provide default hourly slots
        if (empty($slots)) {
            $slots = mobooking_generate_default_time_slots();
        }
        
        wp_send_json_success($slots);
        
    } catch (Exception $e) {
        error_log('MoBooking - Get available slots error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error loading available times.'], 500);
    }
}

// Fix 2: Override the existing public services handler to ensure proper data structure
add_action('wp_ajax_mobooking_get_public_services_fixed', 'mobooking_handle_get_public_services_fixed');
add_action('wp_ajax_nopriv_mobooking_get_public_services_fixed', 'mobooking_handle_get_public_services_fixed');

function mobooking_handle_get_public_services_fixed() {
    // Verify nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }
    
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    if (!$tenant_id) {
        wp_send_json_error(['message' => 'Tenant ID is required.'], 400);
        return;
    }
    
    try {
        // Get services using existing manager if available
        global $mobooking_services_manager;
        if ($mobooking_services_manager && method_exists($mobooking_services_manager, 'get_services_by_tenant_id')) {
            $services = $mobooking_services_manager->get_services_by_tenant_id($tenant_id);
        } else {
            // Fallback: Get services directly from database
            $services = mobooking_get_services_fallback($tenant_id);
        }
        
        // Ensure proper data structure
        $formatted_services = [];
        if (is_array($services)) {
            foreach ($services as $service) {
                $service_array = (array) $service;
                
                // Ensure required fields exist
                $formatted_service = [
                    'service_id' => intval($service_array['service_id'] ?? 0),
                    'name' => $service_array['name'] ?? 'Unnamed Service',
                    'description' => $service_array['description'] ?? '',
                    'price' => floatval($service_array['price'] ?? 0),
                    'duration' => intval($service_array['duration'] ?? 60),
                    'icon_url' => $service_array['icon_url'] ?? $service_array['icon'] ?? '',
                    'image_url' => $service_array['image_url'] ?? '',
                    'status' => $service_array['status'] ?? 'active'
                ];
                
                // Format price
                if ($formatted_service['price'] > 0) {
                    $formatted_service['price_formatted'] = number_format($formatted_service['price'], 2);
                } else {
                    $formatted_service['price_formatted'] = 'Contact for pricing';
                }
                
                // Get service options
                $formatted_service['options'] = mobooking_get_service_options_fallback($formatted_service['service_id'], $tenant_id);
                
                $formatted_services[] = $formatted_service;
            }
        }
        
        wp_send_json_success($formatted_services);
        
    } catch (Exception $e) {
        error_log('MoBooking - Get public services error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error loading services.'], 500);
    }
}

// Fix 3: Fallback function to get services directly from database
function mobooking_get_services_fallback($tenant_id) {
    global $wpdb;
    
    $services_table = $wpdb->prefix . 'mobooking_services';
    if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") != $services_table) {
        return [];
    }
    
    $services = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $services_table WHERE user_id = %d AND status = 'active' ORDER BY name ASC",
        $tenant_id
    ), ARRAY_A);
    
    return $services ?: [];
}

// Fix 4: Fallback function to get service options
function mobooking_get_service_options_fallback($service_id, $tenant_id) {
    global $wpdb;
    
    $options_table = $wpdb->prefix . 'mobooking_service_options';
    if ($wpdb->get_var("SHOW TABLES LIKE '$options_table'") != $options_table) {
        return [];
    }
    
    $options = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $options_table WHERE service_id = %d AND user_id = %d ORDER BY sort_order ASC, name ASC",
        $service_id, $tenant_id
    ), ARRAY_A);
    
    if (!$options) {
        return [];
    }
    
    $formatted_options = [];
    foreach ($options as $option) {
        $formatted_option = [
            'option_id' => intval($option['option_id']),
            'name' => $option['name'],
            'description' => $option['description'] ?? '',
            'type' => $option['type'],
            'required' => boolval($option['is_required'] ?? false),
            'is_required' => boolval($option['is_required'] ?? false),
            'price_impact' => floatval($option['price_impact_value'] ?? 0),
            'price_impact_value' => floatval($option['price_impact_value'] ?? 0),
            'price_impact_type' => $option['price_impact_type'] ?? '',
            'option_values' => $option['option_values'] ?? null,
            'sort_order' => intval($option['sort_order'] ?? 0)
        ];
        
        $formatted_options[] = $formatted_option;
    }
    
    return $formatted_options;
}

// Fix 5: Generate default time slots if no availability is configured
function mobooking_generate_default_time_slots() {
    $slots = [];
    $start_hour = 9; // 9 AM
    $end_hour = 17;  // 5 PM
    
    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        $time_24 = sprintf('%02d:00:00', $hour);
        $time_12 = date('g:i A', strtotime($time_24));
        
        $slots[] = [
            'time' => $time_24,
            'display' => $time_12
        ];
    }
    
    return $slots;
}

// Fix 6: Default availability schedule
function mobooking_get_default_availability_schedule() {
    $default_slots = [
        ['start_time' => '09:00:00', 'end_time' => '10:00:00'],
        ['start_time' => '10:00:00', 'end_time' => '11:00:00'],
        ['start_time' => '11:00:00', 'end_time' => '12:00:00'],
        ['start_time' => '13:00:00', 'end_time' => '14:00:00'],
        ['start_time' => '14:00:00', 'end_time' => '15:00:00'],
        ['start_time' => '15:00:00', 'end_time' => '16:00:00'],
        ['start_time' => '16:00:00', 'end_time' => '17:00:00'],
    ];
    
    return [
        // Monday through Friday
        ['day_of_week' => 1, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 2, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 3, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 4, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 5, 'is_enabled' => true, 'slots' => $default_slots],
        // Weekend disabled
        ['day_of_week' => 0, 'is_enabled' => false, 'slots' => []],
        ['day_of_week' => 6, 'is_enabled' => false, 'slots' => []],
    ];
}

// Fix 7: Register proper booking submission handler with correct nonce
add_action('wp_ajax_mobooking_submit_booking', 'mobooking_handle_booking_submission');
add_action('wp_ajax_nopriv_mobooking_submit_booking', 'mobooking_handle_booking_submission');

function mobooking_handle_booking_submission() {
    // Verify nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }
    
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    
    if (!$tenant_id || !$service_id) {
        wp_send_json_error(['message' => 'Missing required information.'], 400);
        return;
    }
    
    // Collect form data
    $booking_data = [
        'tenant_id' => $tenant_id,
        'service_id' => $service_id,
        'booking_date' => sanitize_text_field($_POST['booking_date'] ?? ''),
        'booking_time' => sanitize_text_field($_POST['booking_time'] ?? ''),
        'customer_first_name' => sanitize_text_field($_POST['customer_first_name'] ?? ''),
        'customer_last_name' => sanitize_text_field($_POST['customer_last_name'] ?? ''),
        'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
        'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
        'customer_address' => sanitize_text_field($_POST['customer_address'] ?? ''),
        'customer_notes' => sanitize_textarea_field($_POST['customer_notes'] ?? ''),
        'service_options' => $_POST['service_options'] ?? []
    ];
    
    // Validate required fields
    $required_fields = ['booking_date', 'booking_time', 'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone'];
    foreach ($required_fields as $field) {
        if (empty($booking_data[$field])) {
            wp_send_json_error(['message' => "Missing required field: $field"], 400);
            return;
        }
    }
    
    // Validate email
    if (!is_email($booking_data['customer_email'])) {
        wp_send_json_error(['message' => 'Invalid email address.'], 400);
        return;
    }
    
    try {
        // Try to use existing bookings manager if available
        global $mobooking_bookings_manager;
        if ($mobooking_bookings_manager && method_exists($mobooking_bookings_manager, 'create_booking')) {
            $result = $mobooking_bookings_manager->create_booking($booking_data);
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()], 500);
                return;
            }
        } else {
            // Fallback: Create basic booking record
            $booking_id = mobooking_create_basic_booking($booking_data);
            if (!$booking_id) {
                wp_send_json_error(['message' => 'Failed to create booking.'], 500);
                return;
            }
        }
        
        // Send notification emails
        mobooking_send_booking_notification_emails($booking_data);
        
        wp_send_json_success([
            'message' => 'Booking submitted successfully! We will contact you soon to confirm.',
            'booking_data' => $booking_data
        ]);
        
    } catch (Exception $e) {
        error_log('MoBooking - Booking submission error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An error occurred while processing your booking.'], 500);
    }
}

// Fix 8: Basic booking creation fallback
function mobooking_create_basic_booking($booking_data) {
    global $wpdb;
    
    // Check if bookings table exists
    $bookings_table = $wpdb->prefix . 'mobooking_bookings';
    if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") != $bookings_table) {
        error_log('MoBooking - Bookings table does not exist: ' . $bookings_table);
        return false;
    }
    
    $booking_reference = 'BK' . date('Ymd') . rand(1000, 9999);
    
    $result = $wpdb->insert(
        $bookings_table,
        [
            'user_id' => $booking_data['tenant_id'],
            'service_id' => $booking_data['service_id'],
            'booking_date' => $booking_data['booking_date'],
            'booking_time' => $booking_data['booking_time'],
            'customer_first_name' => $booking_data['customer_first_name'],
            'customer_last_name' => $booking_data['customer_last_name'],
            'customer_email' => $booking_data['customer_email'],
            'customer_phone' => $booking_data['customer_phone'],
            'customer_address' => $booking_data['customer_address'],
            'customer_notes' => $booking_data['customer_notes'],
            'booking_reference' => $booking_reference,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ],
        [
            '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ]
    );
    
    if ($result === false) {
        error_log('MoBooking - Database error creating booking: ' . $wpdb->last_error);
        return false;
    }
    
    return $wpdb->insert_id;
}

// Fix 9: Send notification emails
function mobooking_send_booking_notification_emails($booking_data) {
    // Customer confirmation email
    $customer_subject = 'Booking Confirmation - ' . get_bloginfo('name');
    $customer_message = "Dear {$booking_data['customer_first_name']},\n\n";
    $customer_message .= "Thank you for your booking request!\n\n";
    $customer_message .= "Booking Details:\n";
    $customer_message .= "Date: {$booking_data['booking_date']}\n";
    $customer_message .= "Time: {$booking_data['booking_time']}\n";
    $customer_message .= "Service ID: {$booking_data['service_id']}\n\n";
    $customer_message .= "We will contact you soon to confirm the details.\n\n";
    $customer_message .= "Best regards,\n" . get_bloginfo('name');
    
    wp_mail($booking_data['customer_email'], $customer_subject, $customer_message);
    
    // Business owner notification
    $owner = get_userdata($booking_data['tenant_id']);
    if ($owner && $owner->user_email) {
        $owner_subject = 'New Booking Request - ' . get_bloginfo('name');
        $owner_message = "You have received a new booking request!\n\n";
        $owner_message .= "Customer: {$booking_data['customer_first_name']} {$booking_data['customer_last_name']}\n";
        $owner_message .= "Email: {$booking_data['customer_email']}\n";
        $owner_message .= "Phone: {$booking_data['customer_phone']}\n";
        $owner_message .= "Date: {$booking_data['booking_date']}\n";
        $owner_message .= "Time: {$booking_data['booking_time']}\n";
        $owner_message .= "Service ID: {$booking_data['service_id']}\n";
        if (!empty($booking_data['customer_notes'])) {
            $owner_message .= "Notes: {$booking_data['customer_notes']}\n";
        }
        $owner_message .= "\nPlease log in to your dashboard to manage this booking.";
        
        wp_mail($owner->user_email, $owner_subject, $owner_message);
    }
}

// Fix 10: Debug logging for tenant resolution issues
add_action('wp_ajax_mobooking_debug_tenant', 'mobooking_debug_tenant_resolution');
add_action('wp_ajax_nopriv_mobooking_debug_tenant', 'mobooking_debug_tenant_resolution');

function mobooking_debug_tenant_resolution() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error(['message' => 'Debug mode not enabled.'], 403);
        return;
    }
    
    $tenant_slug = isset($_POST['tenant_slug']) ? sanitize_text_field($_POST['tenant_slug']) : '';
    
    if (empty($tenant_slug)) {
        wp_send_json_error(['message' => 'Tenant slug required.'], 400);
        return;
    }
    
    global $wpdb;
    
    // Method 1: Check settings table
    $settings_table = $wpdb->prefix . 'mobooking_tenant_settings';
    $tenant_from_settings = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $settings_table WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
        $tenant_slug
    ));
    
    // Method 2: Check WordPress users
    $user_from_slug = get_user_by('slug', $tenant_slug);
    
    // Method 3: Get all users with business owner role
    $business_owners = get_users(['role' => 'mobooking_business_owner']);
    
    $debug_info = [
        'tenant_slug' => $tenant_slug,
        'method_1_settings_table' => [
            'table' => $settings_table,
            'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table,
            'tenant_id' => $tenant_from_settings
        ],
        'method_2_wp_user' => [
            'user_found' => $user_from_slug ? true : false,
            'user_id' => $user_from_slug ? $user_from_slug->ID : null,
            'user_roles' => $user_from_slug ? $user_from_slug->roles : null
        ],
        'all_business_owners' => array_map(function($user) {
            return [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_nicename' => $user->user_nicename,
                'display_name' => $user->display_name
            ];
        }, $business_owners)
    ];
    
    wp_send_json_success($debug_info);
}

// Fix 11: Add a higher priority hook to override the existing services handler
add_action('init', function() {
    // Remove existing handler if it exists
    remove_action('wp_ajax_mobooking_get_public_services', 'mobooking_ajax_get_public_services');
    remove_action('wp_ajax_nopriv_mobooking_get_public_services', 'mobooking_ajax_get_public_services');
    
    // Add our fixed handler
    add_action('wp_ajax_mobooking_get_public_services', 'mobooking_handle_get_public_services_fixed');
    add_action('wp_ajax_nopriv_mobooking_get_public_services', 'mobooking_handle_get_public_services_fixed');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking AJAX Fixes: Services handler overridden');
    }
}, 25);

// Fix 12: Add debug logging for all AJAX requests
add_action('wp_ajax_mobooking_debug_ajax_request', 'mobooking_debug_ajax_request');
add_action('wp_ajax_nopriv_mobooking_debug_ajax_request', 'mobooking_debug_ajax_request');

function mobooking_debug_ajax_request() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error(['message' => 'Debug mode not enabled.'], 403);
        return;
    }
    
    $action = isset($_POST['debug_action']) ? sanitize_text_field($_POST['debug_action']) : '';
    
    if (empty($action)) {
        wp_send_json_error(['message' => 'Debug action required.'], 400);
        return;
    }
    
    $debug_info = [
        'action' => $action,
        'post_data' => $_POST,
        'get_data' => $_GET,
        'server_info' => [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ],
        'wp_info' => [
            'is_admin' => is_admin(),
            'current_user_id' => get_current_user_id(),
            'doing_ajax' => wp_doing_ajax(),
        ]
    ];
     
    wp_send_json_success($debug_info);
}

// Initialize all fixes
add_action('init', function() {
    // Ensure proper initialization order
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking AJAX Fixes: Initialized at priority 20');
    }
}, 20);
?>