<?php
/**
 * NordBooking Database Diagnostic Tool
 * 
 * This script helps diagnose database issues that might be causing
 * the business settings save failures.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function run_database_diagnostics() {
    global $wpdb;
    
    $results = [
        'success' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    // Check if Database class exists
    if (!class_exists('NORDBOOKING\Classes\Database')) {
        $results['errors'][] = 'NORDBOOKING Database class not found. Plugin may not be properly loaded.';
        return $results;
    }
    
    // Check tenant_settings table
    $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
    $results['success'][] = "Table name resolved to: $table_name";
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    
    if (!$table_exists) {
        $results['errors'][] = "Table $table_name does not exist!";
        
        // Try to create the table
        try {
            \NORDBOOKING\Classes\Database::create_tables();
            $results['success'][] = "Attempted to create missing tables.";
            
            // Check again
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            if ($table_exists) {
                $results['success'][] = "Table $table_name created successfully.";
            } else {
                $results['errors'][] = "Failed to create table $table_name.";
            }
        } catch (Exception $e) {
            $results['errors'][] = "Exception creating tables: " . $e->getMessage();
        }
    } else {
        $results['success'][] = "Table $table_name exists.";
        
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $column_names = array_column($columns, 'Field');
        
        $expected_columns = ['setting_id', 'user_id', 'setting_name', 'setting_value'];
        $missing_columns = array_diff($expected_columns, $column_names);
        
        if (empty($missing_columns)) {
            $results['success'][] = "All expected columns are present.";
        } else {
            $results['errors'][] = "Missing columns: " . implode(', ', $missing_columns);
        }
        
        // Check indexes
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $index_names = array_column($indexes, 'Key_name');
        
        if (in_array('user_setting_unique', $index_names)) {
            $results['success'][] = "Unique constraint 'user_setting_unique' exists.";
        } else {
            $results['warnings'][] = "Unique constraint 'user_setting_unique' is missing.";
        }
        
        // Test basic operations
        $test_user_id = get_current_user_id();
        $test_setting_name = 'test_diagnostic_setting';
        $test_value = 'test_value_' . time();
        
        // Test INSERT/REPLACE
        $insert_result = $wpdb->replace(
            $table_name,
            [
                'user_id' => $test_user_id,
                'setting_name' => $test_setting_name,
                'setting_value' => $test_value
            ],
            ['%d', '%s', '%s']
        );
        
        if ($insert_result !== false) {
            $results['success'][] = "Test INSERT/REPLACE operation successful.";
            
            // Test SELECT
            $select_result = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $table_name WHERE user_id = %d AND setting_name = %s",
                $test_user_id,
                $test_setting_name
            ));
            
            if ($select_result === $test_value) {
                $results['success'][] = "Test SELECT operation successful.";
            } else {
                $results['errors'][] = "Test SELECT failed. Expected: $test_value, Got: $select_result";
            }
            
            // Clean up test data
            $wpdb->delete(
                $table_name,
                [
                    'user_id' => $test_user_id,
                    'setting_name' => $test_setting_name
                ],
                ['%d', '%s']
            );
            $results['success'][] = "Test data cleaned up.";
            
        } else {
            $results['errors'][] = "Test INSERT/REPLACE failed: " . $wpdb->last_error;
        }
        
        // Check table size and recent activity
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $results['success'][] = "Table contains $row_count rows.";
        
        if ($test_user_id) {
            $user_settings_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $test_user_id
            ));
            $results['success'][] = "Current user has $user_settings_count settings stored.";
        }
    }
    
    // Check WordPress database connection
    if ($wpdb->last_error) {
        $results['errors'][] = "WordPress database error: " . $wpdb->last_error;
    } else {
        $results['success'][] = "WordPress database connection is working.";
    }
    
    // Check MySQL version
    $mysql_version = $wpdb->get_var("SELECT VERSION()");
    $results['success'][] = "MySQL version: $mysql_version";
    
    // Check character set and collation
    $charset_info = $wpdb->get_row("SHOW TABLE STATUS LIKE '$table_name'");
    if ($charset_info) {
        $results['success'][] = "Table charset: " . $charset_info->Collation;
    }
    
    return $results;
}

// Handle form submission
$diagnostic_results = null;
if (isset($_POST['run_diagnostic']) && wp_verify_nonce($_POST['diagnostic_nonce'], 'nordbooking_diagnostic')) {
    $diagnostic_results = run_database_diagnostics();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>NordBooking Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #008000; background: #f0fff0; padding: 10px; margin: 5px 0; border-left: 4px solid #008000; }
        .error { color: #d00; background: #fff0f0; padding: 10px; margin: 5px 0; border-left: 4px solid #d00; }
        .warning { color: #f57c00; background: #fff8e1; padding: 10px; margin: 5px 0; border-left: 4px solid #f57c00; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #005a87; }
        .container { max-width: 800px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>NordBooking Database Diagnostic</h1>
        
        <p>This tool will check the database setup and identify potential issues with the business settings functionality.</p>
        
        <?php if ($diagnostic_results): ?>
            <h2>Diagnostic Results</h2>
            
            <?php if (!empty($diagnostic_results['success'])): ?>
                <h3>Success Messages</h3>
                <?php foreach ($diagnostic_results['success'] as $message): ?>
                    <div class="success"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($diagnostic_results['warnings'])): ?>
                <h3>Warnings</h3>
                <?php foreach ($diagnostic_results['warnings'] as $message): ?>
                    <div class="warning"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($diagnostic_results['errors'])): ?>
                <h3>Errors</h3>
                <?php foreach ($diagnostic_results['errors'] as $message): ?>
                    <div class="error"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (empty($diagnostic_results['errors'])): ?>
                <div class="success"><strong>All database checks passed! The issue may be elsewhere.</strong></div>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <form method="post" style="margin: 20px 0;">
            <?php wp_nonce_field('nordbooking_diagnostic', 'diagnostic_nonce'); ?>
            <input type="submit" name="run_diagnostic" value="Run Database Diagnostic" class="button">
        </form>
        
        <h3>Manual Database Check</h3>
        <p>You can also run this SQL query manually in phpMyAdmin to check the table:</p>
        <pre>
-- Check if table exists
SHOW TABLES LIKE '<?php echo $wpdb->prefix; ?>nordbooking_tenant_settings';

-- Check table structure
DESCRIBE <?php echo $wpdb->prefix; ?>nordbooking_tenant_settings;

-- Test basic operations
SELECT COUNT(*) FROM <?php echo $wpdb->prefix; ?>nordbooking_tenant_settings;

-- Check for current user's settings
SELECT * FROM <?php echo $wpdb->prefix; ?>nordbooking_tenant_settings 
WHERE user_id = <?php echo get_current_user_id(); ?> 
LIMIT 10;
        </pre>
        
        <p>
            <a href="<?php echo admin_url(); ?>" class="button">Return to WordPress Admin</a>
        </p>
    </div>
</body>
</html>