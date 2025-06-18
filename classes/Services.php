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

require_once __DIR__ . '/ServiceOptions.php';

class Services {
    private $wpdb;
    private $service_options_manager;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->service_options_manager = new ServiceOptions();
    }

    // --- Ownership Verification Helper Methods ---

    private function _verify_service_ownership(int $service_id, int $user_id): bool {
        if (empty($service_id) || empty($user_id)) return false;
        $table_name = Database::get_table_name('services');
        $service = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT service_id FROM $table_name WHERE service_id = %d AND user_id = %d", $service_id, $user_id ) );
        return !is_null($service);
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
            $options_raw = $this->service_options_manager->get_service_options($service_id, $user_id); // This returns array of arrays/objects
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

        // Base SQL and parameters for counting
        $sql_count_base = " FROM $table_name WHERE user_id = %d";
        $params = [$user_id];

        // Build WHERE clause for filtering
        $sql_where = "";
        if ( !empty($args['status']) ) {
            $sql_where .= " AND status = %s";
            $params[] = $args['status'];
        }
        if ( !empty($args['category']) ) {
            $sql_where .= " AND category = %s";
            $params[] = $args['category'];
        }
        if ( !empty($args['search_query']) ) {
            $search_term = '%' . $this->wpdb->esc_like($args['search_query']) . '%';
            $sql_where .= " AND (name LIKE %s OR description LIKE %s OR category LIKE %s)";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Get total count
        $total_count_sql = "SELECT COUNT(service_id)" . $sql_count_base . $sql_where;
        $total_count = $this->wpdb->get_var($this->wpdb->prepare($total_count_sql, ...$params));

        // SQL for fetching services data
        $sql_select = "SELECT *" . $sql_count_base . $sql_where;

        // Order and pagination
        $valid_orderby_columns = ['service_id', 'name', 'price', 'duration', 'category', 'status', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $valid_orderby_columns) ? $args['orderby'] : 'name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql_select .= " ORDER BY " . $orderby . " " . $order;
        $sql_select .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $args['number'], $args['offset']);

        $services_data = $this->wpdb->get_results($this->wpdb->prepare($sql_select, ...$params), ARRAY_A);

        if ($services_data) {
            foreach ($services_data as $key => $service) {
                if (is_array($service)) { // Ensure it's an array before trying to access by key
                    $options_raw = $this->service_options_manager->get_service_options($service['service_id'], $user_id);
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
        // Return data and pagination info
        return [
            'services' => $services_data,
            'total_count' => intval($total_count),
            'per_page' => intval($args['number']),
            'current_page' => intval($args['offset'] / $args['number']) + 1,
        ];
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
        $this->service_options_manager->delete_options_for_service($service_id, $user_id); // This also verifies ownership

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
        add_action('wp_ajax_mobooking_save_service', [$this, 'handle_save_service_ajax']); // Covers Create and Update for service + options

        // AJAX handlers for individual service options
        add_action('wp_ajax_mobooking_get_service_options', [$this, 'handle_get_service_options_ajax']);
        add_action('wp_ajax_mobooking_add_service_option', [$this, 'handle_add_service_option_ajax']);
        add_action('wp_ajax_mobooking_update_service_option', [$this, 'handle_update_service_option_ajax']);
        add_action('wp_ajax_mobooking_delete_service_option', [$this, 'handle_delete_service_option_ajax']);
        add_action('wp_ajax_mobooking_get_service_details', [$this, 'handle_get_service_details_ajax']); // For editing

        // For public booking form
        add_action('wp_ajax_nopriv_mobooking_get_public_services', [$this, 'handle_get_public_services_ajax']);
        add_action('wp_ajax_mobooking_get_public_services', [$this, 'handle_get_public_services_ajax']);
    }

    public function handle_get_public_services_ajax() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (empty($tenant_id)) {
            wp_send_json_error(['message' => __('Tenant ID is required.', 'mobooking')], 400);
            return;
        }

        // Get only active services for public view.
        // get_services_by_user now returns an array with 'services', 'total_count' etc.
        $result = $this->get_services_by_user($tenant_id, ['status' => 'active', 'number' => -1]); // -1 for all active

        if (is_wp_error($result)) { // Should not happen if get_services_by_user returns array
            wp_send_json_error(['message' => __('Error retrieving services.', 'mobooking')], 500);
        } else {
            $services_for_public = [];
            if (!empty($result['services'])) {
                foreach ($result['services'] as $service_item) {
                    $item = (array) $service_item;
                    if (isset($item['price'])) {
                        $item['price_formatted'] = number_format_i18n(floatval($item['price']), 2);
                    } else {
                        $item['price_formatted'] = __('N/A', 'mobooking');
                    }
                    if (!isset($item['options']) || !is_array($item['options'])) {
                        $item['options'] = [];
                    }
                    $services_for_public[] = $item;
                }
            }
            wp_send_json_success($services_for_public);
        }
    }

    public function handle_get_services_ajax() {
        // error_log('[MoBooking Services Debug] handle_get_services_ajax reached.');
        // error_log('[MoBooking Services Debug] POST data: ' . print_r($_POST, true));

        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $args = [
            'status' => isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : null,
            'category' => isset($_POST['category_filter']) ? sanitize_text_field($_POST['category_filter']) : null,
            'search_query' => isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : null,
            'number' => isset($_POST['per_page']) ? intval($_POST['per_page']) : 20,
            'offset' => (isset($_POST['paged']) && intval($_POST['paged']) > 0) ? (intval($_POST['paged']) - 1) * intval(isset($_POST['per_page']) ? $_POST['per_page'] : 20) : 0,
            'orderby' => isset($_POST['orderby']) ? sanitize_key($_POST['orderby']) : 'name',
            'order' => isset($_POST['order']) ? sanitize_key($_POST['order']) : 'ASC',
        ];
        if (empty($args['status'])) $args['status'] = null; // Ensure 'all' statuses if filter is empty string

        $result = $this->get_services_by_user($user_id, $args);

        // get_services_by_user now returns an array with 'services', 'total_count' etc.
        // No need to check is_wp_error if it always returns this array structure.
        wp_send_json_success($result);
    }

    public function handle_delete_service_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
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

    // AJAX handler for service OPTIONS
    public function handle_get_service_options_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        if (empty($service_id)) { wp_send_json_error(['message' => __('Service ID is required.', 'mobooking')], 400); return; }

        // Verify parent service ownership first
        if (!$this->_verify_service_ownership($service_id, $user_id)) {
            wp_send_json_error(['message' => __('Service not found or permission denied.', 'mobooking')], 404); return;
        }

        $options = $this->service_options_manager->get_service_options($service_id, $user_id);
        wp_send_json_success($options);
    }

    public function handle_add_service_option_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $option_data_json = isset($_POST['option_data']) ? stripslashes_deep($_POST['option_data']) : '';
        $option_data = json_decode($option_data_json, true);

        if (empty($service_id) || json_last_error() !== JSON_ERROR_NONE || empty($option_data)) {
            wp_send_json_error(['message' => __('Service ID and valid option data are required.', 'mobooking')], 400);
            return;
        }

        // Verify parent service ownership
        if (!$this->_verify_service_ownership($service_id, $user_id)) {
            wp_send_json_error(['message' => __('Service not found or permission denied for adding option.', 'mobooking')], 404); return;
        }

        // Sanitize option_values if it's part of $option_data and needs to be JSON
        if (isset($option_data['option_values']) && is_array($option_data['option_values'])) {
            $option_data['option_values'] = wp_json_encode($option_data['option_values']);
        }


        $result = $this->service_options_manager->add_service_option($user_id, $service_id, $option_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        } else {
            $new_option_id = $result;
            $new_option = $this->service_options_manager->get_service_option($new_option_id, $user_id);
            wp_send_json_success(['message' => __('Service option added successfully.', 'mobooking'), 'option' => $new_option]);
        }
    }

    public function handle_update_service_option_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $option_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
        $option_data_json = isset($_POST['option_data']) ? stripslashes_deep($_POST['option_data']) : '';
        $option_data = json_decode($option_data_json, true);

        if (empty($option_id) || json_last_error() !== JSON_ERROR_NONE || empty($option_data)) {
            wp_send_json_error(['message' => __('Option ID and valid option data are required.', 'mobooking')], 400);
            return;
        }

        // Sanitize option_values if it's part of $option_data and needs to be JSON
        // The update_service_option method in ServiceOptions class already handles wp_json_encode
        // if $option_data['option_values'] is passed as an array.

        $result = $this->service_options_manager->update_service_option($option_id, $user_id, $option_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'not_owner' ? 403 : 400) );
        } else {
            $updated_option = $this->service_options_manager->get_service_option($option_id, $user_id);
            wp_send_json_success(['message' => __('Service option updated successfully.', 'mobooking'), 'option' => $updated_option]);
        }
    }

    public function handle_delete_service_option_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $option_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
        if (empty($option_id)) {
            wp_send_json_error(['message' => __('Option ID is required.', 'mobooking')], 400);
            return;
        }

        $result = $this->service_options_manager->delete_service_option($option_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'not_owner' ? 403 : 400) );
        } else {
            wp_send_json_success(['message' => __('Service option deleted successfully.', 'mobooking')]);
        }
    }

    public function handle_save_service_ajax() {
        ob_start();
        // die('[DEBUG] Reached handle_save_service_ajax start.');
        error_log('[MoBooking SaveSvc Debug] handle_save_service_ajax reached.');
        error_log('[MoBooking SaveSvc Debug] RAW POST data: ' . print_r($_POST, true));

        $nonce_verified = check_ajax_referer('mobooking_services_nonce', 'nonce', false); // false to not die
        if (!$nonce_verified) {
            error_log('[MoBooking SaveSvc Debug] Nonce verification FAILED.');
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Nonce verification failed. Please refresh and try again.', 'mobooking')], 403);
            return;
        }
        // die('[DEBUG] Nonce verification PASSED.');
        error_log('[MoBooking SaveSvc Debug] Nonce verified successfully.');

        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('[MoBooking SaveSvc Debug] User not logged in.');
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        // die('[DEBUG] User ID check PASSED. User ID: ' . $user_id);
        error_log('[MoBooking SaveSvc Debug] User ID: ' . $user_id);

        $service_id = isset($_POST['service_id']) && !empty($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        // die('[DEBUG] Service ID determined: ' . $service_id);
        error_log('[MoBooking SaveSvc Debug] Service ID for save/update: ' . $service_id);

    $service_name_from_post = isset($_POST['name']) ? (string) $_POST['name'] : '';
    $trimmed_service_name = trim($service_name_from_post);

    if (empty($trimmed_service_name)) {
        error_log('[MoBooking SaveSvc Debug] Validation Error: Service name (after trim) is required. Original POST name: \'' . $service_name_from_post . '\'');
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Service name is required.', 'mobooking')], 400);
            return;
        }

    $price_from_post = isset($_POST['price']) ? $_POST['price'] : null;
    if (is_null($price_from_post) || !is_numeric($price_from_post)) {
        error_log('[MoBooking SaveSvc Debug] Validation Error: Valid price is required. Received: ' . print_r($price_from_post, true));
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Valid price is required.', 'mobooking')], 400);
            return;
        }

    $duration_from_post = isset($_POST['duration']) ? strval($_POST['duration']) : null;
    if (is_null($duration_from_post) || !ctype_digit($duration_from_post) || intval($duration_from_post) <=0) {
        error_log('[MoBooking SaveSvc Debug] Validation Error: Duration validation failed. Input: \'' . print_r($duration_from_post, true) . '\'. Must be a positive integer.');
            if (ob_get_length()) ob_clean();
        wp_send_json_error(['message' => __('Valid positive duration (integer) is required.', 'mobooking')], 400);
            return;
        }

    // Prepare data, converting empty optional strings to null
    $category_from_post = isset($_POST['category']) ? trim($_POST['category']) : '';
    $icon_from_post = isset($_POST['icon']) ? trim($_POST['icon']) : '';
    $image_url_from_post = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';

        $data_for_service_method = [
        'name' => sanitize_text_field($trimmed_service_name),
            'description' => wp_kses_post(isset($_POST['description']) ? $_POST['description'] : ''),
        'price' => floatval($price_from_post),
        'duration' => intval($duration_from_post),
        'category' => !empty($category_from_post) ? sanitize_text_field($category_from_post) : null,
        'icon' => !empty($icon_from_post) ? sanitize_text_field($icon_from_post) : null,
        'image_url' => !empty($image_url_from_post) ? esc_url_raw($image_url_from_post) : null,
            'status' => sanitize_text_field(isset($_POST['status']) ? $_POST['status'] : 'active'),
        ];
    error_log('[MoBooking SaveSvc Debug] Data for add/update_service (with nulls for empty optionals): ' . print_r($data_for_service_method, true));

        $result_service_save = null;
        $message = '';

        // die('[DEBUG] All initial validations passed. About to call add/update service. Service ID: ' . $service_id);
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
            if (ob_get_length()) ob_clean();
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
                $this->service_options_manager->delete_options_for_service($service_id, $user_id);

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
                         $option_result = $this->service_options_manager->add_service_option($user_id, $service_id, $clean_option_data);
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
                 if (ob_get_length()) ob_clean();
                 wp_send_json_error(['message' => __('Invalid format for service options data.', 'mobooking')], 400);
                 return;
            }
        } else {
            error_log('[MoBooking SaveSvc Debug] No service_options provided in POST.');
        }

        $saved_service = $this->get_service($service_id, $user_id);
        if ($saved_service) {
            error_log('[MoBooking SaveSvc Debug] Successfully saved and retrieved service. Sending success response.');
            if (ob_get_length()) ob_clean();
            wp_send_json_success(['message' => $message, 'service' => $saved_service]);
        } else {
            error_log('[MoBooking SaveSvc Debug] Error: Could not retrieve service after saving. Service ID: ' . $service_id);
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Could not retrieve service after saving.', 'mobooking')], 500);
            // No explicit return here, as wp_send_json_error will die.
            // However, if it didn't, the ob_end_clean below would catch it.
        }
        // wp_die(); // Not strictly necessary as wp_send_json_* calls wp_die().
        if (ob_get_length()) ob_end_clean(); // Final cleanup if buffer is still active.
    }

    public function handle_get_service_details_ajax() {
        // Make check_ajax_referer not die, so we can send a custom JSON response
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Error: User not authenticated.', 'mobooking')], 401); // 401 Unauthorized
            return;
        }

        if (!isset($_POST['service_id']) || empty($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
            wp_send_json_error(['message' => __('Error: Service ID is missing or invalid.', 'mobooking')], 400);
            return;
        }
        $service_id = (int) $_POST['service_id'];

        $service_details = $this->get_service($service_id, $user_id);

        if (is_wp_error($service_details)) { // If get_service could return WP_Error for some reason
            wp_send_json_error(['message' => $service_details->get_error_message()], 500);
            return;
        }

        if (!$service_details) { // Assuming get_service returns null for "not found" or permission issues
            wp_send_json_error(['message' => __('Error: Service not found or access denied.', 'mobooking')], 404);
            return;
        }

        wp_send_json_success(['service' => $service_details]); // Ensure data is keyed under 'service' if JS expects response.data.service
    }
}
