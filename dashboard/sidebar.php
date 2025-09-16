<?php
/**
 * The sidebar for the NORDBOOKING Dashboard.
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_page = isset($GLOBALS['nordbooking_current_dashboard_view']) ? $GLOBALS['nordbooking_current_dashboard_view'] : 'overview';
$dashboard_base_url = home_url('/dashboard/');

$user_nordbooking_role_display_name = '';
$current_user_id = get_current_user_id();

// Determine the display brand name
$display_brand_name = esc_html__('NORDBOOKING', 'NORDBOOKING'); // Default brand name
if ($current_user_id > 0 && isset($GLOBALS['nordbooking_settings_manager']) && class_exists('NORDBOOKING\Classes\Settings')) {
    // Ensure get_setting method exists before calling
    if (method_exists($GLOBALS['nordbooking_settings_manager'], 'get_setting')) {
        // Use the correct string key 'biz_name' as defined in Settings.php
        $business_name_setting = $GLOBALS['nordbooking_settings_manager']->get_setting($current_user_id, 'biz_name', '');
        if (!empty(trim($business_name_setting))) {
            $display_brand_name = esc_html(trim($business_name_setting));
        }
    }
}


if ( $current_user_id > 0 ) {
    $user = get_userdata( $current_user_id );
    if ( $user ) {
        $nordbooking_role_keys = [
            \NORDBOOKING\Classes\Auth::ROLE_BUSINESS_OWNER,
            \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF,
        ];

        $all_roles = wp_roles(); // Get all role objects, which include names

        foreach ( $user->roles as $user_role_key ) {
            if ( in_array( $user_role_key, $nordbooking_role_keys ) ) {
                if ( isset( $all_roles->role_names[$user_role_key] ) ) {
                    $user_nordbooking_role_display_name = $all_roles->role_names[$user_role_key];
                    break; // Found the primary NORDBOOKING role
                }
            }
        }
    }
}
?>
<aside class="nordbooking-dashboard-sidebar">
    <div class="dashboard-branding">
        <a href="<?php echo esc_url($dashboard_base_url); ?>">
            <h3><?php echo $display_brand_name; ?></h3>
        </a>
    </div>

    <!-- <?php if ( ! empty( $user_nordbooking_role_display_name ) ) : ?>
        <div class="dashboard-user-role" style="padding: 10px 1rem; color: #E0E0E0;background-color: rgba(0,0,0,0.1);">
            <?php echo esc_html( sprintf( __( 'Role: %s', 'NORDBOOKING' ), $user_nordbooking_role_display_name ) ); ?>
        </div>
    <?php endif; ?> -->

    <nav class="dashboard-nav">
        <div class="nav-group">
            <h4 class="nav-group-title"><?php esc_html_e('Main', 'NORDBOOKING'); ?></h4>
            <ul>
                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD)) : ?>
                <li class="<?php echo ($current_page === 'overview') ? 'active' : ''; ?>"><a href="<?php echo esc_url($dashboard_base_url); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('overview'); ?></span><?php esc_html_e('Overview', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>

                <?php
                if ( current_user_can( \NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF ) ) :
                ?>
                <li class="<?php echo ($current_page === 'my-assigned-bookings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'my-assigned-bookings/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('bookings'); ?></span><?php esc_html_e('My Assigned Bookings', 'NORDBOOKING'); ?></a></li>
                <?php
                elseif (current_user_can(\NORDBOOKING\Classes\Auth::CAP_VIEW_BOOKINGS) || current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKINGS)) :
                    $active_bookings_count = 0;
                    if (isset($GLOBALS['nordbooking_bookings_manager'])) {
                        $active_bookings_count = $GLOBALS['nordbooking_bookings_manager']->get_active_bookings_count(get_current_user_id());
                    }
                ?>
                <li class="<?php echo ($current_page === 'bookings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'bookings/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('bookings'); ?></span><?php esc_html_e('Bookings', 'NORDBOOKING'); ?><?php if ($active_bookings_count > 0) : ?><span class="nav-badge"><?php echo esc_html($active_bookings_count); ?></span><?php endif; ?></a></li>
                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_VIEW_BOOKINGS) || current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKINGS)) : ?>
                <li class="<?php echo ($current_page === 'calendar') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'calendar/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('calendar'); ?></span><?php esc_html_e('Calendar', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_VIEW_CUSTOMERS) || current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_CUSTOMERS)) : ?>
                <li class="<?php echo ($current_page === 'customers') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'customers/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('clients'); ?></span><?php esc_html_e('Customers', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="nav-group">
            <h4 class="nav-group-title"><?php esc_html_e('Business', 'NORDBOOKING'); ?></h4>
            <ul>
                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_VIEW_SERVICES) || current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_SERVICES)) : ?>
                <li class="<?php echo ($current_page === 'services') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'services/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('services'); ?></span><?php esc_html_e('Services', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_AVAILABILITY)) : ?>
                <li class="<?php echo ($current_page === 'availability') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'availability/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('availability'); ?></span><?php esc_html_e('Availability', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_VIEW_DISCOUNTS) || current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_DISCOUNTS)) : ?>
                <li class="<?php echo ($current_page === 'discounts') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'discounts/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('discounts'); ?></span><?php esc_html_e('Discounts', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_VIEW_AREAS) || current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_AREAS)) : ?>
                <li class="<?php echo ($current_page === 'areas') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'areas/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('areas'); ?></span><?php esc_html_e('Service Areas', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_WORKERS)) : ?>
                <li class="<?php echo ($current_page === 'workers') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'workers/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('workers'); ?></span><?php esc_html_e('Workers', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="nav-group">
            <h4 class="nav-group-title"><?php esc_html_e('Settings', 'NORDBOOKING'); ?></h4>
            <ul>
                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKING_FORM)) : ?>
                <li class="<?php echo ($current_page === 'booking-form') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'booking-form/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('booking_form'); ?></span><?php esc_html_e('Booking Form', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>

                <?php if (current_user_can(\NORDBOOKING\Classes\Auth::CAP_MANAGE_BUSINESS_SETTINGS)) : ?>
                <li class="<?php echo ($current_page === 'settings') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'settings/'); ?>"><span class="NORDBOOKING-menu-icon"><?php echo nordbooking_get_dashboard_menu_icon('settings'); ?></span><?php esc_html_e('Settings', 'NORDBOOKING'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="nav-group subscription-box">
        <h4 class="nav-group-title"><?php esc_html_e('Subscription', 'NORDBOOKING'); ?></h4>
        <?php
        $user_id = get_current_user_id();
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
        $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
        ?>
        <div class="subscription-status">
            <p><strong>Status:</strong> <?php echo esc_html(ucfirst($status)); ?></p>
            <?php if ($status === 'trial' || $status === 'active'): ?>
                <p><strong>Days Left:</strong> <?php echo esc_html($days_left); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($status === 'unsubscribed' || $status === 'expired_trial' || $status === 'expired'): ?>
            <button id="subscribe-now-btn" class="button button-primary"><?php esc_html_e('Subscribe Now', 'NORDBOOKING'); ?></button>
        <?php endif; ?>
    </div>
</aside>

<script>
jQuery(document).ready(function($) {
    $('#subscribe-now-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'nordbooking_create_checkout_session',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.checkout_url;
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('Subscribe Now');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('Subscribe Now');
            }
        });
    });
});
</script>
