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
    <h1><?php esc_html_e('Manage Availability', 'mobooking'); ?></h1>
    <p><?php esc_html_e('Define your regular working hours and set specific dates for overrides or days off.', 'mobooking'); ?></p>

    <div id="mobooking-availability-feedback" class="notice" style="display:none;"></div>

    <!-- Recurring Weekly Availability Section -->
    <div class="mobooking-section">
        <h2><?php esc_html_e('Recurring Weekly Schedule', 'mobooking'); ?></h2>
        <p><?php esc_html_e('Set your standard availability for each day of the week. These are your default hours unless overridden by a specific date setting below.', 'mobooking'); ?></p>

        <div id="recurring-slots-container">
            <!-- JS will populate this -->
            <p><?php esc_html_e('Loading recurring schedule...', 'mobooking'); ?></p>
        </div>

        <button type="button" id="mobooking-add-recurring-slot-btn" class="button button-primary">
            <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span> <?php esc_html_e('Add Recurring Slot', 'mobooking'); ?>
        </button>
    </div>

    <!-- Specific Date Overrides Section -->
    <div class="mobooking-section">
        <h2><?php esc_html_e('Specific Date Overrides & Days Off', 'mobooking'); ?></h2>
        <p><?php esc_html_e('Use the calendar to select a date and then define custom availability for that day or mark it as unavailable. Overrides take precedence over the recurring weekly schedule.', 'mobooking'); ?></p>

        <div class="mobooking-date-overrides-flex-container">
            <div class="mobooking-calendar-container">
                <div id="mobooking-availability-datepicker"></div>
            </div>
            <div class="mobooking-override-form-container">
                <h3 id="override-form-title"><?php esc_html_e('Select a date to manage overrides', 'mobooking'); ?></h3>
                <div id="mobooking-override-details" style="display:none;">
                    <form id="mobooking-date-override-form">
                        <input type="hidden" id="override-date-input" name="override_date">
                        <input type="hidden" id="override-id-input" name="override_id">

                        <p class="override-selected-date-display"></p>

                        <div class="form-field">
                            <label for="override-is-unavailable">
                                <input type="checkbox" id="override-is-unavailable" name="is_unavailable">
                                <?php esc_html_e('Mark as completely unavailable (Day Off)', 'mobooking'); ?>
                            </label>
                        </div>

                        <div id="override-time-slots-section">
                             <p><?php esc_html_e('If not marked as unavailable, define the available time slot for this day:', 'mobooking'); ?></p>
                            <div class="form-field">
                                <label for="override-start-time"><?php esc_html_e('Start Time:', 'mobooking'); ?></label>
                                <input type="time" id="override-start-time" name="start_time" class="mobooking-input">
                            </div>
                            <div class="form-field">
                                <label for="override-end-time"><?php esc_html_e('End Time:', 'mobooking'); ?></label>
                                <input type="time" id="override-end-time" name="end_time" class="mobooking-input">
                            </div>
                            <div class="form-field">
                                <label for="override-capacity"><?php esc_html_e('Capacity:', 'mobooking'); ?></label>
                                <input type="number" id="override-capacity" name="capacity" min="1" value="1" class="mobooking-input small-text">
                                <p class="description"><?php esc_html_e('Number of concurrent bookings allowed for this slot.', 'mobooking'); ?></p>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="override-notes"><?php esc_html_e('Notes (Optional):', 'mobooking'); ?></label>
                            <textarea id="override-notes" name="notes" class="mobooking-textarea"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Save Override', 'mobooking'); ?></button>
                            <button type="button" id="mobooking-delete-override-btn" class="button mobooking-button-delete" style="display:none;"><?php esc_html_e('Delete Override for this Date', 'mobooking'); ?></button>
                            <button type="button" id="mobooking-clear-override-form-btn" class="button"><?php esc_html_e('Clear / New Date', 'mobooking'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Adding/Editing Recurring Slot -->
    <div id="mobooking-recurring-slot-modal" class="mobooking-modal">
        <div class="mobooking-modal-content">
            <h3 id="recurring-slot-modal-title"><?php esc_html_e('Add Recurring Slot', 'mobooking'); ?></h3>
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
                        <input type="checkbox" id="recurring-is-active" name="is_active" checked>
                        <?php esc_html_e('Active (slot is available for booking)', 'mobooking'); ?>
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

</div>
