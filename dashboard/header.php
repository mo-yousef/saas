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
            <div class="user-menu-toggle">
                <div class="user-avatar">
                    <?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?>
                </div>
                <span><?php echo esc_html( $user->display_name ); ?></span>
                <div class="arrow-down"></div>
            </div>
            <ul class="user-dropdown-menu">
                <li class="dropdown-header">
                    <span class="user-display-name"><?php echo esc_html( $user->display_name ); ?></span>
                    <span class="user-email"><?php echo esc_html( $user->user_email ); ?></span>
                </li>
                <li><div class="dropdown-divider"></div></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/my-assigned-bookings/')); ?>"><?php esc_html_e('My Bookings', 'mobooking'); ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>"><?php esc_html_e('Booking Form', 'mobooking'); ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>"><?php esc_html_e('Settings', 'mobooking'); ?></a></li>
                <li><div class="dropdown-divider"></div></li>
                <li><a class="dropdown-item" href="<?php echo wp_logout_url( home_url() ); ?>"><?php esc_html_e('Logout', 'mobooking'); ?></a></li>
            </ul>
        </div>
    </div>
</header>
