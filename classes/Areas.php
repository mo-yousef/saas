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
        add_action('wp_ajax_mobooking_get_countries', [$this, 'handle_get_countries_ajax']);
        add_action('wp_ajax_mobooking_get_cities_for_country', [$this, 'handle_get_cities_for_country_ajax']);
        add_action('wp_ajax_mobooking_get_areas_for_city', [$this, 'handle_get_areas_for_city_ajax']);
        add_action('wp_ajax_mobooking_add_bulk_areas', [$this, 'handle_add_bulk_areas_ajax']);
    }

    private function load_area_data_from_json() {
        $json_file_path = get_template_directory() . '/data/service-areas-data.json';
        if (!file_exists($json_file_path)) {
            return new \WP_Error('file_not_found', __('Area data file is missing.', 'mobooking'));
        }
        $json_content = file_get_contents($json_file_path);
        $data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_decode_error', __('Error decoding area data.', 'mobooking'));
        }
        return $data;
    }

    public function get_countries() {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }
        $countries = [];
        foreach ($data as $country_data) {
            $countries[] = [
                'code' => $country_data['country_code'],
                'name' => $country_data['country_name']
            ];
        }
        return $countries;
    }

    public function get_cities_for_country($country_code) {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }
        $cities = [];
        foreach ($data as $country_data) {
            if ($country_data['country_code'] === $country_code) {
                foreach ($country_data['cities'] as $city_data) {
                    $cities[] = [
                        'code' => $city_data['city_name'],
                        'name' => $city_data['city_name']
                    ];
                }
                break;
            }
        }
        return $cities;
    }

    public function get_areas_for_city($country_code, $city_name) {
        $data = $this->load_area_data_from_json();
        if (is_wp_error($data)) {
            return $data;
        }
        foreach ($data as $country_data) {
            if ($country_data['country_code'] === $country_code) {
                foreach ($country_data['cities'] as $city_data) {
                    if ($city_data['city_name'] === $city_name) {
                        return $city_data['areas'];
                    }
                }
            }
        }
        return [];
    }

    public function add_bulk_areas(int $user_id, array $areas_data) {
        if (empty($user_id) || empty($areas_data)) {
            return new \WP_Error('invalid_data', __('Invalid data.', 'mobooking'));
        }
        $table_name = Database::get_table_name('service_areas');
        $added_count = 0;
        foreach ($areas_data as $area_data) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT area_id FROM $table_name WHERE user_id = %d AND area_value = %s AND country_code = %s",
                $user_id, $area_data['area_zipcode'], $area_data['country_code']
            ));
            if (!$existing) {
                $this->wpdb->insert(
                    $table_name,
                    [
                        'user_id' => $user_id,
                        'area_type' => 'zip_code',
                        'area_name' => $area_data['area_name'],
                        'area_value' => $area_data['area_zipcode'],
                        'country_code' => $area_data['country_code'],
                        'created_at' => current_time('mysql', 1)
                    ]
                );
                $added_count++;
            }
        }
        return $added_count;
    }

    public function handle_get_countries_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $countries = $this->get_countries();
        if (is_wp_error($countries)) {
            wp_send_json_error(['message' => $countries->get_error_message()]);
        } else {
            wp_send_json_success(['countries' => $countries]);
        }
    }

    public function handle_get_cities_for_country_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        $cities = $this->get_cities_for_country($country_code);
        if (is_wp_error($cities)) {
            wp_send_json_error(['message' => $cities->get_error_message()]);
        } else {
            wp_send_json_success(['cities' => $cities]);
        }
    }

    public function handle_get_areas_for_city_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        $city_name = isset($_POST['city_name']) ? sanitize_text_field($_POST['city_name']) : '';
        error_log('Country Code: ' . $country_code);
        error_log('City Name: ' . $city_name);
        $areas = $this->get_areas_for_city($country_code, $city_name);
        if (is_wp_error($areas)) {
            wp_send_json_error(['message' => $areas->get_error_message()]);
        } else {
            wp_send_json_success(['areas' => $areas]);
        }
    }

    public function handle_add_bulk_areas_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        $areas_json = isset($_POST['areas']) ? stripslashes($_POST['areas']) : '';
        $areas_data = json_decode($areas_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON data.']);
            return;
        }
        $added_count = $this->add_bulk_areas($user_id, $areas_data);
        if (is_wp_error($added_count)) {
            wp_send_json_error(['message' => $added_count->get_error_message()]);
        } else {
            wp_send_json_success([
                'message' => sprintf(
                    _n(
                        '%d service area has been added successfully!',
                        '%d service areas have been added successfully!',
                        $added_count,
                        'mobooking'
                    ),
                    $added_count
                ),
                'added_count' => $added_count
            ]);
        }
    }
}