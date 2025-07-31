<?php
/**
 * Dashboard Page: Overview - Refactored for shadcn UI
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

<div class="mobooking-overview">
    <div class="overview-header">
        <h1 class="overview-title">
            <?php printf(esc_html__('Welcome back, %s!', 'mobooking'), esc_html($user->display_name)); ?>
        </h1>
        <p class="overview-subtitle">
            <?php esc_html_e('Here\'s what\'s happening with your business today.', 'mobooking'); ?>
        </p>
    </div>

    <div class="overview-grid">
        <!-- KPI Cards -->
        <div class="card kpi-card">
            <div class="card-header">
                <h3 class="card-title">Total Bookings</h3>
                <div class="card-icon"><i data-feather="book-open"></i></div>
            </div>
            <div class="card-content">
                <p class="card-value" id="total-bookings-value">--</p>
                <p class="card-description">All pending & confirmed</p>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="card-header">
                <h3 class="card-title">Total Revenue</h3>
                <div class="card-icon"><i data-feather="dollar-sign"></i></div>
            </div>
            <div class="card-content">
                <p class="card-value" id="total-revenue-value">--</p>
                <p class="card-description">This month</p>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="card-header">
                <h3 class="card-title">Today's Revenue</h3>
                <div class="card-icon"><i data-feather="bar-chart-2"></i></div>
            </div>
            <div class="card-content">
                <p class="card-value" id="revenue-breakdown-value">--</p>
                <p class="card-description">Revenue for today</p>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="card-header">
                <h3 class="card-title">Completion Rate</h3>
                <div class="card-icon"><i data-feather="check-circle"></i></div>
            </div>
            <div class="card-content">
                <p class="card-value" id="completion-rate-value">--</p>
                <p class="card-description">Of all bookings</p>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card recent-bookings-card">
            <div class="card-header">
                <h3 class="card-title">Recent Bookings</h3>
            </div>
            <div class="card-content" id="recent-bookings-list">
                <!-- Recent bookings will be loaded here -->
            </div>
            <div class="card-footer">
                <a href="<?php echo esc_url($dashboard_base_url . 'bookings/'); ?>" class="btn btn-ghost">View All</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card quick-actions-card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-content">
                <a href="<?php echo esc_url($dashboard_base_url . 'services/'); ?>" class="btn btn-secondary"><i data-feather="plus"></i> Add Service</a>
                <a href="<?php echo esc_url($dashboard_base_url . 'discounts/'); ?>" class="btn btn-secondary"><i data-feather="percent"></i> Create Discount</a>
                <a href="<?php echo esc_url($dashboard_base_url . 'areas/'); ?>" class="btn btn-secondary"><i data-feather="map"></i> Service Areas</a>
                <a href="<?php echo esc_url($dashboard_base_url . 'settings/'); ?>" class="btn btn-secondary"><i data-feather="settings"></i> Settings</a>
            </div>
        </div>

        <!-- Setup Progress -->
        <div class="card setup-progress-card">
            <div class="card-header">
                <h3 class="card-title">Setup Progress</h3>
            </div>
            <div class="card-content" id="setup-progress-list">
                <!-- Progress steps will be loaded here -->
            </div>
        </div>

        <!-- Share Link -->
        <div class="card share-link-card">
            <div class="card-header">
                <h3 class="card-title">Share Your Booking Link</h3>
            </div>
            <div class="card-content">
                <p class="card-description">Add your booking form link to your website and social media.</p>
                <div class="share-link-input-wrapper">
                    <input type="text" readonly value="<?php echo esc_url(home_url('/booking/')); // This is a placeholder, will need to be made dynamic ?>" class="share-link-input">
                    <button class="btn btn-primary" id="copy-share-link-button"><i data-feather="copy"></i></button>
                </div>
            </div>
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
    is_worker: <?php echo $is_worker ? 'true' : 'false'; ?>,
    dashboard_base_url: '<?php echo esc_js($dashboard_base_url); ?>',
    i18n: {
        time_ago_just_now: '<?php esc_html_e('Just now', 'mobooking'); ?>',
        time_ago_seconds_suffix: '<?php esc_html_e('s ago', 'mobooking'); ?>',
        time_ago_minutes_suffix: '<?php esc_html_e('m ago', 'mobooking'); ?>',
        time_ago_hours_suffix: '<?php esc_html_e('h ago', 'mobooking'); ?>',
        time_ago_days_suffix: '<?php esc_html_e('d ago', 'mobooking'); ?>',
        loading: '<?php esc_html_e('Loading...', 'mobooking'); ?>',
        no_data: '<?php esc_html_e('No data available', 'mobooking'); ?>',
        error: '<?php esc_html_e('Error loading data', 'mobooking'); ?>',
        copied: '<?php esc_html_e('Copied!', 'mobooking'); ?>'
    }
};
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>