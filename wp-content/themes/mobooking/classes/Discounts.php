<?php
namespace MoBooking\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Discounts {
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    private function _normalize_code(string $code): string {
        return strtoupper(trim($code));
    }

    public function add_discount_code(int $user_id, array $data) {
        if (empty($user_id)) return new \WP_Error('invalid_user', __('Invalid user ID.', 'mobooking'));

        $required_fields = ['code', 'type', 'value'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                // For 'value', 0 is not considered empty for this check, but will be caught by $value <= 0 later if 0.
                if ($field === 'value' && isset($data[$field]) && is_numeric($data[$field])) {
                    // Allow 0 if it's a valid scenario, though current logic requires positive.
                } else {
                    return new \WP_Error('missing_field', sprintf(__('Field "%s" is required.', 'mobooking'), $field));
                }
            }
        }

        $code = $this->_normalize_code($data['code']);
        $type = sanitize_text_field($data['type']);
        $value = floatval($data['value']);
        $expiry_date = !empty($data['expiry_date']) ? sanitize_text_field($data['expiry_date']) : null;
        $usage_limit = !empty($data['usage_limit']) ? intval($data['usage_limit']) : null;
        $status = !empty($data['status']) && in_array($data['status'], ['active', 'inactive']) ? $data['status'] : 'active';

        if (!in_array($type, ['percentage', 'fixed_amount'])) {
            return new \WP_Error('invalid_type', __('Invalid discount type. Must be "percentage" or "fixed_amount".', 'mobooking'));
        }
        if ($value <= 0) {
            return new \WP_Error('invalid_value', __('Discount value must be positive.', 'mobooking'));
        }
        if ($type === 'percentage' && $value > 100) {
            return new \WP_Error('invalid_percentage', __('Percentage discount cannot exceed 100.', 'mobooking'));
        }
        if ($expiry_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) {
            return new \WP_Error('invalid_date_format', __('Expiry date must be in YYYY-MM-DD format.', 'mobooking'));
        }
        if (!is_null($usage_limit) && $usage_limit < 0) {
            return new \WP_Error('invalid_usage_limit', __('Usage limit cannot be negative.', 'mobooking'));
        }


        // Check for code uniqueness for this user
        $existing_code = $this->get_discount_code_by_code($code, $user_id);
        if ($existing_code) {
            return new \WP_Error('duplicate_code', __('This discount code already exists for your account.', 'mobooking'));
        }

        $table_name = Database::get_table_name('discount_codes');
        $inserted = $this->wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'expiry_date' => $expiry_date,
                'usage_limit' => $usage_limit,
                'status' => $status,
                'times_used' => 0,
                'created_at' => current_time('mysql', 1)
            ],
            ['%d', '%s', '%s', '%f', '%s', '%d', '%s', '%d', '%s']
        );

        if (!$inserted) return new \WP_Error('db_error', __('Could not add discount code.', 'mobooking'));
        return $this->wpdb->insert_id;
    }

    public function get_discount_code(int $discount_id, int $user_id) {
        if (empty($user_id) || empty($discount_id)) return null;
        $table_name = Database::get_table_name('discount_codes');
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE discount_id = %d AND user_id = %d", $discount_id, $user_id
        ), ARRAY_A);
    }

    public function get_discount_code_by_code(string $code, int $user_id) {
        if (empty($user_id) || empty($code)) return null;
        $normalized_code = $this->_normalize_code($code);
        $table_name = Database::get_table_name('discount_codes');
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s AND user_id = %d", $normalized_code, $user_id
        ), ARRAY_A);
    }

    public function get_discount_codes_by_user(int $user_id, array $args = []) {
        if (empty($user_id)) return [];
        $table_name = Database::get_table_name('discount_codes');
        $defaults = ['status' => null, 'orderby' => 'created_at', 'order' => 'DESC', 'limit' => -1, 'offset' => 0];
        $args = wp_parse_args($args, $defaults);

        // Validate orderby and order
        $valid_orderby = ['discount_id', 'code', 'type', 'value', 'expiry_date', 'usage_limit', 'times_used', 'status', 'created_at'];
        $orderby = in_array($args['orderby'], $valid_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = [$user_id];

        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = sanitize_text_field($args['status']);
        }
        $sql .= " ORDER BY " . $orderby . " " . $order; // Already sanitized
        if (intval($args['limit']) > 0) {
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", intval($args['limit']), intval($args['offset']));
        }
        return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    public function update_discount_code(int $discount_id, int $user_id, array $data) {
        $current_discount = $this->get_discount_code($discount_id, $user_id);
        if (!$current_discount) {
            return new \WP_Error('not_found_or_owner', __('Discount code not found or you do not own it.', 'mobooking'));
        }

        $update_data = [];
        $format = [];

        if (isset($data['code'])) {
            $new_code = $this->_normalize_code($data['code']);
            if (empty($new_code)) return new \WP_Error('missing_field', __('Code cannot be empty.', 'mobooking'));
            if ($new_code !== $current_discount['code']) {
                $existing_code = $this->get_discount_code_by_code($new_code, $user_id);
                if ($existing_code) {
                    return new \WP_Error('duplicate_code', __('This discount code already exists for your account.', 'mobooking'));
                }
            }
            $update_data['code'] = $new_code;
            $format[] = '%s';
        }

        if (isset($data['type'])) {
            if (!in_array($data['type'], ['percentage', 'fixed_amount'])) {
                return new \WP_Error('invalid_type', __('Invalid discount type.', 'mobooking'));
            }
            $update_data['type'] = $data['type']; $format[] = '%s';
        }

        $type_for_value_check = isset($update_data['type']) ? $update_data['type'] : $current_discount['type'];
        if (isset($data['value'])) {
            $value = floatval($data['value']);
            if ($value <= 0) return new \WP_Error('invalid_value', __('Discount value must be positive.', 'mobooking'));
            if ($type_for_value_check === 'percentage' && $value > 100) {
                 return new \WP_Error('invalid_percentage', __('Percentage discount cannot exceed 100.', 'mobooking'));
            }
            $update_data['value'] = $value; $format[] = '%f';
        }

        if (array_key_exists('expiry_date', $data)) { // Allow clearing the date
            $expiry_date = $data['expiry_date'];
            if (empty($expiry_date)) {
                $update_data['expiry_date'] = null; $format[] = '%s';
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) {
                $update_data['expiry_date'] = sanitize_text_field($expiry_date); $format[] = '%s';
            } else {
                 return new \WP_Error('invalid_date_format', __('Expiry date must be YYYY-MM-DD or empty.', 'mobooking'));
            }
        }

        if (array_key_exists('usage_limit', $data)) { // Allow clearing the limit
             $usage_limit = $data['usage_limit'];
             if (empty($usage_limit)) { // Check if it's an empty string or 0 to set to NULL
                 $update_data['usage_limit'] = null; $format[] = '%d'; // Assuming DB field can be NULL
             } else {
                 $usage_limit_int = intval($usage_limit);
                 if ($usage_limit_int < 0) return new \WP_Error('invalid_usage_limit', __('Usage limit cannot be negative.', 'mobooking'));
                 $update_data['usage_limit'] = $usage_limit_int; $format[] = '%d';
             }
        }

        if (isset($data['status']) && in_array($data['status'], ['active', 'inactive'])) {
            $update_data['status'] = $data['status']; $format[] = '%s';
        }

        if (empty($update_data)) return true;

        $table_name = Database::get_table_name('discount_codes');
        $updated = $this->wpdb->update(
            $table_name,
            $update_data,
            ['discount_id' => $discount_id, 'user_id' => $user_id],
            $format,
            ['%d', '%d']
        );

        if (false === $updated) return new \WP_Error('db_error', __('Could not update discount code.', 'mobooking'));
        return true;
    }

    public function delete_discount_code(int $discount_id, int $user_id) {
        $current_discount = $this->get_discount_code($discount_id, $user_id);
        if (!$current_discount) {
            return new \WP_Error('not_found_or_owner', __('Discount code not found or you do not own it.', 'mobooking'));
        }
        $table_name = Database::get_table_name('discount_codes');
        $deleted = $this->wpdb->delete($table_name, ['discount_id' => $discount_id, 'user_id' => $user_id], ['%d', '%d']);
        if (false === $deleted) return new \WP_Error('db_error', __('Could not delete discount code.', 'mobooking'));
        return true;
    }

    public function validate_discount_code(string $code, int $user_id) {
        $discount = $this->get_discount_code_by_code($code, $user_id);
        if (!$discount) return new \WP_Error('not_found', __('Discount code not found.', 'mobooking'));

        if ($discount['status'] !== 'active') return new \WP_Error('inactive', __('This discount code is not active.', 'mobooking'));

        if (!empty($discount['expiry_date'])) {
            $today = current_time('Y-m-d', 0);
            if ($discount['expiry_date'] < $today) return new \WP_Error('expired', __('This discount code has expired.', 'mobooking'));
        }
        if (!empty($discount['usage_limit'])) {
            if (intval($discount['times_used']) >= intval($discount['usage_limit'])) {
                return new \WP_Error('limit_reached', __('This discount code has reached its usage limit.', 'mobooking'));
            }
        }
        return $discount;
    }

    public function increment_discount_usage(int $discount_id) {
        $table_name = Database::get_table_name('discount_codes');
        $result = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE $table_name SET times_used = times_used + 1 WHERE discount_id = %d", $discount_id
        ));
        return $result !== false;
    }
}
