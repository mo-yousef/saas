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
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403); return;
        }
        // Forcing a simple success for now to test AJAX plumbing
        wp_send_json_success(array(
            array('id' => 'test1', 'title' => 'Calendar Test Event', 'start' => date('Y-m-d'))
        ));
        return;
    }
}
// END MOVED DASHBOARD AJAX HANDLERS
?>
