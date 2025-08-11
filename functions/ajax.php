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

    $formatted_slots = array_map(function($slot) {
        return [
            'time' => $slot['start_time'],
            'display' => date('g:i A', strtotime($slot['start_time']))
        ];
    }, $slots);

    wp_send_json_success($formatted_slots);
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

add_action('wp_ajax_mobooking_get_service_coverage_grouped', 'mobooking_ajax_get_service_coverage_grouped');
function mobooking_ajax_get_service_coverage_grouped() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
        return;
    }

    $areas_manager = new \MoBooking\Classes\Areas();
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    $result = $areas_manager->get_service_coverage_grouped($filters);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()), 500);
        return;
    }

    wp_send_json_success($result);
}

add_action('wp_ajax_mobooking_get_service_coverage', 'mobooking_ajax_get_service_coverage');
function mobooking_ajax_get_service_coverage() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
        return;
    }

    $areas_manager = new \MoBooking\Classes\Areas();
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : -1;
    $result = $areas_manager->get_service_coverage($city, $limit);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()), 500);
        return;
    }

    wp_send_json_success($result);
}
?>


<?php
/**
 * Enhanced Dashboard AJAX Handlers
 * Add these functions to your functions/ajax.php file
 * @package MoBooking
 */

// AJAX handler for dashboard statistics
add_action('wp_ajax_mobooking_get_dashboard_stats', 'mobooking_ajax_get_dashboard_stats');
if (!function_exists('mobooking_ajax_get_dashboard_stats')) {
    function mobooking_ajax_get_dashboard_stats() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Initialize managers
            $services_manager = new \MoBooking\Classes\Services();
            $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
            $notifications_manager = new \MoBooking\Classes\Notifications();
            $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
            
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            // Get fresh statistics
            $stats = $bookings_manager->get_booking_statistics($data_user_id);
            
            global $wpdb;
            $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
            
            // Today's revenue
            $today_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed') AND DATE(booking_date) = CURDATE()",
                $data_user_id
            ));
            
            // This week's bookings
            $week_bookings = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE user_id = %d AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)",
                $data_user_id
            ));
            
            // Completion rate
            $completed_bookings = $stats['by_status']['completed'] ?? 0;
            $total_bookings = $stats['total'];
            $completion_rate = ($total_bookings > 0) ? ($completed_bookings / $total_bookings) * 100 : 0;
            
            // Average booking value
            $avg_booking_value = ($total_bookings > 0) ? ($stats['total_revenue'] / $total_bookings) : 0;
            
            // Active services count
            $services_result = $services_manager->get_services_by_user($data_user_id, ['status' => 'active']);
            $active_services = $services_result['total_count'] ?? 0;
            
            // Get setup progress
            $setup_progress = $settings_manager->get_setup_progress($data_user_id);
            $setup_percentage = 0;
            if (!empty($setup_progress['total_count']) && $setup_progress['total_count'] > 0) {
                $setup_percentage = round(($setup_progress['completed_count'] / $setup_progress['total_count']) * 100);
            }
            
            wp_send_json_success(array(
                'total_revenue' => floatval($stats['total_revenue'] ?? 0),
                'total_bookings' => intval($stats['total'] ?? 0),
                'today_revenue' => floatval($today_revenue ?? 0),
                'completion_rate' => floatval($completion_rate),
                'week_bookings' => intval($week_bookings ?? 0),
                'avg_booking_value' => floatval($avg_booking_value),
                'active_services' => intval($active_services),
                'setup_percentage' => intval($setup_percentage)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load dashboard stats: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for recent bookings
add_action('wp_ajax_mobooking_get_recent_bookings', 'mobooking_ajax_get_recent_bookings');
if (!function_exists('mobooking_ajax_get_recent_bookings')) {
    function mobooking_ajax_get_recent_bookings() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Initialize managers
            $services_manager = new \MoBooking\Classes\Services();
            $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
            $notifications_manager = new \MoBooking\Classes\Notifications();
            $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
            
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;
            $recent_bookings = $bookings_manager->get_bookings_by_tenant($data_user_id, ['limit' => $limit]);
            
            wp_send_json_success($recent_bookings);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load recent bookings: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for recent activity
add_action('wp_ajax_mobooking_get_recent_activity', 'mobooking_ajax_get_recent_activity');
if (!function_exists('mobooking_ajax_get_recent_activity')) {
    function mobooking_ajax_get_recent_activity() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
            $activities = mobooking_get_recent_activity($data_user_id, $limit);
            
            wp_send_json_success($activities);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load recent activity: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for today's revenue (for real-time updates)
add_action('wp_ajax_mobooking_get_today_revenue', 'mobooking_ajax_get_today_revenue');
if (!function_exists('mobooking_ajax_get_today_revenue')) {
    function mobooking_ajax_get_today_revenue() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            global $wpdb;
            $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
            $today_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed') AND DATE(booking_date) = CURDATE()",
                $data_user_id
            ));
            
            wp_send_json_success(array(
                'today_revenue' => floatval($today_revenue ?? 0)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load today revenue: ' . $e->getMessage()), 500);
        }
    }
}

// Helper function to get recent activity (add this to your utility functions)
if (!function_exists('mobooking_get_recent_activity')) {
    function mobooking_get_recent_activity($user_id, $limit = 10) {
        global $wpdb;
        
        $activities = array();
        $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
        
        // Get recent bookings as activity
        $recent_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT id, customer_name, status, created_at, total_price 
             FROM $bookings_table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        foreach ($recent_bookings as $booking) {
            $icon = 'calendar-plus';
            $title = __('New booking received', 'mobooking');
            $description = sprintf(__('%s booked a service', 'mobooking'), $booking->customer_name);
            
            if ($booking->status === 'completed') {
                $icon = 'check-circle';
                $title = __('Booking completed', 'mobooking');
                $description = sprintf(__('Booking #%d completed', 'mobooking'), $booking->id);
            } elseif ($booking->status === 'cancelled') {
                $icon = 'x-circle';
                $title = __('Booking cancelled', 'mobooking');
                $description = sprintf(__('Booking #%d cancelled', 'mobooking'), $booking->id);
            }
            
            $activities[] = array(
                'icon' => $icon,
                'title' => $title,
                'description' => $description,
                'timestamp' => $booking->created_at,
                'type' => 'booking'
            );
        }
        
        // Sort by timestamp (newest first)
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($activities, 0, $limit);
    }
}

// Enhanced chart data handler (extends existing)
add_action('wp_ajax_mobooking_get_chart_data_enhanced', 'mobooking_ajax_get_chart_data_enhanced');
if (!function_exists('mobooking_ajax_get_chart_data_enhanced')) {
    function mobooking_ajax_get_chart_data_enhanced() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
            
            global $wpdb;
            $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
            
            $labels = array();
            $revenue_data = array();
            $bookings_data = array();
            
            switch ($period) {
                case 'week':
                    // Last 7 days
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $labels[] = date('M j', strtotime($date));
                        
                        $daily_revenue = $wpdb->get_var($wpdb->prepare(
                            "SELECT SUM(total_price) FROM $bookings_table 
                             WHERE user_id = %d AND status IN ('completed', 'confirmed') 
                             AND DATE(booking_date) = %s",
                            $data_user_id,
                            $date
                        ));
                        
                        $daily_bookings = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $bookings_table 
                             WHERE user_id = %d 
                             AND DATE(booking_date) = %s",
                            $data_user_id,
                            $date
                        ));
                        
                        $revenue_data[] = floatval($daily_revenue ?? 0);
                        $bookings_data[] = intval($daily_bookings ?? 0);
                    }
                    break;
                    
                case 'month':
                    // Last 30 days, grouped by week
                    for ($i = 3; $i >= 0; $i--) {
                        $start_date = date('Y-m-d', strtotime("-" . (($i + 1) * 7) . " days"));
                        $end_date = date('Y-m-d', strtotime("-" . ($i * 7) . " days"));
                        $labels[] = date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date));
                        
                        $weekly_revenue = $wpdb->get_var($wpdb->prepare(
                            "SELECT SUM(total_price) FROM $bookings_table 
                             WHERE user_id = %d AND status IN ('completed', 'confirmed') 
                             AND DATE(booking_date) >= %s AND DATE(booking_date) <= %s",
                            $data_user_id,
                            $start_date,
                            $end_date
                        ));
                        
                        $weekly_bookings = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $bookings_table 
                             WHERE user_id = %d 
                             AND DATE(booking_date) >= %s AND DATE(booking_date) <= %s",
                            $data_user_id,
                            $start_date,
                            $end_date
                        ));
                        
                        $revenue_data[] = floatval($weekly_revenue ?? 0);
                        $bookings_data[] = intval($weekly_bookings ?? 0);
                    }
                    break;
                    
                case 'year':
                    // Last 12 months
                    for ($i = 11; $i >= 0; $i--) {
                        $month_start = date('Y-m-01', strtotime("-$i months"));
                        $month_end = date('Y-m-t', strtotime("-$i months"));
                        $labels[] = date('M Y', strtotime($month_start));
                        
                        $monthly_revenue = $wpdb->get_var($wpdb->prepare(
                            "SELECT SUM(total_price) FROM $bookings_table 
                             WHERE user_id = %d AND status IN ('completed', 'confirmed') 
                             AND DATE(booking_date) >= %s AND DATE(booking_date) <= %s",
                            $data_user_id,
                            $month_start,
                            $month_end
                        ));
                        
                        $monthly_bookings = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $bookings_table 
                             WHERE user_id = %d 
                             AND DATE(booking_date) >= %s AND DATE(booking_date) <= %s",
                            $data_user_id,
                            $month_start,
                            $month_end
                        ));
                        
                        $revenue_data[] = floatval($monthly_revenue ?? 0);
                        $bookings_data[] = intval($monthly_bookings ?? 0);
                    }
                    break;
            }
            
            wp_send_json_success(array(
                'labels' => $labels,
                'revenue' => $revenue_data,
                'bookings' => $bookings_data,
                'period' => $period
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load chart data: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for setup progress updates
add_action('wp_ajax_mobooking_update_setup_step', 'mobooking_ajax_update_setup_step');
if (!function_exists('mobooking_ajax_update_setup_step')) {
    function mobooking_ajax_update_setup_step() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            $step_key = isset($_POST['step_key']) ? sanitize_text_field($_POST['step_key']) : '';
            $completed = isset($_POST['completed']) ? (bool) $_POST['completed'] : false;
            
            if (empty($step_key)) {
                wp_send_json_error(array('message' => 'Step key is required.'), 400);
                return;
            }
            
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            $settings_manager = new \MoBooking\Classes\Settings();
            $result = $settings_manager->update_setup_step($data_user_id, $step_key, $completed);
            
            if ($result) {
                $updated_progress = $settings_manager->get_setup_progress($data_user_id);
                wp_send_json_success($updated_progress);
            } else {
                wp_send_json_error(array('message' => 'Failed to update setup step.'), 500);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to update setup step: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for live dashboard updates (WebSocket alternative)
add_action('wp_ajax_mobooking_get_live_updates', 'mobooking_ajax_get_live_updates');
if (!function_exists('mobooking_ajax_get_live_updates')) {
    function mobooking_ajax_get_live_updates() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            $last_update = isset($_POST['last_update']) ? sanitize_text_field($_POST['last_update']) : '';
            
            // Get updates since last check
            $updates = mobooking_get_dashboard_updates($data_user_id, $last_update);
            
            wp_send_json_success($updates);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to get live updates: ' . $e->getMessage()), 500);
        }
    }
}

// Helper function to get dashboard updates
if (!function_exists('mobooking_get_dashboard_updates')) {
    function mobooking_get_dashboard_updates($user_id, $since = '') {
        global $wpdb;
        
        $updates = array(
            'timestamp' => current_time('mysql'),
            'has_updates' => false,
            'new_bookings' => 0,
            'status_changes' => 0,
            'notifications' => array()
        );
        
        if (empty($since)) {
            $since = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        }
        
        $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');
        
        // Check for new bookings
        $new_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE user_id = %d AND created_at > %s",
            $user_id,
            $since
        ));
        
        if ($new_bookings > 0) {
            $updates['has_updates'] = true;
            $updates['new_bookings'] = intval($new_bookings);
            $updates['notifications'][] = array(
                'type' => 'success',
                'title' => __('New Bookings', 'mobooking'),
                'message' => sprintf(__('%d new booking(s) received', 'mobooking'), $new_bookings),
                'timestamp' => current_time('mysql')
            );
        }
        
        // Check for status changes
        $status_changes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE user_id = %d AND updated_at > %s AND updated_at != created_at",
            $user_id,
            $since
        ));
        
        if ($status_changes > 0) {
            $updates['has_updates'] = true;
            $updates['status_changes'] = intval($status_changes);
        }
        
        return $updates;
    }
}

// AJAX handler for exporting dashboard data
add_action('wp_ajax_mobooking_export_dashboard_data', 'mobooking_ajax_export_dashboard_data');
if (!function_exists('mobooking_ajax_export_dashboard_data')) {
    function mobooking_ajax_export_dashboard_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'), 401);
            return;
        }
        
        try {
            // Determine user for data fetching (handle workers)
            $data_user_id = $current_user_id;
            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                if ($owner_id) {
                    $data_user_id = $owner_id;
                }
            }
            
            $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
            $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
            
            // Initialize managers
            $services_manager = new \MoBooking\Classes\Services();
            $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
            $notifications_manager = new \MoBooking\Classes\Notifications();
            $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
            
            // Get export data
            $export_data = mobooking_prepare_dashboard_export($data_user_id, $period, $bookings_manager);
            
            if ($format === 'json') {
                wp_send_json_success(array(
                    'data' => $export_data,
                    'filename' => 'mobooking-dashboard-' . date('Y-m-d') . '.json'
                ));
            } else {
                // CSV format
                $csv_data = mobooking_convert_to_csv($export_data);
                wp_send_json_success(array(
                    'csv' => $csv_data,
                    'filename' => 'mobooking-dashboard-' . date('Y-m-d') . '.csv'
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to export dashboard data: ' . $e->getMessage()), 500);
        }
    }
}

// Helper function to prepare dashboard export data
if (!function_exists('mobooking_prepare_dashboard_export')) {
    function mobooking_prepare_dashboard_export($user_id, $period, $bookings_manager) {
        $stats = $bookings_manager->get_booking_statistics($user_id);
        $bookings = $bookings_manager->get_bookings_by_tenant($user_id, array('limit' => 1000));
        
        return array(
            'summary' => array(
                'total_revenue' => $stats['total_revenue'] ?? 0,
                'total_bookings' => $stats['total'] ?? 0,
                'completion_rate' => $stats['by_status']['completed'] ?? 0,
                'export_date' => current_time('mysql'),
                'period' => $period
            ),
            'bookings' => $bookings['bookings'] ?? array(),
            'stats_by_status' => $stats['by_status'] ?? array()
        );
    }
}

// Helper function to convert data to CSV
if (!function_exists('mobooking_convert_to_csv')) {
    function mobooking_convert_to_csv($data) {
        $csv_lines = array();
        
        // Add summary header
        $csv_lines[] = 'Dashboard Summary';
        $csv_lines[] = 'Total Revenue,' . ($data['summary']['total_revenue'] ?? 0);
        $csv_lines[] = 'Total Bookings,' . ($data['summary']['total_bookings'] ?? 0);
        $csv_lines[] = 'Completion Rate,' . ($data['summary']['completion_rate'] ?? 0);
        $csv_lines[] = 'Export Date,' . ($data['summary']['export_date'] ?? '');
        $csv_lines[] = '';
        
        // Add bookings header
        $csv_lines[] = 'Recent Bookings';
        $csv_lines[] = 'ID,Customer Name,Email,Service,Date,Status,Price';
        
        // Add booking data
        foreach ($data['bookings'] as $booking) {
            $csv_lines[] = implode(',', array(
                $booking['id'] ?? '',
                '"' . str_replace('"', '""', $booking['customer_name'] ?? '') . '"',
                '"' . str_replace('"', '""', $booking['customer_email'] ?? '') . '"',
                '"' . str_replace('"', '""', $booking['service_name'] ?? '') . '"',
                $booking['booking_date'] ?? '',
                $booking['status'] ?? '',
                $booking['total_price'] ?? 0
            ));
        }
        
        return implode("\n", $csv_lines);
    }
}

// AJAX handler for dashboard live search
add_action('wp_ajax_mobooking_dashboard_live_search', 'mobooking_ajax_dashboard_live_search');
if (!function_exists('mobooking_ajax_dashboard_live_search')) {
    function mobooking_ajax_dashboard_live_search() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking_dashboard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed: Invalid nonce.'), 403);
            return;
        }

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        if (strlen($query) < 2) {
            wp_send_json_success(array('results' => []));
            return;
        }

        $results = [];
        $tenant_id = \MoBooking\Classes\Auth::get_effective_tenant_id_for_user(get_current_user_id());

        // Search Customers
        $customers_manager = new \MoBooking\Classes\Customers();
        $customers = $customers_manager->get_customers_by_tenant_id($tenant_id, ['search' => $query, 'per_page' => 5]);
        foreach ($customers as $customer) {
            $results[] = [
                'title' => $customer->full_name,
                'url' => home_url('/dashboard/customer-details/?customer_id=' . $customer->id),
                'type' => 'Customer'
            ];
        }

        // Search Bookings
        $bookings_manager = new \MoBooking\Classes\Bookings(new \MoBooking\Classes\Discounts($tenant_id), new \MoBooking\Classes\Notifications(), new \MoBooking\Classes\Services());
        $bookings = $bookings_manager->get_bookings_by_tenant($tenant_id, ['search' => $query, 'limit' => 5]);
        foreach ($bookings['bookings'] as $booking) {
            $results[] = [
                'title' => 'Booking #' . $booking['booking_reference'],
                'url' => home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']),
                'type' => 'Booking'
            ];
        }

        wp_send_json_success(array('results' => $results));
    }
}