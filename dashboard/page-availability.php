<?php
/**
 * MoBooking Dashboard Page: Availability Management
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Capability check for managing availability
if ( ! current_user_can( \MoBooking\Classes\Auth::CAP_MANAGE_AVAILABILITY ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}
?>
<div class="wrap mobooking-wrap mobooking-availability-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('availability'); ?>
            </span>
            <h1><?php esc_html_e('Manage Availability', 'mobooking'); ?></h1>
        </div>
    </div>
    <p><?php esc_html_e('Define your regular working hours and set specific dates for overrides or days off.', 'mobooking'); ?></p>

    <div id="mobooking-floating-alert" class="mobooking-floating-alert" style="display:none;"></div>

    <div id="mobooking-availability-feedback" class="notice" style="display:none;"></div>

    <!-- Recurring Weekly Availability Section -->
    <div class="mobooking-section">
        <h2><?php esc_html_e('Recurring Weekly Schedule', 'mobooking'); ?></h2>
        <p><?php esc_html_e('Set your standard availability for each day of the week. These are your default hours unless overridden by a specific date setting below.', 'mobooking'); ?></p>

        <div id="recurring-schedule-container">
            <!-- JS will populate this with the schedule editor -->
            <p><?php esc_html_e('Loading schedule editor...', 'mobooking'); ?></p>
        </div>

        <button type="button" id="mobooking-save-recurring-schedule-btn" class="btn btn-primary" style="margin-top: 20px;">
            <?php esc_html_e('Save Weekly Schedule', 'mobooking'); ?>
        </button>
    </div>


    <!-- Modals are now handled via the MoBookingDialog class in assets/js/dialog.js -->
</div>
