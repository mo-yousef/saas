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

    error_log('MoBooking - Fixed AJAX Handler Called');
    error_log('MoBooking - POST Data: ' . print_r($_POST, true));

    try {
        // 1. Security Check & Basic Validation
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (!$tenant_id || !get_userdata($tenant_id)) {
            wp_send_json_error(['message' => 'Invalid business information.'], 400);
            return;
        }

        // 2. Parse and Validate Input Data
        $customer_details = mobooking_safe_json_decode($_POST['customer_details'] ?? '', 'customer_details');
        $selected_services_raw = mobooking_safe_json_decode($_POST['selected_services'] ?? '', 'selected_services');

        if (empty($selected_services_raw) || !isset($selected_services_raw[0]['service_id'])) {
            wp_send_json_error(['message' => 'Please select a service.'], 400);
            return;
        }

        // 3. Securely Fetch Service and Option Data from DB
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $service_id = intval($selected_services_raw[0]['service_id']);
        $service_from_db = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$services_table} WHERE service_id = %d AND user_id = %d",
            $service_id, $tenant_id
        ), ARRAY_A);

        if (!$service_from_db) {
            wp_send_json_error(['message' => 'Selected service is not valid.'], 400);
            return;
        }

        // 4. Calculate Total Amount Securely on the Backend
        $base_price = floatval($service_from_db['price']);
        $options_price = 0;
        $percentage_impact = 0;

        $service_options_manager = new \MoBooking\Classes\ServiceOptions();
        $submitted_options = $selected_services_raw[0]['configured_options'] ?? [];

        foreach ($submitted_options as $option_id => $user_inputs) {
            $option_from_db = $service_options_manager->get_service_option(intval($option_id), $tenant_id);
            if (!$option_from_db) continue;

            $option_from_db['option_values'] = json_decode($option_from_db['option_values'] ?? '[]', true);

            $price_impact_type = $option_from_db['price_impact_type'];
            $price_impact_value = floatval($option_from_db['price_impact_value'] ?? 0);
            $option_type = $option_from_db['type'];

            // Handle option-level impacts
            if ($option_type !== 'quantity') {
                if ($price_impact_type === 'fixed') $options_price += $price_impact_value;
                if ($price_impact_type === 'percentage') $percentage_impact += $price_impact_value;
            }

            // Handle value-based pricing
            $user_value = $user_inputs['value'] ?? null;
            if ($user_value !== null) {
                $numeric_user_value = floatval($user_value);
                if ($option_type === 'quantity' && $price_impact_type === 'multiply') {
                    $options_price += $price_impact_value * $numeric_user_value;
                } elseif (($option_type === 'sqm' || $option_type === 'kilometers') && is_array($option_from_db['option_values'])) {
                    foreach ($option_from_db['option_values'] as $range) {
                        $from = floatval($range['from_sqm'] ?? $range['from_km']);
                        $to_str = $range['to_sqm'] ?? $range['to_km'];
                        $to = ($to_str === '∞' || $to_str === 'infinity') ? INF : floatval($to_str);
                        if ($numeric_user_value >= $from && ($numeric_user_value <= $to || $to === INF)) {
                            $price_per_unit = floatval($range['price_per_sqm'] ?? $range['price_per_km']);
                            $options_price += $numeric_user_value * $price_per_unit;
                            break;
                        }
                    }
                }
            }

            // Handle choice-based pricing
            $user_selected_choices = $user_inputs['selectedChoices'] ?? [];
            if (!empty($user_selected_choices) && is_array($option_from_db['option_values'])) {
                foreach ($user_selected_choices as $user_choice) {
                    foreach ($option_from_db['option_values'] as $db_choice) {
                        if (isset($db_choice['label']) && $db_choice['label'] === $user_choice['label']) {
                            $options_price += floatval($db_choice['price'] ?? 0);
                            break;
                        }
                    }
                }
            }
        }

        $sub_total = $base_price + $options_price;
        $total_amount = $sub_total + ($sub_total * ($percentage_impact / 100));

        // 5. Create Booking Record
        $booking_data = [
            'user_id' => $tenant_id,
            'booking_reference' => 'MB-' . date('Ymd') . '-' . rand(1000, 9999),
            'customer_name' => sanitize_text_field($customer_details['name']),
            'customer_email' => sanitize_email($customer_details['email']),
            'customer_phone' => sanitize_text_field($customer_details['phone']),
            'customer_address' => sanitize_textarea_field($customer_details['address'] ?? ''),
            'booking_date' => sanitize_text_field($customer_details['date']),
            'booking_time' => sanitize_text_field($customer_details['time']),
            'total_amount' => $total_amount,
            'status' => 'pending',
            'special_instructions' => sanitize_textarea_field($customer_details['instructions'] ?? ''),
            'service_frequency' => sanitize_text_field($_POST['service_frequency'] ?? 'one-time'),
            'selected_services' => wp_json_encode($selected_services_raw), // Save the raw submitted structure
            'pet_information' => $_POST['pet_information'] ?? '{}',
            'property_access' => $_POST['property_access'] ?? '{}',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $bookings_table = $wpdb->prefix . 'mobooking_bookings';
        $insert_result = $wpdb->insert($bookings_table, $booking_data);

        if ($insert_result === false) {
            wp_send_json_error(['message' => 'Failed to create booking. Please try again.'], 500);
            return;
        }

        $booking_id = $wpdb->insert_id;

        // 6. Success Response
        wp_send_json_success([
            'message' => 'Booking submitted successfully!',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_data['booking_reference'],
            'total_amount' => $total_amount,
        ]);

    } catch (Exception $e) {
        error_log('MoBooking - Exception in booking handler: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An unexpected error occurred.'], 500);
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
