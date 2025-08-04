<?php
/**
 * AJAX Handlers for MoBooking
 *
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register all AJAX handlers for the booking form.
 */
function mobooking_register_booking_ajax_handlers() {
    $ajax_actions = [
        'mobooking_check_service_area',
        'mobooking_validate_discount',
        'mobooking_submit_booking'
    ];

    foreach ($ajax_actions as $action) {
        add_action("wp_ajax_{$action}", $action);
        add_action("wp_ajax_nopriv_{$action}", $action);
    }
}
add_action('init', 'mobooking_register_booking_ajax_handlers');

/**
 * AJAX handler to check service area.
 */
function mobooking_check_service_area() {
    check_ajax_referer('mobooking-booking-nonce', 'nonce');

    $zip_code = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if (empty($zip_code) || empty($user_id)) {
        wp_send_json_error(['message' => __('Invalid input.', 'mobooking')]);
    }

    $geography_manager = new \MoBooking\Geography\Manager();
    $is_covered = $geography_manager->is_area_covered($user_id, $zip_code);

    if ($is_covered) {
        wp_send_json_success(['message' => __('Great! We service your area.', 'mobooking')]);
    } else {
        wp_send_json_error(['message' => __('Sorry, we don\'t service this area.', 'mobooking')]);
    }
}

/**
 * AJAX handler to validate a discount code.
 */
function mobooking_validate_discount() {
    check_ajax_referer('mobooking-booking-nonce', 'nonce');

    $discount_code = isset($_POST['discount_code']) ? sanitize_text_field($_POST['discount_code']) : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if (empty($discount_code) || empty($user_id)) {
        wp_send_json_error(['message' => __('Invalid input.', 'mobooking')]);
    }

    // This is a placeholder for a real discount validation logic
    if ($discount_code === 'SAVE10') {
        wp_send_json_success([
            'message' => __('Discount applied successfully.', 'mobooking'),
            'discount_amount' => 10
        ]);
    } else {
        wp_send_json_error(['message' => __('Invalid discount code.', 'mobooking')]);
    }
}

/**
 * AJAX handler for booking submission.
 */
function mobooking_submit_booking() {
    check_ajax_referer('mobooking-booking-nonce', 'nonce');

    // Sanitize and validate all the POST data
    $booking_data = [
        'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
        'zip_code' => isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '',
        'selected_services' => isset($_POST['selected_services']) ? array_map('intval', $_POST['selected_services']) : [],
        'service_options_data' => isset($_POST['service_options_data']) ? json_decode(stripslashes($_POST['service_options_data']), true) : [],
        'customer_name' => isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '',
        'customer_email' => isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '',
        'customer_phone' => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
        'service_date' => isset($_POST['service_date']) ? sanitize_text_field($_POST['service_date']) : '',
        'customer_address' => isset($_POST['customer_address']) ? sanitize_textarea_field($_POST['customer_address']) : '',
        'booking_notes' => isset($_POST['booking_notes']) ? sanitize_textarea_field($_POST['booking_notes']) : '',
        'total_price' => isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0,
        'discount_amount' => isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0,
    ];

    // Basic validation
    if (empty($booking_data['user_id']) || empty($booking_data['selected_services']) || empty($booking_data['customer_name']) || empty($booking_data['customer_email']) || empty($booking_data['service_date'])) {
        wp_send_json_error(['message' => __('Please fill in all required fields.', 'mobooking')]);
    }

    // Here you would typically save the booking to the database
    // For this example, we'll just return a success message with a fake booking reference

    wp_send_json_success([
        'message' => __('Booking confirmed successfully!', 'mobooking'),
        'booking_reference' => 'MB-' . time()
    ]);
}
