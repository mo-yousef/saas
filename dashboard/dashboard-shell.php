<?php
if ( ! function_exists( 'mobooking_dashboard_enqueue_styles_fix' ) ) {
    function mobooking_dashboard_enqueue_styles_fix() {
        // Conditional logic to only load on our dashboard pages
        $is_mobooking_dashboard_page = false;
        if (function_exists('get_query_var')) {
            $mobooking_page = get_query_var('mobooking_dashboard_page');
            if (!empty($mobooking_page)) {
                $is_mobooking_dashboard_page = true;
            }
        }
        // Fallback or additional check if query var isn't reliable here
        if (!$is_mobooking_dashboard_page && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
            $is_mobooking_dashboard_page = true;
        }

        if ( $is_mobooking_dashboard_page ) {
            // Assuming dashboard-shell.php is in 'plugin_root/dashboard/'
            // and assets is in 'plugin_root/assets/'
            $plugin_dir_path_from_shell = dirname(__DIR__); // Goes up from 'dashboard' to 'plugin_root'
            $css_file_rel_path = '/assets/css/dashboard.css';
            $css_file_abs_path = get_stylesheet_directory() . $css_file_rel_path;
            $css_file_url = get_stylesheet_directory_uri() . $css_file_rel_path;

            if (file_exists($css_file_abs_path)) {
                wp_enqueue_style(
                    'mobooking-dashboard-styles', // Handle
                    $css_file_url,               // Source URL
                    array(),                     // Dependencies
                    filemtime($css_file_abs_path), // Version
                    'all'                        // Media
                );
            } else {
                // Log error if CSS file itself is not found
                error_log('[MoBooking Styles Fix] CSS file not found at: ' . $css_file_abs_path);
            }

            // Enqueue the new enhanced sidebar CSS
            $sidebar_css_rel_path = '/assets/css/mobooking-dashboard-sidebar-enhanced.css';
            $sidebar_css_abs_path = get_stylesheet_directory() . $sidebar_css_rel_path;
            $sidebar_css_url = get_stylesheet_directory_uri() . $sidebar_css_rel_path;

            if (file_exists($sidebar_css_abs_path)) {
                wp_enqueue_style(
                    'mobooking-dashboard-sidebar-enhanced',
                    $sidebar_css_url,
                    array('mobooking-dashboard-styles'), // Depends on the main dashboard styles
                    filemtime($sidebar_css_abs_path),
                    'all'
                );
            } else {
                error_log('[MoBooking Styles Fix] Enhanced sidebar CSS file not found at: ' . $sidebar_css_abs_path);
            }

            // Enqueue the new dropdown CSS
            $dropdown_css_rel_path = '/assets/css/dashboard-header-dropdown.css';
            $dropdown_css_abs_path = get_stylesheet_directory() . $dropdown_css_rel_path;
            $dropdown_css_url = get_stylesheet_directory_uri() . $dropdown_css_rel_path;

            if (file_exists($dropdown_css_abs_path)) {
                wp_enqueue_style(
                    'mobooking-dashboard-header-dropdown',
                    $dropdown_css_url,
                    array('mobooking-dashboard-styles'), // Depends on the main dashboard styles
                    filemtime($dropdown_css_abs_path),
                    'all'
                );
            } else {
                error_log('[MoBooking Styles Fix] Dropdown CSS file not found at: ' . $dropdown_css_abs_path);
            }
        }
    }
    add_action( 'wp_enqueue_scripts', 'mobooking_dashboard_enqueue_styles_fix', 20 );
}

/**
 * Main shell for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Initial Access Control
if ( ! is_user_logged_in() ) {
    // Consider using wp_safe_redirect to prevent header modification issues if output has started.
    // However, at this point, it should be safe.
    wp_redirect( home_url( '/login/' ) ); // Assuming '/login/' is your login page slug
    exit;
}

// Ensure Auth class is available. This might be better handled by an autoloader or ensuring it's included earlier.
if ( ! class_exists('\MoBooking\Classes\Auth') ) {
    // This is a critical error, means the plugin structure or loading is incorrect.
    wp_die( 'Critical Error: Auth class not found. Cannot proceed with dashboard loading.' );
}

if ( ! current_user_can( \MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this dashboard.', 'mobooking' ) );
}

// FIXED: Get the requested page from query vars set by the router
$requested_page = get_query_var('mobooking_dashboard_page');

// Fallback methods if query var is not set
if (empty($requested_page)) {
    // Try global variable as fallback
    $requested_page = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : '';
}

if (empty($requested_page)) {
    // Try to parse from URL as final fallback
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
    $path_segments = explode('/', $path);
    
    if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
        $requested_page = isset($path_segments[1]) && !empty($path_segments[1]) ? sanitize_title($path_segments[1]) : 'overview';
    }
}

// Final fallback to overview
if (empty($requested_page)) {
    $requested_page = 'overview';
}

error_log('[MoBooking Shell Debug] Final determined requested page: ' . $requested_page);

// Page-specific capability check
$page_capabilities = [
    'overview' => \MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD,
    'bookings' => \MoBooking\Classes\Auth::CAP_VIEW_BOOKINGS, // Users with CAP_MANAGE_BOOKINGS will also pass this if CAP_VIEW_BOOKINGS is granted to them
    'services' => \MoBooking\Classes\Auth::CAP_VIEW_SERVICES, // Same logic for other view caps
    'service-edit' => \MoBooking\Classes\Auth::CAP_MANAGE_SERVICES, // Editing requires manage cap
    'discounts' => \MoBooking\Classes\Auth::CAP_VIEW_DISCOUNTS,
    'areas' => \MoBooking\Classes\Auth::CAP_VIEW_AREAS,
    'workers' => \MoBooking\Classes\Auth::CAP_MANAGE_WORKERS,
    'booking-form' => \MoBooking\Classes\Auth::CAP_MANAGE_BOOKING_FORM,
    'settings' => \MoBooking\Classes\Auth::CAP_MANAGE_BUSINESS_SETTINGS,
    'availability' => \MoBooking\Classes\Auth::CAP_MANAGE_AVAILABILITY,
    'customers' => \MoBooking\Classes\Auth::CAP_VIEW_CUSTOMERS, // Use view capability for page access
    // Add other specific pages like 'discount-edit', 'area-edit' if they exist and need specific manage caps
];

$required_capability_for_page = isset( $page_capabilities[$requested_page] ) ? $page_capabilities[$requested_page] : \MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD; // Default to basic access

// For pages that have both view and manage capabilities (like bookings, services, etc.),
// if the required cap is a "view" cap, we should also allow users who have the "manage" cap.
// This logic is a bit more complex if not all roles with "manage_X" also have "view_X".
// The current role setup in Auth.php *does* grant view caps when manage caps are granted, so a direct check is okay.

if ( ! current_user_can( $required_capability_for_page ) ) {
    // If it's a "view" cap that failed, check if they have the corresponding "manage" cap.
    // This is a simplified check. A more robust system might involve checking an array of caps.
    $can_access = false;
    if ( strpos( $required_capability_for_page, '_view_' ) !== false ) {
        $manage_cap = str_replace( '_view_', '_manage_', $required_capability_for_page );
        if ( current_user_can( $manage_cap ) ) {
            $can_access = true;
        }
    }

    if ( ! $can_access ) {
        // Redirect to overview or show error
        // wp_redirect( home_url('/dashboard/') );
        // exit;
        wp_die( esc_html__( 'You do not have sufficient permissions to access this specific page.', 'mobooking' ) . ' (Req: ' . esc_html($required_capability_for_page). ')' );
    }
}

error_log('[MoBooking Shell Debug] dashboard-shell.php execution started. User logged in and has basic dashboard access.');

?>
<!DOCTYPE html>

<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(ucfirst($requested_page)); ?> - <?php esc_html_e('Dashboard', 'mobooking'); ?> - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <?php wp_head(); ?>
</head>
<body <?php body_class('mobooking-dashboard'); ?>>
    <div class="mobooking-dashboard-layout">
        <?php
        error_log('[MoBooking Shell Debug] Including sidebar.php. Current view for sidebar: ' . $requested_page);
        // Set the global variable for the sidebar to use
        $GLOBALS['mobooking_current_dashboard_view'] = $requested_page;
        include_once MOBOOKING_THEME_DIR . 'dashboard/sidebar.php';
        error_log('[MoBooking Shell Debug] sidebar.php included.');
        ?>
        <div class="mobooking-dashboard-main-wrapper">
            <?php
            error_log('[MoBooking Shell Debug] Including header.php.');
            include_once MOBOOKING_THEME_DIR . 'dashboard/header.php';
            error_log('[MoBooking Shell Debug] header.php included.');
            ?>
            <main class="dashboard-page-content-area">
                <?php
                error_log('[MoBooking Shell Debug] Determined requested page for content: ' . $requested_page);
                $template_file = MOBOOKING_THEME_DIR . 'dashboard/page-' . sanitize_key($requested_page) . '.php';
                error_log('[MoBooking Shell Debug] Template file path to include: ' . $template_file);
                
                if ( !file_exists( $template_file ) ) {
                    error_log('[MoBooking Shell Debug] CRITICAL ERROR: Content template file NOT FOUND: ' . $template_file);
                }

                if ( file_exists( $template_file ) ) {
                    include_once $template_file;
                } else {
                    // If a specific page file doesn't exist, try to load a default or overview.
                    // For now, ensure page-overview.php exists or handle this more gracefully.
                    $overview_file = MOBOOKING_THEME_DIR . 'dashboard/page-overview.php';
                    error_log('[MoBooking Shell Debug] Fallback: Attempting to load overview_file: ' . $overview_file);
                    if (file_exists($overview_file)) {
                        include_once $overview_file;
                    } else {
                        error_log('[MoBooking Shell Debug] CRITICAL ERROR: Fallback overview_file NOT FOUND: ' . $overview_file);
                        echo "<p>Content for " . esc_html($requested_page) . " not found. Overview page also missing.</p>";
                    }
                }
                error_log('[MoBooking Shell Debug] Content template included. Shell execution nearing end.');
                ?>
            </main>
        </div>
    </div>
    <div id="toast-container"></div>
    <?php wp_footer(); ?>
    <script>
        // Basic mobile nav toggle
        const mobileNavToggle = document.getElementById('mobooking-mobile-nav-toggle');
        const sidebar = document.querySelector('.mobooking-dashboard-sidebar');
        if (mobileNavToggle && sidebar) {
            mobileNavToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }
    </script>
</body>
</html>