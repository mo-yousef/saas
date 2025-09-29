<?php
/**
 * Fix Database SQL Syntax Errors
 * 
 * This script fixes the MySQL syntax errors in the NordBooking plugin:
 * 1. Foreign key constraint syntax error
 * 2. DROP INDEX IF EXISTS syntax error (not supported in older MySQL versions)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function fix_nordbooking_database_errors() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
    $messages = [];
    $errors = [];
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    
    if (!$table_exists) {
        $errors[] = "Table $table_name does not exist. Please run the plugin installation first.";
        return ['messages' => $messages, 'errors' => $errors];
    }
    
    // Fix 1: Drop problematic index safely (compatible with older MySQL versions)
    try {
        // First check if the index exists
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
                $messages[] = "Successfully removed problematic unique constraint on stripe_subscription_id.";
            } else {
                $errors[] = "Failed to remove unique constraint: " . $wpdb->last_error;
            }
        } else {
            $messages[] = "Index stripe_subscription_id_unique does not exist (already removed or never created).";
        }
    } catch (Exception $e) {
        $errors[] = "Error checking/removing index: " . $e->getMessage();
    }
    
    // Fix 2: Check and fix foreign key constraint
    try {
        // Check if foreign key constraint exists
        $fk_exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM information_schema.key_column_usage 
            WHERE table_schema = DATABASE() 
            AND table_name = %s 
            AND referenced_table_name = %s
        ", $table_name, $wpdb->prefix . 'users'));
        
        if ($fk_exists == 0) {
            // Add foreign key constraint with proper syntax
            $result = $wpdb->query($wpdb->prepare("
                ALTER TABLE %s 
                ADD CONSTRAINT fk_subscription_user_id 
                FOREIGN KEY (user_id) REFERENCES %s(ID) ON DELETE CASCADE
            ", $table_name, $wpdb->prefix . 'users'));
            
            if ($result !== false) {
                $messages[] = "Successfully added foreign key constraint for user_id.";
            } else {
                $errors[] = "Failed to add foreign key constraint: " . $wpdb->last_error;
            }
        } else {
            $messages[] = "Foreign key constraint already exists.";
        }
    } catch (Exception $e) {
        $errors[] = "Error checking/adding foreign key: " . $e->getMessage();
    }
    
    // Fix 3: Clean up any problematic data
    try {
        // Convert empty strings to NULL for stripe_subscription_id
        $result = $wpdb->query("UPDATE $table_name SET stripe_subscription_id = NULL WHERE stripe_subscription_id = ''");
        if ($result !== false) {
            $messages[] = "Cleaned up empty stripe_subscription_id values (converted to NULL).";
        }
    } catch (Exception $e) {
        $errors[] = "Error cleaning up data: " . $e->getMessage();
    }
    
    // Fix 4: Ensure table structure is correct
    try {
        // Check current table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $column_names = array_column($columns, 'Field');
        
        $required_columns = [
            'id', 'user_id', 'status', 'stripe_customer_id', 
            'stripe_subscription_id', 'trial_ends_at', 'ends_at', 
            'created_at', 'updated_at'
        ];
        
        $missing_columns = array_diff($required_columns, $column_names);
        
        if (!empty($missing_columns)) {
            $errors[] = "Missing columns in table: " . implode(', ', $missing_columns);
        } else {
            $messages[] = "All required columns are present in the table.";
        }
    } catch (Exception $e) {
        $errors[] = "Error checking table structure: " . $e->getMessage();
    }
    
    return ['messages' => $messages, 'errors' => $errors];
}

// Run the fix if this file is accessed directly with proper authentication
if (isset($_GET['run_fix']) && current_user_can('manage_options')) {
    $result = fix_nordbooking_database_errors();
    
    echo "<h2>NordBooking Database Fix Results</h2>";
    
    if (!empty($result['messages'])) {
        echo "<h3 style='color: green;'>Success Messages:</h3>";
        echo "<ul>";
        foreach ($result['messages'] as $message) {
            echo "<li>" . esc_html($message) . "</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($result['errors'])) {
        echo "<h3 style='color: red;'>Errors:</h3>";
        echo "<ul>";
        foreach ($result['errors'] as $error) {
            echo "<li>" . esc_html($error) . "</li>";
        }
        echo "</ul>";
    }
    
    if (empty($result['errors'])) {
        echo "<p style='color: green; font-weight: bold;'>All database issues have been resolved!</p>";
    }
    
    echo "<p><a href='" . admin_url() . "'>Return to WordPress Admin</a></p>";
}

// Add admin notice if there are database errors
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        $fix_url = add_query_arg('run_fix', '1', home_url('/fix-database-errors.php'));
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>NordBooking Database Issues Detected:</strong> ';
        echo 'There are SQL syntax errors that need to be fixed. ';
        echo '<a href="' . esc_url($fix_url) . '" class="button button-primary">Fix Database Issues</a>';
        echo '</p>';
        echo '</div>';
    }
});