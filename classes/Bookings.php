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

        // Revenue this month (from completed bookings)
        $revenue_month = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(total_price) FROM $bookings_table
             WHERE user_id = %d AND status = 'completed'
             AND booking_date BETWEEN %s AND %s",
            $tenant_user_id, $current_month_start, $current_month_end
        ));

        // Upcoming confirmed bookings
        $upcoming_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(booking_id) FROM $bookings_table
             WHERE user_id = %d AND status = 'confirmed' AND booking_date >= %s",
            $tenant_user_id, $today
        ));

        return [
            'bookings_month' => intval($bookings_month),
            'revenue_month' => floatval($revenue_month), // Ensure it's a float
            'upcoming_count' => intval($upcoming_count)
        ];
    }

    public function handle_get_dashboard_overview_data_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $kpis = $this->get_kpi_data($user_id);

        $recent_bookings_args = [
            'limit' => 5,
            'orderby' => 'booking_date',
            'order' => 'ASC', // Show nearest upcoming first
            // Fetching statuses that are "active" or need attention
            'status' => ['pending', 'confirmed', 'on-hold', 'processing'],
            'date_from' => current_time('Y-m-d') // From today onwards
        ];
        $recent_bookings_data = $this->get_bookings_by_tenant($user_id, $recent_bookings_args);

        wp_send_json_success([
            'kpis' => $kpis,
            'recent_bookings' => $recent_bookings_data['bookings'] // get_bookings_by_tenant returns an array with 'bookings' key
        ]);
    }


    public function get_booking(int $booking_id, int $tenant_user_id) {
        if (empty($booking_id) || empty($tenant_user_id)) {
            return null;
        }
        $bookings_table = Database::get_table_name('bookings');
        $booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE booking_id = %d AND user_id = %d",
            $booking_id, $tenant_user_id
        ), ARRAY_A);

        if ($booking) {
            $items_table = Database::get_table_name('booking_items');
            $booking_items_raw = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM $items_table WHERE booking_id = %d ORDER BY item_id ASC", $booking_id
            ), ARRAY_A);

            $booking['items'] = [];
            foreach ($booking_items_raw as $item) {
                $item['selected_options'] = json_decode($item['selected_options'], true);
                // Ensure selected_options is always an array
                if (!is_array($item['selected_options'])) {
                    $item['selected_options'] = [];
                }
                $booking['items'][] = $item;
            }
        }
        return $booking;
    }

    public function get_bookings_by_tenant(int $tenant_user_id, array $args = []) {
        if (empty($tenant_user_id)) {
            return ['bookings' => [], 'total_count' => 0, 'per_page' => 20, 'current_page' => 1];
        }

        $defaults = [
            'status' => null, 'date_from' => null, 'date_to' => null,
            'orderby' => 'booking_date', 'order' => 'DESC',
            'limit' => 20, 'paged' => 1, 'search_query' => null
        ];
        $args = wp_parse_args($args, $defaults);

        $bookings_table = Database::get_table_name('bookings');

        $sql_select = "SELECT *";
        $sql_count_select = "SELECT COUNT(booking_id)";
        $sql_from = " FROM $bookings_table";
        $sql_where = " WHERE user_id = %d";
        $params = [$tenant_user_id];

        if (!empty($args['status'])) {
            if (is_array($args['status'])) {
                $status_placeholders = implode(', ', array_fill(0, count($args['status']), '%s'));
                $sql_where .= " AND status IN ($status_placeholders)";
                $params = array_merge($params, $args['status']);
            } else {
                $sql_where .= " AND status = %s";
                $params[] = sanitize_text_field($args['status']);
            }
        }
        if (!empty($args['date_from'])) {
            $sql_where .= " AND booking_date >= %s";
            $params[] = sanitize_text_field($args['date_from']);
        }
        if (!empty($args['date_to'])) {
            $sql_where .= " AND booking_date <= %s";
            $params[] = sanitize_text_field($args['date_to']);
        }
        if (!empty($args['search_query'])) {
            $search_term = '%' . $this->wpdb->esc_like(sanitize_text_field($args['search_query'])) . '%';
            $sql_where .= " AND (booking_reference LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s)";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $total_count_sql = $sql_count_select . $sql_from . $sql_where;
        $total_count = $this->wpdb->get_var($this->wpdb->prepare($total_count_sql, ...$params));

        $valid_orderby_columns = ['booking_id', 'customer_name', 'booking_date', 'total_price', 'status', 'booking_reference'];
        $orderby = in_array($args['orderby'], $valid_orderby_columns) ? $args['orderby'] : 'booking_date';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql_orderby = " ORDER BY " . $orderby . " " . $order;

        $limit = intval($args['limit']);
        $paged = intval($args['paged']);
        $offset = ($paged > 0) ? ($paged - 1) * $limit : 0;
        $sql_limit = $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);

        $bookings_sql = $sql_select . $sql_from . $sql_where . $sql_orderby . $sql_limit;
        $bookings = $this->wpdb->get_results($this->wpdb->prepare($bookings_sql, ...$params), ARRAY_A);

        return [
            'bookings' => $bookings,
            'total_count' => intval($total_count),
            'per_page' => $limit,
            'current_page' => $paged
        ];
    }

    public function update_booking_status(int $booking_id, int $tenant_user_id, string $new_status) {
        $new_status = sanitize_text_field($new_status);
        // Define allowed statuses, these could also come from a helper or config
        $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'on-hold', 'processing'];
        if (!in_array($new_status, $allowed_statuses)) {
            return new \WP_Error('invalid_status', __('Invalid booking status provided.', 'mobooking'));
        }

        // Verify ownership by attempting to fetch the booking first
        $current_booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM " . Database::get_table_name('bookings') . " WHERE booking_id = %d AND user_id = %d",
            $booking_id, $tenant_user_id
        ));

        if (!$current_booking) {
            return new \WP_Error('not_found_or_owner', __('Booking not found or you do not have permission to update it.', 'mobooking'));
        }

        if ($current_booking->status === $new_status) {
            return true; // No change needed
        }

        $bookings_table = Database::get_table_name('bookings');
        $updated = $this->wpdb->update(
            $bookings_table,
            ['status' => $new_status, 'updated_at' => current_time('mysql', 1)],
            ['booking_id' => $booking_id, 'user_id' => $tenant_user_id], // Ensure user_id in WHERE for security
            ['%s', '%s'], // format for data
            ['%d', '%d']  // format for where
        );

        if (false === $updated) {
            return new \WP_Error('db_update_error', __('Could not update booking status in the database.', 'mobooking'));
        }

        // TODO: Trigger notification to customer about status change (e.g., if confirmed or cancelled by admin)
        // $email_booking_details = (array) $current_booking; // Cast to array
        // $email_booking_details['new_status'] = $new_status;
        // $this->notifications_manager->send_booking_status_update_customer($email_booking_details, $current_booking->customer_email, $tenant_user_id);

        return true;
    }

    // AJAX Handlers for Tenant Dashboard
    public function handle_get_tenant_bookings_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // A general dashboard nonce
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $args = [
            'status' => isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : null,
            'date_from' => isset($_POST['date_from_filter']) ? sanitize_text_field($_POST['date_from_filter']) : null,
            'date_to' => isset($_POST['date_to_filter']) ? sanitize_text_field($_POST['date_to_filter']) : null,
            'search_query' => isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : null,
            'paged' => isset($_POST['paged']) ? intval($_POST['paged']) : 1,
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 20, // Default items per page
            'orderby' => isset($_POST['orderby']) ? sanitize_key($_POST['orderby']) : 'booking_date',
            'order' => isset($_POST['order']) ? sanitize_key($_POST['order']) : 'DESC',
        ];

        $result = $this->get_bookings_by_tenant($user_id, $args);
        wp_send_json_success($result);
    }

    public function handle_get_tenant_booking_details_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        if (empty($booking_id)) { wp_send_json_error(['message' => __('Booking ID is required.', 'mobooking')], 400); return; }

        $booking_details = $this->get_booking($booking_id, $user_id);
        if ($booking_details) {
            wp_send_json_success($booking_details);
        } else {
            wp_send_json_error(['message' => __('Booking not found or access denied.', 'mobooking')], 404);
        }
    }

    public function handle_update_booking_status_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

        if (empty($booking_id) || empty($new_status)) {
            wp_send_json_error(['message' => __('Booking ID and new status are required.', 'mobooking')], 400);
            return;
        }

        $result = $this->update_booking_status($booking_id, $user_id, $new_status);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400); // Or 500 for DB errors
        } else {
            wp_send_json_success(['message' => __('Booking status updated successfully.', 'mobooking')]);
        }
    }

    private function generate_unique_booking_reference(): string {
        return 'MB-' . current_time('Ymd') . '-' . strtoupper(wp_generate_password(8, false, false));
    }

    /**
     * Calculates prices and validates services/options server-side.
     * Throws Exceptions on validation failures.
     */
    private function calculate_server_side_price(int $tenant_user_id, array $selected_services_data, ?array $client_discount_info): array {
        $subtotal = 0;
        $calculated_service_items = [];

        foreach ($selected_services_data as $client_service) {
            if (empty($client_service['service_id'])) continue;

            $db_service = $this->services_manager->get_service(intval($client_service['service_id']), $tenant_user_id);
            if (!$db_service || $db_service['status'] !== 'active') {
                throw new \Exception(sprintf(__('Service "%s" is no longer available or invalid.', 'mobooking'), esc_html($client_service['name'] ?? 'Unknown')));
            }

            $current_item_base_price = floatval($db_service['price']);
            $current_item_total_price = $current_item_base_price;
            $item_options_summary_for_db = [];

            if (!empty($client_service['configured_options']) && is_array($client_service['configured_options'])) {
                foreach ($client_service['configured_options'] as $conf_opt) {
                    if (empty($conf_opt['option_id'])) continue;

                    $db_option = $this->services_manager->get_service_option(intval($conf_opt['option_id']), $tenant_user_id);
                    // Ensure option belongs to the correct service and tenant
                    if (!$db_option || (int)$db_option['service_id'] !== (int)$client_service['service_id']) {
                         throw new \Exception(sprintf(__('Option "%s" for service "%s" is invalid.', 'mobooking'), esc_html($conf_opt['option_name'] ?? 'Unknown'), esc_html($client_service['name'] ?? 'Unknown')));
                    }

                    $option_price_impact = 0;
                    $impact_val = floatval($db_option['price_impact_value']); // Price impact defined in DB
                    $selected_val = $conf_opt['selected_value']; // Value selected by user
                    $is_option_selected_or_has_value = false;

                    // Determine if the option was actually selected or has a meaningful value
                    switch ($db_option['type']) {
                        case 'checkbox': if ($selected_val === '1') $is_option_selected_or_has_value = true; break;
                        case 'quantity': if (intval($selected_val) > 0) $is_option_selected_or_has_value = true; break;
                        default: if ($selected_val !== '' && !is_null($selected_val)) $is_option_selected_or_has_value = true; break;
                    }

                    if ($is_option_selected_or_has_value) {
                        if ($db_option['price_impact_type'] === 'fixed') {
                            option_price_impact = $impact_val;
                        } elseif ($db_option['price_impact_type'] === 'percentage') {
                            option_price_impact = $current_item_base_price * ($impact_val / 100);
                        } elseif ($db_option['price_impact_type'] === 'multiply_value' && $db_option['type'] === 'quantity') {
                            option_price_impact = $impact_val * (intval($selected_val) ?: 0);
                        } elseif (($db_option['type'] === 'select' || $db_option['type'] === 'radio') && !empty($db_option['option_values'])) {
                             // Price adjustments from specific choices within select/radio
                            $parsed_choices = json_decode($db_option['option_values'], true);
                            if (is_array($parsed_choices)) {
                                $chosen = array_values(array_filter($parsed_choices, function($c) use ($selected_val) { return isset($c['value']) && $c['value'] === $selected_val; }));
                                if (!empty($chosen) && isset($chosen[0]['price_adjust'])) {
                                    option_price_impact += floatval($chosen[0]['price_adjust']);
                                }
                            }
                        }
                        $current_item_total_price += $option_price_impact;
                        $item_options_summary_for_db[] = [
                            'option_id' => $db_option['option_id'], 'name' => $db_option['name'],
                            'value' => $selected_val, 'price_impact' => round($option_price_impact, 2)
                        ];
                    }
                }
            }
            $subtotal += $current_item_total_price;
            $calculated_service_items[] = [
                'service_id' => $db_service['service_id'], 'service_name' => $db_service['name'],
                'service_price' => $current_item_base_price, 'quantity' => 1,
                'selected_options_summary' => $item_options_summary_for_db,
                'item_total_price' => $current_item_total_price
            ];
        }

        $discount_applied_amount = 0;
        $final_valid_discount_info = null;

        if ($client_discount_info && !empty($client_discount_info['code']) && !empty($client_discount_info['discount_id'])) {
            $db_discount = $this->discounts_manager->validate_discount_code($client_discount_info['code'], $tenant_user_id);
            if ($db_discount && !is_wp_error($db_discount) && (int)$db_discount['discount_id'] === (int)$client_discount_info['discount_id']) {
                if ($db_discount['type'] === 'percentage') {
                    $discount_applied_amount = $subtotal * (floatval($db_discount['value']) / 100);
                } elseif ($db_discount['type'] === 'fixed_amount') {
                    $discount_applied_amount = floatval($db_discount['value']);
                }
                $discount_applied_amount = min($discount_applied_amount, $subtotal);
                $final_valid_discount_info = $db_discount;
            } else {
                 throw new \Exception(__('The applied discount code is no longer valid.', 'mobooking'));
            }
        }

        $final_total = $subtotal - $discount_applied_amount;
        if ($final_total < 0) $final_total = 0;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_applied' => round($discount_applied_amount, 2),
            'final_total' => round($final_total, 2),
            'calculated_service_items' => $calculated_service_items,
            'validated_discount_info' => $final_valid_discount_info
        ];
    }

    public function create_booking(int $tenant_user_id, array $payload) {
        try {
            if (empty($tenant_user_id) || empty($payload['selected_services']) || empty($payload['customer_details']) || empty($payload['pricing'])) {
                return new \WP_Error('invalid_payload', __('Incomplete booking data provided.', 'mobooking'));
            }

            $customer = $payload['customer_details'];
            $services_data = $payload['selected_services'];
            $discount_info_from_client = $payload['discount_info'] ?? null;
            $zip_code_from_payload = $payload['zip_code'] ?? ($customer['zip_code'] ?? '');


            $tenant_user = get_userdata($tenant_user_id);
            if (!$tenant_user || !in_array(Auth::ROLE_BUSINESS_OWNER, (array)$tenant_user->roles)) {
                return new \WP_Error('invalid_tenant', __('Invalid business specified.', 'mobooking'));
            }

            if (empty($customer['customer_name']) || !is_email($customer['customer_email']) || empty($customer['booking_date']) || empty($customer['booking_time']) || empty($customer['service_address'])) {
                return new \WP_Error('invalid_customer_data', __('Customer name, email, address, booking date, and time are required.', 'mobooking'));
            }
            // TODO: Add robust date/time validation, check against business hours, lead times etc.

            $server_price_details = $this->calculate_server_side_price($tenant_user_id, $services_data, $discount_info_from_client);

            $final_total_server = $server_price_details['final_total'];
            $validated_discount_info = $server_price_details['validated_discount_info'];
            $calculated_service_items = $server_price_details['calculated_service_items'];

            $booking_reference = $this->generate_unique_booking_reference();
            $bookings_table = Database::get_table_name('bookings');
            $booking_items_table = Database::get_table_name('booking_items');

            $booking_data_for_db = [
                'user_id' => $tenant_user_id,
                'customer_name' => sanitize_text_field($customer['customer_name']),
                'customer_email' => sanitize_email($customer['customer_email']),
                'customer_phone' => sanitize_text_field($customer['customer_phone']),
                'service_address' => sanitize_textarea_field($customer['service_address']),
                'zip_code' => sanitize_text_field($zip_code_from_payload),
                'booking_date' => sanitize_text_field($customer['booking_date']),
                'booking_time' => sanitize_text_field($customer['booking_time']),
                'special_instructions' => sanitize_textarea_field($customer['special_instructions']),
                'total_price' => $final_total_server,
                'discount_id' => ($validated_discount_info && isset($validated_discount_info['discount_id'])) ? intval($validated_discount_info['discount_id']) : null,
                'discount_amount' => $server_price_details['discount_applied'],
                'status' => 'confirmed',
                'booking_reference' => $booking_reference,
                'payment_status' => 'pending',
                'created_at' => current_time('mysql', 1),
                'updated_at' => current_time('mysql', 1),
            ];

            $this->wpdb->show_errors(); // Temporarily for debugging if issues
            $inserted_booking = $this->wpdb->insert($bookings_table, $booking_data_for_db);

            if (!$inserted_booking) {
                 error_log("MoBooking DB Error inserting booking: " . $this->wpdb->last_error);
                return new \WP_Error('db_booking_error', __('Could not save your booking. Please try again.', 'mobooking'));
            }
            $new_booking_id = $this->wpdb->insert_id;

            foreach ($calculated_service_items as $item) {
                $this->wpdb->insert($booking_items_table, [
                    'booking_id' => $new_booking_id, 'service_id' => $item['service_id'],
                    'service_name' => $item['service_name'], 'service_price' => $item['service_price'],
                    'quantity' => $item['quantity'], 'selected_options' => wp_json_encode($item['selected_options_summary']),
                    'item_total_price' => $item['item_total_price']
                ]);
            }

            if ($validated_discount_info && isset($validated_discount_info['discount_id'])) {
                $this->discounts_manager->increment_discount_usage(intval($validated_discount_info['discount_id']));
            }

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
                'booking_date_time' => $customer['booking_date'] . ' at ' . $customer['booking_time'],
                'total_price' => $final_total_server,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $customer['customer_phone'],
                'service_address' => $customer['service_address'],
                'special_instructions' => $customer['special_instructions'],
                'admin_booking_link' => admin_url('admin.php?page=mobooking-bookings&booking_id=' . $new_booking_id) // Example
            ];

            $this->notifications_manager->send_booking_confirmation_customer($email_booking_details, $customer['customer_email'], $tenant_user_id);
            $this->notifications_manager->send_booking_confirmation_admin($email_booking_details, $tenant_user_id);

            return [
                'booking_id' => $new_booking_id, 'booking_reference' => $booking_reference,
                'final_total' => $final_total_server,
                'message' => __('Booking confirmed! Your reference is %s.', 'mobooking')
            ];

        } catch (\Exception $e) {
             error_log("MoBooking Create Booking Exception: " . $e->getMessage());
            return new \WP_Error('booking_creation_exception', $e->getMessage());
        }
    }

    public function handle_create_booking_public_ajax() {
        check_ajax_referer('mobooking_booking_form_nonce', 'nonce');

        $payload_json = isset($_POST['finalBookingData']) ? stripslashes_deep($_POST['finalBookingData']) : '';
        $payload = json_decode($payload_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($payload) || empty($payload['tenant_id'])) {
            wp_send_json_error(['message' => __('Invalid booking data received. JSON error or missing tenant ID.', 'mobooking')], 400);
            return;
        }

        // Ensure zip_code from step 1 is part of the payload for create_booking if needed
        if (empty($payload['zip_code']) && !empty($payload['booking_details']['zip_code_from_step1'])) {
             $payload['zip_code'] = sanitize_text_field($payload['booking_details']['zip_code_from_step1']);
        }


        $tenant_id = intval($payload['tenant_id']);
        $result = $this->create_booking($tenant_id, $payload);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'db_booking_error' ? 500 : 400) );
        } else {
            $success_message = !empty($result['message']) ? sprintf($result['message'], $result['booking_reference']) : __('Booking successful!', 'mobooking');
            wp_send_json_success([
                'message' => $success_message,
                'booking_reference' => $result['booking_reference'],
                'booking_id' => $result['booking_id']
            ]);
        }
    }

    // Method to delete a booking
    public function delete_booking(int $booking_id, int $tenant_user_id) {
        if (empty($booking_id) || empty($tenant_user_id)) {
            return new \WP_Error('invalid_input', __('Booking ID and Tenant User ID are required.', 'mobooking'));
        }

        // Verify ownership by attempting to fetch the booking first
        $bookings_table = Database::get_table_name('bookings');
        $booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT user_id FROM $bookings_table WHERE booking_id = %d",
            $booking_id
        ));

        if (!$booking) {
            return new \WP_Error('not_found', __('Booking not found.', 'mobooking'));
        }
        if ((int)$booking->user_id !== $tenant_user_id) {
            return new \WP_Error('permission_denied', __('You do not have permission to delete this booking.', 'mobooking'));
        }

        $booking_items_table = Database::get_table_name('booking_items');

        // Start transaction
        $this->wpdb->query('START TRANSACTION');

        // Delete booking items
        $deleted_items = $this->wpdb->delete($booking_items_table, ['booking_id' => $booking_id], ['%d']);
        // $deleted_items can be false on error, or number of rows affected.

        // Delete main booking
        $deleted_booking = $this->wpdb->delete($bookings_table, ['booking_id' => $booking_id, 'user_id' => $tenant_user_id], ['%d', '%d']);

        if ($deleted_booking === false || $deleted_items === false) {
            $this->wpdb->query('ROLLBACK');
            return new \WP_Error('db_delete_error', __('Could not delete booking from the database.', 'mobooking'));
        }

        $this->wpdb->query('COMMIT');
        return true;
    }

    public function handle_delete_dashboard_booking_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Use a general dashboard nonce or create a specific one
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
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'db_delete_error' ? 500 : 400) );
        } else {
            wp_send_json_success(['message' => __('Booking deleted successfully.', 'mobooking')]);
        }
    }

    // Placeholder for creating a booking from dashboard - might reuse create_booking directly
    public function handle_create_dashboard_booking_ajax() {
        // This will be similar to handle_create_booking_public_ajax
        // but might use a different nonce and potentially different payload structure
        // For now, let's assume it can reuse the existing create_booking method.
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Or a specific "create booking" nonce
        $user_id = get_current_user_id(); // This is the tenant creating the booking
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

        // The create_booking method expects tenant_user_id as its first argument.
        // Here, $user_id is the tenant_user_id.
        $result = $this->create_booking($user_id, $payload);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'db_booking_error' ? 500 : 400) );
        } else {
            $success_message = !empty($result['message']) ? sprintf($result['message'], $result['booking_reference']) : __('Booking created successfully!', 'mobooking');
            wp_send_json_success([
                'message' => $success_message,
                'booking_id' => $result['booking_id'],
                'booking_reference' => $result['booking_reference']
                // Consider returning the full booking object or enough data to prepend it to the list
            ]);
        }
    }

    // Placeholder for updating booking fields from dashboard
    // This would be for more general field updates beyond just status
    public function update_booking_fields(int $booking_id, int $tenant_user_id, array $fields_to_update) {
        if (empty($booking_id) || empty($tenant_user_id) || empty($fields_to_update)) {
            return new \WP_Error('invalid_input', __('Booking ID, User ID, and fields to update are required.', 'mobooking'));
        }

        // Verify ownership
        $bookings_table = Database::get_table_name('bookings');
        $booking = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT user_id FROM $bookings_table WHERE booking_id = %d", $booking_id
        ));

        if (!$booking) {
            return new \WP_Error('not_found', __('Booking not found.', 'mobooking'));
        }
        if ((int)$booking->user_id !== $tenant_user_id) {
            return new \WP_Error('permission_denied', __('You do not have permission to update this booking.', 'mobooking'));
        }

        $allowed_fields = [
            'customer_name' => 'sanitize_text_field',
            'customer_email' => 'sanitize_email',
            'customer_phone' => 'sanitize_text_field',
            'service_address' => 'sanitize_textarea_field',
            'booking_date' => 'sanitize_text_field', // Needs validation YYYY-MM-DD
            'booking_time' => 'sanitize_text_field', // Needs validation HH:MM
            'special_instructions' => 'sanitize_textarea_field',
            // 'status' is handled by update_booking_status, but could be included here if logic is merged
        ];

        $data_to_update = [];
        $data_format = [];

        foreach ($fields_to_update as $key => $value) {
            if (array_key_exists($key, $allowed_fields)) {
                $sanitization_function = $allowed_fields[$key];
                $data_to_update[$key] = call_user_func($sanitization_function, $value);
                // Determine format for wpdb::update
                if (in_array($key, ['total_price', 'discount_amount'])) { // Example if we allow price edits
                    $data_format[] = '%f';
                } else {
                    $data_format[] = '%s';
                }
            }
        }

        if (empty($data_to_update)) {
            return new \WP_Error('no_valid_fields', __('No valid fields provided for update.', 'mobooking'));
        }

        // Add updated_at timestamp
        $data_to_update['updated_at'] = current_time('mysql', 1);
        $data_format[] = '%s';

        $updated = $this->wpdb->update(
            $bookings_table,
            $data_to_update,
            ['booking_id' => $booking_id, 'user_id' => $tenant_user_id],
            $data_format, // Format for data
            ['%d', '%d']  // Format for where
        );

        if (false === $updated) {
            return new \WP_Error('db_update_error', __('Could not update booking in the database.', 'mobooking'));
        }

        return $this->get_booking($booking_id, $tenant_user_id); // Return updated booking details
    }

    public function handle_update_dashboard_booking_fields_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $fields_json = isset($_POST['fields_to_update']) ? stripslashes_deep($_POST['fields_to_update']) : '';
        $fields_to_update = json_decode($fields_json, true);

        if (empty($booking_id) || json_last_error() !== JSON_ERROR_NONE || empty($fields_to_update)) {
            wp_send_json_error(['message' => __('Booking ID and valid fields to update are required.', 'mobooking')], 400);
            return;
        }

        $result = $this->update_booking_fields($booking_id, $user_id, $fields_to_update);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'db_update_error' ? 500 : 400) );
        } else {
            wp_send_json_success([
                'message' => __('Booking updated successfully.', 'mobooking'),
                'booking_data' => $result // Send back the updated booking data
            ]);
        }
    }
}
