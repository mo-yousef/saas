<?php
namespace NORDBOOKING\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ServicePerformance {
    public function __construct() {
        add_action('wp_ajax_nordbooking_get_service_performance', [$this, 'get_service_performance_data']);
    }

    public function get_service_performance_data() {
        error_log('NORDBOOKING ServicePerformance: AJAX handler called');
        
        try {
            // Check nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_ajax_nonce')) {
                error_log('NORDBOOKING ServicePerformance: Nonce verification failed');
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            global $wpdb;
            $bookings_table = Database::get_table_name('bookings');
            $services_table = Database::get_table_name('services');
            
            error_log('NORDBOOKING ServicePerformance: Tables - bookings: ' . $bookings_table . ', services: ' . $services_table);

        $current_user_id = get_current_user_id();
        $data_user_id = $current_user_id;

        if (class_exists('NORDBOOKING\Classes\Auth') && Auth::is_user_worker($current_user_id)) {
            $owner_id = Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        error_log('NORDBOOKING ServicePerformance: Querying for user_id: ' . $data_user_id);
        
        // Get booking_items table name
        $booking_items_table = Database::get_table_name('booking_items');
        
        // First check if tables exist
        $bookings_exists = $wpdb->get_var("SHOW TABLES LIKE '{$bookings_table}'") == $bookings_table;
        $services_exists = $wpdb->get_var("SHOW TABLES LIKE '{$services_table}'") == $services_table;
        $booking_items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$booking_items_table}'") == $booking_items_table;
        
        if (!$bookings_exists || !$services_exists || !$booking_items_exists) {
            error_log('NORDBOOKING ServicePerformance: Required tables do not exist - bookings: ' . ($bookings_exists ? 'yes' : 'no') . ', services: ' . ($services_exists ? 'yes' : 'no') . ', booking_items: ' . ($booking_items_exists ? 'yes' : 'no'));
            wp_send_json_success([
                'labels' => ['No Data'],
                'data' => [0],
            ]);
            return;
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.name, COUNT(DISTINCT b.booking_id) as booking_count
            FROM {$bookings_table} b
            JOIN {$booking_items_table} bi ON b.booking_id = bi.booking_id
            JOIN {$services_table} s ON bi.service_id = s.service_id
            WHERE b.user_id = %d
            GROUP BY s.service_id, s.name
            ORDER BY booking_count DESC
            LIMIT 10",
            $data_user_id
        ), ARRAY_A);
        
        if ($wpdb->last_error) {
            error_log('NORDBOOKING ServicePerformance: SQL Error: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            return;
        }
        
        error_log('NORDBOOKING ServicePerformance: Query results: ' . print_r($results, true));

        $labels = [];
        $data = [];

        if (!empty($results)) {
            foreach ($results as $row) {
                $labels[] = $row['name'];
                $data[] = (int)$row['booking_count'];
            }
        } else {
            // Try to get services without bookings
            $services_only = $wpdb->get_results($wpdb->prepare(
                "SELECT name FROM {$services_table} WHERE user_id = %d AND status = 'active' LIMIT 5",
                $data_user_id
            ), ARRAY_A);
            
            if (!empty($services_only)) {
                foreach ($services_only as $service) {
                    $labels[] = $service['name'];
                    $data[] = 0;
                }
            } else {
                // Provide sample data if no services found
                $labels = ['No Services Available'];
                $data = [0];
            }
        }

        error_log('NORDBOOKING ServicePerformance: Sending response - labels: ' . print_r($labels, true) . ', data: ' . print_r($data, true));

        wp_send_json_success([
            'labels' => $labels,
            'data' => $data,
        ]);
        
        } catch (Exception $e) {
            error_log('NORDBOOKING ServicePerformance: Exception - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}
