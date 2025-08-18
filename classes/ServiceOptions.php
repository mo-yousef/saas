<?php
/**
 * Class ServiceOptions
 * Manages options for services.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ServiceOptions {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // Option-specific methods will be added here in the next steps.

    private function _verify_option_ownership(int $option_id, int $user_id): bool {
        if (empty($option_id) || empty($user_id)) return false;
        $table_name = Database::get_table_name('service_options');
        $option = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT option_id FROM $table_name WHERE option_id = %d AND user_id = %d", $option_id, $user_id ) );
        return !is_null($option);
    }

    // --- Service Option CRUD Methods ---

    public function add_service_option(int $user_id, int $service_id, array $data) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid user or service ID.', 'mobooking'));
        }
        if ( empty($data['name']) || empty($data['type']) ) {
            return new \WP_Error('missing_fields', __('Option name and type are required.', 'mobooking'));
        }

        $defaults = array(
            'description' => '',
            'is_required' => 0,
            'price_impact_type' => 'fixed',
            'price_impact_value' => 0,
            'option_values' => null,
            'sort_order' => 0
        );
        $option_data = wp_parse_args($data, $defaults);

        if (in_array($option_data['type'], ['sqm', 'kilometers'])) {
            $option_data['option_values'] = null;
        }


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
                'price_impact_type' => $option_data['price_impact_type'],
                'price_impact_value' => $option_data['price_impact_value'],
                'option_values' => $option_data['option_values'], // JSON string for SQM and other types
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
                '%s',
                (is_null($option_data['price_impact_value']) || $option_data['type'] === 'sqm' ? '%s' : '%f'), // price_impact_value (float or null)
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

        // For SQM type, option_values (JSON string of ranges) is crucial.
        // No automatic decoding here, as JS might expect the string.
        // If PHP logic needs the ranges decoded, it should do so explicitly.
        // e.g., if ($option && $option['type'] === 'sqm' && !empty($option['option_values'])) {
        //    $option['decoded_sqm_ranges'] = json_decode($option['option_values'], true);
        // }
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

        // Decode option_values from JSON string to array
        foreach ($options as $key => $option) {
            if (!empty($option['option_values']) && is_string($option['option_values'])) {
                $decoded_values = json_decode($option['option_values'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $options[$key]['option_values'] = $decoded_values;
                } else {
                    // Handle broken JSON, maybe default to empty array
                    $options[$key]['option_values'] = [];
                }
            }
        }

        return $options;
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

        // Fetch current option type if not provided in $data, to ensure validation consistency
        $current_option_type = isset($data['type']) ? $data['type'] : null;
        if (!$current_option_type) {
            $current_option = $this->get_service_option($option_id, $user_id);
            $current_option_type = $current_option ? $current_option['type'] : null;
        }

        // For SQM/Kilometers, we no longer use option_values for ranges.
        if (in_array($current_option_type, ['sqm', 'kilometers'])) {
            $data['option_values'] = null; // Ensure it's not saving old range data.
        }


        $table_name = Database::get_table_name('service_options');

        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['type'])) { $update_data['type'] = sanitize_text_field($data['type']); $update_formats[] = '%s'; }
        if (isset($data['is_required'])) { $update_data['is_required'] = boolval($data['is_required']); $update_formats[] = '%d'; }

        if (array_key_exists('price_impact_type', $data)) { $update_data['price_impact_type'] = !is_null($data['price_impact_type']) ? sanitize_text_field($data['price_impact_type']) : null; $update_formats[] = '%s'; }
        if (array_key_exists('price_impact_value', $data)) { $update_data['price_impact_value'] = !is_null($data['price_impact_value']) ? floatval($data['price_impact_value']) : null; $update_formats[] = (is_null($data['price_impact_value']) ? '%s' : '%f'); }

        if (array_key_exists('option_values', $data)) {
            $update_data['option_values'] = !is_null($data['option_values']) ? wp_json_encode($data['option_values']) : null;
            $update_formats[] = '%s';
        }
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
