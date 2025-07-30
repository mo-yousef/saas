<?php
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
    wp_enqueue_style( 'mobooking-toggle-switch', MOBOOKING_THEME_URI . 'assets/css/toggle-switch.css', array('mobooking-style'), MOBOOKING_VERSION );

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
if ( is_page_template('templates/booking-form-modern.php') || get_query_var('mobooking_page_type') === 'public_booking' ) {
    // Enqueue the new modern booking form assets
    wp_enqueue_style( 'mobooking-booking-form-modern', MOBOOKING_THEME_URI . 'assets/css/booking-form-modern.css', array('mobooking-style'), MOBOOKING_VERSION );
    wp_enqueue_script('mobooking-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);

    // The rest of the localization logic can be simplified as it's now handled in the template itself.
    // However, to keep things consistent, we can still pass the nonce here.
    wp_localize_script('mobooking-booking-form', 'mobooking_booking_form_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_booking_nonce'),
    ]);
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
?>
