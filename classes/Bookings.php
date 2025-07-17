<?php
namespace MoBooking\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Bookings {
    private $wpdb;
    private $discounts_manager;
    private $notifications_manager;
    private $services_manager;

    public function __construct(Discounts $discounts_manager, Notifications $notifications_manager, Services $services_manager) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->discounts_manager = $discounts_manager;
        $this->notifications_manager = $notifications_manager;
        $this->services_manager = $services_manager;
    }

    public function register_ajax_actions() {
        // Public booking form
        add_action('wp_ajax_nopriv_mobooking_create_booking', [$this, 'handle_create_booking_public_ajax']);
        add_action('wp_ajax_mobooking_create_booking', [$this, 'handle_create_booking_public_ajax']);

        // Tenant Dashboard - Bookings section
        add_action('wp_ajax_mobooking_get_tenant_bookings', [$this, 'handle_get_tenant_bookings_ajax']);
        add_action('wp_ajax_mobooking_get_tenant_booking_details', [$this, 'handle_get_tenant_booking_details_ajax']);
        add_action('wp_ajax_mobooking_update_booking_status', [$this, 'handle_update_booking_status_ajax']);
        add_action('wp_ajax_mobooking_get_dashboard_overview_data', [$this, 'handle_get_dashboard_overview_data_ajax']);

        // New AJAX actions for dashboard CRUD
        add_action('wp_ajax_mobooking_create_dashboard_booking', [$this, 'handle_create_dashboard_booking_ajax']);
        add_action('wp_ajax_mobooking_update_dashboard_booking_fields', [$this, 'handle_update_dashboard_booking_fields_ajax']);
        add_action('wp_ajax_mobooking_delete_dashboard_booking', [$this, 'handle_delete_dashboard_booking_ajax']);
        add_action('wp_ajax_mobooking_assign_staff_to_booking', [$this, 'handle_assign_staff_to_booking_ajax']);
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
        return new \WP_Error('empty_json', __('Empty JSON string provided for ' . $data_type, 'mobooking'));
    }

    // Log the original JSON for debugging
    error_log("MoBooking - Decoding {$data_type}: " . substr($json_string, 0, 200) . (strlen($json_string) > 200 ? '...' : ''));

    // First, try to decode as-is
    $decoded = json_decode($json_string, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // If that fails, try to fix common issues
    error_log("MoBooking - Initial JSON decode failed for {$data_type}. Error: " . json_last_error_msg());

    // Try to fix escaped quotes and other common issues
    $fixed_json = str_replace(['\\"', "\\'"], ['"', "'"], $json_string);
    $decoded = json_decode($fixed_json, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        error_log("MoBooking - JSON decode successful after fixing quotes for {$data_type}");
        return $decoded;
    }

    // Try stripslashes if it's still failing
    $stripped_json = stripslashes($json_string);
    $decoded = json_decode($stripped_json, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        error_log("MoBooking - JSON decode successful after stripslashes for {$data_type}");
        return $decoded;
    }

    // If all attempts fail, return error
    $error_message = sprintf(
        __('Failed to decode JSON for %s. Error: %s', 'mobooking'),
        $data_type,
        json_last_error_msg()
    );
    
    error_log("MoBooking - " . $error_message);
    return new \WP_Error('json_decode_error', $error_message);
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
        
        // Fix common escaping issues
        $json_string = str_replace(['\\"', "\\'"], ['"', "'"], $json_string);
        
        // Remove control characters
        $json_string = preg_replace('/[\x00-\x1F\x7F]/', '', $json_string);
        
        // Fix double encoding
        if (strpos($json_string, '\\u') !== false) {
            $decoded = json_decode('"' . $json_string . '"');
            if ($decoded !== null) {
                $json_string = $decoded;
            }
        }
        
        return trim($json_string);
    }

public function handle_create_booking_public_ajax() {
    // Security check - verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce')) {
        error_log('MoBooking - Nonce verification failed');
        wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'mobooking')], 403);
        return;
    }

    error_log('MoBooking - Processing booking submission: ' . print_r($_POST, true));

    // Get and validate tenant ID
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    if (!$tenant_id || !get_userdata($tenant_id)) {
        error_log('MoBooking - Invalid tenant ID: ' . $tenant_id);
        wp_send_json_error(['message' => __('Invalid tenant ID.', 'mobooking')], 400);
        return;
    }

    // Get and validate required JSON data
    $selected_services_json = isset($_POST['selected_services']) ? stripslashes_deep($_POST['selected_services']) : '';
    $customer_details_json = isset($_POST['customer_details']) ? stripslashes_deep($_POST['customer_details']) : '';
    $discount_info_json = isset($_POST['discount_info']) ? stripslashes_deep($_POST['discount_info']) : '';
    $pricing_json = isset($_POST['pricing']) ? stripslashes_deep($_POST['pricing']) : '';

    // Decode JSON data with error handling
    $selected_services = $this->safe_json_decode($selected_services_json, 'selected_services');
    if (is_wp_error($selected_services)) {
        error_log('MoBooking - Selected services JSON decode error: ' . $selected_services->get_error_message());
        wp_send_json_error(['message' => __('Invalid services data.', 'mobooking')], 400);
        return;
    }

    $customer_details = $this->safe_json_decode($customer_details_json, 'customer_details');
    if (is_wp_error($customer_details)) {
        error_log('MoBooking - Customer details JSON decode error: ' . $customer_details->get_error_message());
        wp_send_json_error(['message' => __('Invalid customer data.', 'mobooking')], 400);
        return;
    }

    // Decode optional data
    $discount_info = null;
    if (!empty($discount_info_json)) {
        $discount_info = $this->safe_json_decode($discount_info_json, 'discount_info');
        if (is_wp_error($discount_info)) {
            error_log('MoBooking - Discount info JSON decode error: ' . $discount_info->get_error_message());
            $discount_info = null; // Continue without discount if invalid
        }
    }

    $pricing_info = null;
    if (!empty($pricing_json)) {
        $pricing_info = $this->safe_json_decode($pricing_json, 'pricing');
        if (is_wp_error($pricing_info)) {
            error_log('MoBooking - Pricing info JSON decode error: ' . $pricing_info->get_error_message());
            $pricing_info = null; // Continue without pricing if invalid
        }
    }

    // Get optional location data
    $zip_code = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
    $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';

    // Validate required data
    if (empty($selected_services) || !is_array($selected_services)) {
        wp_send_json_error(['message' => __('No services selected.', 'mobooking')], 400);
        return;
    }

    if (empty($customer_details) || !is_array($customer_details)) {
        wp_send_json_error(['message' => __('Customer information is required.', 'mobooking')], 400);
        return;
    }

    // Validate customer details
    $required_fields = ['name', 'email', 'phone', 'address', 'date', 'time'];
    foreach ($required_fields as $field) {
        if (empty($customer_details[$field])) {
            wp_send_json_error(['message' => sprintf(__('Missing required field: %s', 'mobooking'), $field)], 400);
            return;
        }
    }

    // Validate email format
    if (!is_email($customer_details['email'])) {
        wp_send_json_error(['message' => __('Invalid email address.', 'mobooking')], 400);
        return;
    }

    // Prepare the payload for create_booking method
    $payload = [
        'selected_services' => $selected_services,
        'customer' => $customer_details,
        'discount_info' => $discount_info,
        'zip_code' => $zip_code,
        'country_code' => $country_code,
        'pricing' => $pricing_info
    ];

    error_log('MoBooking - Processed payload: ' . print_r($payload, true));

    // Call the main create_booking method
    $result = $this->create_booking($tenant_id, $payload);

    if (is_wp_error($result)) {
        error_log('MoBooking - Booking creation failed: ' . $result->get_error_message());
        wp_send_json_error(['message' => $result->get_error_message()], 400);
    } else {
        error_log('MoBooking - Booking created successfully: ' . print_r($result, true));
        
        // Format success message
        $success_message = !empty($result['message']) ? 
            sprintf($result['message'], $result['booking_reference']) : 
            __('Booking created successfully!', 'mobooking');
        
        wp_send_json_success([
            'message' => $success_message,
            'booking_id' => $result['booking_id'],
            'booking_reference' => $result['booking_reference'],
            'final_total' => $result['final_total']
        ]);
    }
}


    public function get_kpi_data(int $tenant_user_id) {
        if (empty($tenant_user_id)) {
            return ['bookings_month' => 0, 'revenue_month' => 0, 'upcoming_count' => 0];
        }
        $bookings_table = Database::get_table_name('bookings');
        $current_month_start = current_time('Y-m-01');
        $current_month_end = current_time('Y-m-t');
        $today = current_time('Y-m-d');

        // Bookings this month (confirmed or completed)
        $bookings_month = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(booking_id) FROM $bookings_table
             WHERE user_id = %d AND status IN ('confirmed', 'completed')
             AND booking_date BETWEEN %s AND %s",
            $tenant_user_id, $current_month_start, $current_month_end
        ));

        // Revenue this month
        $revenue_month = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(total_price) FROM $bookings_table
             WHERE user_id = %d AND status IN ('confirmed', 'completed')
             AND booking_date BETWEEN %s AND %s",
            $tenant_user_id, $current_month_start, $current_month_end
        ));

        // Upcoming bookings (from today onwards, pending or confirmed)
        $upcoming_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(booking_id) FROM $bookings_table
             WHERE user_id = %d AND status IN ('pending', 'confirmed')
             AND booking_date >= %s",
            $tenant_user_id, $today
        ));

        return [
            'bookings_month' => intval($bookings_month),
            'revenue_month' => floatval($revenue_month),
            'upcoming_count' => intval($upcoming_count)
        ];
    }

    public function create_booking(int $tenant_user_id, array $payload) {
        try {
            error_log('MoBooking - create_booking called with tenant_id: ' . $tenant_user_id);
            
            if (empty($tenant_user_id)) {
                return new \WP_Error('invalid_tenant', __('Tenant user ID is required.', 'mobooking'));
            }

            // Extract data from payload
            $selected_service_items = $payload['selected_services'] ?? [];
            $customer = $payload['customer'] ?? [];
            $discount_info = $payload['discount_info'] ?? null;

            if (empty($selected_service_items) || empty($customer)) {
                return new \WP_Error('missing_data', __('Selected services and customer data are required.', 'mobooking'));
            }

            // Validate and process services
            $calculated_service_items = [];
            $subtotal_server = 0;

            foreach ($selected_service_items as $item) {
                $service_id = intval($item['service_id'] ?? 0);
                if (!$service_id) continue;

                // Get service details from database
                //$service_details = $this->services_manager->get_service_by_id($service_id, $tenant_user_id);
                $service_details = $this->services_manager->get_service($service_id, $tenant_user_id);
                if (!$service_details) {
                    return new \WP_Error('invalid_service', __('Invalid service selected.', 'mobooking'));
                }

                $service_price = floatval($service_details['price']);
                $configured_options = $item['configured_options'] ?? [];

                // Calculate options price
                $options_total = 0;
                $selected_options_summary = [];

                if (!empty($configured_options)) {
                    // Process service options if they exist
                    // This would integrate with your ServiceOptions class
                    foreach ($configured_options as $option_id => $option_value) {
                        // Add option processing logic here
                        $selected_options_summary[] = [
                            'name' => 'Option ' . $option_id,
                            'value' => $option_value
                        ];
                    }
                }

                $item_total = $service_price + $options_total;
                $subtotal_server += $item_total;

                $calculated_service_items[] = [
                    'service_id' => $service_id,
                    'service_name' => $service_details['name'],
                    'service_price' => $service_price,
                    'selected_options_summary' => $selected_options_summary,
                    'options_total_price' => $options_total,
                    'item_total_price' => $item_total
                ];
            }

            // Validate discount if provided
            $discount_amount = 0;
            $validated_discount_info = null;

            if ($discount_info && !empty($discount_info['code'])) {
                $validation_result = $this->discounts_manager->validate_discount_code(
                    $discount_info['code'], 
                    $tenant_user_id, 
                    $subtotal_server
                );
                
                if (!is_wp_error($validation_result)) {
                    $discount_amount = $validation_result['discount_amount'];
                    $validated_discount_info = $validation_result;
                } else {
                    error_log('MoBooking - Discount validation failed: ' . $validation_result->get_error_message());
                }
            }

            $final_total_server = max(0, $subtotal_server - $discount_amount);

            // Generate booking reference
            $booking_reference = 'MB-' . strtoupper(wp_generate_password(8, false));

            // Prepare booking data for database
            $booking_data = [
                'user_id' => $tenant_user_id,
                'booking_reference' => $booking_reference,
                'customer_name' => sanitize_text_field($customer['name']),
                'customer_email' => sanitize_email($customer['email']),
                'customer_phone' => sanitize_text_field($customer['phone']),
                'service_address' => sanitize_textarea_field($customer['address']),
                'booking_date' => sanitize_text_field($customer['date']),
                'booking_time' => sanitize_text_field($customer['time']),
                'special_instructions' => sanitize_textarea_field($customer['instructions'] ?? ''),
                // REMOVED: 'selected_services' - this column doesn't exist in the schema
                // REMOVED: 'subtotal_price' - this column doesn't exist in the schema
                'discount_amount' => $discount_amount,
                'total_price' => $final_total_server,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'assigned_staff_id' => isset($payload['assigned_staff_id']) && !empty($payload['assigned_staff_id']) ? intval($payload['assigned_staff_id']) : null
            ];


            // Insert booking into database
            $bookings_table = Database::get_table_name('bookings');
            $inserted = $this->wpdb->insert($bookings_table, $booking_data);

            if (false === $inserted) {
                error_log('MoBooking - Database insert failed: ' . $this->wpdb->last_error);
                return new \WP_Error('db_booking_error', __('Could not save booking to database.', 'mobooking'));
            }

            $new_booking_id = $this->wpdb->insert_id;
            error_log('MoBooking - Booking saved with ID: ' . $new_booking_id);

// Now insert booking items into the booking_items table
$booking_items_table = Database::get_table_name('booking_items');
foreach ($calculated_service_items as $service_item) {
    $item_data = [
        'booking_id' => $new_booking_id,
        'service_id' => $service_item['service_id'],
        'service_name' => $service_item['service_name'],
        'service_price' => $service_item['service_price'],
        'quantity' => 1, // Default quantity
        'selected_options' => wp_json_encode($service_item['selected_options_summary']),
        'item_total_price' => $service_item['item_total_price']
    ];
    
    $item_inserted = $this->wpdb->insert($booking_items_table, $item_data);
    
    if (false === $item_inserted) {
        error_log('MoBooking - Failed to insert booking item: ' . $this->wpdb->last_error);
        // You might want to handle this error more gracefully
    }
}



            // Update customer records (if Customers class exists)
            if (class_exists('MoBooking\Classes\Customers')) {
                try {
                    $customers_manager = new \MoBooking\Classes\Customers();
                    // Prepare customer data array with keys expected by Customers::create_or_update_customer_for_booking
                    $customer_data_for_manager = [
                        'full_name' => $customer['name'] ?? '',
                        'email' => $customer['email'] ?? '',
                        'phone_number' => $customer['phone'] ?? '',
                        'address_line_1' => $customer['address'] ?? '',
                        // Add other fields if they are available in $customer and expected by create_or_update_customer_for_booking
                        // e.g., 'city', 'state', 'zip_code', 'country'
                        // For now, mapping based on available $customer fields from logs.
                    ];

                    $mob_customer_id = $customers_manager->create_or_update_customer_for_booking(
                        $tenant_user_id,
                        $customer_data_for_manager
                    );
                    
                    if (!is_wp_error($mob_customer_id) && $mob_customer_id > 0) {
                        $customers_manager->update_customer_booking_stats($mob_customer_id, $booking_data['created_at']);
                    } else if (is_wp_error($mob_customer_id)) {
                        error_log("MoBooking: Error creating/updating customer: " . $mob_customer_id->get_error_message());
                    }
                } catch (\Exception $e) {
                    error_log("MoBooking: Error updating customer stats: " . $e->getMessage());
                }
            }

            // Update discount usage
            if ($validated_discount_info && isset($validated_discount_info['discount_id'])) {
                $this->discounts_manager->increment_discount_usage(intval($validated_discount_info['discount_id']));
            }

            // Send email notifications
            $email_services_summary_array = array_map(function($item) {
                $opt_str = "";
                if (!empty($item['selected_options_summary'])) {
                    $opt_parts = array_map(function($opt) { return "{$opt['name']}: {$opt['value']}"; }, $item['selected_options_summary']);
                    $opt_str = !empty($opt_parts) ? " (" . implode(', ', $opt_parts) . ")" : "";
                }
                return "{$item['service_name']}{$opt_str}";
            }, $calculated_service_items);

            $email_booking_details = [
                'booking_reference' => $booking_reference,
                'service_names' => implode('; ', $email_services_summary_array),
                'booking_date_time' => $customer['date'] . ' at ' . $customer['time'],
                'total_price' => $final_total_server,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'service_address' => $customer['address'],
                'special_instructions' => $customer['instructions'] ?? '',
                'admin_booking_link' => admin_url('admin.php?page=mobooking-bookings&booking_id=' . $new_booking_id)
            ];

            $this->notifications_manager->send_booking_confirmation_customer($email_booking_details, $customer['email'], $tenant_user_id);
            $this->notifications_manager->send_booking_confirmation_admin($email_booking_details, $tenant_user_id);

            return [
                'booking_id' => $new_booking_id, 
                'booking_reference' => $booking_reference,
                'final_total' => $final_total_server,
                'message' => __('Booking confirmed! Your reference is %s.', 'mobooking')
            ];

        } catch (\Exception $e) {
             error_log("MoBooking Create Booking Exception: " . $e->getMessage());
            return new \WP_Error('booking_creation_exception', $e->getMessage());
        }
    }

    // Dashboard AJAX handlers (keeping existing methods)
    public function handle_get_tenant_bookings_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? max(5, min(100, intval($_POST['per_page']))) : 10;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status_filter = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $result = $this->get_tenant_bookings($user_id, $page, $per_page, $search, $status_filter);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    public function get_tenant_bookings(int $tenant_user_id, int $page = 1, int $per_page = 10, string $search = '', string $status_filter = '') {
        $bookings_table = Database::get_table_name('bookings');
        $offset = ($page - 1) * $per_page;

        $where_conditions = ['user_id = %d'];
        $where_values = [$tenant_user_id];

        if (!empty($search)) {
            $where_conditions[] = "(customer_name LIKE %s OR customer_email LIKE %s OR booking_reference LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        if (!empty($status_filter)) {
            $where_conditions[] = "status = %s";
            $where_values[] = $status_filter;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table $where_clause",
            $where_values
        ));

        $bookings = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $bookings_table $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($where_values, [$per_page, $offset])
        ), ARRAY_A);

        if (!empty($bookings)) {
            foreach ($bookings as &$booking_item) {
                if (!empty($booking_item['assigned_staff_id'])) {
                    $staff_user = get_userdata($booking_item['assigned_staff_id']);
                    $booking_item['assigned_staff_name'] = $staff_user ? $staff_user->display_name : __('Unknown Staff', 'mobooking');
                } else {
                    $booking_item['assigned_staff_name'] = __('Unassigned', 'mobooking');
                }
            }
        }

        return [
            'bookings' => $bookings ?: [],
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    /**
     * Get bookings for a tenant with the expected dashboard interface
     * This method matches the signature expected by dashboard/page-bookings.php
     */
    public function get_bookings_by_tenant(int $current_logged_in_user_id, array $args = []) {
        if (empty($current_logged_in_user_id)) {
            return ['bookings' => [], 'total_count' => 0, 'per_page' => 20, 'current_page' => 1];
        }

        // Handle worker/owner relationship
        $user_to_fetch_for_id = $current_logged_in_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_logged_in_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_logged_in_user_id);
            if ($owner_id) {
                $user_to_fetch_for_id = $owner_id;
                error_log("[MoBooking Bookings->get_bookings_by_tenant] Worker {$current_logged_in_user_id} associated with owner {$owner_id}. Querying for owner_id: {$owner_id}");
            } else {
                error_log("[MoBooking Bookings->get_bookings_by_tenant] Worker {$current_logged_in_user_id} has no associated owner. Cannot fetch bookings.");
                return ['bookings' => [], 'total_count' => 0, 'per_page' => 20, 'current_page' => 1];
            }
        }

        // Extract arguments with defaults
        $limit = isset($args['limit']) ? max(1, intval($args['limit'])) : 20;
        $paged = isset($args['paged']) ? max(1, intval($args['paged'])) : 1;
        $orderby = isset($args['orderby']) ? sanitize_key($args['orderby']) : 'booking_date';
        $order = isset($args['order']) ? strtoupper(sanitize_key($args['order'])) : 'DESC';
        $status_filter = isset($args['status']) ? sanitize_text_field($args['status']) : '';
        $search_query = isset($args['search_query']) ? sanitize_text_field($args['search_query']) : '';
        $date_from = isset($args['date_from']) ? sanitize_text_field($args['date_from']) : '';
        $date_to = isset($args['date_to']) ? sanitize_text_field($args['date_to']) : '';
        $assigned_staff_id_filter = isset($args['assigned_staff_id_filter']) ? $args['assigned_staff_id_filter'] : null; // Null for all, '0' for unassigned, or specific ID
        $filter_by_exactly_assigned_staff_id = isset($args['filter_by_exactly_assigned_staff_id']) ? intval($args['filter_by_exactly_assigned_staff_id']) : null;


        // Validate order direction
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        // Validate orderby field
        $allowed_orderby = ['booking_date', 'created_at', 'customer_name', 'total_price', 'status'];
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'booking_date';
        }

        $bookings_table = Database::get_table_name('bookings');
        $offset = ($paged - 1) * $limit;

        // Build WHERE conditions
        $where_conditions = ['user_id = %d'];
        $where_values = [$user_to_fetch_for_id];

        // Status filter
        if (!empty($status_filter)) {
            $where_conditions[] = "status = %s";
            $where_values[] = $status_filter;
        }

        // Search query (customer name, email, or booking reference)
        if (!empty($search_query)) {
            $where_conditions[] = "(customer_name LIKE %s OR customer_email LIKE %s OR booking_reference LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($search_query) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Date range filters
        if (!empty($date_from)) {
            $where_conditions[] = "booking_date >= %s";
            $where_values[] = $date_from;
        }

        if (!empty($date_to)) {
            $where_conditions[] = "booking_date <= %s";
            $where_values[] = $date_to;
        }

        // Assigned staff filter
        if ($filter_by_exactly_assigned_staff_id !== null && $filter_by_exactly_assigned_staff_id > 0) {
            // This filter takes precedence for the "My Assigned Bookings" page
            $where_conditions[] = "assigned_staff_id = %d";
            $where_values[] = $filter_by_exactly_assigned_staff_id;
        } elseif ($assigned_staff_id_filter !== null && $assigned_staff_id_filter !== '') {
            // This is the general filter from the main bookings list
            if ($assigned_staff_id_filter === '0') { // Specifically check for '0' for unassigned
                $where_conditions[] = "assigned_staff_id IS NULL";
            } else {
                $where_conditions[] = "assigned_staff_id = %d";
                $where_values[] = intval($assigned_staff_id_filter);
            }
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get total count for pagination
        $total_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table $where_clause",
            $where_values
        ));

        // Get bookings with pagination
        $bookings = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $bookings_table $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d",
            array_merge($where_values, [$limit, $offset])
        ), ARRAY_A);

        // Process booking items if they exist
        if (!empty($bookings)) {
            $items_table = Database::get_table_name('booking_items');
            
            foreach ($bookings as &$booking) {
                // Fetch assigned staff member display name
                if (!empty($booking['assigned_staff_id'])) {
                    $staff_user = get_userdata($booking['assigned_staff_id']);
                    $booking['assigned_staff_name'] = $staff_user ? $staff_user->display_name : __('Unknown Staff', 'mobooking');
                } else {
                    $booking['assigned_staff_name'] = __('Unassigned', 'mobooking');
                }

                // Try to get booking items from separate table first
                $booking_items = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT * FROM $items_table WHERE booking_id = %d ORDER BY item_id ASC", 
                    $booking['booking_id']
                ), ARRAY_A);

                if ($booking_items) {
                    // Process items from separate table
                    $booking['items'] = [];
                    foreach ($booking_items as $item) {
                        $item['selected_options'] = json_decode($item['selected_options'], true);
                        if (!is_array($item['selected_options'])) {
                            $item['selected_options'] = [];
                        }
                        $booking['items'][] = $item;
                    }
                } else {
                    // Fallback to selected_services field if booking_items table is empty
                    if (!empty($booking['selected_services'])) {
                        $selected_services = json_decode($booking['selected_services'], true);
                        $booking['items'] = is_array($selected_services) ? $selected_services : [];
                    } else {
                        $booking['items'] = [];
                    }
                }
            }
        }

        return [
            'bookings' => $bookings ?: [],
            'total_count' => intval($total_count),
            'per_page' => $limit,
            'current_page' => $paged,
            'total_pages' => ceil($total_count / $limit)
        ];
    }

    /**
     * Get a single booking by ID with proper authorization
     * This method matches the signature expected by dashboard templates
     */
    public function get_booking(int $booking_id, int $current_logged_in_user_id) {
        if (empty($booking_id) || empty($current_logged_in_user_id)) {
            return null;
        }

        // Handle worker/owner relationship
        $user_id_to_query_for = $current_logged_in_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_logged_in_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_logged_in_user_id);
            if ($owner_id) {
                $user_id_to_query_for = $owner_id;
                error_log("[MoBooking Bookings->get_booking] Worker {$current_logged_in_user_id} associated with owner {$owner_id}. Querying for owner_id: {$owner_id}");
            } else {
                error_log("[MoBooking Bookings->get_booking] Worker {$current_logged_in_user_id} has no associated owner. Cannot fetch booking {$booking_id}.");
                return null;
            }
        }

        $bookings_table = Database::get_table_name('bookings');
        $booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE booking_id = %d AND user_id = %d",
            $booking_id, $user_id_to_query_for
        ), ARRAY_A);

        if ($booking) {
            error_log("[MoBooking Bookings->get_booking] Booking {$booking_id} found for user_id_to_query_for: {$user_id_to_query_for}.");

            // Fetch assigned staff member display name
            if (!empty($booking['assigned_staff_id'])) {
                $staff_user = get_userdata($booking['assigned_staff_id']);
                $booking['assigned_staff_name'] = $staff_user ? $staff_user->display_name : __('Unknown Staff', 'mobooking');
            } else {
                $booking['assigned_staff_name'] = __('Unassigned', 'mobooking');
            }
            
            // Try to get booking items from separate table first
            $items_table = Database::get_table_name('booking_items');
            $booking_items_raw = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM $items_table WHERE booking_id = %d ORDER BY item_id ASC", 
                $booking_id
            ), ARRAY_A);

            if ($booking_items_raw) {
                $booking['items'] = [];
                foreach ($booking_items_raw as $item) {
                    $item['selected_options'] = json_decode($item['selected_options'], true);
                    if (!is_array($item['selected_options'])) {
                        $item['selected_options'] = [];
                    }
                    $booking['items'][] = $item;
                }
            } else {
                // Fallback to selected_services field
                if (!empty($booking['selected_services'])) {
                    $selected_services = json_decode($booking['selected_services'], true);
                    $booking['items'] = is_array($selected_services) ? $selected_services : [];
                } else {
                    $booking['items'] = [];
                }
            }
        }

        return $booking;
    }

    public function handle_get_tenant_booking_details_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        if (!$user_id || !$booking_id) {
            wp_send_json_error(['message' => __('Invalid request.', 'mobooking')]);
            return;
        }

        $booking = $this->get_booking_by_id($booking_id, $user_id);
        if (!$booking) {
            wp_send_json_error(['message' => __('Booking not found.', 'mobooking')]);
            return;
        }

        wp_send_json_success(['booking' => $booking]);
    }

    public function get_booking_by_id(int $booking_id, int $tenant_user_id = 0) {
        $bookings_table = Database::get_table_name('bookings');
        $where_clause = 'booking_id = %d';
        $params = [$booking_id];

        if ($tenant_user_id > 0) {
            $where_clause .= ' AND user_id = %d';
            $params[] = $tenant_user_id;
        }

        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE $where_clause",
            $params
        ), ARRAY_A);
    }

    public function handle_update_booking_status_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

        if (!$user_id || !$booking_id || !$new_status) {
            wp_send_json_error(['message' => __('Invalid request parameters.', 'mobooking')]);
            return;
        }

        // Ensure all statuses from the dropdown are considered valid
        $valid_statuses = ['pending', 'confirmed', 'assigned', 'in-progress', 'completed', 'cancelled', 'on-hold', 'processing'];
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(['message' => __('Invalid status selected.', 'mobooking')]); // Slightly clearer message
            return;
        }

        // Permission check:
        // 1. Owner can always update.
        // 2. Staff can update if they have CAP_UPDATE_OWN_BOOKING_STATUS and are assigned to this booking.
        $booking_owner_id = $this->get_booking_owner_id($booking_id);
        $can_update = false;

        if ($booking_owner_id === $user_id) { // User is the owner of the business that owns the booking
            $can_update = true;
        } elseif (Auth::is_user_worker($user_id) && current_user_can(Auth::CAP_UPDATE_OWN_BOOKING_STATUS)) {
            // Check if worker is assigned to this booking
            $booking_details = $this->get_booking($booking_id, $booking_owner_id); // Fetch with owner ID to get details
            if ($booking_details && isset($booking_details['assigned_staff_id']) && (int)$booking_details['assigned_staff_id'] === $user_id) {
                $can_update = true;
            } else {
                 wp_send_json_error(['message' => __('You can only update status for bookings assigned to you.', 'mobooking')], 403);
                 return;
            }
        }

        if (!$can_update) {
            wp_send_json_error(['message' => __('You do not have permission to update this booking status.', 'mobooking')], 403);
            return;
        }

        $result = $this->update_booking_status($booking_id, $new_status, $booking_owner_id, $user_id); // Pass current user_id as updater_id
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => __('Booking status updated successfully.', 'mobooking')]);
        }
    }

    public function update_booking_status(int $booking_id, string $new_status, int $tenant_user_id, int $updated_by_user_id = 0) {
        $bookings_table = Database::get_table_name('bookings');

        if ($updated_by_user_id === 0) {
            $updated_by_user_id = get_current_user_id(); // Default to current user if not explicitly passed
        }

        // Get old status for notification
        $old_booking_data = $this->get_booking_by_id($booking_id, $tenant_user_id);
        if (!$old_booking_data) {
            return new \WP_Error('booking_not_found', __('Booking not found to update status.', 'mobooking'));
        }
        $old_status = $old_booking_data['status'];

        // Prevent update if status is the same
        if ($old_status === $new_status) {
            return true; // No change needed, not an error.
        }
        
        $updated = $this->wpdb->update(
            $bookings_table,
            ['status' => $new_status, 'updated_at' => current_time('mysql')],
            ['booking_id' => $booking_id, 'user_id' => $tenant_user_id], // Ensure user_id is tenant's ID
            ['%s', '%s'],
            ['%d', '%d']
        );

        if (false === $updated) {
            return new \WP_Error('db_update_error', __('Could not update booking status.', 'mobooking'));
        }

        // Send notification to admin
        if ($this->notifications_manager && method_exists($this->notifications_manager, 'send_admin_status_change_notification')) {
            // Fetch fresh booking details for the email, including assigned staff etc.
            $booking_details_for_email = $this->get_booking($booking_id, $tenant_user_id);
            if ($booking_details_for_email) {
                 $this->notifications_manager->send_admin_status_change_notification($booking_id, $new_status, $old_status, $booking_details_for_email, $tenant_user_id, $updated_by_user_id);
            }
        }

        return true;
    }

    public function handle_get_dashboard_overview_data_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $kpi_data = $this->get_kpi_data($user_id);
        $recent_bookings = $this->get_recent_bookings($user_id, 5);

        wp_send_json_success([
            'kpi' => $kpi_data,
            'recent_bookings' => $recent_bookings
        ]);
    }

    public function get_recent_bookings(int $tenant_user_id, int $limit = 5) {
        $bookings_table = Database::get_table_name('bookings');
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT booking_id, booking_reference, customer_name, booking_date, booking_time, status, total_price 
             FROM $bookings_table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $tenant_user_id, $limit
        ), ARRAY_A) ?: [];
    }

    // Dashboard CRUD methods
    public function handle_create_dashboard_booking_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $payload_json = isset($_POST['booking_data']) ? stripslashes_deep($_POST['booking_data']) : '';
        $payload = json_decode($payload_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($payload)) {
            wp_send_json_error(['message' => __('Invalid booking data received.', 'mobooking')], 400);
            return;
        }

        $result = $this->create_booking($user_id, $payload);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        } else {
            $success_message = !empty($result['message']) ? sprintf($result['message'], $result['booking_reference']) : __('Booking created successfully!', 'mobooking');
            wp_send_json_success([
                'message' => $success_message,
                'booking_id' => $result['booking_id']
            ]);
        }
    }

    public function handle_update_dashboard_booking_fields_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $update_data_json = isset($_POST['update_data']) ? stripslashes_deep($_POST['update_data']) : '';

        if (empty($booking_id)) {
            wp_send_json_error(['message' => __('Booking ID is required.', 'mobooking')], 400);
            return;
        }

        $update_data = json_decode($update_data_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($update_data)) {
            wp_send_json_error(['message' => __('Invalid update data received.', 'mobooking')], 400);
            return;
        }

        $result = $this->update_booking_fields($booking_id, $update_data, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        } else {
            wp_send_json_success(['message' => __('Booking updated successfully.', 'mobooking')]);
        }
    }

    public function update_booking_fields(int $booking_id, array $update_data, int $tenant_user_id) {
        $bookings_table = Database::get_table_name('bookings');
        
        // Whitelist of allowed fields to update
        $allowed_fields = [
            'customer_name', 'customer_email', 'customer_phone', 'service_address',
            'booking_date', 'booking_time', 'special_instructions', 'status', 'assigned_staff_id'
        ];
        
        $filtered_data = [];
        foreach ($update_data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $filtered_data[$field] = sanitize_text_field($value);
            }
        }
        
        if (empty($filtered_data)) {
            return new \WP_Error('no_valid_fields', __('No valid fields to update.', 'mobooking'));
        }
        
        $filtered_data['updated_at'] = current_time('mysql');
        
        $updated = $this->wpdb->update(
            $bookings_table,
            $filtered_data,
            ['booking_id' => $booking_id, 'user_id' => $tenant_user_id],
            array_fill(0, count($filtered_data), '%s'),
            ['%d', '%d']
        );

        if (false === $updated) {
            return new \WP_Error('db_update_error', __('Could not update booking.', 'mobooking'));
        }

        return true;
    }

    public function handle_delete_dashboard_booking_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        if (empty($booking_id)) {
            wp_send_json_error(['message' => __('Booking ID is required for deletion.', 'mobooking')], 400);
            return;
        }

        $result = $this->delete_booking($booking_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        } else {
            wp_send_json_success(['message' => __('Booking deleted successfully.', 'mobooking')]);
        }
    }

    public function delete_booking(int $booking_id, int $tenant_user_id) {
        $bookings_table = Database::get_table_name('bookings');
        
        // First verify the booking exists and belongs to the tenant
        $booking = $this->get_booking_by_id($booking_id, $tenant_user_id);
        if (!$booking) {
            return new \WP_Error('booking_not_found', __('Booking not found or access denied.', 'mobooking'));
        }
        
        $deleted = $this->wpdb->delete(
            $bookings_table,
            ['booking_id' => $booking_id, 'user_id' => $tenant_user_id],
            ['%d', '%d']
        );

        if (false === $deleted) {
            return new \WP_Error('db_delete_error', __('Could not delete booking from database.', 'mobooking'));
        }

        return true;
    }

    /**
     * Get all bookings for a specific status
     */
    public function get_bookings_by_status(int $tenant_user_id, string $status) {
        $bookings_table = Database::get_table_name('bookings');
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE user_id = %d AND status = %s ORDER BY booking_date ASC",
            $tenant_user_id, $status
        ), ARRAY_A) ?: [];
    }

    /**
     * Get booking statistics for dashboard
     */
    public function get_booking_statistics(int $tenant_user_id) {
        $bookings_table = Database::get_table_name('bookings');
        
        $stats = [];
        
        // Total bookings
        $stats['total'] = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table WHERE user_id = %d",
            $tenant_user_id
        ));
        
        // Bookings by status
        $status_counts = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $bookings_table WHERE user_id = %d GROUP BY status",
            $tenant_user_id
        ), ARRAY_A);
        
        foreach ($status_counts as $row) {
            $stats['by_status'][$row['status']] = intval($row['count']);
        }
        
        // Revenue statistics
        $stats['total_revenue'] = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed')",
            $tenant_user_id
        ));
        
        // Average booking value
        $stats['average_booking_value'] = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT AVG(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed')",
            $tenant_user_id
        ));
        
        return $stats;
    }

    /**
     * Search bookings with advanced filters
     */
    public function search_bookings(int $tenant_user_id, array $filters = []) {
        $bookings_table = Database::get_table_name('bookings');
        
        $where_conditions = ['user_id = %d'];
        $where_values = [$tenant_user_id];
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "booking_date >= %s";
            $where_values[] = sanitize_text_field($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "booking_date <= %s";
            $where_values[] = sanitize_text_field($filters['date_to']);
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = %s";
            $where_values[] = sanitize_text_field($filters['status']);
        }
        
        // Customer search
        if (!empty($filters['customer_search'])) {
            $where_conditions[] = "(customer_name LIKE %s OR customer_email LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($filters['customer_search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Price range
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "total_price >= %f";
            $where_values[] = floatval($filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "total_price <= %f";
            $where_values[] = floatval($filters['max_price']);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $order_by = isset($filters['order_by']) ? sanitize_sql_orderby($filters['order_by']) : 'created_at DESC';
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $bookings_table $where_clause ORDER BY $order_by",
            $where_values
        ), ARRAY_A) ?: [];
    }

    /**
     * Export bookings to CSV format
     */
    public function export_bookings_csv(int $tenant_user_id, array $filters = []) {
        $bookings = $this->search_bookings($tenant_user_id, $filters);
        
        if (empty($bookings)) {
            return new \WP_Error('no_bookings', __('No bookings found to export.', 'mobooking'));
        }
        
        $csv_data = [];
        $csv_data[] = [
            'Booking Reference',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Service Address',
            'Booking Date',
            'Booking Time',
            'Status',
            'Total Price',
            'Created At'
        ];
        
        foreach ($bookings as $booking) {
            $csv_data[] = [
                $booking['booking_reference'],
                $booking['customer_name'],
                $booking['customer_email'],
                $booking['customer_phone'],
                $booking['service_address'],
                $booking['booking_date'],
                $booking['booking_time'],
                $booking['status'],
                $booking['total_price'],
                $booking['created_at']
            ];
        }
        
        return $csv_data;
    }

    /**
     * Get upcoming bookings for reminders
     */
    public function get_upcoming_bookings(int $tenant_user_id, int $days_ahead = 7) {
        $bookings_table = Database::get_table_name('bookings');
        $start_date = current_time('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $bookings_table 
             WHERE user_id = %d 
             AND status IN ('confirmed', 'pending') 
             AND booking_date BETWEEN %s AND %s 
             ORDER BY booking_date ASC, booking_time ASC",
            $tenant_user_id, $start_date, $end_date
        ), ARRAY_A) ?: [];
    }

    /**
     * Validate booking data before saving
     */
    private function validate_booking_data(array $booking_data) {
        $errors = [];
        
        // Required fields validation
        $required_fields = ['customer_name', 'customer_email', 'booking_date', 'booking_time'];
        foreach ($required_fields as $field) {
            if (empty($booking_data[$field])) {
                $errors[] = sprintf(__('Field %s is required.', 'mobooking'), $field);
            }
        }
        
        // Email validation
        if (!empty($booking_data['customer_email']) && !is_email($booking_data['customer_email'])) {
            $errors[] = __('Invalid email address.', 'mobooking');
        }
        
        // Date validation
        if (!empty($booking_data['booking_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $booking_data['booking_date']);
            if (!$date || $date->format('Y-m-d') !== $booking_data['booking_date']) {
                $errors[] = __('Invalid booking date format.', 'mobooking');
            }
        }
        
        // Time validation
        if (!empty($booking_data['booking_time'])) {
            $time = \DateTime::createFromFormat('H:i', $booking_data['booking_time']);
            if (!$time || $time->format('H:i') !== $booking_data['booking_time']) {
                $errors[] = __('Invalid booking time format.', 'mobooking');
            }
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Get the owner user_id for a given booking_id.
     *
     * @param int $booking_id The ID of the booking.
     * @return int|null The user_id of the booking owner, or null if not found.
     */
    public function get_booking_owner_id(int $booking_id): ?int {
        if (empty($booking_id)) {
            return null;
        }
        $bookings_table = Database::get_table_name('bookings');
        $owner_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT user_id FROM $bookings_table WHERE booking_id = %d",
            $booking_id
        ));

        if ($owner_id === null) { // Check for null explicitly, as 0 could be a valid (though unlikely) user_id if not for auto-increment.
            return null;
        }
        return (int) $owner_id;
    }

    public function handle_assign_staff_to_booking_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Assuming a general dashboard nonce
        $current_user_id = get_current_user_id();

        if ( !current_user_can(Auth::CAP_MANAGE_BOOKINGS) && !current_user_can(Auth::CAP_ASSIGN_BOOKINGS) ) { // CAP_ASSIGN_BOOKINGS would be a new capability
            wp_send_json_error(['message' => __('You do not have permission to assign bookings.', 'mobooking')], 403);
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0; // 0 means unassign

        if (empty($booking_id)) {
            wp_send_json_error(['message' => __('Booking ID is required.', 'mobooking')], 400);
            return;
        }

        // Validate staff_id if not 0 (unassigning)
        if ($staff_id !== 0) {
            $staff_user = get_userdata($staff_id);
            if (!$staff_user || !in_array(Auth::ROLE_WORKER_STAFF, $staff_user->roles)) {
                wp_send_json_error(['message' => __('Invalid staff member selected.', 'mobooking')], 400);
                return;
            }
            // Further check: ensure the staff member belongs to the same business owner if applicable
            $booking_owner_id = $this->get_booking_owner_id($booking_id);
            if ($booking_owner_id) {
                $staff_owner_id = Auth::get_business_owner_id_for_worker($staff_id);
                if ($booking_owner_id !== $staff_owner_id) {
                    wp_send_json_error(['message' => __('Staff member does not belong to this business.', 'mobooking')], 403);
                    return;
                }
            }
        }

        $result = $this->assign_staff_to_booking($booking_id, $staff_id, $current_user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        } else {
            // Optionally, send notification here if this step is confirmed
            // if ($staff_id !== 0 && class_exists('MoBooking\Classes\Notifications')) {
            //     $booking_details = $this->get_booking($booking_id, $current_user_id); // Or a simplified version
            //     $this->notifications_manager->send_staff_assignment_notification($staff_id, $booking_id, $booking_details);
            // }
            wp_send_json_success(['message' => __('Booking assignment updated successfully.', 'mobooking')]);
        }
    }

    public function assign_staff_to_booking(int $booking_id, int $staff_id, int $current_user_id) {
        $bookings_table = Database::get_table_name('bookings');

        // Verify booking belongs to the current user's business or user has rights
        $booking_owner_id = $this->get_booking_owner_id($booking_id);
        $user_to_check_against = $current_user_id;

        if (Auth::is_user_worker($current_user_id)) {
            $user_to_check_against = Auth::get_business_owner_id_for_worker($current_user_id);
        }

        if (!$booking_owner_id || $booking_owner_id !== $user_to_check_against) {
            if (!current_user_can(Auth::CAP_MANAGE_BOOKINGS_OTHERS)) { // A more global capability if needed
                 return new \WP_Error('auth_error', __('You do not have permission to modify this booking.', 'mobooking'));
            }
        }

        $staff_id_to_save = ($staff_id === 0) ? null : $staff_id;

        $updated = $this->wpdb->update(
            $bookings_table,
            ['assigned_staff_id' => $staff_id_to_save, 'updated_at' => current_time('mysql')],
            ['booking_id' => $booking_id],
            [$staff_id_to_save === null ? '%s' : '%d', '%s'], // Handle null correctly
            ['%d']
        );

        if (false === $updated) {
            return new \WP_Error('db_update_error', __('Could not assign staff to booking.', 'mobooking'));
        }

        // Potentially update booking status to 'Assigned' if it was 'pending' and a staff member is assigned
        if ($staff_id_to_save !== null) {
            $booking = $this->get_booking($booking_id, $current_user_id); // Fetch current booking data
            if ($booking && $booking['status'] === 'pending') {
                // Note: update_booking_status will trigger its own admin notification if implemented there.
                $this->update_booking_status($booking_id, 'assigned', $booking_owner_id, $current_user_id); // Pass $current_user_id as updater
            }
            // Send notification to staff
            if ($this->notifications_manager && method_exists($this->notifications_manager, 'send_staff_assignment_notification')) {
                $booking_details_for_staff_email = $booking ?: $this->get_booking($booking_id, $current_user_id);
                if ($booking_details_for_staff_email) {
                    $this->notifications_manager->send_staff_assignment_notification($staff_id_to_save, $booking_id, $booking_details_for_staff_email, $booking_owner_id);
                }
            }
        }


        return true;
    }

    public function get_bookings_by_customer_id( $customer_id ) {
        $customer_id = absint( $customer_id );
        if ( ! $customer_id ) {
            return [];
        }

        $bookings_table = Database::get_table_name('bookings');
        $items_table = Database::get_table_name('booking_items');

        $bookings = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT b.*, GROUP_CONCAT(i.service_name SEPARATOR ', ') as service_name
                 FROM {$bookings_table} b
                 LEFT JOIN {$items_table} i ON b.booking_id = i.booking_id
                 WHERE b.customer_id = %d
                 GROUP BY b.booking_id
                 ORDER BY b.booking_date DESC",
                $customer_id
            ),
            ARRAY_A
        );

        return [
            'bookings' => $bookings ?: [],
        ];
    }
/**
 * Get chart data for dashboard
 * Add this method to the Bookings class
 */
public function get_chart_data($tenant_id, $period = 'week') {
    $bookings_table = Database::get_table_name('bookings');
    
    // Define date range based on period
    switch ($period) {
        case 'month':
            $days = 30;
            $date_format = '%Y-%m-%d';
            break;
        case '3months':
            $days = 90;
            $date_format = '%Y-%m-%d';
            break;
        case 'year':
            $days = 365;
            $date_format = '%Y-%m';
            break;
        default: // week
            $days = 7;
            $date_format = '%Y-%m-%d';
            break;
    }
    
    $sql = "
        SELECT 
            DATE_FORMAT(booking_date, '{$date_format}') as period_label,
            COUNT(*) as booking_count,
            SUM(total_price) as revenue
        FROM {$bookings_table}
        WHERE user_id = %d 
        AND booking_date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
        GROUP BY period_label
        ORDER BY period_label ASC
    ";
    
    $results = $this->wpdb->get_results(
        $this->wpdb->prepare($sql, $tenant_id),
        ARRAY_A
    );
    
    // Format data for Chart.js
    $labels = [];
    $values = [];
    
    foreach ($results as $row) {
        $labels[] = $row['period_label'];
        $values[] = intval($row['booking_count']);
    }
    
    // Fill missing dates with zeros if needed
    if ($period === 'week') {
        $labels = [];
        $values = [];
        $data_map = [];
        
        // Create map from results
        foreach ($results as $row) {
            $data_map[$row['period_label']] = intval($row['booking_count']);
        }
        
        // Fill last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($date));
            $values[] = isset($data_map[$date]) ? $data_map[$date] : 0;
        }
    }
    
    return [
        'labels' => $labels,
        'values' => $values,
        'period' => $period
    ];
}

// Add these methods to classes/Services.php

    /**
     * Get top services by booking count
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_top_services(int $user_id, int $limit = 5) {
        if (empty($user_id)) {
            return [];
        }
        
        $services_table = Database::get_table_name('services');
        $bookings_table = Database::get_table_name('bookings');
        
        $sql = "SELECT s.service_id, s.name, s.price, 
                       COUNT(b.booking_id) as booking_count,
                       SUM(b.total_price) as total_revenue
                FROM $services_table s
                LEFT JOIN $bookings_table b ON s.service_id = b.service_id AND b.user_id = %d
                WHERE s.user_id = %d AND s.status = 'active'
                GROUP BY s.service_id
                ORDER BY booking_count DESC, total_revenue DESC
                LIMIT %d";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $user_id, $user_id, $limit), ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        // Format the results
        return array_map(function($service) {
            return [
                'service_id' => intval($service['service_id']),
                'name' => $service['name'],
                'price' => floatval($service['price']),
                'booking_count' => intval($service['booking_count']),
                'revenue' => floatval($service['total_revenue'] ?? 0)
            ];
        }, $results);
    }

    /**
     * Get services count for a user
     * @param int $user_id
     * @return int
     */
    public function get_services_count(int $user_id) {
        if (empty($user_id)) {
            return 0;
        }
        
        $table_name = Database::get_table_name('services');
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        return intval($count);
    }

// Add these methods to classes/Bookings.php

    /**
     * Get live activity feed for dashboard
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_live_activity(int $user_id, int $limit = 10) {
        if (empty($user_id)) {
            return [];
        }
        
        $table_name = Database::get_table_name('bookings');
        
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT booking_id, booking_reference, customer_name, customer_email, 
                    booking_date, booking_time, status, total_price, created_at
             FROM $table_name 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id, $limit
        ), ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        // Format activities for the frontend
        return array_map(function($booking) {
            $type = $this->get_activity_type($booking['status']);
            return [
                'type' => $type,
                'text' => $this->get_activity_text($booking),
                'timestamp' => $booking['created_at'],
                'status' => $booking['status'],
                'booking_id' => $booking['booking_id'],
                'customer_name' => $booking['customer_name']
            ];
        }, $results);
    }

    /**
     * Get activity type based on booking status
     * @param string $status
     * @return string
     */
    private function get_activity_type($status) {
        $types = [
            'pending' => 'booking_created',
            'confirmed' => 'booking_confirmed', 
            'completed' => 'booking_completed',
            'cancelled' => 'booking_cancelled'
        ];
        
        return $types[$status] ?? 'booking_created';
    }

    /**
     * Get activity text for dashboard feed
     * @param array $booking
     * @return string
     */
    private function get_activity_text($booking) {
        $customer_name = $booking['customer_name'] ?? 'Unknown Customer';
        $date = date('M j', strtotime($booking['booking_date']));
        $price = number_format($booking['total_price'], 2);
        
        switch ($booking['status']) {
            case 'confirmed':
                return sprintf('%s confirmed booking for %s ($%s)', $customer_name, $date, $price);
            case 'completed':
                return sprintf('%s completed booking for %s ($%s)', $customer_name, $date, $price);
            case 'cancelled':
                return sprintf('%s cancelled booking for %s', $customer_name, $date);
            default:
                return sprintf('%s created new booking for %s ($%s)', $customer_name, $date, $price);
        }
    }

    /**
     * Get booking chart data for dashboard
     * @param int $user_id
     * @param string $period (week, month, year)
     * @return array
     */
    public function get_booking_chart_data(int $user_id, string $period = 'week') {
        if (empty($user_id)) {
            return [];
        }
        
        $table_name = Database::get_table_name('bookings');
        
        // Define date range based on period
        switch ($period) {
            case 'year':
                $date_condition = "booking_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case 'month':
                $date_condition = "booking_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "booking_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
        }
        
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM $table_name 
             WHERE user_id = %d AND $date_condition
             GROUP BY status",
            $user_id
        ), ARRAY_A);
        
        // Default statuses
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        $data = array_fill_keys($statuses, 0);
        
        // Fill with actual data
        foreach ($results as $row) {
            $data[$row['status']] = intval($row['count']);
        }
        
        return [
            'labels' => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
            'values' => array_values($data)
        ];
    }

// Add this method to classes/Customers.php (or create the class if it doesn't exist)

    /**
     * Get customer insights for dashboard
     * @param int $user_id
     * @return array
     */
    public function get_customer_insights(int $user_id) {
        if (empty($user_id)) {
            return [
                'new_customers' => 0,
                'returning_customers' => 0,
                'retention_rate' => 0
            ];
        }
        
        $bookings_table = Database::get_table_name('bookings');
        
        // Get unique customers this month
        $new_customers = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT customer_email) 
             FROM $bookings_table 
             WHERE user_id = %d 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            $user_id
        ));
        
        // Get returning customers (customers with more than 1 booking)
        $returning_customers = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT customer_email, COUNT(*) as booking_count
                FROM $bookings_table 
                WHERE user_id = %d 
                GROUP BY customer_email 
                HAVING booking_count > 1
            ) as repeat_customers",
            $user_id
        ));
        
        // Calculate retention rate
        $total_customers = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT customer_email) FROM $bookings_table WHERE user_id = %d",
            $user_id
        ));
        
        $retention_rate = $total_customers > 0 ? round(($returning_customers / $total_customers) * 100, 1) : 0;
        
        return [
            'new_customers' => intval($new_customers),
            'returning_customers' => intval($returning_customers),
            'retention_rate' => floatval($retention_rate)
        ];
    }

}
?>