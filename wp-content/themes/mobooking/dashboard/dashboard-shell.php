<?php
/**
 * Main shell for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$requested_page = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : 'overview';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(ucfirst($requested_page)); ?> - <?php esc_html_e('Dashboard', 'mobooking'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Basic Dashboard Styles */
        html, body { margin: 0; padding: 0; height: 100%; }
        body.mobooking-dashboard { font-family: sans-serif; background-color: #f0f0f1; color: #444; display: flex; flex-direction: column; }
        .mobooking-dashboard-layout { display: flex; flex-grow: 1; }
        .mobooking-dashboard-sidebar { width: 230px; background: #2c3e50; color: #fff; flex-shrink: 0; display: flex; flex-direction: column; }
        .mobooking-dashboard-sidebar .dashboard-branding { padding: 15px; border-bottom: 1px solid #444; }
        .mobooking-dashboard-sidebar .dashboard-branding h3 { margin: 0; color: #fff; }
        .mobooking-dashboard-sidebar .dashboard-branding a { text-decoration:none; }
        .mobooking-dashboard-sidebar .dashboard-nav { flex-grow:1; }
        .mobooking-dashboard-sidebar .dashboard-nav ul { list-style: none; padding: 0; margin: 15px 0; }
        .mobooking-dashboard-sidebar .dashboard-nav li a { display: block; padding: 10px 15px; color: #ecf0f1; text-decoration: none; }
        .mobooking-dashboard-sidebar .dashboard-nav li a:hover { background: #34495e; }
        .mobooking-dashboard-sidebar .dashboard-nav li.active a { background: #1abc9c; color: #fff; }
        .mobooking-dashboard-main-wrapper { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .mobooking-dashboard-header { background: #fff; padding: 0 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; height: 50px; flex-shrink:0;}
        .mobooking-dashboard-header .dashboard-header-left, .mobooking-dashboard-header .dashboard-header-right { display:flex; align-items:center;}
        .mobooking-dashboard-header .mobooking-breadcrumbs a, .mobooking-dashboard-header .mobooking-breadcrumbs { color: #555; text-decoration:none;}
        .mobooking-dashboard-header .user-menu span { margin-right:10px; }
        .mobooking-dashboard-header .user-menu a { text-decoration:none; color: #0073aa;}
        .dashboard-page-content-area { padding: 20px; flex-grow:1; }
        #mobooking-mobile-nav-toggle { display: none; font-size:20px; background:transparent; border:none; margin-right:15px; cursor:pointer; }
        @media (max-width: 768px) {
            #mobooking-mobile-nav-toggle { display: block; }
            .mobooking-dashboard-sidebar { /* Add styles for hidden sidebar by default on mobile */
                position: fixed;
                left: -230px; /* Hidden by default */
                top: 0;
                height: 100%;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            .mobooking-dashboard-sidebar.open {
                left: 0; /* Shown when open */
            }
        }
    </style>
</head>
<body <?php body_class('mobooking-dashboard'); ?>>
    <div class="mobooking-dashboard-layout">
        <?php include_once MOBOOKING_THEME_DIR . 'dashboard/sidebar.php'; ?>
        <div class="mobooking-dashboard-main-wrapper">
            <?php include_once MOBOOKING_THEME_DIR . 'dashboard/header.php'; ?>
            <main class="dashboard-page-content-area">
                <?php
                $template_file = MOBOOKING_THEME_DIR . 'dashboard/page-' . sanitize_key($requested_page) . '.php';
                if ( file_exists( $template_file ) ) {
                    include_once $template_file;
                } else {
                    // If a specific page file doesn't exist, try to load a default or overview.
                    // For now, ensure page-overview.php exists or handle this more gracefully.
                    $overview_file = MOBOOKING_THEME_DIR . 'dashboard/page-overview.php';
                    if (file_exists($overview_file)) {
                        include_once $overview_file;
                    } else {
                        echo "<p>Content for " . esc_html($requested_page) . " not found. Overview page also missing.</p>";
                    }
                }
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
