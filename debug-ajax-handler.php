<?php
/**
 * Debug AJAX Handler for Customer Booking Management
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request
error_log('NORDBOOKING DEBUG: AJAX request received');
error_log('NORDBOOKING DEBUG: POST data: ' . print_r($_POST, true));

// Check if this is the reschedule action
if (isset($_POST['action']) && $_POST['action'] === 'nordbooking_reschedule_booking') {
    
    // Test 1: Check nonce
    $nonce_check = wp_verify_nonce($_POST['nonce'], 'nordbooking_customer_booking_management');
    error_log('NORDBOOKING DEBUG: Nonce check result: ' . ($nonce_check ? 'PASS' : 'FAIL'));
    
    if (!$nonce_check) {
        wp_send_json_error(['message' => 'Security check failed.']);
        exit;
    }
    
    // Test 2: Get parameters
    $booking_token = sanitize_text_field($_POST['booking_token'] ?? '');
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $new_date = sanitize_text_field($_POST['new_date'] ?? '');
    $new_time = sanitize_text_field($_POST['new_time'] ?? '');
    
    error_log("NORDBOOKING DEBUG: Parameters - Token: $booking_token, ID: $booking_id, Date: $new_date, Time: $new_time");
    
    if (empty($booking_token) || empty($booking_id) || empty($new_date) || empty($new_time)) {
        wp_send_json_error(['message' => 'Missing required information.']);
        exit;
    }
    
    // Test 3: Check if classes exist
    if (!class_exists('NORDBOOKING\Classes\Database')) {
        wp_send_json_error(['message' => 'Database class not found.']);
        exit;
    }
    
    // Test 4: Get booking from database
    global $wpdb;
    $bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
    error_log("NORDBOOKING DEBUG: Bookings table: $bookings_table");
    
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $bookings_table WHERE booking_id = %d AND status IN ('pending', 'confirmed')",
        $booking_id
    ));
    
    if (!$booking) {
        wp_send_json_error(['message' => 'Booking not found or not eligible for changes.']);
        exit;
    }
    
    error_log("NORDBOOKING DEBUG: Booking found - ID: {$booking->booking_id}, Email: {$booking->customer_email}");
    
    // Test 5: Verify token
    $expected_token = hash('sha256', $booking->booking_id . $booking->customer_email . wp_salt());
    error_log("NORDBOOKING DEBUG: Expected token: $expected_token");
    error_log("NORDBOOKING DEBUG: Received token: $booking_token");
    
    if (!hash_equals($expected_token, $booking_token)) {
        wp_send_json_error(['message' => 'Invalid token.']);
        exit;
    }
    
    // Test 6: Validate date and time
    $date_obj = DateTime::createFromFormat('Y-m-d', $new_date);
    $time_obj = DateTime::createFromFormat('H:i', $new_time);
    
    if (!$date_obj || !$time_obj) {
        wp_send_json_error(['message' => 'Invalid date or time format.']);
        exit;
    }
    
    // Test 7: Check if date is not in the past
    $new_datetime = DateTime::createFromFormat('Y-m-d H:i', $new_date . ' ' . $new_time);
    $now = new DateTime();
    if ($new_datetime < $now) {
        wp_send_json_error(['message' => 'New booking date and time cannot be in the past.']);
        exit;
    }
    
    // Test 8: Update the booking
    $update_result = $wpdb->update(
        $bookings_table,
        [
            'booking_date' => $new_date,
            'booking_time' => $new_time,
            'updated_at' => current_time('mysql')
        ],
        ['booking_id' => $booking_id],
        ['%s', '%s', '%s'],
        ['%d']
    );
    
    error_log("NORDBOOKING DEBUG: Update result: " . ($update_result !== false ? 'SUCCESS' : 'FAILED'));
    if ($update_result === false) {
        error_log("NORDBOOKING DEBUG: Database error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to update booking. Database error: ' . $wpdb->last_error]);
        exit;
    }
    
    // Success
    wp_send_json_success([
        'message' => 'Booking rescheduled successfully (DEBUG MODE)',
        'new_date' => $new_date,
        'new_time' => $new_time,
        'new_date_formatted' => date('F j, Y', strtotime($new_date)),
        'new_time_formatted' => date('g:i A', strtotime($new_time))
    ]);
    
} else {
    wp_send_json_error(['message' => 'Invalid action or not a reschedule request.']);
}
?>