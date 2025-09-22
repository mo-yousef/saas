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
        
        // Public time slots for booking (both logged in and non-logged in users)
        add_action('wp_ajax_nordbooking_get_available_time_slots', [$this, 'ajax_get_available_time_slots']);
        add_action('wp_ajax_nopriv_nordbooking_get_available_time_slots', [$this, 'ajax_get_available_time_slots']);
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

    /**
     * Get available time slots for a specific date and business owner
     */
    public function get_available_time_slots_for_date(int $user_id, string $date): array {
        try {
            if (empty($user_id) || empty($date)) {
                error_log('NORDBOOKING: Empty user_id or date provided');
                return [];
            }

            // Validate date format
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
                error_log('NORDBOOKING: Invalid date format: ' . $date);
                return [];
            }

            // Get day of week (0 = Sunday, 1 = Monday, etc.)
            $day_of_week = $date_obj->format('w');
            error_log("NORDBOOKING: Looking for availability on day $day_of_week for user $user_id");

            // Get recurring schedule for this day
            $recurring_slots = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT start_time, end_time FROM {$this->slots_table_name} 
                     WHERE user_id = %d AND day_of_week = %d AND is_active = 1 
                     ORDER BY start_time ASC",
                    $user_id,
                    $day_of_week
                ),
                ARRAY_A
            );

            if ($this->wpdb->last_error) {
                error_log('NORDBOOKING: Database error: ' . $this->wpdb->last_error);
                return [];
            }

            error_log('NORDBOOKING: Found ' . count($recurring_slots) . ' recurring slots');

            if (empty($recurring_slots)) {
                return [];
            }

            // Generate time slots based on recurring schedule
            $available_slots = [];
            $slot_duration = 30; // 30-minute slots

            foreach ($recurring_slots as $slot) {
                $start_time = $slot['start_time'];
                $end_time = $slot['end_time'];

                // Convert to DateTime objects
                $start = DateTime::createFromFormat('H:i:s', $start_time);
                $end = DateTime::createFromFormat('H:i:s', $end_time);

                if (!$start || !$end) {
                    error_log("NORDBOOKING: Invalid time format - start: $start_time, end: $end_time");
                    continue;
                }

                // Generate 30-minute slots within this time range
                $current = clone $start;
                while ($current < $end) {
                    $slot_time = $current->format('H:i');
                    
                    // Check if this slot is already booked
                    if (!$this->is_time_slot_booked($user_id, $date, $slot_time)) {
                        $available_slots[] = [
                            'time' => $slot_time,
                            'display' => $current->format('g:i A'),
                            'available' => true
                        ];
                    }

                    // Add 30 minutes
                    $current->add(new DateInterval('PT' . $slot_duration . 'M'));
                }
            }

            error_log('NORDBOOKING: Generated ' . count($available_slots) . ' available slots');
            return $available_slots;

        } catch (Exception $e) {
            error_log('NORDBOOKING: Exception in get_available_time_slots_for_date: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a specific time slot is already booked
     */
    private function is_time_slot_booked(int $user_id, string $date, string $time): bool {
        global $wpdb;
        
        $bookings_table = Database::get_table_name('bookings');
        
        $existing_booking = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT booking_id FROM {$bookings_table} 
                 WHERE user_id = %d AND booking_date = %s AND booking_time = %s 
                 AND status IN ('pending', 'confirmed') 
                 LIMIT 1",
                $user_id,
                $date,
                $time . ':00' // Add seconds for database format
            )
        );

        return !empty($existing_booking);
    }

    /**
     * AJAX handler to get available time slots for a specific date
     */
    public function ajax_get_available_time_slots() {
        try {
            error_log('NORDBOOKING: ajax_get_available_time_slots called');
            
            // Get parameters
            $tenant_id = intval($_POST['tenant_id'] ?? 0);
            $date = sanitize_text_field($_POST['date'] ?? '');

            error_log("NORDBOOKING: Parameters - tenant_id: $tenant_id, date: $date");

            if (empty($tenant_id) || empty($date)) {
                error_log('NORDBOOKING: Missing required parameters');
                wp_send_json_error(['message' => __('Missing required parameters.', 'NORDBOOKING')]);
                return;
            }

            // Verify nonce if provided (for logged-in users)
            if (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
                if (!wp_verify_nonce($_POST['nonce'], 'nordbooking_booking_form_nonce') && 
                    !wp_verify_nonce($_POST['nonce'], 'nordbooking_customer_booking_management')) {
                    error_log('NORDBOOKING: Nonce verification failed');
                    wp_send_json_error(['message' => __('Security check failed.', 'NORDBOOKING')]);
                    return;
                }
            }

            // Check if availability tables exist
            $tables_exist = $this->check_availability_tables();
            if (!$tables_exist) {
                error_log('NORDBOOKING: Availability tables do not exist, using fallback');
                // Use fallback time slots if tables don't exist
                $time_slots = $this->get_fallback_time_slots();
            } else {
                // Get available time slots from database
                $time_slots = $this->get_available_time_slots_for_date($tenant_id, $date);
                
                // If no slots found in database, use fallback
                if (empty($time_slots)) {
                    error_log('NORDBOOKING: No availability rules found, using fallback');
                    $time_slots = $this->get_fallback_time_slots();
                }
            }

            error_log('NORDBOOKING: Returning ' . count($time_slots) . ' time slots');

            wp_send_json_success([
                'date' => $date,
                'time_slots' => $time_slots
            ]);

        } catch (Exception $e) {
            error_log('NORDBOOKING: Exception in ajax_get_available_time_slots: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if availability tables exist
     */
    private function check_availability_tables(): bool {
        $rules_table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->slots_table_name}'") == $this->slots_table_name;
        return $rules_table_exists;
    }

    /**
     * Get fallback time slots when no availability rules are set
     */
    private function get_fallback_time_slots(): array {
        $fallback_slots = [];
        
        // Generate standard business hours (9 AM to 5 PM, 30-minute intervals)
        for ($hour = 9; $hour <= 17; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                if ($hour === 17 && $minute > 0) break; // Stop at 5:00 PM
                
                $time_24 = sprintf('%02d:%02d', $hour, $minute);
                $time_obj = DateTime::createFromFormat('H:i', $time_24);
                $time_12 = $time_obj->format('g:i A');
                
                $fallback_slots[] = [
                    'time' => $time_24,
                    'display' => $time_12,
                    'available' => true
                ];
            }
        }
        
        return $fallback_slots;
    }

}
