<?php
/**
 * Simple Worker Booking Access Test
 * 
 * This script tests if a worker can access their assigned bookings.
 * Run this while logged in as a worker.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Only allow logged-in users
if (!is_user_logged_in()) {
    wp_die('You must be logged in to run this test.');
}

$current_user_id = get_current_user_id();
$user = get_userdata($current_user_id);

echo "<h1>Simple Worker Booking Access Test</h1>";

// Check if user is a worker
$is_worker = \NORDBOOKING\Classes\Auth::is_user_worker($current_user_id);
if (!$is_worker) {
    echo "<p style='color: red;'>❌ You are not logged in as a worker. This test is for workers only.</p>";
    echo "<p>Your roles: " . implode(', ', $user->roles) . "</p>";
    exit;
}

echo "<p style='color: green;'>✅ You are logged in as a worker (ID: {$current_user_id})</p>";

// Get business owner
$business_owner_id = \NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
if (!$business_owner_id) {
    echo "<p style='color: red;'>❌ Could not find your business owner. Worker association may be broken.</p>";
    exit;
}

echo "<p style='color: green;'>✅ Business owner found (ID: {$business_owner_id})</p>";

// Check for assigned bookings
global $wpdb;
$bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');

$assigned_bookings = $wpdb->get_results($wpdb->prepare(
    "SELECT booking_id, user_id, assigned_staff_id, customer_name, booking_date, status 
     FROM {$bookings_table} 
     WHERE assigned_staff_id = %d 
     ORDER BY booking_date DESC 
     LIMIT 5",
    $current_user_id
), ARRAY_A);

if (empty($assigned_bookings)) {
    echo "<p style='color: orange;'>⚠️ No bookings are assigned to you.</p>";
    echo "<p>To test this functionality:</p>";
    echo "<ol>";
    echo "<li>Ask your business owner to assign a booking to you</li>";
    echo "<li>Or create a test booking and assign it to yourself</li>";
    echo "</ol>";
    exit;
}

echo "<p style='color: green;'>✅ Found " . count($assigned_bookings) . " assigned bookings</p>";

// Test booking access
echo "<h2>Testing Booking Access</h2>";

$services_manager = new \NORDBOOKING\Classes\Services();
$discounts_manager = new \NORDBOOKING\Classes\Discounts($business_owner_id);
$notifications_manager = new \NORDBOOKING\Classes\Notifications();
$bookings_manager = new \NORDBOOKING\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

foreach ($assigned_bookings as $booking_data) {
    $booking_id = $booking_data['booking_id'];
    echo "<h3>Testing Booking ID: {$booking_id}</h3>";
    
    // Test 1: Try to get booking with business owner ID
    $booking_with_owner_id = $bookings_manager->get_booking($booking_id, $business_owner_id);
    if ($booking_with_owner_id) {
        echo "<p style='color: green;'>✅ Successfully retrieved booking using business owner ID</p>";
        echo "<p>Assigned staff ID in booking: " . ($booking_with_owner_id['assigned_staff_id'] ?? 'null') . "</p>";
        echo "<p>Current worker ID: {$current_user_id}</p>";
        
        if ((int)($booking_with_owner_id['assigned_staff_id'] ?? 0) === $current_user_id) {
            echo "<p style='color: green;'>✅ Booking is correctly assigned to you</p>";
            
            // Generate test URL
            $test_url = home_url("/dashboard/my-assigned-bookings/?action=view_booking&booking_id={$booking_id}");
            echo "<p><strong>Test URL:</strong> <a href='{$test_url}' target='_blank'>Click here to test booking view</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Booking assignment mismatch</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Could not retrieve booking using business owner ID</p>";
    }
    
    // Test 2: Try to get booking with worker ID
    $booking_with_worker_id = $bookings_manager->get_booking($booking_id, $current_user_id);
    if ($booking_with_worker_id) {
        echo "<p style='color: green;'>✅ Successfully retrieved booking using worker ID</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not retrieve booking using worker ID (this is expected)</p>";
    }
    
    echo "<hr>";
    break; // Only test the first booking
}

echo "<h2>Summary</h2>";
echo "<p>If you see ✅ marks above, the booking access should work.</p>";
echo "<p>If you see ❌ marks, there may be an issue with the booking assignment or data.</p>";
echo "<p>Try clicking the test URL above to see if you can access the booking details.</p>";
?>