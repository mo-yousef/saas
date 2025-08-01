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

<div class="mobooking-overview-refactored">

    <!-- Top Section -->
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Total Revenue</h3>
                <i data-feather="dollar-sign" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold">$45,231.89</div>
                <p class="text-xs text-muted-foreground">+20.1% from last month</p>
            </div>
        </div>
    </div>
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Subscriptions</h3>
                <i data-feather="users" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold">+2350</div>
                <p class="text-xs text-muted-foreground">+180.1% from last month</p>
            </div>
        </div>
    </div>
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Sales</h3>
                <i data-feather="credit-card" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold">+12,234</div>
                <p class="text-xs text-muted-foreground">+19% from last month</p>
            </div>
        </div>
    </div>
    <div class="widget-span-3">
        <div class="card dashboard-kpi-card">
            <div class="card-header">
                <h3 class="card-title">Active Now</h3>
                <i data-feather="activity" class="text-muted-foreground"></i>
            </div>
            <div class="card-content">
                <div class="text-2xl font-bold">+573</div>
                <p class="text-xs text-muted-foreground">+201 since last hour</p>
            </div>
        </div>
    </div>

    <!-- Middle Section -->
    <div class="widget-span-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overview</h3>
            </div>
            <div class="card-content">
                <div class="chart-placeholder" style="height: 200px; background: #f0f0f0;"></div>
            </div>
        </div>
    </div>
    <div class="widget-span-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overview</h3>
            </div>
            <div class="card-content">
                <div class="chart-placeholder" style="height: 200px; background: #f0f0f0;"></div>
            </div>
        </div>
    </div>
    <div class="widget-span-4">
        <div class="card">
            <div class="card-header">
                <div class="tabs">
                    <button class="tab-item active">Overview</button>
                    <button class="tab-item">Analytics</button>
                    <button class="tab-item">Reports</button>
                    <button class="tab-item">Notifications</button>
                </div>
            </div>
            <div class="card-content">
                <div class="sub-card">
                    <p>Total Subscriptions</p>
                    <p>2,350</p>
                </div>
                <div class="sub-card">
                    <p>Total Revenue</p>
                    <p>$45,231.89</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="widget-span-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overview</h3>
            </div>
            <div class="card-content">
                <div class="chart-placeholder" style="height: 300px; background: #f0f0f0;"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Sales</h3>
                <p class="text-muted-foreground">You made 265 sales this month.</p>
            </div>
            <div class="card-content">
                <div class="recent-sales-table">
                    <div class="table-row">
                        <div class="table-cell">
                            <div class="avatar">
                                <img src="https://i.pravatar.cc/40?u=a" alt="Avatar">
                            </div>
                            <div>
                                <p>Olivia Martin</p>
                                <p class="text-muted-foreground">olivia.martin@email.com</p>
                            </div>
                        </div>
                        <div class="table-cell text-right">+$1,999.00</div>
                    </div>
                    <div class="table-row">
                        <div class="table-cell">
                            <div class="avatar">
                                <img src="https://i.pravatar.cc/40?u=b" alt="Avatar">
                            </div>
                            <div>
                                <p>Jackson Lee</p>
                                <p class="text-muted-foreground">jackson.lee@email.com</p>
                            </div>
                        </div>
                        <div class="table-cell text-right">+$39.00</div>
                    </div>
                    <!-- Add more rows as needed -->
                </div>
            </div>
        </div>
    </div>
    <div class="widget-span-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overview</h3>
            </div>
            <div class="card-content">
                <div class="chart-placeholder" style="height: 200px; background: #f0f0f0;"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activities</h3>
            </div>
            <div class="card-content">
                <div class="recent-activities-list">
                    <div class="activity-item">
                        <div class="avatar">
                            <img src="https://i.pravatar.cc/40?u=c" alt="Avatar">
                        </div>
                        <p><strong>Olivia Martin</strong> subscribed to your service.</p>
                    </div>
                    <div class="activity-item">
                        <div class="avatar">
                            <img src="https://i.pravatar.cc/40?u=d" alt="Avatar">
                        </div>
                        <p><strong>Jackson Lee</strong> created a new booking.</p>
                    </div>
                    <!-- Add more activities as needed -->
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