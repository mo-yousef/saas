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
<div class="mobooking-availability-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('availability'); ?>
            </span>
            <h1><?php esc_html_e('Manage Availability', 'mobooking'); ?></h1>
        </div>

        <button type="button" id="mobooking-save-recurring-schedule-btn" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>    
            <?php esc_html_e('Save Weekly Schedule', 'mobooking'); ?>
        </button>
    </div>
    <p><?php esc_html_e('Define your regular working hours and set specific dates for overrides or days off.', 'mobooking'); ?></p>

    <div id="mobooking-floating-alert" class="mobooking-floating-alert" style="display:none;"></div>

    <div id="mobooking-availability-feedback" class="notice" style="display:none;"></div>

    <!-- Recurring Weekly Availability Section -->

        <div class="mobooking-card-availability-content">
            <div id="recurring-schedule-container">
                <!-- JS will populate this with the schedule editor -->
                <p><?php esc_html_e('Loading schedule editor...', 'mobooking'); ?></p>
            </div>
        </div>



    <!-- Modals are now handled via the MoBookingDialog class in assets/js/dialog.js -->
</div>
