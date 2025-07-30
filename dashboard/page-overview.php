<?php
/**
 * Dashboard Page: Overview - Refactored KPI-focused version
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get current user and initialize managers
$user = wp_get_current_user();
$current_user_id = get_current_user_id();

// Initialize managers
$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

// Get currency symbol
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol('USD');
if ($current_user_id && isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($current_user_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}

// Determine user for data fetching (handle workers)
$data_user_id = $current_user_id;
$is_worker = false;
if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
    $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
    if ($owner_id) {
        $data_user_id = $owner_id;
        $is_worker = true;
    }
}

// Get dashboard URLs
$dashboard_base_url = home_url('/dashboard/');
?>

<div class="mobooking-overview-refactored">

    <!-- Statistics Widgets -->
    <div class="card widget-span-3">
        <div class="card-header">
            <h3 class="card-title">Total Bookings</h3>
            <p class="card-description">pending, confirmed</p>
        </div>
        <div class="card-content">
            <p class="text-2xl font-bold" id="total-bookings-value">--</p>
        </div>
    </div>

    <div class="card widget-span-3">
        <div class="card-header">
            <h3 class="card-title">Total Revenue</h3>
            <p class="card-description">this month</p>
        </div>
        <div class="card-content">
            <p class="text-2xl font-bold" id="total-revenue-value">--</p>
        </div>
    </div>

    <div class="card widget-span-3">
        <div class="card-header">
            <h3 class="card-title">This Month</h3>
            <p class="card-description">this week, today</p>
        </div>
        <div class="card-content">
            <p class="text-2xl font-bold" id="revenue-breakdown-value">--</p>
        </div>
    </div>

    <div class="card widget-span-3">
        <div class="card-header">
            <h3 class="card-title">Completion Rate</h3>
            <p class="card-description">completed</p>
        </div>
        <div class="card-content">
            <p class="text-2xl font-bold" id="completion-rate-value">--</p>
        </div>
    </div>

    <!-- Activity Widgets -->
    <div class="card widget-span-12">
        <div class="card-header">
            <h3 class="card-title">Recent Bookings</h3>
        </div>
        <div class="card-content" id="recent-bookings-list">
            <!-- Recent bookings will be loaded here -->
        </div>
        <div class="card-footer">
            <a href="#" class="btn btn-ghost">View All</a>
        </div>
    </div>

    <!-- Promotion & Sharing, Quick Actions, Setup Progress -->
    <div class="card widget-span-4">
        <div class="card-header">
            <h3 class="card-title">Share Your Booking Link</h3>
            <p class="card-description">Add your booking form link to your website and social media</p>
        </div>
        <div class="card-content">
            <!-- Sharing link content -->
        </div>
    </div>

    <div class="card widget-span-4">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-content">
            <a href="<?php echo esc_url($dashboard_base_url . 'services/'); ?>" class="btn btn-secondary">Add Service</a>
            <a href="<?php echo esc_url($dashboard_base_url . 'discounts/'); ?>" class="btn btn-secondary">Create Discount</a>
            <a href="<?php echo esc_url($dashboard_base_url . 'areas/'); ?>" class="btn btn-secondary">Service Areas</a>
            <a href="<?php echo esc_url($dashboard_base_url . 'settings/'); ?>" class="btn btn-secondary">Settings</a>
        </div>
    </div>

    <div class="card widget-span-4">
        <div class="card-header">
            <h3 class="card-title">Setup Progress</h3>
        </div>
        <div class="card-content" id="setup-progress-list">
            <!-- Progress steps will be loaded here -->
        </div>
    </div>

    <!-- Tips & Resources -->
    <div class="card widget-span-12">
        <div class="card-header">
            <h3 class="card-title">Tips & Resources</h3>
        </div>
        <div class="card-content" id="tips-resources-list">
            <!-- Tips will be loaded here -->
        </div>
    </div>
</div>

<!-- Scripts for localization -->
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var mobooking_overview_params = {
    ajax_url: ajaxurl,
    nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>',
    currency_symbol: '<?php echo esc_js($currency_symbol); ?>',
    is__worker: <?php echo $is_worker ? 'true' : 'false'; ?>,
    dashboard_base_url: '<?php echo esc_js($dashboard_base_url); ?>',
    i18n: {
        time_ago_just_now: '<?php esc_html_e('Just now', 'mobooking'); ?>',
        time_ago_seconds_suffix: '<?php esc_html_e('s ago', 'mobooking'); ?>',
        time_ago_minutes_suffix: '<?php esc_html_e('m ago', 'mobooking'); ?>',
        time_ago_hours_suffix: '<?php esc_html_e('h ago', 'mobooking'); ?>',
        time_ago_days_suffix: '<?php esc_html_e('d ago', 'mobooking'); ?>',
        loading: '<?php esc_html_e('Loading...', 'mobooking'); ?>',
        no_data: '<?php esc_html_e('No data available', 'mobooking'); ?>',
        error: '<?php esc_html_e('Error loading data', 'mobooking'); ?>'
    }
};
</script>