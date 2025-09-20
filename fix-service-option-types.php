<?php
/**
 * Fix script to update service option types
 * 
 * @package NORDBOOKING
 */

// WordPress environment
require_once __DIR__ . '/wp-config.php';
require_once ABSPATH . 'wp-load.php';

// Check if user is logged in and has proper permissions
if (!is_user_logged_in() || !current_user_can('nordbooking_business_owner')) {
    wp_die('You must be logged in as a business owner to run this fix script.');
}

echo "<h1>Service Option Types Fix Script</h1>";

// Direct database query to find and fix various type issues
global $wpdb;
$table_name = \NORDBOOKING\Classes\Database::get_table_name('service_options');

// 1. Find all options with type 'dropdown' and update to 'select'
$dropdown_options = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'dropdown'", ARRAY_A);

echo "<h2>1. Fixing 'dropdown' → 'select'</h2>";
echo "<p>Found " . count($dropdown_options) . " service options with type 'dropdown'</p>";

if (!empty($dropdown_options)) {
    echo "<ul>";
    foreach ($dropdown_options as $option) {
        $updated = $wpdb->update(
            $table_name,
            ['type' => 'select'],
            ['option_id' => $option['option_id']],
            ['%s'],
            ['%d']
        );
        
        if ($updated !== false) {
            echo "<li>✅ Updated option '{$option['name']}' (ID: {$option['option_id']}) from 'dropdown' to 'select'</li>";
        } else {
            echo "<li>❌ Failed to update option '{$option['name']}' (ID: {$option['option_id']})</li>";
        }
    }
    echo "</ul>";
}

// 2. Find checkbox options without choices and update to 'toggle'
$checkbox_options = $wpdb->get_results("SELECT * FROM $table_name WHERE type = 'checkbox'", ARRAY_A);

echo "<h2>2. Fixing 'checkbox' without choices → 'toggle'</h2>";
echo "<p>Found " . count($checkbox_options) . " service options with type 'checkbox'</p>";

$updated_checkboxes = 0;
if (!empty($checkbox_options)) {
    echo "<ul>";
    foreach ($checkbox_options as $option) {
        // Check if this checkbox has no choices (empty or null option_values)
        $has_choices = false;
        if (!empty($option['option_values'])) {
            $decoded = json_decode($option['option_values'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $has_choices = true;
            }
        }
        
        if (!$has_choices) {
            // This is a simple yes/no checkbox, convert to toggle
            $updated = $wpdb->update(
                $table_name,
                ['type' => 'toggle'],
                ['option_id' => $option['option_id']],
                ['%s'],
                ['%d']
            );
            
            if ($updated !== false) {
                echo "<li>✅ Updated option '{$option['name']}' (ID: {$option['option_id']}) from 'checkbox' to 'toggle' (no choices found)</li>";
                $updated_checkboxes++;
            } else {
                echo "<li>❌ Failed to update option '{$option['name']}' (ID: {$option['option_id']})</li>";
            }
        } else {
            echo "<li>ℹ️ Kept option '{$option['name']}' (ID: {$option['option_id']}) as 'checkbox' (has choices)</li>";
        }
    }
    echo "</ul>";
}

// 3. Fix price impact types - convert old types to new simplified system
$old_price_types = $wpdb->get_results("SELECT * FROM $table_name WHERE price_impact_type IN ('per_unit', 'percentage', 'multiply')", ARRAY_A);

echo "<h2>3. Fixing old price impact types → 'fixed'</h2>";
echo "<p>Found " . count($old_price_types) . " service options with old price impact types</p>";

$updated_price_types = 0;
if (!empty($old_price_types)) {
    echo "<ul>";
    foreach ($old_price_types as $option) {
        $updated = $wpdb->update(
            $table_name,
            ['price_impact_type' => 'fixed'],
            ['option_id' => $option['option_id']],
            ['%s'],
            ['%d']
        );
        
        if ($updated !== false) {
            echo "<li>✅ Updated option '{$option['name']}' (ID: {$option['option_id']}) from '{$option['price_impact_type']}' to 'fixed'</li>";
            $updated_price_types++;
        } else {
            echo "<li>❌ Failed to update option '{$option['name']}' (ID: {$option['option_id']})</li>";
        }
    }
    echo "</ul>";
}

echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>Updated " . count($dropdown_options) . " 'dropdown' options to 'select'</li>";
echo "<li>Updated {$updated_checkboxes} 'checkbox' options to 'toggle'</li>";
echo "<li>Updated {$updated_price_types} old price impact types to 'fixed'</li>";
echo "</ul>";

// Show current state
$all_types = $wpdb->get_results("SELECT DISTINCT type, COUNT(*) as count FROM $table_name GROUP BY type", ARRAY_A);

echo "<h2>Current service option types in database:</h2>";
echo "<ul>";
foreach ($all_types as $type_info) {
    echo "<li>{$type_info['type']}: {$type_info['count']} options</li>";
}
echo "</ul>";

echo "<p><strong>Fix completed!</strong> Please refresh your service edit pages to see the changes.</p>";
?>