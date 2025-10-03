<?php
/**
 * Test Demo Services Creation
 * 
 * This file can be used to test if demo services are being created properly.
 * Access via: /test-demo-services.php
 */

// Load WordPress
require_once __DIR__ . '/wp-config.php';

// Check if user is logged in and is a business owner
if (!is_user_logged_in()) {
    wp_die('You must be logged in to test demo services.');
}

$current_user = wp_get_current_user();
if (!current_user_can('nordbooking_business_owner')) {
    wp_die('You must be a business owner to test demo services.');
}

$user_id = $current_user->ID;

echo '<h1>Demo Services Test</h1>';
echo '<p>Testing demo services for user ID: ' . $user_id . '</p>';

// Check if demo services were already created
$demo_services_created = get_user_meta($user_id, 'demo_services_created', true);
echo '<p>Demo services created flag: ' . ($demo_services_created ? 'Yes' : 'No') . '</p>';

// Get current services
if (class_exists('NORDBOOKING\Classes\Services')) {
    $services_manager = new \NORDBOOKING\Classes\Services();
    $services_result = $services_manager->get_services($user_id);
    
    if (!is_wp_error($services_result)) {
        $services = $services_result['services'] ?? [];
        echo '<h2>Current Services (' . count($services) . '):</h2>';
        
        if (empty($services)) {
            echo '<p>No services found.</p>';
        } else {
            echo '<ul>';
            foreach ($services as $service) {
                echo '<li>' . esc_html($service['name']) . ' - $' . esc_html($service['price']) . '</li>';
            }
            echo '</ul>';
        }
    } else {
        echo '<p>Error getting services: ' . $services_result->get_error_message() . '</p>';
    }
} else {
    echo '<p>Services class not found.</p>';
}

// Option to reset and recreate demo services
if (isset($_POST['reset_demo_services'])) {
    delete_user_meta($user_id, 'demo_services_created');
    
    // Delete existing demo services
    if (class_exists('NORDBOOKING\Classes\Services')) {
        $services_manager = new \NORDBOOKING\Classes\Services();
        $services_result = $services_manager->get_services($user_id);
        
        if (!is_wp_error($services_result)) {
            $services = $services_result['services'] ?? [];
            foreach ($services as $service) {
                if (strpos($service['name'], 'Demo -') === 0) {
                    $services_manager->delete_service($service['service_id'], $user_id);
                }
            }
        }
    }
    
    // Recreate demo services
    if (class_exists('NORDBOOKING\Classes\Settings')) {
        \NORDBOOKING\Classes\Settings::initialize_default_settings($user_id);
        echo '<p style="color: green;">Demo services reset and recreated!</p>';
        echo '<script>setTimeout(function(){ location.reload(); }, 1000);</script>';
    }
}

?>

<form method="post" style="margin-top: 20px;">
    <button type="submit" name="reset_demo_services" value="1" 
            onclick="return confirm('This will delete existing demo services and recreate them. Continue?')"
            style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        Reset & Recreate Demo Services
    </button>
</form>

<p><a href="/dashboard/services/">← Back to Services Dashboard</a></p>