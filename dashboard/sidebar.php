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
            <a href="#" class="mobooking-btn mobooking-btn-primary"><?php esc_html_e('Upgrade', 'mobooking'); ?></a>
        </div>
    </div>
</aside>
