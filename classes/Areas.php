<?php
namespace MoBooking\Classes;

if (!defined('ABSPATH')) exit;

/**
 * Refactored Areas Class
 * Streamlined service areas management with bulk operations focus
 */
class Areas {
    private $wpdb;
    private $countries_cache = null;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Register AJAX actions
     */
    public function register_ajax_actions() {
        // Core area management
        add_action('wp_ajax_mobooking_get_areas', [$this, 'handle_get_areas_ajax']);
        add_action('wp_ajax_mobooking_add_bulk_areas', [$this, 'handle_add_bulk_areas_ajax']);
        add_action('wp_ajax_mobooking_delete_area', [$this, 'handle_delete_area_ajax']);

        // Data retrieval for quick selection
        add_action('wp_ajax_mobooking_get_countries', [$this, 'handle_get_countries_ajax']);
        add_action('wp_ajax_mobooking_get_cities_for_country', [$this, 'handle_get_cities_for_country_ajax']);
        add_action('wp_ajax_mobooking_get_areas_for_city', [$this, 'handle_get_areas_for_city_ajax']);

        // Public ZIP code checking
        add_action('wp_ajax_nopriv_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
        add_action('wp_ajax_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
    }

    /**
     * Load and cache area data from JSON
     */
    private function load_area_data_from_json() {
        if ($this->countries_cache !== null) {
            return $this->countries_cache;
        }

        $json_file_path = get_template_directory() . '/data/service-areas-data.json';
        
        if (!file_exists($json_file_path)) {
            return new \WP_Error('file_not_found', __('Service areas data file not found.', 'mobooking'));
        }

        $json_content = file_get_contents($json_file_path);
        if ($json_content === false) {
            return new \WP_Error('file_read_error', __('Could not read service areas data file.', 'mobooking'));
        }

        $data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_decode_error', __('Invalid JSON in service areas data file.', 'mobooking'));
        }

        $this->countries_cache = $data;
        return $data;
    }

    /**
     * Get available countries
     */
    public function get_countries() {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }

        $countries = [];
        
        // FIXED: Access $data directly, not $data['countries']
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

        // Sort countries by name
        usort($countries, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $countries;
    }

    /**
     * Get cities for a country
     */
    public function get_cities_for_country($country_code) {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }

        $cities = [];
        
        // FIXED: Access $data[$country_code] directly, not $data['countries'][$country_code]
        if (isset($data[$country_code]['cities']) && is_array($data[$country_code]['cities'])) {
            foreach ($data[$country_code]['cities'] as $city_name => $areas_in_city) {
                $cities[] = [
                    'code' => $city_name, // Use city name as the code
                    'name' => $city_name  // City name is the display name
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
    
    // FIXED: Access $data[$country_code]['cities'][$city_code] directly
    if (isset($data[$country_code]['cities'][$city_code]) &&
        is_array($data[$country_code]['cities'][$city_code])) {
        
        foreach ($data[$country_code]['cities'][$city_code] as $area_data_item) {
            if (is_array($area_data_item) && isset($area_data_item['name']) && isset($area_data_item['zip'])) {
                $areas[] = [
                    'name' => $area_data_item['name'],
                    'zip_code' => $area_data_item['zip'],
                    'code' => $area_data_item['zip']
                ];
            }
        }
    }

    return $areas;
}

    /**
     * Add multiple areas at once (bulk operation)
     */
    public function add_bulk_areas(int $user_id, array $areas_data) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        }

    if (!is_array($areas_data)) {
        return new \WP_Error('invalid_data', __('Invalid areas data. Must be an array.', 'mobooking'));
        }

        $table_name = Database::get_table_name('areas');
        $added_count = 0;
        $skipped_count = 0;
        $errors = [];

        foreach ($areas_data as $area_data) {
            // Validate required fields
            if (empty($area_data['area_zipcode']) || empty($area_data['country_name'])) {
                $errors[] = __('Missing required fields for one or more areas.', 'mobooking');
                continue;
            }

            $country_name = sanitize_text_field($area_data['country_name']);
            $area_zipcode = sanitize_text_field(str_replace(' ', '', strtoupper($area_data['area_zipcode'])));

            // Get country code
            $db_country_code = '';
            if (!empty($area_data['country_code'])) {
                $db_country_code = sanitize_text_field(strtoupper($area_data['country_code']));
            } else {
                $db_country_code = $this->get_country_code_from_name($country_name);
            }

            // Check for duplicates
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT area_id FROM $table_name WHERE user_id = %d AND area_value = %s AND country_code = %s",
                $user_id, $area_zipcode, $db_country_code
            ));

            if ($existing) {
                $skipped_count++;
                continue; // Skip duplicates
            }

            // Insert new area
            $insert_data = [
                'user_id' => $user_id,
                'area_type' => 'zip_code',
                'area_value' => $area_zipcode,
                'country_code' => $db_country_code,
                'created_at' => current_time('mysql', 1)
            ];

            $inserted = $this->wpdb->insert(
                $table_name,
                $insert_data,
                ['%d', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $added_count++;
            } else {
                $errors[] = sprintf(
                __('Failed to insert area: %s (%s). Error: %s', 'mobooking'),
                    esc_html($country_name),
                    esc_html($area_zipcode),
                    esc_html($this->wpdb->last_error)
                );
            }
        }

        return [
            'added_count' => $added_count,
            'skipped_count' => $skipped_count,
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

        $table_name = Database::get_table_name('areas');
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
            $where_conditions[] = '(area_value LIKE %s OR country_code LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Add country filter (expects country name, convert to country code for query)
        if (!empty($args['country'])) {
            $country_name = sanitize_text_field($args['country']);
            $country_code = $this->get_country_code_from_name($country_name);
            if ($country_code) {
                $where_conditions[] = 'country_code = %s';
                $where_values[] = $country_code;
            }
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get total count
        $total_count_sql = "SELECT COUNT(area_id) FROM $table_name $where_clause";
        $total_count = $this->wpdb->get_var($this->wpdb->prepare($total_count_sql, $where_values));

        // Get paginated results
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY country_code ASC, area_value ASC LIMIT %d OFFSET %d";
        $areas = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, array_merge($where_values, [$limit, $offset])), 
            ARRAY_A
        );

        return [
            'areas' => $areas,
            'total_count' => (int) $total_count,
            'per_page' => $limit,
            'current_page' => $paged
        ];
    }

    /**
     * Delete an area
     */
    public function delete_area(int $area_id, int $user_id) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'mobooking'));
        }

        $table_name = Database::get_table_name('areas');

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

        $table_name = Database::get_table_name('areas');
        $normalized_zip = sanitize_text_field(str_replace(' ', '', strtoupper($zip_code)));

        $sql = $this->wpdb->prepare(
            "SELECT area_id FROM $table_name WHERE user_id = %d AND area_type = 'zip_code' AND area_value = %s",
            $tenant_user_id, $normalized_zip
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

        // FIXED: Access $data directly, not $data['countries']
        if (is_array($data)) {
            foreach ($data as $country_code => $country_data) {
                if (isset($country_data['name']) && $country_data['name'] === $country_name) {
                    return $country_code;
                }
            }
        }

        return '';
    }


    // ==================== AJAX HANDLERS ====================

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
            'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
        ];

        $result = $this->get_areas_by_user($user_id, 'zip_code', $args);
        wp_send_json_success($result);
    }

    /**
     * Handle add bulk areas AJAX request
     */
    public function handle_add_bulk_areas_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $areas_data = isset($_POST['areas_data']) ? $_POST['areas_data'] : [];
        if (empty($areas_data) || !is_array($areas_data)) {
            wp_send_json_error(['message' => __('No areas data provided.', 'mobooking')], 400);
            return;
        }

        $result = $this->add_bulk_areas($user_id, $areas_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
            return;
        }

        $message = sprintf(
            __('Successfully added %d areas.', 'mobooking'),
            $result['added_count']
        );

        if ($result['skipped_count'] > 0) {
            $message .= ' ' . sprintf(
                __('%d areas were skipped (already exist).', 'mobooking'),
                $result['skipped_count']
            );
        }

        wp_send_json_success([
            'message' => $message,
            'added_count' => $result['added_count'],
            'skipped_count' => $result['skipped_count'],
            'errors' => $result['errors']
        ]);
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
            wp_send_json_error(['message' => __('Invalid area ID.', 'mobooking')], 400);
            return;
        }

        $result = $this->delete_area($area_id, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
            return;
        }

        wp_send_json_success(['message' => __('Service area deleted successfully.', 'mobooking')]);
    }

    /**
     * Handle get countries AJAX request
     */
    public function handle_get_countries_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $countries = $this->get_countries();
        
        if (is_wp_error($countries)) {
            wp_send_json_error(['message' => $countries->get_error_message()], 500);
            return;
        }

        wp_send_json_success(['countries' => $countries]);
    }

    /**
     * Handle get cities for country AJAX request
     */
    public function handle_get_cities_for_country_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        if (empty($country_code)) {
            wp_send_json_error(['message' => __('Country code is required.', 'mobooking')], 400);
            return;
        }

        $cities = $this->get_cities_for_country($country_code);
        
        if (is_wp_error($cities)) {
            wp_send_json_error(['message' => $cities->get_error_message()], 500);
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
            wp_send_json_error(['message' => __('Country code and city code are required.', 'mobooking')], 400);
            return;
        }

        $areas = $this->get_areas_for_city($country_code, $city_code);
        
        if (is_wp_error($areas)) {
            wp_send_json_error(['message' => $areas->get_error_message()], 500);
            return;
        }

        wp_send_json_success(['areas' => $areas]);
    }


    /**
     * Handle public ZIP code availability check
     */
    public function handle_check_zip_code_public_ajax() {
        check_ajax_referer('mobooking_public_nonce', 'nonce');
        
        $tenant_user_id = isset($_POST['tenant_user_id']) ? intval($_POST['tenant_user_id']) : 0;
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';

        if (empty($tenant_user_id) || empty($location)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'mobooking')], 400);
            return;
        }

        try {
            // Get tenant's service areas
            $table_name = Database::get_table_name('areas');
            $areas = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT area_type, area_value, country_code FROM $table_name WHERE user_id = %d",
                $tenant_user_id
            ), ARRAY_A);

            if (empty($areas)) {
                wp_send_json_error(['message' => __('No service areas configured.', 'mobooking')], 404);
                return;
            }

            $location_normalized = strtolower(trim($location));
            $is_covered = false;

            foreach ($areas as $area) {
                $area_value_normalized = strtolower(trim($area['area_value']));
                
                // Check for exact match or partial match
                if ($area_value_normalized === $location_normalized || 
                    strpos($location_normalized, $area_value_normalized) !== false ||
                    strpos($area_value_normalized, $location_normalized) !== false) {
                    $is_covered = true;
                    break;
                }

                // For ZIP codes, check if it's a 5-digit match
                if ($area['area_type'] === 'zip_code' && 
                    preg_match('/^\d{5}/', $location_normalized) && 
                    preg_match('/^\d{5}/', $area_value_normalized)) {
                    if (substr($location_normalized, 0, 5) === substr($area_value_normalized, 0, 5)) {
                        $is_covered = true;
                        break;
                    }
                }
            }

            if ($is_covered) {
                wp_send_json_success([
                    'message' => __('Service is available in your area!', 'mobooking'),
                    'covered' => true
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
 * Enhanced Areas Backend Methods
 * Add these methods to your existing Areas class to support the new flow
 */

/**
 * Get service coverage grouped by country for display
 */
public function get_service_coverage(int $user_id, $args = []) {
    if (empty($user_id)) {
        return ['coverage' => [], 'total_count' => 0, 'per_page' => 0, 'current_page' => 1];
    }

    $table_name = Database::get_table_name('areas');

    // Base query to get all saved ZIP codes for the user
    $where_conditions = ["user_id = %d", "area_type = 'zip_code'"];
    $where_values = [$user_id];

    if (!empty($args['search'])) {
        $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
        $where_conditions[] = 'area_value LIKE %s';
        $where_values[] = $search_term;
    }

    if (!empty($args['status'])) {
        $where_conditions[] = "status = %s";
        $where_values[] = sanitize_text_field($args['status']);
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    $sql = "SELECT * FROM $table_name $where_clause ORDER BY area_value ASC";
    $all_user_areas = $this->wpdb->get_results($this->wpdb->prepare($sql, $where_values), ARRAY_A);

    $final_areas = $all_user_areas;

    // If a city filter is applied, we must filter the results in PHP
    // because the database does not link ZIP codes to cities.
    if (!empty($args['city'])) {
        $area_data = $this->load_area_data_from_json();
        if (is_wp_error($area_data)) {
            // If we can't load the JSON, we can't filter, so return empty.
            return ['coverage' => [], 'total_count' => 0, 'per_page' => 0, 'current_page' => 1];
        }

        // Assuming a fixed country for now, as per the frontend's implementation.
        $country_code = 'SE';
        $city_name = sanitize_text_field($args['city']);
        $city_zips = [];

        if (isset($area_data[$country_code]['cities'][$city_name])) {
            foreach ($area_data[$country_code]['cities'][$city_name] as $area_info) {
                if (isset($area_info['zip'])) {
                    $city_zips[] = $area_info['zip'];
                }
            }
        }

        // Filter the database results to include only ZIPs that exist in the specified city
        $final_areas = array_filter($all_user_areas, function($area) use ($city_zips) {
            return in_array($area['area_value'], $city_zips);
        });
    }

    // Apply pagination to the final, filtered list of areas
    $paged = isset($args['paged']) ? max(1, intval($args['paged'])) : 1;
    $limit = isset($args['limit']) ? intval($args['limit']) : 20;
    if ($limit === -1 || $limit > 9000) { // Handle "get all" case
        $limit = count($final_areas);
    }
    $offset = ($paged - 1) * $limit;

    $total_count = count($final_areas);
    $paginated_areas = array_slice($final_areas, $offset, $limit);

    return ['coverage' => $paginated_areas, 'total_count' => (int) $total_count, 'per_page' => $limit, 'current_page' => $paged];
}

public function get_service_coverage_grouped(int $user_id, $filters = []) {
    if (empty($user_id)) {
        return ['cities' => []];
    }

    $table_name = Database::get_table_name('areas');
    $where_conditions = ["user_id = %d", "area_type = 'city'"]; // Also filter by area_type
    $where_values = [$user_id];

    if (!empty($filters['city'])) {
        $where_conditions[] = 'area_value = %s';
        $where_values[] = sanitize_text_field($filters['city']);
    }

    // The 'status' filter was removed as the column does not exist in the current schema.
    // if (!empty($filters['status'])) {
    //     $where_conditions[] = 'status = %s';
    //     $where_values[] = sanitize_text_field($filters['status']);
    // }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    // Corrected SQL to use `area_value` and removed the non-existent `status` column.
    // It now groups by city to count how many sub-areas (like zip codes) are under it.
    // Let's adjust the query to be more aligned with what "grouped" might mean.
    // It should probably group by the city-level areas.
    $sql = "SELECT area_value as city_name, COUNT(area_id) as area_count FROM $table_name $where_clause GROUP BY area_value ORDER BY area_value ASC";

    $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $where_values), ARRAY_A);

    return ['cities' => $results];
}

/**
 * Toggle area status (active/inactive)
 */
public function toggle_area_status(int $area_id, int $user_id, string $status) {
    if (empty($user_id) || empty($area_id)) {
        return new \WP_Error('invalid_params', __('Invalid parameters.', 'mobooking'));
    }

    if (!in_array($status, ['active', 'inactive'])) {
        return new \WP_Error('invalid_status', __('Invalid status value.', 'mobooking'));
    }

    $table_name = Database::get_table_name('areas');

    // Verify ownership
    $owner_id = $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT user_id FROM $table_name WHERE area_id = %d", 
        $area_id
    ));

    if (intval($owner_id) !== $user_id) {
        return new \WP_Error('not_owner', __('You do not own this area.', 'mobooking'));
    }

    // Update status
    $updated = $this->wpdb->update(
        $table_name,
        ['status' => $status],
        ['area_id' => $area_id, 'user_id' => $user_id],
        ['%s'],
        ['%d', '%d']
    );

    if (false === $updated) {
        return new \WP_Error('db_error', __('Could not update area status.', 'mobooking'));
    }

    return true;
}

/**
 * Remove all areas for a specific country
 */
public function remove_country_coverage(int $user_id, string $country_code) {
    if (empty($user_id) || empty($country_code)) {
        return new \WP_Error('invalid_params', __('Invalid parameters.', 'mobooking'));
    }

    $table_name = Database::get_table_name('areas');
    $country_code = sanitize_text_field(strtoupper($country_code));

    $deleted = $this->wpdb->delete(
        $table_name,
        [
            'user_id' => $user_id,
            'country_code' => $country_code
        ],
        ['%d', '%s']
    );

    if (false === $deleted) {
        return new \WP_Error('db_error', __('Could not remove country coverage.', 'mobooking'));
    }

    return $deleted; // Returns number of deleted rows
}

// ==================== ENHANCED AJAX HANDLERS ====================

/**
 * Handle get service coverage AJAX request
 */
public function handle_get_service_coverage_ajax() {
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
        'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
    ];

    $result = $this->get_service_coverage($user_id, $args);
    wp_send_json_success($result);
}

public function handle_get_service_coverage_grouped_ajax() {
    check_ajax_referer('mobooking_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
        return;
    }

    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    $result = $this->get_service_coverage_grouped($user_id, $filters);
    wp_send_json_success($result);
}

/**
 * Handle toggle area status AJAX request
 */
public function handle_toggle_area_status_ajax() {
    check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
        return;
    }

    $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    if (empty($area_id) || empty($status)) {
        wp_send_json_error(['message' => __('Area ID and status are required.', 'mobooking')], 400);
        return;
    }

    $result = $this->toggle_area_status($area_id, $user_id, $status);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 400);
        return;
    }

    $message = $status === 'active' ? 
        __('Area enabled successfully.', 'mobooking') : 
        __('Area disabled successfully.', 'mobooking');

    wp_send_json_success(['message' => $message]);
}

public function handle_save_city_areas_ajax() {
    check_ajax_referer('mobooking_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
        return;
    }

    $city_name = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';
    $areas_data = isset($_POST['areas_data']) ? $_POST['areas_data'] : [];

    if (empty($city_name)) {
        wp_send_json_error(['message' => __('City code is required.', 'mobooking')], 400);
        return;
    }

    // Get all ZIP codes for the specified city from the JSON file.
    $area_data = $this->load_area_data_from_json();
    if (is_wp_error($area_data)) {
        wp_send_json_error(['message' => 'Could not load area data file.'], 500);
        return;
    }

    $country_code = 'SE'; // Hardcoded as per frontend
    $city_zips = [];
    if (isset($area_data[$country_code]['cities'][$city_name])) {
        foreach ($area_data[$country_code]['cities'][$city_name] as $area_info) {
            if (isset($area_info['zip'])) {
                $city_zips[] = $area_info['zip'];
            }
        }
    }

    // Delete all existing database entries for this user for any of the city's ZIP codes.
    if (!empty($city_zips)) {
        $table_name = Database::get_table_name('areas');

        $placeholders = implode(', ', array_fill(0, count($city_zips), '%s'));

        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM $table_name WHERE user_id = %d AND area_type = 'zip_code' AND area_value IN ($placeholders)",
            array_merge([$user_id], $city_zips)
        ));
    }

    // Add the new areas from the submission.
    $result = $this->add_bulk_areas($user_id, $areas_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 400);
        return;
    }

    wp_send_json_success($result);
}

public function handle_remove_city_coverage_ajax() {
    check_ajax_referer('mobooking_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
        return;
    }

    $city_name = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';

    if (empty($city_name)) {
        wp_send_json_error(['message' => __('City code is required.', 'mobooking')], 400);
        return;
    }

    // Get all ZIP codes for the specified city from the JSON file.
    $area_data = $this->load_area_data_from_json();
    if (is_wp_error($area_data)) {
        wp_send_json_error(['message' => 'Could not load area data file.'], 500);
        return;
    }

    $country_code = 'SE'; // Hardcoded as per frontend
    $city_zips = [];
    if (isset($area_data[$country_code]['cities'][$city_name])) {
        foreach ($area_data[$country_code]['cities'][$city_name] as $area_info) {
            if (isset($area_info['zip'])) {
                $city_zips[] = $area_info['zip'];
            }
        }
    }

    $deleted_count = 0;
    if (!empty($city_zips)) {
        $table_name = Database::get_table_name('areas');
        $placeholders = implode(', ', array_fill(0, count($city_zips), '%s'));

        $deleted_count = $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM $table_name WHERE user_id = %d AND area_type = 'zip_code' AND area_value IN ($placeholders)",
            array_merge([$user_id], $city_zips)
        ));
    }

    if (false === $deleted_count) {
        wp_send_json_error(['message' => __('Could not remove city coverage.', 'mobooking')], 500);
        return;
    }

    wp_send_json_success([
        'message' => sprintf(__('%d service areas removed for %s.', 'mobooking'), $deleted_count, $city_name),
        'deleted_count' => $deleted_count
    ]);
}

/**
 * Handle remove country coverage AJAX request
 */
public function handle_remove_country_coverage_ajax() {
    check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
        return;
    }

    $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';

    if (empty($country_code)) {
        wp_send_json_error(['message' => __('Country code is required.', 'mobooking')], 400);
        return;
    }

    $result = $this->remove_country_coverage($user_id, $country_code);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 400);
        return;
    }

    $message = sprintf(
        __('Removed %d service areas for the selected country.', 'mobooking'),
        $result
    );

    wp_send_json_success(['message' => $message, 'deleted_count' => $result]);
}

/**
 * Enhanced register_ajax_actions method - ADD THESE TO YOUR EXISTING METHOD
 */
public function register_enhanced_ajax_actions() {
    // Add these to your existing register_ajax_actions method
    add_action('wp_ajax_mobooking_get_service_coverage', [$this, 'handle_get_service_coverage_ajax']);
    add_action('wp_ajax_mobooking_toggle_area_status', [$this, 'handle_toggle_area_status_ajax']);
    add_action('wp_ajax__remove_country_coverage', [$this, 'handle_remove_country_coverage_ajax']);
        add_action('wp_ajax_mobooking_save_city_areas', [$this, 'handle_save_city_areas_ajax']);
        add_action('wp_ajax_mobooking_remove_city_coverage', [$this, 'handle_remove_city_coverage_ajax']);
}

/**
 * Update database schema to support status column (run this once)
 */
public function add_status_column_to_areas() {
    $table_name = Database::get_table_name('areas');
    
    // Check if status column exists
    $column_exists = $this->wpdb->get_results(
        "SHOW COLUMNS FROM $table_name LIKE 'status'"
    );
    
    if (empty($column_exists)) {
        $sql = "ALTER TABLE $table_name ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER area_value";
        $this->wpdb->query($sql);
        
        // Add index for better performance
        $this->wpdb->query("ALTER TABLE $table_name ADD INDEX idx_user_status (user_id, status)");
    }
}

/**
 * Get the count of service areas for a specific user
 */
public function get_areas_count_by_user(int $user_id): int {
    if (empty($user_id)) {
        return 0;
    }

    $table_name = Database::get_table_name('areas');
    $count = $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT COUNT(area_id) FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    return (int) $count;
}
}