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

<div class="mobooking-overview">
    <!-- Header -->
    <div class="overview-header">
        <h1 class="overview-title">
            <?php printf(esc_html__('Welcome back, %s!', 'mobooking'), esc_html($user->display_name)); ?>
        </h1>
        <p class="overview-subtitle">
            <?php esc_html_e('Here\'s what\'s happening with your business today.', 'mobooking'); ?>
        </p>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-container">
        <div class="actions-grid">
            <button class="action-btn action-add-booking" id="add-booking-action">
                <span class="action-icon">➕</span>
                <span class="action-label"><?php esc_html_e('Add Booking', 'mobooking'); ?></span>
            </button>
            <button class="action-btn action-manage-services" onclick="location.href='<?php echo esc_url($dashboard_base_url . 'services/'); ?>'">
                <span class="action-icon">🧹</span>
                <span class="action-label"><?php esc_html_e('Manage Services', 'mobooking'); ?></span>
            </button>
            <button class="action-btn action-view-bookings" onclick="location.href='<?php echo esc_url($dashboard_base_url . 'bookings/'); ?>'">
                <span class="action-icon">📋</span>
                <span class="action-label"><?php esc_html_e('View Bookings', 'mobooking'); ?></span>
            </button>
            <button class="action-btn action-manage-availability" onclick="location.href='<?php echo esc_url($dashboard_base_url . 'availability/'); ?>'">
                <span class="action-icon">📅</span>
                <span class="action-label"><?php esc_html_e('Set Availability', 'mobooking'); ?></span>
            </button>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="dashboard-kpi-grid">
        <!-- Revenue This Month -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Revenue (This Month)', 'mobooking'); ?></span>
                <div class="kpi-icon revenue">💰</div>
            </div>
            <div class="kpi-value" id="kpi-revenue-month">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="revenue-month-trend">
                <span>↗</span> +8% from last month
            </div>
        </div>

        <!-- Bookings This Month -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Bookings (This Month)', 'mobooking'); ?></span>
                <div class="kpi-icon bookings">📊</div>
            </div>
            <div class="kpi-value" id="kpi-bookings-month">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="bookings-month-trend">
                <span>↗</span> +12% from last month
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Upcoming Bookings', 'mobooking'); ?></span>
                <div class="kpi-icon upcoming">⏰</div>
            </div>
            <div class="kpi-value" id="kpi-upcoming-bookings">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend neutral" id="upcoming-bookings-trend">
                <span>→</span> Next 7 days
            </div>
        </div>

        <!-- New Customers -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('New Customers (This Month)', 'mobooking'); ?></span>
                <div class="kpi-icon new-customers">👥</div>
            </div>
            <div class="kpi-value" id="kpi-new-customers">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="new-customers-trend">
                <span>↗</span> +5 from last month
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="analytics-section">
        <h2 class="analytics-title"><?php esc_html_e('Analytics & Reports', 'mobooking'); ?></h2>
        <div class="analytics-grid">
            <!-- Booking Status Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title"><?php esc_html_e('Booking Volume', 'mobooking'); ?></h3>
                    <div class="chart-period-selector">
                        <button class="period-btn active" data-period="week"><?php esc_html_e('Week', 'mobooking'); ?></button>
                        <button class="period-btn" data-period="month"><?php esc_html_e('Month', 'mobooking'); ?></button>
                        <button class="period-btn" data-period="year"><?php esc_html_e('Year', 'mobooking'); ?></button>
                    </div>
                </div>
                <div class="chart-content">
                    <canvas id="booking-status-chart"></canvas>
                </div>
            </div>

            <!-- Tabbed Content -->
            <div class="tabbed-content-container">
                <div class="tab-header">
                    <button class="tab-link active" data-tab="live-activity"><?php esc_html_e('Live Activity', 'mobooking'); ?></button>
                    <button class="tab-link" data-tab="top-services"><?php esc_html_e('Top Services', 'mobooking'); ?></button>
                </div>
                <div class="tab-content">
                    <!-- Live Activity Feed -->
                    <div id="live-activity" class="tab-pane active">
                        <div id="live-activity-feed">
                            <div class="loading-activity">
                                <span class="loading"></span>
                                <p><?php esc_html_e('Loading recent activities...', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Top Services -->
                    <div id="top-services" class="tab-pane">
                        <div id="top-services-list">
                            <div class="loading-services">
                                <span class="loading"></span>
                                <p><?php esc_html_e('Loading top services...', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
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
        error: '<?php esc_html_e('Error loading data', 'mobooking'); ?>'
    }
};
</script>

<?php
// The main dashboard logic is handled in assets/js/dashboard-overview.js
// Chart.js and other required scripts should be enqueued via mobooking_enqueue_dashboard_scripts in functions.php
?>