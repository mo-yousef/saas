<?php
/**
 * Debug Worker Booking Access
 * 
 * This script helps debug why workers can't access their assigned bookings.
 * Run this script while logged in as a worker to see what's happening.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Only allow logged-in users
if (!is_user_logged_in()) {
    wp_die('You must be logged in to run this debug script.');
}

$current_user_id = get_current_user_id();
$user = get_userdata($current_user_id);

echo "<h1>Worker Booking Access Debug</h1>";

echo "<h2>Current User Information</h2>";
echo "<p><strong>User ID:</strong> {$current_user_id}</p>";
echo "<p><strong>Username:</strong> {$user->user_login}</p>";
echo "<p><strong>Email:</strong> {$user->user_email}</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $user->roles) . "</p>";

// Check if user is a worker
$is_worker = \NORDBOOKING\Classes\Auth::is_user_worker($current_user_id);
echo "<p><strong>Is Worker:</strong> " . ($is_worker ? 'Yes' : 'No') . "</p>";

if ($is_worker) {
    $business_owner_id = \NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
    echo "<p><strong>Business Owner ID:</strong> " . ($business_owner_id ?? 'null') . "</p>";
    
    if ($business_owner_id) {
        $owner_user = get_userdata($business_owner_id);
        echo "<p><strong>Business Owner:</strong> {$owner_user->display_name} ({$owner_user->user_email})</p>";
    }
}

// Check bookings assigned to this worker
echo "<h2>Assigned Bookings</h2>";

global $wpdb;
$bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');

$assigned_bookings = $wpdb->get_results($wpdb->prepare(
    "SELECT booking_id, user_id, assigned_staff_id, customer_name, booking_date, status FROM {$bookings_table} WHERE assigned_staff_id = %d ORDER BY booking_date DESC LIMIT 10",
    $current_user_id
), ARRAY_A);

if (!empty($assigned_bookings)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Booking ID</th><th>Owner ID</th><th>Assigned Staff ID</th><th>Customer</th><th>Date</th><th>Status</th><th>Test Access</th></tr>";
    
    foreach ($assigned_bookings as $booking) {
        echo "<tr>";
        echo "<td>{$booking['booking_id']}</td>";
        echo "<td>{$booking['user_id']}</td>";
        echo "<td>{$booking['assigned_staff_id']}</td>";
        echo "<td>{$booking['customer_name']}</td>";
        echo "<td>{$booking['booking_date']}</td>";
        echo "<td>{$booking['status']}</td>";
        
        // Test access
        $services_manager = new \NORDBOOKING\Classes\Services();
        $discounts_manager = new \NORDBOOKING\Classes\Discounts($current_user_id);
        $notifications_manager = new \NORDBOOKING\Classes\Notifications();
        $bookings_manager = new \NORDBOOKING\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
        
        $test_booking = $bookings_manager->get_booking($booking['booking_id'], $current_user_id);
        $access_result = $test_booking ? 'SUCCESS' : 'FAILED';
        
        echo "<td style='color: " . ($test_booking ? 'green' : 'red') . ";'>{$access_result}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test with first booking
    if (!empty($assigned_bookings)) {
        $test_booking_id = $assigned_bookings[0]['booking_id'];
        echo "<h3>Detailed Test for Booking ID: {$test_booking_id}</h3>";
        
        $test_booking = $bookings_manager->get_booking($test_booking_id, $current_user_id);
        if ($test_booking) {
            echo "<p style='color: green;'>✅ Successfully retrieved booking</p>";
            echo "<p><strong>Assigned Staff ID in booking:</strong> " . ($test_booking['assigned_staff_id'] ?? 'null') . "</p>";
            echo "<p><strong>Current User ID:</strong> {$current_user_id}</p>";
            echo "<p><strong>Match:</strong> " . ((int)($test_booking['assigned_staff_id'] ?? 0) === $current_user_id ? 'Yes' : 'No') . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve booking</p>";
        }
        
        // Test booking owner ID
        $booking_owner_id = $bookings_manager->get_booking_owner_id($test_booking_id);
        echo "<p><strong>Booking Owner ID:</strong> " . ($booking_owner_id ?? 'null') . "</p>";
        
        if ($is_worker && $business_owner_id) {
            echo "<p><strong>Owner Match:</strong> " . ($booking_owner_id === $business_owner_id ? 'Yes' : 'No') . "</p>";
        }
    }
    
} else {
    echo "<p>No bookings assigned to this worker.</p>";
}

// Test the My Assigned Bookings URL
echo "<h2>Test Links</h2>";
$booking_url = home_url('/dashboard/my-assigned-bookings/');
echo "<p><a href='{$booking_url}' target='_blank'>My Assigned Bookings Page</a></p>";

if (!empty($assigned_bookings)) {
    $test_booking_id = $assigned_bookings[0]['booking_id'];
    $single_booking_url = home_url("/dashboard/my-assigned-bookings/?action=view_booking&booking_id={$test_booking_id}");
    echo "<p><a href='{$single_booking_url}' target='_blank'>Test Single Booking View (ID: {$test_booking_id})</a></p>";
}

echo "<h2>Debug Complete</h2>";
echo "<p>Check the error logs for additional debugging information.</p>";
?>