<?php
/**
 * MoBooking functions and definitions
 *
 * @package MoBooking
 */

if ( ! defined( 'MOBOOKING_VERSION' ) ) {
    define( 'MOBOOKING_VERSION', '0.1.0' );
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
    $public_form_currency_symbol = '$'; // Default value
    $public_form_currency_position = 'before'; // Default value

    // Enqueue CSS Reset first
    wp_enqueue_style( 'mobooking-reset', MOBOOKING_THEME_URI . 'assets/css/reset.css', array(), MOBOOKING_VERSION );

    // Enqueue main stylesheet, making it dependent on the reset
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), array('mobooking-reset'), MOBOOKING_VERSION );
    // wp_enqueue_script( 'mobooking-navigation', MOBOOKING_THEME_URI . 'js/navigation.js', array(), MOBOOKING_VERSION, true );

    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') ) { // Assuming page-register.php for future
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

    // For Public Booking Form page
    if ( is_page_template('templates/booking-form-public.php') ) {
        wp_enqueue_script('mobooking-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery'), MOBOOKING_VERSION, true);

        $tenant_id_on_page = get_query_var('mobooking_tenant_id_on_page', 0);

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
            'option_required_prefix' => __('Option', 'mobooking'),
            'option_required_suffix' => __('is required', 'mobooking'),
            'for_service' => __('for service', 'mobooking'),
            'invalid_json_for_option' => __('Invalid JSON format in Option Values for:', 'mobooking'),
            'no_options_prompt' => __('This service has no additional options to configure.', 'mobooking'), // Adjusted from 'Click "Add Option"...'
            // Step 4
            'name_required' => __('Full name is required.', 'mobooking'),
            'email_required' => __('Email address is required.', 'mobooking'),
            'email_invalid' => __('Please enter a valid email address.', 'mobooking'),
            'phone_required' => __('Phone number is required.', 'mobooking'),
            'address_required' => __('Service address is required.', 'mobooking'),
            'date_required' => __('Preferred date is required.', 'mobooking'),
            'time_required' => __('Preferred time is required.', 'mobooking'),
            // Step 5
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
            'discount_applied' => __('Discount applied', 'mobooking'),
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
            'currency_symbol' => $public_form_currency_symbol, // Added in previous step
            'currency_position' => $public_form_currency_position, // Added in previous step
            'i18n' => $i18n_strings
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'mobooking_scripts' );


// Initialize Authentication
if ( class_exists( 'MoBooking\Classes\Auth' ) ) {
    $mobooking_auth = new MoBooking\Classes\Auth();
    $mobooking_auth->init_ajax_handlers(); // Set up AJAX listeners

    // Role creation/removal on theme activation/deactivation
    add_action( 'after_switch_theme', array( 'MoBooking\Classes\Auth', 'add_business_owner_role' ) );
    add_action( 'switch_theme', array( 'MoBooking\Classes\Auth', 'remove_business_owner_role' ) );
}

// Initialize Database and create tables on activation
if ( class_exists( 'MoBooking\Classes\Database' ) ) {
    // The Database class constructor itself doesn't do anything in this setup
    // The create_tables method is static and called directly.
    add_action( 'after_switch_theme', array( 'MoBooking\Classes\Database', 'create_tables' ) );
}

// Initialize Services Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Services')) {
    // Making it global to ensure it's instantiated once and accessible for AJAX hooks
    // A more robust solution might use a service container or a singleton pattern.
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
// This needs Discounts, Notifications, and Services managers, so instantiate after them.
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

// Ensure Business Owner Role exists on init
function mobooking_ensure_business_owner_role_exists() {
    if (class_exists('MoBooking\Classes\Auth')) {
        if ( !get_role( MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER ) ) {
            MoBooking\Classes\Auth::add_business_owner_role();
            // Add an admin notice that the role was re-created.
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('MoBooking: The "Business Owner" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'mobooking_ensure_business_owner_role_exists' );

// Ensure Custom Database Tables exist on admin_init
function mobooking_ensure_custom_tables_exist() {
    if (is_admin() && class_exists('MoBooking\Classes\Database')) {
        global $wpdb;
        // Use a key table name that your theme relies on.
        // Using Database::get_table_name() is good practice.
        $services_table_name = \MoBooking\Classes\Database::get_table_name('services');

        // $wpdb->get_var returns NULL if table doesn't exist.
        // Need to compare with the actual table name string.
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $services_table_name)) != $services_table_name) {
            error_log('[MoBooking DB Debug] Key table ' . $services_table_name . ' not found during admin_init check. Forcing table creation.');
            \MoBooking\Classes\Database::create_tables(); // Attempt to create/update all tables

            // Add an admin notice to inform the admin that tables were (re)created.
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>' .
                     esc_html__('MoBooking: Core database tables were missing and an attempt was made to create them. Please verify their integrity or contact support if issues persist.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'admin_init', 'mobooking_ensure_custom_tables_exist' );


// Custom Class Autoloader
spl_autoload_register(function ($class_name) {
    // Check if the class belongs to our theme's namespace
    if (strpos($class_name, 'MoBooking\\') !== 0) {
        return false; // Not our class, skip
    }

    // Remove the root namespace prefix 'MoBooking\'
    // Example: 'MoBooking\Classes\Services' becomes 'Classes\Services'
    $relative_class_name = substr($class_name, strlen('MoBooking\\'));

    // Convert namespace separators to directory separators
    // Example: 'Classes\Services' becomes 'Classes/Services'
    // Example: 'Classes\Payments\Manager' becomes 'Classes/Payments/Manager'
    $file_path_part = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class_name);

    // Prepend the theme directory and append .php extension
    // Example: MOBOOKING_THEME_DIR . 'classes/Services.php'
    $file = MOBOOKING_THEME_DIR . $file_path_part . '.php';

    // Check if the file exists (case-sensitive check on Linux/macOS)
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Placeholder for screenshot.png (actual image to be added manually later)
// For now, just acknowledge it's part of the plan.
// touch wp-content/themes/mobooking/screenshot.png


// Dashboard Routing and Template Handling

function mobooking_flush_rewrite_rules_on_activation_deactivation() {
    // Call add_dashboard_rewrite_rules to ensure they are present before flushing on activation
    // On deactivation, they won't be added by the deactivated theme's init, so flush clears them.
    mobooking_add_dashboard_rewrite_rules(); // Ensure rules are defined for activation flush
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation');
add_action('switch_theme', 'mobooking_flush_rewrite_rules_on_activation_deactivation'); // Flush on deactivation too


function mobooking_add_dashboard_rewrite_rules() {
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
}
add_action('init', 'mobooking_add_dashboard_rewrite_rules');

function mobooking_add_query_vars($vars) {
    $vars[] = 'mobooking_dashboard_page';
    $vars[] = 'mobooking_dashboard_action';
    return $vars;
}
add_filter('query_vars', 'mobooking_add_query_vars');

function mobooking_dashboard_template_include( $template ) {
    error_log('[MoBooking Debug] ====== New Request ======');
    error_log('[MoBooking Debug] REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

    $is_dashboard_request = false;
    $current_page_slug = get_query_var('mobooking_dashboard_page');
    error_log('[MoBooking Debug] Initial query_var "mobooking_dashboard_page": ' . print_r($current_page_slug, true));

    if (!empty($current_page_slug)) {
        $is_dashboard_request = true;
        error_log('[MoBooking Debug] Detected dashboard from query_var.');
    } else {
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = explode('/', $path);
        error_log('[MoBooking Debug] Path segments from URI: ' . print_r($segments, true));

        if (isset($segments[0]) && strtolower($segments[0]) === 'dashboard') { // Case-insensitive check
            $is_dashboard_request = true;
            // If the base is 'dashboard', subsequent segments determine the page.
            // $matches[1] from rewrite rule corresponds to $segments[1] here if rule is simple.
            // $current_page_slug was for query_var, now set it from segment.
            $current_page_slug = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : 'overview';
            error_log('[MoBooking Debug] Detected dashboard from URI. Page slug determined as: ' . $current_page_slug);
        }
    }

    error_log('[MoBooking Debug] Is dashboard request? ' . ($is_dashboard_request ? 'Yes' : 'No'));

    if ( $is_dashboard_request ) {
        if ( !is_user_logged_in() ) {
            error_log('[MoBooking Debug] User not logged in. Redirecting to login.');
            wp_redirect( home_url( '/login/' ) );
            exit;
        }

        $user = wp_get_current_user();
        $user_roles_string = !empty($user->roles) ? implode(', ', $user->roles) : 'No roles';
        error_log('[MoBooking Debug] Current user ID: ' . $user->ID . ', Roles: ' . $user_roles_string);

        if ( !in_array( \MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER, (array) $user->roles ) ) {
            error_log('[MoBooking Debug] User ID ' . $user->ID . ' does NOT have ROLE_BUSINESS_OWNER. Redirecting to home.');
            wp_redirect( home_url( '/' ) );
            exit;
        }

        // Define allowed dashboard pages to prevent arbitrary file inclusion.
        $allowed_pages = ['overview', 'bookings', 'services', 'discounts', 'areas', 'booking-form', 'settings'];
        if (!in_array($current_page_slug, $allowed_pages)) {
            error_log('[MoBooking Debug] Page slug "' . $current_page_slug . '" not in allowed_pages. Defaulting to overview.');
            $current_page_slug = 'overview';
        }

        // Set a global variable that can be used by dashboard components
        $GLOBALS['mobooking_current_dashboard_view'] = $current_page_slug;
        error_log('[MoBooking Debug] Current dashboard view global set to: ' . $current_page_slug);

        $new_template = MOBOOKING_THEME_DIR . 'dashboard/dashboard-shell.php';
        if ( file_exists( $new_template ) ) {
            error_log('[MoBooking Debug] Loading dashboard shell: ' . $new_template);
            remove_filter('template_redirect', 'redirect_canonical');
            status_header(200);
            return $new_template;
        } else {
            error_log('[MoBooking Debug] CRITICAL ERROR: Dashboard shell file not found: ' . $new_template);
        }
    }
    // error_log('[MoBooking Debug] Returning original template: ' . $template); // This can be very verbose
    return $template;
}
add_filter( 'template_include', 'mobooking_dashboard_template_include', 99 ); // High priority

// Enqueue dashboard specific scripts or styles if needed
function mobooking_dashboard_scripts_styles() {
    // Check if we are on a dashboard page using the global var
    if (isset($GLOBALS['mobooking_current_dashboard_view'])) {
        // Enqueue scripts/styles common to all dashboard pages
        // wp_enqueue_style('mobooking-dashboard-common', MOBOOKING_THEME_URI . 'assets/css/dashboard-common.css', array(), MOBOOKING_VERSION);

        // Specific to Services page
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'services') {
            wp_enqueue_script('mobooking-dashboard-services', MOBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery'), MOBOOKING_VERSION, true);
            wp_localize_script('mobooking-dashboard-services', 'mobooking_services_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_services_nonce'),
                'i18n' => [
                    'loading_services' => __('Loading services...', 'mobooking'),
                    'no_services_found' => __('No services found. Click "Add New Service" to get started.', 'mobooking'),
                    'error_loading_services' => __('Error loading services.', 'mobooking'),
                    'confirm_delete_service' => __('Are you sure you want to delete this service and all its options? This cannot be undone.', 'mobooking'),
                    'error_deleting_service' => __('Error deleting service.', 'mobooking'),
                    'add_new_service' => __('Add New Service', 'mobooking'),
                    'edit_service' => __('Edit Service', 'mobooking'),
                    'save_service_before_options' => __('Please save the service before managing its options.', 'mobooking'),
                    'options_loaded_here' => __('Service options will be loaded and managed here once the service is saved.', 'mobooking'),
                    'error_finding_service' => __('Error: Could not find service data. Please refresh.', 'mobooking'),
                    'name_required' => __('Service name is required.', 'mobooking'),
                    'valid_price_required' => __('A valid, non-negative price is required.', 'mobooking'),
                    'valid_duration_required' => __('A valid, positive duration in minutes is required.', 'mobooking'),
                    'saving' => __('Saving...', 'mobooking'),
                    'error_saving_service' => __('Error saving service. Please check your input and try again.', 'mobooking'),
                ]
            ));
        }
        // Specific to Areas page
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'areas') {
            wp_enqueue_script('mobooking-dashboard-areas', MOBOOKING_THEME_URI . 'assets/js/dashboard-areas.js', array('jquery'), MOBOOKING_VERSION, true);
            wp_localize_script('mobooking-dashboard-areas', 'mobooking_areas_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
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
            ));
        }
        // Specific to Discounts page (Dashboard)
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'discounts') {
            wp_enqueue_script('jquery-ui-datepicker'); // For expiry date field
            wp_enqueue_script('mobooking-dashboard-discounts', MOBOOKING_THEME_URI . 'assets/js/dashboard-discounts.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);
            wp_localize_script('mobooking-dashboard-discounts', 'mobooking_discounts_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'types' => [ // For display in JS
                    'percentage' => __('Percentage', 'mobooking'),
                    'fixed_amount' => __('Fixed Amount', 'mobooking'),
                ],
                'statuses' => [ // For display in JS
                    'active' => __('Active', 'mobooking'),
                    'inactive' => __('Inactive', 'mobooking'),
                    'expired' => __('Expired', 'mobooking'), // JS might not set this, but good for display consistency
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
            ));
        }
        // Specific to Bookings page (Dashboard)
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'bookings') {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('mobooking-dashboard-bookings', MOBOOKING_THEME_URI . 'assets/js/dashboard-bookings.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);

            // Prepare statuses for JS
            $booking_statuses_for_js = [ '' => __('All Statuses', 'mobooking')];
            // This list should ideally match the one in page-bookings.php or come from a central config
             $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'on-hold', 'processing'];
             foreach ($statuses as $status) {
                 $booking_statuses_for_js[$status] = ucfirst($status); // Simple ucfirst, better to use __()
             }

            wp_localize_script('mobooking-dashboard-bookings', 'mobooking_bookings_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'), // General dashboard nonce
                'statuses' => $booking_statuses_for_js,
                'i18n' => [
                    'loading_bookings' => __('Loading bookings...', 'mobooking'),
                    'no_bookings_found' => __('No bookings found.', 'mobooking'),
                    'error_loading_bookings' => __('Error loading bookings.', 'mobooking'),
                    'loading_details' => __('Loading details...', 'mobooking'),
                    'error_loading_details' => __('Error loading booking details.', 'mobooking'),
                    'none' => __('None', 'mobooking'), // For empty special instructions in modal
                    'no_items_in_booking' => __('No items were found in this booking.', 'mobooking'),
                    'updating_status' => __('Updating status...', 'mobooking'),
                    'error_status_update' => __('Could not update status. Please try again.', 'mobooking'),
                    'error_status_update_ajax' => __('A network error occurred while updating status. Please try again.', 'mobooking'),
                ]
            ));
        }
        // Specific to Booking Form Settings page (Dashboard)
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'booking-form') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script(
                'mobooking-dashboard-booking-form-settings',
                MOBOOKING_THEME_URI . 'assets/js/dashboard-booking-form-settings.js',
                array('jquery', 'wp-color-picker'),
                MOBOOKING_VERSION,
                true
            );
            wp_localize_script('mobooking-dashboard-booking-form-settings', 'mobooking_bf_settings_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'i18n' => [
                    'saving' => __('Saving...', 'mobooking'),
                    'save_success' => __('Settings saved successfully.', 'mobooking'),
                    'error_saving' => __('Error saving settings.', 'mobooking'),
                    'error_loading' => __('Error loading settings.', 'mobooking'),
                    'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                    'invalid_json' => __('Invalid JSON format in Business Hours.', 'mobooking'), // This i18n key might be more relevant for Business Settings
                ]
            ));
        }
        // Specific to Business Settings page (Dashboard) - this is 'settings' view key
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'settings') {
             wp_enqueue_script(
                'mobooking-dashboard-business-settings',
                MOBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js',
                array('jquery'),
                MOBOOKING_VERSION,
                true
            );
            wp_localize_script('mobooking-dashboard-business-settings', 'mobooking_biz_settings_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'i18n' => [
                    'saving' => __('Saving...', 'mobooking'),
                    'save_success' => __('Business settings saved successfully.', 'mobooking'),
                    'error_saving' => __('Error saving business settings.', 'mobooking'),
                    'error_loading' => __('Error loading business settings.', 'mobooking'),
                    'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                    'invalid_json' => __('Business Hours JSON is not valid.', 'mobooking'), // Specific to this page
                ]
            ));
        }
        // Specific to Overview page (Dashboard)
        if ($GLOBALS['mobooking_current_dashboard_view'] === 'overview') {
            wp_enqueue_script(
                'mobooking-dashboard-overview',
                MOBOOKING_THEME_URI . 'assets/js/dashboard-overview.js',
                array('jquery'),
                MOBOOKING_VERSION,
                true
            );
            // Prepare statuses for JS, similar to bookings page if needed for display
            $booking_statuses_for_js = [ '' => __('All Statuses', 'mobooking')];
            $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'on-hold', 'processing'];
            foreach ($statuses as $status) {
                $booking_statuses_for_js[$status] = ucfirst($status);
            }
            $current_user_id = get_current_user_id();
            $settings_manager_for_currency = isset($GLOBALS['mobooking_settings_manager']) ? $GLOBALS['mobooking_settings_manager'] : null;
            $currency_symbol = '$'; // Default
            $currency_position = 'before'; // Default
            if ($settings_manager_for_currency && $current_user_id) {
                $currency_symbol = $settings_manager_for_currency->get_setting($current_user_id, 'biz_currency_symbol', '$');
                $currency_position = $settings_manager_for_currency->get_setting($current_user_id, 'biz_currency_position', 'before');
            }

            wp_localize_script('mobooking-dashboard-overview', 'mobooking_overview_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'currency_symbol' => $currency_symbol,
                'currency_position' => $currency_position,
                'statuses' => $booking_statuses_for_js,
                'i18n' => [
                    'loading_data' => __('Loading overview data...', 'mobooking'),
                    'no_recent_bookings' => __('No recent bookings to display.', 'mobooking'),
                    'error_loading_data' => __('Error loading overview data.', 'mobooking'),
                    'error_ajax' => __('An AJAX error occurred.', 'mobooking'),
                ]
            ));
        }
        // Add other page-specific scripts here
    }

    // For Public Booking Form page
    if ( is_page_template('templates/booking-form-public.php') ) {
        wp_enqueue_script('jquery-ui-datepicker'); // Enqueue jQuery UI Datepicker
        // Optionally enqueue a jQuery UI theme if not loaded by default or if specific theme needed
        // Example: wp_enqueue_style('jquery-ui-theme', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');

        wp_enqueue_script('mobooking-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);

        // Try to get tenant_id from query var if set by a shortcode or other server-side logic for the page
        // For now, JS primarily uses URL param 'tid'. This is a fallback or alternative.
        $tenant_id_on_page = get_query_var('mobooking_tenant_id_on_page', 0);
        // If tenant_id is determined by URL param 'tid', JS handles it.
        // If this page template is used for a specific tenant (e.g. via shortcode), $tenant_id_on_page should be set.
        // For currency on public form, it must be based on the tenant being booked.
        // This requires tenant_id to be known when localizing.

        $public_form_currency_symbol = '$';
        $public_form_currency_position = 'before';
        $effective_tenant_id_for_public_form = 0;

        // Try to get tenant_id from query var first for public form context
        if (!empty($_GET['tid'])) { // If 'tid' is in URL, it's the primary source
            $effective_tenant_id_for_public_form = intval($_GET['tid']);
        } elseif ($tenant_id_on_page) { // Fallback to query_var if set by other means
            $effective_tenant_id_for_public_form = intval($tenant_id_on_page);
        }

        if ($effective_tenant_id_for_public_form && isset($GLOBALS['mobooking_settings_manager'])) {
            $public_form_currency_symbol = $GLOBALS['mobooking_settings_manager']->get_setting($effective_tenant_id_for_public_form, 'biz_currency_symbol', '$');
            $public_form_currency_position = $GLOBALS['mobooking_settings_manager']->get_setting($effective_tenant_id_for_public_form, 'biz_currency_position', 'before');
        }


        wp_localize_script('mobooking-booking-form', 'mobooking_booking_form_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
            'tenant_id' => $tenant_id_on_page,
            'currency_symbol' => $public_form_currency_symbol,
            'currency_position' => $public_form_currency_position,
            'i18n' => [
                'zip_required' => __('Please enter your ZIP code.', 'mobooking'),
                'country_code_required' => __('Please enter your Country Code.', 'mobooking'),
                'tenant_id_missing' => __('Business identifier is missing. Cannot check availability.', 'mobooking'),
                'tenant_id_missing_refresh' => __('Business ID is missing. Please refresh and try again or contact support.', 'mobooking'),
                'checking' => __('Checking...', 'mobooking'),
                'error_generic' => __('An unexpected error occurred. Please try again.', 'mobooking'),
                'loading_services' => __('Loading services...', 'mobooking'),
                'no_services_available' => __('No services are currently available for this area. Please try another location or check back later.', 'mobooking'),
                'error_loading_services' => __('Could not load services. Please try again.', 'mobooking'),
                'select_one_service' => __('Please select at least one service to continue.', 'mobooking'),
                // New i18n strings for Step 3 (Options)
                'configure_options' => __('Configure Options for', 'mobooking'),
                'no_options_for_services' => __('No configurable options for the selected service(s).', 'mobooking'),
                'fill_required_options' => __('Please fill in all required options.', 'mobooking'),
                'option_required_prefix' => __('Option', 'mobooking'),
                'option_required_suffix' => __('is required', 'mobooking'),
                'for_service' => __('for service', 'mobooking'),
                'invalid_json_for_option' => __('Invalid JSON format in Option Values for:', 'mobooking'),
                'no_options_prompt' => __('Click "Add Option" to create service options.', 'mobooking'),
                // New i18n strings for Step 4 (Customer Details)
                'name_required' => __('Full name is required.', 'mobooking'),
                'email_required' => __('Email address is required.', 'mobooking'),
                'email_invalid' => __('Please enter a valid email address.', 'mobooking'),
                'phone_required' => __('Phone number is required.', 'mobooking'),
                'address_required' => __('Service address is required.', 'mobooking'),
                'date_required' => __('Preferred date is required.', 'mobooking'),
                'time_required' => __('Preferred time is required.', 'mobooking'),
                // New i18n strings for Step 5 (Review)
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
                 // Step 6 (Confirmation)
                'processing_booking' => __('Processing your booking...', 'mobooking'),
                'error_booking_failed' => __('Could not create your booking. Please try again.', 'mobooking'),
                'error_booking_failed_ajax' => __('An network error occurred while creating your booking. Please try again.', 'mobooking'),
                'your_ref_is' => __('Your booking reference is:', 'mobooking'),
            ]
        ));
    }
}
// Note: mobooking_scripts now handles general frontend scripts,
// mobooking_dashboard_scripts_styles handles dashboard specific scripts.
// Both are hooked to wp_enqueue_scripts. This is fine.
add_action('wp_enqueue_scripts', 'mobooking_scripts');
add_action('wp_enqueue_scripts', 'mobooking_dashboard_scripts_styles');

?>
