<?php
/**
 * Debug Worker System
 * 
 * This script helps debug and test the worker management system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<h1>NORDBOOKING Worker System Debug</h1>";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p style='color: red;'>❌ You must be logged in to run this debug script.</p>";
    echo "<p><a href='" . wp_login_url() . "'>Login here</a></p>";
    exit;
}

$current_user = wp_get_current_user();
echo "<h2>Current User Info</h2>";
echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
echo "<p><strong>Email:</strong> " . $current_user->user_email . "</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";

// Check permissions
echo "<h2>Permissions Check</h2>";
if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS)) {
    echo "<p style='color: green;'>✅ User has CAP_MANAGE_WORKERS permission</p>";
} else {
    echo "<p style='color: red;'>❌ User does NOT have CAP_MANAGE_WORKERS permission</p>";
}

// Check if Auth class exists and methods are available
echo "<h2>Class and Method Availability</h2>";
if (class_exists('NORDBOOKING\Classes\Auth')) {
    echo "<p style='color: green;'>✅ Auth class exists</p>";
    
    $auth = new \NORDBOOKING\Classes\Auth();
    $methods = [
        'handle_ajax_send_invitation',
        'handle_ajax_direct_add_staff', 
        'handle_ajax_revoke_worker_access'
    ];
    
    foreach ($methods as $method) {
        if (method_exists($auth, $method)) {
            echo "<p style='color: green;'>✅ Method {$method} exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Method {$method} missing</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Auth class not found</p>";
}

// Check Notifications class
echo "<h2>Notifications Class</h2>";
if (class_exists('NORDBOOKING\Classes\Notifications')) {
    echo "<p style='color: green;'>✅ Notifications class exists</p>";
    
    $notifications = new \NORDBOOKING\Classes\Notifications();
    if (method_exists($notifications, 'send_invitation_email')) {
        echo "<p style='color: green;'>✅ send_invitation_email method exists</p>";
    } else {
        echo "<p style='color: red;'>❌ send_invitation_email method missing</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Notifications class not found</p>";
}

// Check current workers
echo "<h2>Current Workers</h2>";
if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS)) {
    $workers = get_users([
        'meta_key'   => \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID,
        'meta_value' => $current_user->ID,
    ]);
    
    if (!empty($workers)) {
        echo "<p>Found " . count($workers) . " workers:</p>";
        echo "<ul>";
        foreach ($workers as $worker) {
            echo "<li>{$worker->display_name} ({$worker->user_email}) - Roles: " . implode(', ', $worker->roles) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No workers found for current user.</p>";
    }
} else {
    echo "<p>Cannot check workers - insufficient permissions.</p>";
}

// Check AJAX hooks
echo "<h2>AJAX Hooks Registration</h2>";
global $wp_filter;

$hooks_to_check = [
    'wp_ajax_nordbooking_send_invitation',
    'wp_ajax_nordbooking_direct_add_staff',
    'wp_ajax_nordbooking_revoke_worker_access'
];

foreach ($hooks_to_check as $hook) {
    if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks)) {
        echo "<p style='color: green;'>✅ Hook {$hook} is registered</p>";
    } else {
        echo "<p style='color: red;'>❌ Hook {$hook} is NOT registered</p>";
    }
}

// Test email functionality
echo "<h2>Email System Test</h2>";
$test_email = get_option('admin_email');
echo "<p><strong>Admin Email:</strong> {$test_email}</p>";

if (function_exists('wp_mail')) {
    echo "<p style='color: green;'>✅ wp_mail function is available</p>";
    
    // Test sending a simple email (commented out to avoid spam)
    /*
    $test_subject = 'NORDBOOKING Worker System Test';
    $test_message = 'This is a test email from the NORDBOOKING worker system debug script.';
    $sent = wp_mail($test_email, $test_subject, $test_message);
    
    if ($sent) {
        echo "<p style='color: green;'>✅ Test email sent successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Test email failed to send</p>";
    }
    */
    echo "<p><em>Email sending test is commented out to avoid spam. Uncomment in the debug script if needed.</em></p>";
} else {
    echo "<p style='color: red;'>❌ wp_mail function is NOT available</p>";
}

// Check for pending invitations
echo "<h2>Pending Invitations</h2>";
global $wpdb;

$transients = $wpdb->get_results(
    "SELECT option_name, option_value FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_nordbooking_invitation_%'"
);

if (!empty($transients)) {
    echo "<p>Found " . count($transients) . " pending invitations:</p>";
    echo "<ul>";
    foreach ($transients as $transient) {
        $data = maybe_unserialize($transient->option_value);
        if (is_array($data)) {
            $token = str_replace('_transient_nordbooking_invitation_', '', $transient->option_name);
            echo "<li>Email: {$data['worker_email']}, Role: {$data['assigned_role']}, Token: {$token}</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>No pending invitations found.</p>";
}

// Check WordPress roles
echo "<h2>WordPress Roles</h2>";
$wp_roles = wp_roles();
$nordbooking_roles = [
    \NORDBOOKING\Classes\Auth::ROLE_BUSINESS_OWNER => 'Business Owner',
    \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF => 'Worker Staff'
];

foreach ($nordbooking_roles as $role_key => $role_name) {
    if ($wp_roles->is_role($role_key)) {
        echo "<p style='color: green;'>✅ Role '{$role_name}' ({$role_key}) exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Role '{$role_name}' ({$role_key}) missing</p>";
    }
}

// Test form simulation (if user has permissions)
if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS)) {
    echo "<h2>Form Simulation Test</h2>";
    echo "<p>You can test the worker management forms at: <a href='" . home_url('/dashboard/workers/') . "' target='_blank'>Workers Page</a></p>";
    
    echo "<h3>Test Invitation Form</h3>";
    echo "<form method='post' action='" . admin_url('admin-ajax.php') . "' target='_blank'>";
    wp_nonce_field('nordbooking_send_invitation_nonce', 'nordbooking_nonce');
    echo "<input type='hidden' name='action' value='nordbooking_send_invitation'>";
    echo "<p><label>Worker Email: <input type='email' name='worker_email' placeholder='test@example.com' required></label></p>";
    echo "<p><label>Role: <select name='worker_role'><option value='" . \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF . "'>Staff</option></select></label></p>";
    echo "<p><input type='submit' value='Test Send Invitation' style='background: #0073aa; color: white; padding: 8px 16px; border: none; cursor: pointer;'></p>";
    echo "</form>";
    
    echo "<h3>Test Direct Add Form</h3>";
    echo "<form method='post' action='" . admin_url('admin-ajax.php') . "' target='_blank'>";
    wp_nonce_field('nordbooking_direct_add_staff_nonce', 'nordbooking_direct_add_staff_nonce_field');
    echo "<input type='hidden' name='action' value='nordbooking_direct_add_staff'>";
    echo "<p><label>Email: <input type='email' name='direct_add_staff_email' placeholder='worker@example.com' required></label></p>";
    echo "<p><label>Password: <input type='password' name='direct_add_staff_password' placeholder='password123' required></label></p>";
    echo "<p><label>First Name: <input type='text' name='direct_add_staff_first_name' placeholder='John'></label></p>";
    echo "<p><label>Last Name: <input type='text' name='direct_add_staff_last_name' placeholder='Doe'></label></p>";
    echo "<p><input type='submit' value='Test Direct Add Worker' style='background: #0073aa; color: white; padding: 8px 16px; border: none; cursor: pointer;'></p>";
    echo "</form>";
    
    echo "<p><em>Note: These forms will actually create invitations/users if submitted. Use test email addresses.</em></p>";
}

echo "<h2>Debug Complete</h2>";
echo "<p>If you see any ❌ marks above, those indicate issues that need to be addressed for the worker system to function properly.</p>";
?>