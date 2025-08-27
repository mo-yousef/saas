<?php
/**
 * Complete MoBooking AJAX Handlers
 * File: classes/BookingFormAjax.php
 * 
 * This class handles all AJAX requests for the public booking form,
 * including service area checking, service loading, options retrieval,
 * availability checking, discount application, and booking creation.
 */

namespace MoBooking\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class BookingFormAjax {
    
    private $wpdb;
    private $services_manager;
    private $areas_manager;
    private $availability_manager;
    private $discounts_manager;
    private $bookings_manager;
    private $customers_manager;
    private $notifications_manager;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Initialize managers
        global $mobooking_services_manager, $mobooking_areas_manager, $mobooking_availability_manager;
        global $mobooking_discounts_manager, $mobooking_bookings_manager, $mobooking_customers_manager;
        global $mobooking_notifications_manager;
        
        $this->services_manager = $mobooking_services_manager;
        $this->areas_manager = $mobooking_areas_manager;
        $this->availability_manager = $mobooking_availability_manager;
        $this->discounts_manager = $mobooking_discounts_manager;
        $this->bookings_manager = $mobooking_bookings_manager;
        $this->customers_manager = $mobooking_customers_manager;
        $this->notifications_manager = $mobooking_notifications_manager;
    }

    public function register_ajax_actions() {
        // Public booking form AJAX handlers
        add_action('wp_ajax_nopriv_mobooking_check_service_area', [$this, 'handle_check_service_area']);
        add_action('wp_ajax_mobooking_check_service_area', [$this, 'handle_check_service_area']);

        add_action('wp_ajax_nopriv_mobooking_get_public_services', [$this, 'handle_get_public_services']);
        add_action('wp_ajax_mobooking_get_public_services', [$this, 'handle_get_public_services']);
        
        add_action('wp_ajax_nopriv_mobooking_get_service_options', [$this, 'handle_get_service_options']);
        add_action('wp_ajax_mobooking_get_service_options', [$this, 'handle_get_service_options']);
        
        add_action('wp_ajax_nopriv_mobooking_get_available_time_slots', [$this, 'handle_get_available_time_slots']);
        add_action('wp_ajax_mobooking_get_available_time_slots', [$this, 'handle_get_available_time_slots']);
        
        add_action('wp_ajax_nopriv_mobooking_apply_discount', [$this, 'handle_apply_discount']);
        add_action('wp_ajax_mobooking_apply_discount', [$this, 'handle_apply_discount']);
        
        add_action('wp_ajax_nopriv_mobooking_create_booking', [$this, 'handle_create_booking_public_ajax']);
        add_action('wp_ajax_mobooking_create_booking', [$this, 'handle_create_booking_public_ajax']);
    }

    /**
     * Check if service is available in the specified area
     */
    public function handle_check_service_area() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';

        if (empty($tenant_id) || empty($location)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'mobooking')], 400);
            return;
        }

        try {
            // Get tenant's service areas
            $areas_table = Database::get_table_name('areas');
            $areas = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT area_name, area_type, area_value, country_code FROM $areas_table WHERE user_id = %d",
                $tenant_id
            ), ARRAY_A);

            if (empty($areas)) {
                // If no service areas configured, consider service available (do not block booking)
                wp_send_json_success([
                    'message' => __('Service is available in your area!', 'mobooking'),
                    'covered' => true,
                    'note' => 'no_areas_configured'
                ]);
                return;
            }

            $location_normalized = strtolower(trim($location));
            $is_covered = false;
            $area_name = '';

            foreach ($areas as $area) {
                $area_value_normalized = strtolower(trim($area['area_value']));
                
                // Check for exact match or partial match
                if ($area_value_normalized === $location_normalized || 
                    strpos($location_normalized, $area_value_normalized) !== false ||
                    strpos($area_value_normalized, $location_normalized) !== false) {
                    $is_covered = true;
                    $area_name = $area['area_name'];
                    break;
                }

                // For ZIP codes, check if it's a 5-digit match
                if ($area['area_type'] === 'zipcode' && 
                    preg_match('/^\d{5}/', $location_normalized) && 
                    preg_match('/^\d{5}/', $area_value_normalized)) {
                    if (substr($location_normalized, 0, 5) === substr($area_value_normalized, 0, 5)) {
                        $is_covered = true;
                        $area_name = $area['area_name'];
                        break;
                    }
                }
            }

            if ($is_covered) {
                wp_send_json_success([
                    'message' => __('Service is available in your area!', 'mobooking'),
                    'covered' => true,
                    'area_name' => $area_name
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Sorry, we do not currently service your area.', 'mobooking'),
                    'covered' => false
                ], 404);
            }

        } catch (Exception $e) {
            error_log('MoBooking - Service area check error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error checking service area.', 'mobooking')], 500);
        }
    }

    /**
     * Get public services for a tenant
     */
    public function handle_get_public_services() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;

        if (empty($tenant_id)) {
            wp_send_json_error(['message' => __('Invalid tenant ID.', 'mobooking')], 400);
            return;
        }

        try {
            // Get active services for the tenant
            $services_table = Database::get_table_name('services');
            $services = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT service_id, name, description, price, duration, icon, image_url 
                 FROM $services_table 
                 WHERE user_id = %d AND status = 'active' 
                 ORDER BY name ASC",
                $tenant_id
            ), ARRAY_A);

            if (empty($services)) {
                wp_send_json_error(['message' => __('No services available.', 'mobooking')], 404);
                return;
            }

            // Format services for frontend
            $formatted_services = array_map(function($service) {
                return [
                    'service_id' => intval($service['service_id']),
                    'name' => $service['name'],
                    'description' => $service['description'],
                    'price' => floatval($service['price']),
                    'duration' => intval($service['duration']),
                    'icon' => $service['icon'],
                    'image_url' => $service['image_url']
                ];
            }, $services);

            wp_send_json_success([
                'services' => $formatted_services,
                'count' => count($formatted_services)
            ]);

        } catch (Exception $e) {
            error_log('MoBooking - Get public services error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading services.', 'mobooking')], 500);
        }
    }

    /**
     * Get service options for selected services
     */
    public function handle_get_service_options() {
        error_log('[MoBooking AJAX Debug] Received POST for get_service_options: ' . print_r($_POST, true));
        $nonce_verified = check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false);
        error_log('[MoBooking AJAX Debug] Nonce verification result: ' . ($nonce_verified ? 'SUCCESS' : 'FAILURE'));

        if (!$nonce_verified) {
            wp_send_json_error(['message' => 'Error: Nonce verification failed.'], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $service_ids = isset($_POST['service_ids']) ? array_map('intval', $_POST['service_ids']) : [];

        if (empty($tenant_id) || empty($service_ids)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'mobooking')], 400);
            return;
        }

        try {
            $options = [];
            $service_options_table = Database::get_table_name('service_options');

            foreach ($service_ids as $service_id) {
                // Verify service belongs to tenant
                $services_table = Database::get_table_name('services');
                $service_exists = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM $services_table WHERE service_id = %d AND user_id = %d",
                    $service_id, $tenant_id
                ));

                if (!$service_exists) {
                    continue;
                }

                // Get options for this service
                $service_options = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT option_id, name, description, type, is_required, 
                            price_impact_type, price_impact_value, option_values, sort_order
                     FROM $service_options_table 
                     WHERE service_id = %d AND user_id = %d 
                     ORDER BY sort_order ASC, name ASC",
                    $service_id, $tenant_id
                ), ARRAY_A);

                if (!empty($service_options)) {
                    $options[$service_id] = array_map(function($option) {
                        return [
                            'option_id' => intval($option['option_id']),
                            'name' => $option['name'],
                            'description' => $option['description'],
                            'type' => $option['type'],
                            'is_required' => boolval($option['is_required']),
                            'price_impact_type' => $option['price_impact_type'],
                            'price_impact_value' => floatval($option['price_impact_value']),
                            'option_values' => $this->parse_option_values($option['option_values']),
                            'sort_order' => intval($option['sort_order'])
                        ];
                    }, $service_options);
                }
            }

            wp_send_json_success([
                'options' => $options,
                'services_count' => count($service_ids)
            ]);

        } catch (Exception $e) {
            error_log('MoBooking - Get service options error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading service options.', 'mobooking')], 500);
        }
    }

    /**
     * Get available time slots for a specific date
     */
    public function handle_get_available_time_slots() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        if (empty($tenant_id) || empty($date)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'mobooking')], 400);
            return;
        }

        // More robust date validation
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            wp_send_json_error(['message' => __('Invalid date format. Please use YYYY-MM-DD.', 'mobooking')], 400);
            return;
        }

        // Handle services as an array
        $selected_services = isset($_POST['services']) && is_array($_POST['services']) ? array_map('intval', $_POST['services']) : [];

        try {
            $time_slots = $this->get_available_time_slots($tenant_id, $date, $selected_services);

            wp_send_json_success([
                'time_slots' => $time_slots,
                'date' => $date,
                'slots_count' => count($time_slots)
            ]);

        } catch (Exception $e) {
            error_log('MoBooking - Get time slots error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading available times.', 'mobooking')], 500);
        }
    }

    /**
     * Apply discount code to booking
     */
    public function handle_apply_discount() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $discount_code = isset($_POST['discount_code']) ? sanitize_text_field($_POST['discount_code']) : '';
        $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;

        if (empty($tenant_id) || empty($discount_code) || $subtotal <= 0) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'mobooking')], 400);
            return;
        }

        try {
            // Get discount from database
            $discounts_table = Database::get_table_name('discounts');
            $discount = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT discount_id, code, type, value, expiry_date, usage_limit, times_used, status
                 FROM $discounts_table 
                 WHERE user_id = %d AND code = %s AND status = 'active'",
                $tenant_id, $discount_code
            ), ARRAY_A);

            if (!$discount) {
                wp_send_json_error(['message' => __('Invalid discount code.', 'mobooking')], 404);
                return;
            }

            // Validate discount
            $validation_result = $this->validate_discount($discount, $subtotal);
            if (is_wp_error($validation_result)) {
                wp_send_json_error(['message' => $validation_result->get_error_message()], 400);
                return;
            }

            // Calculate discount amount
            $discount_amount = $this->calculate_discount_amount($discount, $subtotal);

            wp_send_json_success([
                'discount' => [
                    'discount_id' => intval($discount['discount_id']),
                    'code' => $discount['code'],
                    'type' => $discount['type'],
                    'value' => floatval($discount['value']),
                    'discount_amount' => $discount_amount
                ],
                'message' => sprintf(__('Discount "%s" applied successfully!', 'mobooking'), $discount['code'])
            ]);

        } catch (Exception $e) {
            error_log('MoBooking - Apply discount error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error applying discount code.', 'mobooking')], 500);
        }
    }

    public function handle_create_booking_public_ajax() {
        try {
            // 1. Security and Validation
            if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
                return;
            }

            $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
            if (empty($tenant_id) || !get_userdata($tenant_id)) {
                wp_send_json_error(['message' => __('Invalid tenant information.', 'mobooking')], 400);
                return;
            }

            // 2. Decode and Sanitize All Inputs
            $customer_details = $this->safe_json_decode(stripslashes($_POST['customer_details'] ?? ''), 'customer_details');
            $selected_services = $this->safe_json_decode(stripslashes($_POST['selected_services'] ?? ''), 'selected_services');
            $service_options = $this->safe_json_decode(stripslashes($_POST['service_options'] ?? ''), 'service_options');
            $service_frequency = sanitize_text_field($_POST['service_frequency'] ?? 'one-time');

            // Basic data integrity check
            if (is_wp_error($customer_details) || is_wp_error($selected_services) || is_wp_error($service_options)) {
                wp_send_json_error(['message' => __('Invalid form data submitted.', 'mobooking')], 400);
                return;
            }
            if (empty($selected_services)) {
                wp_send_json_error(['message' => __('At least one service must be selected.', 'mobooking')], 400);
                return;
            }

            // 3. Server-side Price Calculation
            $total_price = 0;
            $total_duration = 0;
            $service_options_manager = new ServiceOptions();

            foreach ($selected_services as $item) {
                $service = $this->services_manager->get_service(intval($item['service_id']), $tenant_id);
                if ($service) {
                    $total_price += floatval($service['price']);
                    $total_duration += intval($service['duration']);

                    if (!empty($item['configured_options'])) {
                        foreach($item['configured_options'] as $option_id => $option_data) {
                            $option_details = $service_options_manager->get_service_option(intval($option_id), $tenant_id);
                            if ($option_details) {
                                $option_type = $option_details['type'];
                                if ($option_type === 'sqm' || $option_type === 'kilometers') {
                                    $quantity = floatval($option_data['value']);
                                    $price_per_unit = floatval($option_details['price_impact_value']);
                                    $total_price += $quantity * $price_per_unit;
                                } else if ($option_type === 'select' || $option_type === 'radio') {
                                    // For select/radio, find the chosen value and add its price
                                    $option_values = json_decode($option_details['option_values'], true);
                                    if(is_array($option_values)) {
                                        foreach($option_values as $choice) {
                                            if ($choice['label'] === $option_data['value']) {
                                                $total_price += floatval($choice['price']);
                                                break;
                                            }
                                        }
                                    }
                                } else {
                                     $total_price += floatval($option_details['price_impact_value']);
                                }
                            }
                        }
                    }
                }
            }

            // 4. Database Insertion
            $bookings_table = Database::get_table_name('bookings');
            $booking_items_table = Database::get_table_name('booking_items');
            $booking_reference = 'MB-' . strtoupper(wp_generate_password(8, false));

            $booking_data = [
                'user_id' => $tenant_id,
                'customer_name' => sanitize_text_field($customer_details['name']),
                'customer_email' => sanitize_email($customer_details['email']),
                'customer_phone' => sanitize_text_field($customer_details['phone']),
                'service_address' => sanitize_textarea_field($customer_details['address']),
                'booking_date' => sanitize_text_field($customer_details['date']),
                'booking_time' => sanitize_text_field($customer_details['time']),
                'special_instructions' => sanitize_textarea_field($customer_details['instructions'] ?? ''),
                'total_price' => $total_price, // Use server-calculated subtotal
                'total_duration' => $total_duration,
                'status' => 'pending',
                'booking_reference' => $booking_reference,
                'service_frequency' => $service_frequency,
                'created_at' => current_time('mysql', 1),
                'updated_at' => current_time('mysql', 1),
            ];

            $this->wpdb->insert($bookings_table, $booking_data);
            $booking_id = $this->wpdb->insert_id;

            if (!$booking_id) {
                wp_send_json_error(['message' => __('Could not save booking. Database error.', 'mobooking')], 500);
                return;
            }

            // Insert booking items
            foreach ($selected_services as $item) {
                 $service = $this->services_manager->get_service(intval($item['service_id']), $tenant_id);
                 if($service) {
                    $this->wpdb->insert($booking_items_table, [
                        'booking_id' => $booking_id,
                        'service_id' => intval($item['service_id']),
                        'service_name' => $service['name'],
                        'service_price' => floatval($service['price']),
                        'quantity' => 1,
                        'selected_options' => wp_json_encode($item['configured_options'] ?? []),
                        'item_total_price' => $total_price,
                    ]);
                 }
            }

            // 5. Post-booking actions (e.g., notifications)
            $this->notifications_manager->send_booking_confirmation_customer($booking_data, $customer_details['email'], $tenant_id);
            $this->notifications_manager->send_booking_confirmation_admin($booking_data, $tenant_id);

            // 6. Success Response
            wp_send_json_success([
                'message' => 'Booking created successfully!',
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
            ]);

        } catch (\Throwable $e) {
            error_log('MoBooking Fatal Error in handle_create_booking_public_ajax: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            wp_send_json_error(['message' => 'A critical error occurred while creating your booking.'], 500);
        }
    }

    /**
     * Verify that required database tables exist
     */
    private function verify_database_tables() {
        $required_tables = ['bookings', 'booking_items', 'services'];

        foreach ($required_tables as $table_suffix) {
            $table_name = Database::get_table_name($table_suffix);

            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                error_log("MoBooking - Required table missing: $table_name");
                return false;
            }
        }

        return true;
    }

    /**
     * Parse option values from JSON string
     */
    private function parse_option_values($option_values) {
        if (empty($option_values)) {
            return [];
        }

        $parsed = json_decode($option_values, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $parsed;
    }

    /**
     * Get available time slots for a specific date
     */
    private function get_available_time_slots($tenant_id, $date, $selected_services = []) {
        // Get day of week (0 = Sunday, 1 = Monday, etc.)
        $day_of_week = date('w', strtotime($date));

        // Get recurring availability rules
        $availability_rules_table = Database::get_table_name('availability_rules');
        $rules = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT start_time, end_time, capacity 
             FROM $availability_rules_table 
             WHERE user_id = %d AND day_of_week = %d AND is_active = 1 
             ORDER BY start_time ASC",
            $tenant_id, $day_of_week
        ), ARRAY_A);

        // Get availability exceptions for this specific date
        $availability_exceptions_table = Database::get_table_name('availability_exceptions');
        $exceptions = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT start_time, end_time, capacity, is_unavailable 
             FROM $availability_exceptions_table 
             WHERE user_id = %d AND exception_date = %s",
            $tenant_id, $date
        ), ARRAY_A);

        // Check if entire day is unavailable
        foreach ($exceptions as $exception) {
            if ($exception['is_unavailable']) {
                return []; // No available slots
            }
        }

        // If there are specific exceptions, use those instead of rules
        $slots_source = !empty($exceptions) ? $exceptions : $rules;

        if (empty($slots_source)) {
            return []; // No availability configured
        }

        // Generate time slots
        $time_slots = [];
        $slot_interval = 30; // 30-minute intervals
        $total_duration = $this->calculate_total_service_duration($selected_services);

        foreach ($slots_source as $slot) {
            $start_time = $slot['start_time'];
            $end_time = $slot['end_time'];

            $current_time = strtotime($date . ' ' . $start_time);
            $end_timestamp = strtotime($date . ' ' . $end_time);

            while ($current_time < $end_timestamp) {
                $end_slot_time = $current_time + ($total_duration * 60);
                // Check if there's enough time for all services
                if ($end_slot_time <= $end_timestamp) {
                    // Check if slot is not already booked
                    if ($this->is_time_slot_available($tenant_id, $date, date('H:i', $current_time), $total_duration)) {
                        $time_slots[] = [
                            'start_time' => date('H:i', $current_time),
                            'end_time'   => date('H:i', $end_slot_time),
                            'display'    => date('g:i A', $current_time) . ' until ' . date('g:i A', $end_slot_time)
                        ];
                    }
                }

                $current_time += ($slot_interval * 60); // Add interval in seconds
            }
        }

        return $time_slots;
    }

    /**
     * Calculate total duration of selected services
     */
    private function calculate_total_service_duration($service_ids) {
        if (empty($service_ids)) {
            return 60; // Default duration
        }

        // Ensure all IDs are integers
        $service_ids_int = array_map('intval', $service_ids);
        $ids_string = implode(',', $service_ids_int);

        if (empty($ids_string)) {
            return 60; // Return default if array was empty or contained non-numeric values
        }

        $services_table = Database::get_table_name('services');

        $sql = "SELECT SUM(duration) FROM $services_table WHERE service_id IN ($ids_string)";

        $total_duration = $this->wpdb->get_var($sql);

        return $total_duration > 0 ? intval($total_duration) : 60;
    }

    /**
     * Check if a time slot is available (not already booked)
     */
    private function is_time_slot_available($tenant_id, $date, $time, $duration_minutes) {
        $bookings_table = Database::get_table_name('bookings');
        
        // Get booking end time
        $booking_start = strtotime($date . ' ' . $time);
        $booking_end = $booking_start + ($duration_minutes * 60);

        // Check for overlapping bookings
        $existing_bookings = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT booking_time, total_duration 
             FROM $bookings_table 
             WHERE user_id = %d AND booking_date = %s 
             AND status IN ('pending', 'confirmed') 
             AND booking_time IS NOT NULL AND total_duration IS NOT NULL",
            $tenant_id, $date
        ), ARRAY_A);

        foreach ($existing_bookings as $booking) {
            $existing_start = strtotime($date . ' ' . $booking['booking_time']);
            $existing_end = $existing_start + (intval($booking['total_duration']) * 60);

            // Check for overlap
            if (($booking_start < $existing_end) && ($booking_end > $existing_start)) {
                return false; // Time slot is not available
            }
        }

        return true; // Time slot is available
    }

    /**
     * Validate discount code
     */
    private function validate_discount($discount, $subtotal) {
        // Check if discount is expired
        if (!empty($discount['expiry_date'])) {
            $expiry_date = strtotime($discount['expiry_date']);
            if ($expiry_date < time()) {
                return new \WP_Error('discount_expired', __('This discount code has expired.', 'mobooking'));
            }
        }

        // Check usage limit
        if (!empty($discount['usage_limit'])) {
            if (intval($discount['times_used']) >= intval($discount['usage_limit'])) {
                return new \WP_Error('discount_limit_reached', __('This discount code has reached its usage limit.', 'mobooking'));
            }
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    private function calculate_discount_amount($discount, $subtotal) {
        $discount_value = floatval($discount['value']);

        if ($discount['type'] === 'percentage') {
            return ($subtotal * $discount_value) / 100;
        } else {
            // Fixed amount
            return min($discount_value, $subtotal); // Don't exceed subtotal
        }
    }

    /**
     * Validate booking data
     */
    private function validate_booking_data($booking_data) {
        $required_fields = [
            'tenant_id', 'customer', 'services', 'pricing'
        ];

        foreach ($required_fields as $field) {
            if (empty($booking_data[$field])) {
                return new \WP_Error('missing_field', sprintf(__('Required field "%s" is missing.', 'mobooking'), $field));
            }
        }

        // Validate customer data
        $customer_required = ['name', 'email', 'phone', 'address', 'date', 'time'];
        foreach ($customer_required as $field) {
            if (empty($booking_data['customer'][$field])) {
                return new \WP_Error('missing_customer_field', sprintf(__('Customer field "%s" is required.', 'mobooking'), $field));
            }
        }

        // Validate email
        if (!is_email($booking_data['customer']['email'])) {
            return new \WP_Error('invalid_email', __('Invalid email address.', 'mobooking'));
        }

        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_data['customer']['date'])) {
            return new \WP_Error('invalid_date', __('Invalid date format.', 'mobooking'));
        }

        // Validate time
        if (!preg_match('/^\d{2}:\d{2}$/', $booking_data['customer']['time'])) {
            return new \WP_Error('invalid_time', __('Invalid time format.', 'mobooking'));
        }

        // Validate services
        if (empty($booking_data['services']) || !is_array($booking_data['services'])) {
            return new \WP_Error('no_services', __('At least one service must be selected.', 'mobooking'));
        }

        return true;
    }

    /**
     * Create or get existing customer
     */
    private function create_or_get_customer($customer_data) {
        $customers_table = Database::get_table_name('customers');

        // Check if customer already exists
        $existing_customer = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT customer_id FROM $customers_table WHERE email = %s",
            $customer_data['email']
        ), ARRAY_A);

        if ($existing_customer) {
            // Update existing customer data
            $this->wpdb->update(
                $customers_table,
                [
                    'name' => sanitize_text_field($customer_data['name']),
                    'phone' => sanitize_text_field($customer_data['phone']),
                    'updated_at' => current_time('mysql', 1)
                ],
                ['customer_id' => $existing_customer['customer_id']],
                ['%s', '%s', '%s'],
                ['%d']
            );

            return ['customer_id' => intval($existing_customer['customer_id'])];
        } else {
            // Create new customer
            $inserted = $this->wpdb->insert(
                $customers_table,
                [
                    'name' => sanitize_text_field($customer_data['name']),
                    'email' => sanitize_email($customer_data['email']),
                    'phone' => sanitize_text_field($customer_data['phone']),
                    'created_at' => current_time('mysql', 1),
                    'updated_at' => current_time('mysql', 1)
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );

            if (!$inserted) {
                return new \WP_Error('customer_creation_failed', __('Failed to create customer record.', 'mobooking'));
            }

            return ['customer_id' => $this->wpdb->insert_id];
        }
    }

    /**
     * Generate unique booking reference
     */
    private function generate_booking_reference() {
        $prefix = 'MB';
        $date_part = date('ymd');
        $random_part = strtoupper(wp_generate_password(4, false));

        return $prefix . $date_part . $random_part;
    }

    /**
     * Create booking items for services
     */
    private function create_booking_items($booking_id, $services) {
        $booking_items_table = Database::get_table_name('booking_items');

        foreach ($services as $service) {
            // Calculate item total price including options
            $item_total = floatval($service['price']);
            $configured_options = $service['configured_options'] ?? [];

            // Add option prices
            foreach ($configured_options as $option_id => $option_data) {
                if (is_array($option_data)) {
                    // Multiple values (checkboxes)
                    foreach ($option_data as $item) {
                        $item_total += floatval($item['price'] ?? 0);
                    }
                } else {
                    // Single value
                    $item_total += floatval($option_data['price'] ?? 0);
                }
            }

            $inserted = $this->wpdb->insert(
                $booking_items_table,
                [
                    'booking_id' => intval($booking_id),
                    'service_id' => intval($service['service_id']),
                    'service_name' => sanitize_text_field($service['name']),
                    'service_price' => floatval($service['price']),
                    'quantity' => 1,
                    'selected_options' => wp_json_encode($configured_options),
                    'item_total_price' => $item_total
                ],
                ['%d', '%d', '%s', '%f', '%d', '%s', '%f']
            );

            if (!$inserted) {
                return new \WP_Error('booking_item_creation_failed', __('Failed to create booking items.', 'mobooking'));
            }
        }

        return true;
    }

    /**
     * Update discount usage count
     */
    private function update_discount_usage($discount_id) {
        $discounts_table = Database::get_table_name('discounts');
        
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE $discounts_table SET times_used = times_used + 1 WHERE discount_id = %d",
            $discount_id
        ));
    }

    /**
     * Send booking confirmation notifications
     */
    private function send_booking_notifications($booking_id, $booking_data) {
        try {
            // Get business settings for email templates
            global $mobooking_settings_manager;
            $business_settings = $mobooking_settings_manager->get_business_settings($booking_data['tenant_id']);

            // Prepare email variables
            $email_vars = [
                'booking_reference' => $this->generate_booking_reference(),
                'customer_name' => $booking_data['customer']['name'],
                'customer_email' => $booking_data['customer']['email'],
                'customer_phone' => $booking_data['customer']['phone'],
                'service_names' => implode(', ', array_column($booking_data['services'], 'name')),
                'booking_date_time' => date('F j, Y', strtotime($booking_data['customer']['date'])) . ' at ' . date('g:i A', strtotime($booking_data['customer']['time'])),
                'service_address' => $booking_data['customer']['address'],
                'total_price' => number_format($booking_data['pricing']['total'], 2),
                'special_instructions' => $booking_data['customer']['instructions'] ?? 'None',
                'business_name' => $business_settings['biz_name'] ?? 'MoBooking',
                'admin_booking_link' => admin_url('admin.php?page=mobooking-bookings&booking_id=' . $booking_id)
            ];

            // Send customer confirmation email
            $this->send_customer_confirmation_email($booking_data['customer']['email'], $email_vars, $business_settings);

            // Send admin notification email
            $admin_email = $business_settings['biz_email'] ?? get_option('admin_email');
            $this->send_admin_notification_email($admin_email, $email_vars, $business_settings);

        } catch (Exception $e) {
            error_log('MoBooking - Notification sending error: ' . $e->getMessage());
            // Don't fail the booking if email sending fails
        }
    }

    /**
     * Send customer confirmation email
     */
    private function send_customer_confirmation_email($customer_email, $email_vars, $business_settings) {
        $subject_template = $business_settings['email_booking_conf_subj_customer'] ?? 'Booking Confirmation - {{booking_reference}}';
        $body_template = $business_settings['email_booking_conf_body_customer'] ?? 'Your booking has been confirmed.

Booking Details:
Reference: {{booking_reference}}
Services: {{service_names}}
Date & Time: {{booking_date_time}}
Address: {{service_address}}
Total: {{total_price}}

Thank you for choosing {{business_name}}!';

        $subject = $this->replace_email_variables($subject_template, $email_vars);
        $body = $this->replace_email_variables($body_template, $email_vars);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($business_settings['biz_name'] ?? 'MoBooking') . ' <' . ($business_settings['biz_email'] ?? get_option('admin_email')) . '>'
        ];

        wp_mail($customer_email, $subject, nl2br($body), $headers);
    }

    /**
     * Send admin notification email
     */
    private function send_admin_notification_email($admin_email, $email_vars, $business_settings) {
        $subject_template = $business_settings['email_booking_conf_subj_admin'] ?? 'New Booking Received - {{booking_reference}}';
        $body_template = $business_settings['email_booking_conf_body_admin'] ?? 'New booking received from {{customer_name}}.

Customer Details:
Name: {{customer_name}}
Email: {{customer_email}}
Phone: {{customer_phone}}

Booking Details:
Reference: {{booking_reference}}
Services: {{service_names}}
Date & Time: {{booking_date_time}}
Address: {{service_address}}
Total: {{total_price}}
Instructions: {{special_instructions}}

View booking: {{admin_booking_link}}';

        $subject = $this->replace_email_variables($subject_template, $email_vars);
        $body = $this->replace_email_variables($body_template, $email_vars);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $email_vars['customer_email']
        ];

        wp_mail($admin_email, $subject, nl2br($body), $headers);
    }

    /**
     * Replace email template variables
     */
    private function replace_email_variables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Safely decode JSON with enhanced error handling
     *
     * @param string $json_string The JSON string to decode
     * @param string $data_type Description of what data is being decoded (for error messages)
     * @return array|WP_Error Decoded array on success, WP_Error on failure
     */
    private function safe_json_decode($json_string, $data_type = 'data') {
        if (empty($json_string)) {
            return new \WP_Error('empty_json', sprintf(__('Empty data provided for %s', 'mobooking'), $data_type));
        }

        // Clean the JSON string first
        $json_string = $this->clean_json_string($json_string);

        // Log the cleaned JSON for debugging
        error_log("MoBooking - Decoding {$data_type}: " . substr($json_string, 0, 300) . (strlen($json_string) > 300 ? '...' : ''));

        // Try to decode
        $decoded = json_decode($json_string, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If initial decode fails, try common fixes
        $error_msg = json_last_error_msg();
        error_log("MoBooking - Initial JSON decode failed for {$data_type}. Error: " . $error_msg);

        // Try with stripslashes
        $stripped_json = stripslashes($json_string);
        $decoded = json_decode($stripped_json, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            error_log("MoBooking - JSON decode successful after stripslashes for {$data_type}");
            return $decoded;
        }

        // Try with recursive stripslashes (for double encoding)
        $double_stripped = stripslashes(stripslashes($json_string));
        $decoded = json_decode($double_stripped, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            error_log("MoBooking - JSON decode successful after double stripslashes for {$data_type}");
            return $decoded;
        }

        // Final attempt: try to parse URL-decoded string
        $url_decoded = urldecode($json_string);
        $decoded = json_decode($url_decoded, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            error_log("MoBooking - JSON decode successful after URL decode for {$data_type}");
            return $decoded;
        }

        // If all attempts fail, return comprehensive error
        $final_error = sprintf(
            __('Failed to decode JSON for %s. Original error: %s', 'mobooking'),
            $data_type,
            $error_msg
        );

        error_log("MoBooking - " . $final_error);
        error_log("MoBooking - Raw JSON string (first 500 chars): " . substr($json_string, 0, 500));

        return new \WP_Error('json_decode_error', $final_error);
    }

    /**
     * Manually clean JSON string to fix common issues
     *
     * @param string $json_string
     * @return string
     */
    private function clean_json_string($json_string) {
        // Remove BOM if present
        $json_string = preg_replace('/^\xEF\xBB\xBF/', '', $json_string);

        // Remove null bytes
        $json_string = str_replace("\0", '', $json_string);

        // Trim whitespace
        $json_string = trim($json_string);

        // Remove control characters but preserve needed escapes
        $json_string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $json_string);

        return $json_string;
    }
}
