<?php
/**
 * Dashboard Page: Overview - Enhanced shadcn UI with Interactive Widgets
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Feather Icons Fallback Function
if (!function_exists('mobooking_get_feather_icon_fallback')) {
    function mobooking_get_feather_icon_fallback($icon_name, $size = '20') {
        $icons = array(
            'dollar-sign' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
            'calendar' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
            'bar-chart-2' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
            'check-circle' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>',
            'trending-up' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline><polyline points="17,6 23,6 23,12"></polyline></svg>',
            'activity' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"></polyline></svg>',
            'refresh-cw' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23,4 23,10 17,10"></polyline><polyline points="1,20 1,14 7,14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>',
            'check' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"></polyline></svg>',
            'circle' => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle></svg>'
        );
        return $icons[$icon_name] ?? '';
    }
}

// Get current user and initialize managers
$user = wp_get_current_user();
$current_user_id = get_current_user_id();

// Initialize managers
$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
$settings_manager = new \MoBooking\Classes\Settings();

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

// Fetch data
$stats = $bookings_manager->get_booking_statistics($data_user_id);
$recent_bookings = $bookings_manager->get_bookings_by_tenant($data_user_id, ['limit' => 8]);
$setup_progress = $settings_manager->get_setup_progress($data_user_id);

// Calculate setup progress percentage
$setup_percentage = 0;
if (!empty($setup_progress['total_count']) && $setup_progress['total_count'] > 0) {
    $setup_percentage = round(($setup_progress['completed_count'] / $setup_progress['total_count']) * 100);
}

// Calculate advanced metrics
global $wpdb;
$bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');

// Today's revenue
$today_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed') AND DATE(booking_date) = CURDATE()",
    $data_user_id
));

// This week's bookings
$week_bookings = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $bookings_table WHERE user_id = %d AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)",
    $data_user_id
));

// Completion rate
$completed_bookings = $stats['by_status']['completed'] ?? 0;
$total_bookings = $stats['total'];
$completion_rate = ($total_bookings > 0) ? ($completed_bookings / $total_bookings) * 100 : 0;

// Average booking value
$avg_booking_value = ($total_bookings > 0) ? ($stats['total_revenue'] / $total_bookings) : 0;

// Get active services count
$services_result = $services_manager->get_services_by_user($data_user_id, ['status' => 'active']);
$active_services = $services_result['total_count'] ?? 0;

// Get currency symbol
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));

// Get dashboard URLs
$dashboard_base_url = home_url('/dashboard/');
?>


<div class="mobooking-overview-enhanced">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1><?php esc_html_e('Dashboard Overview', 'mobooking'); ?></h1>
        <p>
            <?php if ($is_worker): ?>
                <?php esc_html_e('Welcome back! Here\'s your business overview.', 'mobooking'); ?>
            <?php else: ?>
                <?php printf(esc_html__('Welcome back, %s! Here\'s your business overview.', 'mobooking'), esc_html($user->display_name)); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Main Overview Grid -->
    <div class="overview-grid">
        
        <!-- KPI Cards Row 1 -->
        <div class="grid-span-3">
            <div class="card" data-widget="total-revenue">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Total Revenue', 'mobooking'); ?></h3>
                    <span class="card-icon">
                        <i data-feather="dollar-sign"></i>
                    </span>
                </div>
                <div class="card-content">
                    <div class="kpi-value" id="total-revenue-value">
                        <?php echo esc_html($currency_symbol . number_format($stats['total_revenue'] ?? 0, 2)); ?>
                    </div>
                    <div class="kpi-trend positive" id="total-revenue-trend">
                        <i data-feather="trending-up"></i>
                        <span>+20.1% from last month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-span-3">
            <div class="card" data-widget="total-bookings">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Total Bookings', 'mobooking'); ?></h3>
                    <span class="card-icon">
                        <i data-feather="calendar"></i>
                    </span>
                </div>
                <div class="card-content">
                    <div class="kpi-value" id="total-bookings-value">
                        <?php echo esc_html($stats['total'] ?? 0); ?>
                    </div>
                    <div class="kpi-trend positive" id="total-bookings-trend">
                        <i data-feather="trending-up"></i>
                        <span>+12.5% from last month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-span-3">
            <div class="card" data-widget="today-revenue">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Today\'s Revenue', 'mobooking'); ?></h3>
                    <span class="card-icon">
                        <i data-feather="bar-chart-2"></i>
                    </span>
                </div>
                <div class="card-content">
                    <div class="kpi-value" id="today-revenue-value">
                        <?php echo esc_html($currency_symbol . number_format($today_revenue ?? 0, 2)); ?>
                    </div>
                    <div class="kpi-trend positive" id="today-revenue-trend">
                        <i data-feather="trending-up"></i>
                        <span>+8.2% from yesterday</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-span-3">
            <div class="card" data-widget="completion-rate">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Completion Rate', 'mobooking'); ?></h3>
                    <span class="card-icon">
                        <i data-feather="check-circle"></i>
                    </span>
                </div>
                <div class="card-content">
                    <div class="kpi-value" id="completion-rate-value">
                        <?php echo esc_html(number_format($completion_rate, 1)); ?>%
                    </div>
                    <div class="kpi-trend neutral" id="completion-rate-trend">
                        <i data-feather="minus"></i>
                        <span>No change</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="grid-span-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Revenue Overview', 'mobooking'); ?></h3>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <div class="period-selector">
                            <button class="period-btn active" data-period="week"><?php esc_html_e('Week', 'mobooking'); ?></button>
                            <button class="period-btn" data-period="month"><?php esc_html_e('Month', 'mobooking'); ?></button>
                            <button class="period-btn" data-period="year"><?php esc_html_e('Year', 'mobooking'); ?></button>
                        </div>
                        <button class="refresh-btn" id="refresh-chart" title="<?php esc_attr_e('Refresh Chart', 'mobooking'); ?>">
                            <i data-feather="refresh-cw"></i>
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="revenue-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid-span-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Quick Stats', 'mobooking'); ?></h3>
                    <span class="card-icon">
                        <i data-feather="activity"></i>
                    </span>
                </div>
                <div class="card-content">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: hsl(var(--mobk-muted-foreground)); font-size: 0.875rem;">
                                <?php esc_html_e('This Week', 'mobooking'); ?>
                            </span>
                            <span style="font-weight: 600;" id="week-bookings">
                                <?php echo esc_html($week_bookings); ?> <?php esc_html_e('bookings', 'mobooking'); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: hsl(var(--mobk-muted-foreground)); font-size: 0.875rem;">
                                <?php esc_html_e('Avg. Booking Value', 'mobooking'); ?>
                            </span>
                            <span style="font-weight: 600;" id="avg-booking-value">
                                <?php echo esc_html($currency_symbol . number_format($avg_booking_value, 2)); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: hsl(var(--mobk-muted-foreground)); font-size: 0.875rem;">
                                <?php esc_html_e('Active Services', 'mobooking'); ?>
                            </span>
                            <span style="font-weight: 600;" id="active-services">
                                <?php echo esc_html($active_services); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: hsl(var(--mobk-muted-foreground)); font-size: 0.875rem;">
                                <?php esc_html_e('Setup Progress', 'mobooking'); ?>
                            </span>
                            <span style="font-weight: 600;">
                                <?php echo esc_html($setup_percentage); ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="grid-span-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Recent Bookings', 'mobooking'); ?></h3>
                    <button class="refresh-btn" id="refresh-bookings" title="<?php esc_attr_e('Refresh Bookings', 'mobooking'); ?>">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                        <?php if (!empty($recent_bookings['bookings'])) : ?>
                            <?php foreach ($recent_bookings['bookings'] as $booking) : ?>
                                <div class="booking-item">
                                    <div class="booking-customer">
                                        <div class="customer-avatar">
                                            <?php echo esc_html(strtoupper(substr($booking['customer_name'], 0, 2))); ?>
                                        </div>
                                        <div class="customer-info">
                                            <h4><?php echo esc_html($booking['customer_name']); ?></h4>
                                            <p><?php echo esc_html($booking['customer_email']); ?></p>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="booking-amount">
                                            <?php echo esc_html($currency_symbol . number_format($booking['total_price'], 2)); ?>
                                        </div>
                                        <div class="booking-status">
                                            <?php echo esc_html(ucfirst($booking['status'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div style="text-align: center; padding: 2rem; color: hsl(var(--mobk-muted-foreground));">
                                <i data-feather="calendar" style="width: 3rem; height: 3rem; margin-bottom: 1rem;"></i>
                                <p><?php esc_html_e('No recent bookings found.', 'mobooking'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Progress -->
        <div class="grid-span-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Setup Progress', 'mobooking'); ?></h3>
                    <span style="font-size: 0.875rem; color: hsl(var(--mobk-muted-foreground));">
                        <?php echo esc_html($setup_percentage); ?>%
                    </span>
                </div>
                <div class="card-content">
                    <ul class="setup-progress-list" id="setup-progress-list">
                        <?php foreach ($setup_progress['steps'] as $step) : ?>
                            <li class="setup-progress-item <?php echo $step['completed'] ? 'completed' : ''; ?>">
                                <div class="icon">
                                    <i data-feather="<?php echo $step['completed'] ? 'check-circle' : 'circle'; ?>"></i>
                                </div>
                                <span><?php echo esc_html($step['label']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid-span-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Recent Activity', 'mobooking'); ?></h3>
                    <button class="refresh-btn" id="refresh-activity" title="<?php esc_attr_e('Refresh Activity', 'mobooking'); ?>">
                        <i data-feather="refresh-cw"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="activity-feed" id="activity-feed">
                        <!-- Activity items will be loaded via JavaScript -->
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i data-feather="calendar"></i>
                            </div>
                            <div class="activity-content">
                                <h4><?php esc_html_e('New booking received', 'mobooking'); ?></h4>
                                <p><?php esc_html_e('John Doe booked Hair Cut service • 15m ago', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i data-feather="check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4><?php esc_html_e('Booking completed', 'mobooking'); ?></h4>
                                <p><?php esc_html_e('Booking #1234 marked as completed • 2h ago', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid-span-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('Performance Metrics', 'mobooking'); ?></h3>
                    <span class="card-icon">
                        <i data-feather="trending-up"></i>
                    </span>
                </div>
                <div class="card-content">
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="performance-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

