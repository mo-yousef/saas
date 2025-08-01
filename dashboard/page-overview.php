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

<style>
:root {
    --mobk-background: hsl(0 0% 100%);
    --mobk-foreground: hsl(222.2 84% 4.9%);
    --mobk-card: hsl(0 0% 100%);
    --mobk-card-foreground: hsl(222.2 84% 4.9%);
    --mobk-primary: hsl(221.2 83.2% 53.3%);
    --mobk-primary-foreground: hsl(210 40% 98%);
    --mobk-secondary: hsl(210 40% 96.1%);
    --mobk-secondary-foreground: hsl(222.2 84% 4.9%);
    --mobk-muted: hsl(210 40% 96.1%);
    --mobk-muted-foreground: hsl(215.4 16.3% 46.9%);
    --mobk-destructive: hsl(0 84.2% 60.2%);
    --mobk-destructive-foreground: hsl(210 40% 98%);
    --mobk-border: hsl(214.3 31.8% 91.4%);
    --mobk-input: hsl(214.3 31.8% 91.4%);
    --mobk-ring: hsl(221.2 83.2% 53.3%);
    --mobk-radius: 0.5rem;
    --mobk-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --mobk-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --mobk-success: hsl(142.1 76.2% 36.3%);
    --mobk-warning: hsl(45.4 93.4% 47.5%);
}

.mobooking-overview-enhanced {
    min-height: 100vh;
    background: hsl(var(--mobk-background));
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    padding: 1.5rem;
    color: hsl(var(--mobk-foreground));
}

.dashboard-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid hsl(var(--mobk-border));
}

.dashboard-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: hsl(var(--mobk-foreground));
}

.dashboard-header p {
    color: hsl(var(--mobk-muted-foreground));
    margin: 0;
    font-size: 1rem;
}

.overview-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.grid-span-3 { grid-column: span 3; }
.grid-span-4 { grid-column: span 4; }
.grid-span-6 { grid-column: span 6; }
.grid-span-8 { grid-column: span 8; }
.grid-span-12 { grid-column: span 12; }

.card {
    background: hsl(var(--mobk-card));
    border: 1px solid hsl(var(--mobk-border));
    border-radius: var(--mobk-radius);
    box-shadow: var(--mobk-shadow-sm);
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.card:hover {
    box-shadow: var(--mobk-shadow);
    transform: translateY(-1px);
}

.card-header {
    padding: 1.5rem 1.5rem 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--mobk-muted-foreground));
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.card-icon {
    width: 1.25rem;
    height: 1.25rem;
    color: hsl(var(--mobk-muted-foreground));
}

.card-content {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
}

.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(var(--mobk-foreground));
    margin: 0.5rem 0 0.25rem 0;
    line-height: 1.2;
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.kpi-trend.positive { color: hsl(var(--mobk-success)); }
.kpi-trend.negative { color: hsl(var(--mobk-destructive)); }
.kpi-trend.neutral { color: hsl(var(--mobk-muted-foreground)); }

.kpi-trend svg {
    width: 0.75rem;
    height: 0.75rem;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-top: 1rem;
}

.recent-bookings-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.booking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border: 1px solid hsl(var(--mobk-border));
    border-radius: calc(var(--mobk-radius) - 2px);
    background: hsl(var(--mobk-muted) / 0.3);
    transition: background-color 0.2s ease;
}

.booking-item:hover {
    background: hsl(var(--mobk-muted) / 0.6);
}

.booking-customer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.customer-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: hsl(var(--mobk-primary) / 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: hsl(var(--mobk-primary));
    font-weight: 600;
    font-size: 0.875rem;
}

.customer-info h4 {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--mobk-foreground));
}

.customer-info p {
    margin: 0;
    font-size: 0.75rem;
    color: hsl(var(--mobk-muted-foreground));
}

.booking-amount {
    font-weight: 600;
    color: hsl(var(--mobk-foreground));
    text-align: right;
}

.booking-status {
    font-size: 0.75rem;
    color: hsl(var(--mobk-muted-foreground));
}

.setup-progress-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.setup-progress-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    border-radius: calc(var(--mobk-radius) - 2px);
    transition: background-color 0.2s ease;
}

.setup-progress-item:hover {
    background: hsl(var(--mobk-muted) / 0.5);
}

.setup-progress-item.completed {
    color: hsl(var(--mobk-success));
}

.setup-progress-item .icon {
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.setup-progress-item.completed .icon {
    color: hsl(var(--mobk-success));
}

.activity-feed {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: calc(var(--mobk-radius) - 2px);
    background: hsl(var(--mobk-muted) / 0.3);
}

.activity-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: hsl(var(--mobk-primary) / 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: hsl(var(--mobk-primary));
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-content h4 {
    margin: 0 0 0.25rem 0;
    font-size: 0.875rem;
    font-weight: 500;
}

.activity-content p {
    margin: 0;
    font-size: 0.75rem;
    color: hsl(var(--mobk-muted-foreground));
}

.period-selector {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
}

.period-btn {
    padding: 0.5rem 1rem;
    border: 1px solid hsl(var(--mobk-border));
    background: hsl(var(--mobk-background));
    color: hsl(var(--mobk-foreground));
    border-radius: calc(var(--mobk-radius) - 2px);
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.period-btn:hover {
    background: hsl(var(--mobk-muted));
}

.period-btn.active {
    background: hsl(var(--mobk-primary));
    color: hsl(var(--mobk-primary-foreground));
    border-color: hsl(var(--mobk-primary));
}

.refresh-btn {
    padding: 0.5rem;
    border: 1px solid hsl(var(--mobk-border));
    background: hsl(var(--mobk-background));
    color: hsl(var(--mobk-muted-foreground));
    border-radius: calc(var(--mobk-radius) - 2px);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.refresh-btn:hover {
    background: hsl(var(--mobk-muted));
    color: hsl(var(--mobk-foreground));
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}

@media (max-width: 1024px) {
    .grid-span-3 { grid-column: span 6; }
    .grid-span-4 { grid-column: span 6; }
    .grid-span-8 { grid-column: span 12; }
}

@media (max-width: 768px) {
    .overview-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .grid-span-3,
    .grid-span-4,
    .grid-span-6,
    .grid-span-8,
    .grid-span-12 {
        grid-column: span 1;
    }
    
    .mobooking-overview-enhanced {
        padding: 1rem;
    }
    
    .kpi-value {
        font-size: 1.5rem;
    }
}
</style>

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

<!-- Scripts for localization and functionality -->
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var mobooking_overview_params = {
    ajax_url: ajaxurl,
    nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>',
    currency_symbol: '<?php echo esc_js($currency_symbol); ?>',
    is_worker: <?php echo $is_worker ? 'true' : 'false'; ?>,
    dashboard_base_url: '<?php echo esc_js($dashboard_base_url); ?>',
    user_id: <?php echo intval($data_user_id); ?>,
    current_time: '<?php echo current_time('mysql'); ?>',
    stats: {
        total_revenue: <?php echo floatval($stats['total_revenue'] ?? 0); ?>,
        total_bookings: <?php echo intval($stats['total'] ?? 0); ?>,
        today_revenue: <?php echo floatval($today_revenue ?? 0); ?>,
        completion_rate: <?php echo floatval($completion_rate); ?>,
        week_bookings: <?php echo intval($week_bookings ?? 0); ?>,
        avg_booking_value: <?php echo floatval($avg_booking_value); ?>,
        active_services: <?php echo intval($active_services); ?>,
        setup_percentage: <?php echo intval($setup_percentage); ?>
    },
    i18n: {
        time_ago_just_now: '<?php esc_html_e('Just now', 'mobooking'); ?>',
        time_ago_seconds_suffix: '<?php esc_html_e('s ago', 'mobooking'); ?>',
        time_ago_minutes_suffix: '<?php esc_html_e('m ago', 'mobooking'); ?>',
        time_ago_hours_suffix: '<?php esc_html_e('h ago', 'mobooking'); ?>',
        time_ago_days_suffix: '<?php esc_html_e('d ago', 'mobooking'); ?>',
        loading: '<?php esc_html_e('Loading...', 'mobooking'); ?>',
        no_data: '<?php esc_html_e('No data available', 'mobooking'); ?>',
        error: '<?php esc_html_e('Error loading data', 'mobooking'); ?>',
        copied: '<?php esc_html_e('Copied!', 'mobooking'); ?>',
        new_booking: '<?php esc_html_e('New booking received', 'mobooking'); ?>',
        booking_updated: '<?php esc_html_e('Booking status updated', 'mobooking'); ?>',
        service_created: '<?php esc_html_e('New service created', 'mobooking'); ?>',
        worker_added: '<?php esc_html_e('New worker added', 'mobooking'); ?>',
        settings_updated: '<?php esc_html_e('Settings updated', 'mobooking'); ?>',
        refresh_success: '<?php esc_html_e('Data refreshed successfully', 'mobooking'); ?>',
        refresh_error: '<?php esc_html_e('Failed to refresh data', 'mobooking'); ?>',
        export_success: '<?php esc_html_e('Data exported successfully', 'mobooking'); ?>',
        export_error: '<?php esc_html_e('Failed to export data', 'mobooking'); ?>',
        network_error: '<?php esc_html_e('Network connection error', 'mobooking'); ?>',
        unauthorized: '<?php esc_html_e('You are not authorized to perform this action', 'mobooking'); ?>'
    }
};
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if feather is available, if not, load it dynamically
    function initializeFeather() {
        if (typeof feather === 'undefined') {
            console.log('Feather icons not loaded, loading dynamically...');
            
            // Create script element for Feather
            const featherScript = document.createElement('script');
            featherScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js';
            featherScript.onload = function() {
                console.log('Feather icons loaded successfully');
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            };
            featherScript.onerror = function() {
                console.error('Failed to load Feather icons');
                // Use fallback icons
                useFallbackIcons();
            };
            document.head.appendChild(featherScript);
        } else {
            // Feather is already loaded
            feather.replace();
        }
    }
    
    // Fallback icon function
    function useFallbackIcons() {
        const fallbackIcons = {
            'dollar-sign': '💰',
            'calendar': '📅',
            'bar-chart-2': '📊',
            'check-circle': '✅',
            'trending-up': '📈',
            'activity': '📊',
            'refresh-cw': '🔄',
            'check': '✓',
            'circle': '○'
        };
        
        document.querySelectorAll('[data-feather]').forEach(function(el) {
            const iconName = el.getAttribute('data-feather');
            if (fallbackIcons[iconName]) {
                el.innerHTML = fallbackIcons[iconName];
                el.style.fontSize = '1.2em';
            }
        });
    }
    
    // Initialize icons
    initializeFeather();
    
    // Initialize basic dashboard functionality
    initializeDashboard();
});

// Basic dashboard initialization
function initializeDashboard() {
    // Period selector functionality
    document.querySelectorAll('.period-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    
    // Refresh buttons
    document.querySelectorAll('.refresh-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            btn.classList.add('loading');
            setTimeout(function() {
                btn.classList.remove('loading');
                if (window.showAlert) {
                    window.showAlert('Data refreshed successfully', 'success');
                }
            }, 1000);
        });
    });
    
    // Add hover effects to cards
    document.querySelectorAll('.card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Setup progress click handlers
    document.querySelectorAll('.setup-progress-item:not(.completed)').forEach(function(item) {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
            const stepText = this.querySelector('span').textContent;
            alert('Navigate to ' + stepText + ' section to complete this step.');
        });
    });
    
    // Initialize Chart.js if available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    } else {
        // Load Chart.js dynamically
        const chartScript = document.createElement('script');
        chartScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
        chartScript.onload = function() {
            initializeCharts();
        };
        document.head.appendChild(chartScript);
    }
}

// Initialize charts
function initializeCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenue-chart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue',
                    data: [120, 190, 300, 500, 200, 300, 450],
                    borderColor: 'hsl(221.2 83.2% 53.3%)',
                    backgroundColor: 'hsl(221.2 83.2% 53.3% / 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'hsl(214.3 31.8% 91.4%)' },
                        ticks: {
                            callback: function(value) {
                                return mobooking_overview_params.currency_symbol + value;
                            }
                        }
                    },
                    x: {
                        grid: { color: 'hsl(214.3 31.8% 91.4%)' }
                    }
                }
            }
        });
    }
    
    // Performance Chart
    const performanceCtx = document.getElementById('performance-chart');
    if (performanceCtx) {
        const stats = mobooking_overview_params.stats;
        const totalBookings = stats.total_bookings || 1;
        const completedBookings = Math.round(totalBookings * (stats.completion_rate / 100));
        const pendingBookings = Math.round(totalBookings * 0.3);
        const cancelledBookings = totalBookings - completedBookings - pendingBookings;
        
        new Chart(performanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Cancelled'],
                datasets: [{
                    data: [completedBookings, pendingBookings, Math.max(0, cancelledBookings)],
                    backgroundColor: [
                        'hsl(142.1 76.2% 36.3%)',
                        'hsl(45.4 93.4% 47.5%)',
                        'hsl(0 84.2% 60.2%)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}

// Auto-refresh functionality
setInterval(function() {
    // Update today's revenue every 2 minutes
    if (mobooking_overview_params.ajax_url) {
        fetch(mobooking_overview_params.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'mobooking_get_today_revenue',
                nonce: mobooking_overview_params.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const todayRevenueEl = document.getElementById('today-revenue-value');
                if (todayRevenueEl) {
                    const newValue = mobooking_overview_params.currency_symbol + parseFloat(data.data.today_revenue).toFixed(2);
                    if (todayRevenueEl.textContent !== newValue) {
                        todayRevenueEl.style.transform = 'scale(1.1)';
                        todayRevenueEl.textContent = newValue;
                        setTimeout(() => {
                            todayRevenueEl.style.transform = 'scale(1)';
                        }, 200);
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating today revenue:', error);
        });
    }
}, 2 * 60 * 1000); // Every 2 minutes
</script>