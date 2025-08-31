<?php
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

/**
 * Creates the necessary authentication pages when the theme is activated.
 */
function mobooking_create_authentication_pages() {
    $pages = array(
        array(
            'slug' => 'login',
            'title' => 'Login',
            'template' => 'page-login.php',
        ),
        array(
            'slug' => 'register',
            'title' => 'Register',
            'template' => 'page-register.php',
        ),
        array(
            'slug' => 'forgot-password',
            'title' => 'Forgot Password',
            'template' => 'page-forgot-password.php',
        ),
    );

    foreach ( $pages as $page ) {
        // Check if the page already exists
        if ( ! get_page_by_path( $page['slug'] ) ) {
            $page_data = array(
                'post_title'    => $page['title'],
                'post_name'     => $page['slug'],
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'page_template' => $page['template'],
            );

            // Insert the page into the database
            wp_insert_post( $page_data );
        }
    }
}
add_action( 'after_switch_theme', 'mobooking_create_authentication_pages' );

// Initialize Services Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Services')) {
    if (!isset($GLOBALS['mobooking_services_manager'])) {
        $GLOBALS['mobooking_services_manager'] = new MoBooking\Classes\Services();
        $GLOBALS['mobooking_services_manager']->register_actions();
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

// Initialize Customers Manager and register its AJAX actions
if (class_exists('MoBooking\Classes\Customers')) {
    if (!isset($GLOBALS['mobooking_customers_manager'])) {
        $GLOBALS['mobooking_customers_manager'] = new MoBooking\Classes\Customers();
        if (method_exists($GLOBALS['mobooking_customers_manager'], 'register_ajax_actions')) {
            $GLOBALS['mobooking_customers_manager']->register_ajax_actions();
        }
    }
}

// Also add this check to ensure all required globals exist before initializing bookings
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

        if (method_exists($GLOBALS['mobooking_bookings_manager'], 'register_ajax_actions')) {
            $GLOBALS['mobooking_bookings_manager']->register_ajax_actions();
        }
    }
} else {
    error_log('MoBooking: Required managers not available for Bookings initialization');
}


// Register Admin Pages
if ( class_exists( 'MoBooking\Classes\Admin\UserManagementPage' ) ) {
    add_action( 'admin_menu', array( 'MoBooking\Classes\Admin\UserManagementPage', 'register_page' ) );
}

if ( class_exists( 'MoBooking\Classes\Database' ) ) {
    add_action( 'admin_menu', array( 'MoBooking\Classes\Database', 'register_diagnostic_page' ) );
}

// Initialize Public Booking Form AJAX and its dependencies
if (class_exists('MoBooking\Classes\BookingFormAjax')) {
    if (!isset($GLOBALS['mobooking_booking_form_ajax'])) {
        $GLOBALS['mobooking_booking_form_ajax'] = new \MoBooking\Classes\BookingFormAjax();
        $GLOBALS['mobooking_booking_form_ajax']->register_ajax_actions();
    }
}

// Initialize Service Options AJAX
if (class_exists('MoBooking\Classes\ServiceOptionsAjax')) {
    if (!isset($GLOBALS['mobooking_service_options_ajax'])) {
        $GLOBALS['mobooking_service_options_ajax'] = new \MoBooking\Classes\ServiceOptionsAjax();
        $GLOBALS['mobooking_service_options_ajax']->register_ajax_actions();
    }
}

// Initialize Availability AJAX
if (class_exists('MoBooking\Classes\AvailabilityAjax')) {
    if (!isset($GLOBALS['mobooking_availability_ajax'])) {
        $GLOBALS['mobooking_availability_ajax'] = new \MoBooking\Classes\AvailabilityAjax();
        $GLOBALS['mobooking_availability_ajax']->register_ajax_actions();
    }
}
?>
