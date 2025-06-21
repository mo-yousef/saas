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
            <h3><?php esc_html_e('MoBooking', 'mobooking'); ?></h3>
        </a>
    </div>

    <?php if ( ! empty( $user_mobooking_role_display_name ) ) : ?>
        <div class="dashboard-user-role" style="padding: 10px 15px; color: #E0E0E0; text-align: center; background-color: rgba(0,0,0,0.1);">
            <?php echo esc_html( sprintf( __( 'Role: %s', 'mobooking' ), $user_mobooking_role_display_name ) ); ?>
        </div>
    <?php endif; ?>

    <nav class="dashboard-nav">
        <ul>
            <?php if (current_user_can(\MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD)) : ?>
            <li class="<?php echo ($current_page === 'overview') ? 'active' : ''; ?>"><a href="<?php echo esc_url($dashboard_base_url); ?>"><?php esc_html_e('Overview', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_BOOKINGS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS)) : ?>
            <li class="<?php echo ($current_page === 'bookings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'bookings/'); ?>"><?php esc_html_e('Bookings', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_SERVICES) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_SERVICES)) : ?>
            <li class="<?php echo ($current_page === 'services') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'services/'); ?>"><?php esc_html_e('Services', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_DISCOUNTS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_DISCOUNTS)) : ?>
            <li class="<?php echo ($current_page === 'discounts') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'discounts/'); ?>"><?php esc_html_e('Discounts', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_VIEW_AREAS) || current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_AREAS)) : ?>
            <li class="<?php echo ($current_page === 'areas') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'areas/'); ?>"><?php esc_html_e('Service Areas', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_WORKERS)) : ?>
            <li class="<?php echo ($current_page === 'workers') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'workers/'); ?>"><?php esc_html_e('Workers', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKING_FORM)) : ?>
            <li class="<?php echo ($current_page === 'booking-form') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'booking-form/'); ?>"><?php esc_html_e('Booking Form', 'mobooking'); ?></a></li>
            <?php endif; ?>

            <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BUSINESS_SETTINGS)) : ?>
            <li class="<?php echo ($current_page === 'settings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'settings/'); ?>"><?php esc_html_e('Settings', 'mobooking'); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
