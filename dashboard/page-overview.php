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
            <?php esc_html_e('Here\'s what\'s happening with your cleaning business today.', 'mobooking'); ?>
        </p>
    </div>

    <!-- Top KPI Cards -->
    <div class="dashboard-kpi-grid">
        <!-- Total Bookings Today -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Bookings Today', 'mobooking'); ?></span>
                <div class="kpi-icon bookings">📅</div>
            </div>
            <div class="kpi-value" id="kpi-bookings-today">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="bookings-today-trend">
                <span>↗</span> +3 from yesterday
            </div>
        </div>

        <!-- Bookings This Month -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Bookings This Month', 'mobooking'); ?></span>
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
            <div class="kpi-value" id="kpi-upcoming-count">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend neutral" id="upcoming-trend">
                <span>→</span> Next 7 days
            </div>
        </div>

        <!-- Revenue This Month (Only for non-workers) -->
        <?php if (!$is_worker): ?>
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Revenue This Month', 'mobooking'); ?></span>
                <div class="kpi-icon revenue">💰</div>
            </div>
            <div class="kpi-value" id="kpi-revenue-month">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="revenue-trend">
                <span>↗</span> +8% from last month
            </div>
        </div>
        <?php endif; ?>

        <!-- Completed Bookings -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Completed', 'mobooking'); ?></span>
                <div class="kpi-icon completed">✅</div>
            </div>
            <div class="kpi-value" id="kpi-completed-count">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="completed-trend">
                <span>↗</span> This month
            </div>
        </div>

        <!-- Cancelled Bookings -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Cancelled', 'mobooking'); ?></span>
                <div class="kpi-icon cancelled">❌</div>
            </div>
            <div class="kpi-value" id="kpi-cancelled-count">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend negative" id="cancelled-trend">
                <span>↘</span> This month
            </div>
        </div>

        <!-- Average Booking Value (Only for non-workers) -->
        <?php if (!$is_worker): ?>
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Avg Booking Value', 'mobooking'); ?></span>
                <div class="kpi-icon average">💵</div>
            </div>
            <div class="kpi-value" id="kpi-avg-booking-value">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="avg-booking-trend">
                <span>↗</span> +5% from last month
            </div>
        </div>
        <?php endif; ?>

        <!-- New Customers -->
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('New Customers', 'mobooking'); ?></span>
                <div class="kpi-icon new-customers">👥</div>
            </div>
            <div class="kpi-value" id="kpi-new-customers">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="new-customers-trend">
                <span>↗</span> This month
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Live Activity Feed -->
        <div class="activity-container">
            <div class="activity-header">
                <h3 class="activity-title">
                    <span class="activity-icon">🔄</span>
                    <?php esc_html_e('Live Activity Feed', 'mobooking'); ?>
                </h3>
                <a href="<?php echo esc_url($dashboard_base_url . 'bookings/'); ?>" class="view-all-link">
                    <?php esc_html_e('View All', 'mobooking'); ?>
                </a>
            </div>
            <div class="activity-content">
                <div id="live-activity-feed">
                    <div class="loading-activity">
                        <span class="loading"></span>
                        <p><?php esc_html_e('Loading recent activities...', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Services -->
        <div class="top-services-container">
            <div class="services-header">
                <h3 class="services-title">
                    <span class="services-icon">🏆</span>
                    <?php esc_html_e('Top Services', 'mobooking'); ?>
                </h3>
                <a href="<?php echo esc_url($dashboard_base_url . 'services/'); ?>" class="view-all-link">
                    <?php esc_html_e('Manage Services', 'mobooking'); ?>
                </a>
            </div>
            <div class="services-content">
                <div id="top-services-list">
                    <div class="loading-services">
                        <span class="loading"></span>
                        <p><?php esc_html_e('Loading top services...', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Status Chart -->
        <div class="status-chart-container">
            <div class="chart-header">
                <h3 class="chart-title">
                    <span class="chart-icon">📊</span>
                    <?php esc_html_e('Booking Status', 'mobooking'); ?>
                </h3>
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

        <!-- Customer Insights -->
        <div class="customer-insights-container">
            <div class="insights-header">
                <h3 class="insights-title">
                    <span class="insights-icon">👥</span>
                    <?php esc_html_e('Customer Insights', 'mobooking'); ?>
                </h3>
            </div>
            <div class="insights-content">
                <div class="insight-item">
                    <div class="insight-label"><?php esc_html_e('New Customers', 'mobooking'); ?></div>
                    <div class="insight-value" id="insight-new-customers">
                        <span class="loading"></span>
                    </div>
                    <div class="insight-percentage" id="insight-new-percentage">0%</div>
                </div>
                <div class="insight-item">
                    <div class="insight-label"><?php esc_html_e('Returning Customers', 'mobooking'); ?></div>
                    <div class="insight-value" id="insight-returning-customers">
                        <span class="loading"></span>
                    </div>
                    <div class="insight-percentage" id="insight-returning-percentage">0%</div>
                </div>
                <div class="insight-item">
                    <div class="insight-label"><?php esc_html_e('Customer Retention', 'mobooking'); ?></div>
                    <div class="insight-value" id="insight-retention-rate">
                        <span class="loading"></span>
                    </div>
                    <div class="insight-percentage" id="insight-retention-percentage">0%</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-container">
            <div class="actions-header">
                <h3 class="actions-title">
                    <span class="actions-icon">⚡</span>
                    <?php esc_html_e('Quick Actions', 'mobooking'); ?>
                </h3>
            </div>
            <div class="actions-content">
                <div class="action-grid">
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
                    <button class="action-btn action-manage-discounts" onclick="location.href='<?php echo esc_url($dashboard_base_url . 'discounts/'); ?>'">
                        <span class="action-icon">🏷️</span>
                        <span class="action-label"><?php esc_html_e('Manage Discounts', 'mobooking'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section - Subscription Plan Box -->
    <div class="subscription-section">
        <div class="subscription-container">
            <div class="subscription-header">
                <h3 class="subscription-title">
                    <span class="subscription-icon">💎</span>
                    <?php esc_html_e('Your Subscription', 'mobooking'); ?>
                </h3>
                <div class="subscription-status">
                    <span class="status-badge status-active"><?php esc_html_e('Active', 'mobooking'); ?></span>
                </div>
            </div>
            <div class="subscription-content">
                <div class="plan-info">
                    <div class="plan-name"><?php esc_html_e('Professional Plan', 'mobooking'); ?></div>
                    <div class="plan-price">$29<span class="plan-period">/month</span></div>
                </div>
                <div class="usage-info">
                    <div class="usage-item">
                        <span class="usage-label"><?php esc_html_e('Bookings this month:', 'mobooking'); ?></span>
                        <span class="usage-value" id="usage-bookings">0 / 500</span>
                    </div>
                    <div class="usage-item">
                        <span class="usage-label"><?php esc_html_e('Services:', 'mobooking'); ?></span>
                        <span class="usage-value" id="usage-services">0 / 50</span>
                    </div>
                    <div class="usage-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" id="usage-progress-bar" style="width: 0%"></div>
                        </div>
                        <span class="progress-text" id="usage-progress-text">0% used</span>
                    </div>
                </div>
                <div class="subscription-actions">
                    <button class="btn btn-primary btn-upgrade">
                        <?php esc_html_e('Upgrade Plan', 'mobooking'); ?>
                    </button>
                    <button class="btn btn-secondary btn-manage">
                        <?php esc_html_e('Manage Subscription', 'mobooking'); ?>
                    </button>
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