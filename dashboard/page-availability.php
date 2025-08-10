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

        <button type="button" id="mobooking-save-recurring-schedule-btn" class="button button-primary" style="margin-top: 20px;">
            <?php esc_html_e('Save Weekly Schedule', 'mobooking'); ?>
        </button>
    </div>


    <!-- Modal for Adding/Editing Recurring Slot -->
    <div id="mobooking-recurring-slot-modal" class="mobooking-modal">
        <div class="mobooking-modal-content">
            <h3 id="recurring-slot-modal-title"><?php esc_html_e('Add Recurring Slot', 'mobooking'); ?></h3>
            <div id="mobooking-recurring-slot-modal-error" class="mobooking-notice notice-error" style="display:none; margin-bottom: 15px;"></div>
            <form id="mobooking-recurring-slot-form">
                <input type="hidden" id="recurring-slot-id" name="slot_id">
                <div class="form-field">
                    <label for="recurring-day-of-week"><?php esc_html_e('Day of Week:', 'mobooking'); ?> <span class="required">*</span></label>
                    <select id="recurring-day-of-week" name="day_of_week" required class="mobooking-select">
                        <option value="1"><?php esc_html_e('Monday', 'mobooking'); ?></option>
                        <option value="2"><?php esc_html_e('Tuesday', 'mobooking'); ?></option>
                        <option value="3"><?php esc_html_e('Wednesday', 'mobooking'); ?></option>
                        <option value="4"><?php esc_html_e('Thursday', 'mobooking'); ?></option>
                        <option value="5"><?php esc_html_e('Friday', 'mobooking'); ?></option>
                        <option value="6"><?php esc_html_e('Saturday', 'mobooking'); ?></option>
                        <option value="0"><?php esc_html_e('Sunday', 'mobooking'); ?></option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="recurring-start-time"><?php esc_html_e('Start Time:', 'mobooking'); ?> <span class="required">*</span></label>
                    <input type="time" id="recurring-start-time" name="start_time" required class="mobooking-input">
                </div>
                <div class="form-field">
                    <label for="recurring-end-time"><?php esc_html_e('End Time:', 'mobooking'); ?> <span class="required">*</span></label>
                    <input type="time" id="recurring-end-time" name="end_time" required class="mobooking-input">
                </div>
                <div class="form-field">
                    <label for="recurring-capacity"><?php esc_html_e('Capacity:', 'mobooking'); ?> <span class="required">*</span></label>
                    <input type="number" id="recurring-capacity" name="capacity" min="1" value="1" required class="mobooking-input small-text">
                     <p class="description"><?php esc_html_e('Number of concurrent bookings for this slot.', 'mobooking'); ?></p>
                </div>
                 <div class="form-field">
                    <label for="recurring-is-active">
                        <label class="mobooking-toggle-switch">
                            <input type="checkbox" id="recurring-is-active" name="is_active" checked>
                            <span class="slider"></span>
                        </label>
                        <label for="recurring-is-active" class="toggle-label"><?php esc_html_e('Active', 'mobooking'); ?></label>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Slot', 'mobooking'); ?></button>
                    <button type="button" class="button mobooking-modal-close"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <div id="mobooking-recurring-slot-modal-backdrop" class="mobooking-modal-backdrop"></div>

    <!-- Generic Confirmation/Alert Modal -->
    <div id="mobooking-generic-modal" class="mobooking-modal">
        <div class="mobooking-modal-content">
            <h3 id="mobooking-generic-modal-title"></h3>
            <p id="mobooking-generic-modal-message"></p>
            <div id="mobooking-generic-modal-actions" class="form-actions">
                <button type="button" id="mobooking-generic-modal-confirm-btn" class="button button-primary"></button>
                <button type="button" id="mobooking-generic-modal-cancel-btn" class="button mobooking-modal-close"></button>
            </div>
        </div>
    </div>
    <div id="mobooking-generic-modal-backdrop" class="mobooking-modal-backdrop"></div>

</div>
