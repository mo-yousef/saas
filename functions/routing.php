<?php
// Routing and Template Handling Refactored to BookingFormRouter class

// Initialize the new router
if (class_exists('NORDBOOKING\\Classes\\Routes\\BookingFormRouter')) {
    new \NORDBOOKING\Classes\Routes\BookingFormRouter();
}

// Theme activation/deactivation hook for flushing rewrite rules
function nordbooking_flush_rewrite_rules_on_activation_deactivation() {
    // The BookingFormRouter hooks its rule registration to 'init'.
    // WordPress calls 'init' before 'flush_rewrite_rules' during theme activation.
    // So, just calling flush_rewrite_rules() here is sufficient.
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'nordbooking_flush_rewrite_rules_on_activation_deactivation');
add_action('switch_theme', 'nordbooking_flush_rewrite_rules_on_activation_deactivation'); // Flushes on deactivation too

// Function to handle script enqueuing (was nordbooking_enqueue_dashboard_scripts)
// No changes needed to its definition, only to its invocation if necessary
function nordbooking_enqueue_dashboard_scripts($current_page_slug = '') {
    error_log('[NORDBOOKING Debug] Enqueueing dashboard scripts. Page slug passed: ' . $current_page_slug);

    // FIXED: Handle missing parameter by getting it from various sources
    if (empty($current_page_slug)) {
        // Try to get from query vars first (set by router)
        $current_page_slug = get_query_var('nordbooking_dashboard_page');
        error_log('[NORDBOOKING Debug] Slug from query var: ' . $current_page_slug);


        // Fallback to global variable
        if (empty($current_page_slug)) {
            $current_page_slug = isset($GLOBALS['nordbooking_current_dashboard_view']) ? $GLOBALS['nordbooking_current_dashboard_view'] : '';
            error_log('[NORDBOOKING Debug] Slug from global var: ' . $current_page_slug);
        }

        // Final fallback to 'overview'
        if (empty($current_page_slug)) {
            $current_page_slug = 'overview';
            error_log('[NORDBOOKING Debug] Slug fell back to overview.');
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

    if (isset($GLOBALS['nordbooking_settings_manager']) && $user_id) {
        $settings_manager = $GLOBALS['nordbooking_settings_manager'];
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
        'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'), // General dashboard nonce
        'availability_nonce' => wp_create_nonce('nordbooking_availability_nonce'),
        'services_nonce' => wp_create_nonce('nordbooking_services_nonce'),
        // Add other specific nonces here if they are widely used or needed for a base script
        'currency_code' => $currency_code,
        'currency_symbol' => $currency_symbol,
        'currency_position' => $currency_pos,
        'currency_decimals' => $currency_decimals,
        'currency_decimal_sep' => $currency_decimal_sep,
        'currency_thousand_sep' => $currency_thousand_sep,
        'site_url' => site_url(),
        'dashboard_slug' => 'dashboard', // Consistent dashboard slug
        'currentUserCanDeleteBookings' => (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKINGS) || !\NORDBOOKING\Classes\Auth::is_user_worker(get_current_user_id())),
    ];

    // Specific to Services page
    if ($current_page_slug === 'services') {
        wp_enqueue_style('nordbooking-dashboard-services-enhanced', NORDBOOKING_THEME_URI . 'assets/css/dashboard-services-enhanced.css', array(), NORDBOOKING_VERSION);
        wp_enqueue_script('nordbooking-dashboard-services', NORDBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery', 'jquery-ui-sortable', 'nordbooking-dialog'), NORDBOOKING_VERSION, true);

        $services_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_services' => __('Loading services...', 'NORDBOOKING'),
                'no_services_found' => __('No services found.', 'NORDBOOKING'),
                'error_loading_services' => __('Error loading services.', 'NORDBOOKING'),
                'service_deleted' => __('Service deleted.', 'NORDBOOKING'),
                'error_deleting_service_ajax' => __('AJAX error deleting service.', 'NORDBOOKING'),
                'active' => __('Active', 'NORDBOOKING'),
                'inactive' => __('Inactive', 'NORDBOOKING'),
                'confirm_delete' => __('Are you sure you want to delete this service?', 'NORDBOOKING'),
            ]
        ]);
        wp_localize_script('nordbooking-dashboard-services', 'nordbooking_services_params', $services_params);
    }

// Specific to Service Edit page
    if ($current_page_slug === 'service-edit') {
        error_log('[NORDBOOKING Debug] Correctly identified service-edit page. Enqueueing scripts.');
        // FIXED: Added jquery-ui-sortable dependency for drag and drop functionality
        wp_enqueue_script('NORDBOOKING-service-edit', NORDBOOKING_THEME_URI . 'assets/js/dashboard-service-edit.js', array('jquery', 'jquery-ui-sortable'), NORDBOOKING_VERSION, true);

        $service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
        $option_count = 0;
        if ($service_id > 0) {
            $services_manager = new \NORDBOOKING\Classes\Services();
            $service_data = $services_manager->get_service($service_id, get_current_user_id());
            if ($service_data && !is_wp_error($service_data) && !empty($service_data['options'])) {
                $option_count = count($service_data['options']);
            }
        }

        wp_localize_script('NORDBOOKING-service-edit', 'nordbooking_service_edit_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_base_url' => admin_url('admin.php'),
            'nonce' => wp_create_nonce('nordbooking_services_nonce'),
            'option_count' => $option_count,
            'redirect_url' => home_url('/dashboard/services'),
            'i18n' => [
                'saving' => __('Saving...', 'NORDBOOKING'),
                'service_saved' => __('Service saved successfully.', 'NORDBOOKING'),
                'error_saving_service' => __('Error saving service. Please check your input and try again.', 'NORDBOOKING'),
                'error_ajax' => __('An AJAX error occurred. Please try again.', 'NORDBOOKING'),
                'confirm_delete' => __('Are you sure you want to delete this service? This action cannot be undone.', 'NORDBOOKING'),
                'confirm_delete_option' => __('Are you sure you want to delete this option?', 'NORDBOOKING'),
                'service_deleted' => __('Service deleted successfully', 'NORDBOOKING'),
                'error_deleting_service' => __('Failed to delete service', 'NORDBOOKING'),
                'service_duplicated' => __('Service duplicated successfully', 'NORDBOOKING'),
                'error_duplicating_service' => __('Failed to duplicate service', 'NORDBOOKING'),
                'error_uploading_image' => __('Failed to upload image', 'NORDBOOKING'),
                'price_per_sqm' => __('Price per Square Meter', 'NORDBOOKING'),
                'price_per_km' => __('Price per Kilometer', 'NORDBOOKING'),
                'price_impact' => __('Price Impact', 'NORDBOOKING'),
                'price_value' => __('Price Value', 'NORDBOOKING'),
                'no_options_yet' => __('No options added yet', 'NORDBOOKING'),
                'add_options_prompt' => __('Add customization options like room size, add-ons, or special requirements to make your service more flexible.', 'NORDBOOKING'),
                'add_first_option' => __('Add Your First Option', 'NORDBOOKING'),
            ]
        ]);
    }

    // Specific to Bookings page
    if ($current_page_slug === 'bookings') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('nordbooking-dashboard-bookings', NORDBOOKING_THEME_URI . 'assets/js/dashboard-bookings.js', array('jquery', 'jquery-ui-datepicker', 'nordbooking-dialog', 'nordbooking-dashboard-global'), NORDBOOKING_VERSION, true);
        $bookings_params = array_merge($dashboard_params, [
            'statuses' => [
                'pending' => __('Pending', 'NORDBOOKING'),
                'confirmed' => __('Confirmed', 'NORDBOOKING'),
                'completed' => __('Completed', 'NORDBOOKING'),
                'cancelled' => __('Cancelled', 'NORDBOOKING'),
                'on-hold' => __('On Hold', 'NORDBOOKING'),
                'processing' => __('Processing', 'NORDBOOKING'),
            ],
            'icons' => [
                'pending' => nordbooking_get_status_badge_icon_svg('pending'),
                'confirmed' => nordbooking_get_status_badge_icon_svg('confirmed'),
                'completed' => nordbooking_get_status_badge_icon_svg('completed'),
                'cancelled' => nordbooking_get_status_badge_icon_svg('cancelled'),
                'on-hold' => nordbooking_get_status_badge_icon_svg('on-hold'),
                'processing' => nordbooking_get_status_badge_icon_svg('processing'),
            ],
            'i18n' => [
                'loading_bookings' => __('Loading bookings...', 'NORDBOOKING'),
                'no_bookings_found' => __('No bookings found.', 'NORDBOOKING'),
                'error_loading_bookings' => __('Error loading bookings.', 'NORDBOOKING'),
                'error_ajax' => __('An AJAX error occurred.', 'NORDBOOKING'),
                'confirm_delete' => __('Are you sure you want to delete this booking?', 'NORDBOOKING'),
                'booking_deleted' => __('Booking deleted.', 'NORDBOOKING'),
                'error_deleting_booking' => __('Error deleting booking.', 'NORDBOOKING'),
                'booking_updated' => __('Booking updated.', 'NORDBOOKING'),
                'error_updating_booking' => __('Error updating booking.', 'NORDBOOKING'),
                'more_filters' => __('More', 'NORDBOOKING'),
                'less_filters' => __('Less', 'NORDBOOKING'),
                'try_different_filters' => __('Try adjusting your filters or clearing them to see all bookings.', 'NORDBOOKING'),
                'unassigned' => __('Unassigned', 'NORDBOOKING'),
                'delete_btn_text' => __('Delete', 'NORDBOOKING'),
            ]
        ]);
        wp_localize_script('nordbooking-dashboard-bookings', 'nordbooking_bookings_params', $bookings_params);
    }

    // Specific to Calendar page
    if ($current_page_slug === 'calendar') {
        wp_enqueue_style('nordbooking-fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css', array(), '6.1.11');
        wp_enqueue_script('nordbooking-fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.js', array('jquery'), '6.1.11', true);
        wp_enqueue_script('nordbooking-dashboard-calendar', NORDBOOKING_THEME_URI . 'assets/js/dashboard-calendar.js', array('jquery', 'nordbooking-fullcalendar', 'nordbooking-dialog'), NORDBOOKING_VERSION, true);

        wp_localize_script('nordbooking-dashboard-calendar', 'nordbooking_calendar_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nordbooking_calendar_nonce'),
						'i18n' => [
                'loading_events' => __('Loading events...', 'NORDBOOKING'),
                'error_loading_events' => __('Error loading events.', 'NORDBOOKING'),
								'booking_details' => __('Booking Details', 'NORDBOOKING'),
            ]
        ]);
    }

    // Specific to Discounts page
    if ($current_page_slug === 'discounts') {
        wp_enqueue_style('nordbooking-dashboard-discounts', NORDBOOKING_THEME_URI . 'assets/css/dashboard-discounts.css', array(), NORDBOOKING_VERSION);
        wp_enqueue_script('nordbooking-dashboard-discounts', NORDBOOKING_THEME_URI . 'assets/js/dashboard-discounts.js', array('jquery', 'nordbooking-dialog'), NORDBOOKING_VERSION, true);
        $discounts_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_discounts' => __('Loading discounts...', 'NORDBOOKING'),
                'no_discounts_found' => __('No discounts found.', 'NORDBOOKING'),
                'error_loading_discounts' => __('Error loading discounts.', 'NORDBOOKING'),
                'error_ajax' => __('An AJAX error occurred.', 'NORDBOOKING'),
                'confirm_delete' => __('Are you sure you want to delete this discount?', 'NORDBOOKING'),
                'discount_deleted' => __('Discount deleted.', 'NORDBOOKING'),
                'error_deleting_discount' => __('Error deleting discount.', 'NORDBOOKING'),
                'discount_saved' => __('Discount saved.', 'NORDBOOKING'),
                'error_saving_discount' => __('Error saving discount.', 'NORDBOOKING'),
                'saving' => __('Saving...', 'NORDBOOKING'),
            ],
            'types' => [
                'percentage' => __('Percentage', 'NORDBOOKING'),
                'fixed_amount' => __('Fixed Amount', 'NORDBOOKING'),
            ],
            'statuses' => [
                'active' => __('Active', 'NORDBOOKING'),
                'inactive' => __('Inactive', 'NORDBOOKING'),
            ],
        ]);
        wp_localize_script('nordbooking-dashboard-discounts', 'nordbooking_discounts_params', $discounts_params);
    }

    // Specific to Areas page
    if ($current_page_slug === 'areas') {
        wp_enqueue_style('NORDBOOKING-enhanced-areas', NORDBOOKING_THEME_URI . 'assets/css/enhanced-areas.css', array(), NORDBOOKING_VERSION);
        wp_enqueue_script('NORDBOOKING-enhanced-areas', NORDBOOKING_THEME_URI . 'assets/js/enhanced-areas.js', array('jquery', 'wp-i18n', 'nordbooking-dialog'), NORDBOOKING_VERSION, true);
        $areas_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_areas' => __('Loading areas...', 'NORDBOOKING'),
                'no_areas_found' => __('No areas found.', 'NORDBOOKING'),
                'error_loading_areas' => __('Error loading areas.', 'NORDBOOKING'),
                'error_ajax' => __('An AJAX error occurred.', 'NORDBOOKING'),
                'confirm_delete' => __('Are you sure you want to delete this area?', 'NORDBOOKING'),
                'area_deleted' => __('Area deleted.', 'NORDBOOKING'),
                'error_deleting_area' => __('Error deleting area.', 'NORDBOOKING'),
                'area_saved' => __('Area saved.', 'NORDBOOKING'),
                'error_saving_area' => __('Error saving area.', 'NORDBOOKING'),
                'saving' => __('Saving...', 'NORDBOOKING'),
            ]
        ]);
        $areas_params['nonce'] = wp_create_nonce('nordbooking_areas_nonce');
        wp_localize_script('NORDBOOKING-enhanced-areas', 'nordbooking_areas_params', $areas_params);
    }

    // Specific to Booking Form Settings page
// Specific to Booking Form Settings page
if ($current_page_slug === 'booking-form') {
    // Enqueue WordPress Color Picker
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('wp-color-picker');

    // Enqueue specific CSS for booking form settings page
    wp_enqueue_style(
        'nordbooking-booking-form-settings-css',
        NORDBOOKING_THEME_URI . 'assets/css/booking-form-modern.css',
        array('nordbooking-dashboard-main', 'wp-color-picker'),
        NORDBOOKING_VERSION
    );

    // Enqueue the JavaScript
    wp_enqueue_script(
        'nordbooking-dashboard-booking-form-settings',
        NORDBOOKING_THEME_URI . 'assets/js/dashboard-booking-form-settings.js',
        array('jquery'),
        NORDBOOKING_VERSION,
        true
    );

    // Prepare localized parameters
    $bf_settings_params = array_merge($dashboard_params, [
        'i18n' => [
            'saving' => __('Saving...', 'NORDBOOKING'),
            'save_success' => __('Booking form settings saved successfully.', 'NORDBOOKING'),
            'error_saving' => __('Error saving settings.', 'NORDBOOKING'),
            'error_loading' => __('Error loading settings.', 'NORDBOOKING'),
            'error_ajax' => __('An AJAX error occurred.', 'NORDBOOKING'),
            'invalid_json' => __('Invalid JSON format in Business Hours.', 'NORDBOOKING'),
            'copied' => __('Copied!', 'NORDBOOKING'),
            'copy_failed' => __('Copy failed. Please try manually.', 'NORDBOOKING'),
            'booking_form_title' => __('Booking Form', 'NORDBOOKING'),
            'link_will_appear_here' => __('Link will appear here once slug is saved.', 'NORDBOOKING'),
            'embed_will_appear_here' => __('Embed code will appear here once slug is saved.', 'NORDBOOKING'),
        ]
    ]);

    wp_localize_script('nordbooking-dashboard-booking-form-settings', 'nordbooking_bf_settings_params', $bf_settings_params);

    // Debug output (remove this in production)
    error_log('[NORDBOOKING] Booking form settings assets enqueued successfully');
}


    // Specific to Business Settings page
    // This block has been removed because it conflicts with the script enqueuing
    // in `functions/theme-setup.php`. The logic in `theme-setup.php` is more
    // complete and is the single source of truth for this page's scripts.
    // if ($current_page_slug === 'settings') { ... }

    // Specific to Overview page (Dashboard)
    if ($current_page_slug === 'overview') {
        $version = defined('NORDBOOKING_VERSION') ? NORDBOOKING_VERSION : '1.0.0';

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
            'nordbooking-dashboard-enhanced-css',
            NORDBOOKING_THEME_URI . 'assets/css/dashboard-overview-enhanced.css',
            array(),
            $version
        );

        // Enqueue enhanced dashboard JavaScript (depends on feather and chart)
        wp_enqueue_script(
            'nordbooking-dashboard-enhanced-js',
            NORDBOOKING_THEME_URI . 'assets/js/dashboard-overview-enhanced.js',
            array('jquery', 'feather-icons', 'chart-js'),
            $version,
            true
        );

        // Localize script with dashboard data
	    $current_user_id = get_current_user_id();
	    $data_user_id = $current_user_id;
	    $is_worker = false;

	    // Handle workers
	    if (class_exists('NORDBOOKING\Classes\Auth') && \NORDBOOKING\Classes\Auth::is_user_worker($current_user_id)) {
	        $owner_id = \NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
	        if ($owner_id) {
	            $data_user_id = $owner_id;
	            $is_worker = true;
	        }
	    }

	    // Get managers and data
	    $services_manager = new \NORDBOOKING\Classes\Services();
	    $discounts_manager = new \NORDBOOKING\Classes\Discounts($current_user_id);
	    $notifications_manager = new \NORDBOOKING\Classes\Notifications();
	    $bookings_manager = new \NORDBOOKING\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
	    $settings_manager = new \NORDBOOKING\Classes\Settings();

	    // Get statistics
	    $stats = $bookings_manager->get_booking_statistics($data_user_id);

	    // Calculate metrics
	    global $wpdb;
	    $bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');

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
	    $currency_symbol = \NORDBOOKING\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));

	    wp_localize_script('nordbooking-dashboard-enhanced-js', 'nordbooking_overview_params', array(
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'),
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
	            'time_ago_just_now' => __('Just now', 'NORDBOOKING'),
	            'time_ago_seconds_suffix' => __('s ago', 'NORDBOOKING'),
	            'time_ago_minutes_suffix' => __('m ago', 'NORDBOOKING'),
	            'time_ago_hours_suffix' => __('h ago', 'NORDBOOKING'),
	            'time_ago_days_suffix' => __('d ago', 'NORDBOOKING'),
	            'loading' => __('Loading...', 'NORDBOOKING'),
	            'no_data' => __('No data available', 'NORDBOOKING'),
	            'error' => __('Error loading data', 'NORDBOOKING'),
	            'copied' => __('Copied!', 'NORDBOOKING'),
	            'new_booking' => __('New booking received', 'NORDBOOKING'),
	            'booking_updated' => __('Booking status updated', 'NORDBOOKING'),
	            'service_created' => __('New service created', 'NORDBOOKING'),
	            'worker_added' => __('New worker added', 'NORDBOOKING'),
	            'settings_updated' => __('Settings updated', 'NORDBOOKING'),
	            'refresh_success' => __('Data refreshed successfully', 'NORDBOOKING'),
	            'refresh_error' => __('Failed to refresh data', 'NORDBOOKING'),
	            'export_success' => __('Data exported successfully', 'NORDBOOKING'),
	            'export_error' => __('Failed to export data', 'NORDBOOKING'),
	            'network_error' => __('Network connection error', 'NORDBOOKING'),
	            'unauthorized' => __('You are not authorized to perform this action', 'NORDBOOKING')
	        )
	    ));
    }

    // Specific to Availability page
    if ($current_page_slug === 'availability') {
        wp_enqueue_style('nordbooking-dashboard-availability', NORDBOOKING_THEME_URI . 'assets/css/dashboard-availability.css', array(), NORDBOOKING_VERSION);
        wp_enqueue_script('jquery-ui-datepicker'); // For calendar
        wp_enqueue_script('nordbooking-dashboard-availability', NORDBOOKING_THEME_URI . 'assets/js/dashboard-availability.js', array('jquery', 'jquery-ui-datepicker', 'nordbooking-dialog'), NORDBOOKING_VERSION, true);

        $availability_i18n_strings = [
            'sunday' => __('Sunday', 'NORDBOOKING'),
            'monday' => __('Monday', 'NORDBOOKING'),
            'tuesday' => __('Tuesday', 'NORDBOOKING'),
            'wednesday' => __('Wednesday', 'NORDBOOKING'),
            'thursday' => __('Thursday', 'NORDBOOKING'),
            'friday' => __('Friday', 'NORDBOOKING'),
            'saturday' => __('Saturday', 'NORDBOOKING'),
            'edit' => __('Edit', 'NORDBOOKING'),
            'delete' => __('Delete', 'NORDBOOKING'),
            'active' => __('Active', 'NORDBOOKING'),
            'inactive' => __('Inactive', 'NORDBOOKING'),
            'unavailable_all_day' => __("Unavailable all day", 'NORDBOOKING'),
            'no_recurring_slots' => __("No recurring slots defined yet.", 'NORDBOOKING'),
            'add_recurring_slot' => __("Add Recurring Slot", 'NORDBOOKING'),
            'edit_recurring_slot' => __("Edit Recurring Slot", 'NORDBOOKING'),
            'error_loading_recurring_schedule' => __("Error loading recurring schedule.", 'NORDBOOKING'),
            'error_loading_recurring_schedule_retry' => __("Could not load schedule. Please try again.", 'NORDBOOKING'),
            'error_ajax' => __("An AJAX error occurred. Please try again.", 'NORDBOOKING'),
            'start_end_time_required' => __("Start and End time are required.", 'NORDBOOKING'),
            'start_time_before_end' => __("Start time must be before end time.", 'NORDBOOKING'),
            'error_saving_slot' => __("Error saving slot.", 'NORDBOOKING'),
            'error_slot_not_found' => __("Slot not found for editing.", 'NORDBOOKING'),
            'error_general' => __("An error occurred.", 'NORDBOOKING'),
            'confirm_delete_slot' => __("Are you sure you want to delete this recurring slot?", 'NORDBOOKING'),
            'error_deleting_slot' => __("Error deleting slot.", 'NORDBOOKING'),
        ];

        $availability_params = array_merge($dashboard_params, [
            'i18n' => $availability_i18n_strings
        ]);

        $plus_icon_path = NORDBOOKING_THEME_DIR . 'assets/svg-icons/plus.svg';
        $trash_icon_path = NORDBOOKING_THEME_DIR . 'assets/svg-icons/trash.svg';
        $copy_icon_path = NORDBOOKING_THEME_DIR . 'assets/svg-icons/copy.svg';

        if (file_exists($plus_icon_path)) {
            $availability_params['icons']['plus'] = file_get_contents($plus_icon_path);
        }

        if (file_exists($trash_icon_path)) {
            $availability_params['icons']['trash'] = file_get_contents($trash_icon_path);
        }

        if (file_exists($copy_icon_path)) {
            $availability_params['icons']['copy'] = file_get_contents($copy_icon_path);
        }

        wp_localize_script('nordbooking-dashboard-availability', 'nordbooking_availability_params', $availability_params);
    }

    // Specific to Workers page (if exists)
    if ($current_page_slug === 'workers') {
        wp_enqueue_style('NORDBOOKING-workers-enhanced', NORDBOOKING_THEME_URI . 'assets/css/dashboard-workers-enhanced.css', [], NORDBOOKING_VERSION);
        wp_enqueue_script('nordbooking-dashboard-workers', NORDBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array('jquery', 'nordbooking-dialog'), NORDBOOKING_VERSION, true);
        $workers_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_workers' => __('Loading workers...', 'NORDBOOKING'),
                'no_workers_found' => __('No workers found.', 'NORDBOOKING'),
                'error_loading_workers' => __('Error loading workers.', 'NORDBOOKING'),
                'error_ajax' => __('An AJAX error occurred.', 'NORDBOOKING'),
                'confirm_delete' => __('Are you sure you want to delete this worker?', 'NORDBOOKING'),
                'worker_deleted' => __('Worker deleted.', 'NORDBOOKING'),
                'error_deleting_worker' => __('Error deleting worker.', 'NORDBOOKING'),
                'worker_saved' => __('Worker saved.', 'NORDBOOKING'),
                'error_saving_worker' => __('Error saving worker.', 'NORDBOOKING'),
                'saving' => __('Saving...', 'NORDBOOKING'),
            ]
        ]);
        wp_localize_script('nordbooking-dashboard-workers', 'nordbooking_workers_params', $workers_params);
    }

    // Specific to Customers page
    if ($current_page_slug === 'customers' || $current_page_slug === 'customer-details') {
        wp_enqueue_style('nordbooking-dashboard-customer-details', NORDBOOKING_THEME_URI . 'assets/css/dashboard-customer-details.css', array(), NORDBOOKING_VERSION);
    }

    // Global dashboard script (always load for all dashboard pages)
    wp_enqueue_script('nordbooking-dashboard-global', NORDBOOKING_THEME_URI . 'assets/js/dashboard.js', array('jquery'), NORDBOOKING_VERSION, true);
    wp_localize_script('nordbooking-dashboard-global', 'nordbooking_dashboard_params', $dashboard_params);
}
?>
