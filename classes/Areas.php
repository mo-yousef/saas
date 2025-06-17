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
        add_action('wp_ajax_mobooking_delete_area', [$this, 'handle_delete_area_ajax']);
        add_action('wp_ajax_nopriv_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
        add_action('wp_ajax_mobooking_check_zip_availability', [$this, 'handle_check_zip_code_public_ajax']);
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
        if (empty($user_id)) return [];
        $table_name = Database::get_table_name('service_areas');
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND area_type = %s ORDER BY country_code ASC, area_value ASC",
            $user_id, sanitize_text_field($type)
        ), ARRAY_A);
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
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Corrected nonce
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $areas = $this->get_areas_by_user($user_id);
        wp_send_json_success($areas);
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
}
