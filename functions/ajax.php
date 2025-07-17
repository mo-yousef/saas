<?php
// START MOVED DASHBOARD AJAX HANDLERS
// Ensure these are defined globally and early for admin-ajax.php

// AJAX handler for dashboard overview data (KPIs)
add_action('wp_ajax_mobooking_get_dashboard_overview_data', 'mobooking_ajax_get_dashboard_overview_data');
if ( ! function_exists( 'mobooking_ajax_get_dashboard_overview_data' ) ) {
    function mobooking_ajax_get_dashboard_overview_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403); return;
        }
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401); return;
        }
        if (!isset($GLOBALS['mobooking_services_manager']) || !isset($GLOBALS['mobooking_bookings_manager'])) {
             wp_send_json_error(array('message' => 'Core components not available.'), 500); return;
        }
        $services_manager = $GLOBALS['mobooking_services_manager'];
        $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) { $data_user_id = $owner_id; }
        }
        try {
            $kpi_data = $bookings_manager->get_kpi_data($data_user_id);
            $services_count = $services_manager->get_services_count($data_user_id);
            $kpi_data['services_count'] = $services_count;
            wp_send_json_success(array('kpis' => $kpi_data));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load dashboard data: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for recent bookings
add_action('wp_ajax_mobooking_get_recent_bookings', 'mobooking_ajax_get_recent_bookings');
if ( ! function_exists( 'mobooking_ajax_get_recent_bookings' ) ) {
    function mobooking_ajax_get_recent_bookings() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403); return;
        }
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401); return;
        }
        if (!isset($GLOBALS['mobooking_bookings_manager'])) {
             wp_send_json_error(array('message' => 'Bookings component not available.'), 500); return;
        }
        $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
        try {
            $args = array('limit' => $limit, 'orderby' => 'created_at', 'order' => 'DESC');
            $bookings_result = $bookings_manager->get_bookings_by_tenant($current_user_id, $args);
            if (is_wp_error($bookings_result)) {
                wp_send_json_error(array('message' => $bookings_result->get_error_message()), 400); return;
            }
            wp_send_json_success($bookings_result['bookings'] ?? array());
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load recent bookings: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for fetching all bookings for FullCalendar
add_action('wp_ajax_mobooking_get_all_bookings_for_calendar', 'mobooking_ajax_get_all_bookings_for_calendar');
if ( ! function_exists( 'mobooking_ajax_get_all_bookings_for_calendar' ) ) {
    function mobooking_ajax_get_all_bookings_for_calendar() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }

        if (!isset($GLOBALS['mobooking_bookings_manager'])) {
             wp_send_json_error(array('message' => 'Bookings component not available.'), 500);
            return;
        }
        $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
        $dashboard_base_url = home_url('/dashboard/bookings/?booking_id='); // Base URL for booking details

        // Determine user for data fetching (handle workers)
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        // Get start and end date parameters if provided by FullCalendar
        $start_date_str = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : null;
        $end_date_str = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : null;

        try {
            // TEMPORARY TEST: Return a simple dummy event to check if the AJAX handler itself is working.
            $dummy_events = array(
                array(
                    'id'    => 'dummy1',
                    'title' => 'Test Booking @ ' . date('H:i'),
                    'start' => date('Y-m-d') . 'T10:00:00', // Today at 10 AM
                    'end'   => date('Y-m-d') . 'T11:00:00', // Today at 11 AM
                    'allDay' => false,
                    'backgroundColor' => '#3a87ad',
                    'borderColor' => '#3a87ad',
                    'url' => '#'
                ),
                array(
                    'id'    => 'dummy2',
                    'title' => 'Another Test @ ' . date('H:i', strtotime('+2 hours')),
                    'start' => date('Y-m-d', strtotime('+1 day')) . 'T14:00:00', // Tomorrow at 2 PM
                    'end'   => date('Y-m-d', strtotime('+1 day')) . 'T15:00:00', // Tomorrow at 3 PM
                    'allDay' => false,
                    'backgroundColor' => '#468847',
                    'borderColor' => '#468847',
                    'url' => '#'
                )
            );
            wp_send_json_success($dummy_events);
            return; // Exit after sending dummy data

            // TODO: Restore actual data fetching logic below and remove dummy data above.
            // $all_bookings_result = $bookings_manager->get_bookings_by_tenant($data_user_id, ['limit' => -1, 'status' => null]);

            // if (is_wp_error($all_bookings_result)) {
            //     wp_send_json_error(array('message' => $all_bookings_result->get_error_message()), 400);
            //     return;
            // }

            // $bookings = $all_bookings_result['bookings'] ?? array();
            // $calendar_events = array();

            // foreach ($bookings as $booking) {
            //     if (empty($booking['booking_date']) || empty($booking['booking_time'])) {
            //         continue;
            //     }

            //     $start_datetime_str = $booking['booking_date'] . ' ' . $booking['booking_time'];
            //     $start_datetime = new DateTime($start_datetime_str);
            //     $end_datetime = clone $start_datetime;
            //     // $service_duration_minutes = $booking['service_duration'] ?? 60;
            //     // $end_datetime->add(new DateInterval('PT' . $service_duration_minutes . 'M'));

            //     $event_color = '#3a87ad';
            //     switch ($booking['status']) {
            //         case 'confirmed': $event_color = '#468847'; break;
            //         case 'pending': $event_color = '#f89406'; break;
            //         case 'cancelled': $event_color = '#b94a48'; break;
            //         case 'completed': $event_color = '#3a58ad'; break;
            //     }

            //     $calendar_events[] = array(
            //         'id' => $booking['booking_id'],
            //         'title' => $booking['customer_name'] . ' - ' . ($booking['service_name'] ?? 'Booking'),
            //         'start' => $start_datetime->format(DateTime::ATOM),
            //         // 'end' => $end_datetime->format(DateTime::ATOM),
            //         'allDay' => false,
            //         'url' => esc_url($dashboard_base_url . $booking['booking_id']),
            //         'backgroundColor' => $event_color,
            //         'borderColor' => $event_color,
            //     );
            // }
            // wp_send_json_success($calendar_events);

        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load bookings for calendar: ' . $e->getMessage()), 500);
        }
    }
}
// END MOVED DASHBOARD AJAX HANDLERS
?>
