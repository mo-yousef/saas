<?php
/**
 * The header for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<header class="mobooking-dashboard-header">
    <div class="dashboard-header-left">
        <button id="mobooking-mobile-nav-toggle" aria-label="<?php esc_attr_e('Toggle navigation', 'mobooking'); ?>">&#9776;</button>
        <div class="mobooking-breadcrumbs">
            <a href="<?php echo esc_url(home_url('/dashboard/')); ?>"><?php esc_html_e('Dashboard', 'mobooking'); ?></a>
            <?php
            // Breadcrumb will be refined later based on $GLOBALS['mobooking_current_dashboard_view']
            $current_view = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : '';
            if ($current_view && $current_view !== 'overview') {
                echo ' / ' . esc_html(ucfirst($current_view));
            }
            ?>
        </div>
    </div>
    <div class="dashboard-header-right">
        <?php $user = wp_get_current_user(); ?>
        <div class="user-menu">
            <span><?php echo esc_html( $user->display_name ); ?></span>
            <a href="<?php echo wp_logout_url( home_url('/login/') ); // Assumes /login/ uses page-login.php ?>"><?php esc_html_e('Logout', 'mobooking'); ?></a>
        </div>
    </div>
</header>
