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
 * MoBooking Database Diagnostic Script
 * Run this to diagnose exactly what's in your database tables
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

function mobooking_run_database_diagnostic($tenant_id = null) {
    global $wpdb;
    
    // If no tenant_id provided, try to get current user's
    if (!$tenant_id) {
        $current_user_id = get_current_user_id();
        $tenant_id = \MoBooking\Classes\Auth::get_effective_tenant_id_for_user($current_user_id);
    }
    
    $diagnostic = [
        'tenant_id' => $tenant_id,
        'current_user_id' => get_current_user_id(),
        'tables' => [],
        'recommendations' => []
    ];
    
    // Define all the tables to check
    $tables_to_check = [
        'customers' => $wpdb->prefix . 'mobooking_customers',
        'bookings' => $wpdb->prefix . 'mobooking_bookings',
        'services' => $wpdb->prefix . 'mobooking_services',
        'booking_items' => $wpdb->prefix . 'mobooking_booking_items'
    ];
    
    foreach ($tables_to_check as $table_key => $table_name) {
        $table_info = [
            'name' => $table_name,
            'exists' => false,
            'total_rows' => 0,
            'tenant_rows' => 0,
            'sample_data' => [],
            'columns' => []
        ];
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        ));
        
        if ($table_exists) {
            $table_info['exists'] = true;
            
            // Get column information
            $columns = $wpdb->get_results("DESCRIBE $table_name", ARRAY_A);
            $table_info['columns'] = array_column($columns, 'Field');
            
            // Get total row count
            $table_info['total_rows'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            // Get tenant-specific row count
            if (in_array('tenant_id', $table_info['columns'])) {
                $table_info['tenant_rows'] = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE tenant_id = %d",
                    $tenant_id
                ));
            } elseif (in_array('user_id', $table_info['columns'])) {
                $table_info['tenant_rows'] = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                    $tenant_id
                ));
            }
            
            // Get sample data (first 3 rows for tenant)
            if ($table_info['tenant_rows'] > 0) {
                if (in_array('tenant_id', $table_info['columns'])) {
                    $table_info['sample_data'] = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE tenant_id = %d LIMIT 3",
                        $tenant_id
                    ), ARRAY_A);
                } elseif (in_array('user_id', $table_info['columns'])) {
                    $table_info['sample_data'] = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE user_id = %d LIMIT 3",
                        $tenant_id
                    ), ARRAY_A);
                }
            } elseif ($table_info['total_rows'] > 0) {
                // Get sample data without tenant filter
                $table_info['sample_data'] = $wpdb->get_results(
                    "SELECT * FROM $table_name LIMIT 3",
                    ARRAY_A
                );
            }
        }
        
        $diagnostic['tables'][$table_key] = $table_info;
    }
    
    // Generate recommendations
    $customers_table = $diagnostic['tables']['customers'];
    $bookings_table = $diagnostic['tables']['bookings'];
    
    if (!$customers_table['exists']) {
        $diagnostic['recommendations'][] = '❌ No customers table found. You need to create one or extract from bookings.';
    } elseif ($customers_table['tenant_rows'] == 0) {
        if ($bookings_table['tenant_rows'] > 0) {
            $diagnostic['recommendations'][] = '⚠️ Customers table exists but is empty. You have bookings data that can be migrated.';
        } else {
            $diagnostic['recommendations'][] = 'ℹ️ No customer or booking data found for your tenant ID.';
        }
    } elseif ($customers_table['tenant_rows'] > 0) {
        $diagnostic['recommendations'][] = '✅ Use customers table - it has ' . $customers_table['tenant_rows'] . ' customer records.';
    }
    
    // Check for potential issues
    if ($tenant_id <= 0) {
        $diagnostic['recommendations'][] = '🚨 ISSUE: Invalid tenant_id (' . $tenant_id . '). Check Auth::get_effective_tenant_id_for_user().';
    }
    
    if ($bookings_table['exists'] && $bookings_table['tenant_rows'] > 0) {
        $diagnostic['recommendations'][] = '✅ You have ' . $bookings_table['tenant_rows'] . ' bookings. Customers can be extracted from here.';
    }
    
    return $diagnostic;
}

// Admin page for diagnostics
add_action('admin_menu', 'mobooking_add_diagnostic_page');

function mobooking_add_diagnostic_page() {
    add_submenu_page(
        null, // Hidden page
        'Database Diagnostic',
        'Database Diagnostic', 
        'manage_options',
        'mobooking-diagnostic',
        'mobooking_diagnostic_page'
    );
}

function mobooking_diagnostic_page() {
    $tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : null;
    $diagnostic = mobooking_run_database_diagnostic($tenant_id);
    ?>
    <div class="wrap">
        <h1>MoBooking Database Diagnostic</h1>
        
        <div class="card">
            <h2>Overview</h2>
            <p><strong>Current User ID:</strong> <?php echo $diagnostic['current_user_id']; ?></p>
            <p><strong>Tenant ID:</strong> <?php echo $diagnostic['tenant_id']; ?></p>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="mobooking-diagnostic">
                <label>Test Different Tenant ID: </label>
                <input type="number" name="tenant_id" value="<?php echo $diagnostic['tenant_id']; ?>" min="1">
                <input type="submit" class="button" value="Run Diagnostic">
            </form>
        </div>
        
        <div class="card">
            <h2>Recommendations</h2>
            <?php if (!empty($diagnostic['recommendations'])): ?>
                <ul>
                    <?php foreach ($diagnostic['recommendations'] as $rec): ?>
                        <li style="margin: 0.5rem 0; padding: 0.5rem; background: #f9f9f9; border-left: 4px solid #0073aa;">
                            <?php echo esc_html($rec); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No specific recommendations.</p>
            <?php endif; ?>
        </div>
        
        <?php foreach ($diagnostic['tables'] as $table_key => $table_info): ?>
            <div class="card">
                <h2>Table: <?php echo esc_html($table_info['name']); ?></h2>
                
                <p><strong>Exists:</strong> <?php echo $table_info['exists'] ? '✅ Yes' : '❌ No'; ?></p>
                
                <?php if ($table_info['exists']): ?>
                    <p><strong>Total Rows:</strong> <?php echo $table_info['total_rows']; ?></p>
                    <p><strong>Your Tenant Rows:</strong> <?php echo $table_info['tenant_rows']; ?></p>
                    
                    <h3>Columns</h3>
                    <p><code><?php echo implode(', ', $table_info['columns']); ?></code></p>
                    
                    <?php if (!empty($table_info['sample_data'])): ?>
                        <h3>Sample Data</h3>
                        <div style="overflow-x: auto; max-height: 300px; background: #f9f9f9; padding: 1rem; border-radius: 4px;">
                            <pre><?php echo esc_html(print_r($table_info['sample_data'], true)); ?></pre>
                        </div>
                    <?php else: ?>
                        <p><em>No sample data available for your tenant.</em></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="card">
            <h2>Quick Fixes</h2>
            <p>Based on the diagnostic above, here are direct SQL queries you can run:</p>
            
            <h3>Check Customer Data in Bookings</h3>
            <code>
                SELECT customer_email, customer_name, COUNT(*) as booking_count 
                FROM wp_mobooking_bookings 
                WHERE user_id = <?php echo $diagnostic['tenant_id']; ?> 
                GROUP BY customer_email 
                LIMIT 10;
            </code>
            
            <h3>Check All Customers Tables</h3>
            <code>
                SELECT 'customers' as table_name, COUNT(*) as count FROM wp_mobooking_customers WHERE tenant_id = <?php echo $diagnostic['tenant_id']; ?>
                UNION ALL
                SELECT 'bookings_unique' as table_name, COUNT(DISTINCT customer_email) as count FROM wp_mobooking_bookings WHERE user_id = <?php echo $diagnostic['tenant_id']; ?>;
            </code>
        </div>
    </div>
    
    <style>
    .card {
        background: white;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 1.5rem;
        margin: 1rem 0;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    .card h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.5rem;
    }
    
    code {
        background: #f1f1f1;
        padding: 0.5rem;
        border-radius: 3px;
        display: block;
        margin: 0.5rem 0;
        white-space: pre-wrap;
        font-family: monospace;
    }
    </style>
    <?php
}

// Quick function to run diagnostic from anywhere
function mobooking_debug_customer_data($tenant_id = null) {
    $diagnostic = mobooking_run_database_diagnostic($tenant_id);
    error_log("MoBooking Diagnostic Results: " . print_r($diagnostic, true));
    return $diagnostic;
}

// Add diagnostic link in admin notices
add_action('admin_notices', 'mobooking_diagnostic_notice');

function mobooking_diagnostic_notice() {
    if (!isset($_GET['page']) || strpos($_GET['page'], 'mobooking') === false) {
        return;
    }
    
    // Only show if customers page shows no data
    if (isset($_GET['page']) && $_GET['page'] === 'mobooking-customers') {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>Troubleshooting Customers Issue:</strong> 
                If customers aren't showing, run a diagnostic to identify the problem.
                <a href="<?php echo admin_url('admin.php?page=mobooking-diagnostic'); ?>" class="button">
                    Run Database Diagnostic
                </a>
            </p>
        </div>
        <?php
    }
}
?>