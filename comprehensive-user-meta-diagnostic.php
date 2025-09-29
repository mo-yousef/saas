<?php
/**
 * Comprehensive User Meta Diagnostic
 * 
 * This script performs a comprehensive check of user meta functionality
 * to identify why the business settings are failing.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

function comprehensive_user_meta_diagnostic() {
    global $wpdb;
    
    $results = [];
    $user_id = get_current_user_id();
    
    // Basic info
    $results['basic'] = [
        'user_id' => $user_id,
        'is_multisite' => is_multisite(),
        'current_blog_id' => get_current_blog_id(),
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'mysql_version' => $wpdb->get_var("SELECT VERSION()")
    ];
    
    if (!$user_id) {
        $results['error'] = 'No user logged in';
        return $results;
    }
    
    // User info
    $user = get_userdata($user_id);
    $results['user'] = [
        'login' => $user->user_login,
        'email' => $user->user_email,
        'roles' => $user->roles,
        'capabilities' => array_keys($user->allcaps, true)
    ];
    
    // Capability checks
    $results['capabilities'] = [
        'edit_users' => current_user_can('edit_users'),
        'edit_user_self' => current_user_can('edit_user', $user_id),
        'manage_options' => current_user_can('manage_options'),
        'administrator' => current_user_can('administrator')
    ];
    
    // Database table checks
    $results['database'] = [
        'usermeta_table' => $wpdb->usermeta,
        'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->usermeta}'") ? true : false,
        'user_exists_in_users' => $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID = %d", $user_id)) ? true : false
    ];
    
    // Current meta values
    $current_meta = get_user_meta($user_id);
    $results['current_meta'] = [
        'first_name' => $current_meta['first_name'][0] ?? 'Not set',
        'last_name' => $current_meta['last_name'][0] ?? 'Not set',
        'total_meta_count' => count($current_meta)
    ];
    
    // Test direct database operations
    $test_key = 'test_meta_' . time();
    $test_value = 'test_value_' . time();
    
    // Test 1: Direct database insert
    $insert_result = $wpdb->insert(
        $wpdb->usermeta,
        [
            'user_id' => $user_id,
            'meta_key' => $test_key,
            'meta_value' => $test_value
        ],
        ['%d', '%s', '%s']
    );
    
    $results['database_tests']['direct_insert'] = [
        'result' => $insert_result,
        'error' => $wpdb->last_error,
        'insert_id' => $wpdb->insert_id
    ];
    
    // Test 2: WordPress add_user_meta
    $add_result = add_user_meta($user_id, $test_key . '_wp', $test_value);
    $results['database_tests']['wp_add_meta'] = [
        'result' => $add_result,
        'type' => gettype($add_result)
    ];
    
    // Test 3: WordPress update_user_meta (new)
    $update_new_result = update_user_meta($user_id, $test_key . '_update_new', $test_value);
    $results['database_tests']['wp_update_new'] = [
        'result' => $update_new_result,
        'type' => gettype($update_new_result)
    ];
    
    // Test 4: WordPress update_user_meta (existing)
    $update_existing_result = update_user_meta($user_id, $test_key . '_wp', $test_value . '_updated');
    $results['database_tests']['wp_update_existing'] = [
        'result' => $update_existing_result,
        'type' => gettype($update_existing_result)
    ];
    
    // Test 5: Update first_name specifically
    $original_first_name = get_user_meta($user_id, 'first_name', true);
    $new_first_name = 'Diagnostic Test ' . time();
    
    $first_name_result = update_user_meta($user_id, 'first_name', $new_first_name);
    $results['first_name_test'] = [
        'original_value' => $original_first_name,
        'new_value' => $new_first_name,
        'update_result' => $first_name_result,
        'result_type' => gettype($first_name_result),
        'is_false' => $first_name_result === false,
        'is_truthy' => $first_name_result ? true : false,
        'verified_value' => get_user_meta($user_id, 'first_name', true)
    ];
    
    // Test 6: Update last_name specifically
    $original_last_name = get_user_meta($user_id, 'last_name', true);
    $new_last_name = 'Diagnostic Last ' . time();
    
    $last_name_result = update_user_meta($user_id, 'last_name', $new_last_name);
    $results['last_name_test'] = [
        'original_value' => $original_last_name,
        'new_value' => $new_last_name,
        'update_result' => $last_name_result,
        'result_type' => gettype($last_name_result),
        'is_false' => $last_name_result === false,
        'is_truthy' => $last_name_result ? true : false,
        'verified_value' => get_user_meta($user_id, 'last_name', true)
    ];
    
    // Test 7: WordPress hooks and filters
    $results['hooks'] = [
        'has_update_user_meta_filter' => has_filter('update_user_meta'),
        'has_updated_user_meta_action' => has_action('updated_user_meta'),
        'active_plugins' => get_option('active_plugins', [])
    ];
    
    // Clean up test data
    delete_user_meta($user_id, $test_key);
    delete_user_meta($user_id, $test_key . '_wp');
    delete_user_meta($user_id, $test_key . '_update_new');
    
    // Restore original values
    if ($original_first_name !== '') {
        update_user_meta($user_id, 'first_name', $original_first_name);
    }
    if ($original_last_name !== '') {
        update_user_meta($user_id, 'last_name', $original_last_name);
    }
    
    return $results;
}

// Handle the test
$diagnostic_results = null;
if (isset($_POST['run_diagnostic']) && wp_verify_nonce($_POST['diagnostic_nonce'], 'comprehensive_diagnostic')) {
    $diagnostic_results = comprehensive_user_meta_diagnostic();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive User Meta Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #e8f5e8; }
        .error { background: #ffebee; }
        .warning { background: #fff8e1; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Comprehensive User Meta Diagnostic</h1>
        
        <?php if ($diagnostic_results): ?>
            <h2>Diagnostic Results</h2>
            
            <div class="section">
                <h3>Basic Information</h3>
                <table>
                    <?php foreach ($diagnostic_results['basic'] as $key => $value): ?>
                        <tr>
                            <th><?php echo esc_html($key); ?></th>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <?php if (isset($diagnostic_results['error'])): ?>
                <div class="section error">
                    <h3>Error</h3>
                    <p><?php echo esc_html($diagnostic_results['error']); ?></p>
                </div>
            <?php else: ?>
                
                <div class="section">
                    <h3>User Information</h3>
                    <table>
                        <tr><th>Login</th><td><?php echo esc_html($diagnostic_results['user']['login']); ?></td></tr>
                        <tr><th>Email</th><td><?php echo esc_html($diagnostic_results['user']['email']); ?></td></tr>
                        <tr><th>Roles</th><td><?php echo esc_html(implode(', ', $diagnostic_results['user']['roles'])); ?></td></tr>
                    </table>
                </div>
                
                <div class="section <?php echo $diagnostic_results['capabilities']['edit_users'] ? 'success' : 'warning'; ?>">
                    <h3>Capabilities</h3>
                    <table>
                        <?php foreach ($diagnostic_results['capabilities'] as $cap => $has_cap): ?>
                            <tr>
                                <th><?php echo esc_html($cap); ?></th>
                                <td><?php echo $has_cap ? 'YES' : 'NO'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <div class="section <?php echo $diagnostic_results['database']['table_exists'] ? 'success' : 'error'; ?>">
                    <h3>Database</h3>
                    <table>
                        <?php foreach ($diagnostic_results['database'] as $key => $value): ?>
                            <tr>
                                <th><?php echo esc_html($key); ?></th>
                                <td><?php echo is_bool($value) ? ($value ? 'YES' : 'NO') : esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <div class="section">
                    <h3>Current Meta Values</h3>
                    <table>
                        <?php foreach ($diagnostic_results['current_meta'] as $key => $value): ?>
                            <tr>
                                <th><?php echo esc_html($key); ?></th>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <div class="section">
                    <h3>Database Tests</h3>
                    <?php foreach ($diagnostic_results['database_tests'] as $test_name => $test_result): ?>
                        <h4><?php echo esc_html($test_name); ?></h4>
                        <pre><?php echo esc_html(print_r($test_result, true)); ?></pre>
                    <?php endforeach; ?>
                </div>
                
                <div class="section <?php echo $diagnostic_results['first_name_test']['is_false'] ? 'error' : 'success'; ?>">
                    <h3>First Name Test</h3>
                    <pre><?php echo esc_html(print_r($diagnostic_results['first_name_test'], true)); ?></pre>
                </div>
                
                <div class="section <?php echo $diagnostic_results['last_name_test']['is_false'] ? 'error' : 'success'; ?>">
                    <h3>Last Name Test</h3>
                    <pre><?php echo esc_html(print_r($diagnostic_results['last_name_test'], true)); ?></pre>
                </div>
                
                <div class="section">
                    <h3>WordPress Hooks</h3>
                    <pre><?php echo esc_html(print_r($diagnostic_results['hooks'], true)); ?></pre>
                </div>
                
            <?php endif; ?>
            
        <?php endif; ?>
        
        <form method="post" style="margin: 20px 0;">
            <?php wp_nonce_field('comprehensive_diagnostic', 'diagnostic_nonce'); ?>
            <input type="submit" name="run_diagnostic" value="Run Comprehensive Diagnostic" 
                   style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        </form>
        
        <p>
            <a href="<?php echo admin_url(); ?>">Return to WordPress Admin</a> |
            <a href="/test-direct-ajax.php">Test Direct AJAX</a> |
            <a href="/debug-user-meta-issue.php">Debug User Meta</a>
        </p>
    </div>
</body>
</html>