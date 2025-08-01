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



<?php
/**
 * Enhanced Dashboard Asset Enqueue Function
 * Add this to your main plugin file or functions.php
 * @package MoBooking
 */

/**
 * Enqueue enhanced dashboard assets
 * Call this function on the dashboard overview page
 */
function mobooking_enqueue_enhanced_dashboard_assets() {
    // Only enqueue on dashboard overview page
    if (!is_admin() && !mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    $plugin_url = get_template_directory_uri() . '/';
    $version = defined('MOBOOKING_VERSION') ? MOBOOKING_VERSION : '1.0.0';
    
    // Enqueue Feather Icons first (highest priority)
    wp_enqueue_script(
        'feather-icons',
        'https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js',
        array(),
        '4.29.0',
        false // Load in head for immediate availability
    );
    
    // Enqueue Chart.js
    wp_enqueue_script(
        'chart-js',
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
        array(),
        '3.9.1',
        true
    );
    
    // Enqueue enhanced dashboard CSS
    wp_enqueue_style(
        'mobooking-dashboard-enhanced',
        $plugin_url . 'assets/css/dashboard-overview-enhanced.css',
        array(),
        $version
    );
    
    // Enqueue enhanced dashboard JavaScript (depends on feather and chart)
    wp_enqueue_script(
        'mobooking-dashboard-enhanced',
        $plugin_url . 'assets/js/dashboard-overview-enhanced.js',
        array('jquery', 'feather-icons', 'chart-js'),
        $version,
        true
    );
    
    // Localize script with dashboard data
    $current_user_id = get_current_user_id();
    $data_user_id = $current_user_id;
    $is_worker = false;
    
    // Handle workers
    if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
        $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
        if ($owner_id) {
            $data_user_id = $owner_id;
            $is_worker = true;
        }
    }
    
    // Get managers and data
    $services_manager = new \MoBooking\Classes\Services();
    $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
    $notifications_manager = new \MoBooking\Classes\Notifications();
    $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
    $settings_manager = new \MoBooking\Classes\Settings();
    
    // Get statistics
    $stats = $bookings_manager->get_booking_statistics($data_user_id);
    
    // Calculate metrics
    global $wpdb;
    $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
    
    $today_revenue = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed') AND DATE(booking_date) = CURDATE()",
        $data_user_id
    ));
    
    $week_bookings = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE user_id = %d AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)",
        $data_user_id
    ));
    
    $completed_bookings = $stats['by_status']['completed'] ?? 0;
    $total_bookings = $stats['total'];
    $completion_rate = ($total_bookings > 0) ? ($completed_bookings / $total_bookings) * 100 : 0;
    $avg_booking_value = ($total_bookings > 0) ? ($stats['total_revenue'] / $total_bookings) : 0;
    // Get setup progress
    $setup_progress = $settings_manager->get_setup_progress($data_user_id);
    $setup_percentage = 0;
    if (!empty($setup_progress['total_count']) && $setup_progress['total_count'] > 0) {
        $setup_percentage = round(($setup_progress['completed_count'] / $setup_progress['total_count']) * 100);
    }
    
    // Get services count
    $services_result = $services_manager->get_services_by_user($data_user_id, ['status' => 'active']);
    $active_services = $services_result['total_count'] ?? 0;
    
    // Get currency symbol
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));
    
    wp_localize_script('mobooking-dashboard-enhanced', 'mobooking_overview_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
        'currency_symbol' => $currency_symbol,
        'is_worker' => $is_worker,
        'dashboard_base_url' => home_url('/dashboard/'),
        'user_id' => $data_user_id,
        'current_time' => current_time('mysql'),
        'stats' => array(
            'total_revenue' => floatval($stats['total_revenue'] ?? 0),
            'total_bookings' => intval($stats['total'] ?? 0),
            'today_revenue' => floatval($today_revenue ?? 0),
            'completion_rate' => floatval($completion_rate),
            'week_bookings' => intval($week_bookings ?? 0),
            'avg_booking_value' => floatval($avg_booking_value),
            'active_services' => intval($active_services),
            'setup_percentage' => intval($setup_percentage)
        ),
        'i18n' => array(
            'time_ago_just_now' => __('Just now', 'mobooking'),
            'time_ago_seconds_suffix' => __('s ago', 'mobooking'),
            'time_ago_minutes_suffix' => __('m ago', 'mobooking'),
            'time_ago_hours_suffix' => __('h ago', 'mobooking'),
            'time_ago_days_suffix' => __('d ago', 'mobooking'),
            'loading' => __('Loading...', 'mobooking'),
            'no_data' => __('No data available', 'mobooking'),
            'error' => __('Error loading data', 'mobooking'),
            'copied' => __('Copied!', 'mobooking'),
            'new_booking' => __('New booking received', 'mobooking'),
            'booking_updated' => __('Booking status updated', 'mobooking'),
            'service_created' => __('New service created', 'mobooking'),
            'worker_added' => __('New worker added', 'mobooking'),
            'settings_updated' => __('Settings updated', 'mobooking'),
            'refresh_success' => __('Data refreshed successfully', 'mobooking'),
            'refresh_error' => __('Failed to refresh data', 'mobooking'),
            'export_success' => __('Data exported successfully', 'mobooking'),
            'export_error' => __('Failed to export data', 'mobooking'),
            'network_error' => __('Network connection error', 'mobooking'),
            'unauthorized' => __('You are not authorized to perform this action', 'mobooking')
        )
    ));
}

/**
 * Helper function to check if current page is dashboard overview
 */
function mobooking_is_dashboard_overview_page() {
    // Adjust this logic based on your routing system
    global $wp;
    $current_url = home_url($wp->request);
    
    // Check if we're on the dashboard overview page
    return (
        strpos($current_url, '/dashboard/') !== false && 
        (strpos($current_url, '/dashboard/overview') !== false || 
         $current_url === home_url('/dashboard/') ||
         $current_url === home_url('/dashboard'))
    );
}

/**
 * Add inline styles for critical rendering path optimization
 */
function mobooking_add_critical_dashboard_styles() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    ?>
    <style id="mobooking-critical-styles">
        /* Critical styles for immediate rendering */
        .mobooking-overview-enhanced {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: hsl(0 0% 100%);
            min-height: 100vh;
            padding: 1.5rem;
        }
        
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
        }
        
        .card {
            background: hsl(0 0% 100%);
            border: 1px solid hsl(214.3 31.8% 91.4%);
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        
        .grid-span-3 { grid-column: span 3; }
        .grid-span-4 { grid-column: span 4; }
        .grid-span-6 { grid-column: span 6; }
        .grid-span-8 { grid-column: span 8; }
        
        @media (max-width: 1024px) {
            .grid-span-3, .grid-span-4 { grid-column: span 6; }
            .grid-span-8 { grid-column: span 12; }
        }
        
        @media (max-width: 768px) {
            .overview-grid { grid-template-columns: 1fr; }
            .grid-span-3, .grid-span-4, .grid-span-6, .grid-span-8 { grid-column: span 1; }
            .mobooking-overview-enhanced { padding: 1rem; }
        }
    </style>
    <?php
}

/**
 * Hook the asset enqueue functions
 */
add_action('wp_enqueue_scripts', 'mobooking_enqueue_enhanced_dashboard_assets');
add_action('wp_head', 'mobooking_add_critical_dashboard_styles', 1);

/**
 * Add preload hints for external resources
 */
function mobooking_add_dashboard_preload_hints() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    ?>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" as="script">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js" as="script">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <?php
}
add_action('wp_head', 'mobooking_add_dashboard_preload_hints', 2);

/**
 * Add dashboard meta tags for better mobile experience
 */
function mobooking_add_dashboard_meta_tags() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    ?>
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MoBooking Dashboard">
    <?php
}
add_action('wp_head', 'mobooking_add_dashboard_meta_tags', 3);

/**
 * Add schema markup for dashboard data
 */
function mobooking_add_dashboard_schema() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    $current_user_id = get_current_user_id();
    if (!$current_user_id) return;
    
    // Get basic business info for schema
    $settings_manager = new \MoBooking\Classes\Settings();
    $business_name = $settings_manager->get_setting($current_user_id, 'biz_name', get_bloginfo('name'));
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'MoBooking Dashboard',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web Browser',
        'description' => 'Business booking management dashboard',
        'publisher' => array(
            '@type' => 'Organization',
            'name' => $business_name
        )
    );
    
    ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php
}
add_action('wp_head', 'mobooking_add_dashboard_schema', 4);

/**
 * Add service worker registration for offline capabilities (optional)
 */
function mobooking_add_dashboard_service_worker() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    ?>
    <script>
        // Register service worker for offline dashboard capabilities
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/mobooking-sw.js')
                    .then(function(registration) {
                        console.log('MoBooking SW registered:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('MoBooking SW registration failed:', error);
                    });
            });
        }
    </script>
    <?php
}
// Uncomment the line below if you want to add service worker support
// add_action('wp_footer', 'mobooking_add_dashboard_service_worker');

/**
 * Enhanced dashboard widget registration
 * This allows for modular widget system
 */
function mobooking_register_dashboard_widgets() {
    $widgets = array(
        'total_revenue' => array(
            'title' => __('Total Revenue', 'mobooking'),
            'icon' => 'dollar-sign',
            'class' => 'MoBooking\\Widgets\\TotalRevenueWidget',
            'priority' => 10
        ),
        'total_bookings' => array(
            'title' => __('Total Bookings', 'mobooking'),
            'icon' => 'calendar',
            'class' => 'MoBooking\\Widgets\\TotalBookingsWidget',
            'priority' => 20
        ),
        'today_revenue' => array(
            'title' => __('Today\'s Revenue', 'mobooking'),
            'icon' => 'bar-chart-2',
            'class' => 'MoBooking\\Widgets\\TodayRevenueWidget',
            'priority' => 30
        ),
        'completion_rate' => array(
            'title' => __('Completion Rate', 'mobooking'),
            'icon' => 'check-circle',
            'class' => 'MoBooking\\Widgets\\CompletionRateWidget',
            'priority' => 40
        ),
        'revenue_chart' => array(
            'title' => __('Revenue Overview', 'mobooking'),
            'icon' => 'trending-up',
            'class' => 'MoBooking\\Widgets\\RevenueChartWidget',
            'priority' => 50,
            'span' => 8
        ),
        'quick_stats' => array(
            'title' => __('Quick Stats', 'mobooking'),
            'icon' => 'activity',
            'class' => 'MoBooking\\Widgets\\QuickStatsWidget',
            'priority' => 60,
            'span' => 4
        ),
        'recent_bookings' => array(
            'title' => __('Recent Bookings', 'mobooking'),
            'icon' => 'list',
            'class' => 'MoBooking\\Widgets\\RecentBookingsWidget',
            'priority' => 70,
            'span' => 8
        ),
        'setup_progress' => array(
            'title' => __('Setup Progress', 'mobooking'),
            'icon' => 'check-square',
            'class' => 'MoBooking\\Widgets\\SetupProgressWidget',
            'priority' => 80,
            'span' => 4
        )
    );
    
    // Allow filtering of widgets
    $widgets = apply_filters('mobooking_dashboard_widgets', $widgets);
    
    // Store in global for access
    $GLOBALS['mobooking_dashboard_widgets'] = $widgets;
    
    return $widgets;
}

/**
 * Get dashboard widgets
 */
function mobooking_get_dashboard_widgets() {
    if (!isset($GLOBALS['mobooking_dashboard_widgets'])) {
        mobooking_register_dashboard_widgets();
    }
    
    return $GLOBALS['mobooking_dashboard_widgets'];
}

/**
 * Render dashboard widget
 */
function mobooking_render_dashboard_widget($widget_id, $widget_config) {
    $span_class = 'grid-span-' . ($widget_config['span'] ?? 3);
    $widget_data = mobooking_get_widget_data($widget_id);
    
    ?>
    <div class="<?php echo esc_attr($span_class); ?>">
        <div class="card" data-widget="<?php echo esc_attr($widget_id); ?>">
            <div class="card-header">
                <h3 class="card-title"><?php echo esc_html($widget_config['title']); ?></h3>
                <?php if (isset($widget_config['icon'])): ?>
                    <i data-feather="<?php echo esc_attr($widget_config['icon']); ?>" class="card-icon"></i>
                <?php endif; ?>
            </div>
            <div class="card-content">
                <?php
                // Render widget content based on type
                if (class_exists($widget_config['class'])) {
                    $widget_instance = new $widget_config['class']();
                    $widget_instance->render($widget_data);
                } else {
                    // Fallback rendering
                    mobooking_render_fallback_widget($widget_id, $widget_data);
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get widget data
 */
function mobooking_get_widget_data($widget_id) {
    // This would typically fetch data specific to each widget
    // For now, return the global stats
    return mobooking_overview_params['stats'] ?? array();
}

/**
 * Fallback widget rendering
 */
function mobooking_render_fallback_widget($widget_id, $data) {
    switch ($widget_id) {
        case 'total_revenue':
            ?>
            <div class="kpi-value" id="total-revenue-value">
                <?php echo esc_html(mobooking_overview_params['currency_symbol'] . number_format($data['total_revenue'] ?? 0, 2)); ?>
            </div>
            <div class="kpi-trend positive">
                <i data-feather="trending-up"></i>
                <span>+20.1% from last month</span>
            </div>
            <?php
            break;
            
        case 'total_bookings':
            ?>
            <div class="kpi-value" id="total-bookings-value">
                <?php echo esc_html($data['total_bookings'] ?? 0); ?>
            </div>
            <div class="kpi-trend positive">
                <i data-feather="trending-up"></i>
                <span>+12.5% from last month</span>
            </div>
            <?php
            break;
            
        default:
            echo '<p>' . esc_html__('Widget content not available', 'mobooking') . '</p>';
    }
}

/**
 * Dashboard performance optimization
 */
function mobooking_optimize_dashboard_performance() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    // Disable unnecessary WordPress features on dashboard
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Add performance hints
    ?>
    <style>
        /* Preload critical fonts */
        @font-face {
            font-family: 'System UI';
            src: local('system-ui'), local('-apple-system'), local('BlinkMacSystemFont');
            font-display: swap;
        }
    </style>
    <?php
}
add_action('wp_head', 'mobooking_optimize_dashboard_performance', 1);

/**
 * Add dashboard-specific body classes
 */
function mobooking_add_dashboard_body_classes($classes) {
    if (mobooking_is_dashboard_overview_page()) {
        $classes[] = 'mobooking-dashboard';
        $classes[] = 'mobooking-overview-page';
        
        // Add device-specific classes
        if (wp_is_mobile()) {
            $classes[] = 'mobooking-mobile';
        }
        
        // Add theme preference class
        $classes[] = 'mobooking-light-theme'; // Could be dynamic based on user preference
    }
    
    return $classes;
}
add_filter('body_class', 'mobooking_add_dashboard_body_classes');

/**
 * Dashboard security enhancements
 */
function mobooking_dashboard_security_headers() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    // Add security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
add_action('template_redirect', 'mobooking_dashboard_security_headers');

/**
 * Dashboard cache control
 */
function mobooking_dashboard_cache_control() {
    if (!mobooking_is_dashboard_overview_page()) {
        return;
    }
    
    // Set appropriate cache headers for dashboard
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
add_action('template_redirect', 'mobooking_dashboard_cache_control');

/**
 * Usage example in your dashboard page template:
 * 
 * // In your dashboard/page-overview.php file:
 * mobooking_enqueue_enhanced_dashboard_assets();
 * 
 * // Then include the enhanced overview page content
 * include 'enhanced-overview-content.php';
 */