<?php
/**
 * Direct AJAX Test - Bypasses WordPress AJAX system
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

try {
    // Log the request
    error_log('NORDBOOKING DIRECT TEST: Request received');
    error_log('NORDBOOKING DIRECT TEST: POST data: ' . print_r($_POST, true));
    error_log('NORDBOOKING DIRECT TEST: GET data: ' . print_r($_GET, true));

    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
        exit;
    }

    // Check if we have the required data
    if (!isset($_POST['action']) || $_POST['action'] !== 'test_reschedule') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    // Test database connection
    global $wpdb;
    if (!$wpdb) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Test if our classes exist
    if (!class_exists('NORDBOOKING\Classes\Database')) {
        echo json_encode(['success' => false, 'message' => 'NORDBOOKING Database class not found']);
        exit;
    }

    // Get booking data
    $booking_id = intval($_POST['booking_id'] ?? 0);
    if (!$booking_id) {
        echo json_encode(['success' => false, 'message' => 'No booking ID provided']);
        exit;
    }

    // Test database query
    $bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
    error_log("NORDBOOKING DIRECT TEST: Bookings table: $bookings_table");

    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $bookings_table WHERE booking_id = %d",
        $booking_id
    ));

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Test token generation
    $test_token = hash('sha256', $booking->booking_id . $booking->customer_email . wp_salt());
    
    error_log("NORDBOOKING DIRECT TEST: Booking found - ID: {$booking->booking_id}");
    error_log("NORDBOOKING DIRECT TEST: Generated token: $test_token");

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Direct test successful',
        'data' => [
            'booking_id' => $booking->booking_id,
            'customer_name' => $booking->customer_name,
            'current_date' => $booking->booking_date,
            'current_time' => $booking->booking_time,
            'generated_token' => $test_token
        ]
    ]);

} catch (Exception $e) {
    error_log('NORDBOOKING DIRECT TEST: Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log('NORDBOOKING DIRECT TEST: Fatal Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $e->getMessage()]);
}
?>