<?php
/**
 * Debug script to check service options
 * 
 * @package NORDBOOKING
 */

// WordPress environment
require_once __DIR__ . '/wp-config.php';
require_once ABSPATH . 'wp-load.php';

// Check if user is logged in and has proper permissions
if (!is_user_logged_in() || !current_user_can('nordbooking_business_owner')) {
    wp_die('You must be logged in as a business owner to run this debug script.');
}

$user_id = get_current_user_id();
echo "<h1>Service Options Debug for User ID: {$user_id}</h1>";

// Initialize classes
$services_manager = new \NORDBOOKING\Classes\Services();
$service_options_manager = new \NORDBOOKING\Classes\ServiceOptions();

// Get all services for this user
$services_result = $services_manager->get_services_by_user($user_id);
$services = $services_result['services'] ?? [];

echo "<h2>Found " . count($services) . " services</h2>";

foreach ($services as $service) {
    echo "<h3>Service: {$service['name']} (ID: {$service['service_id']})</h3>";
    
    // Get service options
    $options = $service_options_manager->get_service_options($service['service_id'], $user_id);
    
    echo "<p>Found " . count($options) . " options for this service:</p>";
    
    if (!empty($options)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr><th>Option ID</th><th>Name</th><th>Type</th><th>Required</th><th>Price Impact</th><th>Has Choices?</th><th>Option Values (Decoded)</th></tr>";
        
        foreach ($options as $option) {
            echo "<tr>";
            echo "<td>{$option['option_id']}</td>";
            echo "<td>{$option['name']}</td>";
            echo "<td><strong>{$option['type']}</strong></td>";
            echo "<td>" . ($option['is_required'] ? 'Yes' : 'No') . "</td>";
            
            // Price impact info
            $price_info = '';
            if (!empty($option['price_impact_type']) && !empty($option['price_impact_value'])) {
                $price_info = $option['price_impact_type'] . ': ' . $option['price_impact_value'];
            }
            echo "<td>{$price_info}</td>";
            
            // Check if has choices
            $has_choices = false;
            $decoded = null;
            if (!empty($option['option_values'])) {
                if (is_string($option['option_values'])) {
                    $decoded = json_decode($option['option_values'], true);
                } elseif (is_array($option['option_values'])) {
                    $decoded = $option['option_values'];
                }
                if (is_array($decoded) && !empty($decoded)) {
                    $has_choices = true;
                }
            }
            
            echo "<td>" . ($has_choices ? '<strong style="color: green;">YES (' . count($decoded) . ' choices)</strong>' : '<span style="color: red;">NO</span>') . "</td>";
            echo "<td><pre>" . htmlspecialchars(print_r($decoded, true)) . "</pre></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No options found for this service.</p>";
    }
}

echo "<h2>Raw Database Query</h2>";

// Direct database query to check what's actually stored
global $wpdb;
$table_name = \NORDBOOKING\Classes\Database::get_table_name('service_options');
$raw_options = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id), ARRAY_A);

echo "<p>Raw database results:</p>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Option ID</th><th>Service ID</th><th>Name</th><th>Type</th><th>Option Values (Raw DB)</th></tr>";

foreach ($raw_options as $raw_option) {
    echo "<tr>";
    echo "<td>{$raw_option['option_id']}</td>";
    echo "<td>{$raw_option['service_id']}</td>";
    echo "<td>{$raw_option['name']}</td>";
    echo "<td>{$raw_option['type']}</td>";
    echo "<td><pre>" . htmlspecialchars($raw_option['option_values']) . "</pre></td>";
    echo "</tr>";
}

echo "</table>";
?>