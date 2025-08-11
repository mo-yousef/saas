<?php
/**
 * The header for the MoBooking Dashboard.
 * Features a global search and improved user menu.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<header class="mobooking-dashboard-header">
    <div class="dashboard-header-left">
        <button id="mobooking-mobile-nav-toggle" aria-label="<?php esc_attr_e('Toggle navigation', 'mobooking'); ?>">&#9776;</button>

        <!-- Live Search Bar -->
        <div class="mobooking-search-container">
            <svg class="mobooking-search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="mobooking-live-search" placeholder="<?php esc_attr_e('Search bookings, customers, etc...', 'mobooking'); ?>">
            <div id="mobooking-search-results" class="mobooking-search-results-dropdown" style="display: none;">
                <div class="search-quick-links">
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>"><span><?php esc_html_e('Bookings', 'mobooking'); ?></span><span class="shortcut">⌘B</span></a>
                    <a href="<?php echo esc_url(home_url('/dashboard/customers/')); ?>"><span><?php esc_html_e('Customers', 'mobooking'); ?></span><span class="shortcut">⌘C</span></a>
                    <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>"><span><?php esc_html_e('Services', 'mobooking'); ?></span><span class="shortcut">⌘S</span></a>
                </div>
                <div id="search-results-list">
                    <!-- Real-time results will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-header-right">
        <?php $user = wp_get_current_user(); ?>
        <div class="user-menu">
            <button class="user-menu-toggle">
                <div class="user-avatar">
                    <?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?>
                </div>
                <span class="user-name"><?php echo esc_html( $user->display_name ); ?></span>
                <svg class="chevron-down" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
            </button>
            <div class="user-dropdown-menu">
                <div class="dropdown-header">
                    <span class="user-display-name"><?php echo esc_html( $user->display_name ); ?></span>
                    <span class="user-email"><?php echo esc_html( $user->user_email ); ?></span>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/')); ?>"><?php echo mobooking_get_dashboard_menu_icon('overview'); ?><span><?php esc_html_e('Dashboard', 'mobooking'); ?></span></a>
                <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/availability/')); ?>"><?php echo mobooking_get_dashboard_menu_icon('availability'); ?><span><?php esc_html_e('Availability', 'mobooking'); ?></span></a>
                <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/discounts/')); ?>"><?php echo mobooking_get_dashboard_menu_icon('discounts'); ?><span><?php esc_html_e('Discounts', 'mobooking'); ?></span></a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>"><?php echo mobooking_get_dashboard_menu_icon('settings'); ?><span><?php esc_html_e('Settings', 'mobooking'); ?></span></a>
                <a class="dropdown-item" href="<?php echo esc_url(home_url('/')); ?>"><?php echo mobooking_get_dashboard_menu_icon('booking_form'); ?><span><?php esc_html_e('View Booking Form', 'mobooking'); ?></span></a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?php echo wp_logout_url( home_url() ); ?>"><?php echo mobooking_get_dashboard_menu_icon('logout'); ?><span><?php esc_html_e('Logout', 'mobooking'); ?></span></a>
            </div>
        </div>
    </div>
</header>
