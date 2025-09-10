<?php

/**
 * Main shell for the NORDBOOKING Dashboard.
 * @package NORDBOOKING
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
if ( ! class_exists('\NORDBOOKING\Classes\Auth') ) {
    // This is a critical error, means the plugin structure or loading is incorrect.
    wp_die( 'Critical Error: Auth class not found. Cannot proceed with dashboard loading.' );
}

if ( ! current_user_can( \NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this dashboard.', 'NORDBOOKING' ) );
}

// FIXED: Get the requested page from query vars set by the router
$requested_page = get_query_var('nordbooking_dashboard_page');

// Fallback methods if query var is not set
if (empty($requested_page)) {
    // Try global variable as fallback
    $requested_page = isset($GLOBALS['nordbooking_current_dashboard_view']) ? $GLOBALS['nordbooking_current_dashboard_view'] : '';
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

error_log('[NORDBOOKING Shell Debug] Final determined requested page: ' . $requested_page);

// Page-specific capability check
$page_capabilities = [
    'overview' => \NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD,
    'bookings' => \NORDBOOKING\Classes\Auth::CAP_VIEW_BOOKINGS, // Users with CAP_MANAGE_BOOKINGS will also pass this if CAP_VIEW_BOOKINGS is granted to them
    'services' => \NORDBOOKING\Classes\Auth::CAP_VIEW_SERVICES, // Same logic for other view caps
    'service-edit' => \NORDBOOKING\Classes\Auth::CAP_MANAGE_SERVICES, // Editing requires manage cap
    'discounts' => \NORDBOOKING\Classes\Auth::CAP_VIEW_DISCOUNTS,
    'areas' => \NORDBOOKING\Classes\Auth::CAP_VIEW_AREAS,
    'workers' => \NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS,
    'booking-form' => \NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKING_FORM,
    'settings' => \NORDBOOKING\Classes\Auth::CAP_MANAGE_BUSINESS_SETTINGS,
    'availability' => \NORDBOOKING\Classes\Auth::CAP_MANAGE_AVAILABILITY,
    'customers' => \NORDBOOKING\Classes\Auth::CAP_VIEW_CUSTOMERS, // Use view capability for page access
    // Add other specific pages like 'discount-edit', 'area-edit' if they exist and need specific manage caps
];

$required_capability_for_page = isset( $page_capabilities[$requested_page] ) ? $page_capabilities[$requested_page] : \NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD; // Default to basic access

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
        wp_die( esc_html__( 'You do not have sufficient permissions to access this specific page.', 'NORDBOOKING' ) . ' (Req: ' . esc_html($required_capability_for_page). ')' );
    }
}

error_log('[NORDBOOKING Shell Debug] dashboard-shell.php execution started. User logged in and has basic dashboard access.');

?>
<!DOCTYPE html>

<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(ucfirst($requested_page)); ?> - <?php esc_html_e('Dashboard', 'NORDBOOKING'); ?> - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <?php wp_head(); ?>
</head>
<body <?php body_class('NORDBOOKING-dashboard'); ?>>
    <div class="NORDBOOKING-dashboard-layout">
        <?php
        error_log('[NORDBOOKING Shell Debug] Including sidebar.php. Current view for sidebar: ' . $requested_page);
        // Set the global variable for the sidebar to use
        $GLOBALS['nordbooking_current_dashboard_view'] = $requested_page;
        include_once NORDBOOKING_THEME_DIR . 'dashboard/sidebar.php';
        error_log('[NORDBOOKING Shell Debug] sidebar.php included.');
        ?>
        <div class="NORDBOOKING-dashboard-main-wrapper">
            <?php
            error_log('[NORDBOOKING Shell Debug] Including header.php.');
            include_once NORDBOOKING_THEME_DIR . 'dashboard/header.php';
            error_log('[NORDBOOKING Shell Debug] header.php included.');
            ?>
            <main class="dashboard-page-content-area">
                <?php
                error_log('[NORDBOOKING Shell Debug] Determined requested page for content: ' . $requested_page);
                $template_file = NORDBOOKING_THEME_DIR . 'dashboard/page-' . sanitize_key($requested_page) . '.php';
                error_log('[NORDBOOKING Shell Debug] Template file path to include: ' . $template_file);
                
                if ( !file_exists( $template_file ) ) {
                    error_log('[NORDBOOKING Shell Debug] CRITICAL ERROR: Content template file NOT FOUND: ' . $template_file);
                }

                if ( file_exists( $template_file ) ) {
                    include_once $template_file;
                } else {
                    // If a specific page file doesn't exist, try to load a default or overview.
                    // For now, ensure page-overview.php exists or handle this more gracefully.
                    $overview_file = NORDBOOKING_THEME_DIR . 'dashboard/page-overview.php';
                    error_log('[NORDBOOKING Shell Debug] Fallback: Attempting to load overview_file: ' . $overview_file);
                    if (file_exists($overview_file)) {
                        include_once $overview_file;
                    } else {
                        error_log('[NORDBOOKING Shell Debug] CRITICAL ERROR: Fallback overview_file NOT FOUND: ' . $overview_file);
                        echo "<p>Content for " . esc_html($requested_page) . " not found. Overview page also missing.</p>";
                    }
                }
                error_log('[NORDBOOKING Shell Debug] Content template included. Shell execution nearing end.');
                ?>
            </main>
        </div>
    </div>
    <div id="toast-container"></div>
    <?php wp_footer(); ?>
    <script>
        // Basic mobile nav toggle
        const mobileNavToggle = document.getElementById('NORDBOOKING-mobile-nav-toggle');
        const sidebar = document.querySelector('.NORDBOOKING-dashboard-sidebar');
        if (mobileNavToggle && sidebar) {
            mobileNavToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }
    </script>
</body>
</html>