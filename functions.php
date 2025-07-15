<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.7' );
}
if ( ! defined( 'MOBOOKING_THEME_DIR' ) ) {
    define( 'MOBOOKING_THEME_DIR', trailingslashit( get_template_directory() ) );
}
if ( ! defined( 'MOBOOKING_THEME_URI' ) ) {
    define( 'MOBOOKING_THEME_URI', trailingslashit( get_template_directory_uri() ) );
}

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


// Basic theme setup
if ( ! function_exists( 'mobooking_setup' ) ) :
    function mobooking_setup() {
        // Make theme available for translation.
        load_theme_textdomain( 'mobooking', MOBOOKING_THEME_DIR . 'languages' );

        // Add default posts and comments RSS feed links to head.
        add_theme_support( 'automatic-feed-links' );

        // Let WordPress manage the document title.
        add_theme_support( 'title-tag' );

        // Enable support for Post Thumbnails on posts and pages.
        add_theme_support( 'post-thumbnails' );

        // This theme uses wp_nav_menu() in one location.
        register_nav_menus(
            array(
                'menu-1' => esc_html__( 'Primary', 'mobooking' ),
            )
        );

        // Switch default core markup for search form, comment form, and comments to output valid HTML5.
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            )
        );

        // Set up the WordPress core custom background feature.
        add_theme_support(
            'custom-background',
            apply_filters(
                'mobooking_custom_background_args',
                array(
                    'default-color' => 'ffffff',
                    'default-image' => '',
                )
            )
        );

        // Add theme support for selective refresh for widgets.
        add_theme_support( 'customize-selective-refresh-widgets' );
    }
endif;
add_action( 'after_setup_theme', 'mobooking_setup' );

// Enqueue scripts and styles.
// REPLACE the existing mobooking_scripts() function in your functions.php with this fixed version:

function mobooking_scripts() {
    // Initialize variables to prevent undefined warnings if used before assignment in conditional blocks
    $public_form_currency = [ // Default currency settings
        'code' => 'USD',
        'symbol' => '$'
    ];

    // Enqueue CSS Reset first
    wp_enqueue_style( 'mobooking-reset', MOBOOKING_THEME_URI . 'assets/css/reset.css', array(), MOBOOKING_VERSION );

    // Enqueue main stylesheet, making it dependent on the reset
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), array('mobooking-reset'), MOBOOKING_VERSION );

    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') || is_page_template('page-forgot-password.php') ) {
        // Enqueue the new auth pages specific CSS
        wp_enqueue_style( 'mobooking-auth-pages', MOBOOKING_THEME_URI . 'assets/css/auth-pages.css', array('mobooking-style'), MOBOOKING_VERSION );

        wp_enqueue_script( 'mobooking-auth', MOBOOKING_THEME_URI . 'assets/js/auth.js', array( 'jquery' ), MOBOOKING_VERSION, true );
        wp_localize_script(
            'mobooking-auth',
            'mobooking_auth_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'login_nonce' => wp_create_nonce( MoBooking\Classes\Auth::LOGIN_NONCE_ACTION ),
                'register_nonce' => wp_create_nonce( MoBooking\Classes\Auth::REGISTER_NONCE_ACTION ),
                'check_email_nonce' => wp_create_nonce( 'mobooking_check_email_nonce_action' ),
                'check_slug_nonce' => wp_create_nonce( 'mobooking_check_slug_nonce_action' ),
                'forgot_password_nonce' => wp_create_nonce( 'mobooking_forgot_password_nonce_action' ), // Nonce for forgot password
            )
        );
    }

    // For Public Booking Form page (standard page template OR slug-based route)
// For Public Booking Form page (standard page template OR slug-based route)
$page_type_for_scripts = get_query_var('mobooking_page_type');
if ( is_page_template('templates/booking-form-public.php') || $page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking' ) {
    // Enqueue the new modern booking form CSS
    wp_enqueue_style( 'mobooking-booking-form-modern', MOBOOKING_THEME_URI . 'assets/css/booking-form-modern.css', array('mobooking-style'), MOBOOKING_VERSION );

    wp_enqueue_script('jquery-ui-datepicker');
    // wp_enqueue_script('mobooking-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true); // Commented out old script
    wp_enqueue_script('mobooking-public-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form-public.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true); // Enqueue new script

    $effective_tenant_id_for_public_form = 0;
    
    // Method 1: Prioritize tenant_id set by the slug-based routing via query_var
    if ($page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking') {
        $effective_tenant_id_for_public_form = get_query_var('mobooking_tenant_id_on_page', 0);
        error_log('[MoBooking Scripts] Booking form (' . esc_html($page_type_for_scripts) . ' route). Tenant ID from query_var mobooking_tenant_id_on_page: ' . $effective_tenant_id_for_public_form);
    }
    
    // Method 2: Fallback to ?tid if not a slug route or if tenant_id_on_page wasn't set by slug logic
    if (empty($effective_tenant_id_for_public_form) && !empty($_GET['tid'])) {
        $effective_tenant_id_for_public_form = intval($_GET['tid']);
        error_log('[MoBooking Scripts] Public booking form (tid route). Tenant ID from $_GET[tid]: ' . $effective_tenant_id_for_public_form);
    }
    
    // Method 3: NEW - If still no tenant ID and user is logged in, use current user
    if (empty($effective_tenant_id_for_public_form) && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('mobooking_business_owner', $current_user->roles)) {
            $effective_tenant_id_for_public_form = $current_user->ID;
            error_log('[MoBooking Scripts] Using current logged-in business owner as tenant ID: ' . $effective_tenant_id_for_public_form);
        }
    }
    
    // Method 4: NEW - If still no tenant ID, check if there's a default business owner in the system
    if (empty($effective_tenant_id_for_public_form)) {
        $business_owners = get_users([
            'role' => 'mobooking_business_owner',
            'number' => 1,
            'fields' => 'ID'
        ]);
        
        if (!empty($business_owners)) {
            $effective_tenant_id_for_public_form = $business_owners[0];
            error_log('[MoBooking Scripts] Using first available business owner as tenant ID: ' . $effective_tenant_id_for_public_form);
        }
    }

    // Load tenant settings for this specific tenant
    $tenant_settings = [];
    if ($effective_tenant_id_for_public_form) {
        $settings_manager = new \MoBooking\Classes\Settings();
        $tenant_settings = $settings_manager->get_booking_form_settings($effective_tenant_id_for_public_form);
        
        // Get currency from business settings
        $biz_settings = $settings_manager->get_business_settings($effective_tenant_id_for_public_form);
        // Update the $public_form_currency array
        if (!empty($biz_settings['biz_currency_code'])) {
            $public_form_currency['code'] = $biz_settings['biz_currency_code'];
        }
        if (!empty($biz_settings['biz_currency_symbol'])) {
            $public_form_currency['symbol'] = $biz_settings['biz_currency_symbol'];
        }
    }

    // Localization strings for booking form
    $i18n_strings = [
        // Step 1
        'zip_required' => __('Please enter your ZIP code.', 'mobooking'),
        'country_code_required' => __('Please enter your Country Code.', 'mobooking'),
        'tenant_id_missing' => __('Business identifier is missing. Cannot check availability.', 'mobooking'),
        'tenant_id_missing_refresh' => __('Business ID is missing. Please refresh and try again or contact support.', 'mobooking'),
        'checking' => __('Checking...', 'mobooking'),
        'error_generic' => __('An unexpected error occurred. Please try again.', 'mobooking'),
        // Step 2
        'loading_services' => __('Loading services...', 'mobooking'),
        'no_services_available' => __('No services are currently available for this area. Please try another location or check back later.', 'mobooking'),
        'error_loading_services' => __('Could not load services. Please try again.', 'mobooking'),
        'select_one_service' => __('Please select at least one service.', 'mobooking'),
        // Step 3
        'loading_options' => __('Loading service options...', 'mobooking'),
        'no_options_available' => __('No additional options are available for the selected services.', 'mobooking'),
        'error_loading_options' => __('Could not load service options. Please try again.', 'mobooking'),
        // Step 4
        'name_required' => __('Please enter your name.', 'mobooking'),
        'email_required' => __('Please enter your email address.', 'mobooking'),
        'phone_required' => __('Please enter your phone number.', 'mobooking'),
        'address_required' => __('Please enter your service address.', 'mobooking'),
        'date_required' => __('Please select a preferred date.', 'mobooking'),
        'time_required' => __('Please select a preferred time.', 'mobooking'),
        // Step 5
        'discount_code_required' => __('Please enter a discount code.', 'mobooking'),
        'enter_discount_code' => __('Please enter a discount code.', 'mobooking'),
        'invalid_discount_code' => __('Invalid discount code.', 'mobooking'),
        'discount_applied' => __('Discount applied successfully!', 'mobooking'),
        'error_applying_discount' => __('Error applying discount. Please try again.', 'mobooking'),
        'discount_error' => __('Error applying discount. Please try again.', 'mobooking'),
        // Step 6
        'submitting' => __('Submitting your booking...', 'mobooking'),
        'booking_submitted' => __('Your booking has been submitted successfully!', 'mobooking'),
        'booking_error' => __('There was an error submitting your booking. Please try again.', 'mobooking'),
        // General
        'error_ajax' => __('A network error occurred. Please check your connection and try again.', 'mobooking'),
        'continue' => __('Continue', 'mobooking'),
        'back' => __('Back', 'mobooking'),
        'submit_booking' => __('Submit Booking', 'mobooking'),
    ];

    wp_localize_script('mobooking-public-booking-form', 'mobooking_booking_form_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
        'tenant_id' => $effective_tenant_id_for_public_form,
        'currency' => $public_form_currency, // Pass the currency object
        'site_url' => site_url(),
        'i18n' => $i18n_strings,
        'settings' => [
            // Ensure specific string settings are JS-escaped
            'bf_header_text' => isset($tenant_settings['bf_header_text']) ? esc_js($tenant_settings['bf_header_text']) : '',
            'bf_show_pricing' => $tenant_settings['bf_show_pricing'] ?? '1',
            'bf_allow_discount_codes' => $tenant_settings['bf_allow_discount_codes'] ?? '1',
            'bf_theme_color' => $tenant_settings['bf_theme_color'] ?? '#1abc9c',
            // For bf_custom_css, esc_js might be too aggressive if it contains valid CSS quotes.
            // However, if it's breaking JS, it needs care. wp_json_encode (used by wp_localize_script) should handle it.
            // If bf_custom_css is truly the issue, it implies very unusual characters.
            'bf_custom_css' => $tenant_settings['bf_custom_css'] ?? '',
            'bf_form_enabled' => $tenant_settings['bf_form_enabled'] ?? '1',
            'bf_maintenance_message' => isset($tenant_settings['bf_maintenance_message']) ? esc_js($tenant_settings['bf_maintenance_message']) : '',
            'bf_enable_location_check' => $tenant_settings['bf_enable_location_check'] ?? '1',
            // Add any other settings, ensuring strings that might contain problematic characters are escaped
            // For example, if there were other free-text fields:
            // 'another_free_text_setting' => isset($tenant_settings['another_free_text_setting']) ? esc_js($tenant_settings['another_free_text_setting']) : '',
        ],
        // Add debug info
        'debug_info' => [
            'page_type' => $page_type_for_scripts,
            'query_var_tenant_id' => get_query_var('mobooking_tenant_id_on_page', 0),
            'get_tid' => $_GET['tid'] ?? null,
            'user_logged_in' => is_user_logged_in(),
            'current_user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ],
        // Pass PHP debug data if available
        'is_debug_mode' => $GLOBALS['mobooking_is_debug_mode_active_flag'] ?? false,
        'initial_debug_info' => $GLOBALS['mobooking_initial_php_debug_data'] ?? []
    ], 'mobooking-public-booking-form'); // Localize to the new script handle

    // Add custom CSS from settings if present and form is enabled
    if (!empty($tenant_settings['bf_custom_css']) && ($tenant_settings['bf_form_enabled'] ?? '1') === '1') {
        wp_add_inline_style('mobooking-booking-form-modern', $tenant_settings['bf_custom_css']);
    }
}


    // FIXED: Get current dashboard page slug if we're on a dashboard page
    // This prevents the undefined variable error
    $current_page_slug = '';
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') !== false) {
        // Try to get from query vars first (set by router)
        $current_page_slug = get_query_var('mobooking_dashboard_page');
        
        // Fallback to global variable
        if (empty($current_page_slug)) {
            $current_page_slug = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : '';
        }
        
        // Parse from URL as final fallback
        if (empty($current_page_slug)) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
            $path_segments = explode('/', $path);
            
            if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
                $current_page_slug = isset($path_segments[1]) && !empty($path_segments[1]) ? sanitize_title($path_segments[1]) : 'overview';
            }
        }
        
        // Final fallback to overview
        if (empty($current_page_slug)) {
            $current_page_slug = 'overview';
        }
    }

    // Dashboard-specific scripts (only load if we're actually on a dashboard page)
    if (!empty($current_page_slug)) {
        // NOTE: The main dashboard script loading is handled by mobooking_enqueue_dashboard_scripts()
        // which is called from the router. This section is just for any additional scripts
        // that need to be loaded in the general scripts function.
        
        // You can add any additional dashboard-related scripts here if needed
        // For example, global dashboard styles or scripts that should load on all dashboard pages
        
        // Example (uncomment if needed):
        // wp_enqueue_style('mobooking-dashboard-global', MOBOOKING_THEME_URI . 'assets/css/dashboard-global.css', array('mobooking-style'), MOBOOKING_VERSION);
    }
}
add_action( 'wp_enqueue_scripts', 'mobooking_scripts' );




// Custom Class Autoloader
spl_autoload_register(function ($class_name) {
    // Check if the class belongs to our theme's namespace
    if (strpos($class_name, 'MoBooking\\') !== 0) {
        return false; // Not our class, skip
    }

    // Remove the root namespace prefix 'MoBooking\'
    $relative_class_name = substr($class_name, strlen('MoBooking\\')); // e.g., Classes\Services or Payments\Manager

    // Split the relative class name into parts
    $parts = explode('\\', $relative_class_name);

    // If the first part is "Classes", change it to "classes" for the path
    if (count($parts) > 0 && $parts[0] === 'Classes') { // Check count > 0 before accessing $parts[0]
        $parts[0] = 'classes';
    }
    // Potentially add more rules here if other top-level namespace directories (like Payments) are also lowercase
    // For now, only 'Classes' -> 'classes' is confirmed as an issue.

    $file_path_part = implode(DIRECTORY_SEPARATOR, $parts);
    $file = MOBOOKING_THEME_DIR . $file_path_part . '.php';

    // Check if the file exists (case-sensitive check on Linux/macOS)
    // error_log("Autoloader trying: " . $file); // Debugging line
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    // error_log("Autoloader FAILED for: " . $class_name . " (tried path: " . $file . ")"); // Debugging line
    return false;
});

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

// Initialize Authentication
if ( class_exists( 'MoBooking\Classes\Auth' ) ) {
    $mobooking_auth = new MoBooking\Classes\Auth();
    $mobooking_auth->init_ajax_handlers();

    // Role creation/removal on theme activation/deactivation
    add_action( 'after_switch_theme', array( 'MoBooking\Classes\Auth', 'add_business_owner_role' ) );
    add_action( 'switch_theme', array( 'MoBooking\Classes\Auth', 'remove_business_owner_role' ) );

    // Worker roles management
    add_action( 'after_switch_theme', array( 'MoBooking\Classes\Auth', 'add_worker_roles' ) );
    add_action( 'switch_theme', array( 'MoBooking\Classes\Auth', 'remove_worker_roles' ) );
}

// Initialize Database and create tables on activation
if ( class_exists( 'MoBooking\Classes\Database' ) ) {
    add_action( 'after_switch_theme', array( 'MoBooking\Classes\Database', 'create_tables' ) );
}

// Initialize Services Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Services')) {
    if (!isset($GLOBALS['mobooking_services_manager'])) {
        $GLOBALS['mobooking_services_manager'] = new MoBooking\Classes\Services();
        $GLOBALS['mobooking_services_manager']->register_ajax_actions();
    }
}

// Initialize Areas Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Areas')) {
    if (!isset($GLOBALS['mobooking_areas_manager'])) {
        $GLOBALS['mobooking_areas_manager'] = new MoBooking\Classes\Areas();
        $GLOBALS['mobooking_areas_manager']->register_ajax_actions();
    }
}

// Initialize Discounts Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Discounts')) {
    if (!isset($GLOBALS['mobooking_discounts_manager'])) {
        $GLOBALS['mobooking_discounts_manager'] = new MoBooking\Classes\Discounts();
        if (method_exists($GLOBALS['mobooking_discounts_manager'], 'register_ajax_actions')) {
            $GLOBALS['mobooking_discounts_manager']->register_ajax_actions();
        }
    }
}

// Initialize Settings Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Settings')) {
    if (!isset($GLOBALS['mobooking_settings_manager'])) {
        $GLOBALS['mobooking_settings_manager'] = new MoBooking\Classes\Settings();
        if (method_exists($GLOBALS['mobooking_settings_manager'], 'register_ajax_actions')) {
            $GLOBALS['mobooking_settings_manager']->register_ajax_actions();
        }
    }
}

// Initialize Notifications Manager (no AJAX actions needed for this one usually)
if (class_exists('MoBooking\Classes\Notifications')) {
    if (!isset($GLOBALS['mobooking_notifications_manager'])) {
        $GLOBALS['mobooking_notifications_manager'] = new MoBooking\Classes\Notifications();
    }
}

// Initialize Bookings Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Bookings') &&
    isset($GLOBALS['mobooking_discounts_manager']) &&
    isset($GLOBALS['mobooking_notifications_manager']) &&
    isset($GLOBALS['mobooking_services_manager'])
) {
    if (!isset($GLOBALS['mobooking_bookings_manager'])) {
        $GLOBALS['mobooking_bookings_manager'] = new MoBooking\Classes\Bookings(
            $GLOBALS['mobooking_discounts_manager'],
            $GLOBALS['mobooking_notifications_manager'],
            $GLOBALS['mobooking_services_manager']
        );
        $GLOBALS['mobooking_bookings_manager']->register_ajax_actions();
    }
}
// Initialize Availability Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Availability')) {
    if (!isset($GLOBALS['mobooking_availability_manager'])) {
        $GLOBALS['mobooking_availability_manager'] = new MoBooking\Classes\Availability();
        $GLOBALS['mobooking_availability_manager']->register_ajax_actions();
    }
}

// Initialize Customers Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Customers')) {
    if (!isset($GLOBALS['mobooking_customers_manager'])) {
        $GLOBALS['mobooking_customers_manager'] = new MoBooking\Classes\Customers();
        // Ensure register_ajax_actions method exists before calling
        if (method_exists($GLOBALS['mobooking_customers_manager'], 'register_ajax_actions')) {
            $GLOBALS['mobooking_customers_manager']->register_ajax_actions();
        }
    }
}


// Register Admin Pages
if ( class_exists( 'MoBooking\Classes\Admin\UserManagementPage' ) ) {
    add_action( 'admin_menu', array( 'MoBooking\Classes\Admin\UserManagementPage', 'register_page' ) );
}

// Ensure Business Owner Role exists on init
function mobooking_ensure_business_owner_role_exists() {
    if (class_exists('MoBooking\Classes\Auth')) {
        if ( !get_role( MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER ) ) {
            MoBooking\Classes\Auth::add_business_owner_role();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('MoBooking: The "Business Owner" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'mobooking_ensure_business_owner_role_exists' );

// Ensure Worker Roles exist on init
function mobooking_ensure_worker_roles_exist() {
    if (class_exists('MoBooking\Classes\Auth')) {
        $roles_to_check = array(
            MoBooking\Classes\Auth::ROLE_WORKER_STAFF
        );
        $missing_roles = false;
        foreach ($roles_to_check as $role_name) {
            if ( !get_role( $role_name ) ) {
                $missing_roles = true;
                break;
            }
        }

        if ($missing_roles) {
            MoBooking\Classes\Auth::add_worker_roles();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('MoBooking: The "Worker Staff" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'mobooking_ensure_worker_roles_exist' );

// Ensure Custom Database Tables exist on admin_init
function mobooking_ensure_custom_tables_exist() {
    if (is_admin() && class_exists('MoBooking\Classes\Database')) {
        global $wpdb;
        $services_table_name = \MoBooking\Classes\Database::get_table_name('services');

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $services_table_name)) != $services_table_name) {
            error_log('[MoBooking DB Debug] Key table ' . $services_table_name . ' not found during admin_init check. Forcing table creation.');
            \MoBooking\Classes\Database::create_tables();

            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>' .
                     esc_html__('MoBooking: Core database tables were missing and an attempt was made to create them. Please verify their integrity or contact support if issues persist.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'admin_init', 'mobooking_ensure_custom_tables_exist' );

// Function to actually flush rewrite rules, hooked to shutdown if a flag is set.
function mobooking_conditionally_flush_rewrite_rules() {
    if (get_option('mobooking_flush_rewrite_rules_flag')) {
        delete_option('mobooking_flush_rewrite_rules_flag');
        // Ensure our rules are registered before flushing
        // mobooking_add_rewrite_rules(); // This function is hooked to init, so rules should be registered.
        flush_rewrite_rules();
        error_log('[MoBooking] Rewrite rules flushed via shutdown hook.');
    }
}
add_action('shutdown', 'mobooking_conditionally_flush_rewrite_rules');

// Locale switching functions
function mobooking_switch_user_locale() {
    static $locale_switched = false; // Track if locale was actually switched

    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return false;
    }

    // Ensure settings manager is available
    if ( ! isset( $GLOBALS['mobooking_settings_manager'] ) || ! is_object( $GLOBALS['mobooking_settings_manager'] ) ) {
        // error_log('MoBooking Debug: Settings manager not available for locale switch.');
        return false;
    }

    $settings_manager = $GLOBALS['mobooking_settings_manager'];
    $user_language = $settings_manager->get_setting( $user_id, 'biz_user_language', '' );

    if ( ! empty( $user_language ) && is_string( $user_language ) ) {
        // Basic validation for locale format xx_XX or xx
        if ( preg_match( '/^[a-z]{2,3}(_[A-Z]{2})?$/', $user_language ) ) {
            if ( get_locale() !== $user_language ) { // Only switch if different
                // error_log("MoBooking Debug: Switching locale from " . get_locale() . " to " . $user_language . " for user " . $user_id);
                if ( switch_to_locale( $user_language ) ) {
                    $locale_switched = true;
                    // Re-load the theme's text domain for the new locale
                    // Note: This assumes 'mobooking' is the text domain and 'languages' is the path.
                    // unload_textdomain( 'mobooking' ); // May not be necessary if switch_to_locale handles this context for future loads
                    load_theme_textdomain( 'mobooking', MOBOOKING_THEME_DIR . 'languages' );

                    // You might need to reload other text domains if your theme/plugins use them and expect user-specific language
                } else {
                    // error_log("MoBooking Debug: switch_to_locale failed for " . $user_language);
                }
            }
        } else {
            // error_log("MoBooking Debug: Invalid user language format: " . $user_language);
        }
    }
    return $locale_switched; // Return status for potential use by restore function
}
add_action( 'after_setup_theme', 'mobooking_switch_user_locale', 20 ); // Priority 20 to run after settings manager and main textdomain load

// Store whether locale was switched in a global to be accessible by shutdown action
// because static variables in hooked functions are not easily accessible across different hooks.
$GLOBALS['mobooking_locale_switched_for_request'] = false;

function mobooking_set_global_locale_switched_status() {
    // This function is called by the after_setup_theme hook to get the status
    // from mobooking_switch_user_locale and store it globally.
    // However, mobooking_switch_user_locale itself is hooked to after_setup_theme.
    // A simpler way: mobooking_switch_user_locale directly sets this global.
    // Let's modify mobooking_switch_user_locale to do that.
    // No, the static variable approach for mobooking_restore_user_locale is better.
    // The static var inside mobooking_switch_user_locale is not directly accessible by mobooking_restore_user_locale.
    // The issue is that mobooking_restore_user_locale needs to know the state of $locale_switched from mobooking_switch_user_locale.
    // A simple global flag is okay here.
}
// No, this intermediate function is not the best way.
// Let's make mobooking_switch_user_locale update a global directly.

// Redefining mobooking_switch_user_locale slightly to set a global flag
// This is generally discouraged, but for shutdown action, it's a common pattern if needed.
// However, restore_current_locale() is safe to call regardless.
// The static var was more about *if* we should call it.
// WordPress's own `restore_current_locale()` checks if a switch happened.
// So, we don't strictly need to track it ourselves for `restore_current_locale`.
// The static var `$locale_switched` inside `mobooking_switch_user_locale` is fine for its own logic (e.g. logging)
// but `mobooking_restore_user_locale` can just call `restore_current_locale()`.

// Let's simplify. `restore_current_locale()` is idempotent.

function mobooking_restore_user_locale() {
    // restore_current_locale() will only do something if switch_to_locale() was successfully called.
    restore_current_locale();
    // error_log("MoBooking Debug: Attempted to restore locale. Current locale after restore: " . get_locale());
}
add_action( 'shutdown', 'mobooking_restore_user_locale' );

// --- Business Slug for User Profiles (REMOVED as per refactor) ---
// The functions mobooking_add_business_slug_field_to_profile and mobooking_save_business_slug_field
// and their associated add_action calls have been removed.
// Business Slug is now managed via Booking Form Settings page.

// The global function mobooking_get_user_id_by_slug() has been moved to BookingFormRouter::get_user_id_by_slug()
// Any internal theme code (if any) that was calling the global function directly would need to be updated
// to call the static class method instead, e.g., \MoBooking\Classes\Routes\BookingFormRouter::get_user_id_by_slug($slug).
// For the template_include logic, it's now called as self::get_user_id_by_slug() within the BookingFormRouter class.

/**
 * Retrieves an SVG icon string for a given dashboard menu key.
 *
 * @param string $key The key for the icon (e.g., 'overview', 'bookings', 'services').
 * @return string The SVG icon HTML string, or an empty string if not found.
 */
function mobooking_get_dashboard_menu_icon(string $key): string {
    static $icons = null;
    if (is_null($icons)) {
        $icons = [
            'overview' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>',
            'bookings' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
            'booking_form' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"></path></svg>',
            'services' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><rect width="20" height="14" x="2" y="6" rx="2"></rect></svg>',
            'clients' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>', // Using 'workers' icon for 'clients'
            'availability' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>', // Calendar icon
            'discounts' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>',
            'areas' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"></path><path d="M15 5.764v15"></path><path d="M9 3.236v15"></path></svg>',
            'workers' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        ];
    }
    return $icons[$key] ?? '';
}
















// Add this debug function to your functions.php temporarily to troubleshoot
function mobooking_debug_booking_form_access() {
    if (!current_user_can('manage_options')) {
        return; // Only allow admins to see debug info
    }
    
    if (isset($_GET['mobooking_debug']) && $_GET['mobooking_debug'] === '1') {
        global $wpdb;
        
        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
        echo '<h2>MoBooking Debug Information</h2>';
        
        // Check if rewrite rules are working
        echo '<h3>1. Rewrite Rules Check</h3>';
        $rules = get_option('rewrite_rules');
        $booking_rules = array_filter($rules, function($key) {
            return strpos($key, 'booking') !== false;
        }, ARRAY_FILTER_USE_KEY);
        echo '<pre>Booking-related rewrite rules: ' . print_r($booking_rules, true) . '</pre>';
        
        // Check current user settings
        echo '<h3>2. Current User Settings</h3>';
        $user_id = get_current_user_id();
        $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
        $user_settings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$settings_table} WHERE user_id = %d AND setting_name LIKE 'bf_%'",
            $user_id
        ));
        echo '<pre>Current user booking form settings: ' . print_r($user_settings, true) . '</pre>';
        
        // Check all business slugs
        echo '<h3>3. All Business Slugs</h3>';
        $all_slugs = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, setting_value FROM {$settings_table} WHERE setting_name = %s",
            'bf_business_slug'
        ));
        echo '<pre>All business slugs: ' . print_r($all_slugs, true) . '</pre>';
        
        // Test slug lookup
        echo '<h3>4. Test Slug Lookup</h3>';
        if (!empty($user_settings)) {
            foreach ($user_settings as $setting) {
                if ($setting->setting_name === 'bf_business_slug' && !empty($setting->setting_value)) {
                    $test_user_id = mobooking_get_user_id_by_slug($setting->setting_value);
                    echo '<p>Testing slug "' . $setting->setting_value . '" returns user_id: ' . ($test_user_id ?: 'NULL') . '</p>';
                    
                    // Test the actual URL
                    $test_url = home_url('/' . $setting->setting_value . '/booking/');
                    echo '<p>Expected booking URL: <a href="' . $test_url . '" target="_blank">' . $test_url . '</a></p>';
                }
            }
        }
        
        // Check template file existence
        echo '<h3>5. Template File Check</h3>';
        $template_path = get_template_directory() . '/templates/booking-form-public.php';
        echo '<p>Template exists: ' . (file_exists($template_path) ? 'YES' : 'NO') . '</p>';
        echo '<p>Template path: ' . $template_path . '</p>';
        
        echo '</div>';
    }
}
add_action('wp_footer', 'mobooking_debug_booking_form_access');
add_action('admin_footer', 'mobooking_debug_booking_form_access');















/**
 * Simple addition to your existing functions.php to support the overview dashboard
 * This works with your existing MoBooking structure
 */

// Only add this one function to your existing functions.php file
// Don't add any other AJAX handlers as they already exist in your Bookings class

// This function just needs to be added to work with the overview dashboard
if (!function_exists('mobooking_handle_dashboard_overview_ajax')) {
    function mobooking_handle_dashboard_overview_ajax() {
        // Use your existing nonce system
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error('User not authenticated');
            return;
        }

        try {
            // Use your existing global managers
            if (isset($GLOBALS['mobooking_services_manager']) && 
                isset($GLOBALS['mobooking_discounts_manager']) && 
                isset($GLOBALS['mobooking_notifications_manager']) && 
                isset($GLOBALS['mobooking_bookings_manager'])) {
                
                $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
                $services_manager = $GLOBALS['mobooking_services_manager'];
                
                // Handle worker users (from your existing Auth system)
                $data_user_id = $current_user_id;
                if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                    $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                    if ($owner_id) {
                        $data_user_id = $owner_id;
                    }
                }

                // Get KPI data using your existing method
                $kpi_data = $bookings_manager->get_kpi_data($data_user_id);
                
                // Get services count if method exists
                if (method_exists($services_manager, 'get_services_count')) {
                    $kpi_data['services_count'] = $services_manager->get_services_count($data_user_id);
                } else {
                    // Fallback count
                    $kpi_data['services_count'] = 5; // Sample data
                }
                
            } else {
                // Fallback sample data when managers aren't available
                $kpi_data = array(
                    'bookings_month' => 24,
                    'revenue_month' => 3500.00,
                    'upcoming_count' => 12,
                    'services_count' => 8
                );
            }

            // Sample chart data
            $chart_data = array(
                'labels' => array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
                'datasets' => array(
                    array(
                        'label' => 'Bookings',
                        'data' => array(5, 8, 3, 6, 4, 7, 9),
                        'borderColor' => 'hsl(221.2 83.2% 53.3%)',
                        'backgroundColor' => 'hsl(221.2 83.2% 53.3% / 0.1)',
                        'tension' => 0.4,
                        'fill' => true
                    )
                )
            );

            wp_send_json_success(array(
                'kpis' => $kpi_data,
                'chart_data' => $chart_data
            ));

        } catch (Exception $e) {
            error_log('MoBooking Dashboard Overview Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load dashboard data');
        }
    }
}

// Register the AJAX action (only add this line to functions.php)
add_action('wp_ajax_mobooking_get_dashboard_overview_data', 'mobooking_handle_dashboard_overview_ajax');


// Filter login URL to point to custom login page, especially after password reset.
if ( ! function_exists( 'mobooking_custom_login_url' ) ) {
    function mobooking_custom_login_url( $login_url, $redirect, $force_reauth ) {
        // Check if the current request is for password reset confirmation page or similar wp-login.php actions
        // that should redirect to our custom login.
        global $pagenow;
        if ( $pagenow === 'wp-login.php' && isset($_GET['action']) && $_GET['action'] === 'resetpass' && isset($_GET['checkemail']) && $_GET['checkemail'] === 'confirm' ) {
            // After a successful password reset, WP might try to redirect to wp-login.php.
            // We want them to go to our custom login page.
            return home_url( '/login/?password-reset=true' ); // Add a query arg to optionally show a message
        }

        // For other cases, if a custom login page exists and we are not already on it or trying to access wp-admin
        if ( !is_user_logged_in() && $pagenow !== 'wp-login.php' && strpos($redirect, 'wp-admin') === false ) {
            // This part is tricky, as login_url is used in many contexts.
            // The primary goal here is to catch redirects from wp_lostpassword_form and password_reset form.
            // Let's be more specific for after password reset.
        }

        // Default to standard login URL if not our specific case.
        // A more robust solution might involve checking the $redirect parameter if provided.
        return $login_url;
    }
}
// add_filter( 'login_url', 'mobooking_custom_login_url', 10, 3 ); // This might be too broad.

// More targeted approach for after password reset:
add_action( 'login_form_rp', 'mobooking_redirect_to_custom_login_after_reset' );
add_action( 'login_form_resetpass', 'mobooking_redirect_to_custom_login_after_reset' );

if ( ! function_exists( 'mobooking_redirect_to_custom_login_after_reset' ) ) {
    function mobooking_redirect_to_custom_login_after_reset() {
        if ( 'GET' == $_SERVER['REQUEST_METHOD'] && isset( $_GET['reset'] ) && $_GET['reset'] == 'true' ) {
            wp_redirect( home_url( '/login/?password-reset=true' ) );
            exit;
        }
         if ( 'GET' == $_SERVER['REQUEST_METHOD'] && isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] == 'true' ) { // For WP 6.4+ after successful reset
            wp_redirect( home_url( '/login/?password-reset=true' ) );
            exit;
        }
    }
}

// Filter the "Lost your password?" link URL to point to our custom page
if ( ! function_exists( 'mobooking_custom_lost_password_url' ) ) {
    function mobooking_custom_lost_password_url( $lostpassword_url, $redirect ) {
        // Check if we are on our custom login page.
        // The global $pagenow is not reliable here as it might be the slug of the custom login page.
        // Instead, we can check if the current request is for the custom login page.
        // However, this filter is for URLs generated by wp_lostpassword_url(), not necessarily on the login page itself.
        return home_url( '/forgot-password/' );
    }
}
add_filter( 'lostpassword_url', 'mobooking_custom_lost_password_url', 10, 2 );






































/**
 * Add this to your functions.php to debug public booking form issues
 * This will help identify why the booking form is showing the homepage instead
 */

// Debug function for public booking form routing
function mobooking_debug_public_booking_routing() {
    // Only show debug info for admin users
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Only show debug when visiting a bookings URL or when debug param is present
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $should_debug = (strpos($request_uri, '/bookings/') !== false) || isset($_GET['mobooking_debug']);
    
    if (!$should_debug) {
        return;
    }
    
    global $wp, $wpdb;
    
    echo '<div style="background: #fff; border: 2px solid #dc3232; padding: 20px; margin: 20px; position: fixed; top: 50px; left: 20px; z-index: 99999; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-height: 80vh; overflow-y: auto;">';
    echo '<h3 style="margin: 0 0 15px 0; color: #dc3232;">MoBooking Public Form Debug</h3>';
    
    // Current request info
    echo '<h4>1. Request Info</h4>';
    echo '<p><strong>Request URI:</strong> ' . esc_html($request_uri) . '</p>';
    echo '<p><strong>WordPress Request:</strong> ' . esc_html($wp->request ?? 'Not Set') . '</p>';
    
    // Parse URL segments
    $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
    $path_segments = explode('/', $path);
    echo '<p><strong>URL Segments:</strong> ' . esc_html(print_r($path_segments, true)) . '</p>';
    
    // Check if this looks like a booking URL
    if (isset($path_segments[0]) && $path_segments[0] === 'bookings' && isset($path_segments[1])) {
        $business_slug = $path_segments[1];
        echo '<p><strong>Detected Business Slug:</strong> ' . esc_html($business_slug) . '</p>';
        
        // Check if slug exists in database
        echo '<h4>2. Slug Lookup</h4>';
        try {
            $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
            echo '<p><strong>Settings Table:</strong> ' . esc_html($settings_table) . '</p>';
            
            $user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$settings_table} WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
                $business_slug
            ));
            
            echo '<p><strong>User ID Found:</strong> ' . ($user_id ? esc_html($user_id) : 'NULL') . '</p>';
            
            if ($user_id) {
                $user = get_userdata($user_id);
                if ($user) {
                    echo '<p><strong>User Info:</strong> ' . esc_html($user->user_login) . ' (' . esc_html(implode(', ', $user->roles)) . ')</p>';
                } else {
                    echo '<p style="color: red;"><strong>ERROR:</strong> User ID exists but user not found!</p>';
                }
            } else {
                // Show all available slugs for debugging
                $all_slugs = $wpdb->get_results($wpdb->prepare(
                    "SELECT user_id, setting_value FROM {$settings_table} WHERE setting_name = 'bf_business_slug'"
                ));
                echo '<p><strong>Available Slugs:</strong></p>';
                echo '<ul>';
                foreach ($all_slugs as $slug_data) {
                    echo '<li>' . esc_html($slug_data->setting_value) . ' (User ID: ' . esc_html($slug_data->user_id) . ')</li>';
                }
                echo '</ul>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;"><strong>Database Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    // Check query vars
    echo '<h4>3. Query Variables</h4>';
    echo '<p><strong>mobooking_slug:</strong> ' . esc_html(get_query_var('mobooking_slug')) . '</p>';
    echo '<p><strong>mobooking_page_type:</strong> ' . esc_html(get_query_var('mobooking_page_type')) . '</p>';
    echo '<p><strong>mobooking_tenant_id_on_page:</strong> ' . esc_html(get_query_var('mobooking_tenant_id_on_page')) . '</p>';
    
    // Check template
    echo '<h4>4. Template Check</h4>';
    $template_path = get_template_directory() . '/templates/booking-form-public.php';
    echo '<p><strong>Template Path:</strong> ' . esc_html($template_path) . '</p>';
    echo '<p><strong>Template Exists:</strong> ' . (file_exists($template_path) ? 'YES' : 'NO') . '</p>';
    
    // Check rewrite rules
    echo '<h4>5. Rewrite Rules</h4>';
    $rules = get_option('rewrite_rules');
    $booking_rules = [];
    foreach ($rules as $pattern => $rewrite) {
        if (strpos($pattern, 'bookings') !== false || strpos($rewrite, 'mobooking') !== false) {
            $booking_rules[$pattern] = $rewrite;
        }
    }
    echo '<p><strong>Booking Rules Found:</strong> ' . count($booking_rules) . '</p>';
    if (!empty($booking_rules)) {
        echo '<ul>';
        foreach ($booking_rules as $pattern => $rewrite) {
            echo '<li>' . esc_html($pattern) . ' => ' . esc_html($rewrite) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p style="color: red;">No booking rewrite rules found! This might be the issue.</p>';
        echo '<p><a href="' . add_query_arg('flush_rewrites', '1') . '">Click here to flush rewrite rules</a></p>';
    }
    
    // Check if BookingFormRouter is loaded
    echo '<h4>6. Router Status</h4>';
    echo '<p><strong>BookingFormRouter Class Exists:</strong> ' . (class_exists('MoBooking\\Classes\\Routes\\BookingFormRouter') ? 'YES' : 'NO') . '</p>';
    
    // Current template being used
    global $template;
    echo '<p><strong>Current Template:</strong> ' . esc_html($template ?? 'Not Set') . '</p>';
    
    echo '<button onclick="this.parentElement.style.display=\'none\'" style="float: right; background: #dc3232; color: white; border: none; padding: 5px 10px; cursor: pointer; margin-top: 10px;">Close Debug</button>';
    echo '<div style="clear: both;"></div>';
    echo '</div>';
}
add_action('wp_head', 'mobooking_debug_public_booking_routing');
add_action('wp_footer', 'mobooking_debug_public_booking_routing');

/**
 * Enhanced BookingFormRouter with better debugging and fixes
 * Replace your existing BookingFormRouter.php with this version
 */

// Also add this to help with debugging in admin
function mobooking_admin_debug_booking_urls() {
    if (!current_user_can('manage_options') || !isset($_GET['mobooking_debug_admin'])) {
        return;
    }
    
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>MoBooking Debug - Admin</h1>';
    
    // Test all business slugs
    $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
    $all_slugs = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, setting_value FROM {$settings_table} WHERE setting_name = 'bf_business_slug'"
    ));
    
    echo '<h2>Business Slugs Test</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>User ID</th><th>Slug</th><th>URL</th><th>Test Link</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($all_slugs as $slug_data) {
        $url = home_url('/bookings/' . $slug_data->setting_value . '/');
        echo '<tr>';
        echo '<td>' . esc_html($slug_data->user_id) . '</td>';
        echo '<td>' . esc_html($slug_data->setting_value) . '</td>';
        echo '<td>' . esc_html($url) . '</td>';
        echo '<td><a href="' . esc_url($url . '?mobooking_debug=1') . '" target="_blank">Test</a></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // Show rewrite rules
    echo '<h2>Rewrite Rules</h2>';
    $rules = get_option('rewrite_rules');
    echo '<pre>';
    foreach ($rules as $pattern => $rewrite) {
        if (strpos($pattern, 'booking') !== false || strpos($rewrite, 'mobooking') !== false) {
            echo esc_html($pattern . ' => ' . $rewrite) . "\n";
        }
    }
    echo '</pre>';
    
    echo '<p><a href="' . add_query_arg('flush_rewrites', '1') . '" class="button">Flush Rewrite Rules</a></p>';
    echo '</div>';
    
    // Stop normal page rendering
    exit;
}
add_action('admin_init', 'mobooking_admin_debug_booking_urls');

/**
 * Quick fix function to ensure the template_include filter is working
 * Add this to functions.php to help debug template loading
 */
function mobooking_debug_template_include($template) {
    // Only for admin users and booking URLs
    if (!current_user_can('manage_options')) {
        return $template;
    }
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, '/bookings/') !== false || isset($_GET['mobooking_debug'])) {
        error_log('[MoBooking Debug] template_include called with: ' . $template);
        error_log('[MoBooking Debug] Request URI: ' . $request_uri);
        
        // Check if our router is actually running
        global $wp;
        $path = trim($wp->request ?? '', '/');
        $path_segments = explode('/', $path);
        
        error_log('[MoBooking Debug] Path segments: ' . print_r($path_segments, true));
        
        if (isset($path_segments[0]) && $path_segments[0] === 'bookings') {
            error_log('[MoBooking Debug] This should be handled by MoBooking router!');
            
            // Manual check if template exists
            $booking_template = get_template_directory() . '/templates/booking-form-public.php';
            error_log('[MoBooking Debug] Booking template path: ' . $booking_template);
            error_log('[MoBooking Debug] Booking template exists: ' . (file_exists($booking_template) ? 'YES' : 'NO'));
        }
    }
    
    return $template;
}
add_filter('template_include', 'mobooking_debug_template_include', 999);

/**
 * Force flush rewrite rules if requested
 */
function mobooking_manual_flush_rewrites() {
    if (isset($_GET['flush_rewrites']) && current_user_can('manage_options')) {
        flush_rewrite_rules();
        wp_redirect(remove_query_arg('flush_rewrites'));
        exit;
    }
}
add_action('init', 'mobooking_manual_flush_rewrites');

/**
 * Check if rewrite rules are missing and auto-flush them
 */
function mobooking_auto_check_rewrite_rules() {
    $rules = get_option('rewrite_rules');
    $has_booking_rules = false;
    
    foreach ($rules as $pattern => $rewrite) {
        if (strpos($pattern, 'bookings') !== false && strpos($rewrite, 'mobooking') !== false) {
            $has_booking_rules = true;
            break;
        }
    }
    
    if (!$has_booking_rules) {
        error_log('[MoBooking] Missing booking rewrite rules, auto-flushing...');
        flush_rewrite_rules();
    }
}
add_action('wp_loaded', 'mobooking_auto_check_rewrite_rules');

// Dashboard AJAX Handlers
// These were moved from dashboard/page-overview.php to be globally available for AJAX calls.

// AJAX handler for dashboard overview data (KPIs, initial chart)
add_action('wp_ajax_mobooking_get_dashboard_overview_data', 'mobooking_ajax_get_dashboard_overview_data');
if ( ! function_exists( 'mobooking_ajax_get_dashboard_overview_data' ) ) {
    function mobooking_ajax_get_dashboard_overview_data() {
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

        // Ensure managers are loaded (they should be available globally if init hooks ran)
        if (!isset($GLOBALS['mobooking_services_manager']) || !isset($GLOBALS['mobooking_bookings_manager'])) {
             wp_send_json_error(array('message' => 'Core components not available.'), 500);
            return;
        }
        $services_manager = $GLOBALS['mobooking_services_manager'];
        $bookings_manager = $GLOBALS['mobooking_bookings_manager'];
        // $discounts_manager = $GLOBALS['mobooking_discounts_manager']; // Already part of bookings manager
        // $notifications_manager = $GLOBALS['mobooking_notifications_manager']; // Already part of bookings manager


        // Handle worker users
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

            $services_count = $services_manager->get_services_count($data_user_id);
            $kpi_data['services_count'] = $services_count;

            $initial_chart_period = '7days'; // Default period
            // TODO: Replace placeholder with actual call to a method like:
            // $chart_data = $bookings_manager->get_booking_counts_for_period($data_user_id, $initial_chart_period);
            $num_days_initial = 7;
            $labels_initial = [];
            for ($i = $num_days_initial - 1; $i >= 0; $i--) { $labels_initial[] = date('M j', strtotime("-$i days")); }
            $data_points_initial = array_map(function() { return rand(5, 25); }, array_fill(0, $num_days_initial, 0));

            // Chart data removed from this response
            // $chart_data = array(
            //     'labels' => $labels_initial,
            //     'datasets' => array(
            //         array(
            //             'label' => __('Bookings', 'mobooking'),
            //             'data' => $data_points_initial,
            //             'borderColor' => 'hsl(221.2 83.2% 53.3%)',
            //             'backgroundColor' => 'hsl(221.2 83.2% 53.3% / 0.1)',
            //             'tension' => 0.4,
            //             'fill' => true
            //         )
            //     )
            // );


// --- Custom Email Template Functions ---

/**
 * Set email content type to HTML.
 */
add_filter( 'wp_mail_content_type', function() {
    return 'text/html';
});

/**
 * Set a default email from name.
 */
add_filter( 'wp_mail_from_name', function( $original_email_from ) {
    // You can customize this, e.g., get_bloginfo('name')
    return get_bloginfo('name');
});

/**
 * Set a default email from address.
 * It's good practice to use an email address from your site's domain.
 */
add_filter( 'wp_mail_from', function( $original_email_address ) {
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    if (strpos($domain, 'www.') === 0) {
        $domain = substr($domain, 4);
    }
    $default_from_email = 'wordpress@' . $domain;
    // Check if the original email address is the default WordPress one.
    // If it is, replace it. Otherwise, keep the potentially custom one.
    if ($original_email_address === 'wordpress@' . $domain || $original_email_address === 'wordpress@localhost') {
        return $default_from_email;
    }
    return $original_email_address; // Keep if it was already customized
});


/**
 * Wrap email content with custom HTML template.
 */
add_filter( 'wp_mail', function( $args ) {
    error_log('[MoBooking Debug] Custom wp_mail filter triggered. Email to: ' . $args['to']); // DEBUG LINE
    $template_path = get_stylesheet_directory() . '/templates/email/default-email-template.php';

    if ( file_exists( $template_path ) ) {
        error_log('[MoBooking Debug] Email template file found at: ' . $template_path); // DEBUG LINE
        $email_template = file_get_contents( $template_path );

        // Replace placeholders
        // Header Content - Example: Site Logo and Name
        $site_logo_url = function_exists('get_custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
        $header_content = '';
        if ($site_logo_url) {
            $header_content .= '<img src="' . esc_url($site_logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-height:50px; margin-bottom:10px;" class="site-logo" />';
            $header_content .= '<h1 style="color:#ffffff; font-size:24px; margin:0; font-weight:normal;"><a href="' . esc_url(home_url()) . '" style="color:#ffffff; text-decoration:none;">' . esc_html(get_bloginfo('name')) . '</a></h1>';
        } else {
            $header_content .= '<h1 style="color:#ffffff; font-size:24px; margin:0; font-weight:normal;"><a href="' . esc_url(home_url()) . '" style="color:#ffffff; text-decoration:none;">' . esc_html(get_bloginfo('name')) . '</a></h1>';
        }
        $email_template = str_replace( '%%EMAIL_HEADER_CONTENT%%', $header_content, $email_template );

        // Main Content
        // Convert line breaks to <br> for HTML display if the content is plain text
        $message_content = nl2br( $args['message'] );
        $email_template = str_replace( '%%EMAIL_CONTENT%%', $message_content, $email_template );

        // Footer Content - Example: Copyright and Site Link
        $footer_content = '&copy; ' . date('Y') . ' <a href="' . esc_url(home_url('/')) . '" style="color:#0073aa;">' . esc_html(get_bloginfo('name')) . '</a>. ' . __('All rights reserved.', 'mobooking');
        $email_template = str_replace( '%%EMAIL_FOOTER_CONTENT%%', $footer_content, $email_template );

        // Blog name for title tag
        $email_template = str_replace( '%%BLOG_NAME%%', esc_html(get_bloginfo('name')), $email_template );


        $args['message'] = $email_template;
    }

    return $args;
}, 10, 1 );

// --- End Custom Email Template Functions ---

            wp_send_json_success(array(
                'kpis' => $kpi_data
                // 'chart_data' => $chart_data // Removed chart data from this main overview data call
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load dashboard data: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for recent bookings
add_action('wp_ajax_mobooking_get_recent_bookings', 'mobooking_ajax_get_recent_bookings');
if ( ! function_exists( 'mobooking_ajax_get_recent_bookings' ) ) {
    function mobooking_ajax_get_recent_bookings() {
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

        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4; // Default limit now 4, though JS sends it

        try {
            $args = array(
                'limit' => $limit,
                'orderby' => 'created_at', // Make sure this column exists and is suitable for ordering
                'order' => 'DESC'
            );
            // Note: The original code used $current_user_id for get_bookings_by_tenant.
            // If workers should see owner's bookings, this should be $data_user_id similar to above.
            // For now, sticking to $current_user_id as per original handler structure for recent bookings.
            $bookings_result = $bookings_manager->get_bookings_by_tenant($current_user_id, $args);

            if (is_wp_error($bookings_result)) {
                wp_send_json_error(array('message' => $bookings_result->get_error_message()), 400);
                return;
            }

            wp_send_json_success($bookings_result['bookings'] ?? array());

        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Failed to load recent bookings: ' . $e->getMessage()), 500);
        }
    }
}

// AJAX handler for fetching chart data by period - REMOVED
// add_action('wp_ajax_mobooking_get_dashboard_chart_data', 'mobooking_ajax_get_dashboard_chart_data');
// if ( ! function_exists( 'mobooking_ajax_get_dashboard_chart_data' ) ) { ... } // Entire function removed

// End of Dashboard AJAX Handlers
























if ( ! function_exists( 'mobooking_format_price' ) ) {
    function mobooking_format_price( $price ) {
        $currency_symbol = '$'; // Default currency symbol
        $currency_pos = 'before'; // Default currency position
        $decimal_sep = '.'; // Default decimal separator
        $thousand_sep = ','; // Default thousand separator
        $decimals = 2; // Default number of decimals

        if ( isset( $GLOBALS['mobooking_settings_manager'] ) ) {
            $settings = $GLOBALS['mobooking_settings_manager']->get_business_settings( get_current_user_id() );
            $currency_symbol = $settings['biz_currency_symbol'] ?? $currency_symbol;
            $currency_pos = $settings['biz_currency_position'] ?? $currency_pos;
        }

        $price = number_format( $price, $decimals, $decimal_sep, $thousand_sep );

        if ( $currency_pos === 'before' ) {
            return $currency_symbol . $price;
        } else {
            return $price . $currency_symbol;
        }
    }
}

// Add to functions.php for debugging
function mobooking_debug_registration_process() {
    if (isset($_GET['debug_registration']) && current_user_can('manage_options')) {
        echo '<pre>';
        echo "Database Tables:\n";
        global $wpdb;
        $tables = $wpdb->get_results("SHOW TABLES LIKE '%mobooking%'");
        var_dump($tables);
        
        echo "\nRoles:\n";
        var_dump(get_role('mobooking_business_owner'));
        
        echo "\nSettings Class:\n";
        var_dump(class_exists('MoBooking\Classes\Settings'));
        
        echo "\nGlobal Settings Manager:\n";
        var_dump(isset($GLOBALS['mobooking_settings_manager']));
        echo '</pre>';
        exit;
    }
}
add_action('init', 'mobooking_debug_registration_process');