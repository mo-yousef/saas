<?php
/**
 * Class Services
 * Manages cleaning services and their options.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Services {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // --- Ownership Verification Helper Methods ---

    private function _verify_service_ownership(int $service_id, int $user_id): bool {
        if (empty($service_id) || empty($user_id)) return false;
        $table_name = Database::get_table_name('services');
        $service = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT service_id FROM $table_name WHERE service_id = %d AND user_id = %d", $service_id, $user_id ) );
        return !is_null($service);
    }

    private function _verify_option_ownership(int $option_id, int $user_id): bool {
        if (empty($option_id) || empty($user_id)) return false;
        $table_name = Database::get_table_name('service_options');
        $option = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT option_id FROM $table_name WHERE option_id = %d AND user_id = %d", $option_id, $user_id ) );
        return !is_null($option);
    }

    // --- Service CRUD Methods ---

    public function add_service(int $user_id, array $data) {
        if ( empty($user_id) ) {
            return new \WP_Error('invalid_user', __('Invalid user ID.', 'mobooking'));
        }
        if ( empty($data['name']) ) {
            return new \WP_Error('missing_name', __('Service name is required.', 'mobooking'));
        }

        $defaults = array(
            'description' => '',
            'price' => 0.00,
            'duration' => 30, // Default duration in minutes
            'category' => '',
            'icon' => '',
            'image_url' => '',
            'status' => 'active'
        );
        $service_data = wp_parse_args($data, $defaults);

        $table_name = Database::get_table_name('services');

        $inserted = $this->wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'name' => sanitize_text_field($service_data['name']),
                'description' => wp_kses_post($service_data['description']),
                'price' => floatval($service_data['price']),
                'duration' => intval($service_data['duration']),
                'category' => sanitize_text_field($service_data['category']),
                'icon' => sanitize_text_field($service_data['icon']),
                'image_url' => esc_url_raw($service_data['image_url']),
                'status' => sanitize_text_field($service_data['status']),
                'created_at' => current_time('mysql', 1), // GMT
                'updated_at' => current_time('mysql', 1), // GMT
            ),
            array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return new \WP_Error('db_error', __('Could not add service to the database.', 'mobooking'));
        }
        return $this->wpdb->insert_id;
    }

    public function get_service(int $service_id, int $user_id) {
        if ( empty($user_id) || empty($service_id) ) {
            return null;
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return null; // Or WP_Error for permission denied
        }
        $table_name = Database::get_table_name('services');
        $service = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $table_name WHERE service_id = %d AND user_id = %d", $service_id, $user_id ), ARRAY_A );

        if ($service) {
            // Ensure options are fetched as an array of arrays (consistent with get_service_options)
            $options_raw = $this->get_service_options($service_id, $user_id); // This returns array of arrays/objects
            $options = [];
            if (is_array($options_raw)) {
                foreach ($options_raw as $opt) {
                    $options[] = (array) $opt; // Cast to array if objects
                }
            }
            $service['options'] = $options;
        }
        return $service;
    }

    public function get_services_by_user(int $user_id, array $args = []) {
        if ( empty($user_id) ) {
            return array();
        }
        $defaults = array(
            'status' => 'active',
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 20, // Similar to posts_per_page
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $table_name = Database::get_table_name('services');
        $sql = $this->wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $user_id );

        if ( !empty($args['status']) ) {
            $sql .= $this->wpdb->prepare( " AND status = %s", $args['status'] );
        }
        $sql .= $this->wpdb->prepare( " ORDER BY " . sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] ) ); // Whitelist orderby columns if possible
        $sql .= $this->wpdb->prepare( " LIMIT %d OFFSET %d", $args['number'], $args['offset'] );

        $services_data = $this->wpdb->get_results( $sql, ARRAY_A );

        if ($services_data) {
            foreach ($services_data as $key => $service) {
                if (is_array($service)) { // Ensure it's an array before trying to access by key
                    $options_raw = $this->get_service_options($service['service_id'], $user_id);
                    $options = [];
                    if (is_array($options_raw)) {
                        foreach ($options_raw as $opt) {
                            $options[] = (array) $opt;
                        }
                    }
                    $services_data[$key]['options'] = $options;
                }
            }
        }
        return $services_data;
    }

    public function update_service(int $service_id, int $user_id, array $data) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid service or user ID.', 'mobooking'));
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own this service.', 'mobooking'));
        }
        if ( empty($data) ) {
            return new \WP_Error('no_data', __('No data provided for update.', 'mobooking'));
        }

        $table_name = Database::get_table_name('services');

        // Prepare data and formats dynamically based on what's provided
        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['price'])) { $update_data['price'] = floatval($data['price']); $update_formats[] = '%f'; }
        if (isset($data['duration'])) { $update_data['duration'] = intval($data['duration']); $update_formats[] = '%d'; }
        if (isset($data['category'])) { $update_data['category'] = sanitize_text_field($data['category']); $update_formats[] = '%s'; }
        if (isset($data['icon'])) { $update_data['icon'] = sanitize_text_field($data['icon']); $update_formats[] = '%s'; }
        if (isset($data['image_url'])) { $update_data['image_url'] = esc_url_raw($data['image_url']); $update_formats[] = '%s'; }
        if (isset($data['status'])) { $update_data['status'] = sanitize_text_field($data['status']); $update_formats[] = '%s'; }

        if (empty($update_data)) {
            return new \WP_Error('no_valid_data', __('No valid data provided for update.', 'mobooking'));
        }
        $update_data['updated_at'] = current_time('mysql', 1); // GMT
        $update_formats[] = '%s';

        $updated = $this->wpdb->update(
            $table_name,
            $update_data,
            array('service_id' => $service_id, 'user_id' => $user_id),
            $update_formats,
            array('%d', '%d')
        );

        if (false === $updated) {
            return new \WP_Error('db_error', __('Could not update service in the database.', 'mobooking'));
        }
        return true; // Or $updated which is number of rows affected
    }

    public function delete_service(int $service_id, int $user_id) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid service or user ID.', 'mobooking'));
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own this service.', 'mobooking'));
        }

        // Delete associated options first
        $this->delete_options_for_service($service_id, $user_id); // This also verifies ownership

        $table_name = Database::get_table_name('services');
        $deleted = $this->wpdb->delete(
            $table_name,
            array('service_id' => $service_id, 'user_id' => $user_id),
            array('%d', '%d')
        );

        if (false === $deleted) {
            return new \WP_Error('db_error', __('Could not delete service from the database.', 'mobooking'));
        }
        return true;
    }

    // --- AJAX Handlers ---

    public function register_ajax_actions() {
        add_action('wp_ajax_mobooking_get_services', [$this, 'handle_get_services_ajax']);
        add_action('wp_ajax_mobooking_delete_service', [$this, 'handle_delete_service_ajax']);
        add_action('wp_ajax_mobooking_save_service', [$this, 'handle_save_service_ajax']);

        // For public booking form
        add_action('wp_ajax_nopriv_mobooking_get_public_services', [$this, 'handle_get_public_services_ajax']);
        add_action('wp_ajax_mobooking_get_public_services', [$this, 'handle_get_public_services_ajax']);
    }

    public function handle_get_public_services_ajax() {
        check_ajax_referer('mobooking_booking_form_nonce', 'nonce'); // Use the booking form nonce

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (empty($tenant_id)) {
            wp_send_json_error(['message' => __('Tenant ID is required.', 'mobooking')], 400);
            return;
        }

        // get_services_by_user already includes options if they exist and are fetched by that method.
        // We pass ['status' => 'active'] to only get active services for the public form.
        $services_raw = $this->get_services_by_user($tenant_id, ['status' => 'active']);

        if (is_wp_error($services_raw)) {
            wp_send_json_error(['message' => $services_raw->get_error_message()], 500);
        } else {
            $services = [];
            foreach ($services_raw as $service_item) {
                $item = (array) $service_item; // Ensure array format
                if (isset($item['price'])) {
                    $item['price_formatted'] = number_format_i18n(floatval($item['price']), 2);
                } else {
                    $item['price_formatted'] = __('N/A', 'mobooking');
                }
                // Ensure 'options' key exists, even if empty, for consistency.
                if (!isset($item['options']) || !is_array($item['options'])) {
                    $item['options'] = [];
                }
                $services[] = $item;
            }
            wp_send_json_success($services);
        }
    }


    public function handle_get_services_ajax() {
        error_log('[MoBooking Services Debug] handle_get_services_ajax reached.');
        error_log('[MoBooking Services Debug] POST data: ' . print_r($_POST, true));

        // Check nonce immediately
        $nonce_verified = check_ajax_referer('mobooking_services_nonce', 'nonce', false); // false to not die, so we can log
        if (!$nonce_verified) {
            error_log('[MoBooking Services Debug] Nonce verification failed.');
            wp_send_json_error(['message' => __('Nonce verification failed.', 'mobooking')], 403);
            return; // Explicitly return after sending error
        }
        error_log('[MoBooking Services Debug] Nonce verified successfully.');

        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('[MoBooking Services Debug] User not logged in.');
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        error_log('[MoBooking Services Debug] User ID: ' . $user_id);

        // Consider allowing args from POST/GET for pagination/filtering if needed
        $services = $this->get_services_by_user($user_id, ['status' => null]); // Get all statuses by default for management

        if (is_wp_error($services)) {
            error_log('[MoBooking Services Debug] Error from get_services_by_user: ' . $services->get_error_message());
            wp_send_json_error(['message' => $services->get_error_message()], 500);
        } else {
            error_log('[MoBooking Services Debug] Services fetched successfully: ' . count($services) . ' services.');
            wp_send_json_success($services);
        }
        // wp_die(); // Not strictly necessary if wp_send_json_* is the last thing called.
    }

    public function handle_delete_service_ajax() {
        check_ajax_referer('mobooking_services_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        if (!isset($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
            wp_send_json_error(['message' => __('Invalid service ID.', 'mobooking')], 400);
            return;
        }
        $service_id = intval($_POST['service_id']);
        $result = $this->delete_service($service_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ('not_owner' === $result->get_error_code() ? 403 : 500) );
        } elseif ($result) {
            wp_send_json_success(['message' => __('Service deleted successfully.', 'mobooking')]);
        } else {
            // This case might not be reached if delete_service always returns WP_Error on failure
            wp_send_json_error(['message' => __('Could not delete service.', 'mobooking')], 500);
        }
    }

    public function handle_save_service_ajax() {
        error_log('[MoBooking SaveSvc Debug] handle_save_service_ajax reached.');
        error_log('[MoBooking SaveSvc Debug] RAW POST data: ' . print_r($_POST, true));

        $nonce_verified = check_ajax_referer('mobooking_services_nonce', 'nonce', false); // false to not die
        if (!$nonce_verified) {
            error_log('[MoBooking SaveSvc Debug] Nonce verification FAILED.');
            wp_send_json_error(['message' => __('Nonce verification failed. Please refresh and try again.', 'mobooking')], 403);
            return;
        }
        error_log('[MoBooking SaveSvc Debug] Nonce verified successfully.');

        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('[MoBooking SaveSvc Debug] User not logged in.');
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        error_log('[MoBooking SaveSvc Debug] User ID: ' . $user_id);

        $service_id = isset($_POST['service_id']) && !empty($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        error_log('[MoBooking SaveSvc Debug] Service ID for save/update: ' . $service_id);

        if (empty($_POST['name'])) {
            error_log('[MoBooking SaveSvc Debug] Validation Error: Service name is required.');
            wp_send_json_error(['message' => __('Service name is required.', 'mobooking')], 400);
            return;
        }
        if (!isset($_POST['price']) || !is_numeric($_POST['price'])) {
            error_log('[MoBooking SaveSvc Debug] Validation Error: Valid price is required. Received: ' . print_r($_POST['price'], true));
            wp_send_json_error(['message' => __('Valid price is required.', 'mobooking')], 400);
            return;
        }
         if (!isset($_POST['duration']) || !ctype_digit(strval($_POST['duration']))) {
            error_log('[MoBooking SaveSvc Debug] Validation Error: Valid duration (positive integer) is required. Received: ' . print_r($_POST['duration'], true));
            wp_send_json_error(['message' => __('Valid duration (positive integer) is required.', 'mobooking')], 400);
            return;
        }

        $data_for_service_method = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post(isset($_POST['description']) ? $_POST['description'] : ''),
            'price' => floatval($_POST['price']),
            'duration' => intval($_POST['duration']),
            'category' => sanitize_text_field(isset($_POST['category']) ? $_POST['category'] : ''),
            'icon' => sanitize_text_field(isset($_POST['icon']) ? $_POST['icon'] : ''),
            'image_url' => esc_url_raw(isset($_POST['image_url']) ? $_POST['image_url'] : ''),
            'status' => sanitize_text_field(isset($_POST['status']) ? $_POST['status'] : 'active'),
        ];
        error_log('[MoBooking SaveSvc Debug] Data for add/update_service: ' . print_r($data_for_service_method, true));

        $result_service_save = null;
        $message = '';

        if ($service_id) { // Update
            error_log('[MoBooking SaveSvc Debug] Attempting to update service ID: ' . $service_id);
            $result_service_save = $this->update_service($service_id, $user_id, $data_for_service_method);
            $message = __('Service updated successfully.', 'mobooking');
        } else { // Add
            error_log('[MoBooking SaveSvc Debug] Attempting to add new service.');
            $result_service_save = $this->add_service($user_id, $data_for_service_method);
            $message = __('Service added successfully.', 'mobooking');
            if (!is_wp_error($result_service_save)) {
                 $service_id = $result_service_save; // Get new ID for options processing
                 error_log('[MoBooking SaveSvc Debug] New service added with ID: ' . $service_id);
            }
        }

        if (is_wp_error($result_service_save)) {
            error_log('[MoBooking SaveSvc Debug] Error saving/updating service: ' . $result_service_save->get_error_message());
            wp_send_json_error(['message' => $result_service_save->get_error_message()], ('not_owner' === $result_service_save->get_error_code() ? 403 : 500) );
            return;
        }
        error_log('[MoBooking SaveSvc Debug] Service main data saved/updated successfully for service_id: ' . $service_id);

        // Process service options if provided
        if (isset($_POST['service_options'])) {
            $options_json = stripslashes($_POST['service_options']);
            error_log('[MoBooking SaveSvc Debug] Received service_options JSON string: ' . $options_json);
            $options = json_decode($options_json, true);

            if (is_array($options)) {
                error_log('[MoBooking SaveSvc Debug] Decoded options array: ' . print_r($options, true));
                error_log('[MoBooking SaveSvc Debug] Deleting existing options for service_id: ' . $service_id);
                $this->delete_options_for_service($service_id, $user_id);

                foreach ($options as $idx => $option_data_from_client) {
                    error_log("[MoBooking SaveSvc Debug] Processing option #{$idx}: " . print_r($option_data_from_client, true));

                    $current_option_values_str = isset($option_data_from_client['option_values']) ? stripslashes($option_data_from_client['option_values']) : null;
                    $processed_option_values_for_db = null;
                    $option_type_for_values = isset($option_data_from_client['type']) ? sanitize_text_field($option_data_from_client['type']) : '';

                    if (!is_null($current_option_values_str) && !empty(trim($current_option_values_str))) {
                        if (in_array($option_type_for_values, ['select', 'radio'])) {
                            $decoded_values = json_decode($current_option_values_str, true);
                            if (is_array($decoded_values)) {
                                $processed_option_values_for_db = wp_json_encode($decoded_values);
                            } else {
                                $processed_option_values_for_db = wp_json_encode([]);
                                error_log("[MoBooking SaveSvc Debug] Invalid JSON for option_values (select/radio) for option '{$option_data_from_client['name']}': " . $current_option_values_str);
                            }
                        } else {
                            $processed_option_values_for_db = null;
                        }
                    } else if (in_array($option_type_for_values, ['select', 'radio'])) {
                        $processed_option_values_for_db = wp_json_encode([]);
                    }

                    $clean_option_data = [
                        'name' => isset($option_data_from_client['name']) ? sanitize_text_field($option_data_from_client['name']) : '',
                        'description' => isset($option_data_from_client['description']) ? wp_kses_post($option_data_from_client['description']) : '',
                        'type' => $option_type_for_values,
                        'is_required' => !empty($option_data_from_client['is_required']) && $option_data_from_client['is_required'] === '1' ? 1 : 0,
                        'price_impact_type' => isset($option_data_from_client['price_impact_type']) ? sanitize_text_field($option_data_from_client['price_impact_type']) : null,
                        'price_impact_value' => !empty($option_data_from_client['price_impact_value']) ? floatval($option_data_from_client['price_impact_value']) : null,
                        'option_values' => $processed_option_values_for_db,
                        'sort_order' => isset($option_data_from_client['sort_order']) ? intval($option_data_from_client['sort_order']) : 0,
                    ];

                    if (!empty($clean_option_data['name'])) {
                         error_log("[MoBooking SaveSvc Debug] Adding/updating option: " . print_r($clean_option_data, true));
                         $option_result = $this->add_service_option($user_id, $service_id, $clean_option_data);
                         if (is_wp_error($option_result)) {
                            error_log("[MoBooking SaveSvc Debug] Error adding service option '{$clean_option_data['name']}': " . $option_result->get_error_message());
                         } else {
                            error_log("[MoBooking SaveSvc Debug] Successfully added option '{$clean_option_data['name']}'. New option ID: " . $option_result);
                         }
                    } else {
                        error_log("[MoBooking SaveSvc Debug] Skipped processing option #{$idx} due to empty name.");
                    }
                }
                error_log('[MoBooking SaveSvc Debug] Finished processing service options.');

            } else if (!empty($_POST['service_options'])) {
                 error_log('[MoBooking SaveSvc Debug] Error: service_options was not empty but failed json_decode. Original: ' . $options_json);
                 wp_send_json_error(['message' => __('Invalid format for service options data.', 'mobooking')], 400);
                 return;
            }
        } else {
            error_log('[MoBooking SaveSvc Debug] No service_options provided in POST.');
        }

        $saved_service = $this->get_service($service_id, $user_id);
        if ($saved_service) {
            error_log('[MoBooking SaveSvc Debug] Successfully saved and retrieved service. Sending success response.');
            wp_send_json_success(['message' => $message, 'service' => $saved_service]);
        } else {
            error_log('[MoBooking SaveSvc Debug] Error: Could not retrieve service after saving. Service ID: ' . $service_id);
            wp_send_json_error(['message' => __('Could not retrieve service after saving.', 'mobooking')], 500);
        }
        // wp_die(); // Not strictly necessary
    }


    // --- Service Option CRUD Methods ---

    public function add_service_option(int $user_id, int $service_id, array $data) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid user or service ID.', 'mobooking'));
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return new \WP_Error('service_not_owner', __('You do not own the parent service.', 'mobooking'));
        }
        if ( empty($data['name']) || empty($data['type']) ) {
            return new \WP_Error('missing_fields', __('Option name and type are required.', 'mobooking'));
        }

        $defaults = array(
            'description' => '',
            'is_required' => 0,
            'price_impact_type' => null,
            'price_impact_value' => null,
            'option_values' => null, // Should be JSON string or null
            'sort_order' => 0
        );
        $option_data = wp_parse_args($data, $defaults);

        $table_name = Database::get_table_name('service_options');

        $inserted = $this->wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'service_id' => $service_id,
                'name' => sanitize_text_field($option_data['name']),
                'description' => wp_kses_post($option_data['description']),
                'type' => sanitize_text_field($option_data['type']),
                'is_required' => boolval($option_data['is_required']),
                'price_impact_type' => $option_data['price_impact_type'], // Already sanitized
                'price_impact_value' => $option_data['price_impact_value'], // Already floatval or null
                'option_values' => $option_data['option_values'], // Already JSON string or null
                'sort_order' => intval($option_data['sort_order']),
                'created_at' => current_time('mysql', 1), // GMT
                'updated_at' => current_time('mysql', 1), // GMT
            ),
            // Ensure formats match the data being inserted
            array(
                '%d', // user_id
                '%d', // service_id
                '%s', // name
                '%s', // description
                '%s', // type
                '%d', // is_required (boolval results in 0 or 1)
                '%s', // price_impact_type (string or null)
                (is_null($option_data['price_impact_value']) ? '%s' : '%f'), // price_impact_value (float or null)
                '%s', // option_values (JSON string or null)
                '%d', // sort_order
                '%s', // created_at
                '%s'  // updated_at
            )
        );

        if (false === $inserted) {
            return new \WP_Error('db_error', __('Could not add service option to the database.', 'mobooking'));
        }
        return $this->wpdb->insert_id;
    }

    public function get_service_option(int $option_id, int $user_id) {
        if ( empty($user_id) || empty($option_id) ) {
            return null;
        }
        if ( !$this->_verify_option_ownership($option_id, $user_id) ) {
            return null; // Or WP_Error
        }
        $table_name = Database::get_table_name('service_options');
        $option = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $table_name WHERE option_id = %d AND user_id = %d", $option_id, $user_id ), ARRAY_A );
        if ($option && !empty($option['option_values']) && is_string($option['option_values'])) {
            // Assuming option_values is stored as a JSON string.
            // The JS side now expects this as a string, so no need to decode here for the PHP methods.
            // $option['option_values'] = json_decode($option['option_values'], true);
        }
        return $option;
    }

    public function get_service_options(int $service_id, int $user_id) {
        if ( empty($user_id) || empty($service_id) ) {
            return array();
        }
        // Not verifying parent service ownership here, as options are directly tied to user_id as well.
        // If options didn't have user_id, parent check would be essential.
        $table_name = Database::get_table_name('service_options');
        $options = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM $table_name WHERE service_id = %d AND user_id = %d ORDER BY sort_order ASC", $service_id, $user_id ), ARRAY_A );

        // No need to json_decode option_values here if JS expects a string.
        // The JS `populateForm` will handle parsing for its textarea.
        // If other PHP parts need it decoded, they can do so.
        return $options; // Returns array of arrays, option_values is JSON string.
    }

    public function update_service_option(int $option_id, int $user_id, array $data) {
        if ( empty($user_id) || empty($option_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid option or user ID.', 'mobooking'));
        }
        if ( !$this->_verify_option_ownership($option_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own this service option.', 'mobooking'));
        }
        if ( empty($data) ) {
            return new \WP_Error('no_data', __('No data provided for update.', 'mobooking'));
        }

        $table_name = Database::get_table_name('service_options');

        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['type'])) { $update_data['type'] = sanitize_text_field($data['type']); $update_formats[] = '%s'; }
        if (isset($data['is_required'])) { $update_data['is_required'] = boolval($data['is_required']); $update_formats[] = '%d'; }
        if (array_key_exists('price_impact_type', $data)) { $update_data['price_impact_type'] = !is_null($data['price_impact_type']) ? sanitize_text_field($data['price_impact_type']) : null; $update_formats[] = '%s'; }
        if (array_key_exists('price_impact_value', $data)) { $update_data['price_impact_value'] = !is_null($data['price_impact_value']) ? floatval($data['price_impact_value']) : null; $update_formats[] = '%f'; }
        if (array_key_exists('option_values', $data)) { $update_data['option_values'] = !is_null($data['option_values']) ? wp_json_encode($data['option_values']) : null; $update_formats[] = '%s'; }
        if (isset($data['sort_order'])) { $update_data['sort_order'] = intval($data['sort_order']); $update_formats[] = '%d'; }

        if (empty($update_data)) {
            return new \WP_Error('no_valid_data', __('No valid data provided for update.', 'mobooking'));
        }
        $update_data['updated_at'] = current_time('mysql', 1); // GMT
        $update_formats[] = '%s';

        $updated = $this->wpdb->update(
            $table_name,
            $update_data,
            array('option_id' => $option_id, 'user_id' => $user_id),
            $update_formats,
            array('%d', '%d')
        );

        if (false === $updated) {
            return new \WP_Error('db_error', __('Could not update service option in the database.', 'mobooking'));
        }
        return true;
    }

    public function delete_service_option(int $option_id, int $user_id) {
        if ( empty($user_id) || empty($option_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid option or user ID.', 'mobooking'));
        }
        if ( !$this->_verify_option_ownership($option_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own this service option.', 'mobooking'));
        }

        $table_name = Database::get_table_name('service_options');
        $deleted = $this->wpdb->delete(
            $table_name,
            array('option_id' => $option_id, 'user_id' => $user_id),
            array('%d', '%d')
        );

        if (false === $deleted) {
            return new \WP_Error('db_error', __('Could not delete service option from the database.', 'mobooking'));
        }
        return true;
    }

    public function delete_options_for_service(int $service_id, int $user_id) {
        if ( empty($user_id) || empty($service_id) ) {
             return new \WP_Error('invalid_ids', __('Invalid service or user ID.', 'mobooking'));
        }
        // This verifies that the user owns the PARENT service before bulk-deleting its options.
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own the parent service of these options.', 'mobooking'));
        }

        $table_name = Database::get_table_name('service_options');
        // user_id is also in service_options table, so we can use it directly for safety.
        $deleted = $this->wpdb->delete(
            $table_name,
            array('service_id' => $service_id, 'user_id' => $user_id),
            array('%d', '%d')
        );
        if (false === $deleted) {
             return new \WP_Error('db_error', __('Could not delete service options for the service.', 'mobooking'));
        }
        return true; // Number of rows affected
    }
}
