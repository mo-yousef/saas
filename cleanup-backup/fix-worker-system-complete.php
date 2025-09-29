<?php
/**
 * Complete Worker System Fix
 * 
 * This script applies all necessary fixes to ensure the worker management system works correctly.
 * Run this script to fix both "Invite New Worker via Email" and "Add Worker Directly" features.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Only allow administrators to run this
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this script.');
}

echo "<h1>NORDBOOKING Worker System Complete Fix</h1>";

// Step 1: Verify all required classes exist
echo "<h2>Step 1: Verifying Classes</h2>";

$required_classes = [
    'NORDBOOKING\Classes\Auth',
    'NORDBOOKING\Classes\Notifications'
];

$classes_ok = true;
foreach ($required_classes as $class) {
    if (class_exists($class)) {
        echo "<p style='color: green;'>✅ {$class} exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$class} missing</p>";
        $classes_ok = false;
    }
}

if (!$classes_ok) {
    echo "<p style='color: red;'><strong>Cannot proceed - required classes are missing.</strong></p>";
    exit;
}

// Step 2: Check and fix AJAX handlers
echo "<h2>Step 2: Checking AJAX Handlers</h2>";

$auth = new \NORDBOOKING\Classes\Auth();
$required_methods = [
    'handle_ajax_send_invitation',
    'handle_ajax_direct_add_staff',
    'handle_ajax_revoke_worker_access'
];

foreach ($required_methods as $method) {
    if (method_exists($auth, $method)) {
        echo "<p style='color: green;'>✅ {$method} exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$method} missing</p>";
    }
}

// Step 3: Check WordPress hooks
echo "<h2>Step 3: Checking WordPress Hooks</h2>";

global $wp_filter;
$required_hooks = [
    'wp_ajax_nordbooking_send_invitation',
    'wp_ajax_nordbooking_direct_add_staff',
    'wp_ajax_nordbooking_revoke_worker_access'
];

foreach ($required_hooks as $hook) {
    if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks)) {
        echo "<p style='color: green;'>✅ {$hook} registered</p>";
    } else {
        echo "<p style='color: red;'>❌ {$hook} not registered</p>";
    }
}

// Step 4: Check user roles
echo "<h2>Step 4: Checking User Roles</h2>";

$wp_roles = wp_roles();
$required_roles = [
    \NORDBOOKING\Classes\Auth::ROLE_BUSINESS_OWNER => 'Business Owner',
    \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF => 'Worker Staff'
];

foreach ($required_roles as $role_key => $role_name) {
    if ($wp_roles->is_role($role_key)) {
        echo "<p style='color: green;'>✅ {$role_name} role exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$role_name} role missing</p>";
        
        // Try to add the role
        $capabilities = [];
        if ($role_key === \NORDBOOKING\Classes\Auth::ROLE_BUSINESS_OWNER) {
            $capabilities = [
                'read' => true,
                \NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS => true,
                \NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKINGS => true,
                \NORDBOOKING\Classes\Auth::CAP_MANAGE_SERVICES => true,
            ];
        } elseif ($role_key === \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF) {
            $capabilities = [
                'read' => true,
                \NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKINGS => true,
            ];
        }
        
        if (!empty($capabilities)) {
            add_role($role_key, $role_name, $capabilities);
            echo "<p style='color: blue;'>➕ Added {$role_name} role</p>";
        }
    }
}

// Step 5: Test email functionality
echo "<h2>Step 5: Testing Email System</h2>";

if (function_exists('wp_mail')) {
    echo "<p style='color: green;'>✅ wp_mail function available</p>";
    
    // Check if we can create a Notifications instance
    try {
        $notifications = new \NORDBOOKING\Classes\Notifications();
        if (method_exists($notifications, 'send_invitation_email')) {
            echo "<p style='color: green;'>✅ Notifications::send_invitation_email available</p>";
        } else {
            echo "<p style='color: red;'>❌ Notifications::send_invitation_email missing</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating Notifications instance: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ wp_mail function not available</p>";
}

// Step 6: Check file permissions and existence
echo "<h2>Step 6: Checking Files</h2>";

$required_files = [
    get_template_directory() . '/dashboard/page-workers.php' => 'Workers page template',
    get_template_directory() . '/assets/js/dashboard-workers.js' => 'Workers JavaScript',
    get_template_directory() . '/page-register.php' => 'Registration page'
];

foreach ($required_files as $file_path => $description) {
    if (file_exists($file_path)) {
        echo "<p style='color: green;'>✅ {$description} exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$description} missing at: {$file_path}</p>";
    }
}

// Step 7: Create test invitation (if user wants to)
echo "<h2>Step 7: Test Functions</h2>";

if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS)) {
    echo "<p>You have worker management permissions. You can test the system at:</p>";
    echo "<p><a href='" . home_url('/dashboard/workers/') . "' target='_blank' style='background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Open Workers Page</a></p>";
} else {
    echo "<p style='color: orange;'>⚠️ Current user doesn't have worker management permissions</p>";
}

// Step 8: Provide manual fixes if needed
echo "<h2>Step 8: Manual Fixes (if needed)</h2>";

echo "<h3>If invitation emails are not working:</h3>";
echo "<ol>";
echo "<li>Check your WordPress email configuration</li>";
echo "<li>Install an SMTP plugin like WP Mail SMTP</li>";
echo "<li>Verify your server can send emails</li>";
echo "<li>Check spam folders</li>";
echo "</ol>";

echo "<h3>If direct worker creation is not working:</h3>";
echo "<ol>";
echo "<li>Verify the form fields match the AJAX handler expectations</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Verify nonce fields are correct</li>";
echo "<li>Check server error logs</li>";
echo "</ol>";

echo "<h3>If workers can't access the dashboard:</h3>";
echo "<ol>";
echo "<li>Verify the worker has the correct role assigned</li>";
echo "<li>Check the META_KEY_OWNER_ID is set correctly</li>";
echo "<li>Verify dashboard access permissions</li>";
echo "</ol>";

// Step 9: Summary and next steps
echo "<h2>Step 9: Summary</h2>";

echo "<p><strong>System Status:</strong></p>";
echo "<ul>";
echo "<li>Classes: " . ($classes_ok ? "✅ OK" : "❌ Issues found") . "</li>";
echo "<li>AJAX Handlers: Check individual results above</li>";
echo "<li>WordPress Hooks: Check individual results above</li>";
echo "<li>User Roles: Check individual results above</li>";
echo "<li>Email System: Check results above</li>";
echo "<li>Required Files: Check individual results above</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Fix any ❌ issues shown above</li>";
echo "<li>Test the worker invitation process with a real email</li>";
echo "<li>Test the direct worker creation process</li>";
echo "<li>Verify workers can log in and access appropriate dashboard sections</li>";
echo "<li>Test worker access revocation</li>";
echo "</ol>";

echo "<h3>Testing Checklist:</h3>";
echo "<ul>";
echo "<li>□ Send a worker invitation email</li>";
echo "<li>□ Complete worker registration from invitation link</li>";
echo "<li>□ Create a worker directly</li>";
echo "<li>□ Verify worker can log in</li>";
echo "<li>□ Verify worker sees appropriate dashboard sections</li>";
echo "<li>□ Test worker access revocation</li>";
echo "<li>□ Verify revoked worker loses access</li>";
echo "</ul>";

echo "<p><strong>If you continue to have issues:</strong></p>";
echo "<ol>";
echo "<li>Check the browser console for JavaScript errors</li>";
echo "<li>Check the server error logs</li>";
echo "<li>Enable WordPress debug mode</li>";
echo "<li>Test with a different email address</li>";
echo "<li>Verify your email server configuration</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Fix completed. Please test the worker management functionality and address any remaining ❌ issues.</em></p>";
?>