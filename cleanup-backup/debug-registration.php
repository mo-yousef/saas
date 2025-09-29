<?php
/**
 * Debug Registration Process
 * 
 * Simple test to debug registration issues
 */

// Include WordPress
require_once('wp-config.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

// Set JSON header
header('Content-Type: application/json');

try {
    // Test basic class loading
    $classes_status = [
        'Auth' => class_exists('NORDBOOKING\Classes\Auth'),
        'Settings' => class_exists('NORDBOOKING\Classes\Settings'),
        'Database' => class_exists('NORDBOOKING\Classes\Database'),
        'BookingFormRouter' => class_exists('NORDBOOKING\Classes\Routes\BookingFormRouter'),
        'Notifications' => class_exists('NORDBOOKING\Classes\Notifications')
    ];
    
    // Test database table access
    $database_status = [];
    if (class_exists('NORDBOOKING\Classes\Database')) {
        try {
            $settings_table = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
            $database_status['settings_table'] = $settings_table;
            $database_status['settings_table_exists'] = !empty($settings_table);
        } catch (Exception $e) {
            $database_status['settings_table_error'] = $e->getMessage();
        }
    }
    
    // Test settings manager
    $settings_status = [];
    if (class_exists('NORDBOOKING\Classes\Settings')) {
        try {
            $settings_manager = new \NORDBOOKING\Classes\Settings();
            $settings_status['instantiated'] = true;
            
            // Test getting a setting for user 1 (admin)
            $test_setting = $settings_manager->get_setting(1, 'biz_name', 'default');
            $settings_status['test_get_setting'] = $test_setting;
        } catch (Exception $e) {
            $settings_status['error'] = $e->getMessage();
        }
    }
    
    $response = [
        'success' => true,
        'classes' => $classes_status,
        'database' => $database_status,
        'settings' => $settings_status,
        'wordpress_loaded' => defined('ABSPATH'),
        'current_user' => get_current_user_id()
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    echo json_encode($error_response, JSON_PRETTY_PRINT);
}
?>