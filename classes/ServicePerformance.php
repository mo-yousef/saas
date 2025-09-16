<?php
namespace NORDBOOKING\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ServicePerformance {
    public function __construct() {
        add_action('wp_ajax_nordbooking_get_service_performance', [$this, 'get_service_performance_data']);
    }

    public function get_service_performance_data() {
        check_ajax_referer('nordbooking_ajax_nonce', 'nonce');

        global $wpdb;
        $bookings_table = Database::get_table_name('bookings');
        $services_table = Database::get_table_name('services');

        $current_user_id = get_current_user_id();
        $data_user_id = $current_user_id;

        if (class_exists('NORDBOOKING\Classes\Auth') && Auth::is_user_worker($current_user_id)) {
            $owner_id = Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.name, COUNT(b.booking_id) as booking_count
            FROM {$bookings_table} b
            JOIN {$services_table} s ON b.service_id = s.id
            WHERE b.user_id = %d
            GROUP BY s.name
            ORDER BY booking_count DESC
            LIMIT 10",
            $data_user_id
        ), ARRAY_A);

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = $row['name'];
            $data[] = (int)$row['booking_count'];
        }

        wp_send_json_success([
            'labels' => $labels,
            'data' => $data,
        ]);
    }
}
