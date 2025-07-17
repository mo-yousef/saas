<?php
// Routing and Template Handling Refactored to BookingFormRouter class

// Initialize the new router
if (class_exists('MoBooking\\Classes\\Routes\\BookingFormRouter')) {
    new \MoBooking\Classes\Routes\BookingFormRouter();
}

// Theme activation/deactivation hook for flushing rewrite rules
function mobooking_flush_rewrite_rules_on_activation_deactivation() {
    // The BookingFormRouter hooks its rule registration to 'init'.
    // WordPress calls 'init' before 'flush_rewrite_rules' during theme activation.
    // So, just calling flush_rewrite_rules() here is sufficient.
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation');
add_action('switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation'); // Flushes on deactivation too

// Function to handle script enqueuing (was mobooking_enqueue_dashboard_scripts)
// No changes needed to its definition, only to its invocation if necessary
function mobooking_enqueue_dashboard_scripts($current_page_slug = '') {
    // FIXED: Handle missing parameter by getting it from various sources
    if (empty($current_page_slug)) {
        // Try to get from query vars first (set by router)
        $current_page_slug = get_query_var('mobooking_dashboard_page');

        // Fallback to global variable
        if (empty($current_page_slug)) {
            $current_page_slug = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : '';
        }

        // Final fallback to 'overview'
        if (empty($current_page_slug)) {
            $current_page_slug = 'overview';
        }
    }

    // Ensure jQuery and Dashicons are always available for dashboard pages
    wp_enqueue_script('jquery');
    wp_enqueue_style('dashicons');

    $user_id = get_current_user_id();
    $currency_code = 'USD'; // Default
    $currency_symbol = '$';
    $currency_pos = 'before';
    $currency_decimals = 2;
    $currency_decimal_sep = '.';
    $currency_thousand_sep = ',';

    if (isset($GLOBALS['mobooking_settings_manager']) && $user_id) {
        $settings_manager = $GLOBALS['mobooking_settings_manager'];
        $biz_settings = $settings_manager->get_business_settings($user_id);

        $currency_code = $biz_settings['biz_currency_code'];
        $currency_symbol = $biz_settings['biz_currency_symbol'];
        $currency_pos = $biz_settings['biz_currency_position'];
        // In a future update, these could also come from settings:
        // $currency_decimals = $biz_settings['biz_currency_decimals'];
        // $currency_decimal_sep = $biz_settings['biz_currency_decimal_sep'];
        // $currency_thousand_sep = $biz_settings['biz_currency_thousand_sep'];
    }

    // General dashboard params

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
    $dashboard_params = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_dashboard_nonce'), // General dashboard nonce
        'availability_nonce' => wp_create_nonce('mobooking_availability_nonce'),
        'services_nonce' => wp_create_nonce('mobooking_services_nonce'),
        // Add other specific nonces here if they are widely used or needed for a base script
        'currency_code' => $currency_code,
        'currency_symbol' => $currency_symbol,
        'currency_position' => $currency_pos,
        'currency_decimals' => $currency_decimals,
        'currency_decimal_sep' => $currency_decimal_sep,
        'currency_thousand_sep' => $currency_thousand_sep,
        'site_url' => site_url(),
        'dashboard_slug' => 'dashboard', // Consistent dashboard slug
        'currentUserCanDeleteBookings' => (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS) || !\MoBooking\Classes\Auth::is_user_worker(get_current_user_id())),
    ];

    // Specific to Services page
    if ($current_page_slug === 'services' || $current_page_slug === 'service-edit') {
        wp_enqueue_script('mobooking-dashboard-services', MOBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery', 'jquery-ui-sortable'), MOBOOKING_VERSION, true);
        // Ensure jQuery UI Sortable CSS is also enqueued if needed, or handle styling in plugin's CSS
        // wp_enqueue_style('jquery-ui-sortable-css', MOBOOKING_THEME_URI . 'path/to/jquery-ui-sortable.css');

        if ($current_page_slug === 'service-edit') {
            wp_enqueue_style('mobooking-dashboard-service-edit', MOBOOKING_THEME_URI . 'assets/css/dashboard-service-edit.css', array(), MOBOOKING_VERSION);
        }

        $services_params = array_merge($dashboard_params, [
            // 'nonce' is already general, if services needs specific, it's services_nonce from dashboard_params
            // 'currency_code', 'currency_symbol', etc. are already in dashboard_params
            'i18n' => [
                'loading_details' => __('Loading details...', 'mobooking'),
                'error_fetching_service_details' => __('Error: Could not fetch service details.', 'mobooking'),
                'name_required' => __('Service name is required.', 'mobooking'),
                'valid_price_required' => __('A valid, non-negative price is required.', 'mobooking'),
                'valid_duration_required' => __('A valid, positive duration in minutes is required.', 'mobooking'),
                'saving' => __('Saving...', 'mobooking'),
                'service_saved' => __('Service saved successfully.', 'mobooking'),
                'error_saving_service' => __('Error saving service. Please check your input and try again.', 'mobooking'),
                'error_saving_service_ajax' => __('AJAX error saving service. Check console.', 'mobooking'),
                'invalid_json_for_option' => __('Invalid JSON in Option Values for: ', 'mobooking'),
                'loading_services' => __('Loading services...', 'mobooking'),
                'no_services_found' => __('No services found.', 'mobooking'),
                'error_loading_services' => __('Error loading services.', 'mobooking'),
                'error_loading_services_ajax' => __('AJAX error loading services.', 'mobooking'),
                'service_deleted' => __('Service deleted.', 'mobooking'),
                'error_deleting_service_ajax' => __('AJAX error deleting service.', 'mobooking'),
                'fill_service_details_first' => __('Please fill in the following service details before adding options: %s.', 'mobooking'),
                'service_name_label' => __('Service Name', 'mobooking'),
                'service_price_label' => __('Price', 'mobooking'),
                'service_duration_label' => __('Duration', 'mobooking'),
                'active' => __('Active', 'mobooking'),
                'inactive' => __('Inactive', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this service?', 'mobooking'),
                'confirm_delete_option' => __('Are you sure you want to delete this option?', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-services', 'mobooking_services_params', $services_params);
    }

    // Specific to Bookings page
    if ($current_page_slug === 'bookings') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('mobooking-dashboard-bookings', MOBOOKING_THEME_URI . 'assets/js/dashboard-bookings.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);
        $bookings_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_bookings' => __('Loading bookings...', 'mobooking'),
                'no_bookings_found' => __('No bookings found.', 'mobooking'),
                'error_loading_bookings' => __('Error loading bookings.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this booking?', 'mobooking'),
                'booking_deleted' => __('Booking deleted.', 'mobooking'),
                'error_deleting_booking' => __('Error deleting booking.', 'mobooking'),
                'booking_updated' => __('Booking updated.', 'mobooking'),
                'error_updating_booking' => __('Error updating booking.', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-bookings', 'mobooking_bookings_params', $bookings_params);
    }

    // Specific to Discounts page
    if ($current_page_slug === 'discounts') {
        wp_enqueue_script('mobooking-dashboard-discounts', MOBOOKING_THEME_URI . 'assets/js/dashboard-discounts.js', array('jquery'), MOBOOKING_VERSION, true);
        $discounts_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_discounts' => __('Loading discounts...', 'mobooking'),
                'no_discounts_found' => __('No discounts found.', 'mobooking'),
                'error_loading_discounts' => __('Error loading discounts.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this discount?', 'mobooking'),
                'discount_deleted' => __('Discount deleted.', 'mobooking'),
                'error_deleting_discount' => __('Error deleting discount.', 'mobooking'),
                'discount_saved' => __('Discount saved.', 'mobooking'),
                'error_saving_discount' => __('Error saving discount.', 'mobooking'),
                'saving' => __('Saving...', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-discounts', 'mobooking_discounts_params', $discounts_params);
    }

    // Specific to Areas page
    if ($current_page_slug === 'areas') {
        wp_enqueue_script('mobooking-dashboard-areas', MOBOOKING_THEME_URI . 'assets/js/dashboard-areas.js', array('jquery'), MOBOOKING_VERSION, true);
        $areas_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_areas' => __('Loading areas...', 'mobooking'),
                'no_areas_found' => __('No areas found.', 'mobooking'),
                'error_loading_areas' => __('Error loading areas.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this area?', 'mobooking'),
                'area_deleted' => __('Area deleted.', 'mobooking'),
                'error_deleting_area' => __('Error deleting area.', 'mobooking'),
                'area_saved' => __('Area saved.', 'mobooking'),
                'error_saving_area' => __('Error saving area.', 'mobooking'),
                'saving' => __('Saving...', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-areas', 'mobooking_areas_params', $areas_params);
    }

    // Specific to Booking Form Settings page
    if ($current_page_slug === 'booking-form') {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script(
            'mobooking-dashboard-booking-form-settings',
            MOBOOKING_THEME_URI . 'assets/js/dashboard-booking-form-settings.js',
            array('jquery', 'wp-color-picker'),
            MOBOOKING_VERSION,
            true
        );
        $bf_settings_params = array_merge($dashboard_params, [
            'i18n' => [
                'saving' => __('Saving...', 'mobooking'),
                'save_success' => __('Booking form settings saved successfully.', 'mobooking'),
                'error_saving' => __('Error saving settings.', 'mobooking'),
                'error_loading' => __('Error loading settings.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'invalid_json' => __('Invalid JSON format in Business Hours.', 'mobooking'),
                'copied' => __('Copied!', 'mobooking'),
                'copy_failed' => __('Copy failed. Please try manually.', 'mobooking'),
                'booking_form_title' => __('Booking Form', 'mobooking'),
                'link_will_appear_here' => __('Link will appear here once slug is saved.', 'mobooking'),
                'embed_will_appear_here' => __('Embed code will appear here once slug is saved.', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-booking-form-settings', 'mobooking_bf_settings_params', $bf_settings_params);
    }

    // Specific to Business Settings page
    if ($current_page_slug === 'settings') {
        wp_enqueue_script(
            'mobooking-dashboard-business-settings',
            MOBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js',
            array('jquery'),
            MOBOOKING_VERSION,
            true
        );
        $biz_settings_params = array_merge($dashboard_params, [
            'i18n' => [
                'saving' => __('Saving...', 'mobooking'),
                'save_success' => __('Business settings saved successfully.', 'mobooking'),
                'error_saving' => __('Error saving business settings.', 'mobooking'),
                'error_loading' => __('Error loading business settings.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'invalid_json' => __('Business Hours JSON is not valid.', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-business-settings', 'mobooking_biz_settings_params', $biz_settings_params);
    }

    // Specific to Overview page (Dashboard)
    if ($current_page_slug === 'overview') {
        // Enqueue FullCalendar (using CDN links for v5)
        wp_enqueue_style('fullcalendar-main-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css', array(), '5.11.3');
        wp_enqueue_script('fullcalendar-main-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', array('jquery'), '5.11.3', true);
        // Note: Chart.js enqueue removed as it's no longer used on this page.

        // Enqueue specific dashboard CSS. Assuming dashboard-areas.css and dashboard-bookings-responsive.css are relevant.
        wp_enqueue_style('mobooking-dashboard-areas', MOBOOKING_THEME_URI . 'assets/css/dashboard-areas.css', array('mobooking-style'), MOBOOKING_VERSION);
        wp_enqueue_style('mobooking-dashboard-bookings-responsive', MOBOOKING_THEME_URI . 'assets/css/dashboard-bookings-responsive.css', array('mobooking-style'), MOBOOKING_VERSION);

        wp_enqueue_script('mobooking-dashboard-overview', MOBOOKING_THEME_URI . 'assets/js/dashboard-overview.js', array('jquery', 'fullcalendar-main-js'), MOBOOKING_VERSION, true);

        wp_enqueue_style(
            'mobooking-overview-css',
            get_template_directory_uri() . '/assets/css/dashboard-overview.css',
            array(),
            MOBOOKING_VERSION
        );

        $current_user_id_for_scripts = get_current_user_id();
        $is_worker_status = false;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id_for_scripts)) {
            $is_worker_status = true;
        }

        $overview_params = array_merge($dashboard_params, [
            'is_worker' => $is_worker_status,
            // dashboard_nonce is already in $dashboard_params and will be part of mobooking_overview_params
            // currency_symbol is already in $dashboard_params
            'i18n' => [
                'loading_data' => __('Loading dashboard data...', 'mobooking'),
                'error_loading_data' => __('Error loading overview data.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'time_ago_just_now' => __('Just now', 'mobooking'),
                'time_ago_seconds_suffix' => __('s ago', 'mobooking'),
                'time_ago_minutes_suffix' => __('m ago', 'mobooking'),
                'time_ago_hours_suffix' => __('h ago', 'mobooking'),
                'time_ago_days_suffix' => __('d ago', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-overview', 'mobooking_overview_params', $overview_params);
    }

    // Specific to Availability page
    if ($current_page_slug === 'availability') {
        wp_enqueue_style('mobooking-dashboard-availability', MOBOOKING_THEME_URI . 'assets/css/dashboard-availability.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('jquery-ui-datepicker'); // For calendar
        wp_enqueue_script('mobooking-dashboard-availability', MOBOOKING_THEME_URI . 'assets/js/dashboard-availability.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);

        $availability_i18n_strings = [
            'sunday' => __('Sunday', 'mobooking'),
            'monday' => __('Monday', 'mobooking'),
            'tuesday' => __('Tuesday', 'mobooking'),
            'wednesday' => __('Wednesday', 'mobooking'),
            'thursday' => __('Thursday', 'mobooking'),
            'friday' => __('Friday', 'mobooking'),
            'saturday' => __('Saturday', 'mobooking'),
            'edit' => __('Edit', 'mobooking'),
            'delete' => __('Delete', 'mobooking'),
            'active' => __('Active', 'mobooking'),
            'inactive' => __('Inactive', 'mobooking'),
            'unavailable_all_day' => __("Unavailable all day", 'mobooking'),
            'no_recurring_slots' => __("No recurring slots defined yet.", 'mobooking'),
            'add_recurring_slot' => __("Add Recurring Slot", 'mobooking'),
            'edit_recurring_slot' => __("Edit Recurring Slot", 'mobooking'),
            'error_loading_recurring_schedule' => __("Error loading recurring schedule.", 'mobooking'),
            'error_loading_recurring_schedule_retry' => __("Could not load schedule. Please try again.", 'mobooking'),
            'error_ajax' => __("An AJAX error occurred. Please try again.", 'mobooking'),
            'start_end_time_required' => __("Start and End time are required.", 'mobooking'),
            'start_time_before_end' => __("Start time must be before end time.", 'mobooking'),
            'error_saving_slot' => __("Error saving slot.", 'mobooking'),
            'error_slot_not_found' => __("Slot not found for editing.", 'mobooking'),
            'error_general' => __("An error occurred.", 'mobooking'),
            'confirm_delete_slot' => __("Are you sure you want to delete this recurring slot?", 'mobooking'),
            'error_deleting_slot' => __("Error deleting slot.", 'mobooking'),
        ];

        $availability_params = array_merge($dashboard_params, [
            'i18n' => $availability_i18n_strings
        ]);
        wp_localize_script('mobooking-dashboard-availability', 'mobooking_availability_params', $availability_params);
    }

    // Specific to Workers page (if exists)
    if ($current_page_slug === 'workers') {
        wp_enqueue_style('mobooking-workers-enhanced', MOBOOKING_THEME_URI . 'assets/css/dashboard-workers-enhanced.css', [], MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-dashboard-workers', MOBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array('jquery'), MOBOOKING_VERSION, true);
        $workers_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_workers' => __('Loading workers...', 'mobooking'),
                'no_workers_found' => __('No workers found.', 'mobooking'),
                'error_loading_workers' => __('Error loading workers.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this worker?', 'mobooking'),
                'worker_deleted' => __('Worker deleted.', 'mobooking'),
                'error_deleting_worker' => __('Error deleting worker.', 'mobooking'),
                'worker_saved' => __('Worker saved.', 'mobooking'),
                'error_saving_worker' => __('Error saving worker.', 'mobooking'),
                'saving' => __('Saving...', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-workers', 'mobooking_workers_params', $workers_params);
    }

    // Specific to Customers page
    if ($current_page_slug === 'customers' || $current_page_slug === 'customer-details') {
        wp_enqueue_style('mobooking-dashboard-customer-details', MOBOOKING_THEME_URI . 'assets/css/dashboard-customer-details.css', array(), MOBOOKING_VERSION);
    }

    // Global dashboard script (always load for all dashboard pages)
    wp_enqueue_script('mobooking-dashboard-global', MOBOOKING_THEME_URI . 'assets/js/dashboard.js', array('jquery'), MOBOOKING_VERSION, true);
    wp_localize_script('mobooking-dashboard-global', 'mobooking_dashboard_params', $dashboard_params);
}
?>
