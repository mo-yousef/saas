<?php
/**
 * Migration script to add disable_discount_code column to services table
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
$services_table = $wpdb->prefix . 'nordbooking_services';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;

if (!$table_exists) {
    echo "Services table does not exist. Please run the main database creation first.";
    exit;
}

// Check if column already exists
$columns = $wpdb->get_results("DESCRIBE $services_table");
$column_names = array_column($columns, 'Field');

if (in_array('disable_discount_code', $column_names)) {
    echo "✅ disable_discount_code column already exists!";
    exit;
}

// Add the column
$sql = "ALTER TABLE $services_table ADD COLUMN disable_discount_code BOOLEAN NOT NULL DEFAULT 0 AFTER disable_frequency_option";
$result = $wpdb->query($sql);

if ($result !== false) {
    echo "✅ Successfully added disable_discount_code column to services table!";
} else {
    echo "❌ Failed to add column: " . $wpdb->last_error;
}
?>