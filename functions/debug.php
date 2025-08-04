<?php

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
// Add this debug function to your functions.php temporarily to troubleshoot
function mobooking_debug_booking_form_access() {
    if (!current_user_can('manage_options')) {
        return; // Only allow admins to see debug info
    }

    if (isset($_GET['mobooking_debug']) && $_GET['mobooking_debug'] === '1') {
        global $wpdb;

        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
        echo '<h2>MoBooking Debug Information</h2>';

        // Check if rewrite rules are working
        echo '<h3>1. Rewrite Rules Check</h3>';
        $rules = get_option('rewrite_rules');
        $booking_rules = array_filter($rules, function($key) {
            return strpos($key, 'booking') !== false;
        }, ARRAY_FILTER_USE_KEY);
        echo '<pre>Booking-related rewrite rules: ' . print_r($booking_rules, true) . '</pre>';

        // Check current user settings
        echo '<h3>2. Current User Settings</h3>';
        $user_id = get_current_user_id();
        $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
        $user_settings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$settings_table} WHERE user_id = %d AND setting_name LIKE 'bf_%'",
            $user_id
        ));
        echo '<pre>Current user booking form settings: ' . print_r($user_settings, true) . '</pre>';

        // Check all business slugs
        echo '<h3>3. All Business Slugs</h3>';
        $all_slugs = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, setting_value FROM {$settings_table} WHERE setting_name = %s",
            'bf_business_slug'
        ));
        echo '<pre>All business slugs: ' . print_r($all_slugs, true) . '</pre>';

        // Test slug lookup
        echo '<h3>4. Test Slug Lookup</h3>';
        if (!empty($user_settings)) {
            foreach ($user_settings as $setting) {
                if ($setting->setting_name === 'bf_business_slug' && !empty($setting->setting_value)) {
                    $test_user_id = mobooking_get_user_id_by_slug($setting->setting_value);
                    echo '<p>Testing slug "' . $setting->setting_value . '" returns user_id: ' . ($test_user_id ?: 'NULL') . '</p>';

                    // Test the actual URL
                    $test_url = home_url('/' . $setting->setting_value . '/booking/');
                    echo '<p>Expected booking URL: <a href="' . $test_url . '" target="_blank">' . $test_url . '</a></p>';
                }
            }
        }

        // Check template file existence
        echo '<h3>5. Template File Check</h3>';
        $template_path = get_template_directory() . '/templates/booking-form-public.php';
        echo '<p>Template exists: ' . (file_exists($template_path) ? 'YES' : 'NO') . '</p>';
        echo '<p>Template path: ' . $template_path . '</p>';

        echo '</div>';
    }
}
add_action('wp_footer', 'mobooking_debug_booking_form_access');
add_action('admin_footer', 'mobooking_debug_booking_form_access');
?>
