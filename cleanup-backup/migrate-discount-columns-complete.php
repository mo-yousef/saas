<?php
/**
 * Complete migration script for discount system
 * Adds all necessary columns for discount functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Only allow admin access
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

global $wpdb;

echo "<h1>NORDBOOKING Discount System Migration</h1>";

// 1. Add disable_discount_code to services table
echo "<h2>1. Services Table Migration</h2>";
$services_table = $wpdb->prefix . 'nordbooking_services';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;

if ($table_exists) {
    $columns = $wpdb->get_results("DESCRIBE $services_table");
    $column_names = array_column($columns, 'Field');
    
    if (!in_array('disable_discount_code', $column_names)) {
        $sql = "ALTER TABLE $services_table ADD COLUMN disable_discount_code BOOLEAN NOT NULL DEFAULT 0 AFTER disable_frequency_option";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p style='color: green;'>✅ Added disable_discount_code column to services table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add disable_discount_code column: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ disable_discount_code column already exists in services table</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Services table does not exist</p>";
}

// 2. Add discount columns to bookings table
echo "<h2>2. Bookings Table Migration</h2>";
$bookings_table = $wpdb->prefix . 'nordbooking_bookings';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table;

if ($table_exists) {
    $columns = $wpdb->get_results("DESCRIBE $bookings_table");
    $column_names = array_column($columns, 'Field');
    
    // Add discount_id column
    if (!in_array('discount_id', $column_names)) {
        $sql = "ALTER TABLE $bookings_table ADD COLUMN discount_id BIGINT UNSIGNED NULL AFTER total_price";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p style='color: green;'>✅ Added discount_id column to bookings table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add discount_id column: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ discount_id column already exists in bookings table</p>";
    }
    
    // Add discount_amount column
    if (!in_array('discount_amount', $column_names)) {
        $sql = "ALTER TABLE $bookings_table ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER discount_id";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p style='color: green;'>✅ Added discount_amount column to bookings table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add discount_amount column: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ discount_amount column already exists in bookings table</p>";
    }
    
    // Add index for discount_id
    $indexes = $wpdb->get_results("SHOW INDEX FROM $bookings_table WHERE Key_name = 'discount_id_idx'");
    if (empty($indexes)) {
        $sql = "ALTER TABLE $bookings_table ADD INDEX discount_id_idx (discount_id)";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p style='color: green;'>✅ Added index for discount_id column</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add discount_id index: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ discount_id index already exists</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Bookings table does not exist</p>";
}

// 3. Verify discounts table exists
echo "<h2>3. Discounts Table Verification</h2>";
$discounts_table = $wpdb->prefix . 'nordbooking_discounts';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$discounts_table'") == $discounts_table;

if ($table_exists) {
    echo "<p style='color: green;'>✅ Discounts table exists</p>";
    
    $discount_count = $wpdb->get_var("SELECT COUNT(*) FROM $discounts_table");
    echo "<p style='color: blue;'>ℹ️ Total discount codes: $discount_count</p>";
} else {
    echo "<p style='color: red;'>❌ Discounts table does not exist - run main database creation</p>";
}

echo "<h2>Migration Complete</h2>";
echo "<p>You can now test the discount system using the debug and test pages.</p>";
echo "<p><a href='debug-discount-system.php'>Debug Discount System</a> | <a href='test-discount-system.php'>Test Discount System</a></p>";
?>