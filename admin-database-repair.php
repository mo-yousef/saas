<?php
/**
 * NordBooking Database Repair Tool
 * 
 * This admin tool fixes common database issues in the NordBooking plugin
 * including SQL syntax errors and constraint problems.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function nordbooking_repair_database() {
    global $wpdb;
    
    $results = [
        'success' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    $tables_to_check = [
        'nordbooking_subscriptions',
        'nordbooking_bookings',
        'nordbooking_services',
        'nordbooking_customers',
        'nordbooking_booking_items'
    ];
    
    foreach ($tables_to_check as $table_suffix) {
        $table_name = $wpdb->prefix . $table_suffix;
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        
        if (!$table_exists) {
            $results['warnings'][] = "Table $table_name does not exist - skipping.";
            continue;
        }
        
        $results['success'][] = "Table $table_name exists.";
        
        // Fix specific issues for subscriptions table
        if ($table_suffix === 'nordbooking_subscriptions') {
            $repair_result = repair_subscriptions_table($table_name);
            $results['success'] = array_merge($results['success'], $repair_result['success']);
            $results['errors'] = array_merge($results['errors'], $repair_result['errors']);
            $results['warnings'] = array_merge($results['warnings'], $repair_result['warnings']);
        }
        
        // Check for orphaned records
        if (in_array($table_suffix, ['nordbooking_bookings', 'nordbooking_services', 'nordbooking_subscriptions'])) {
            $orphan_result = check_orphaned_records($table_name, $table_suffix);
            $results['success'] = array_merge($results['success'], $orphan_result['success']);
            $results['errors'] = array_merge($results['errors'], $orphan_result['errors']);
            $results['warnings'] = array_merge($results['warnings'], $orphan_result['warnings']);
        }
    }
    
    return $results;
}

function repair_subscriptions_table($table_name) {
    global $wpdb;
    
    $results = [
        'success' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    try {
        // 1. Remove problematic unique constraint if it exists
        $index_exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = %s 
            AND index_name = 'stripe_subscription_id_unique'
        ", $table_name));
        
        if ($index_exists > 0) {
            $result = $wpdb->query("ALTER TABLE $table_name DROP INDEX stripe_subscription_id_unique");
            if ($result !== false) {
                $results['success'][] = "Removed problematic unique constraint on stripe_subscription_id.";
            } else {
                $results['errors'][] = "Failed to remove unique constraint: " . $wpdb->last_error;
            }
        } else {
            $results['success'][] = "No problematic unique constraint found.";
        }
        
        // 2. Check and add foreign key constraint if missing
        $fk_exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM information_schema.key_column_usage 
            WHERE table_schema = DATABASE() 
            AND table_name = %s 
            AND referenced_table_name = %s
        ", $table_name, $wpdb->prefix . 'users'));
        
        if ($fk_exists == 0) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD CONSTRAINT fk_subscription_user_id FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE");
            if ($result !== false) {
                $results['success'][] = "Added foreign key constraint for user_id.";
            } else {
                $results['errors'][] = "Failed to add foreign key constraint: " . $wpdb->last_error;
            }
        } else {
            $results['success'][] = "Foreign key constraint already exists.";
        }
        
        // 3. Clean up empty string values
        $result = $wpdb->query("UPDATE $table_name SET stripe_subscription_id = NULL WHERE stripe_subscription_id = ''");
        if ($result !== false) {
            $affected_rows = $wpdb->rows_affected;
            if ($affected_rows > 0) {
                $results['success'][] = "Cleaned up $affected_rows empty stripe_subscription_id values.";
            } else {
                $results['success'][] = "No empty stripe_subscription_id values found.";
            }
        }
        
        // 4. Check table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $column_names = array_column($columns, 'Field');
        
        $required_columns = [
            'id', 'user_id', 'status', 'stripe_customer_id', 
            'stripe_subscription_id', 'trial_ends_at', 'ends_at', 
            'created_at', 'updated_at'
        ];
        
        $missing_columns = array_diff($required_columns, $column_names);
        
        if (!empty($missing_columns)) {
            $results['errors'][] = "Missing columns: " . implode(', ', $missing_columns);
        } else {
            $results['success'][] = "All required columns are present.";
        }
        
    } catch (Exception $e) {
        $results['errors'][] = "Exception during repair: " . $e->getMessage();
    }
    
    return $results;
}

function check_orphaned_records($table_name, $table_suffix) {
    global $wpdb;
    
    $results = [
        'success' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    try {
        // Check for orphaned records (user_id references non-existent users)
        $orphaned_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table_name t
            LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        if ($orphaned_count > 0) {
            $results['warnings'][] = "Found $orphaned_count orphaned records in $table_name (user_id references non-existent users).";
            
            // Optionally clean up orphaned records (commented out for safety)
            // $wpdb->query("DELETE t FROM $table_name t LEFT JOIN {$wpdb->prefix}users u ON t.user_id = u.ID WHERE u.ID IS NULL");
            // $results['success'][] = "Cleaned up $orphaned_count orphaned records.";
        } else {
            $results['success'][] = "No orphaned records found in $table_name.";
        }
        
    } catch (Exception $e) {
        $results['errors'][] = "Exception checking orphaned records: " . $e->getMessage();
    }
    
    return $results;
}

// Handle form submission
$repair_results = null;
if (isset($_POST['run_repair']) && wp_verify_nonce($_POST['repair_nonce'], 'nordbooking_repair')) {
    $repair_results = nordbooking_repair_database();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>NordBooking Database Repair Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #008000; background: #f0fff0; padding: 10px; margin: 5px 0; border-left: 4px solid #008000; }
        .error { color: #d00; background: #fff0f0; padding: 10px; margin: 5px 0; border-left: 4px solid #d00; }
        .warning { color: #f57c00; background: #fff8e1; padding: 10px; margin: 5px 0; border-left: 4px solid #f57c00; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #005a87; }
        .container { max-width: 800px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>NordBooking Database Repair Tool</h1>
        
        <p>This tool will check and repair common database issues in the NordBooking plugin, including:</p>
        <ul>
            <li>SQL syntax errors with foreign key constraints</li>
            <li>Problematic unique constraints</li>
            <li>Empty string values that should be NULL</li>
            <li>Missing table columns</li>
            <li>Orphaned records</li>
        </ul>
        
        <?php if ($repair_results): ?>
            <h2>Repair Results</h2>
            
            <?php if (!empty($repair_results['success'])): ?>
                <h3>Success Messages</h3>
                <?php foreach ($repair_results['success'] as $message): ?>
                    <div class="success"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($repair_results['warnings'])): ?>
                <h3>Warnings</h3>
                <?php foreach ($repair_results['warnings'] as $message): ?>
                    <div class="warning"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($repair_results['errors'])): ?>
                <h3>Errors</h3>
                <?php foreach ($repair_results['errors'] as $message): ?>
                    <div class="error"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (empty($repair_results['errors'])): ?>
                <div class="success"><strong>Database repair completed successfully!</strong></div>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <form method="post" style="margin: 20px 0;">
            <?php wp_nonce_field('nordbooking_repair', 'repair_nonce'); ?>
            <input type="submit" name="run_repair" value="Run Database Repair" class="button" 
                   onclick="return confirm('Are you sure you want to run the database repair? This will modify your database structure.');">
        </form>
        
        <p>
            <a href="<?php echo admin_url(); ?>" class="button">Return to WordPress Admin</a>
        </p>
        
        <h3>Manual SQL Commands (if needed)</h3>
        <p>If the automated repair doesn't work, you can run these SQL commands manually in phpMyAdmin or similar:</p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">
-- Remove problematic unique constraint
DROP INDEX stripe_subscription_id_unique ON <?php echo $wpdb->prefix; ?>nordbooking_subscriptions;

-- Add foreign key constraint
ALTER TABLE <?php echo $wpdb->prefix; ?>nordbooking_subscriptions 
ADD CONSTRAINT fk_subscription_user_id 
FOREIGN KEY (user_id) REFERENCES <?php echo $wpdb->prefix; ?>users(ID) ON DELETE CASCADE;

-- Clean up empty values
UPDATE <?php echo $wpdb->prefix; ?>nordbooking_subscriptions 
SET stripe_subscription_id = NULL 
WHERE stripe_subscription_id = '';
        </pre>
    </div>
</body>
</html>