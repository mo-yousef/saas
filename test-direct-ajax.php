<?php
/**
 * Test Direct AJAX Call
 * 
 * This script makes a direct AJAX call to test the business settings
 * without going through the form validation.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function test_direct_ajax_call() {
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        return ['error' => 'No user logged in'];
    }
    
    // Simulate the AJAX request
    $_POST['action'] = 'nordbooking_save_business_settings';
    $_POST['nonce'] = wp_create_nonce('nordbooking_dashboard_nonce');
    $_POST['settings'] = [
        'first_name' => 'Test First Direct',
        'last_name' => 'Test Last Direct',
        'biz_name' => 'Test Business Direct',
        'biz_email' => 'test@direct.com'
    ];
    
    // Capture the output
    ob_start();
    
    try {
        // Create Settings instance and call the handler
        $settings = new \NORDBOOKING\Classes\Settings();
        $settings->handle_save_business_settings_ajax();
    } catch (Exception $e) {
        ob_end_clean();
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
    
    $output = ob_get_clean();
    
    // Clean up
    unset($_POST['action'], $_POST['nonce'], $_POST['settings']);
    
    return ['output' => $output];
}

// Handle the test
$test_result = null;
if (isset($_POST['run_test']) && wp_verify_nonce($_POST['test_nonce'], 'direct_ajax_test')) {
    $test_result = test_direct_ajax_call();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Direct AJAX Call</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; }
        .output { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #ffebee; color: #c62828; }
        .success { background: #e8f5e8; color: #2e7d32; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Direct AJAX Call</h1>
        
        <p>This test makes a direct call to the AJAX handler to bypass any form issues.</p>
        
        <?php if ($test_result): ?>
            <h2>Test Results</h2>
            
            <?php if (isset($test_result['error'])): ?>
                <div class="output error">
                    <strong>Error:</strong> <?php echo esc_html($test_result['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($test_result['output'])): ?>
                <div class="output">
                    <strong>AJAX Output:</strong>
                    <pre><?php echo esc_html($test_result['output']); ?></pre>
                </div>
                
                <?php 
                $json_response = json_decode($test_result['output'], true);
                if ($json_response): ?>
                    <div class="output <?php echo $json_response['success'] ? 'success' : 'error'; ?>">
                        <strong>Parsed Response:</strong><br>
                        Success: <?php echo $json_response['success'] ? 'YES' : 'NO'; ?><br>
                        <?php if (isset($json_response['data']['message'])): ?>
                            Message: <?php echo esc_html($json_response['data']['message']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="post" style="margin: 20px 0;">
            <?php wp_nonce_field('direct_ajax_test', 'test_nonce'); ?>
            <input type="submit" name="run_test" value="Run Direct AJAX Test" 
                   style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        </form>
        
        <h3>Current User Info</h3>
        <p><strong>User ID:</strong> <?php echo get_current_user_id(); ?></p>
        <p><strong>Current first_name:</strong> <?php echo esc_html(get_user_meta(get_current_user_id(), 'first_name', true)); ?></p>
        <p><strong>Current last_name:</strong> <?php echo esc_html(get_user_meta(get_current_user_id(), 'last_name', true)); ?></p>
        
        <p>
            <a href="<?php echo admin_url(); ?>">Return to WordPress Admin</a> |
            <a href="/debug-user-meta-issue.php">Run User Meta Debug</a>
        </p>
    </div>
</body>
</html>