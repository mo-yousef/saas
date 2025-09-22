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

// Determine the display brand name - for workers, show business owner's name
$display_brand_name = esc_html__('NORDBOOKING', 'NORDBOOKING'); // Default brand name
$effective_user_id = $current_user_id;

// If user is a worker, get the business owner's information
if (class_exists('NORDBOOKING\Classes\Auth') && \NORDBOOKING\Classes\Auth::is_user_worker($current_user_id)) {
    $owner_id = \NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
    if ($owner_id) {
        $effective_user_id = $owner_id;
    }
}

if ($effective_user_id > 0 && isset($GLOBALS['nordbooking_settings_manager']) && class_exists('NORDBOOKING\Classes\Settings')) {
    // Ensure get_setting method exists before calling
    if (method_exists($GLOBALS['nordbooking_settings_manager'], 'get_setting')) {
        // Use the correct string key 'biz_name' as defined in Settings.php
        $business_name_setting = $GLOBALS['nordbooking_settings_manager']->get_setting($effective_user_id, 'biz_name', '');
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

        <?php if (!current_user_can(\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF)) : ?>
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
        <?php endif; ?>

    </nav>

    <!-- Subscription Status Box - Only show for business owners -->
    <?php if (!current_user_can(\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF)) : ?>
    <?php
    $user_id = get_current_user_id();
    $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
    
    // Auto-create trial subscription for new users
    if (!$subscription && current_user_can(\NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD)) {
        try {
            \NORDBOOKING\Classes\Subscription::create_trial_subscription($user_id);
            $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
        } catch (Exception $e) {
            error_log('Failed to create trial subscription: ' . $e->getMessage());
        }
    }
    
    $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
    $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
    
    // Auto-sync if user just came from a successful payment
    if (isset($_GET['success']) && $_GET['success'] == '1' && $status === 'trial') {
        \NORDBOOKING\Classes\Subscription::sync_subscription_status($user_id);
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
        $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
    }
    
    // Get subscription details
    $status_text = '';
    $status_class = '';
    $expiry_text = '';
    $action_text = '';
    $action_class = '';
    
    switch($status) {
        case 'active':
            $status_text = __('Pro', 'NORDBOOKING');
            $status_class = 'status-active';
            $expiry_text = $days_left > 0 ? sprintf(_n('%d day left', '%d days left', $days_left, 'NORDBOOKING'), $days_left) : __('Renews today', 'NORDBOOKING');
            $action_text = __('Manage', 'NORDBOOKING');
            $action_class = 'subscription-btn-secondary';
            break;
        case 'trial':
            $status_text = __('Trial', 'NORDBOOKING');
            $status_class = 'status-trial';
            $expiry_text = $days_left > 0 ? sprintf(_n('%d day left', '%d days left', $days_left, 'NORDBOOKING'), $days_left) : __('Expires today', 'NORDBOOKING');
            $action_text = __('Upgrade', 'NORDBOOKING');
            $action_class = 'subscription-btn-primary';
            break;
        case 'expired_trial':
            $status_text = __('Expired', 'NORDBOOKING');
            $status_class = 'status-expired';
            $expiry_text = __('Trial ended', 'NORDBOOKING');
            $action_text = __('Subscribe', 'NORDBOOKING');
            $action_class = 'subscription-btn-primary';
            break;
        case 'expired':
            $status_text = __('Expired', 'NORDBOOKING');
            $status_class = 'status-expired';
            $expiry_text = __('Renew now', 'NORDBOOKING');
            $action_text = __('Renew', 'NORDBOOKING');
            $action_class = 'subscription-btn-primary';
            break;
        case 'cancelled':
            $status_text = __('Cancelled', 'NORDBOOKING');
            $status_class = 'status-cancelled';
            $expiry_text = $days_left > 0 ? sprintf(_n('%d day left', '%d days left', $days_left, 'NORDBOOKING'), $days_left) : __('Access ended', 'NORDBOOKING');
            $action_text = __('Reactivate', 'NORDBOOKING');
            $action_class = 'subscription-btn-primary';
            break;
        default:
            $status_text = __('Free', 'NORDBOOKING');
            $status_class = 'status-none';
            $expiry_text = __('Limited access', 'NORDBOOKING');
            $action_text = __('Start Trial', 'NORDBOOKING');
            $action_class = 'subscription-btn-primary';
            break;
    }
    ?>
    
    <div class="subscription-status-box">
        <div class="subscription-compact">
            <div class="subscription-main">
                <div class="subscription-icon">
                    <?php echo nordbooking_get_dashboard_menu_icon('subscription'); ?>
                </div>
                <div class="subscription-info">
                    <div class="subscription-status-line">
                        <span class="status-badge <?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                        <?php if ($status === 'trial' && $days_left <= 3): ?>
                            <span class="warning-dot"></span>
                        <?php endif; ?>
                    </div>
                    <div class="subscription-expiry">
                        <?php echo esc_html($expiry_text); ?>
                    </div>
                </div>
            </div>
            <div class="subscription-action">
                <a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'subscription/'); ?>" class="subscription-btn btn btn-outline btn-sm <?php echo esc_attr($action_class); ?>">
                    <?php echo esc_html($action_text); ?>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</aside>


