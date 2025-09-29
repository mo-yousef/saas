<?php
/**
 * Test Business Settings AJAX Endpoint
 * 
 * This script tests the business settings AJAX functionality
 * to help identify where the issue is occurring.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function test_business_settings_ajax() {
    $results = [
        'success' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    // Check if Settings class exists
    if (!class_exists('NORDBOOKING\Classes\Settings')) {
        $results['errors'][] = 'NORDBOOKING Settings class not found.';
        return $results;
    }
    
    $settings = new \NORDBOOKING\Classes\Settings();
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        $results['errors'][] = 'No user logged in for testing.';
        return $results;
    }
    
    $results['success'][] = "Testing with user ID: $user_id";
    
    // Test 1: Get business settings
    try {
        $current_settings = $settings->get_business_settings($user_id);
        $results['success'][] = "Successfully retrieved " . count($current_settings) . " business settings.";
    } catch (Exception $e) {
        $results['errors'][] = "Failed to get business settings: " . $e->getMessage();
    }
    
    // Test 2: Test individual setting update
    $test_setting_name = 'biz_name';
    $test_value = 'Test Business Name ' . time();
    
    try {
        $update_result = $settings->update_setting($user_id, $test_setting_name, $test_value);
        if ($update_result) {
            $results['success'][] = "Successfully updated test setting: $test_setting_name";
            
            // Verify the update
            $retrieved_value = $settings->get_setting($user_id, $test_setting_name);
            if ($retrieved_value === $test_value) {
                $results['success'][] = "Test setting value verified correctly.";
            } else {
                $results['errors'][] = "Test setting value mismatch. Expected: $test_value, Got: $retrieved_value";
            }
        } else {
            $results['errors'][] = "Failed to update test setting: $test_setting_name";
        }
    } catch (Exception $e) {
        $results['errors'][] = "Exception updating test setting: " . $e->getMessage();
    }
    
    // Test 3: Test save_business_settings method
    $test_settings_data = [
        'biz_name' => 'Test Company ' . time(),
        'biz_email' => 'test@example.com',
        'biz_phone' => '123-456-7890'
    ];
    
    try {
        $save_result = $settings->save_business_settings($user_id, $test_settings_data);
        if ($save_result) {
            $results['success'][] = "Successfully saved business settings batch.";
        } else {
            $results['errors'][] = "Failed to save business settings batch.";
        }
    } catch (Exception $e) {
        $results['errors'][] = "Exception saving business settings: " . $e->getMessage();
    }
    
    // Test 4: Test AJAX handler simulation
    try {
        // Simulate the AJAX request data
        $_POST['settings'] = [
            'biz_name' => 'AJAX Test Company',
            'biz_email' => 'ajax@test.com',
            'first_name' => 'Test',
            'last_name' => 'User'
        ];
        $_POST['nonce'] = wp_create_nonce('nordbooking_dashboard_nonce');
        
        // Capture output
        ob_start();
        
        try {
            $settings->handle_save_business_settings_ajax();
        } catch (Exception $e) {
            $results['errors'][] = "Exception in AJAX handler: " . $e->getMessage();
        }
        
        $ajax_output = ob_get_clean();
        
        if (!empty($ajax_output)) {
            $ajax_response = json_decode($ajax_output, true);
            if ($ajax_response && isset($ajax_response['success'])) {
                if ($ajax_response['success']) {
                    $results['success'][] = "AJAX handler test successful.";
                } else {
                    $results['errors'][] = "AJAX handler returned error: " . ($ajax_response['data']['message'] ?? 'Unknown error');
                }
            } else {
                $results['warnings'][] = "AJAX handler output: " . substr($ajax_output, 0, 200);
            }
        } else {
            $results['warnings'][] = "AJAX handler produced no output.";
        }
        
        // Clean up
        unset($_POST['settings'], $_POST['nonce']);
        
    } catch (Exception $e) {
        $results['errors'][] = "Exception testing AJAX handler: " . $e->getMessage();
    }
    
    return $results;
}

// Handle form submission
$test_results = null;
if (isset($_POST['run_test']) && wp_verify_nonce($_POST['test_nonce'], 'nordbooking_test')) {
    $test_results = test_business_settings_ajax();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>NordBooking Business Settings AJAX Test</title>
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
        <h1>NordBooking Business Settings AJAX Test</h1>
        
        <p>This tool tests the business settings AJAX functionality to identify where the issue is occurring.</p>
        
        <?php if ($test_results): ?>
            <h2>Test Results</h2>
            
            <?php if (!empty($test_results['success'])): ?>
                <h3>Success Messages</h3>
                <?php foreach ($test_results['success'] as $message): ?>
                    <div class="success"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($test_results['warnings'])): ?>
                <h3>Warnings</h3>
                <?php foreach ($test_results['warnings'] as $message): ?>
                    <div class="warning"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($test_results['errors'])): ?>
                <h3>Errors</h3>
                <?php foreach ($test_results['errors'] as $message): ?>
                    <div class="error"><?php echo esc_html($message); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <form method="post" style="margin: 20px 0;">
            <?php wp_nonce_field('nordbooking_test', 'test_nonce'); ?>
            <input type="submit" name="run_test" value="Run AJAX Test" class="button">
        </form>
        
        <h3>Debug Information</h3>
        <p><strong>Current User ID:</strong> <?php echo get_current_user_id(); ?></p>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>MySQL Version:</strong> <?php global $wpdb; echo $wpdb->get_var("SELECT VERSION()"); ?></p>
        
        <p>
            <a href="<?php echo admin_url(); ?>" class="button">Return to WordPress Admin</a>
        </p>
    </div>
</body>
</html>