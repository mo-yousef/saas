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
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), array(), MOBOOKING_VERSION );
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
    $is_dashboard_request = false;
    $current_page_slug = get_query_var('mobooking_dashboard_page');

    if (!empty($current_page_slug)) {
        $is_dashboard_request = true;
    } else {
        // Fallback for servers where query vars might not be immediately available after rewrite rule changes
        // or for direct access attempts without WordPress fully parsing the request via query vars.
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = explode('/', $path);
        if (isset($segments[0]) && $segments[0] === 'dashboard') {
            $is_dashboard_request = true;
            $current_page_slug = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : 'overview';
        }
    }

    if ( $is_dashboard_request ) {
        if ( !is_user_logged_in() ) {
            wp_redirect( home_url( '/login/' ) );
            exit;
        }
        $user = wp_get_current_user();
        if ( !in_array( MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER, (array) $user->roles ) ) {
            // If logged in but not a business owner, redirect to home or a 'permission denied' page.
            wp_redirect( home_url( '/' ) );
            exit;
        }

        // Define allowed dashboard pages to prevent arbitrary file inclusion.
        $allowed_pages = ['overview', 'bookings', 'services', 'discounts', 'areas', 'booking-form', 'settings'];
        if (!in_array($current_page_slug, $allowed_pages)) {
            $current_page_slug = 'overview'; // Default to overview if the page is not allowed or not found.
        }

        // Set a global variable that can be used by dashboard components (header, sidebar)
        // to know the current view.
        $GLOBALS['mobooking_current_dashboard_view'] = $current_page_slug;

        $new_template = MOBOOKING_THEME_DIR . 'dashboard/dashboard-shell.php';
        if ( file_exists( $new_template ) ) {
            // Prevent WordPress from trying to redirect to a canonical URL (e.g. /dashboard to /dashboard/)
            // as our rewrite rules handle this.
            remove_filter('template_redirect', 'redirect_canonical');

            // Ensure correct HTTP status header for these dynamically routed pages.
            status_header(200);
            return $new_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'mobooking_dashboard_template_include', 99 ); // High priority

// Enqueue dashboard specific scripts or styles if needed
function mobooking_dashboard_scripts_styles() {
    // Check if we are on a dashboard page using the global var
    if (isset($GLOBALS['mobooking_current_dashboard_view'])) {
        // Example: Enqueue a dedicated dashboard stylesheet
        // wp_enqueue_style('mobooking-dashboard-styles', MOBOOKING_THEME_URI . 'assets/css/dashboard.css', array(), MOBOOKING_VERSION);

        // Example: Enqueue a dedicated dashboard script
        // wp_enqueue_script('mobooking-dashboard-scripts', MOBOOKING_THEME_URI . 'assets/js/dashboard.js', array('jquery'), MOBOOKING_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'mobooking_dashboard_scripts_styles');

?>
