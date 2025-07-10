<?php
/**
 * Dashboard Page: Overview
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Chart.js and other scripts are now enqueued via mobooking_enqueue_dashboard_scripts in functions.php

// Get current user and initialize managers
$user = wp_get_current_user();
$current_user_id = get_current_user_id();

// Initialize managers (assuming they exist in your structure)
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

<?php // Inline styles removed. They will be merged into assets/css/dashboard-bookings-responsive.css (or a new common dashboard CSS) ?>

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

    <!-- KPI Cards -->
    <div class="dashboard-kpi-grid">
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Bookings This Month', 'mobooking'); ?></span>
                <div class="kpi-icon bookings">📅</div>
            </div>
            <div class="kpi-value" id="kpi-bookings-month">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend positive" id="bookings-trend">
                <span>↗</span> +12% from last month
            </div>
        </div>

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

        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Active Services', 'mobooking'); ?></span>
                <div class="kpi-icon services">🧹</div>
            </div>
            <div class="kpi-value" id="kpi-services-count">
                <span class="loading"></span>
            </div>
            <div class="kpi-trend neutral" id="services-trend">
                <span>→</span> Ready to book
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Calendar Section (Placeholder for New Calendar) -->
        <div class="calendar-container">
            <!-- New calendar will be initialized here -->
            <div id="mobooking-bookings-calendar"></div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-container">
            <div class="activity-header">
                <h3 class="activity-title"><?php esc_html_e('Recent Bookings', 'mobooking'); ?></h3>
                <a href="<?php echo esc_url($dashboard_base_url . 'bookings/'); ?>" class="view-all-btn">
                    <?php esc_html_e('View all', 'mobooking'); ?>
                </a>
            </div>
            <div class="activity-list" id="recent-bookings-list">
                <div class="loading" style="margin: 2rem auto; display: block;"></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-grid">
        <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS)) : ?>
        <a href="#" class="quick-action-card" id="add-booking-action">
            <div class="quick-action-icon">➕</div>
            <h4 class="quick-action-title"><?php esc_html_e('Add New Booking', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Create a booking for a customer', 'mobooking'); ?></p>
        </a>
        <?php endif; ?>

        <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_SERVICES)) : ?>
        <a href="<?php echo esc_url($dashboard_base_url . 'services/'); ?>" class="quick-action-card">
            <div class="quick-action-icon">🧹</div>
            <h4 class="quick-action-title"><?php esc_html_e('Manage Services', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Add or edit your cleaning services', 'mobooking'); ?></p>
        </a>
        <?php endif; ?>

        <?php if (current_user_can(\MoBooking\Classes\Auth::CAP_MANAGE_DISCOUNTS)) : ?>
        <a href="<?php echo esc_url($dashboard_base_url . 'discounts/'); ?>" class="quick-action-card">
            <div class="quick-action-icon">🎯</div>
            <h4 class="quick-action-title"><?php esc_html_e('Create Discount', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Set up promotional offers', 'mobooking'); ?></p>
        </a>
        <?php endif; ?>

        <a href="<?php echo esc_url($dashboard_base_url . 'booking-form/'); ?>" class="quick-action-card">
            <div class="quick-action-icon">🔗</div>
            <h4 class="quick-action-title"><?php esc_html_e('Share Booking Form', 'mobooking'); ?></h4>
            <p class="quick-action-desc"><?php esc_html_e('Get your booking form link', 'mobooking'); ?></p>
        </a>
    </div>
</div>

<?php
// Ensure dashboard_nonce is available for JS
// It's better to localize this with other params for the main JS file.
// However, if other small inline scripts might use it, we can define it here.
// For now, we assume the main JS file will handle its nonce via wp_localize_script.
?>

<script type="text/javascript">
// Make ajaxurl available globally for WordPress AJAX
// This is a common practice, but it's better to pass it via wp_localize_script if possible.
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// The main dashboard logic is now in assets/js/dashboard-overview.js
// We might still have some very specific, small scripts here if necessary,
// or scripts related to other plugins/themes that expect `ajaxurl` globally.

// Example: Refresh data every 5 minutes - this was in the original inline script.
// We need to ensure `loadKPIData` is defined in the global scope or refactor this.
// For now, I'll assume `loadKPIData` will be part of the global functions in dashboard-overview.js or this will be moved.
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional initialization code here specific to this page if not covered by the main JS.
    
    // If loadKPIData is meant to be globally accessible from dashboard-overview.js, this might work.
    // Otherwise, this interval should be set within the scope where loadKPIData is defined.
    // For now, commenting out to avoid errors until dashboard-overview.js is updated.
    /*
    setInterval(function() {
        if (typeof jQuery !== 'undefined' && typeof loadKPIData === 'function') {
            jQuery('#kpi-bookings-month, #kpi-revenue-month, #kpi-upcoming-count, #kpi-services-count').each(function() {
                if (!jQuery(this).find('.loading').length) {
                    // Only refresh if not currently loading
                    loadKPIData();
                }
            });
        }
    }, 300000); // 5 minutes
    */
});
</script>

<?php
// AJAX Handlers have been moved to functions.php
?>