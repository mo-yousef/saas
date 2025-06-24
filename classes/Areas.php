<?php
namespace MoBooking\Classes;

if ( ! defined( 'ABSPATH' ) ) exit;

class Areas {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function register_ajax_actions() {
        add_action('wp_ajax_mobooking_get_areas', [$this, 'handle_get_areas_ajax']);
        add_action('wp_ajax_mobooking_add_area', [$this, 'handle_add_area_ajax']);
        // Make sure mobooking_update_area is registered if it's used by frontend (it is in dashboard-areas.js)
        add_action('wp_ajax_mobooking_update_area', [$this, 'handle_update_area_ajax']);
        add_action('wp_ajax_mobooking_delete_area', [$this, 'handle_delete_area_ajax']);

        // New AJAX actions for country/city/area selection
        add_action('wp_ajax_mobooking_get_countries', [$this, 'handle_get_countries_ajax']);
        add_action('wp_ajax_mobooking_get_cities_for_country', [$this, 'handle_get_cities_for_country_ajax']);
        add_action('wp_ajax_mobooking_get_areas_for_city', [$this, 'handle_get_areas_for_city_ajax']);

        // Public AJAX actions
        add_action('wp_ajax_nopriv_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
        add_action('wp_ajax_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
    }

    /**
     * Loads area data from the JSON file.
     * Uses WordPress transients for caching.
     *
     * @return array|WP_Error The decoded JSON data or a WP_Error on failure.
     */
    private function load_area_data_from_json() {
        $cache_key = 'mobooking_area_data_json';
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        $json_file_path = MOBOOKING_PLUGIN_DIR . 'data/service-areas-data.json'; // Assuming MOBOOKING_PLUGIN_DIR is defined
        if (!defined('MOBOOKING_PLUGIN_DIR')) {
            // Fallback if the constant is not defined, adjust path as necessary
            // This might happen if the plugin's main file hasn't defined it yet when this class is instantiated.
            // A better approach would be to pass the plugin base path to the constructor or define it early.
            // For now, assuming it's accessible relative to this file's directory.
            $json_file_path = dirname(__DIR__) . '/data/service-areas-data.json';
             if (!file_exists($json_file_path)) { // Try one level up for data if classes is in plugin_root/classes/
                $json_file_path = dirname(dirname(__DIR__)) . '/data/service-areas-data.json';
            }
        }


        if (!file_exists($json_file_path)) {
            error_log("MoBooking Areas: JSON data file not found at $json_file_path");
            return new \WP_Error('file_not_found', __('Area data file is missing.', 'mobooking'));
        }

        $json_content = file_get_contents($json_file_path);
        if (false === $json_content) {
            error_log("MoBooking Areas: Could not read JSON data file at $json_file_path");
            return new \WP_Error('file_read_error', __('Could not read area data file.', 'mobooking'));
        }

        $data = json_decode($json_content, true);
        if (null === $data && json_last_error() !== JSON_ERROR_NONE) {
            error_log("MoBooking Areas: Error decoding JSON data: " . json_last_error_msg());
            return new \WP_Error('json_decode_error', __('Error decoding area data.', 'mobooking') . ' ' . json_last_error_msg());
        }

        set_transient($cache_key, $data, HOUR_IN_SECONDS); // Cache for 1 hour
        return $data;
    }

    public function handle_check_zip_code_public_ajax() {
        check_ajax_referer('mobooking_booking_form_nonce', 'nonce');

        $zip_code = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;

        if (empty($zip_code) || empty($tenant_id)) {
            wp_send_json_error(['message' => __('ZIP code and tenant ID are required.', 'mobooking')], 400);
            return;
        }

        $is_serviced = $this->is_zip_code_serviced($zip_code, $tenant_id, $country_code);

        if ($is_serviced) {
            wp_send_json_success([
                'serviced' => true,
                'message' => __('Great! We service your area.', 'mobooking')
            ]);
        } else {
            wp_send_json_success([
                'serviced' => false,
                'message' => __('Sorry, we do not currently service this ZIP code.', 'mobooking')
            ]);
        }
    }

    public function add_area(int $user_id, array $data) {
        if (empty($user_id)) return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        if (empty($data['area_value']) || empty($data['country_code'])) {
            return new \WP_Error('missing_fields', __('Country and ZIP/Area Value are required.', 'mobooking'));
        }

        $area_type = isset($data['area_type']) ? sanitize_text_field($data['area_type']) : 'zip_code';
        $area_value = sanitize_text_field(str_replace(' ', '', strtoupper($data['area_value']))); // Normalize ZIP
        $country_code = sanitize_text_field(strtoupper($data['country_code']));

        $table_name = Database::get_table_name('service_areas');

        // Check for duplicates
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT area_id FROM $table_name WHERE user_id = %d AND area_type = %s AND area_value = %s AND country_code = %s",
            $user_id, $area_type, $area_value, $country_code
        ));
        if ($existing) {
            return new \WP_Error('duplicate_area', __('This service area is already added.', 'mobooking'));
        }

        $inserted = $this->wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'area_type' => $area_type,
                'area_value' => $area_value,
                'country_code' => $country_code,
                'created_at' => current_time('mysql', 1)
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) return new \WP_Error('db_error', __('Could not add service area.', 'mobooking'));
        return $this->wpdb->insert_id;
    }

    public function get_areas_by_user(int $user_id, $type = 'zip_code') {
        if (empty($user_id)) {
            return ['areas' => [], 'total_count' => 0, 'per_page' => 0, 'current_page' => 1];
        }
        $table_name = Database::get_table_name('service_areas');
        $area_type = sanitize_text_field($type);

        // Args for pagination (could be extended with more filters like country_code later)
        $paged = isset($args['paged']) ? max(1, intval($args['paged'])) : 1;
        $limit = isset($args['limit']) ? max(1, intval($args['limit'])) : 20;
        $offset = ($paged - 1) * $limit;

        // Get total count for this user and type
        $total_count_sql = $this->wpdb->prepare(
            "SELECT COUNT(area_id) FROM $table_name WHERE user_id = %d AND area_type = %s",
            $user_id, $area_type
        );
        $total_count = $this->wpdb->get_var($total_count_sql);

        // Get paginated results
        $sql = $this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND area_type = %s ORDER BY country_code ASC, area_value ASC LIMIT %d OFFSET %d",
            $user_id, $area_type, $limit, $offset
        );
        $areas = $this->wpdb->get_results($sql, ARRAY_A);

        return [
            'areas' => $areas,
            'total_count' => (int) $total_count,
            'per_page' => $limit,
            'current_page' => $paged
        ];
    }

    public function update_area(int $area_id, int $user_id, array $data) {
        if (empty($user_id)) return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        if (empty($area_id)) return new \WP_Error('invalid_area_id', __('Invalid area ID.', 'mobooking'));

        $current_area = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM " . Database::get_table_name('service_areas') . " WHERE area_id = %d AND user_id = %d",
            $area_id, $user_id
        ), ARRAY_A);

        if (!$current_area) {
            return new \WP_Error('not_found_or_owner', __('Area not found or you do not own it.', 'mobooking'));
        }

        $update_payload = [];
        $update_formats = [];

        if (isset($data['area_value'])) {
            $area_value = sanitize_text_field(str_replace(' ', '', strtoupper($data['area_value'])));
            if (empty($area_value)) return new \WP_Error('missing_value', __('Area value cannot be empty.', 'mobooking'));
            $update_payload['area_value'] = $area_value;
            $update_formats[] = '%s';
        } else {
            $area_value = $current_area['area_value']; // Keep current if not provided
        }

        if (isset($data['country_code'])) {
            $country_code = sanitize_text_field(strtoupper($data['country_code']));
            if (empty($country_code)) return new \WP_Error('missing_country', __('Country code cannot be empty.', 'mobooking'));
            $update_payload['country_code'] = $country_code;
            $update_formats[] = '%s';
        } else {
            $country_code = $current_area['country_code']; // Keep current if not provided
        }

        // Area type is generally not updatable, use current.
        $area_type = $current_area['area_type'];

        // Check for duplicates only if area_value or country_code has changed
        if ($area_value !== $current_area['area_value'] || $country_code !== $current_area['country_code']) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT area_id FROM " . Database::get_table_name('service_areas') . " WHERE user_id = %d AND area_type = %s AND area_value = %s AND country_code = %s AND area_id != %d",
                $user_id, $area_type, $area_value, $country_code, $area_id
            ));
            if ($existing) {
                return new \WP_Error('duplicate_area', __('This service area (value/country combination) already exists.', 'mobooking'));
            }
        }

        if (empty($update_payload)) {
            return true; // No changes provided
        }

        $updated = $this->wpdb->update(
            Database::get_table_name('service_areas'),
            $update_payload,
            ['area_id' => $area_id, 'user_id' => $user_id],
            $update_formats,
            ['%d', '%d']
        );

        if (false === $updated) {
            return new \WP_Error('db_error', __('Could not update service area.', 'mobooking'));
        }
        return true;
    }

    public function delete_area(int $area_id, int $user_id) {
        if (empty($user_id)) return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));

        $table_name = Database::get_table_name('service_areas');
        // Verify ownership
        $owner_id = $this->wpdb->get_var($this->wpdb->prepare("SELECT user_id FROM $table_name WHERE area_id = %d", $area_id));
        if (intval($owner_id) !== $user_id) {
            return new \WP_Error('not_owner', __('You do not own this area.', 'mobooking'));
        }

        $deleted = $this->wpdb->delete($table_name, ['area_id' => $area_id, 'user_id' => $user_id], ['%d', '%d']);
        if (!$deleted) return new \WP_Error('db_error', __('Could not delete service area.', 'mobooking'));
        return true;
    }

    public function is_zip_code_serviced(string $zip_code, int $tenant_user_id, string $country_code = '') {
        if (empty($tenant_user_id) || empty($zip_code)) return false;
        $table_name = Database::get_table_name('service_areas');
        $normalized_zip = sanitize_text_field(str_replace(' ', '', strtoupper($zip_code)));
        $normalized_country = sanitize_text_field(strtoupper($country_code));

        $sql = $this->wpdb->prepare(
            "SELECT area_id FROM $table_name WHERE user_id = %d AND area_type = 'zip_code' AND area_value = %s",
            $tenant_user_id, $normalized_zip
        );
        if (!empty($normalized_country)) {
            $sql .= $this->wpdb->prepare(" AND country_code = %s", $normalized_country);
        }
        return (bool) $this->wpdb->get_var($sql);
    }

    // AJAX Handlers
    public function handle_get_areas_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $args = [
            'paged' => isset($_POST['paged']) ? intval($_POST['paged']) : 1,
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 20,
            // Add other filters like 'type' or 'country_code' if needed in future
        ];
        // Default to 'zip_code' type for now, can be parameterized if other types are used in dashboard
        $result = $this->get_areas_by_user($user_id, 'zip_code', $args);
        wp_send_json_success($result); // This now includes pagination data
    }

    public function handle_update_area_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        if (empty($area_id)) { wp_send_json_error(['message' => __('Area ID is required.', 'mobooking')], 400); return; }

        $data = [
            'area_value' => isset($_POST['area_value']) ? $_POST['area_value'] : null, // Let method handle if empty
            'country_code' => isset($_POST['country_code']) ? $_POST['country_code'] : null, // Let method handle if empty
        ];
        // Filter out null values, so only provided fields are passed to update_area
        $data = array_filter($data, function($value) { return !is_null($value); });

        $result = $this->update_area($area_id, $user_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        } else {
            $updated_area = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM " . Database::get_table_name('service_areas') . " WHERE area_id = %d", $area_id), ARRAY_A);
            wp_send_json_success(['message' => __('Service area updated.', 'mobooking'), 'area' => $updated_area]);
        }
    }

    public function handle_add_area_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Corrected nonce
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $data = [
            'area_type' => 'zip_code', // Defaulting to zip_code for now
            'area_value' => isset($_POST['area_value']) ? $_POST['area_value'] : '',
            'country_code' => isset($_POST['country_code']) ? $_POST['country_code'] : ''
        ];
        $result = $this->add_area($user_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        } else {
            // Fetch the newly added area to return its full data
            $new_area = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM " . Database::get_table_name('service_areas') . " WHERE area_id = %d", $result), ARRAY_A);
            wp_send_json_success(['message' => __('Service area added.', 'mobooking'), 'area' => $new_area]);
        }
    }

    public function handle_delete_area_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Corrected nonce
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }
        if (!isset($_POST['area_id']) || !is_numeric($_POST['area_id'])) {
            wp_send_json_error(['message' => __('Invalid area ID.', 'mobooking')], 400); return;
        }
        $area_id = intval($_POST['area_id']);
        $result = $this->delete_area($area_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ('not_owner' === $result->get_error_code() ? 403 : 500));
        } else {
            wp_send_json_success(['message' => __('Service area deleted.', 'mobooking')]);
        }
    }

    // New AJAX Handlers for Country/City/Area selection
    public function handle_get_countries_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Assuming dashboard nonce for these admin actions
        if (!current_user_can('manage_options')) { // Or a more specific capability
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'mobooking')], 403);
            return;
        }

        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            wp_send_json_error(['message' => $data->get_error_message()], 500);
            return;
        }

        $countries = [];
        foreach ($data as $country_code => $country_data) {
            $countries[] = [
                'code' => $country_code,
                'name' => isset($country_data['name']) ? $country_data['name'] : $country_code
            ];
        }

        wp_send_json_success(['countries' => $countries]);
    }

    public function handle_get_cities_for_country_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'mobooking')], 403);
            return;
        }

        $country_code = isset($_POST['country_code']) ? sanitize_text_field(strtoupper($_POST['country_code'])) : '';
        if (empty($country_code)) {
            wp_send_json_error(['message' => __('Country code is required.', 'mobooking')], 400);
            return;
        }

        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            wp_send_json_error(['message' => $data->get_error_message()], 500);
            return;
        }

        if (!isset($data[$country_code]) || !isset($data[$country_code]['cities'])) {
            wp_send_json_success(['cities' => []]); // Send empty array if country or cities not found
            return;
        }

        $cities = [];
        foreach ($data[$country_code]['cities'] as $city_name => $city_data) {
            $cities[] = ['name' => $city_name]; // Assuming city name is the key
        }
        // Sort cities alphabetically by name
        usort($cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });


        wp_send_json_success(['cities' => $cities]);
    }

    public function handle_get_areas_for_city_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'mobooking')], 403);
            return;
        }

        $country_code = isset($_POST['country_code']) ? sanitize_text_field(strtoupper($_POST['country_code'])) : '';
        $city_name = isset($_POST['city_name']) ? sanitize_text_field($_POST['city_name']) : '';

        if (empty($country_code) || empty($city_name)) {
            wp_send_json_error(['message' => __('Country code and city name are required.', 'mobooking')], 400);
            return;
        }

        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            wp_send_json_error(['message' => $data->get_error_message()], 500);
            return;
        }

        if (!isset($data[$country_code]['cities'][$city_name])) {
            wp_send_json_success(['areas' => []]); // Send empty array if city not found
            return;
        }

        $areas_in_city = $data[$country_code]['cities'][$city_name];
        // Sort areas by name
        usort($areas_in_city, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        wp_send_json_success(['areas' => $areas_in_city]);
    }
}
