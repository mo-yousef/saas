<?php
/**
 * Main shell for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit;
error_log('[MoBooking Shell Debug] dashboard-shell.php execution started.');

$requested_page = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : 'overview';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(ucfirst($requested_page)); ?> - <?php esc_html_e('Dashboard', 'mobooking'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('mobooking-dashboard'); ?>>
    <div class="mobooking-dashboard-layout">
        <?php
        error_log('[MoBooking Shell Debug] Including sidebar.php. Current view for sidebar: ' . (isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : 'Not Set'));
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
