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
    if ($current_page_slug === 'services') {
        wp_enqueue_style('mobooking-dashboard-services-enhanced', MOBOOKING_THEME_URI . 'assets/css/dashboard-services-enhanced.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-dashboard-services', MOBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery', 'jquery-ui-sortable', 'mobooking-dialog'), MOBOOKING_VERSION, true);

        $services_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_services' => __('Loading services...', 'mobooking'),
                'no_services_found' => __('No services found.', 'mobooking'),
                'error_loading_services' => __('Error loading services.', 'mobooking'),
                'service_deleted' => __('Service deleted.', 'mobooking'),
                'error_deleting_service_ajax' => __('AJAX error deleting service.', 'mobooking'),
                'active' => __('Active', 'mobooking'),
                'inactive' => __('Inactive', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this service?', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-services', 'mobooking_services_params', $services_params);
    }

    // Specific to Service Edit page
    if ($current_page_slug === 'service-edit') {
        wp_enqueue_style('mobooking-dashboard-service-edit', MOBOOKING_THEME_URI . 'assets/css/dashboard-service-edit.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-service-edit', MOBOOKING_THEME_URI . 'assets/js/dashboard-service-edit.js', array('jquery'), MOBOOKING_VERSION, true);

        $service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
        $option_count = 0;
        if ($service_id > 0) {
            $services_manager = new \MoBooking\Classes\Services();
            $service_data = $services_manager->get_service($service_id, get_current_user_id());
            if ($service_data && !is_wp_error($service_data) && !empty($service_data['options'])) {
                $option_count = count($service_data['options']);
            }
        }

        wp_localize_script('mobooking-service-edit', 'mobooking_service_edit_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking_services_nonce'),
            'option_count' => $option_count,
            'redirect_url' => home_url('/dashboard/services'),
            'i18n' => [
                'saving' => __('Saving...', 'mobooking'),
                'service_saved' => __('Service saved successfully.', 'mobooking'),
                'error_saving_service' => __('Error saving service. Please check your input and try again.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred. Please try again.', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this service? This action cannot be undone.', 'mobooking'),
                'confirm_delete_option' => __('Are you sure you want to delete this option?', 'mobooking'),
                'service_deleted' => __('Service deleted successfully', 'mobooking'),
                'error_deleting_service' => __('Failed to delete service', 'mobooking'),
                'service_duplicated' => __('Service duplicated successfully', 'mobooking'),
                'error_duplicating_service' => __('Failed to duplicate service', 'mobooking'),
                'error_uploading_image' => __('Failed to upload image', 'mobooking'),
            ]
        ]);
    }

    // Specific to Bookings page
    if ($current_page_slug === 'bookings') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('mobooking-dashboard-bookings', MOBOOKING_THEME_URI . 'assets/js/dashboard-bookings.js', array('jquery', 'jquery-ui-datepicker', 'mobooking-dialog'), MOBOOKING_VERSION, true);
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
        wp_enqueue_style('mobooking-dashboard-discounts', MOBOOKING_THEME_URI . 'assets/css/dashboard-discounts.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-dashboard-discounts', MOBOOKING_THEME_URI . 'assets/js/dashboard-discounts.js', array('jquery', 'mobooking-dialog'), MOBOOKING_VERSION, true);
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
            ],
            'types' => [
                'percentage' => __('Percentage', 'mobooking'),
                'fixed_amount' => __('Fixed Amount', 'mobooking'),
            ],
            'statuses' => [
                'active' => __('Active', 'mobooking'),
                'inactive' => __('Inactive', 'mobooking'),
            ],
        ]);
        wp_localize_script('mobooking-dashboard-discounts', 'mobooking_discounts_params', $discounts_params);
    }

    // Specific to Areas page
    if ($current_page_slug === 'areas') {
        wp_enqueue_style('mobooking-enhanced-areas', MOBOOKING_THEME_URI . 'assets/css/enhanced-areas.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-enhanced-areas', MOBOOKING_THEME_URI . 'assets/js/enhanced-areas.js', array('jquery', 'wp-i18n', 'mobooking-dialog'), MOBOOKING_VERSION, true);
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
        $areas_params['nonce'] = wp_create_nonce('mobooking_areas_nonce');
        wp_localize_script('mobooking-enhanced-areas', 'mobooking_areas_params', $areas_params);
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
        $version = defined('MOBOOKING_VERSION') ? MOBOOKING_VERSION : '1.0.0';

        // Enqueue Feather Icons first (highest priority)
        wp_enqueue_script(
            'feather-icons',
            'https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js',
            array(),
            '4.29.0',
            true
        );

        // Enqueue Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );

        // Enqueue enhanced dashboard CSS
        wp_enqueue_style(
            'mobooking-dashboard-enhanced-css',
            MOBOOKING_THEME_URI . 'assets/css/dashboard-overview-enhanced.css',
            array(),
            $version
        );

        // Enqueue enhanced dashboard JavaScript (depends on feather and chart)
        wp_enqueue_script(
            'mobooking-dashboard-enhanced-js',
            MOBOOKING_THEME_URI . 'assets/js/dashboard-overview-enhanced.js',
            array('jquery', 'feather-icons', 'chart-js'),
            $version,
            true
        );

        // Localize script with dashboard data
	    $current_user_id = get_current_user_id();
	    $data_user_id = $current_user_id;
	    $is_worker = false;

	    // Handle workers
	    if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
	        $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
	        if ($owner_id) {
	            $data_user_id = $owner_id;
	            $is_worker = true;
	        }
	    }

	    // Get managers and data
	    $services_manager = new \MoBooking\Classes\Services();
	    $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
	    $notifications_manager = new \MoBooking\Classes\Notifications();
	    $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
	    $settings_manager = new \MoBooking\Classes\Settings();

	    // Get statistics
	    $stats = $bookings_manager->get_booking_statistics($data_user_id);

	    // Calculate metrics
	    global $wpdb;
	    $bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');

	    $today_revenue = $wpdb->get_var($wpdb->prepare(
	        "SELECT SUM(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed') AND DATE(booking_date) = CURDATE()",
	        $data_user_id
	    ));

	    $week_bookings = $wpdb->get_var($wpdb->prepare(
	        "SELECT COUNT(*) FROM $bookings_table WHERE user_id = %d AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)",
	        $data_user_id
	    ));

	    $completed_bookings = $stats['by_status']['completed'] ?? 0;
	    $total_bookings = $stats['total'];
	    $completion_rate = ($total_bookings > 0) ? ($completed_bookings / $total_bookings) * 100 : 0;
	    $avg_booking_value = ($total_bookings > 0) ? ($stats['total_revenue'] / $total_bookings) : 0;
	    // Get setup progress
	    $setup_progress = $settings_manager->get_setup_progress($data_user_id);
	    $setup_percentage = 0;
	    if (!empty($setup_progress['total_count']) && $setup_progress['total_count'] > 0) {
	        $setup_percentage = round(($setup_progress['completed_count'] / $setup_progress['total_count']) * 100);
	    }

	    // Get services count
	    $services_result = $services_manager->get_services_by_user($data_user_id, ['status' => 'active']);
	    $active_services = $services_result['total_count'] ?? 0;

	    // Get currency symbol
	    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));

	    wp_localize_script('mobooking-dashboard-enhanced-js', 'mobooking_overview_params', array(
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
	        'currency_symbol' => $currency_symbol,
	        'is_worker' => $is_worker,
	        'dashboard_base_url' => home_url('/dashboard/'),
	        'user_id' => $data_user_id,
	        'current_time' => current_time('mysql'),
	        'stats' => array(
	            'total_revenue' => floatval($stats['total_revenue'] ?? 0),
	            'total_bookings' => intval($stats['total'] ?? 0),
	            'today_revenue' => floatval($today_revenue ?? 0),
	            'completion_rate' => floatval($completion_rate),
	            'week_bookings' => intval($week_bookings ?? 0),
	            'avg_booking_value' => floatval($avg_booking_value),
	            'active_services' => intval($active_services),
	            'setup_percentage' => intval($setup_percentage)
	        ),
	        'i18n' => array(
	            'time_ago_just_now' => __('Just now', 'mobooking'),
	            'time_ago_seconds_suffix' => __('s ago', 'mobooking'),
	            'time_ago_minutes_suffix' => __('m ago', 'mobooking'),
	            'time_ago_hours_suffix' => __('h ago', 'mobooking'),
	            'time_ago_days_suffix' => __('d ago', 'mobooking'),
	            'loading' => __('Loading...', 'mobooking'),
	            'no_data' => __('No data available', 'mobooking'),
	            'error' => __('Error loading data', 'mobooking'),
	            'copied' => __('Copied!', 'mobooking'),
	            'new_booking' => __('New booking received', 'mobooking'),
	            'booking_updated' => __('Booking status updated', 'mobooking'),
	            'service_created' => __('New service created', 'mobooking'),
	            'worker_added' => __('New worker added', 'mobooking'),
	            'settings_updated' => __('Settings updated', 'mobooking'),
	            'refresh_success' => __('Data refreshed successfully', 'mobooking'),
	            'refresh_error' => __('Failed to refresh data', 'mobooking'),
	            'export_success' => __('Data exported successfully', 'mobooking'),
	            'export_error' => __('Failed to export data', 'mobooking'),
	            'network_error' => __('Network connection error', 'mobooking'),
	            'unauthorized' => __('You are not authorized to perform this action', 'mobooking')
	        )
	    ));
    }

    // Specific to Availability page
    if ($current_page_slug === 'availability') {
        wp_enqueue_style('mobooking-dashboard-availability', MOBOOKING_THEME_URI . 'assets/css/dashboard-availability.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('jquery-ui-datepicker'); // For calendar
        wp_enqueue_script('mobooking-dashboard-availability', MOBOOKING_THEME_URI . 'assets/js/dashboard-availability.js', array('jquery', 'jquery-ui-datepicker', 'mobooking-dialog'), MOBOOKING_VERSION, true);

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

        $plus_icon_path = MOBOOKING_THEME_DIR . 'assets/svg-icons/plus.svg';
        $trash_icon_path = MOBOOKING_THEME_DIR . 'assets/svg-icons/trash.svg';
        $copy_icon_path = MOBOOKING_THEME_DIR . 'assets/svg-icons/copy.svg';

        if (file_exists($plus_icon_path)) {
            $availability_params['icons']['plus'] = file_get_contents($plus_icon_path);
        }

        if (file_exists($trash_icon_path)) {
            $availability_params['icons']['trash'] = file_get_contents($trash_icon_path);
        }

        if (file_exists($copy_icon_path)) {
            $availability_params['icons']['copy'] = file_get_contents($copy_icon_path);
        }

        wp_localize_script('mobooking-dashboard-availability', 'mobooking_availability_params', $availability_params);
    }

    // Specific to Workers page (if exists)
    if ($current_page_slug === 'workers') {
        wp_enqueue_style('mobooking-workers-enhanced', MOBOOKING_THEME_URI . 'assets/css/dashboard-workers-enhanced.css', [], MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-dashboard-workers', MOBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array('jquery', 'mobooking-dialog'), MOBOOKING_VERSION, true);
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
