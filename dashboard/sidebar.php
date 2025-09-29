<?php
/**
 * The sidebar for the NORDBOOKING Dashboard.
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_page = isset($GLOBALS['nordbooking_current_dashboard_view']) ? $GLOBALS['nordbooking_current_dashboard_view'] : 'overview';
$dashboard_base_url = home_url('/dashboard/');

$current_user_id = get_current_user_id();

// Check if subscription has expired (trial or paid)
$is_subscription_expired = false;
$subscription_status = '';
if (class_exists('\NORDBOOKING\Classes\Subscription')) {
    $subscription_status = \NORDBOOKING\Classes\Subscription::get_subscription_status($current_user_id);
    $is_subscription_expired = nordbooking_is_subscription_expired($current_user_id);
}

$user_nordbooking_role_display_name = '';

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
        <?php if ($is_subscription_expired) : ?>
            <div style="background: #ef4444; color: white; padding: 0.5rem; text-align: center; font-size: 0.75rem; border-radius: 0.25rem; margin-top: 0.5rem;">
                <?php 
                if ($subscription_status === 'expired_trial') {
                    esc_html_e('Trial Expired', 'NORDBOOKING');
                } else {
                    esc_html_e('Plan Expired', 'NORDBOOKING');
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- <?php if ( ! empty( $user_nordbooking_role_display_name ) ) : ?>
        <div class="dashboard-user-role" style="padding: 10px 1rem; color: #E0E0E0;background-color: rgba(0,0,0,0.1);">
            <?php echo esc_html( sprintf( __( 'Role: %s', 'NORDBOOKING' ), $user_nordbooking_role_display_name ) ); ?>
        </div>
    <?php endif; ?> -->

    <nav class="dashboard-nav">
        <?php if ($is_subscription_expired) : ?>
            <!-- Subscription Expired - Limited Navigation -->
            <div class="nav-group">
                <h4 class="nav-group-title"><?php esc_html_e('Account', 'NORDBOOKING'); ?></h4>
                <ul>
                    <li class="<?php echo ($current_page === 'subscription') ? 'active' : ''; ?>"><a href="<?php echo esc_url($dashboard_base_url . 'subscription/'); ?>"><span class="NORDBOOKING-menu-icon">💳</span>
                        <?php 
                        if ($subscription_status === 'expired_trial') {
                            esc_html_e('Subscribe', 'NORDBOOKING');
                        } else {
                            esc_html_e('Renew Plan', 'NORDBOOKING');
                        }
                        ?>
                    </a></li>
                </ul>
            </div>
            
            <!-- Disabled Navigation Notice -->
            <div class="nav-group">
                <div class="nav-disabled-notice">
                    <div class="notice-icon">🔒</div>
                    <div class="notice-content">
                        <h5><?php esc_html_e('Access Restricted', 'NORDBOOKING'); ?></h5>
                        <p><?php esc_html_e('Your plan has expired. Renew to access all features.', 'NORDBOOKING'); ?></p>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <!-- Normal Navigation -->
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
                    
                    <li class="<?php echo ($current_page === 'subscription') ? 'active' : ''; ?>"><a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'subscription/'); ?>"><span class="NORDBOOKING-menu-icon">💳</span><?php esc_html_e('Subscription', 'NORDBOOKING'); ?></a></li>
                </ul>
            </div>
            <?php endif; ?>
        <?php endif; // End trial expiration check ?>

    </nav>

    <!-- Subscription Status Box - Show for business owners -->
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
            $action_text = __('Upgrade to Pro', 'NORDBOOKING');
            $action_class = 'subscription-btn-primary';
            break;
        case 'expired_trial':
            $status_text = __('Expired', 'NORDBOOKING');
            $status_class = 'status-expired';
            $expiry_text = __('Trial ended', 'NORDBOOKING');
            $action_text = __('Subscribe Now', 'NORDBOOKING');
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
                    💳
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
                <a href="<?php echo esc_url(trailingslashit($dashboard_base_url) . 'subscription/'); ?>" class="subscription-btn btn btn-outline btn-sm <?php echo esc_attr($action_class); ?><?php echo ($status === 'trial') ? ' upgrade-highlight' : ''; ?>">
                    <?php echo esc_html($action_text); ?>
                </a>
            </div>
        </div>
    </div>
    
    <style>
    .subscription-status-box {
        margin: 1rem 0;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .subscription-compact {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .subscription-main {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .subscription-icon {
        font-size: 1.25rem;
    }
    
    .subscription-info {
        flex: 1;
    }
    
    .subscription-status-line {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }
    
    .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    
    .status-badge.status-trial {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    
    .status-badge.status-active {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }
    
    .status-badge.status-expired {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    
    .subscription-expiry {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .subscription-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: all 0.2s ease;
        width: 100%;
        text-align: center;
    }
    
    .subscription-btn-primary {
        background: #3b82f6;
        color: white;
        border: 1px solid #3b82f6;
    }
    
    .subscription-btn-primary:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }
    
    .subscription-btn-secondary {
        background: transparent;
        color: rgba(255, 255, 255, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .subscription-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .upgrade-highlight {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        animation: pulse-glow 2s infinite;
    }
    
    .upgrade-highlight:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 8px rgba(59, 130, 246, 0.4);
    }
    
    .warning-dot {
        width: 8px;
        height: 8px;
        background: #f59e0b;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        50% {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    .nav-disabled-notice {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 0.5rem;
        padding: 1rem;
        margin: 1rem 0;
        text-align: center;
    }
    
    .notice-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .notice-content h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #dc2626;
        margin: 0 0 0.25rem 0;
    }
    
    .notice-content p {
        font-size: 0.75rem;
        color: #7f1d1d;
        margin: 0;
        line-height: 1.4;
    }
    </style>
    <?php endif; ?>
</aside>


