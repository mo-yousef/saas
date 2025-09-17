<?php
// Initialize Authentication
if ( class_exists( 'NORDBOOKING\Classes\Auth' ) ) {
    $nordbooking_auth = new NORDBOOKING\Classes\Auth();
    $nordbooking_auth->init_ajax_handlers();

    // Role creation/removal on theme activation/deactivation
    add_action( 'after_switch_theme', array( 'NORDBOOKING\Classes\Auth', 'add_business_owner_role' ) );
    add_action( 'switch_theme', array( 'NORDBOOKING\Classes\Auth', 'remove_business_owner_role' ) );

    // Worker roles management
    add_action( 'after_switch_theme', array( 'NORDBOOKING\Classes\Auth', 'add_worker_roles' ) );
    add_action( 'switch_theme', array( 'NORDBOOKING\Classes\Auth', 'remove_worker_roles' ) );
}

// Initialize Database and create tables on activation
if ( class_exists( 'NORDBOOKING\Classes\Database' ) ) {
    add_action( 'after_switch_theme', array( 'NORDBOOKING\Classes\Database', 'create_tables' ) );
}

// Initialize Subscription and create table on activation
if ( class_exists( 'NORDBOOKING\Classes\Subscription' ) ) {
    add_action( 'after_switch_theme', array( 'NORDBOOKING\Classes\Subscription', 'install' ) );
    add_action( 'after_switch_theme', array( 'NORDBOOKING\Classes\Subscription', 'schedule_events' ) );
}

/**
 * Creates the necessary authentication pages when the theme is activated.
 */
function nordbooking_create_authentication_pages() {
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
        array(
            'slug' => 'subscription-required',
            'title' => 'Subscription Required',
            'template' => 'page-subscription-required.php',
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
add_action( 'after_switch_theme', 'nordbooking_create_authentication_pages' );

// Initialize Services Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Services')) {
    if (!isset($GLOBALS['nordbooking_services_manager'])) {
        $GLOBALS['nordbooking_services_manager'] = new NORDBOOKING\Classes\Services();
        $GLOBALS['nordbooking_services_manager']->register_actions();
    }
}

// Initialize Areas Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Areas')) {
    if (!isset($GLOBALS['nordbooking_areas_manager'])) {
        $GLOBALS['nordbooking_areas_manager'] = new NORDBOOKING\Classes\Areas();
        $GLOBALS['nordbooking_areas_manager']->register_ajax_actions();
    }
}

// Initialize Discounts Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Discounts')) {
    if (!isset($GLOBALS['nordbooking_discounts_manager'])) {
        $GLOBALS['nordbooking_discounts_manager'] = new NORDBOOKING\Classes\Discounts();
        if (method_exists($GLOBALS['nordbooking_discounts_manager'], 'register_ajax_actions')) {
            $GLOBALS['nordbooking_discounts_manager']->register_ajax_actions();
        }
    }
}

// Initialize Settings Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Settings')) {
    if (!isset($GLOBALS['nordbooking_settings_manager'])) {
        $GLOBALS['nordbooking_settings_manager'] = new NORDBOOKING\Classes\Settings();
        if (method_exists($GLOBALS['nordbooking_settings_manager'], 'register_ajax_actions')) {
            $GLOBALS['nordbooking_settings_manager']->register_ajax_actions();
        }
    }
}

// Initialize Notifications Manager (no AJAX actions needed for this one usually)
if (class_exists('NORDBOOKING\Classes\Notifications')) {
    if (!isset($GLOBALS['nordbooking_notifications_manager'])) {
        $GLOBALS['nordbooking_notifications_manager'] = new NORDBOOKING\Classes\Notifications();
    }
}

// Initialize Bookings Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Bookings') &&
    isset($GLOBALS['nordbooking_discounts_manager']) &&
    isset($GLOBALS['nordbooking_notifications_manager']) &&
    isset($GLOBALS['nordbooking_services_manager'])
) {
    if (!isset($GLOBALS['nordbooking_bookings_manager'])) {
        $GLOBALS['nordbooking_bookings_manager'] = new NORDBOOKING\Classes\Bookings(
            $GLOBALS['nordbooking_discounts_manager'],
            $GLOBALS['nordbooking_notifications_manager'],
            $GLOBALS['nordbooking_services_manager']
        );
        $GLOBALS['nordbooking_bookings_manager']->register_ajax_actions();
    }
}
// Initialize Availability Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Availability')) {
    if (!isset($GLOBALS['nordbooking_availability_manager'])) {
        $GLOBALS['nordbooking_availability_manager'] = new NORDBOOKING\Classes\Availability();
        $GLOBALS['nordbooking_availability_manager']->register_ajax_actions();
    }
}

// Initialize Customers Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Customers')) {
    if (!isset($GLOBALS['nordbooking_customers_manager'])) {
        $GLOBALS['nordbooking_customers_manager'] = new NORDBOOKING\Classes\Customers();
        // Ensure register_ajax_actions method exists before calling
        if (method_exists($GLOBALS['nordbooking_customers_manager'], 'register_ajax_actions')) {
            $GLOBALS['nordbooking_customers_manager']->register_ajax_actions();
        }
    }
}

// Initialize Customers Manager and register its AJAX actions
if (class_exists('NORDBOOKING\Classes\Customers')) {
    if (!isset($GLOBALS['nordbooking_customers_manager'])) {
        $GLOBALS['nordbooking_customers_manager'] = new NORDBOOKING\Classes\Customers();
        if (method_exists($GLOBALS['nordbooking_customers_manager'], 'register_ajax_actions')) {
            $GLOBALS['nordbooking_customers_manager']->register_ajax_actions();
        }
    }
}

// Also add this check to ensure all required globals exist before initializing bookings
if (class_exists('NORDBOOKING\Classes\Bookings') &&
    isset($GLOBALS['nordbooking_discounts_manager']) &&
    isset($GLOBALS['nordbooking_notifications_manager']) &&
    isset($GLOBALS['nordbooking_services_manager'])
) {
    if (!isset($GLOBALS['nordbooking_bookings_manager'])) {
        $GLOBALS['nordbooking_bookings_manager'] = new NORDBOOKING\Classes\Bookings(
            $GLOBALS['nordbooking_discounts_manager'],
            $GLOBALS['nordbooking_notifications_manager'],
            $GLOBALS['nordbooking_services_manager']
        );

        if (method_exists($GLOBALS['nordbooking_bookings_manager'], 'register_ajax_actions')) {
            $GLOBALS['nordbooking_bookings_manager']->register_ajax_actions();
        }
    }
} else {
    error_log('NORDBOOKING: Required managers not available for Bookings initialization');
}


// Register Admin Pages
if ( class_exists( 'NORDBOOKING\Classes\Admin\UserManagementPage' ) ) {
    add_action( 'admin_menu', array( 'NORDBOOKING\Classes\Admin\UserManagementPage', 'register_page' ) );
    add_action( 'admin_init', array( 'NORDBOOKING\Classes\Admin\UserManagementPage', 'handle_user_switching' ) );
    add_action( 'admin_init', array( 'NORDBOOKING\Classes\Admin\UserManagementPage', 'handle_switch_back' ) );
    add_action( 'admin_bar_menu', array( 'NORDBOOKING\Classes\Admin\UserManagementPage', 'add_switch_back_link' ), 999 );
}



// Initialize Public Booking Form AJAX and its dependencies
if (class_exists('NORDBOOKING\Classes\BookingFormAjax')) {
    if (!isset($GLOBALS['nordbooking_booking_form_ajax'])) {
        $GLOBALS['nordbooking_booking_form_ajax'] = new \NORDBOOKING\Classes\BookingFormAjax();
        $GLOBALS['nordbooking_booking_form_ajax']->register_ajax_actions();
    }
}

// Initialize Service Options AJAX
if (class_exists('NORDBOOKING\Classes\ServiceOptionsAjax')) {
    if (!isset($GLOBALS['nordbooking_service_options_ajax'])) {
        $GLOBALS['nordbooking_service_options_ajax'] = new \NORDBOOKING\Classes\ServiceOptionsAjax();
        $GLOBALS['nordbooking_service_options_ajax']->register_ajax_actions();
    }
}

// Initialize Availability AJAX
if (class_exists('NORDBOOKING\Classes\AvailabilityAjax')) {
    if (!isset($GLOBALS['nordbooking_availability_ajax'])) {
        $GLOBALS['nordbooking_availability_ajax'] = new \NORDBOOKING\Classes\AvailabilityAjax();
        $GLOBALS['nordbooking_availability_ajax']->register_ajax_actions();
    }
}

// Initialize Service Performance AJAX
if (class_exists('NORDBOOKING\Classes\ServicePerformance')) {
    if (!isset($GLOBALS['nordbooking_service_performance'])) {
        $GLOBALS['nordbooking_service_performance'] = new \NORDBOOKING\Classes\ServicePerformance();
        error_log('NORDBOOKING: ServicePerformance class initialized successfully');
    }
}
?>
