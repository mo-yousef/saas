<?php
/**
 * Class Availability
 * Handles managing availability slots and overrides for users.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Availability {
    private $wpdb;
    private $slots_table_name;
    private $overrides_table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->slots_table_name = Database::get_table_name('availability_slots');
        $this->overrides_table_name = Database::get_table_name('availability_overrides');
    }

    public function register_ajax_actions() {
        // Recurring Slots Actions
        add_action('wp_ajax_mobooking_get_recurring_slots', [$this, 'ajax_get_recurring_slots']);
        add_action('wp_ajax_mobooking_save_recurring_slot', [$this, 'ajax_save_recurring_slot']);
        add_action('wp_ajax_mobooking_delete_recurring_slot', [$this, 'ajax_delete_recurring_slot']);

        // Date Override Actions
        add_action('wp_ajax_mobooking_get_date_overrides', [$this, 'ajax_get_date_overrides']); // For a range or specific date
        add_action('wp_ajax_mobooking_save_date_override', [$this, 'ajax_save_date_override']);
        add_action('wp_ajax_mobooking_delete_date_override', [$this, 'ajax_delete_date_override']);

        // Recurring Day Status
        add_action('wp_ajax_mobooking_set_recurring_day_status', [$this, 'ajax_set_recurring_day_status']);
    }

    // --- Recurring Availability Slot Methods ---

    /**
     * Get all recurring availability slots for a user.
     * @param int $user_id
     * @return array
     */
    public function get_recurring_slots(int $user_id): array {
        if (empty($user_id)) {
            return [];
        }
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT slot_id, day_of_week, start_time, end_time, capacity, is_active
                 FROM {$this->slots_table_name}
                 WHERE user_id = %d ORDER BY day_of_week ASC, start_time ASC",
                $user_id
            ),
            ARRAY_A
        );
        return $results ?: [];
    }

    /**
     * Add or update a recurring availability slot.
     * @param int $user_id
     * @param array $slot_data
     * @return int|false The ID of the inserted/updated slot, or false on failure.
     */
    public function save_recurring_slot(int $user_id, array $slot_data) {
        if (empty($user_id) || empty($slot_data) || !isset($slot_data['day_of_week']) || empty($slot_data['start_time']) || empty($slot_data['end_time'])) {
            return false; // Basic validation
        }

        $data = [
            'user_id'       => $user_id,
            'day_of_week'   => intval($slot_data['day_of_week']),
            'start_time'    => sanitize_text_field($slot_data['start_time']),
            'end_time'      => sanitize_text_field($slot_data['end_time']),
            'capacity'      => isset($slot_data['capacity']) ? intval($slot_data['capacity']) : 1,
            'is_active'     => isset($slot_data['is_active']) ? boolval($slot_data['is_active']) : 1,
        ];
        $formats = ['%d', '%d', '%s', '%s', '%d', '%d'];

        // Basic time validation (HH:MM:SS or HH:MM)
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $data['start_time']) ||
            !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $data['end_time'])) {
            error_log("MoBooking Availability: Invalid time format for recurring slot. Start: {$data['start_time']}, End: {$data['end_time']}");
            return false;
        }

        // Ensure start_time is before end_time
        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
             error_log("MoBooking Availability: Start time must be before end time for recurring slot.");
            return false;
        }

        if (isset($slot_data['slot_id']) && !empty($slot_data['slot_id'])) {
            // Update existing slot
            $slot_id = intval($slot_data['slot_id']);
            $updated = $this->wpdb->update($this->slots_table_name, $data, ['slot_id' => $slot_id, 'user_id' => $user_id], $formats, ['%d', '%d']);
            return ($updated !== false) ? $slot_id : false;
        } else {
            // Insert new slot
            $inserted = $this->wpdb->insert($this->slots_table_name, $data, $formats);
            return ($inserted !== false) ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Delete a recurring availability slot.
     * @param int $user_id
     * @param int $slot_id
     * @return bool True on success, false on failure.
     */
    public function delete_recurring_slot(int $user_id, int $slot_id): bool {
        if (empty($user_id) || empty($slot_id)) {
            return false;
        }
        $deleted = $this->wpdb->delete($this->slots_table_name, ['user_id' => $user_id, 'slot_id' => $slot_id], ['%d', '%d']);
        return ($deleted !== false);
    }

    /**
     * Set the status for an entire recurring day (e.g., mark all of Monday as off).
     * This currently means deactivating all slots for that day.
     * @param int $user_id
     * @param int $day_of_week
     * @param bool $is_day_off If true, marks day as off (deactivates slots). If false, (currently does nothing, user adds slots manually)
     * @return bool True on success, false on failure.
     */
    public function set_recurring_day_status(int $user_id, int $day_of_week, bool $is_day_off): bool {
        if (empty($user_id) || $day_of_week < 0 || $day_of_week > 6) {
            return false;
        }

        if ($is_day_off) {
            // Deactivate all existing slots for this user and day_of_week
            $updated = $this->wpdb->update(
                $this->slots_table_name,
                ['is_active' => 0], // Set to inactive
                ['user_id' => $user_id, 'day_of_week' => $day_of_week],
                ['%d'], // format for data
                ['%d', '%d']  // format for where
            );
            return ($updated !== false);
        } else {
            // If changing from "Day Off" to "Working Day", slots remain inactive.
            // User needs to add/activate them explicitly.
            // Potentially, we could activate all previously inactive slots for this day,
            // but that might not be desired. Current approach: user rebuilds the schedule for that day.
            return true; // No specific action to take other than UI will allow adding slots.
        }
    }


    // --- Date Override Methods ---

    /**
     * Get date overrides for a user, optionally within a date range.
     * @param int $user_id
     * @param string|null $date_from (Y-m-d)
     * @param string|null $date_to (Y-m-d)
     * @return array
     */
    public function get_date_overrides(int $user_id, ?string $date_from = null, ?string $date_to = null): array {
        if (empty($user_id)) {
            return [];
        }
        $sql = "SELECT override_id, override_date, start_time, end_time, capacity, is_unavailable, notes
                FROM {$this->overrides_table_name}
                WHERE user_id = %d";
        $params = [$user_id];

        if ($date_from) {
            $sql .= " AND override_date >= %s";
            $params[] = $date_from;
        }
        if ($date_to) {
            $sql .= " AND override_date <= %s";
            $params[] = $date_to;
        }
        $sql .= " ORDER BY override_date ASC";

        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        return $results ?: [];
    }

    /**
     * Get a single date override by its ID.
     * @param int $user_id
     * @param int $override_id
     * @return array|null
     */
    public function get_date_override_by_id(int $user_id, int $override_id): ?array {
        if (empty($user_id) || empty($override_id)) {
            return null;
        }
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT override_id, override_date, start_time, end_time, capacity, is_unavailable, notes
                 FROM {$this->overrides_table_name}
                 WHERE user_id = %d AND override_id = %d",
                $user_id, $override_id
            ),
            ARRAY_A
        );
        return $result ?: null;
    }


    /**
     * Add or update a date override.
     * Unique key on (user_id, override_date) means we INSERT or UPDATE based on this.
     * @param int $user_id
     * @param array $override_data
     * @return int|false The ID of the inserted/updated override, or false on failure.
     */
    public function save_date_override(int $user_id, array $override_data) {
        if (empty($user_id) || empty($override_data) || empty($override_data['override_date'])) {
            return false; // Basic validation
        }

        $data = [
            'user_id'         => $user_id,
            'override_date'   => sanitize_text_field($override_data['override_date']),
            'is_unavailable'  => isset($override_data['is_unavailable']) ? boolval($override_data['is_unavailable']) : 0,
            'notes'           => isset($override_data['notes']) ? sanitize_textarea_field($override_data['notes']) : null,
        ];
        // Default formats, will adjust for nullable fields
        $formats = ['%d', '%s', '%d', '%s'];

        // Date validation (Y-m-d)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['override_date'])) {
            error_log("MoBooking Availability: Invalid date format for override. Date: {$data['override_date']}");
            return false;
        }

        if ($data['is_unavailable']) {
            // If marked as unavailable, start_time, end_time, capacity are typically null or ignored
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['capacity'] = null;
        } else {
            // If it's an available slot, start_time and end_time are required
            if (empty($override_data['start_time']) || empty($override_data['end_time'])) {
                 error_log("MoBooking Availability: Start time and end time are required for an available override slot.");
                return false;
            }
            $data['start_time'] = sanitize_text_field($override_data['start_time']);
            $data['end_time'] = sanitize_text_field($override_data['end_time']);
            $data['capacity'] = isset($override_data['capacity']) ? intval($override_data['capacity']) : 1;

            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $data['start_time']) ||
                !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $data['end_time'])) {
                error_log("MoBooking Availability: Invalid time format for override slot. Start: {$data['start_time']}, End: {$data['end_time']}");
                return false;
            }
            if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
                error_log("MoBooking Availability: Start time must be before end time for override slot.");
                return false;
            }
        }

        // Add formats for start_time, end_time, capacity
        array_splice($formats, 2, 0, ['%s', '%s', '%d']); // Insert after override_date format

        // Check if an override for this user and date already exists
        $existing_override_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT override_id FROM {$this->overrides_table_name} WHERE user_id = %d AND override_date = %s",
                $user_id, $data['override_date']
            )
        );

        if ($existing_override_id) {
            // Update existing override for that date
            $updated = $this->wpdb->update($this->overrides_table_name, $data, ['override_id' => $existing_override_id, 'user_id' => $user_id], $formats, ['%d', '%d']);
            return ($updated !== false) ? $existing_override_id : false;
        } else {
            // Insert new override
            $inserted = $this->wpdb->insert($this->overrides_table_name, $data, $formats);
            return ($inserted !== false) ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Delete a date override.
     * @param int $user_id
     * @param int $override_id
     * @return bool True on success, false on failure.
     */
    public function delete_date_override(int $user_id, int $override_id): bool {
        if (empty($user_id) || empty($override_id)) {
            return false;
        }
        $deleted = $this->wpdb->delete($this->overrides_table_name, ['user_id' => $user_id, 'override_id' => $override_id], ['%d', '%d']);
        return ($deleted !== false);
    }

    // --- AJAX Handlers ---

    public function ajax_get_recurring_slots() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        $slots = $this->get_recurring_slots($user_id);
        wp_send_json_success($slots);
    }

    public function ajax_save_recurring_slot() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $slot_data = isset($_POST['slot_data']) ? (array) json_decode(stripslashes($_POST['slot_data']), true) : [];
        if (empty($slot_data)) {
            wp_send_json_error(['message' => __('Slot data is missing.', 'mobooking')], 400);
            return;
        }

        // Ensure slot_id is passed correctly if present in $slot_data
        if(isset($slot_data['slot_id'])) $slot_data['slot_id'] = intval($slot_data['slot_id']);


        $result_id = $this->save_recurring_slot($user_id, $slot_data);

        if ($result_id === false) {
            wp_send_json_error(['message' => __('Failed to save recurring slot. Please check your input.', 'mobooking')], 500);
        } else {
            // Fetch the saved/updated slot to return it
            $saved_slot = $this->wpdb->get_row( $this->wpdb->prepare("SELECT * FROM {$this->slots_table_name} WHERE slot_id = %d AND user_id = %d", $result_id, $user_id), ARRAY_A);
            wp_send_json_success(['message' => __('Recurring slot saved successfully.', 'mobooking'), 'slot' => $saved_slot]);
        }
    }

    public function ajax_delete_recurring_slot() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
        if (empty($slot_id)) {
            wp_send_json_error(['message' => __('Slot ID is missing.', 'mobooking')], 400);
            return;
        }

        if ($this->delete_recurring_slot($user_id, $slot_id)) {
            wp_send_json_success(['message' => __('Recurring slot deleted successfully.', 'mobooking')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete recurring slot.', 'mobooking')], 500);
        }
    }

    public function ajax_get_date_overrides() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : null;
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : null;
        // Add validation for date formats if needed

        $overrides = $this->get_date_overrides($user_id, $date_from, $date_to);
        wp_send_json_success($overrides);
    }

    public function ajax_save_date_override() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $override_data = isset($_POST['override_data']) ? (array) json_decode(stripslashes($_POST['override_data']), true) : [];
         if (empty($override_data) || empty($override_data['override_date'])) {
            wp_send_json_error(['message' => __('Override data is missing or incomplete.', 'mobooking')], 400);
            return;
        }

        $result_id = $this->save_date_override($user_id, $override_data);

        if ($result_id === false) {
            wp_send_json_error(['message' => __('Failed to save date override. Please check your input.', 'mobooking')], 500);
        } else {
             $saved_override = $this->get_date_override_by_id($user_id, $result_id);
            wp_send_json_success(['message' => __('Date override saved successfully.', 'mobooking'), 'override' => $saved_override]);
        }
    }

    public function ajax_delete_date_override() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $override_id = isset($_POST['override_id']) ? intval($_POST['override_id']) : 0;
        if (empty($override_id)) {
            wp_send_json_error(['message' => __('Override ID is missing.', 'mobooking')], 400);
            return;
        }

        if ($this->delete_date_override($user_id, $override_id)) {
            wp_send_json_success(['message' => __('Date override deleted successfully.', 'mobooking')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete date override.', 'mobooking')], 500);
        }
    }

    public function ajax_set_recurring_day_status() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $day_of_week = isset($_POST['day_of_week']) ? intval($_POST['day_of_week']) : -1;
        $is_day_off = isset($_POST['is_day_off']) ? filter_var($_POST['is_day_off'], FILTER_VALIDATE_BOOLEAN) : false;

        if ($day_of_week < 0 || $day_of_week > 6) {
            wp_send_json_error(['message' => __('Invalid day of the week.', 'mobooking')], 400);
            return;
        }

        if ($this->set_recurring_day_status($user_id, $day_of_week, $is_day_off)) {
            $message = $is_day_off ? __('Day marked as off. All existing slots for this day have been deactivated.', 'mobooking')
                                   : __('Day status updated. You can now add or activate slots for this day.', 'mobooking');
            wp_send_json_success(['message' => $message]);
        } else {
            wp_send_json_error(['message' => __('Failed to update recurring day status.', 'mobooking')], 500);
        }
    }
}
