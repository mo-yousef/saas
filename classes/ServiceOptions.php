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
            'price_impact_type' => null,
            'price_impact_value' => null,
            'option_values' => null, // Should be JSON string or null
            'sort_order' => 0
        );
        $option_data = wp_parse_args($data, $defaults);

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
                'price_impact_type' => $option_data['price_impact_type'], // Already sanitized
                'price_impact_value' => $option_data['price_impact_value'], // Already floatval or null
                'option_values' => $option_data['option_values'], // Already JSON string or null
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
                (is_null($option_data['price_impact_value']) ? '%s' : '%f'), // price_impact_value (float or null)
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
        if ($option && !empty($option['option_values']) && is_string($option['option_values'])) {
            // Assuming option_values is stored as a JSON string.
            // The JS side now expects this as a string, so no need to decode here for the PHP methods.
            // $option['option_values'] = json_decode($option['option_values'], true);
        }
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

        // No need to json_decode option_values here if JS expects a string.
        // The JS `populateForm` will handle parsing for its textarea.
        // If other PHP parts need it decoded, they can do so.
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

        $table_name = Database::get_table_name('service_options');

        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['type'])) { $update_data['type'] = sanitize_text_field($data['type']); $update_formats[] = '%s'; }
        if (isset($data['is_required'])) { $update_data['is_required'] = boolval($data['is_required']); $update_formats[] = '%d'; }
        if (array_key_exists('price_impact_type', $data)) { $update_data['price_impact_type'] = !is_null($data['price_impact_type']) ? sanitize_text_field($data['price_impact_type']) : null; $update_formats[] = '%s'; }
        if (array_key_exists('price_impact_value', $data)) { $update_data['price_impact_value'] = !is_null($data['price_impact_value']) ? floatval($data['price_impact_value']) : null; $update_formats[] = '%f'; }
        if (array_key_exists('option_values', $data)) { $update_data['option_values'] = !is_null($data['option_values']) ? wp_json_encode($data['option_values']) : null; $update_formats[] = '%s'; }
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
