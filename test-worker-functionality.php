<?php
/**
 * Test Worker Management Functionality
 * 
 * This file tests both the "Invite New Worker via Email" and "Add Worker Directly" features
 * to ensure they are working correctly.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Ensure we have the necessary classes
if (!class_exists('NORDBOOKING\Classes\Auth')) {
    echo "Error: NORDBOOKING Auth class not found. Please ensure the plugin is active.\n";
    exit;
}

echo "<h1>NORDBOOKING Worker Management Functionality Test</h1>\n";

// Test 1: Check if AJAX handlers are registered
echo "<h2>Test 1: AJAX Handler Registration</h2>\n";

$auth = new \NORDBOOKING\Classes\Auth();
$reflection = new ReflectionClass($auth);

$required_methods = [
    'handle_ajax_send_invitation',
    'handle_ajax_direct_add_staff',
    'handle_ajax_revoke_worker_access',
    'handle_ajax_registration'
];

foreach ($required_methods as $method) {
    if ($reflection->hasMethod($method)) {
        echo "✅ Method {$method} exists<br>\n";
    } else {
        echo "❌ Method {$method} missing<br>\n";
    }
}

// Test 2: Check if Notifications class has required methods
echo "<h2>Test 2: Notifications Class</h2>\n";

if (class_exists('NORDBOOKING\Classes\Notifications')) {
    $notifications = new \NORDBOOKING\Classes\Notifications();
    $notif_reflection = new ReflectionClass($notifications);
    
    if ($notif_reflection->hasMethod('send_invitation_email')) {
        echo "✅ Notifications::send_invitation_email exists<br>\n";
    } else {
        echo "❌ Notifications::send_invitation_email missing<br>\n";
    }
} else {
    echo "❌ Notifications class not found<br>\n";
}

// Test 3: Check WordPress hooks
echo "<h2>Test 3: WordPress AJAX Hooks</h2>\n";

global $wp_filter;

$ajax_actions = [
    'wp_ajax_nordbooking_send_invitation',
    'wp_ajax_nordbooking_direct_add_staff',
    'wp_ajax_nordbooking_revoke_worker_access',
    'wp_ajax_nopriv_nordbooking_register'
];

foreach ($ajax_actions as $action) {
    if (isset($wp_filter[$action])) {
        echo "✅ Hook {$action} is registered<br>\n";
    } else {
        echo "❌ Hook {$action} not registered<br>\n";
    }
}

// Test 4: Check if worker page exists
echo "<h2>Test 4: Worker Page</h2>\n";

$worker_page_path = get_template_directory() . '/dashboard/page-workers.php';
if (file_exists($worker_page_path)) {
    echo "✅ Worker page exists at: {$worker_page_path}<br>\n";
} else {
    echo "❌ Worker page not found<br>\n";
}

// Test 5: Check JavaScript files
echo "<h2>Test 5: JavaScript Assets</h2>\n";

$js_path = get_template_directory() . '/assets/js/dashboard-workers.js';
if (file_exists($js_path)) {
    echo "✅ Worker JavaScript exists<br>\n";
} else {
    echo "❌ Worker JavaScript not found<br>\n";
}

// Test 6: Test email functionality (basic check)
echo "<h2>Test 6: Email Configuration</h2>\n";

$admin_email = get_option('admin_email');
echo "Site admin email: {$admin_email}<br>\n";

if (function_exists('wp_mail')) {
    echo "✅ wp_mail function available<br>\n";
} else {
    echo "❌ wp_mail function not available<br>\n";
}

// Test 7: Check user capabilities
echo "<h2>Test 7: User Capabilities</h2>\n";

if (defined('NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS')) {
    echo "✅ CAP_MANAGE_WORKERS constant defined<br>\n";
} else {
    echo "❌ CAP_MANAGE_WORKERS constant not defined<br>\n";
}

// Test 8: Database tables (if any specific worker tables exist)
echo "<h2>Test 8: Database Structure</h2>\n";

global $wpdb;

// Check if users table has the necessary meta fields
$meta_check = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s",
    \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID
));

echo "Users with owner_id meta: {$meta_check}<br>\n";

// Test 9: Role definitions
echo "<h2>Test 9: User Roles</h2>\n";

$wp_roles = wp_roles();
$nordbooking_roles = [
    \NORDBOOKING\Classes\Auth::ROLE_BUSINESS_OWNER,
    \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF
];

foreach ($nordbooking_roles as $role) {
    if ($wp_roles->is_role($role)) {
        echo "✅ Role {$role} exists<br>\n";
    } else {
        echo "❌ Role {$role} not found<br>\n";
    }
}

echo "<h2>Test Complete</h2>\n";
echo "<p>If you see any ❌ marks above, those indicate issues that need to be addressed.</p>\n";

// Test 10: Simulate AJAX request (if user is logged in and has permissions)
if (is_user_logged_in() && current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS)) {
    echo "<h2>Test 10: Live AJAX Test</h2>\n";
    echo "<p>You are logged in with worker management permissions. You can test the actual functionality on the <a href='" . home_url('/dashboard/workers/') . "'>Workers page</a>.</p>\n";
} else {
    echo "<h2>Test 10: Live AJAX Test</h2>\n";
    echo "<p>❌ Not logged in or insufficient permissions to test live functionality.</p>\n";
}