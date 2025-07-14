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
        // Recurring Schedule Actions
        add_action('wp_ajax_mobooking_get_recurring_schedule', [$this, 'ajax_get_recurring_schedule']);
        add_action('wp_ajax_mobooking_save_recurring_schedule', [$this, 'ajax_save_recurring_schedule']);

        // Date Override Actions
        add_action('wp_ajax_mobooking_get_date_overrides', [$this, 'ajax_get_date_overrides']);
        add_action('wp_ajax_mobooking_save_date_override', [$this, 'ajax_save_date_override']);
        add_action('wp_ajax_mobooking_delete_date_override', [$this, 'ajax_delete_date_override']);
    }

    // --- Recurring Availability Schedule Methods ---

    public function get_recurring_schedule(int $user_id): array {
        if (empty($user_id)) {
            return [];
        }

        $slots = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT day_of_week, start_time, end_time FROM {$this->slots_table_name} WHERE user_id = %d AND is_active = 1 ORDER BY start_time ASC",
                $user_id
            ),
            ARRAY_A
        );

        $schedule = [];
        for ($i = 0; $i < 7; $i++) {
            $schedule[$i] = [
                'day_of_week' => $i,
                'is_enabled' => false,
                'slots' => [],
            ];
        }

        foreach ($slots as $slot) {
            $day = intval($slot['day_of_week']);
            $schedule[$day]['is_enabled'] = true;
            $schedule[$day]['slots'][] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
            ];
        }

        return array_values($schedule);
    }

    public function save_recurring_schedule(int $user_id, array $schedule_data): bool {
        if (empty($user_id)) {
            return false;
        }

        // Start transaction
        $this->wpdb->query('START TRANSACTION');

        // Delete all existing recurring slots for the user
        $this->wpdb->delete($this->slots_table_name, ['user_id' => $user_id], ['%d']);

        foreach ($schedule_data as $day_data) {
            if (empty($day_data['is_enabled']) || empty($day_data['slots'])) {
                continue;
            }

            foreach ($day_data['slots'] as $slot) {
                if (empty($slot['start_time']) || empty($slot['end_time'])) {
                    continue;
                }

                $inserted = $this->wpdb->insert(
                    $this->slots_table_name,
                    [
                        'user_id' => $user_id,
                        'day_of_week' => intval($day_data['day_of_week']),
                        'start_time' => sanitize_text_field($slot['start_time']),
                        'end_time' => sanitize_text_field($slot['end_time']),
                        'is_active' => 1,
                        'capacity' => 1, // Default capacity
                    ],
                    ['%d', '%d', '%s', '%s', '%d', '%d']
                );

                if (!$inserted) {
                    $this->wpdb->query('ROLLBACK'); // Rollback on failure
                    return false;
                }
            }
        }

        $this->wpdb->query('COMMIT'); // Commit transaction
        return true;
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

    public function ajax_get_recurring_schedule() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        $schedule = $this->get_recurring_schedule($user_id);
        wp_send_json_success($schedule);
    }

    public function ajax_save_recurring_schedule() {
        check_ajax_referer('mobooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $schedule_data = isset($_POST['schedule_data']) ? json_decode(stripslashes($_POST['schedule_data']), true) : [];
        if (empty($schedule_data)) {
            wp_send_json_error(['message' => __('Schedule data is missing.', 'mobooking')], 400);
            return;
        }

        if ($this->save_recurring_schedule($user_id, $schedule_data)) {
            wp_send_json_success(['message' => __('Recurring schedule saved successfully.', 'mobooking')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save recurring schedule.', 'mobooking')], 500);
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
