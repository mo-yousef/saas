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
            // The first range should ideally start at 0 or 0.01 or 1.
            if ($index === 0) {
                if ($from_sqm != 0 && $from_sqm != 1) {
                    // This rule might be too strict, depends on business logic.
                    // For now, allowing any positive start.
                }
            } else {
                // Subsequent ranges must start right after the previous one ended.
                // Allow for a small gap if using integers, e.g. prev ends 50, next starts 51.
                // If using floats, they should be continuous.
                // For simplicity with integer SQM values, we expect "From" to be "Previous To" + 1
                // If To_SQM can be float, this logic needs adjustment. Assuming integer SQM units for now.
                if ($previous_to_sqm !== INF && $from_sqm != ($previous_to_sqm + 1)) {
                     return new \WP_Error('sqm_range_gap_or_overlap', sprintf(__('Range %d: "From SQM" (%s) must logically follow the previous range\'s "To SQM" (%s). Expected %s.', 'mobooking'), $index + 1, $from_sqm, $previous_to_sqm, $previous_to_sqm + 1 ));
                }
            }
             // Check if current from_sqm is less than or equal to previous to_sqm (overlap)
            if ($previous_to_sqm !== INF && $from_sqm <= $previous_to_sqm) {
                return new \WP_Error('sqm_range_overlap', sprintf(__('Range %d: "From SQM" (%s) overlaps with the previous range ending at %s.', 'mobooking'), $index + 1, $from_sqm, $previous_to_sqm));
            }


            $previous_to_sqm = $to_sqm;

            // The last range must have To SQM as infinity
            if ($index === count($ranges) - 1 && $to_sqm !== INF) {
                return new \WP_Error('sqm_last_range_must_be_infinity', __('The last SQM range must have "To SQM" set to infinity (leave blank or use ∞).', 'mobooking'));
            }
            // A non-last range cannot be infinity
            if ($index < count($ranges) - 1 && $to_sqm === INF) {
                return new \WP_Error('sqm_intermediate_range_cannot_be_infinity', __('Only the last SQM range can have "To SQM" as infinity.', 'mobooking'));
            }
        }
        return true;
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
            'price_impact_type' => null,
            'price_impact_value' => null,
            'option_values' => null, // Should be JSON string or null
            'sort_order' => 0
        );
        $option_data = wp_parse_args($data, $defaults);

        // Validate SQM ranges if type is 'sqm'
        if ($option_data['type'] === 'sqm') {
            $ranges = !empty($option_data['option_values']) ? json_decode($option_data['option_values'], true) : [];
            if (json_last_error() !== JSON_ERROR_NONE && !empty($option_data['option_values'])) {
                return new \WP_Error('sqm_invalid_json', __('SQM pricing ranges are not valid JSON.', 'mobooking'));
            }
            $validation_result = $this->validate_sqm_ranges($ranges ?: []); // Pass empty array if null/decode fails
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
            // Ensure option_values is stored as JSON string
            $option_data['option_values'] = wp_json_encode($ranges);
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
                // For SQM, these might not be directly used, or could define a base price if needed.
                // For now, assuming SQM price is solely from ranges.
                'price_impact_type' => ($option_data['type'] === 'sqm') ? null : $option_data['price_impact_type'],
                'price_impact_value' => ($option_data['type'] === 'sqm') ? null : $option_data['price_impact_value'],
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
                '%s', // price_impact_type (string or null)
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

        // As with get_service_option, no automatic decoding of option_values.
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

        // Fetch current option type if not provided in $data, to ensure validation consistency
        $current_option_type = isset($data['type']) ? $data['type'] : null;
        if (!$current_option_type) {
            $current_option = $this->get_service_option($option_id, $user_id);
            $current_option_type = $current_option ? $current_option['type'] : null;
        }

        // Validate SQM ranges if type is 'sqm'
        if ($current_option_type === 'sqm' && array_key_exists('option_values', $data)) {
            // If option_values is already a JSON string from client (e.g., from AJAX handler in Services class)
            $ranges = is_string($data['option_values']) ? json_decode($data['option_values'], true) : $data['option_values'];

            if (json_last_error() !== JSON_ERROR_NONE && is_string($data['option_values']) && !empty($data['option_values'])) {
                 return new \WP_Error('sqm_invalid_json_update', __('SQM pricing ranges for update are not valid JSON.', 'mobooking'));
            }
            // Ensure ranges is an array before validation
            if (!is_array($ranges) && !is_null($ranges)) { // Allow null to clear ranges if business logic permits
                return new \WP_Error('sqm_ranges_not_array', __('SQM pricing ranges must be an array.', 'mobooking'));
            }

            $validation_result = $this->validate_sqm_ranges($ranges ?: []);
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
            // Ensure option_values is stored as JSON string
            // wp_json_encode will handle null correctly (becomes "null" string) or empty array "[]"
            $data['option_values'] = wp_json_encode($ranges);
        }


        $table_name = Database::get_table_name('service_options');

        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['type'])) { $update_data['type'] = sanitize_text_field($data['type']); $update_formats[] = '%s'; }
        if (isset($data['is_required'])) { $update_data['is_required'] = boolval($data['is_required']); $update_formats[] = '%d'; }

        // If type is changing to SQM or is SQM, nullify price_impact fields.
        $effective_type = $current_option_type; // Use the type that will be in the DB after this update.
        if (isset($data['type'])) {
            $effective_type = $data['type'];
        }

        if ($effective_type === 'sqm') {
            $update_data['price_impact_type'] = null;
            $update_data['price_impact_value'] = null;
            $update_formats[] = '%s'; // for price_impact_type
            $update_formats[] = '%s'; // for price_impact_value (will be null)
        } else {
            if (array_key_exists('price_impact_type', $data)) { $update_data['price_impact_type'] = !is_null($data['price_impact_type']) ? sanitize_text_field($data['price_impact_type']) : null; $update_formats[] = '%s'; }
            if (array_key_exists('price_impact_value', $data)) { $update_data['price_impact_value'] = !is_null($data['price_impact_value']) ? floatval($data['price_impact_value']) : null; $update_formats[] = (is_null($data['price_impact_value']) ? '%s' : '%f'); }
        }

        // For option_values, if it's not SQM, it might be an array from direct PHP call, or already JSON string from AJAX
        if (array_key_exists('option_values', $data)) {
            if ($effective_type !== 'sqm') { // SQM already encoded above
                 $update_data['option_values'] = !is_null($data['option_values']) ? wp_json_encode($data['option_values']) : null;
            } else {
                 $update_data['option_values'] = $data['option_values']; // Already JSON encoded for SQM
            }
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
