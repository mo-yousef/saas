<?php
/**
 * AJAX Diagnostic Script
 * 
 * This script helps diagnose AJAX issues
 */

// Get the WordPress root path
$wp_root = dirname(dirname(dirname(__FILE__)));
$wp_config_path = $wp_root . '/wp-config.php';

if (file_exists($wp_config_path)) {
    echo "WordPress root found at: $wp_root\n";
    
    // Include WordPress
    require_once($wp_config_path);
    require_once($wp_root . '/wp-load.php');
    
    echo "WordPress loaded successfully\n";
    
    // Check if AJAX handlers are registered
    global $wp_filter;
    
    $ajax_actions_to_check = [
        'wp_ajax_nopriv_nordbooking_register',
        'wp_ajax_nopriv_nordbooking_test_registration',
        'wp_ajax_nopriv_nordbooking_test_ajax'
    ];
    
    echo "\nChecking AJAX action registrations:\n";
    foreach ($ajax_actions_to_check as $action) {
        if (isset($wp_filter[$action]) && !empty($wp_filter[$action]->callbacks)) {
            echo "✓ $action is registered\n";
            foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $callback_info = '';
                    if (is_array($callback['function'])) {
                        if (is_object($callback['function'][0])) {
                            $callback_info = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                        } else {
                            $callback_info = $callback['function'][0] . '::' . $callback['function'][1];
                        }
                    } else {
                        $callback_info = $callback['function'];
                    }
                    echo "  - Priority $priority: $callback_info\n";
                }
            }
        } else {
            echo "✗ $action is NOT registered\n";
        }
    }
    
    // Test if we can call the handlers directly
    echo "\nTesting direct handler calls:\n";
    
    // Simulate POST data
    $_POST = [
        'action' => 'nordbooking_register',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'TestPassword123!',
        'password_confirm' => 'TestPassword123!',
        'company_name' => 'Test Company'
    ];
    
    // Test Auth class
    if (class_exists('NORDBOOKING\Classes\Auth')) {
        echo "✓ Auth class exists\n";
        
        $auth = new NORDBOOKING\Classes\Auth();
        if (method_exists($auth, 'handle_ajax_registration')) {
            echo "✓ handle_ajax_registration method exists\n";
            
            // Try to call it (this might output JSON)
            echo "Attempting to call registration handler...\n";
            ob_start();
            try {
                $auth->handle_ajax_registration();
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "\n";
            }
            $output = ob_get_clean();
            echo "Handler output: $output\n";
            
        } else {
            echo "✗ handle_ajax_registration method does NOT exist\n";
        }
    } else {
        echo "✗ Auth class does NOT exist\n";
    }
    
} else {
    echo "WordPress config not found at: $wp_config_path\n";
}
?>