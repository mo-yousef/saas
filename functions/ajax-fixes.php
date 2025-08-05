<?php
/**
 * MoBooking Public Booking Form AJAX Fixes
 * Add this to your functions.php file
 */

// Fix 1: Register the missing AJAX handlers with correct names and parameters
add_action('wp_ajax_mobooking_get_available_slots', 'mobooking_handle_get_available_slots');
add_action('wp_ajax_nopriv_mobooking_get_available_slots', 'mobooking_handle_get_available_slots');

function mobooking_handle_get_available_slots() {
    // Verify nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

    if (!$tenant_id || !$date) {
        wp_send_json_error(['message' => 'Missing required parameters.'], 400);
        return;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(['message' => 'Invalid date format.'], 400);
        return;
    }

    try {
        $day_of_week = date('w', strtotime($date));

        // Check if we have the availability manager
        global $mobooking_availability_manager;
        if ($mobooking_availability_manager) {
            $schedule = $mobooking_availability_manager->get_recurring_schedule($tenant_id);
        } else {
            // Fallback: Create basic time slots
            $schedule = mobooking_get_default_availability_schedule();
        }

        $slots = [];
        foreach ($schedule as $day) {
            if ($day['day_of_week'] == $day_of_week && !empty($day['is_enabled'])) {
                if (isset($day['slots']) && is_array($day['slots'])) {
                    foreach ($day['slots'] as $slot) {
                        $slots[] = [
                            'time' => $slot['start_time'],
                            'display' => date('g:i A', strtotime($slot['start_time']))
                        ];
                    }
                }
            }
        }

        // If no slots found, provide default hourly slots
        if (empty($slots)) {
            $slots = mobooking_generate_default_time_slots();
        }

        wp_send_json_success($slots);

    } catch (Exception $e) {
        error_log('MoBooking - Get available slots error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error loading available times.'], 500);
    }
}

// Fix 2: Override the existing public services handler to ensure proper data structure
add_action('wp_ajax_mobooking_get_public_services_fixed', 'mobooking_handle_get_public_services_fixed');
add_action('wp_ajax_nopriv_mobooking_get_public_services_fixed', 'mobooking_handle_get_public_services_fixed');

function mobooking_handle_get_public_services_fixed() {
    // Verify nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    if (!$tenant_id) {
        wp_send_json_error(['message' => 'Tenant ID is required.'], 400);
        return;
    }

    try {
        // Get services using existing manager if available
        global $mobooking_services_manager;
        if ($mobooking_services_manager && method_exists($mobooking_services_manager, 'get_services_by_tenant_id')) {
            $services = $mobooking_services_manager->get_services_by_tenant_id($tenant_id);
        } else {
            // Fallback: Get services directly from database
            $services = mobooking_get_services_fallback($tenant_id);
        }

        // Ensure proper data structure
        $formatted_services = [];
        if (is_array($services)) {
            foreach ($services as $service) {
                $service_array = (array) $service;

                // Ensure required fields exist
                $formatted_service = [
                    'service_id' => intval($service_array['service_id'] ?? 0),
                    'name' => $service_array['name'] ?? 'Unnamed Service',
                    'description' => $service_array['description'] ?? '',
                    'price' => floatval($service_array['price'] ?? 0),
                    'duration' => intval($service_array['duration'] ?? 60),
                    'icon_url' => $service_array['icon_url'] ?? $service_array['icon'] ?? '',
                    'image_url' => $service_array['image_url'] ?? '',
                    'status' => $service_array['status'] ?? 'active'
                ];

                // Format price
                if ($formatted_service['price'] > 0) {
                    $formatted_service['price_formatted'] = number_format($formatted_service['price'], 2);
                } else {
                    $formatted_service['price_formatted'] = 'Contact for pricing';
                }

                // Get service options
                $formatted_service['options'] = mobooking_get_service_options_fallback($formatted_service['service_id'], $tenant_id);

                $formatted_services[] = $formatted_service;
            }
        }

        wp_send_json_success($formatted_services);

    } catch (Exception $e) {
        error_log('MoBooking - Get public services error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error loading services.'], 500);
    }
}

// Fix 3: Fallback function to get services directly from database
function mobooking_get_services_fallback($tenant_id) {
    global $wpdb;

    $services_table = $wpdb->prefix . 'mobooking_services';
    if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") != $services_table) {
        return [];
    }

    $services = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $services_table WHERE user_id = %d AND status = 'active' ORDER BY name ASC",
        $tenant_id
    ), ARRAY_A);

    return $services ?: [];
}

// Fix 4: Fallback function to get service options
function mobooking_get_service_options_fallback($service_id, $tenant_id) {
    global $wpdb;

    $options_table = $wpdb->prefix . 'mobooking_service_options';
    if ($wpdb->get_var("SHOW TABLES LIKE '$options_table'") != $options_table) {
        return [];
    }

    $options = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $options_table WHERE service_id = %d AND user_id = %d ORDER BY sort_order ASC, name ASC",
        $service_id, $tenant_id
    ), ARRAY_A);

    if (!$options) {
        return [];
    }

    $formatted_options = [];
    foreach ($options as $option) {
        $formatted_option = [
            'option_id' => intval($option['option_id']),
            'name' => $option['name'],
            'description' => $option['description'] ?? '',
            'type' => $option['type'],
            'required' => boolval($option['is_required'] ?? false),
            'is_required' => boolval($option['is_required'] ?? false),
            'price_impact' => floatval($option['price_impact_value'] ?? 0),
            'price_impact_value' => floatval($option['price_impact_value'] ?? 0),
            'price_impact_type' => $option['price_impact_type'] ?? '',
            'option_values' => $option['option_values'] ?? null,
            'sort_order' => intval($option['sort_order'] ?? 0)
        ];

        $formatted_options[] = $formatted_option;
    }

    return $formatted_options;
}

// Fix 5: Generate default time slots if no availability is configured
function mobooking_generate_default_time_slots() {
    $slots = [];
    $start_hour = 9; // 9 AM
    $end_hour = 17;  // 5 PM

    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        $time_24 = sprintf('%02d:00:00', $hour);
        $time_12 = date('g:i A', strtotime($time_24));

        $slots[] = [
            'time' => $time_24,
            'display' => $time_12
        ];
    }

    return $slots;
}

// Fix 6: Default availability schedule
function mobooking_get_default_availability_schedule() {
    $default_slots = [
        ['start_time' => '09:00:00', 'end_time' => '10:00:00'],
        ['start_time' => '10:00:00', 'end_time' => '11:00:00'],
        ['start_time' => '11:00:00', 'end_time' => '12:00:00'],
        ['start_time' => '13:00:00', 'end_time' => '14:00:00'],
        ['start_time' => '14:00:00', 'end_time' => '15:00:00'],
        ['start_time' => '15:00:00', 'end_time' => '16:00:00'],
        ['start_time' => '16:00:00', 'end_time' => '17:00:00'],
    ];

    return [
        // Monday through Friday
        ['day_of_week' => 1, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 2, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 3, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 4, 'is_enabled' => true, 'slots' => $default_slots],
        ['day_of_week' => 5, 'is_enabled' => true, 'slots' => $default_slots],
        // Weekend disabled
        ['day_of_week' => 0, 'is_enabled' => false, 'slots' => []],
        ['day_of_week' => 6, 'is_enabled' => false, 'slots' => []],
    ];
}

// Fix 7: Register proper booking submission handler with correct nonce
add_action('wp_ajax_mobooking_submit_booking', 'mobooking_handle_booking_submission');
add_action('wp_ajax_nopriv_mobooking_submit_booking', 'mobooking_handle_booking_submission');

function mobooking_handle_booking_submission() {
    // Verify nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed.'], 403);
        return;
    }

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;

    if (!$tenant_id || !$service_id) {
        wp_send_json_error(['message' => 'Missing required information.'], 400);
        return;
    }

    // Collect form data
    $booking_data = [
        'tenant_id' => $tenant_id,
        'service_id' => $service_id,
        'booking_date' => sanitize_text_field($_POST['booking_date'] ?? ''),
        'booking_time' => sanitize_text_field($_POST['booking_time'] ?? ''),
        'customer_first_name' => sanitize_text_field($_POST['customer_first_name'] ?? ''),
        'customer_last_name' => sanitize_text_field($_POST['customer_last_name'] ?? ''),
        'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
        'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
        'customer_address' => sanitize_text_field($_POST['customer_address'] ?? ''),
        'customer_notes' => sanitize_textarea_field($_POST['customer_notes'] ?? ''),
        'service_options' => $_POST['service_options'] ?? []
    ];

    // Validate required fields
    $required_fields = ['booking_date', 'booking_time', 'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone'];
    foreach ($required_fields as $field) {
        if (empty($booking_data[$field])) {
            wp_send_json_error(['message' => "Missing required field: $field"], 400);
            return;
        }
    }

    // Validate email
    if (!is_email($booking_data['customer_email'])) {
        wp_send_json_error(['message' => 'Invalid email address.'], 400);
        return;
    }

    try {
        // Try to use existing bookings manager if available
        global $mobooking_bookings_manager;
        if ($mobooking_bookings_manager && method_exists($mobooking_bookings_manager, 'create_booking')) {
            $result = $mobooking_bookings_manager->create_booking($booking_data);
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()], 500);
                return;
            }
        } else {
            // Fallback: Create basic booking record
            $booking_id = mobooking_create_basic_booking($booking_data);
            if (!$booking_id) {
                wp_send_json_error(['message' => 'Failed to create booking.'], 500);
                return;
            }
        }

        // Send notification emails
        mobooking_send_booking_notification_emails($booking_data);

        wp_send_json_success([
            'message' => 'Booking submitted successfully! We will contact you soon to confirm.',
            'booking_data' => $booking_data
        ]);

    } catch (Exception $e) {
        error_log('MoBooking - Booking submission error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An error occurred while processing your booking.'], 500);
    }
}

// Fix 8: Basic booking creation fallback
function mobooking_create_basic_booking($booking_data) {
    global $wpdb;

    // Check if bookings table exists
    $bookings_table = $wpdb->prefix . 'mobooking_bookings';
    if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") != $bookings_table) {
        error_log('MoBooking - Bookings table does not exist: ' . $bookings_table);
        return false;
    }

    $booking_reference = 'BK' . date('Ymd') . rand(1000, 9999);

    $result = $wpdb->insert(
        $bookings_table,
        [
            'user_id' => $booking_data['tenant_id'],
            'service_id' => $booking_data['service_id'],
            'booking_date' => $booking_data['booking_date'],
            'booking_time' => $booking_data['booking_time'],
            'customer_first_name' => $booking_data['customer_first_name'],
            'customer_last_name' => $booking_data['customer_last_name'],
            'customer_email' => $booking_data['customer_email'],
            'customer_phone' => $booking_data['customer_phone'],
            'customer_address' => $booking_data['customer_address'],
            'customer_notes' => $booking_data['customer_notes'],
            'booking_reference' => $booking_reference,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ],
        [
            '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ]
    );

    if ($result === false) {
        error_log('MoBooking - Database error creating booking: ' . $wpdb->last_error);
        return false;
    }

    return $wpdb->insert_id;
}

// Fix 9: Send notification emails
function mobooking_send_booking_notification_emails($booking_data) {
    // Customer confirmation email
    $customer_subject = 'Booking Confirmation - ' . get_bloginfo('name');
    $customer_message = "Dear {$booking_data['customer_first_name']},\n\n";
    $customer_message .= "Thank you for your booking request!\n\n";
    $customer_message .= "Booking Details:\n";
    $customer_message .= "Date: {$booking_data['booking_date']}\n";
    $customer_message .= "Time: {$booking_data['booking_time']}\n";
    $customer_message .= "Service ID: {$booking_data['service_id']}\n\n";
    $customer_message .= "We will contact you soon to confirm the details.\n\n";
    $customer_message .= "Best regards,\n" . get_bloginfo('name');

    wp_mail($booking_data['customer_email'], $customer_subject, $customer_message);

    // Business owner notification
    $owner = get_userdata($booking_data['tenant_id']);
    if ($owner && $owner->user_email) {
        $owner_subject = 'New Booking Request - ' . get_bloginfo('name');
        $owner_message = "You have received a new booking request!\n\n";
        $owner_message .= "Customer: {$booking_data['customer_first_name']} {$booking_data['customer_last_name']}\n";
        $owner_message .= "Email: {$booking_data['customer_email']}\n";
        $owner_message .= "Phone: {$booking_data['customer_phone']}\n";
        $owner_message .= "Date: {$booking_data['booking_date']}\n";
        $owner_message .= "Time: {$booking_data['booking_time']}\n";
        $owner_message .= "Service ID: {$booking_data['service_id']}\n";
        if (!empty($booking_data['customer_notes'])) {
            $owner_message .= "Notes: {$booking_data['customer_notes']}\n";
        }
        $owner_message .= "\nPlease log in to your dashboard to manage this booking.";

        wp_mail($owner->user_email, $owner_subject, $owner_message);
    }
}

// Fix 10: Debug logging for tenant resolution issues
add_action('wp_ajax_mobooking_debug_tenant', 'mobooking_debug_tenant_resolution');
add_action('wp_ajax_nopriv_mobooking_debug_tenant', 'mobooking_debug_tenant_resolution');

function mobooking_debug_tenant_resolution() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error(['message' => 'Debug mode not enabled.'], 403);
        return;
    }

    $tenant_slug = isset($_POST['tenant_slug']) ? sanitize_text_field($_POST['tenant_slug']) : '';

    if (empty($tenant_slug)) {
        wp_send_json_error(['message' => 'Tenant slug required.'], 400);
        return;
    }

    global $wpdb;

    // Method 1: Check settings table
    $settings_table = $wpdb->prefix . 'mobooking_tenant_settings';
    $tenant_from_settings = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $settings_table WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
        $tenant_slug
    ));

    // Method 2: Check WordPress users
    $user_from_slug = get_user_by('slug', $tenant_slug);

    // Method 3: Get all users with business owner role
    $business_owners = get_users(['role' => 'mobooking_business_owner']);

    $debug_info = [
        'tenant_slug' => $tenant_slug,
        'method_1_settings_table' => [
            'table' => $settings_table,
            'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table,
            'tenant_id' => $tenant_from_settings
        ],
        'method_2_wp_user' => [
            'user_found' => $user_from_slug ? true : false,
            'user_id' => $user_from_slug ? $user_from_slug->ID : null,
            'user_roles' => $user_from_slug ? $user_from_slug->roles : null
        ],
        'all_business_owners' => array_map(function($user) {
            return [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_nicename' => $user->user_nicename,
                'display_name' => $user->display_name
            ];
        }, $business_owners)
    ];

    wp_send_json_success($debug_info);
}

// Fix 11: Add a higher priority hook to override the existing services handler
add_action('init', function() {
    // Remove existing handler if it exists
    remove_action('wp_ajax_mobooking_get_public_services', 'mobooking_ajax_get_public_services');
    remove_action('wp_ajax_nopriv_mobooking_get_public_services', 'mobooking_ajax_get_public_services');

    // Add our fixed handler
    add_action('wp_ajax_mobooking_get_public_services', 'mobooking_handle_get_public_services_fixed');
    add_action('wp_ajax_nopriv_mobooking_get_public_services', 'mobooking_handle_get_public_services_fixed');

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking AJAX Fixes: Services handler overridden');
    }
}, 25);

// Fix 12: Add debug logging for all AJAX requests
add_action('wp_ajax_mobooking_debug_ajax_request', 'mobooking_debug_ajax_request');
add_action('wp_ajax_nopriv_mobooking_debug_ajax_request', 'mobooking_debug_ajax_request');

function mobooking_debug_ajax_request() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error(['message' => 'Debug mode not enabled.'], 403);
        return;
    }

    $action = isset($_POST['debug_action']) ? sanitize_text_field($_POST['debug_action']) : '';

    if (empty($action)) {
        wp_send_json_error(['message' => 'Debug action required.'], 400);
        return;
    }

    $debug_info = [
        'action' => $action,
        'post_data' => $_POST,
        'get_data' => $_GET,
        'server_info' => [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ],
        'wp_info' => [
            'is_admin' => is_admin(),
            'current_user_id' => get_current_user_id(),
            'doing_ajax' => wp_doing_ajax(),
        ]
    ];

    wp_send_json_success($debug_info);
}

// Initialize all fixes
add_action('init', function() {
    // Ensure proper initialization order
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking AJAX Fixes: Initialized at priority 20');
    }
}, 20);
?>
