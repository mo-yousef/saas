<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.6' );
}
if ( ! defined( 'MOBOOKING_THEME_DIR' ) ) {
    define( 'MOBOOKING_THEME_DIR', trailingslashit( get_template_directory() ) );
}
if ( ! defined( 'MOBOOKING_THEME_URI' ) ) {
    define( 'MOBOOKING_THEME_URI', trailingslashit( get_template_directory_uri() ) );
}

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
function mobooking_scripts() {
    // Initialize variables to prevent undefined warnings if used before assignment in conditional blocks
    // $public_form_currency_symbol = '$'; // Default value - REMOVED
    // $public_form_currency_position = 'before'; // Default value - REMOVED
    $public_form_currency_code = 'USD'; // Default value

    // Enqueue CSS Reset first
    wp_enqueue_style( 'mobooking-reset', MOBOOKING_THEME_URI . 'assets/css/reset.css', array(), MOBOOKING_VERSION );

    // Enqueue main stylesheet, making it dependent on the reset
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), array('mobooking-reset'), MOBOOKING_VERSION );

    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') ) {
        wp_enqueue_script( 'mobooking-auth', MOBOOKING_THEME_URI . 'assets/js/auth.js', array( 'jquery' ), MOBOOKING_VERSION, true );
        wp_localize_script(
            'mobooking-auth',
            'mobooking_auth_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'login_nonce' => wp_create_nonce( MoBooking\Classes\Auth::LOGIN_NONCE_ACTION ),
                'register_nonce' => wp_create_nonce( MoBooking\Classes\Auth::REGISTER_NONCE_ACTION ),
            )
        );
    }

    // For Public Booking Form page (standard page template OR slug-based route)
    if ( is_page_template('templates/booking-form-public.php') || get_query_var('mobooking_page_type') === 'public_booking' ) {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('mobooking-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);

        $effective_tenant_id_for_public_form = 0;
        // Prioritize tenant_id set by the slug-based routing via query_var
        if (get_query_var('mobooking_page_type') === 'public_booking') {
            $effective_tenant_id_for_public_form = get_query_var('mobooking_tenant_id_on_page', 0);
            error_log('[MoBooking Scripts] Public booking form (slug route). Tenant ID from query_var mobooking_tenant_id_on_page: ' . $effective_tenant_id_for_public_form);
        }
        // Fallback to ?tid if not a slug route or if tenant_id_on_page wasn't set by slug logic (e.g. direct page template usage)
        if (empty($effective_tenant_id_for_public_form) && !empty($_GET['tid'])) {
            $effective_tenant_id_for_public_form = intval($_GET['tid']);
            error_log('[MoBooking Scripts] Public booking form (tid route). Tenant ID from $_GET[tid]: ' . $effective_tenant_id_for_public_form);
        }
        // If it's a direct page template assignment without slug/tid, $effective_tenant_id_for_public_form will be 0.
        // The JS should handle this (e.g. show an error or expect tenant_id to be entered manually if that was a feature).
        // Currently, the JS expects tenant_id to be available for most operations.

        if ($effective_tenant_id_for_public_form && isset($GLOBALS['mobooking_settings_manager'])) {
            $public_form_currency_code = $GLOBALS['mobooking_settings_manager']->get_setting($effective_tenant_id_for_public_form, 'biz_currency_code', 'USD');
        }
        error_log('[MoBooking Scripts] Effective Tenant ID for public form localization: ' . $effective_tenant_id_for_public_form);

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
            'select_one_service' => __('Please select at least one service to continue.', 'mobooking'),
            // Step 3
            'configure_options' => __('Configure Options for', 'mobooking'),
            'no_options_for_services' => __('No configurable options for the selected service(s).', 'mobooking'),
            'fill_required_options' => __('Please fill in all required options.', 'mobooking'),
            // Step 4
            'name_required' => __('Full name is required.', 'mobooking'),
            'email_required' => __('Email address is required.', 'mobooking'),
            'email_invalid' => __('Please enter a valid email address.', 'mobooking'),
            'phone_required' => __('Phone number is required.', 'mobooking'),
            'address_required' => __('Service address is required.', 'mobooking'),
            'date_required' => __('Preferred date is required.', 'mobooking'),
            'time_required' => __('Preferred time is required.', 'mobooking'),
            // Step 5 (Review)
            'customer_details' => __('Customer Details', 'mobooking'),
            'name_label' => __('Name', 'mobooking'),
            'email_label' => __('Email', 'mobooking'),
            'phone_label' => __('Phone', 'mobooking'),
            'address_label' => __('Service Address', 'mobooking'),
            'datetime_label' => __('Scheduled Date & Time', 'mobooking'),
            'instructions_label' => __('Special Instructions', 'mobooking'),
            'services_summary' => __('Services & Options Summary', 'mobooking'),
            'enter_discount_code' => __('Please enter a discount code.', 'mobooking'),
            'invalid_discount_code' => __('Invalid or expired discount code.', 'mobooking'),
            'discount_applied' => __('Discount applied:', 'mobooking'),
            'error_applying_discount' => __('Error applying discount.', 'mobooking'),
            'error_review_data_missing' => __('Some booking information is missing. Please go back and complete all steps.', 'mobooking'),
            'error_review_incomplete' => __('Booking data is incomplete. Please review previous steps.', 'mobooking'),
            // Step 6
            'processing_booking' => __('Processing your booking...', 'mobooking'),
            'error_booking_failed' => __('Could not create your booking. Please try again.', 'mobooking'),
            'error_booking_failed_ajax' => __('A network error occurred while creating your booking. Please try again.', 'mobooking'),
            'your_ref_is' => __('Your booking reference is:', 'mobooking'),
        ];

        wp_localize_script('mobooking-booking-form', 'mobooking_booking_form_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
            'tenant_id' => $tenant_id_on_page,
            // 'currency_symbol' => $public_form_currency_symbol, // REMOVED
            // 'currency_position' => $public_form_currency_position, // REMOVED
            'currency_code' => $public_form_currency_code,
            'i18n' => $i18n_strings
        ));
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

// Routing and Template Handling
function mobooking_flush_rewrite_rules_on_activation_deactivation() {
    mobooking_add_rewrite_rules(); // Renamed function
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation');
add_action('switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation');

function mobooking_add_rewrite_rules() { // Renamed function
    // Dashboard Rules
    add_rewrite_rule(
        '^dashboard/?$',
        'index.php?mobooking_dashboard_page=overview',
        'top'
    );
    add_rewrite_rule(
        '^dashboard/([^/]+)/?$',
        'index.php?mobooking_dashboard_page=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^dashboard/([^/]+)/([^/]+)/?$',
        'index.php?mobooking_dashboard_page=$matches[1]&mobooking_dashboard_action=$matches[2]',
        'top'
    );

    // Public Booking Form by Business Slug Rule
    add_rewrite_rule(
        '^([^/]+)/booking/?$', // Matches {slug}/booking/
        'index.php?mobooking_business_slug=$matches[1]&mobooking_page_type=public_booking',
        'top'
    );
}
add_action('init', 'mobooking_add_rewrite_rules'); // Renamed function

function mobooking_add_query_vars($vars) {
    $vars[] = 'mobooking_dashboard_page';
    $vars[] = 'mobooking_dashboard_action';
    $vars[] = 'mobooking_business_slug'; // New query var for business slug
    $vars[] = 'mobooking_page_type';     // New query var for page type (e.g., public_booking)
    return $vars;
}
add_filter('query_vars', 'mobooking_add_query_vars');

function mobooking_template_include_logic( $template ) {
    error_log('[MoBooking Debug] ====== New Request Processing in mobooking_template_include_logic ======');
    error_log('[MoBooking Debug] REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

    $page_type = get_query_var('mobooking_page_type');
    $dashboard_page_slug = get_query_var('mobooking_dashboard_page');
    $business_slug = get_query_var('mobooking_business_slug');

    error_log('[MoBooking Debug] Query Vars: page_type=' . $page_type . '; dashboard_page_slug=' . $dashboard_page_slug . '; business_slug=' . $business_slug);

    // --- Handle Public Booking Form by Slug ---
    if ($page_type === 'public_booking' && !empty($business_slug)) {
        error_log('[MoBooking Debug] Matched public_booking page type with slug: ' . $business_slug);
        $tenant_id = mobooking_get_user_id_by_slug($business_slug);

        if ($tenant_id) {
            error_log('[MoBooking Debug] Found tenant_id: ' . $tenant_id . ' for slug: ' . $business_slug);
            $GLOBALS['mobooking_public_form_tenant_id_from_slug'] = $tenant_id;
            
            // Set a query var that the original public booking form template might use or can be adapted to use.
            // This helps in case the template has logic relying on a query_var for tenant_id.
            set_query_var('mobooking_tenant_id_on_page', $tenant_id);

            $public_booking_template = MOBOOKING_THEME_DIR . 'templates/booking-form-public.php';
            if (file_exists($public_booking_template)) {
                error_log('[MoBooking Debug] Loading public booking form template: ' . $public_booking_template);
                // Enqueue scripts for public booking form
                // Note: mobooking_scripts() already has a condition for this template,
                // but we might need to ensure it runs correctly in this context.
                // For now, relying on the existing is_page_template check in mobooking_scripts might be tricky
                // as we are directly returning a template path.
                // Explicitly enqueue here or ensure mobooking_scripts() can detect this.
                // For simplicity, let's assume mobooking_scripts() needs adjustment or we enqueue here.
                // Let's try to make mobooking_scripts work by ensuring the query var is set.

                // For the `is_page_template('templates/booking-form-public.php')` check in `mobooking_scripts` to work,
                // we need to ensure that the global $wp_query->is_page_template flag is set, or we directly call
                // the script enqueueing logic. Setting `mobooking_tenant_id_on_page` query var is a step towards that.

                remove_filter('template_redirect', 'redirect_canonical'); // Important for custom URLs
                status_header(200);
                return $public_booking_template;
            } else {
                error_log('[MoBooking Debug] CRITICAL ERROR: Public booking form template file not found: ' . $public_booking_template);
                // Fall through to default template or 404
            }
        } else {
            error_log('[MoBooking Debug] No tenant_id found for slug: ' . $business_slug . '. Will proceed to 404.');
            // Let WordPress handle it as a 404 by not returning a template here and setting status.
            // global $wp_query;
            // $wp_query->set_404();
            // status_header(404);
            // return get_404_template(); // This might be too aggressive, let WP do its default.
            // Returning original template will likely lead to WP's 404 handling.
        }
    }
    // --- Handle Dashboard ---
    // This logic needs to be robust enough not to misinterpret parts of a business slug URL as a dashboard URL.
    // The rewrite rules are processed in order, 'top' means they are tried first.
    // If `mobooking_page_type` is not 'public_booking', then it might be a dashboard request or something else.
    // The original dashboard detection was based on `mobooking_dashboard_page` or URI segments.
    
    $is_dashboard_request = false;
    if (!empty($dashboard_page_slug)) { // Primarily rely on the query var from dashboard rewrite rules
        $is_dashboard_request = true;
        error_log('[MoBooking Debug] Detected dashboard from query_var "mobooking_dashboard_page": ' . $dashboard_page_slug);
    } else if (empty($page_type)) { // Only check URI segments if it's not a handled page_type (like public_booking)
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = explode('/', $path);
        if (isset($segments[0]) && strtolower($segments[0]) === 'dashboard') {
            $is_dashboard_request = true;
            $dashboard_page_slug = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : 'overview';
            set_query_var('mobooking_dashboard_page', $dashboard_page_slug); // Ensure query var is set for consistency
            error_log('[MoBooking Debug] Detected dashboard from URI segments. Page slug set to: ' . $dashboard_page_slug);
        }
    }


    if ($is_dashboard_request) {
        error_log('[MoBooking Debug] Processing as dashboard request for page: ' . $dashboard_page_slug);
        if (!is_user_logged_in() || !current_user_can('read')) {
            error_log('[MoBooking Debug] User not authenticated for dashboard access.');
            wp_redirect(wp_login_url(get_permalink())); // Redirect to login, then back to current URL
            exit;
        }

        $GLOBALS['mobooking_current_dashboard_view'] = $dashboard_page_slug;
        mobooking_enqueue_dashboard_scripts($dashboard_page_slug);

        $dashboard_shell_template = MOBOOKING_THEME_DIR . 'dashboard/dashboard-shell.php';
        if (file_exists($dashboard_shell_template)) {
            error_log('[MoBooking Debug] Loading dashboard shell: ' . $dashboard_shell_template);
            remove_filter('template_redirect', 'redirect_canonical');
            status_header(200);
            return $dashboard_shell_template;
        } else {
            error_log('[MoBooking Debug] CRITICAL ERROR: Dashboard shell file not found: ' . $dashboard_shell_template);
        }
    }
    
    // If neither public booking form by slug nor dashboard, return original template
    error_log('[MoBooking Debug] No specific MoBooking template matched. Returning original template: ' . $template);
    return $template;
}
add_filter( 'template_include', 'mobooking_template_include_logic', 99 );

// Function to handle script enqueuing (was mobooking_enqueue_dashboard_scripts)
// No changes needed to its definition, only to its invocation if necessary
function mobooking_enqueue_dashboard_scripts($current_page_slug) {
    // Ensure jQuery is always available for dashboard pages that might use it directly
    // or have inline scripts depending on it (like page-workers.php).
    wp_enqueue_script('jquery');

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
            'placeholder_image_url' => 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22150%22%20height%3D%22150%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20150%20150%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_17ea872690d%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_17ea872690d%22%3E%3Crect%20width%3D%22150%22%20height%3D%22150%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2250.00303268432617%22%20y%3D%2279.5%22%3E150x150%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E', // Fallback data URI
            'i18n' => [
                'confirm_delete_service' => __('Are you sure you want to delete this service and all its options? This cannot be undone.', 'mobooking'),
                'confirm_delete_option' => __('Are you sure you want to delete this option?', 'mobooking'),
                'no_options_yet' => __('No options added yet. Click "Add Option" to create one.', 'mobooking'),
                'error_deleting_service' => __('Error deleting service.', 'mobooking'),
                'add_new_service' => __('Add New Service', 'mobooking'),
                'edit_service' => __('Edit Service', 'mobooking'),
                'save_service_before_options' => __('Please save the service before managing its options.', 'mobooking'),
                'options_loaded_here' => __('Service options will be loaded and managed here once the service is saved.', 'mobooking'),
                'error_finding_service' => __('Error: Could not find service data. Please refresh.', 'mobooking'),
                'error_missing_service_id' => __('Error: Could not identify the service ID for editing.', 'mobooking'),
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

            ]
        ]);
        wp_localize_script('mobooking-dashboard-services', 'mobooking_services_params', $services_params);
    }
    
    // Specific to Bookings page
    if ($current_page_slug === 'bookings') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('mobooking-dashboard-bookings', MOBOOKING_THEME_URI . 'assets/js/dashboard-bookings.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);

        $booking_statuses_for_js = [ '' => __('All Statuses', 'mobooking')];
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'on-hold', 'processing'];
        foreach ($statuses as $status) {
            $booking_statuses_for_js[$status] = ucfirst($status);
        }

        $bookings_params = array_merge($dashboard_params, [
            // 'nonce' is already general from dashboard_params
            'statuses' => $booking_statuses_for_js,
            'i18n' => [
                'loading_bookings' => __('Loading bookings...', 'mobooking'),
                'no_bookings_found' => __('No bookings found.', 'mobooking'),
                'error_loading_bookings' => __('Error loading bookings.', 'mobooking'),
                'loading_details' => __('Loading details...', 'mobooking'),
                'error_loading_details' => __('Error loading booking details.', 'mobooking'),
                'none' => __('None', 'mobooking'),
                'no_items_in_booking' => __('No items were found in this booking.', 'mobooking'),
                'updating_status' => __('Updating status...', 'mobooking'),
                'error_status_update' => __('Could not update status. Please try again.', 'mobooking'),
                'error_status_update_ajax' => __('A network error occurred while updating status. Please try again.', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-bookings', 'mobooking_bookings_params', $bookings_params);
    }

    // Specific to Areas page
    if ($current_page_slug === 'areas') {
        wp_enqueue_script('mobooking-dashboard-areas', MOBOOKING_THEME_URI . 'assets/js/dashboard-areas.js', array('jquery'), MOBOOKING_VERSION, true);
        $areas_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading' => __('Loading areas...', 'mobooking'),
                'no_areas' => __('No service areas defined yet.', 'mobooking'),
                'error_loading' => __('Error loading areas.', 'mobooking'),
                'fields_required' => __('Country Code and ZIP Code are required.', 'mobooking'),
                'adding' => __('Adding...', 'mobooking'),
                'error_adding' => __('Error adding area.', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this service area?', 'mobooking'),
                'error_deleting' => __('Error deleting area.', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-areas', 'mobooking_areas_params', $areas_params);
    }

    // Specific to Discounts page
    if ($current_page_slug === 'discounts') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('mobooking-dashboard-discounts', MOBOOKING_THEME_URI . 'assets/js/dashboard-discounts.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);
        $discounts_params = array_merge($dashboard_params, [
            'types' => [
                'percentage' => __('Percentage', 'mobooking'),
                'fixed_amount' => __('Fixed Amount', 'mobooking'),
            ],
            'statuses' => [
                'active' => __('Active', 'mobooking'),
                'inactive' => __('Inactive', 'mobooking'),
                'expired' => __('Expired', 'mobooking'),
            ],
            'i18n' => [
                'loading' => __('Loading discount codes...', 'mobooking'),
                'no_discounts' => __('No discount codes found.', 'mobooking'),
                'error_loading' => __('Error loading discount codes.', 'mobooking'),
                'add_new_title' => __('Add New Discount Code', 'mobooking'),
                'edit_title' => __('Edit Discount Code', 'mobooking'),
                'saving' => __('Saving...', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this discount code?', 'mobooking'),
                'never' => __('Never', 'mobooking'),
                'unlimited' => __('Unlimited', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-discounts', 'mobooking_discounts_params', $discounts_params);
    }

    // Specific to Booking Form Settings page
    if ($current_page_slug === 'booking-form') {
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
                'save_success' => __('Settings saved successfully.', 'mobooking'),
                'error_saving' => __('Error saving settings.', 'mobooking'),
                'error_loading' => __('Error loading settings.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                'invalid_json' => __('Invalid JSON format in Business Hours.', 'mobooking'),
                'copied' => __('Copied!', 'mobooking'),
                'copy_failed' => __('Copy failed. Please try manually.', 'mobooking'),
                'booking_form_title' => __('Booking Form', 'mobooking'), // New i18n string
                'link_will_appear_here' => __('Link will appear here once slug is saved.', 'mobooking'), // New i18n string
                'embed_will_appear_here' => __('Embed code will appear here once slug is saved.', 'mobooking'), // New i18n string
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
        wp_enqueue_script('mobooking-dashboard-overview', MOBOOKING_THEME_URI . 'assets/js/dashboard-overview.js', array('jquery'), MOBOOKING_VERSION, true);
        $overview_params = array_merge($dashboard_params, [
            'i18n' => [
                'loading_data' => __('Loading dashboard data...', 'mobooking'),
                'error_loading_data' => __('Error loading overview data.', 'mobooking'),
                'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
            ]
        ]);
        wp_localize_script('mobooking-dashboard-overview', 'mobooking_overview_params', $overview_params);
    }

    // Specific to Availability page
    if ($current_page_slug === 'availability') {
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
            'manage_override_for' => __("Manage Override for:", 'mobooking'),
            'selected_date' => __("Selected Date:", 'mobooking'),
            'day_off' => __("Day Off", 'mobooking'),
            'custom_availability' => __("Custom Availability", 'mobooking'),
            'select_date_to_manage' => __('Select a date to manage overrides', 'mobooking'),
            'error_loading_overrides' => __("Error loading date overrides.", 'mobooking'),
            'start_end_time_required_override' => __("Start and End time are required for available overrides.", 'mobooking'),
            'start_time_before_end_override' => __("Start time must be before end time for overrides.", 'mobooking'),
            'error_saving_override' => __("Error saving override.", 'mobooking'),
            'confirm_delete_override' => __("Are you sure you want to delete the override for this date?", 'mobooking'),
            'error_no_override_to_delete' => __("No override selected to delete.", 'mobooking'),
            'error_deleting_override' => __("Error deleting override.", 'mobooking'),
            'capacity_must_be_positive' => __("Capacity must be a positive number.", "mobooking"),
            'mark_day_off' => __('Mark as Day Off', 'mobooking'),
            'set_as_working_day' => __('Set as Working Day', 'mobooking'),
            'day_marked_as_off' => __('This day is marked as off.', 'mobooking'),
            'no_slots_add_some' => __('No time slots. Add some below.', 'mobooking'),
            'no_active_slots_for_working_day' => __('No active slots for this working day. Add some or edit existing ones.', 'mobooking'),
            'confirm_mark_day_off' => __('Are you sure you want to mark this day as off? All existing slots for this day will be deactivated.', 'mobooking'),
            'confirm_set_working_day' => __('Are you sure you want to set this as a working day? You will need to add or activate time slots.', 'mobooking'),
            'error_updating_day_status' => __('Error updating day status.', 'mobooking'),
        ];
        $availability_params = array_merge($dashboard_params, ['i18n' => $availability_i18n_strings]);
        wp_localize_script('mobooking-dashboard-availability', 'mobooking_availability_params', $availability_params);
        // The JS side should refer to these as mobooking_availability_params.i18n.string_key
        // So, I will update the JS to use mobooking_availability_params.i18n instead of mobooking_t
    }

    // Specific to Workers page
    if ($current_page_slug === 'workers') {
        wp_enqueue_script('mobooking-dashboard-workers', MOBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array('jquery'), MOBOOKING_VERSION, true);
        $workers_params = array_merge($dashboard_params, [
            'i18n' => array(
                'error_occurred' => esc_js(__( 'An error occurred.', 'mobooking')),
                'error_unexpected' => esc_js(__( 'An unexpected error occurred. Please try again.', 'mobooking')),
                'confirm_revoke_access' => esc_js(__( "Are you sure you want to revoke this worker's access? This cannot be undone.", 'mobooking')),
                'revoking' => esc_js(__( 'Revoking...', 'mobooking')),
                'error_revoking_access' => esc_js(__( 'An error occurred while revoking access.', 'mobooking')),
                'revoke_access' => esc_js(__( 'Revoke Access', 'mobooking')),
                'changing_role' => esc_js(__( 'Changing...', 'mobooking')),
                'error_server' => esc_js(__( 'An unexpected server error occurred. Please try again.', 'mobooking')),
                'saving' => esc_js(__( 'Saving...', 'mobooking')),
            )
        ]);
        wp_localize_script('mobooking-dashboard-workers', 'mobooking_workers_params', $workers_params);
    }
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

// Function to get user ID by the new bf_business_slug setting
// (This function was already updated in a previous step, ensuring it's correct here)
if ( ! function_exists('mobooking_get_user_id_by_slug')) {
    function mobooking_get_user_id_by_slug(string $slug): ?int {
        if (empty($slug)) {
            error_log('[MoBooking Slug Lookup] Attempted lookup with empty slug.');
            return null;
        }
        global $wpdb;
        // Ensure the Database class is available for get_table_name
        if (!class_exists('MoBooking\Classes\Database')) {
            error_log('[MoBooking Slug Lookup] CRITICAL: MoBooking\Classes\Database class not found.');
            return null;
        }
        $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
        if (empty($settings_table)) {
            error_log('[MoBooking Slug Lookup] CRITICAL: Tenant settings table name is empty.');
            return null;
        }

        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM %i WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
            $settings_table, $slug
        ));

        if ($user_id) {
            // Verify the user actually has the business owner role (or is an admin)
            $user = get_userdata($user_id);
            if ($user && (in_array(MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER, (array)$user->roles) || user_can($user, 'manage_options'))) {
                error_log('[MoBooking Slug Lookup] Found user_id: ' . $user_id . ' for slug: ' . $slug);
                return (int) $user_id;
            } else {
                error_log('[MoBooking Slug Lookup] User ID ' . $user_id . ' found for slug ' . $slug . ', but user is not a business owner or admin.');
            }
        } else {
            error_log('[MoBooking Slug Lookup] No user_id found for slug: ' . $slug . ' in table ' . $settings_table);
        }
        return null;
    }
}


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
            // 'availability' icon from HTML seems to be 'BookOpen', let's map it if there's an 'availability' page.
            // The sidebar.php doesn't show a separate 'availability' link, so I'll omit this for now unless a corresponding menu item is found.
            'services' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><rect width="20" height="14" x="2" y="6" rx="2"></rect></svg>',
            'discounts' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>',
            // 'clients' icon from HTML uses 'lucide-users'. The sidebar.php doesn't have a 'Clients' link.
            'availability' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>', // Calendar icon
            // 'my_profile' icon from HTML also uses 'lucide-users'. There isn't a 'My Profile' link in sidebar.php, but 'Settings' might cover it.
            'areas' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"></path><path d="M15 5.764v15"></path><path d="M9 3.236v15"></path></svg>',
            'workers' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>', // Using the 'lucide-users' icon for Workers as it's staff related.
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        ];
    }
    return $icons[$key] ?? '';
}