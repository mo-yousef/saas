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
        // Existing AJAX actions
        add_action('wp_ajax_mobooking_get_areas', [$this, 'handle_get_areas_ajax']);
        add_action('wp_ajax_mobooking_add_area', [$this, 'handle_add_area_ajax']);
        add_action('wp_ajax_mobooking_update_area', [$this, 'handle_update_area_ajax']);
        add_action('wp_ajax_mobooking_delete_area', [$this, 'handle_delete_area_ajax']);

        // New AJAX actions for improved functionality
        add_action('wp_ajax_mobooking_get_countries', [$this, 'handle_get_countries_ajax']);
        add_action('wp_ajax_mobooking_get_cities_for_country', [$this, 'handle_get_cities_for_country_ajax']);
        add_action('wp_ajax_mobooking_get_areas_for_city', [$this, 'handle_get_areas_for_city_ajax']);
        add_action('wp_ajax_mobooking_add_bulk_areas', [$this, 'handle_add_bulk_areas_ajax']);

        // Public AJAX actions
        add_action('wp_ajax_nopriv_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
        add_action('wp_ajax_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
    }

    /**
     * Loads area data from the JSON file with caching
     */
    private function load_area_data_from_json() {
        $cache_key = 'mobooking_area_data_json';
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        $json_file_path = get_template_directory() . '/data/service-areas-data.json';

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

        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        return $data;
    }

    /**
     * Get list of available countries
     */
    public function get_countries() {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }

        $countries = [];
        if (is_array($data)) { // Changed: Iterate directly over $data
            foreach ($data as $country_code => $country_data) {
                if (is_array($country_data) && isset($country_data['name'])) {
                    $countries[] = [
                        'code' => $country_code,
                        'name' => $country_data['name']
                    ];
                }
            }
        }

        // Sort countries by name
        usort($countries, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $countries;
    }

    /**
     * Get cities for a specific country
     */
    public function get_cities_for_country($country_code) {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }

        $cities = [];
        // Changed: Use $data[$country_code] directly
        if (isset($data[$country_code]['cities']) && is_array($data[$country_code]['cities'])) {
            // $city_code is the city name (e.g., "Stockholm")
            // $city_data is the array of area objects for that city
            foreach ($data[$country_code]['cities'] as $city_name => $areas_in_city) {
                $cities[] = [
                    'code' => $city_name, // Use city name as the code, as JS expects
                    'name' => $city_name  // City name is the key
                ];
            }
        }

        // Sort cities by name
        usort($cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $cities;
    }

    /**
     * Get areas for a specific city
     */
    public function get_areas_for_city($country_code, $city_code) {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }

        $areas = [];
        // Changed: Path to $data[$country_code]['cities'][$city_code] which is the array of areas
        if (isset($data[$country_code]['cities'][$city_code]) &&
            is_array($data[$country_code]['cities'][$city_code])) {
            
            // $city_code here is the city name (e.g. "Stockholm")
            // $data[$country_code]['cities'][$city_code] is the array of area objects
            foreach ($data[$country_code]['cities'][$city_code] as $area_data_item) {
                if (is_array($area_data_item) && isset($area_data_item['name']) && isset($area_data_item['zip'])) {
                    $areas[] = [
                        'name' => $area_data_item['name'],
                        'zip_code' => $area_data_item['zip'], // Use 'zip' from JSON
                        'code' => $area_data_item['zip']     // Use 'zip' as code for consistency if needed
                    ];
                }
            }
        }

        return $areas;
    }

    /**
     * Add a new service area
     */
    public function add_area(int $user_id, array $data) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        }

        // Validate required fields
        $required_fields = ['country_name', 'area_name', 'area_zipcode'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new \WP_Error('missing_fields', __('All fields are required.', 'mobooking'));
            }
        }

        $area_type = 'zip_code'; // Default type
        $country_name = sanitize_text_field($data['country_name']);
        $area_name = sanitize_text_field($data['area_name']);
        $area_zipcode = sanitize_text_field(str_replace(' ', '', strtoupper($data['area_zipcode'])));

        $table_name = Database::get_table_name('service_areas');

        // Check for duplicates
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT area_id FROM $table_name WHERE user_id = %d AND area_zipcode = %s AND country_name = %s",
            $user_id, $area_zipcode, $country_name
        ));

        if ($existing) {
            return new \WP_Error('duplicate_area', __('This service area already exists.', 'mobooking'));
        }

        $inserted = $this->wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'area_type' => $area_type,
                'country_name' => $country_name,
                'area_name' => $area_name,
                'area_zipcode' => $area_zipcode,
                'area_value' => $area_zipcode, // For backward compatibility
                'country_code' => $this->get_country_code_from_name($country_name),
                'created_at' => current_time('mysql', 1)
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return new \WP_Error('db_error', __('Could not add service area.', 'mobooking'));
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Add multiple areas at once
     */
    public function add_bulk_areas(int $user_id, array $areas_data) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        }

        if (empty($areas_data) || !is_array($areas_data)) {
            return new \WP_Error('invalid_data', __('Invalid areas data.', 'mobooking'));
        }

        $table_name = Database::get_table_name('service_areas');
        $added_count = 0;
        $errors = [];

        foreach ($areas_data as $area_data) {
            // Validate required fields
            if (empty($area_data['area_name']) || empty($area_data['area_zipcode']) || empty($area_data['country_name'])) {
                $errors[] = __('Missing required fields for one or more areas.', 'mobooking');
                continue;
            }

            $country_name = sanitize_text_field($area_data['country_name']);
            $area_name = sanitize_text_field($area_data['area_name']);
            $area_zipcode = sanitize_text_field(str_replace(' ', '', strtoupper($area_data['area_zipcode'])));

            // Check for duplicates
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT area_id FROM $table_name WHERE user_id = %d AND area_zipcode = %s AND country_name = %s",
                $user_id, $area_zipcode, $country_name
            ));

            if ($existing) {
                continue; // Skip duplicates
            }

            // Determine country_code to be used for insertion
            // Prioritize country_code from JS if available and valid
            $db_country_code = '';
            if (!empty($area_data['country_code'])) {
                // Assume it's valid if provided from JS, as JS gets it from the JSON source's codes.
                $db_country_code = sanitize_text_field(strtoupper($area_data['country_code']));
            } else {
                // Fallback to deriving from name if code not provided by JS
                // (current JS version should always provide it)
                error_log("MoBooking Areas: country_code missing in bulk add payload for country name '" . $country_name . "'. Falling back to deriving from name. This may indicate an issue with JS payload if it happens frequently.");
                $db_country_code = $this->get_country_code_from_name($country_name);
            }
            // If $db_country_code is still empty here, it means neither direct code nor derived code was found/valid.
            // It will be stored as empty string in DB which is acceptable by schema.

            $inserted = $this->wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'area_type' => 'zip_code',
                    'country_name' => $country_name, // Still store country_name for readability/other uses
                    'area_name' => $area_name,
                    'area_zipcode' => $area_zipcode,
                    'area_value' => $area_zipcode, // For backward compatibility
                    'country_code' => $db_country_code, // Uses the (preferably direct) country_code
                    'created_at' => current_time('mysql', 1)
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $added_count++;
        } else {
            // If insert fails, it doesn't explicitly add to $errors array here,
            // but the $added_count won't increment.
            // It might be good to log $this->wpdb->last_error if $inserted is false.
            $errors[] = sprintf(__('Failed to insert area: Country - %s, Area - %s, ZIP - %s. DB Error: %s', 'mobooking'), esc_html($country_name), esc_html($area_name), esc_html($area_zipcode), esc_html($this->wpdb->last_error));
            }
        }

        return [
            'added_count' => $added_count,
            'errors' => $errors
        ];
    }

    /**
     * Get areas for a user with pagination and filtering
     */
    public function get_areas_by_user(int $user_id, $type = 'zip_code', $args = []) {
        if (empty($user_id)) {
            return ['areas' => [], 'total_count' => 0, 'per_page' => 0, 'current_page' => 1];
        }

        $table_name = Database::get_table_name('service_areas');
        $area_type = sanitize_text_field($type);

        // Pagination parameters
        $paged = isset($args['paged']) ? max(1, intval($args['paged'])) : 1;
        $limit = isset($args['limit']) ? max(1, intval($args['limit'])) : 20;
        $offset = ($paged - 1) * $limit;

        // Build WHERE clause
        $where_conditions = ['user_id = %d', 'area_type = %s'];
        $where_values = [$user_id, $area_type];

        // Add search filter
        if (!empty($args['search'])) {
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $where_conditions[] = '(area_name LIKE %s OR area_zipcode LIKE %s OR country_name LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Add country filter
        if (!empty($args['country'])) {
            $where_conditions[] = 'country_name = %s';
            $where_values[] = sanitize_text_field($args['country']);
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get total count
        $total_count_sql = "SELECT COUNT(area_id) FROM $table_name $where_clause";
        $total_count = $this->wpdb->get_var($this->wpdb->prepare($total_count_sql, $where_values));

        // Get paginated results
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY country_name ASC, area_name ASC LIMIT %d OFFSET %d";
        $areas = $this->wpdb->get_results($this->wpdb->prepare($sql, array_merge($where_values, [$limit, $offset])), ARRAY_A);

        return [
            'areas' => $areas,
            'total_count' => (int) $total_count,
            'per_page' => $limit,
            'current_page' => $paged
        ];
    }

    /**
     * Update an existing area
     */
    public function update_area(int $area_id, int $user_id, array $data) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        }

        if (empty($area_id)) {
            return new \WP_Error('invalid_area_id', __('Invalid area ID.', 'mobooking'));
        }

        $table_name = Database::get_table_name('service_areas');

        // Verify ownership
        $current_area = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE area_id = %d AND user_id = %d",
            $area_id, $user_id
        ), ARRAY_A);

        if (!$current_area) {
            return new \WP_Error('not_found_or_owner', __('Area not found or you do not own it.', 'mobooking'));
        }

        $update_payload = [];
        $update_formats = [];

        // Update country name
        if (isset($data['country_name'])) {
            $country_name = sanitize_text_field($data['country_name']);
            if (empty($country_name)) {
                return new \WP_Error('missing_country', __('Country name cannot be empty.', 'mobooking'));
            }
            $update_payload['country_name'] = $country_name;
            $update_payload['country_code'] = $this->get_country_code_from_name($country_name);
            $update_formats[] = '%s';
            $update_formats[] = '%s';
        }

        // Update area name
        if (isset($data['area_name'])) {
            $area_name = sanitize_text_field($data['area_name']);
            if (empty($area_name)) {
                return new \WP_Error('missing_area_name', __('Area name cannot be empty.', 'mobooking'));
            }
            $update_payload['area_name'] = $area_name;
            $update_formats[] = '%s';
        }

        // Update area zipcode
        if (isset($data['area_zipcode'])) {
            $area_zipcode = sanitize_text_field(str_replace(' ', '', strtoupper($data['area_zipcode'])));
            if (empty($area_zipcode)) {
                return new \WP_Error('missing_zipcode', __('ZIP code cannot be empty.', 'mobooking'));
            }
            $update_payload['area_zipcode'] = $area_zipcode;
            $update_payload['area_value'] = $area_zipcode; // For backward compatibility
            $update_formats[] = '%s';
            $update_formats[] = '%s';
        }

        if (empty($update_payload)) {
            return true; // No changes provided
        }

        // Check for duplicates if key fields changed
        $country_name = $update_payload['country_name'] ?? $current_area['country_name'];
        $area_zipcode = $update_payload['area_zipcode'] ?? $current_area['area_zipcode'];

        if (($country_name !== $current_area['country_name']) || ($area_zipcode !== $current_area['area_zipcode'])) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT area_id FROM $table_name WHERE user_id = %d AND area_zipcode = %s AND country_name = %s AND area_id != %d",
                $user_id, $area_zipcode, $country_name, $area_id
            ));

            if ($existing) {
                return new \WP_Error('duplicate_area', __('This service area already exists.', 'mobooking'));
            }
        }

        $updated = $this->wpdb->update(
            $table_name,
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

    /**
     * Delete an area
     */
    public function delete_area(int $area_id, int $user_id) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        }

        $table_name = Database::get_table_name('service_areas');

        // Verify ownership
        $owner_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE area_id = %d", 
            $area_id
        ));

        if (intval($owner_id) !== $user_id) {
            return new \WP_Error('not_owner', __('You do not own this area.', 'mobooking'));
        }

        $deleted = $this->wpdb->delete(
            $table_name, 
            ['area_id' => $area_id, 'user_id' => $user_id], 
            ['%d', '%d']
        );

        if (!$deleted) {
            return new \WP_Error('db_error', __('Could not delete service area.', 'mobooking'));
        }

        return true;
    }

    /**
     * Check if a ZIP code is serviced by a tenant
     */
    public function is_zip_code_serviced(string $zip_code, int $tenant_user_id, string $country_code = '') {
        if (empty($tenant_user_id) || empty($zip_code)) {
            return false;
        }

        $table_name = Database::get_table_name('service_areas');
        $normalized_zip = sanitize_text_field(str_replace(' ', '', strtoupper($zip_code)));

        $sql = $this->wpdb->prepare(
            "SELECT area_id FROM $table_name WHERE user_id = %d AND area_type = 'zip_code' AND (area_zipcode = %s OR area_value = %s)",
            $tenant_user_id, $normalized_zip, $normalized_zip
        );

        // Add country filter if provided
        if (!empty($country_code)) {
            $normalized_country = sanitize_text_field(strtoupper($country_code));
            $sql .= $this->wpdb->prepare(" AND country_code = %s", $normalized_country);
        }

        return (bool) $this->wpdb->get_var($sql);
    }

    /**
     * Get country code from country name
     */
    private function get_country_code_from_name($country_name) {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return '';
        }

        if (isset($data['countries']) && is_array($data['countries'])) {
            foreach ($data['countries'] as $country_code => $country_data) {
                if (isset($country_data['name']) && $country_data['name'] === $country_name) {
                    return $country_code;
                }
            }
        }

        return '';
    }

    // AJAX Handlers

    /**
     * Handle get areas AJAX request
     */
    public function handle_get_areas_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $args = [
            'paged' => isset($_POST['paged']) ? intval($_POST['paged']) : 1,
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 20,
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : ''
        ];

        $result = $this->get_areas_by_user($user_id, 'zip_code', $args);
        wp_send_json_success($result);
    }

    /**
     * Handle add area AJAX request
     */
    public function handle_add_area_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $data = [
            'country_name' => isset($_POST['country_name']) ? sanitize_text_field($_POST['country_name']) : '',
            'area_name' => isset($_POST['area_name']) ? sanitize_text_field($_POST['area_name']) : '',
            'area_zipcode' => isset($_POST['area_zipcode']) ? sanitize_text_field($_POST['area_zipcode']) : ''
        ];

        $result = $this->add_area($user_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => __('Service area added successfully!', 'mobooking'),
            'area_id' => $result
        ]);
    }

    /**
     * Handle update area AJAX request
     */
    public function handle_update_area_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        if (empty($area_id)) {
            wp_send_json_error(['message' => __('Area ID is required.', 'mobooking')]);
            return;
        }

        $data = [
            'country_name' => isset($_POST['country_name']) ? sanitize_text_field($_POST['country_name']) : '',
            'area_name' => isset($_POST['area_name']) ? sanitize_text_field($_POST['area_name']) : '',
            'area_zipcode' => isset($_POST['area_zipcode']) ? sanitize_text_field($_POST['area_zipcode']) : ''
        ];

        $result = $this->update_area($area_id, $user_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        wp_send_json_success(['message' => __('Service area updated successfully!', 'mobooking')]);
    }

    /**
     * Handle delete area AJAX request
     */
    public function handle_delete_area_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        if (empty($area_id)) {
            wp_send_json_error(['message' => __('Area ID is required.', 'mobooking')]);
            return;
        }

        $result = $this->delete_area($area_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        wp_send_json_success(['message' => __('Service area deleted successfully!', 'mobooking')]);
    }

    /**
     * Handle get countries AJAX request
     */
    public function handle_get_countries_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        // Debug: Let's see what we get from the JSON file
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            error_log("MoBooking: Error loading JSON data: " . $data->get_error_message());
            wp_send_json_error(['message' => $data->get_error_message()]);
            return;
        }

        // Debug: Log the data structure
        error_log("MoBooking: JSON data loaded: " . print_r($data, true));

        $countries = [];
        
        // Handle the JSON structure as it appears in the project
        if (is_array($data)) {
            foreach ($data as $country_code => $country_data) {
                if (is_array($country_data) && isset($country_data['name'])) {
                    $countries[] = [
                        'code' => $country_code,
                        'name' => $country_data['name']
                    ];
                }
            }
        }

        // If no countries found, data might be malformed or empty.
        if (empty($countries)) {
            error_log("MoBooking: No countries extracted from JSON data. JSON structure might be different than expected or file is empty/corrupted. Path: " . get_template_directory() . '/data/service-areas-data.json');
            // Sending empty array is better than a non-existent method call.
            // The frontend JS should handle empty country lists gracefully.
        }

        // Debug: Log the countries array
        error_log("MoBooking: Countries to return: " . print_r($countries, true));

        wp_send_json_success(['countries' => $countries]);
    }

    /**
     * Handle get cities for country AJAX request
     */
    public function handle_get_cities_for_country_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');

        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        if (empty($country_code)) {
            wp_send_json_error(['message' => __('Country code is required.', 'mobooking')]);
            return;
        }

        $cities = $this->get_cities_for_country($country_code);

        if (is_wp_error($cities)) {
            wp_send_json_error(['message' => $cities->get_error_message()]);
            return;
        }

        wp_send_json_success(['cities' => $cities]);
    }

    /**
     * Handle get areas for city AJAX request
     */
    public function handle_get_areas_for_city_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');

        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        $city_code = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';

        if (empty($country_code) || empty($city_code)) {
            wp_send_json_error(['message' => __('Country and city codes are required.', 'mobooking')]);
            return;
        }

        $areas = $this->get_areas_for_city($country_code, $city_code);

        if (is_wp_error($areas)) {
            wp_send_json_error(['message' => $areas->get_error_message()]);
            return;
        }

        wp_send_json_success(['areas' => $areas]);
    }

    /**
     * Handle bulk add areas AJAX request
     */
    public function handle_add_bulk_areas_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $areas_json = isset($_POST['areas']) ? $_POST['areas'] : '';
        if (empty($areas_json)) {
            wp_send_json_error(['message' => __('No areas data provided.', 'mobooking')]);
            return;
        }

        $areas_data = json_decode(stripslashes($areas_json), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => __('Invalid areas data format.', 'mobooking') . ' JSON Error: ' . json_last_error_msg()]);
            return;
        }

        $result = $this->add_bulk_areas($user_id, $areas_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        $message = sprintf(
            _n(
                '%d service area has been added successfully!',
                '%d service areas have been added successfully!',
                $result['added_count'],
                'mobooking'
            ),
            $result['added_count']
        );

        wp_send_json_success([
            'message' => $message,
            'added_count' => $result['added_count'],
            'errors' => $result['errors']
        ]);
    }

    /**
     * Handle public ZIP code availability check
     */
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
}