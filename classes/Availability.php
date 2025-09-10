<?php
/**
 * Class Availability
 * Handles managing availability slots and overrides for users.
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

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
        $this->slots_table_name = Database::get_table_name('availability_rules');
        $this->overrides_table_name = Database::get_table_name('availability_exceptions');
    }

    public function register_ajax_actions() {
        // Recurring Schedule Actions
        add_action('wp_ajax_nordbooking_get_recurring_schedule', [$this, 'ajax_get_recurring_schedule']);
        add_action('wp_ajax_nordbooking_save_recurring_schedule', [$this, 'ajax_save_recurring_schedule']);
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

    // --- AJAX Handlers ---

    public function ajax_get_recurring_schedule() {
        check_ajax_referer('nordbooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }
        $schedule = $this->get_recurring_schedule($user_id);
        wp_send_json_success($schedule);
    }

    public function ajax_save_recurring_schedule() {
        check_ajax_referer('nordbooking_availability_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'NORDBOOKING')], 403);
            return;
        }

        $schedule_data = isset($_POST['schedule_data']) ? json_decode(stripslashes($_POST['schedule_data']), true) : [];
        if (empty($schedule_data)) {
            wp_send_json_error(['message' => __('Schedule data is missing.', 'NORDBOOKING')], 400);
            return;
        }

        if ($this->save_recurring_schedule($user_id, $schedule_data)) {
            wp_send_json_success(['message' => __('Recurring schedule saved successfully.', 'NORDBOOKING')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save recurring schedule.', 'NORDBOOKING')], 500);
        }
    }

}
