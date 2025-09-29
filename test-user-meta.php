<?php
/**
 * Test User Meta Update Functionality
 * 
 * This script tests if user meta updates are working correctly
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function test_user_meta_updates() {
    $results = [
        'success' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        $results['errors'][] = 'No user logged in for testing.';
        return $results;
    }
    
    $results['success'][] = "Testing with user ID: $user_id";
    
    // Test 1: Update first_name
    $test_first_name = 'Test First ' . time();
    $update_result = update_user_meta($user_id, 'first_name', $test_first_name);
    
    if ($update_result === false) {
        $results['errors'][] = "Failed to update first_name. Result: " . var_export($update_result, true);
    } else {
        $results['success'][] = "Successfully updated first_name. Result: " . var_export($update_result, true);
        
        // Verify the update
        $retrieved_value = get_user_meta($user_id, 'first_name', true);
        if ($retrieved_value === $test_first_name) {
            $results['success'][] = "first_name value verified correctly.";
        } else {
            $results['errors'][] = "first_name value mismatch. Expected: $test_first_name, Got: $retrieved_value";
        }
    }
    
    // Test 2: Update last_name
    $test_last_name = 'Test Last ' . time();
    $update_result = update_user_meta($user_id, 'last_name', $test_last_name);
    
    if ($update_result === false) {
        $results['errors'][] = "Failed to update last_name. Result: " . var_export($update_result, true);
    } else {
        $results['success'][] = "Successfully updated last_name. Result: " . var_export($update_result, true);
        
        // Verify the update
        $retrieved_value = get_user_meta($user_id, 'last_name', true);
        if ($retrieved_value === $test_last_name) {
            $results['success'][] = "last_name value verified correctly.";
        } else {
            $results['errors'][] = "last_name value mismatch. Expected: $test_last_name, Got: $retrieved_value";
        }
    }
    
    // Test 3: Update with same value (should return true)
    $same_value_result = update_user_meta($user_id, 'first_name', $test_first_name);
    if ($same_value_result === true) {
        $results['success'][] = "Updating with same value returned true as expected.";
    } else {
        $results['warnings'][] = "Updating with same value returned: " . var_export($same_value_result, true);
    }
    
    // Test 4: Check user capabilities
    $user = get_userdata($user_id);
    $results['success'][] = "User roles: " . implode(', ', $user->roles);
    $results['success'][] = "Can edit_users: " . (current_user_can('edit_users') ? 'YES' : 'NO');
    $results['success'][] = "Can edit_user (self): " . (current_user_can('edit_user', $user_id) ? 'YES' : 'NO');
    
    // Test 5: Check database directly
    global $wpdb;
    $meta_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key IN ('first_name', 'last_name')",
        $user_id
    ));
    $results['success'][] = "User has $meta_count name-related meta entries in database.";
    
    return $results;
}

// Handle form submission
$test_results = null;
if (isset($_POST['run_test']) && wp_verify_nonce($_POST['test_nonce'], 'nordbooking_user_meta_test')) {
    $test_results = test_user_meta_updates();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Meta Update Test</title>
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
        <h1>User Meta Update Test</h1>
        
        <p>This tool tests if user meta updates (first_name, last_name) are working correctly.</p>
        
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
            <?php wp_nonce_field('nordbooking_user_meta_test', 'test_nonce'); ?>
            <input type="submit" name="run_test" value="Run User Meta Test" class="button">
        </form>
        
        <h3>Current User Information</h3>
        <?php 
        $current_user = wp_get_current_user();
        $user_meta = get_user_meta($current_user->ID);
        ?>
        <p><strong>User ID:</strong> <?php echo $current_user->ID; ?></p>
        <p><strong>Username:</strong> <?php echo $current_user->user_login; ?></p>
        <p><strong>Email:</strong> <?php echo $current_user->user_email; ?></p>
        <p><strong>Current first_name:</strong> <?php echo esc_html($user_meta['first_name'][0] ?? 'Not set'); ?></p>
        <p><strong>Current last_name:</strong> <?php echo esc_html($user_meta['last_name'][0] ?? 'Not set'); ?></p>
        
        <p>
            <a href="<?php echo admin_url(); ?>" class="button">Return to WordPress Admin</a>
        </p>
    </div>
</body>
</html>