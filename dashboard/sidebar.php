<?php
/**
 * The sidebar for the MoBooking Dashboard.
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_page = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : 'overview';
$dashboard_base_url = home_url('/dashboard/');

$user_mobooking_role_display_name = '';
$current_user_id = get_current_user_id();

// Determine the display brand name
$display_brand_name = esc_html__('MoBooking', 'mobooking'); // Default brand name
if ($current_user_id > 0 && isset($GLOBALS['mobooking_settings_manager']) && class_exists('MoBooking\Classes\Settings')) {
    // Ensure get_setting method exists before calling
    if (method_exists($GLOBALS['mobooking_settings_manager'], 'get_setting')) {
        // Use the correct string key 'biz_name' as defined in Settings.php
        $business_name_setting = $GLOBALS['mobooking_settings_manager']->get_setting($current_user_id, 'biz_name', '');
        if (!empty(trim($business_name_setting))) {
            $display_brand_name = esc_html(trim($business_name_setting));
        }
    }
}


if ( $current_user_id > 0 ) {
    $user = get_userdata( $current_user_id );
    if ( $user ) {
        $mobooking_role_keys = [
            \MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER,
            \MoBooking\Classes\Auth::ROLE_WORKER_STAFF,
        ];

        $all_roles = wp_roles(); // Get all role objects, which include names

        foreach ( $user->roles as $user_role_key ) {
            if ( in_array( $user_role_key, $mobooking_role_keys ) ) {
                if ( isset( $all_roles->role_names[$user_role_key] ) ) {
                    $user_mobooking_role_display_name = $all_roles->role_names[$user_role_key];
                    break; // Found the primary MoBooking role
                }
            }
        }
    }
}
?>
<aside class="mobooking-dashboard-sidebar">
    <div class="dashboard-branding">
        <a href="<?php echo esc_url($dashboard_base_url); ?>">
            <h3><?php echo $display_brand_name; ?></h3>
        </a>
    </div>

    <!-- <?php if ( ! empty( $user_mobooking_role_display_name ) ) : ?>
        <div class="dashboard-user-role" style="padding: 10px 1rem; color: #E0E0E0;background-color: rgba(0,0,0,0.1);">
            <?php echo esc_html( sprintf( __( 'Role: %s', 'mobooking' ), $user_mobooking_role_display_name ) ); ?>
        </div>
    <?php endif; ?> -->

    <nav class="dashboard-nav">
        <div class="nav-group">
            <h4 class="nav-group-title"><?php esc_html_e('Main', 'mobooking'); ?></h4>
            <ul>
                <?php if (current_user_can(\MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD)) : ?>
                <li class="<?php echo ($current_page === 'overview') ? 'active' : ''; ?>"><a href="<?php echo esc_url($dashboard_base_url); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('overview'); ?></span><?php esc_html_e('Overview', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php
                if ( current_user_can( \MoBooking\Classes\Auth::ROLE_WORKER_STAFF ) ) :
                ?>
                <li class="<?php echo ($current_page === 'my-assigned-bookings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'my-assigned-bookings/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('bookings'); ?></span><?php esc_html_e('My Assigned Bookings', 'mobooking'); ?></a></li>
                <?php
                elseif (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_BOOKINGS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS)) :
                    $active_bookings_count = 0;
                    if (isset($GLOBALS['mobooking_bookings_manager'])) {
                        $active_bookings_count = $GLOBALS['mobooking_bookings_manager']->get_active_bookings_count(get_current_user_id());
                    }
                ?>
                <li class="<?php echo ($current_page === 'bookings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'bookings/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('bookings'); ?></span><?php esc_html_e('Bookings', 'mobooking'); ?><?php if ($active_bookings_count > 0) : ?><span class="nav-badge"><?php echo esc_html($active_bookings_count); ?></span><?php endif; ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_CUSTOMERS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_CUSTOMERS)) : ?>
                <li class="<?php echo ($current_page === 'customers') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'customers/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('clients'); ?></span><?php esc_html_e('Customers', 'mobooking'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="nav-group">
            <h4 class="nav-group-title"><?php esc_html_e('Business', 'mobooking'); ?></h4>
            <ul>
                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_SERVICES) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_SERVICES)) : ?>
                <li class="<?php echo ($current_page === 'services') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'services/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('services'); ?></span><?php esc_html_e('Services', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_AVAILABILITY)) : ?>
                <li class="<?php echo ($current_page === 'availability') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'availability/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('availability'); ?></span><?php esc_html_e('Availability', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_DISCOUNTS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_DISCOUNTS)) : ?>
                <li class="<?php echo ($current_page === 'discounts') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'discounts/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('discounts'); ?></span><?php esc_html_e('Discounts', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_AREAS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_AREAS)) : ?>
                <li class="<?php echo ($current_page === 'areas') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'areas/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('areas'); ?></span><?php esc_html_e('Service Areas', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_WORKERS)) : ?>
                <li class="<?php echo ($current_page === 'workers') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'workers/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('workers'); ?></span><?php esc_html_e('Workers', 'mobooking'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="nav-group">
            <h4 class="nav-group-title"><?php esc_html_e('Settings', 'mobooking'); ?></h4>
            <ul>
                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKING_FORM)) : ?>
                <li class="<?php echo ($current_page === 'booking-form') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'booking-form/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('booking_form'); ?></span><?php esc_html_e('Booking Form', 'mobooking'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BUSINESS_SETTINGS)) : ?>
                <li class="<?php echo ($current_page === 'settings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'settings/'); ?>"><span class="mobooking-menu-icon"><?php echo mobooking_get_dashboard_menu_icon('settings'); ?></span><?php esc_html_e('Settings', 'mobooking'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="mobooking-subscription-plan">
        <div class="mobooking-subscription-plan-box">
            <h4><?php esc_html_e('Subscription Plan', 'mobooking'); ?></h4>
            <p><?php esc_html_e('You are currently on the Free Plan.', 'mobooking'); ?></p>
            <a href="#" class="mobooking-btn btn btn-primary"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.0049 22.0027C6.48204 22.0027 2.00488 17.5256 2.00488 12.0027C2.00488 6.4799 6.48204 2.00275 12.0049 2.00275C17.5277 2.00275 22.0049 6.4799 22.0049 12.0027C22.0049 17.5256 17.5277 22.0027 12.0049 22.0027ZM12.0049 20.0027C16.4232 20.0027 20.0049 16.421 20.0049 12.0027C20.0049 7.58447 16.4232 4.00275 12.0049 4.00275C7.5866 4.00275 4.00488 7.58447 4.00488 12.0027C4.00488 16.421 7.5866 20.0027 12.0049 20.0027ZM8.50488 14.0027H14.0049C14.281 14.0027 14.5049 13.7789 14.5049 13.5027C14.5049 13.2266 14.281 13.0027 14.0049 13.0027H10.0049C8.62417 13.0027 7.50488 11.8835 7.50488 10.5027C7.50488 9.12203 8.62417 8.00275 10.0049 8.00275H11.0049V6.00275H13.0049V8.00275H15.5049V10.0027H10.0049C9.72874 10.0027 9.50488 10.2266 9.50488 10.5027C9.50488 10.7789 9.72874 11.0027 10.0049 11.0027H14.0049C15.3856 11.0027 16.5049 12.122 16.5049 13.5027C16.5049 14.8835 15.3856 16.0027 14.0049 16.0027H13.0049V18.0027H11.0049V16.0027H8.50488V14.0027Z"></path></svg><?php esc_html_e('Upgrade', 'mobooking'); ?></a>
        </div>
    </div>
</aside>
