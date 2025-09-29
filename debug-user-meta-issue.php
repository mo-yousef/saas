<?php
/**
 * Debug User Meta Issue
 * 
 * This script specifically tests the user meta update issue
 * that's causing the business settings to fail.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function debug_user_meta_issue() {
    $user_id = get_current_user_id();
    
    echo "<h2>Debug User Meta Issue</h2>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    
    if (!$user_id) {
        echo "<p style='color: red;'>ERROR: No user logged in!</p>";
        return;
    }
    
    // Test 1: Direct update_user_meta calls
    echo "<h3>Test 1: Direct update_user_meta calls</h3>";
    
    $test_first_name = 'Debug First ' . time();
    $test_last_name = 'Debug Last ' . time();
    
    echo "<p>Attempting to update first_name to: <strong>$test_first_name</strong></p>";
    $result1 = update_user_meta($user_id, 'first_name', $test_first_name);
    echo "<p>Result: " . var_export($result1, true) . "</p>";
    echo "<p>Type: " . gettype($result1) . "</p>";
    echo "<p>Is false: " . ($result1 === false ? 'YES' : 'NO') . "</p>";
    echo "<p>Is truthy: " . ($result1 ? 'YES' : 'NO') . "</p>";
    
    echo "<p>Attempting to update last_name to: <strong>$test_last_name</strong></p>";
    $result2 = update_user_meta($user_id, 'last_name', $test_last_name);
    echo "<p>Result: " . var_export($result2, true) . "</p>";
    echo "<p>Type: " . gettype($result2) . "</p>";
    echo "<p>Is false: " . ($result2 === false ? 'YES' : 'NO') . "</p>";
    echo "<p>Is truthy: " . ($result2 ? 'YES' : 'NO') . "</p>";
    
    // Test 2: Verify the values were saved
    echo "<h3>Test 2: Verify saved values</h3>";
    $saved_first = get_user_meta($user_id, 'first_name', true);
    $saved_last = get_user_meta($user_id, 'last_name', true);
    
    echo "<p>Saved first_name: <strong>$saved_first</strong></p>";
    echo "<p>Saved last_name: <strong>$saved_last</strong></p>";
    echo "<p>First name matches: " . ($saved_first === $test_first_name ? 'YES' : 'NO') . "</p>";
    echo "<p>Last name matches: " . ($saved_last === $test_last_name ? 'YES' : 'NO') . "</p>";
    
    // Test 3: Test the exact logic from Settings.php
    echo "<h3>Test 3: Simulate Settings.php logic</h3>";
    
    $personal_details_updated = true;
    $personal_details_errors = [];
    
    // Test first_name
    $update_result = update_user_meta($user_id, 'first_name', $test_first_name);
    if ($update_result === false) {
        $personal_details_updated = false;
        $personal_details_errors[] = 'first_name';
        echo "<p style='color: red;'>FAILED: first_name update returned false</p>";
    } else {
        echo "<p style='color: green;'>SUCCESS: first_name updated (result: " . var_export($update_result, true) . ")</p>";
    }
    
    // Test last_name
    $update_result = update_user_meta($user_id, 'last_name', $test_last_name);
    if ($update_result === false) {
        $personal_details_updated = false;
        $personal_details_errors[] = 'last_name';
        echo "<p style='color: red;'>FAILED: last_name update returned false</p>";
    } else {
        echo "<p style='color: green;'>SUCCESS: last_name updated (result: " . var_export($update_result, true) . ")</p>";
    }
    
    echo "<p><strong>Final result:</strong> " . ($personal_details_updated ? 'SUCCESS' : 'FAILED') . "</p>";
    if (!empty($personal_details_errors)) {
        echo "<p><strong>Errors:</strong> " . implode(', ', $personal_details_errors) . "</p>";
    }
    
    // Test 4: Check user capabilities
    echo "<h3>Test 4: User capabilities</h3>";
    $user = get_userdata($user_id);
    echo "<p><strong>User roles:</strong> " . implode(', ', $user->roles) . "</p>";
    echo "<p><strong>Can edit_users:</strong> " . (current_user_can('edit_users') ? 'YES' : 'NO') . "</p>";
    echo "<p><strong>Can edit_user (self):</strong> " . (current_user_can('edit_user', $user_id) ? 'YES' : 'NO') . "</p>";
    
    // Test 5: Database check
    echo "<h3>Test 5: Database check</h3>";
    global $wpdb;
    
    $meta_entries = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key IN ('first_name', 'last_name')",
        $user_id
    ));
    
    echo "<p><strong>Database entries:</strong></p>";
    foreach ($meta_entries as $entry) {
        echo "<p>- {$entry->meta_key}: {$entry->meta_value}</p>";
    }
    
    // Test 6: WordPress database errors
    echo "<h3>Test 6: WordPress database errors</h3>";
    if ($wpdb->last_error) {
        echo "<p style='color: red;'><strong>Database error:</strong> " . $wpdb->last_error . "</p>";
    } else {
        echo "<p style='color: green;'>No database errors detected</p>";
    }
    
    // Test 7: Test with same value (should return true)
    echo "<h3>Test 7: Update with same value</h3>";
    $same_result = update_user_meta($user_id, 'first_name', $test_first_name);
    echo "<p>Updating first_name with same value: " . var_export($same_result, true) . "</p>";
    echo "<p>Type: " . gettype($same_result) . "</p>";
    echo "<p>Is exactly true: " . ($same_result === true ? 'YES' : 'NO') . "</p>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug User Meta Issue</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug User Meta Issue</h1>
        
        <?php debug_user_meta_issue(); ?>
        
        <p>
            <a href="<?php echo admin_url(); ?>">Return to WordPress Admin</a>
        </p>
    </div>
</body>
</html>