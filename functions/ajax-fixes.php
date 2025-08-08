<?php
/**
 * Fixed AJAX Handler for MoBooking Form Submission
 * Add this to your functions.php or create a separate plugin file
 */

// First, remove any existing handlers that might be conflicting
add_action('init', function() {
    remove_action('wp_ajax_mobooking_create_booking', 'mobooking_handle_create_booking_ajax');
    remove_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_handle_create_booking_ajax');
}, 1);

// Add our fixed handler with high priority
add_action('wp_ajax_mobooking_create_booking', 'mobooking_create_booking_fixed', 5);
add_action('wp_ajax_nopriv_mobooking_create_booking', 'mobooking_create_booking_fixed', 5);

function mobooking_create_booking_fixed() {
    // Enable error reporting for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    // Log the incoming request
    error_log('MoBooking - Fixed AJAX Handler Called');
    error_log('MoBooking - POST Data: ' . print_r($_POST, true));

    try {
        // 1. Security Check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce')) {
            error_log('MoBooking - Nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.'], 403);
            return;
        }

        // 2. Validate Tenant ID
        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (!$tenant_id || !get_userdata($tenant_id)) {
            error_log('MoBooking - Invalid tenant ID: ' . $tenant_id);
            wp_send_json_error(['message' => 'Invalid business information.'], 400);
            return;
        }

        // 3. Parse JSON Data Safely
        $customer_details = mobooking_safe_json_decode($_POST['customer_details'] ?? '', 'customer_details');
        $selected_services = mobooking_safe_json_decode($_POST['selected_services'] ?? '', 'selected_services');
        $service_options = mobooking_safe_json_decode($_POST['service_options'] ?? '{}', 'service_options');
        $pet_information = mobooking_safe_json_decode($_POST['pet_information'] ?? '{}', 'pet_information');
        $property_access = mobooking_safe_json_decode($_POST['property_access'] ?? '{}', 'property_access');
        $service_frequency = sanitize_text_field($_POST['service_frequency'] ?? 'one-time');

        // 4. Validate Required Data
        if (!$customer_details || !is_array($customer_details)) {
            error_log('MoBooking - Invalid customer details');
            wp_send_json_error(['message' => 'Invalid customer information provided.'], 400);
            return;
        }

        if (!$selected_services || !is_array($selected_services) || empty($selected_services)) {
            error_log('MoBooking - Invalid or empty services');
            wp_send_json_error(['message' => 'Please select at least one service.'], 400);
            return;
        }

        // 5. Validate Required Customer Fields
        $required_fields = ['name', 'email', 'phone', 'date', 'time'];
        foreach ($required_fields as $field) {
            if (empty($customer_details[$field])) {
                error_log("MoBooking - Missing required field: {$field}");
                wp_send_json_error(['message' => "Missing required field: {$field}"], 400);
                return;
            }
        }

        // 6. Validate Email
        if (!is_email($customer_details['email'])) {
            error_log('MoBooking - Invalid email: ' . $customer_details['email']);
            wp_send_json_error(['message' => 'Please provide a valid email address.'], 400);
            return;
        }

        // 7. Validate Date Format
        $booking_date = sanitize_text_field($customer_details['date']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
            error_log('MoBooking - Invalid date format: ' . $booking_date);
            wp_send_json_error(['message' => 'Invalid date format.'], 400);
            return;
        }

        // 8. Validate Time Format
        $booking_time = sanitize_text_field($customer_details['time']);
        if (!preg_match('/^\d{2}:\d{2}$/', $booking_time)) {
            error_log('MoBooking - Invalid time format: ' . $booking_time);
            wp_send_json_error(['message' => 'Invalid time format.'], 400);
            return;
        }

        // 9. Process Services and Calculate Total
        $total_amount = 0;
        $service_details = [];

        foreach ($selected_services as $service_item) {
            if (!isset($service_item['service_id'])) {
                continue;
            }

            $service_id = intval($service_item['service_id']);

            // Get service details from database
            global $wpdb;
            $services_table = $wpdb->prefix . 'mobooking_services';
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$services_table} WHERE service_id = %d AND user_id = %d",
                $service_id,
                $tenant_id
            ), ARRAY_A);

            if ($service) {
                $service_details[] = [
                    'service_id' => $service_id,
                    'name' => $service['name'],
                    'price' => floatval($service['price']),
                    'duration' => intval($service['duration']),
                    'options' => $service_item['configured_options'] ?? []
                ];
                $total_amount += floatval($service['price']);
            }
        }

        if (empty($service_details)) {
            error_log('MoBooking - No valid services found');
            wp_send_json_error(['message' => 'No valid services selected.'], 400);
            return;
        }

        // 10. Create Booking Record
        $booking_reference = 'MB-' . date('Ymd') . '-' . rand(1000, 9999);
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$bookings_table}'") != $bookings_table) {
            error_log('MoBooking - Bookings table does not exist: ' . $bookings_table);
            wp_send_json_error(['message' => 'Database configuration error. Please contact support.'], 500);
            return;
        }

        $booking_data = [
            'user_id' => $tenant_id,
            'booking_reference' => $booking_reference,
            'customer_name' => sanitize_text_field($customer_details['name']),
            'customer_email' => sanitize_email($customer_details['email']),
            'customer_phone' => sanitize_text_field($customer_details['phone']),
            'customer_address' => sanitize_textarea_field($customer_details['address'] ?? ''),
            'booking_date' => $booking_date,
            'booking_time' => $booking_time,
            'total_amount' => $total_amount,
            'status' => 'pending',
            'special_instructions' => sanitize_textarea_field($customer_details['instructions'] ?? ''),
            'service_frequency' => $service_frequency,
            'selected_services' => wp_json_encode($service_details),
            'pet_information' => wp_json_encode($pet_information ?: []),
            'property_access' => wp_json_encode($property_access ?: []),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Insert booking
        $insert_result = $wpdb->insert(
            $bookings_table,
            $booking_data,
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ]
        );

        if ($insert_result === false) {
            error_log('MoBooking - Database insert failed: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Failed to create booking. Please try again.'], 500);
            return;
        }

        $booking_id = $wpdb->insert_id;

        // 11. Send Notification Emails
        try {
            mobooking_send_booking_emails($booking_id, $booking_data, $service_details);
        } catch (Exception $e) {
            error_log('MoBooking - Email sending failed: ' . $e->getMessage());
            // Don't fail the booking if email fails
        }

        // 12. Success Response
        error_log('MoBooking - Booking created successfully: ' . $booking_id);
        wp_send_json_success([
            'message' => 'Booking submitted successfully! We will contact you soon to confirm.',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'total_amount' => $total_amount,
            'booking_data' => [
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
                'customer_name' => $customer_details['name'],
                'customer_email' => $customer_details['email'],
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'total_amount' => $total_amount
            ]
        ]);

    } catch (Exception $e) {
        error_log('MoBooking - Exception in booking handler: ' . $e->getMessage());
        error_log('MoBooking - Exception trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'An unexpected error occurred. Please try again.'], 500);
    }
}

/**
 * Safe JSON decoder with multiple fallback methods
 */
function mobooking_safe_json_decode($json_string, $context = 'data') {
    if (empty($json_string)) {
        return null;
    }

    // Clean the input
    $json_string = trim($json_string);

    // Remove any BOM
    $json_string = preg_replace('/^\xEF\xBB\xBF/', '', $json_string);

    // Try direct decode first
    $decoded = json_decode($json_string, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Try with stripslashes
    $decoded = json_decode(stripslashes($json_string), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Try with wp_unslash
    $decoded = json_decode(wp_unslash($json_string), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Log the error
    error_log("MoBooking - JSON decode failed for {$context}: " . json_last_error_msg());
    error_log("MoBooking - Raw JSON (first 200 chars): " . substr($json_string, 0, 200));

    return null;
}

/**
 * Send booking notification emails
 */
function mobooking_send_booking_emails($booking_id, $booking_data, $service_details) {
    $site_name = get_bloginfo('name');
    $admin_email = get_option('admin_email');

    // Customer confirmation email
    $customer_subject = sprintf('[%s] Booking Confirmation - %s', $site_name, $booking_data['booking_reference']);
    $customer_message = sprintf(
        "Dear %s,\n\nThank you for your booking request!\n\nBooking Details:\n" .
        "Reference: %s\nDate: %s\nTime: %s\nServices: %s\nTotal: $%.2f\n\n" .
        "We will contact you soon to confirm the details.\n\nBest regards,\n%s",
        $booking_data['customer_name'],
        $booking_data['booking_reference'],
        $booking_data['booking_date'],
        $booking_data['booking_time'],
        implode(', ', array_column($service_details, 'name')),
        $booking_data['total_amount'],
        $site_name
    );

    wp_mail($booking_data['customer_email'], $customer_subject, $customer_message);

    // Admin notification email
    $admin_subject = sprintf('[%s] New Booking - %s', $site_name, $booking_data['booking_reference']);
    $admin_message = sprintf(
        "New booking received:\n\nReference: %s\nCustomer: %s\nEmail: %s\nPhone: %s\n" .
        "Date: %s\nTime: %s\nServices: %s\nTotal: $%.2f\n\nView in dashboard to confirm.",
        $booking_data['booking_reference'],
        $booking_data['customer_name'],
        $booking_data['customer_email'],
        $booking_data['customer_phone'],
        $booking_data['booking_date'],
        $booking_data['booking_time'],
        implode(', ', array_column($service_details, 'name')),
        $booking_data['total_amount']
    );

    wp_mail($admin_email, $admin_subject, $admin_message);
}
?>
