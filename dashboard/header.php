<?php
/**
 * The header for the NORDBOOKING Dashboard.
 * Features a global search and improved user menu.
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get current user subscription status for menu restrictions
$user_id = get_current_user_id();
$subscription_status = 'unsubscribed';
if (class_exists('\NORDBOOKING\Classes\Subscription')) {
    $subscription_status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
}
$is_expired = in_array($subscription_status, ['expired_trial', 'expired']);
?>
<header class="nordbooking-dashboard-header">
    <div class="dashboard-header-left">
        <button id="NORDBOOKING-mobile-nav-toggle" aria-label="<?php esc_attr_e('Toggle navigation', 'NORDBOOKING'); ?>">&#9776;</button>

        <!-- Live Search Bar -->
        <div class="NORDBOOKING-search-container">
            <svg class="NORDBOOKING-search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="NORDBOOKING-live-search" placeholder="<?php esc_attr_e('Search bookings, customers, etc...', 'NORDBOOKING'); ?>">
            <div id="NORDBOOKING-search-results" class="NORDBOOKING-search-results-dropdown">
                <div class="search-quick-links">
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>"><span><?php esc_html_e('Bookings', 'NORDBOOKING'); ?></span><span class="shortcut">⌘B</span></a>
                    <a href="<?php echo esc_url(home_url('/dashboard/customers/')); ?>"><span><?php esc_html_e('Customers', 'NORDBOOKING'); ?></span><span class="shortcut">⌘C</span></a>
                    <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>"><span><?php esc_html_e('Services', 'NORDBOOKING'); ?></span><span class="shortcut">⌘S</span></a>
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
                    <?php if ($is_expired): ?>
                        <div class="subscription-status-badge expired">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            <?php esc_html_e('Plan Expired', 'NORDBOOKING'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_expired): ?>
                    <!-- Expired users: Only show subscription page and logout -->
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/subscription/')); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="20" height="14" x="2" y="5" rx="2"/>
                            <line x1="2" x2="22" y1="10" y2="10"/>
                        </svg>
                        <span><?php esc_html_e('Upgrade Plan', 'NORDBOOKING'); ?></span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo wp_logout_url( home_url() ); ?>">
                        <?php echo nordbooking_get_dashboard_menu_icon('logout'); ?>
                        <span><?php esc_html_e('Logout', 'NORDBOOKING'); ?></span>
                    </a>
                <?php else: ?>
                    <!-- Active users: Show full menu -->
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/')); ?>"><?php echo nordbooking_get_dashboard_menu_icon('overview'); ?><span><?php esc_html_e('Dashboard', 'NORDBOOKING'); ?></span></a>
                    <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/availability/')); ?>"><?php echo nordbooking_get_dashboard_menu_icon('availability'); ?><span><?php esc_html_e('Availability', 'NORDBOOKING'); ?></span></a>
                    <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/discounts/')); ?>"><?php echo nordbooking_get_dashboard_menu_icon('discounts'); ?><span><?php esc_html_e('Discounts', 'NORDBOOKING'); ?></span></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>"><?php echo nordbooking_get_dashboard_menu_icon('settings'); ?><span><?php esc_html_e('Settings', 'NORDBOOKING'); ?></span></a>
                    <a class="dropdown-item" href="<?php echo esc_url(home_url('/')); ?>"><?php echo nordbooking_get_dashboard_menu_icon('booking_form'); ?><span><?php esc_html_e('View Booking Form', 'NORDBOOKING'); ?></span></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo wp_logout_url( home_url() ); ?>"><?php echo nordbooking_get_dashboard_menu_icon('logout'); ?><span><?php esc_html_e('Logout', 'NORDBOOKING'); ?></span></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
