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

use MoBooking\Classes\EnhancedServiceOptionsSchema;

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

    private function validate_sqm_ranges(array $ranges) {
        if (empty($ranges)) {
            // For SQM type, ranges are expected. If not, it might be an issue depending on requirements.
            // For now, let's assume an empty set of ranges is invalid if the type is 'sqm'.
            return new \WP_Error('sqm_ranges_empty', __('SQM pricing ranges cannot be empty.', 'mobooking'));
        }

        $previous_to_sqm = -1; // Initialize to handle the first range starting from 0 or 1.

        foreach ($ranges as $index => $range) {
            $from_sqm = isset($range['from_sqm']) ? floatval($range['from_sqm']) : null;
            $to_sqm = isset($range['to_sqm']) ? ($range['to_sqm'] === '' || $range['to_sqm'] === null || strtolower($range['to_sqm']) === 'infinity' || $range['to_sqm'] === '∞' ? INF : floatval($range['to_sqm'])) : null;
            $price_per_sqm = isset($range['price_per_sqm']) ? floatval($range['price_per_sqm']) : null;

            if ($from_sqm === null || $price_per_sqm === null) {
                return new \WP_Error('sqm_missing_fields', sprintf(__('Range %d: "From SQM" and "Price per SQM" are required.', 'mobooking'), $index + 1));
            }

            if ($from_sqm < 0 || ($to_sqm !== INF && $to_sqm < 0) || $price_per_sqm <= 0) {
                return new \WP_Error('sqm_invalid_values', sprintf(__('Range %d: "From SQM", "To SQM" (if not infinity) must be non-negative, and "Price per SQM" must be positive.', 'mobooking'), $index + 1));
            }

            if ($to_sqm !== INF && $from_sqm >= $to_sqm) {
                return new \WP_Error('sqm_from_greater_than_to', sprintf(__('Range %d: "From SQM" must be less than "To SQM".', 'mobooking'), $index + 1));
            }

            // Check for continuity and overlap
            if ($previous_to_sqm !== INF && $from_sqm < $previous_to_sqm) {
                return new \WP_Error('sqm_range_overlap', sprintf(__('Range %d: "From SQM" (%s) cannot be less than the previous range\'s "To SQM" (%s).', 'mobooking'), $index + 1, $from_sqm, $previous_to_sqm));
            }


            $previous_to_sqm = $to_sqm;
        }
        return true;
    }

    private function validate_km_ranges(array $ranges) {
        if (empty($ranges)) {
            return new \WP_Error('km_ranges_empty', __('Kilometer pricing ranges cannot be empty.', 'mobooking'));
        }

        $previous_to_km = -1;

        foreach ($ranges as $index => $range) {
            $from_km = isset($range['from_km']) ? floatval($range['from_km']) : null;
            $to_km = isset($range['to_km']) ? ($range['to_km'] === '' || $range['to_km'] === null || strtolower($range['to_km']) === 'infinity' || $range['to_km'] === '∞' ? INF : floatval($range['to_km'])) : null;
            $price_per_km = isset($range['price_per_km']) ? floatval($range['price_per_km']) : null;

            if ($from_km === null || $price_per_km === null) {
                return new \WP_Error('km_missing_fields', sprintf(__('Range %d: "From KM" and "Price per KM" are required.', 'mobooking'), $index + 1));
            }

            if ($from_km < 0 || ($to_km !== INF && $to_km < 0) || $price_per_km <= 0) {
                return new \WP_Error('km_invalid_values', sprintf(__('Range %d: Values must be non-negative, and price must be positive.', 'mobooking'), $index + 1));
            }

            if ($to_km !== INF && $from_km >= $to_km) {
                return new \WP_Error('km_from_greater_than_to', sprintf(__('Range %d: "From KM" must be less than "To KM".', 'mobooking'), $index + 1));
            }

            if ($previous_to_km !== INF && $from_km < $previous_to_km) {
                return new \WP_Error('km_range_overlap', sprintf(__('Range %d: "From KM" (%s) cannot be less than the previous range\'s "To KM" (%s).', 'mobooking'), $index + 1, $from_km, $previous_to_km));
            }

            $previous_to_km = $to_km;
        }
        return true;
    }


    // --- Service Option CRUD Methods ---

    public function add_service_option(int $user_id, int $service_id, array $data) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid user or service ID.', 'mobooking'));
        }

        $validation_errors = EnhancedServiceOptionsSchema::validate_option_data($data);
        if (!empty($validation_errors)) {
            return new \WP_Error('validation_error', implode(', ', $validation_errors));
        }

        $defaults = array(
            'description' => '',
            'is_required' => 0,
            'price_type' => 'fixed',
            'price_value' => null,
            'option_values' => null, // Should be JSON string or null
            'sort_order' => 0
        );
        $option_data = wp_parse_args($data, $defaults);

        if (is_array($option_data['option_values'])) {
            $option_data['option_values'] = wp_json_encode($option_data['option_values']);
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
                'price_type' => sanitize_text_field($option_data['price_type']),
                'price_value' => is_null($option_data['price_value']) ? null : floatval($option_data['price_value']),
                'option_values' => $option_data['option_values'],
                'sort_order' => intval($option_data['sort_order']),
                'created_at' => current_time('mysql', 1), // GMT
                'updated_at' => current_time('mysql', 1), // GMT
            ),
            array(
                '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%d', '%s', '%s'
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

        $validation_errors = EnhancedServiceOptionsSchema::validate_option_data($data);
        if (!empty($validation_errors)) {
            return new \WP_Error('validation_error', implode(', ', $validation_errors));
        }

        if (isset($data['option_values']) && is_array($data['option_values'])) {
            $data['option_values'] = wp_json_encode($data['option_values']);
        }

        $table_name = Database::get_table_name('service_options');

        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['type'])) { $update_data['type'] = sanitize_text_field($data['type']); $update_formats[] = '%s'; }
        if (isset($data['is_required'])) { $update_data['is_required'] = boolval($data['is_required']); $update_formats[] = '%d'; }
        if (isset($data['price_type'])) { $update_data['price_type'] = sanitize_text_field($data['price_type']); $update_formats[] = '%s'; }
        if (array_key_exists('price_value', $data)) { $update_data['price_value'] = is_null($data['price_value']) ? null : floatval($data['price_value']); $update_formats[] = '%f'; }
        if (array_key_exists('option_values', $data)) { $update_data['option_values'] = $data['option_values']; $update_formats[] = '%s'; }
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
