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

// Add these missing AJAX handlers to functions/ajax.php

// AJAX handler for live activity feed
add_action('wp_ajax_mobooking_get_live_activity', 'mobooking_ajax_get_live_activity');
if (!function_exists('mobooking_ajax_get_live_activity')) {
    function mobooking_ajax_get_live_activity() {
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
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        // Determine user for data fetching (handle workers)
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }
        
        try {
            $activities = $bookings_manager->get_live_activity($data_user_id, $limit);
            wp_send_json_success($activities);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load activity feed: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for top services
add_action('wp_ajax_mobooking_get_top_services', 'mobooking_ajax_get_top_services');
if (!function_exists('mobooking_ajax_get_top_services')) {
    function mobooking_ajax_get_top_services() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        if (!isset($GLOBALS['mobooking_services_manager'])) {
            wp_send_json_error(array('message' => 'Services component not available.'), 500);
            return;
        }
        
        $services_manager = $GLOBALS['mobooking_services_manager'];
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        // Determine user for data fetching (handle workers)
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }
        
        try {
            $top_services = $services_manager->get_top_services($data_user_id, $limit);
            wp_send_json_success($top_services);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load top services: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for customer insights
add_action('wp_ajax_mobooking_get_customer_insights', 'mobooking_ajax_get_customer_insights');
if (!function_exists('mobooking_ajax_get_customer_insights')) {
    function mobooking_ajax_get_customer_insights() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        if (!isset($GLOBALS['mobooking_customers_manager'])) {
            wp_send_json_error(array('message' => 'Customers component not available.'), 500);
            return;
        }
        
        $customers_manager = $GLOBALS['mobooking_customers_manager'];
        
        // Determine user for data fetching (handle workers)
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }
        
        try {
            $insights = $customers_manager->get_customer_insights($data_user_id);
            wp_send_json_success($insights);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load customer insights: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for booking chart data
add_action('wp_ajax_mobooking_get_chart_data', 'mobooking_ajax_get_chart_data');
if (!function_exists('mobooking_ajax_get_chart_data')) {
    function mobooking_ajax_get_chart_data() {
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
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        
        // Determine user for data fetching (handle workers)
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }
        
        try {
            $chart_data = $bookings_manager->get_booking_chart_data($data_user_id, $period);
            wp_send_json_success($chart_data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load chart data: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for subscription usage
add_action('wp_ajax_mobooking_get_subscription_usage', 'mobooking_ajax_get_subscription_usage');
if (!function_exists('mobooking_ajax_get_subscription_usage')) {
    function mobooking_ajax_get_subscription_usage() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        // For now, return mock data since subscription management isn't fully implemented
        // In a real implementation, this would connect to your subscription system
        $usage_data = array(
            'bookings_used' => 15,
            'bookings_limit' => 100,
            'customers_used' => 45,
            'customers_limit' => 500,
            'storage_used' => 2.5, // GB
            'storage_limit' => 10, // GB
            'plan_name' => 'Professional',
            'plan_price' => 49.99,
            'plan_period' => 'month',
            'next_billing_date' => date('Y-m-d', strtotime('+1 month')),
            'status' => 'active'
        );
        
        wp_send_json_success($usage_data);
    }
}

// Fix the KPI data handler - the JS expects 'mobooking_get_dashboard_kpi_data' but we have 'mobooking_get_dashboard_overview_data'
add_action('wp_ajax_mobooking_get_dashboard_kpi_data', 'mobooking_ajax_get_dashboard_overview_data');





/**
 * Fixed AJAX Functions for MoBooking Dashboard
 * This file contains the corrected AJAX handlers to fix the 500 errors
 */

// Ensure these are defined globally and early for admin-ajax.php

// AJAX handler for dashboard KPI data (Fixed action name mismatch)
add_action('wp_ajax_mobooking_get_dashboard_kpi_data', 'mobooking_ajax_get_dashboard_kpi_data');
if (!function_exists('mobooking_ajax_get_dashboard_kpi_data')) {
    function mobooking_ajax_get_dashboard_kpi_data() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }

        // Initialize managers if not already done
        if (!isset($GLOBALS['mobooking_services_manager']) || !isset($GLOBALS['mobooking_bookings_manager'])) {
            // Try to initialize managers
            try {
                if (!isset($GLOBALS['mobooking_services_manager'])) {
                    $GLOBALS['mobooking_services_manager'] = new \MoBooking\Classes\Services();
                }
                if (!isset($GLOBALS['mobooking_bookings_manager'])) {
                    $GLOBALS['mobooking_bookings_manager'] = new \MoBooking\Classes\Bookings();
                }
            } catch (Exception $e) {
                wp_send_json_error(array('message' => 'Failed to initialize core components: ' . $e->getMessage()), 500);
                return;
            }
        }

        $services_manager = $GLOBALS['mobooking_services_manager'];
        $bookings_manager = $GLOBALS['mobooking_bookings_manager'];

        // Handle worker permissions
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        try {
            // Get KPI data
            $kpi_data = $bookings_manager->get_kpi_data($data_user_id);
            
            // Get services count
            $services_count = $services_manager->get_services_count($data_user_id);
            
            // Prepare response data
            $response_data = array(
                'total_bookings' => isset($kpi_data['total_bookings']) ? $kpi_data['total_bookings'] : 0,
                'pending_bookings' => isset($kpi_data['pending_bookings']) ? $kpi_data['pending_bookings'] : 0,
                'revenue_month' => isset($kpi_data['revenue_month']) ? $kpi_data['revenue_month'] : 0,
                'services_count' => $services_count,
                'revenue_today' => isset($kpi_data['revenue_today']) ? $kpi_data['revenue_today'] : 0,
                'bookings_today' => isset($kpi_data['bookings_today']) ? $kpi_data['bookings_today'] : 0,
                'confirmed_bookings' => isset($kpi_data['confirmed_bookings']) ? $kpi_data['confirmed_bookings'] : 0,
                'completed_bookings' => isset($kpi_data['completed_bookings']) ? $kpi_data['completed_bookings'] : 0,
            );

            wp_send_json_success($response_data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load dashboard data: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for recent bookings
add_action('wp_ajax_mobooking_get_recent_bookings', 'mobooking_ajax_get_recent_bookings');
if (!function_exists('mobooking_ajax_get_recent_bookings')) {
    function mobooking_ajax_get_recent_bookings() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }

        // Initialize bookings manager if not available
        if (!isset($GLOBALS['mobooking_bookings_manager'])) {
            try {
                $GLOBALS['mobooking_bookings_manager'] = new \MoBooking\Classes\Bookings();
            } catch (Exception $e) {
                wp_send_json_error(array('message' => 'Failed to initialize bookings manager: ' . $e->getMessage()), 500);
                return;
            }
        }

        $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;

        // Handle worker permissions
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        try {
            $recent_bookings = $bookings_manager->get_recent_bookings($data_user_id, $limit);
            wp_send_json_success(array('recent_bookings' => $recent_bookings));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load recent bookings: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for customer insights
add_action('wp_ajax_mobooking_get_customer_insights', 'mobooking_ajax_get_customer_insights');
if (!function_exists('mobooking_ajax_get_customer_insights')) {
    function mobooking_ajax_get_customer_insights() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }

        // Handle worker permissions
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        try {
            // Initialize customers manager if needed
            if (!class_exists('MoBooking\Classes\Customers')) {
                wp_send_json_error(array('message' => 'Customers class not available.'), 500);
                return;
            }

            $customers_manager = new \MoBooking\Classes\Customers();
            $insights_data = $customers_manager->get_customer_insights($data_user_id);

            wp_send_json_success($insights_data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load customer insights: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for top services
add_action('wp_ajax_mobooking_get_top_services', 'mobooking_ajax_get_top_services');
if (!function_exists('mobooking_ajax_get_top_services')) {
    function mobooking_ajax_get_top_services() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }

        // Handle worker permissions
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        try {
            // Initialize services manager if not available
            if (!isset($GLOBALS['mobooking_services_manager'])) {
                $GLOBALS['mobooking_services_manager'] = new \MoBooking\Classes\Services();
            }

            $services_manager = $GLOBALS['mobooking_services_manager'];
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
            
            $top_services = $services_manager->get_top_services($data_user_id, $limit);
            wp_send_json_success(array('top_services' => $top_services));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load top services: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for chart data
add_action('wp_ajax_mobooking_get_chart_data', 'mobooking_ajax_get_chart_data');
if (!function_exists('mobooking_ajax_get_chart_data')) {
    function mobooking_ajax_get_chart_data() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }

        // Handle worker permissions
        $data_user_id = $current_user_id;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
            $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
            if ($owner_id) {
                $data_user_id = $owner_id;
            }
        }

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';

        try {
            // Initialize bookings manager if not available
            if (!isset($GLOBALS['mobooking_bookings_manager'])) {
                $GLOBALS['mobooking_bookings_manager'] = new \MoBooking\Classes\Bookings();
            }

            $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
            $chart_data = $bookings_manager->get_chart_data($data_user_id, $period);

            wp_send_json_success($chart_data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load chart data: ' . $e->getMessage()), 500);
        }
    }
}

// Error logging function for debugging
if (!function_exists('mobooking_log_ajax_error')) {
    function mobooking_log_ajax_error($message, $context = array()) {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log('[MoBooking AJAX Error] ' . $message . ' Context: ' . print_r($context, true));
        }
    }
}

// Add debugging handler for admin-ajax.php
add_action('wp_ajax_mobooking_debug_ajax', 'mobooking_debug_ajax_handler');
if (!function_exists('mobooking_debug_ajax_handler')) {
    function mobooking_debug_ajax_handler() {
        $debug_info = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'current_user' => get_current_user_id(),
            'nonce_valid' => wp_verify_nonce($_POST['nonce'] ?? '', 'mobooking_dashboard_nonce'),
            'globals_available' => array(
                'services_manager' => isset($GLOBALS['mobooking_services_manager']),
                'bookings_manager' => isset($GLOBALS['mobooking_bookings_manager']),
            ),
            'classes_available' => array(
                'Services' => class_exists('MoBooking\Classes\Services'),
                'Bookings' => class_exists('MoBooking\Classes\Bookings'),
                'Customers' => class_exists('MoBooking\Classes\Customers'),
                'Auth' => class_exists('MoBooking\Classes\Auth'),
            ),
            'wp_debug' => WP_DEBUG,
            'wp_debug_log' => WP_DEBUG_LOG,
        );

        wp_send_json_success($debug_info);
    }
}

add_action('wp_ajax_mobooking_get_available_slots', 'mobooking_ajax_get_available_slots');
add_action('wp_ajax_nopriv_mobooking_get_available_slots', 'mobooking_ajax_get_available_slots');
function mobooking_ajax_get_available_slots() {
    $date = $_POST['date'];
    $day_of_week = date('w', strtotime($date));

    $availability = new MoBooking\Classes\Availability();
    $schedule = $availability->get_recurring_schedule($_POST['tenant_id']);

    $slots = [];
    foreach ($schedule as $day) {
        if ($day['day_of_week'] == $day_of_week && $day['is_enabled']) {
            foreach ($day['slots'] as $slot) {
                $slots[] = $slot;
            }
        }
    }

    wp_send_json_success($slots);
}

add_action('wp_ajax_mobooking_get_public_services', 'mobooking_ajax_get_public_services');
add_action('wp_ajax_nopriv_mobooking_get_public_services', 'mobooking_ajax_get_public_services');
function mobooking_ajax_get_public_services() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_booking_form_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
        return;
    }

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    if (!$tenant_id) {
        wp_send_json_error(array('message' => 'Tenant ID is required.'), 400);
        return;
    }

    if (!isset($GLOBALS['mobooking_services_manager'])) {
        wp_send_json_error(array('message' => 'Services component not available.'), 500);
        return;
    }

    $services_manager = $GLOBALS['mobooking_services_manager'];
    $services = $services_manager->get_services_by_tenant_id($tenant_id);

    if (is_wp_error($services)) {
        wp_send_json_error(array('message' => $services->get_error_message()), 500);
        return;
    }

    wp_send_json_success($services);
}

add_action('wp_ajax_mobooking_update_customer_details', 'mobooking_ajax_update_customer_details');
function mobooking_ajax_update_customer_details() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_customer_details_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
        return;
    }

    $customer_data = $_POST['customer_data'];
    $customer_id = intval($customer_data['customer_id']);

    if (!$customer_id) {
        wp_send_json_error(array('message' => 'Customer ID is required.'), 400);
        return;
    }

    $customers_manager = new \MoBooking\Classes\Customers();
    $result = $customers_manager->update_customer($customer_id, $customer_data);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()), 500);
        return;
    }

    wp_send_json_success(array('message' => 'Customer details updated successfully.'));
}
?>
