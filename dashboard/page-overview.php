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
$stats = $bookings_manager->get_dashboard_stats($data_user_id);
$recent_bookings = $bookings_manager->get_bookings_by_user($data_user_id, ['posts_per_page' => 5]);
$setup_progress = $settings_manager->get_setup_progress($data_user_id);

// Get currency symbol
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));

// Get dashboard URLs
$dashboard_base_url = home_url('/dashboard/');
?>

<div class="mobooking-overview-refactored">

    <!-- Top Section -->
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Total Revenue</h3>
                <i data-feather="dollar-sign" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold"><?php echo esc_html($currency_symbol . number_format($stats['total_revenue'], 2)); ?></div>
                <p class="text-xs text-muted-foreground">+20.1% from last month</p>
            </div>
        </div>
    </div>
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Total Bookings</h3>
                <i data-feather="book-open" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold"><?php echo esc_html($stats['total_bookings']); ?></div>
                <p class="text-xs text-muted-foreground">+180.1% from last month</p>
            </div>
        </div>
    </div>
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Today's Revenue</h3>
                <i data-feather="bar-chart-2" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold"><?php echo esc_html($currency_symbol . number_format($stats['today_revenue'], 2)); ?></div>
                <p class="text-xs text-muted-foreground">+19% from last month</p>
            </div>
        </div>
    </div>
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Completion Rate</h3>
                <i data-feather="check-circle" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold"><?php echo esc_html(number_format($stats['completion_rate'], 1)); ?>%</div>
                <p class="text-xs text-muted-foreground">+201 since last hour</p>
            </div>
        </div>
    </div>

    <!-- Middle Section -->
    <div class="widget-span-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Bookings</h3>
            </div>
            <div class="card-content">
                <?php if (!empty($recent_bookings->posts)) : ?>
                    <div class="recent-sales-table">
                        <?php foreach ($recent_bookings->posts as $booking) :
                            $customer_name = get_post_meta($booking->ID, '_customer_name', true);
                            $customer_email = get_post_meta($booking->ID, '_customer_email', true);
                            $total_price = get_post_meta($booking->ID, '_total_price', true);
                        ?>
                            <div class="table-row">
                                <div class="table-cell">
                                    <div class="avatar">
                                        <img src="https://i.pravatar.cc/40?u=<?php echo esc_attr($customer_email); ?>" alt="Avatar">
                                    </div>
                                    <div>
                                        <p><?php echo esc_html($customer_name); ?></p>
                                        <p class="text-muted-foreground"><?php echo esc_html($customer_email); ?></p>
                                    </div>
                                </div>
                                <div class="table-cell text-right"><?php echo esc_html($currency_symbol . number_format($total_price, 2)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p>No recent bookings.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="widget-span-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Setup Progress</h3>
            </div>
            <div class="card-content">
                <ul class="setup-progress-list">
                    <?php foreach ($setup_progress['steps'] as $step) : ?>
                        <li class="setup-progress-item <?php echo $step['completed'] ? 'completed' : ''; ?>">
                            <div class="icon">
                                <i data-feather="<?php echo $step['completed'] ? 'check' : 'circle'; ?>"></i>
                            </div>
                            <span><?php echo esc_html($step['label']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
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