<?php
namespace NORDBOOKING\Classes;

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
        add_action('wp_ajax_nordbooking_get_areas', [$this, 'handle_get_areas_ajax']);
        add_action('wp_ajax_nordbooking_add_bulk_areas', [$this, 'handle_add_bulk_areas_ajax']);
        add_action('wp_ajax_nordbooking_delete_area', [$this, 'handle_delete_area_ajax']);

        // Data retrieval for quick selection
        add_action('wp_ajax_nordbooking_get_countries', [$this, 'handle_get_countries_ajax']);
        add_action('wp_ajax_nordbooking_get_cities_for_country', [$this, 'handle_get_cities_for_country_ajax']);
        add_action('wp_ajax_nordbooking_get_areas_for_city', [$this, 'handle_get_areas_for_city_ajax']);

        // Public ZIP code checking
        add_action('wp_ajax_nopriv_nordbooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
        add_action('wp_ajax_nordbooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);

        // Enhanced Area Management AJAX actions
        add_action('wp_ajax_nordbooking_get_service_coverage', [$this, 'handle_get_service_coverage_ajax']);
        add_action('wp_ajax_nordbooking_toggle_area_status', [$this, 'handle_toggle_area_status_ajax']);
        add_action('wp_ajax_nordbooking_remove_country_coverage', [$this, 'handle_remove_country_coverage_ajax']);
        add_action('wp_ajax_nordbooking_save_city_areas', [$this, 'handle_save_city_areas_ajax']);
        add_action('wp_ajax_nordbooking_remove_city_coverage', [$this, 'handle_remove_city_coverage_ajax']);
        add_action('wp_ajax_nordbooking_update_city_status', [$this, 'handle_update_status_for_city_ajax']);
        
        // User preferences
        add_action('wp_ajax_nordbooking_save_selected_country', [$this, 'handle_save_selected_country_ajax']);
        add_action('wp_ajax_nordbooking_get_selected_country', [$this, 'handle_get_selected_country_ajax']);
        
        // Bulk operations
        add_action('wp_ajax_nordbooking_bulk_city_action', [$this, 'handle_bulk_city_action_ajax']);
        add_action('wp_ajax_nordbooking_remove_country_areas', [$this, 'handle_remove_country_areas_ajax']);
    }

    /**
     * Load area data for a specific country from its JSON file
     */
    private function load_country_data_from_json($country_code) {
        $countries = $this->get_countries();
        if (is_wp_error($countries)) {
            return $countries;
        }

        $country_config = null;
        foreach ($countries as $country) {
            if ($country['code'] === $country_code) {
                $country_config = $country;
                break;
            }
        }

        if (!$country_config || !isset($country_config['dataFile'])) {
            return new \WP_Error('country_not_found', __('Country configuration not found.', 'NORDBOOKING'));
        }

        $json_file_path = get_template_directory() . '/data/' . $country_config['dataFile'];
        
        if (!file_exists($json_file_path)) {
            return new \WP_Error('file_not_found', sprintf(__('Data file not found for country %s.', 'NORDBOOKING'), $country_code));
        }

        $json_content = file_get_contents($json_file_path);
        if ($json_content === false) {
            return new \WP_Error('file_read_error', sprintf(__('Could not read data file for country %s.', 'NORDBOOKING'), $country_code));
        }

        $data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_decode_error', sprintf(__('Invalid JSON in data file for country %s.', 'NORDBOOKING'), $country_code));
        }

        return $data;
    }

    /**
     * Load and cache area data from JSON (legacy method - now loads all countries)
     */
    private function load_area_data_from_json() {
        if ($this->countries_cache !== null) {
            return $this->countries_cache;
        }

        $countries = $this->get_countries();
        if (is_wp_error($countries)) {
            return $countries;
        }

        $all_data = [];
        foreach ($countries as $country) {
            $country_data = $this->load_country_data_from_json($country['code']);
            if (!is_wp_error($country_data) && is_array($country_data)) {
                $all_data = array_merge($all_data, $country_data);
            }
        }

        $this->countries_cache = $all_data;
        return $all_data;
    }

    /**
     * Get available countries from config file
     */
    public function get_countries() {
        $config_file_path = get_template_directory() . '/data/countries-config.json';
        
        if (!file_exists($config_file_path)) {
            return new \WP_Error('config_not_found', __('Countries configuration file not found.', 'NORDBOOKING'));
        }

        $json_content = file_get_contents($config_file_path);
        if ($json_content === false) {
            return new \WP_Error('config_read_error', __('Could not read countries configuration file.', 'NORDBOOKING'));
        }

        $config = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('config_decode_error', __('Invalid JSON in countries configuration file.', 'NORDBOOKING'));
        }

        if (!isset($config['countries']) || !is_array($config['countries'])) {
            return new \WP_Error('config_invalid', __('Invalid countries configuration format.', 'NORDBOOKING'));
        }

        return $config['countries'];
    }

    /**
     * Get cities (states) for a country from the country-specific JSON file.
     */
    public function get_cities_for_country($country_code) {
        $data = $this->load_country_data_from_json($country_code);
        if (is_wp_error($data) || !is_array($data)) {
            return [];
        }

        $states = [];
        foreach ($data as $location) {
            if (isset($location['state'])) {
                $states[$location['state']] = true;
            }
        }

        $cities = [];
        foreach (array_keys($states) as $state_name) {
            $cities[] = [
                'code' => $state_name,
                'name' => $state_name,
            ];
        }

        // Sort cities by name
        usort($cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $cities;
    }


    /**
     * Get areas (places/zipcodes) for a specific city (state) from the new JSON format.
     */
    public function get_areas_for_city($country_code, $city_code) {
        $data = $this->load_country_data_from_json($country_code);
        if (is_wp_error($data) || !is_array($data)) {
            return [];
        }

        $grouped_areas = [];
        foreach ($data as $location) {
            if (
                isset($location['state']) && $location['state'] === $city_code &&
                isset($location['zipcode']) && isset($location['place'])
            ) {
                $place_name = $location['place'];
                if (!isset($grouped_areas[$place_name])) {
                    $grouped_areas[$place_name] = [];
                }
                $grouped_areas[$place_name][] = $location;
            }
        }

        return $grouped_areas;
    }

    /**
     * Add multiple areas at once (bulk operation)
     */
    public function add_bulk_areas(int $user_id, array $areas_data) {
        if (empty($user_id)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'NORDBOOKING'));
        }

        if (!is_array($areas_data)) {
            return new \WP_Error('invalid_data', __('Invalid areas data. Must be an array.', 'NORDBOOKING'));
        }

        $table_name = Database::get_table_name('areas');
        $added_count = 0;
        $skipped_count = 0;
        $errors = [];

        foreach ($areas_data as $area_data) {
            // Validate required fields from the full location object
            if (empty($area_data['zipcode']) || empty($area_data['country_code'])) {
                $errors[] = __('Missing zipcode or country_code for one or more areas.', 'NORDBOOKING');
                continue;
            }

            $area_zipcode = sanitize_text_field(str_replace(' ', '', strtoupper($area_data['zipcode'])));
            $country_code = sanitize_text_field(strtoupper($area_data['country_code']));

            // Check for duplicates
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT area_id FROM $table_name WHERE user_id = %d AND area_value = %s AND country_code = %s",
                $user_id, $area_zipcode, $country_code
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
                'country_code' => $country_code,
                'area_data' => wp_json_encode($area_data),
                'created_at' => current_time('mysql', 1)
            ];

            $inserted = $this->wpdb->insert(
                $table_name,
                $insert_data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $added_count++;
            } else {
                $errors[] = sprintf(
                    __('Failed to insert area: %s. Error: %s', 'NORDBOOKING'),
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
            return new \WP_Error('invalid_user', __('Invalid user.', 'NORDBOOKING'));
        }

        $table_name = Database::get_table_name('areas');

        // Verify ownership
        $owner_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE area_id = %d", 
            $area_id
        ));

        if (intval($owner_id) !== $user_id) {
            return new \WP_Error('not_owner', __('You do not own this area.', 'NORDBOOKING'));
        }

        $deleted = $this->wpdb->delete(
            $table_name, 
            ['area_id' => $area_id, 'user_id' => $user_id], 
            ['%d', '%d']
        );

        if (!$deleted) {
            return new \WP_Error('db_error', __('Could not delete service area.', 'NORDBOOKING'));
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
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
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
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }

        $areas_data = isset($_POST['areas_data']) ? $_POST['areas_data'] : [];
        if (empty($areas_data) || !is_array($areas_data)) {
            wp_send_json_error(['message' => __('No areas data provided.', 'NORDBOOKING')], 400);
            return;
        }

        $result = $this->add_bulk_areas($user_id, $areas_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
            return;
        }

        $message = sprintf(
            __('Successfully added %d areas.', 'NORDBOOKING'),
            $result['added_count']
        );

        if ($result['skipped_count'] > 0) {
            $message .= ' ' . sprintf(
                __('%d areas were skipped (already exist).', 'NORDBOOKING'),
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
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }

        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        if (empty($area_id)) {
            wp_send_json_error(['message' => __('Invalid area ID.', 'NORDBOOKING')], 400);
            return;
        }

        $result = $this->delete_area($area_id, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
            return;
        }

        wp_send_json_success(['message' => __('Service area deleted successfully.', 'NORDBOOKING')]);
    }

    /**
     * Handle get countries AJAX request
     */
    public function handle_get_countries_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
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
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        if (empty($country_code)) {
            wp_send_json_error(['message' => __('Country code is required.', 'NORDBOOKING')], 400);
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
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        $city_code = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';
        
        if (empty($country_code) || empty($city_code)) {
            wp_send_json_error(['message' => __('Country code and city code are required.', 'NORDBOOKING')], 400);
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
        check_ajax_referer('nordbooking_public_nonce', 'nonce');
        
        $tenant_user_id = isset($_POST['tenant_user_id']) ? intval($_POST['tenant_user_id']) : 0;
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';

        if (empty($tenant_user_id) || empty($location)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'NORDBOOKING')], 400);
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
                wp_send_json_error(['message' => __('No service areas configured.', 'NORDBOOKING')], 404);
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
                    'message' => __('Service is available in your area!', 'NORDBOOKING'),
                    'covered' => true
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Sorry, we do not currently service your area.', 'NORDBOOKING'),
                    'covered' => false
                ], 404);
            }

        } catch (Exception $e) {
            error_log('NORDBOOKING - Service area check error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error checking service area.', 'NORDBOOKING')], 500);
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

        $city_name = sanitize_text_field($args['city']);
        $city_zips = [];

        foreach ($area_data as $location) {
            if (isset($location['state']) && $location['state'] === $city_name && isset($location['zipcode'])) {
                $city_zips[] = $location['zipcode'];
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

    // 1. Get all saved areas (zip and status) for the user.
    $table_name = Database::get_table_name('areas');
    $where_conditions = ["user_id = %d", "area_type = 'zip_code'"];
    $where_values = [$user_id];
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    $sql = "SELECT area_value, status FROM $table_name $where_clause";
    $saved_areas = $this->wpdb->get_results($this->wpdb->prepare($sql, $where_values), OBJECT_K);

    if (empty($saved_areas)) {
        return ['cities' => []];
    }

    // 2. Load the new JSON data to map zips to states (cities).
    $area_data = $this->load_area_data_from_json();
    if (is_wp_error($area_data) || !is_array($area_data)) {
        return ['cities' => []];
    }

    // 3. Create a reverse map from ZIP -> State and Country.
    $zip_to_location_map = [];
    foreach ($area_data as $location) {
        if (isset($location['zipcode']) && isset($location['state']) && isset($location['country_code'])) {
            // Sanitize the zip from the file to match the format in the DB
            $sanitized_zip = str_replace(' ', '', $location['zipcode']);
            $zip_to_location_map[$sanitized_zip] = [
                'state' => $location['state'],
                'country_code' => $location['country_code']
            ];
        }
    }

    // 4. Group saved areas by state, count them, and determine collective status.
    $state_groups = [];
    foreach ($saved_areas as $zip => $area_details) {
        $sanitized_zip = str_replace(' ', '', $zip);
        if (isset($zip_to_location_map[$sanitized_zip])) {
            $location_info = $zip_to_location_map[$sanitized_zip];
            $state_name = $location_info['state'];
            $country_code = $location_info['country_code'];
            
            // Apply country filter if specified
            if (!empty($filters['country']) && $filters['country'] !== $country_code) {
                continue;
            }
            
            if (!isset($state_groups[$state_name])) {
                $state_groups[$state_name] = [
                    'area_count' => 0, 
                    'statuses' => [],
                    'country_code' => $country_code
                ];
            }
            $state_groups[$state_name]['area_count']++;
            $state_groups[$state_name]['statuses'][] = $area_details->status;
        }
    }

    // 5. Format the output to match what the frontend expects, applying filters.
    $results = [];
    foreach ($state_groups as $state_name => $group_details) {
        // Apply text search filter
        if (!empty($filters['search']) && stripos($state_name, $filters['search']) === false) {
            continue;
        }

        // Apply city/state dropdown filter
        if (!empty($filters['city']) && $filters['city'] !== $state_name) {
            continue;
        }

        // Determine overall status: 'inactive' only if all areas are inactive.
        $unique_statuses = array_unique($group_details['statuses']);
        $status = (count($unique_statuses) === 1 && $unique_statuses[0] === 'inactive') ? 'inactive' : 'active';

        $results[] = [
            'city_name' => $state_name,
            'city_code' => $state_name,
            'area_count' => $group_details['area_count'],
            'status' => $status,
            'country_code' => $group_details['country_code']
        ];
    }

    // Sort the final results alphabetically by state name
    usort($results, function($a, $b) {
        return strcmp($a['city_name'], $b['city_name']);
    });

    return ['cities' => $results];
}

/**
 * Toggle area status (active/inactive)
 */
public function toggle_area_status(int $area_id, int $user_id, string $status) {
    if (empty($user_id) || empty($area_id)) {
        return new \WP_Error('invalid_params', __('Invalid parameters.', 'NORDBOOKING'));
    }

    if (!in_array($status, ['active', 'inactive'])) {
        return new \WP_Error('invalid_status', __('Invalid status value.', 'NORDBOOKING'));
    }

    $table_name = Database::get_table_name('areas');

    // Verify ownership
    $owner_id = $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT user_id FROM $table_name WHERE area_id = %d", 
        $area_id
    ));

    if (intval($owner_id) !== $user_id) {
        return new \WP_Error('not_owner', __('You do not own this area.', 'NORDBOOKING'));
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
        return new \WP_Error('db_error', __('Could not update area status.', 'NORDBOOKING'));
    }

    return true;
}

/**
 * Remove all areas for a specific country
 */
public function remove_country_coverage(int $user_id, string $country_code) {
    if (empty($user_id) || empty($country_code)) {
        return new \WP_Error('invalid_params', __('Invalid parameters.', 'NORDBOOKING'));
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
        return new \WP_Error('db_error', __('Could not remove country coverage.', 'NORDBOOKING'));
    }

    return $deleted; // Returns number of deleted rows
}

// ==================== ENHANCED AJAX HANDLERS ====================

/**
 * Handle get service coverage AJAX request
 */
public function handle_get_service_coverage_ajax() {
    check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
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
    check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
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
    check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
        return;
    }

    $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    if (empty($area_id) || empty($status)) {
        wp_send_json_error(['message' => __('Area ID and status are required.', 'NORDBOOKING')], 400);
        return;
    }

    $result = $this->toggle_area_status($area_id, $user_id, $status);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 400);
        return;
    }

    $message = $status === 'active' ? 
        __('Area enabled successfully.', 'NORDBOOKING') : 
        __('Area disabled successfully.', 'NORDBOOKING');

    wp_send_json_success(['message' => $message]);
}

public function handle_save_city_areas_ajax() {
    check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
        return;
    }

    $state_name = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';
    $areas_data = isset($_POST['areas_data']) ? $_POST['areas_data'] : [];

    if (empty($state_name)) {
        wp_send_json_error(['message' => __('State (City) code is required.', 'NORDBOOKING')], 400);
        return;
    }

    // Get all ZIP codes for the specified state from the new JSON file.
    $area_data = $this->load_area_data_from_json();
    if (is_wp_error($area_data) || !is_array($area_data)) {
        wp_send_json_error(['message' => 'Could not load area data file.'], 500);
        return;
    }

    $state_zips = [];
    foreach ($area_data as $location) {
        if (isset($location['state']) && $location['state'] === $state_name && isset($location['zipcode'])) {
            $state_zips[] = $location['zipcode'];
        }
    }

    // Delete all existing database entries for this user for any of the state's ZIP codes.
    if (!empty($state_zips)) {
        $table_name = Database::get_table_name('areas');
        $placeholders = implode(', ', array_fill(0, count($state_zips), '%s'));

        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM $table_name WHERE user_id = %d AND area_type = 'zip_code' AND area_value IN ($placeholders)",
            array_merge([$user_id], $state_zips)
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
    check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
        return;
    }

    $city_name = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';

    if (empty($city_name)) {
        wp_send_json_error(['message' => __('City code is required.', 'NORDBOOKING')], 400);
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
        wp_send_json_error(['message' => __('Could not remove city coverage.', 'NORDBOOKING')], 500);
        return;
    }

    wp_send_json_success([
        'message' => sprintf(__('%d service areas removed for %s.', 'NORDBOOKING'), $deleted_count, $city_name),
        'deleted_count' => $deleted_count
    ]);
}

/**
 * Handle remove country coverage AJAX request
 */
public function handle_remove_country_coverage_ajax() {
    check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
        return;
    }

    $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';

    if (empty($country_code)) {
        wp_send_json_error(['message' => __('Country code is required.', 'NORDBOOKING')], 400);
        return;
    }

    $result = $this->remove_country_coverage($user_id, $country_code);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 400);
        return;
    }

    $message = sprintf(
        __('Removed %d service areas for the selected country.', 'NORDBOOKING'),
        $result
    );

    wp_send_json_success(['message' => $message, 'deleted_count' => $result]);
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


    public function update_status_for_city(int $user_id, string $city_name, string $new_status) {
        if (empty($user_id) || empty($city_name) || !in_array($new_status, ['active', 'inactive'])) {
            return new \WP_Error('invalid_params', __('Invalid parameters.', 'NORDBOOKING'));
        }

        // First, get all the zip codes that belong to this city from the JSON file.
        $area_data = $this->load_area_data_from_json();
        if (is_wp_error($area_data)) {
            return $area_data;
        }

        $city_zips = [];
        foreach ($area_data as $location) {
            if (isset($location['state']) && $location['state'] === $city_name && isset($location['zipcode'])) {
                // Sanitize the zip to match the DB format
                $city_zips[] = str_replace(' ', '', $location['zipcode']);
            }
        }

        if (empty($city_zips)) {
            // No zips found for this city, nothing to update.
            return 0;
        }

        // Now, update the status for all of these zips for the given user.
        $table_name = Database::get_table_name('areas');
        $placeholders = implode(', ', array_fill(0, count($city_zips), '%s'));

        $updated_count = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE $table_name SET status = %s WHERE user_id = %d AND area_type = 'zip_code' AND area_value IN ($placeholders)",
            array_merge([$new_status, $user_id], $city_zips)
        ));

        if (false === $updated_count) {
            return new \WP_Error('db_error', __('Could not update statuses for the city.', 'NORDBOOKING'));
        }

        return $updated_count;
    }

    public function handle_update_status_for_city_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }

        $city_name = isset($_POST['city_code']) ? sanitize_text_field($_POST['city_code']) : '';
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (empty($city_name) || empty($new_status)) {
            wp_send_json_error(['message' => __('City code and status are required.', 'NORDBOOKING')], 400);
            return;
        }

        $result = $this->update_status_for_city($user_id, $city_name, $new_status);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
            return;
        }

        wp_send_json_success([
            'message' => sprintf(__('Successfully updated %d areas in %s.', 'NORDBOOKING'), $result, $city_name),
            'updated_count' => $result
        ]);
    }

    /**
     * Save user's selected country preference
     */
    public function save_selected_country($user_id, $country_code) {
        if (empty($user_id) || empty($country_code)) {
            return false;
        }
        
        return update_user_meta($user_id, 'nordbooking_selected_country', $country_code);
    }
    
    /**
     * Get user's selected country preference
     */
    public function get_selected_country($user_id) {
        if (empty($user_id)) {
            return '';
        }
        
        return get_user_meta($user_id, 'nordbooking_selected_country', true);
    }
    
    /**
     * Handle save selected country AJAX request
     */
    public function handle_save_selected_country_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }
        
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        if (empty($country_code)) {
            wp_send_json_error(['message' => __('Country code is required.', 'NORDBOOKING')], 400);
            return;
        }
        
        $result = $this->save_selected_country($user_id, $country_code);
        
        if ($result) {
            wp_send_json_success(['message' => __('Country preference saved.', 'NORDBOOKING')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save country preference.', 'NORDBOOKING')], 500);
        }
    }
    
    /**
     * Handle get selected country AJAX request
     */
    public function handle_get_selected_country_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }
        
        $selected_country = $this->get_selected_country($user_id);
        
        wp_send_json_success(['country_code' => $selected_country]);
    }

    /**
     * Handle bulk city action AJAX request
     */
    public function handle_bulk_city_action_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }
        
        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $cities = isset($_POST['cities']) ? $_POST['cities'] : [];
        
        if (empty($bulk_action) || empty($cities) || !is_array($cities)) {
            wp_send_json_error(['message' => __('Invalid bulk action parameters.', 'NORDBOOKING')], 400);
            return;
        }
        
        $valid_actions = ['enable', 'disable', 'remove'];
        if (!in_array($bulk_action, $valid_actions)) {
            wp_send_json_error(['message' => __('Invalid bulk action.', 'NORDBOOKING')], 400);
            return;
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($cities as $city_data) {
            if (!isset($city_data['city']) || !isset($city_data['country'])) {
                $error_count++;
                continue;
            }
            
            $city_code = sanitize_text_field($city_data['city']);
            $country_code = sanitize_text_field($city_data['country']);
            
            switch ($bulk_action) {
                case 'enable':
                case 'disable':
                    $new_status = $bulk_action === 'enable' ? 'active' : 'inactive';
                    $result = $this->bulk_update_city_status($user_id, $city_code, $new_status);
                    break;
                case 'remove':
                    $result = $this->remove_city_coverage($user_id, $city_code);
                    break;
            }
            
            if ($result && !is_wp_error($result)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        $message = sprintf(
            __('Bulk action completed: %d successful, %d failed.', 'NORDBOOKING'),
            $success_count,
            $error_count
        );
        
        wp_send_json_success([
            'message' => $message,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
    }
    
    /**
     * Handle remove country areas AJAX request
     */
    public function handle_remove_country_areas_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }
        
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        if (empty($country_code)) {
            wp_send_json_error(['message' => __('Country code is required.', 'NORDBOOKING')], 400);
            return;
        }
        
        $result = $this->remove_country_areas($user_id, $country_code);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
            return;
        }
        
        wp_send_json_success([
            'message' => sprintf(__('All areas for country %s have been removed.', 'NORDBOOKING'), $country_code),
            'deleted_count' => $result
        ]);
    }
    
    /**
     * Bulk update city status
     */
    public function bulk_update_city_status($user_id, $city_code, $status) {
        if (empty($user_id) || empty($city_code) || empty($status)) {
            return new \WP_Error('invalid_params', __('Invalid parameters.', 'NORDBOOKING'));
        }
        
        if (!in_array($status, ['active', 'inactive'])) {
            return new \WP_Error('invalid_status', __('Invalid status value.', 'NORDBOOKING'));
        }
        
        $table_name = Database::get_table_name('areas');
        
        // Get all areas for this city and user
        $area_data = $this->load_area_data_from_json();
        if (is_wp_error($area_data)) {
            return $area_data;
        }
        
        $city_zips = [];
        foreach ($area_data as $location) {
            if (isset($location['state']) && $location['state'] === $city_code && isset($location['zipcode'])) {
                $city_zips[] = str_replace(' ', '', strtoupper($location['zipcode']));
            }
        }
        
        if (empty($city_zips)) {
            return new \WP_Error('no_areas', __('No areas found for this city.', 'NORDBOOKING'));
        }
        
        // Update all areas for this city
        $placeholders = implode(',', array_fill(0, count($city_zips), '%s'));
        $sql = "UPDATE $table_name SET status = %s WHERE user_id = %d AND area_value IN ($placeholders)";
        
        $query_params = array_merge([$status, $user_id], $city_zips);
        $result = $this->wpdb->query($this->wpdb->prepare($sql, $query_params));
        
        return $result !== false ? $result : new \WP_Error('db_error', __('Failed to update city status.', 'NORDBOOKING'));
    }
    
    /**
     * Remove all areas for a specific country
     */
    public function remove_country_areas($user_id, $country_code) {
        if (empty($user_id) || empty($country_code)) {
            return new \WP_Error('invalid_params', __('Invalid parameters.', 'NORDBOOKING'));
        }
        
        $table_name = Database::get_table_name('areas');
        
        // Get all areas for this country
        $country_data = $this->load_country_data_from_json($country_code);
        if (is_wp_error($country_data)) {
            return $country_data;
        }
        
        $country_zips = [];
        foreach ($country_data as $location) {
            if (isset($location['zipcode'])) {
                $country_zips[] = str_replace(' ', '', strtoupper($location['zipcode']));
            }
        }
        
        if (empty($country_zips)) {
            return 0; // No areas to remove
        }
        
        // Remove all areas for this country and user
        $placeholders = implode(',', array_fill(0, count($country_zips), '%s'));
        $sql = "DELETE FROM $table_name WHERE user_id = %d AND area_value IN ($placeholders)";
        
        $query_params = array_merge([$user_id], $country_zips);
        $result = $this->wpdb->query($this->wpdb->prepare($sql, $query_params));
        
        return $result !== false ? $result : new \WP_Error('db_error', __('Failed to remove country areas.', 'NORDBOOKING'));
    }}
