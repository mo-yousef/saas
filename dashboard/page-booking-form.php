<?php
/**
 * Dashboard Page: Booking Form Settings - FIXED VERSION
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \MoBooking\Classes\Settings();
$user_id = get_current_user_id();
$bf_settings = $settings_manager->get_booking_form_settings($user_id);
$biz_settings = $settings_manager->get_business_settings($user_id); // For biz_name

// Helper to get value, escape it, and provide default
function mobooking_get_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function mobooking_get_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function mobooking_is_setting_checked($settings, $key, $default_is_checked = false) {
    $val = isset($settings[$key]) ? $settings[$key] : ($default_is_checked ? '1' : '0');
    return checked('1', $val, false);
}

// Get current public booking form URL
$current_slug = mobooking_get_setting_value($bf_settings, 'bf_business_slug', '');
if (empty($current_slug) && !empty($biz_settings['biz_name'])) {
    $current_slug = sanitize_title($biz_settings['biz_name']);
}

$public_booking_url = '';
if (!empty($current_slug)) {
    // Use the new standardized URL structure
    $public_booking_url = trailingslashit(site_url()) . 'booking/' . esc_attr($current_slug) . '/';
}

?>
<div id="mobooking-booking-form-settings-page" class="wrap mobooking-settings-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('booking_form'); ?>
            </span>
            <div class="heading-wrapper">
                <h1><?php esc_html_e('Booking Form Settings', 'mobooking'); ?></h1>
                <p class="dashboard-subtitle"><?php esc_html_e('Customize the appearance and behavior of your public booking form.', 'mobooking'); ?></p>
            </div>
        </div>
        <div class="mobooking-page-header-actions">
            <button type="submit" form="mobooking-booking-form-settings-form" name="save_booking_form_settings" id="mobooking-save-bf-settings-btn" class="btn btn-primary"><?php esc_html_e('Save Changes', 'mobooking'); ?></button>
        </div>
    </div>

    <?php if (!empty($public_booking_url)): ?>
    <div class="mobooking-public-link-display">
        <span class="link-label"><?php esc_html_e('Your Booking Form is live at:', 'mobooking'); ?></span>
        <a href="<?php echo esc_url($public_booking_url); ?>" target="_blank" id="bf-public-link"><?php echo esc_url($public_booking_url); ?></a>
        <button class="btn btn-sm btn-icon" id="mobooking-copy-public-link-btn" title="<?php esc_attr_e('Copy link', 'mobooking'); ?>" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 16V18.8C8 19.9201 8 20.4802 8.21799 20.908C8.40973 21.2843 8.71569 21.5903 9.09202 21.782C9.51984 22 10.0799 22 11.2 22H18.8C19.9201 22 20.4802 22 20.908 21.782C21.2843 21.5903 21.5903 21.2843 21.782 20.908C22 20.4802 22 19.9201 22 18.8V11.2C22 10.0799 22 9.51984 21.782 9.09202C21.5903 8.71569 21.2843 8.40973 20.908 8.21799C20.4802 8 19.9201 8 18.8 8H16M5.2 16H12.8C13.9201 16 14.4802 16 14.908 15.782C15.2843 15.5903 15.5903 15.2843 15.782 14.908C16 14.4802 16 13.9201 16 12.8V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H5.2C4.0799 2 3.51984 2 3.09202 2.21799C2.71569 2.40973 2.40973 2.71569 2.21799 3.09202C2 3.51984 2 4.07989 2 5.2V12.8C2 13.9201 2 14.4802 2.21799 14.908C2.40973 15.2843 2.71569 15.5903 3.09202 15.782C3.51984 16 4.07989 16 5.2 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <?php if (!empty($public_booking_url)): ?>
            <button type="button" id="download-qr-btn" class="btn btn-secondary btn-sm"><?php esc_html_e('Download QR Code', 'mobooking'); ?></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <form id="mobooking-booking-form-settings-form" method="post" class="mobooking-settings-form">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>

        <!-- Tab Navigation -->
        <div class="mobooking-settings-tabs" role="tablist" aria-label="<?php esc_attr_e('Booking Form Settings', 'mobooking'); ?>">
            <a href="#general" class="mobooking-tab-item active" data-tab="general" role="tab"><?php esc_html_e('General', 'mobooking'); ?></a>
            <a href="#form-control" class="mobooking-tab-item" data-tab="form-control" role="tab"><?php esc_html_e('Form Control', 'mobooking'); ?></a>
            <a href="#design" class="mobooking-tab-item" data-tab="design" role="tab"><?php esc_html_e('Design', 'mobooking'); ?></a>
            <a href="#advanced" class="mobooking-tab-item" data-tab="advanced" role="tab"><?php esc_html_e('Advanced', 'mobooking'); ?></a>
            <a href="#share-embed" class="mobooking-tab-item" data-tab="share-embed" role="tab"><?php esc_html_e('Share & Embed', 'mobooking'); ?></a>
        </div>

        <div class="mobooking-settings-content">
            <!-- General Settings Tab -->
            <div id="general" class="mobooking-settings-tab-pane active" role="tabpanel">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Basic Information', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="bf_business_slug"><?php esc_html_e('Business Slug', 'mobooking'); ?></label>
                            <input name="bf_business_slug" type="text" id="bf_business_slug" value="<?php echo esc_attr($current_slug); ?>" class="form-input" pattern="[a-z0-9-]+" title="<?php esc_attr_e('Only lowercase letters, numbers, and hyphens allowed', 'mobooking'); ?>">
                            <p class="description">
                                <?php esc_html_e('Unique slug for your public booking page URL (e.g., your-business-name).', 'mobooking'); ?>
                                <br>
                                <?php esc_html_e('Changing this will change your public booking form URL. Only use lowercase letters, numbers, and hyphens.', 'mobooking'); ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="bf_header_text"><?php esc_html_e('Header Text', 'mobooking'); ?></label>
                            <input name="bf_header_text" type="text" id="bf_header_text" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>" class="form-input">
                            <p class="description"><?php esc_html_e('The main heading displayed at the top of your booking form.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="bf_description"><?php esc_html_e('Description', 'mobooking'); ?></label>
                            <textarea name="bf_description" id="bf_description" class="form-textarea" rows="3"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_description'); ?></textarea>
                            <p class="description"><?php esc_html_e('Optional description text shown below the header.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_show_progress_bar" type="checkbox" id="bf_show_progress_bar" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_progress_bar', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_show_progress_bar" class="toggle-label"><?php esc_html_e('Show Progress Bar', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Display a progress indicator showing the current step in the booking process.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bf_success_message"><?php esc_html_e('Success Message', 'mobooking'); ?></label>
                            <textarea name="bf_success_message" id="bf_success_message" class="form-textarea" rows="3"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details.'); ?></textarea>
                            <p class="description"><?php esc_html_e('Message shown to customers after successful form submission.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Control Tab -->
            <div id="form-control" class="mobooking-settings-tab-pane" role="tabpanel">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Form Availability', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_form_enabled" type="checkbox" id="bf_form_enabled" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_form_enabled', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_form_enabled" class="toggle-label"><?php esc_html_e('Enable Booking Form', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('When disabled, the form will show a maintenance message instead of allowing bookings.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group" id="maintenance-message-group" style="<?php echo mobooking_is_setting_checked($bf_settings, 'bf_form_enabled', true) ? 'display:none;' : ''; ?>">
                            <label for="bf_maintenance_message"><?php esc_html_e('Maintenance Message', 'mobooking'); ?></label>
                            <textarea name="bf_maintenance_message" id="bf_maintenance_message" class="form-textarea" rows="3"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_maintenance_message', 'We are temporarily not accepting new bookings. Please check back later or contact us directly.'); ?></textarea>
                            <p class="description"><?php esc_html_e('This message will be displayed when the booking form is disabled.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Form Fields', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_enable_location_check" type="checkbox" id="bf_enable_location_check" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_location_check', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_enable_location_check" class="toggle-label"><?php esc_html_e('Enable Location Check', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Allow customers to check if you service their area.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_enable_pet_information" type="checkbox" id="bf_enable_pet_information" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_pet_information', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_enable_pet_information" class="toggle-label"><?php esc_html_e('Enable Pet Information', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Show fields for pet-related service information.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_enable_service_frequency" type="checkbox" id="bf_enable_service_frequency" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_service_frequency', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_enable_service_frequency" class="toggle-label"><?php esc_html_e('Enable Service Frequency', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Allow customers to select recurring service options.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_enable_datetime_selection" type="checkbox" id="bf_enable_datetime_selection" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_datetime_selection', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_enable_datetime_selection" class="toggle-label"><?php esc_html_e('Enable Date & Time Selection', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Show calendar and time slot picker.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_enable_property_access" type="checkbox" id="bf_enable_property_access" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_property_access', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_enable_property_access" class="toggle-label"><?php esc_html_e('Enable Property Access Information', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Collect information about property access and special instructions.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Design Settings Tab -->
            <div id="design" class="mobooking-settings-tab-pane" role="tabpanel">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Theme Colors', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_theme_color"><?php esc_html_e('Primary Color', 'mobooking'); ?></label>
                                <input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="form-input mobooking-color-picker">
                                <p class="description"><?php esc_html_e('Main color used for buttons and accents.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'mobooking'); ?></label>
                                <input name="bf_background_color" type="text" id="bf_background_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="form-input mobooking-color-picker">
                                <p class="description"><?php esc_html_e('Background color of the form.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_text_color"><?php esc_html_e('Text Color', 'mobooking'); ?></label>
                                <input name="bf_text_color" type="text" id="bf_text_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_text_color', '#333333'); ?>" class="form-input mobooking-color-picker">
                                <p class="description"><?php esc_html_e('Primary text color.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_border_radius"><?php esc_html_e('Border Radius (px)', 'mobooking'); ?></label>
                                <input name="bf_border_radius" type="number" id="bf_border_radius" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_border_radius', '8'); ?>" min="0" max="50" class="form-input">
                                <p class="description"><?php esc_html_e('Roundness of form elements.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Custom CSS', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="bf_custom_css"><?php esc_html_e('Custom CSS Rules', 'mobooking'); ?></label>
                            <textarea name="bf_custom_css" id="bf_custom_css" class="form-textarea code" rows="8" placeholder="<?php esc_attr_e('/* Your custom CSS rules here */', 'mobooking'); ?>"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_custom_css'); ?></textarea>
                            <p class="description"><strong><?php esc_html_e('Tip:', 'mobooking'); ?></strong> <?php esc_html_e('Use <code>.mobooking-form</code> as the main selector to target form elements.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings Tab -->
            <div id="advanced" class="mobooking-settings-tab-pane" role="tabpanel">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Booking Logic', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_allow_cancellation_hours"><?php esc_html_e('Cancellation Lead Time (Hours)', 'mobooking'); ?></label>
                                <input name="bf_allow_cancellation_hours" type="number" id="bf_allow_cancellation_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_allow_cancellation_hours', '24'); ?>" min="0" class="form-input">
                                <p class="description"><?php esc_html_e('Minimum hours before booking a customer can cancel.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_booking_advance_days"><?php esc_html_e('Advance Booking Days', 'mobooking'); ?></label>
                                <input name="bf_booking_advance_days" type="number" id="bf_booking_advance_days" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_booking_advance_days', '30'); ?>" min="1" max="365" class="form-input">
                                <p class="description"><?php esc_html_e('How many days in advance customers can book.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_min_booking_notice"><?php esc_html_e('Minimum Booking Notice (Hours)', 'mobooking'); ?></label>
                                <input name="bf_min_booking_notice" type="number" id="bf_min_booking_notice" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_min_booking_notice', '24'); ?>" min="0" class="form-input">
                                <p class="description"><?php esc_html_e('Minimum hours notice required for new bookings.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_time_slot_duration"><?php esc_html_e('Time Slot Duration (Minutes)', 'mobooking'); ?></label>
                                <select name="bf_time_slot_duration" id="bf_time_slot_duration" class="form-select">
                                    <option value="15" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '15'); ?>>15 <?php esc_html_e('minutes', 'mobooking'); ?></option>
                                    <option value="30" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '30'); ?>>30 <?php esc_html_e('minutes', 'mobooking'); ?></option>
                                    <option value="60" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '60'); ?>>1 <?php esc_html_e('hour', 'mobooking'); ?></option>
                                    <option value="120" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '120'); ?>>2 <?php esc_html_e('hours', 'mobooking'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Duration of each available time slot.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Notification Settings', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="bf_admin_email"><?php esc_html_e('Admin Notification Email', 'mobooking'); ?></label>
                            <input name="bf_admin_email" type="email" id="bf_admin_email" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_admin_email', get_option('admin_email')); ?>" class="form-input">
                            <p class="description"><?php esc_html_e('Email address to receive new booking notifications.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_send_customer_confirmation" type="checkbox" id="bf_send_customer_confirmation" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_send_customer_confirmation', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_send_customer_confirmation" class="toggle-label"><?php esc_html_e('Send Customer Confirmation Emails', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Automatically send confirmation emails to customers after booking.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label class="mobooking-toggle-switch">
                                <input name="bf_send_admin_notification" type="checkbox" id="bf_send_admin_notification" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_send_admin_notification', true); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label-group">
                                <label for="bf_send_admin_notification" class="toggle-label"><?php esc_html_e('Send Admin Notification Emails', 'mobooking'); ?></label>
                                <p class="description"><?php esc_html_e('Receive email notifications when new bookings are made.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Share & Embed Tab -->
            <div id="share-embed" class="mobooking-settings-tab-pane" role="tabpanel">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Public Booking Link', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="mobooking-public-link"><?php esc_html_e('Public Link', 'mobooking'); ?></label>
                            <div class="input-group">
                                <input type="url" id="mobooking-public-link" value="<?php echo esc_url($public_booking_url); ?>" class="form-input" readonly>
                                <button type="button" class="btn btn-secondary" id="mobooking-copy-public-link-btn"><?php esc_html_e('Copy', 'mobooking'); ?></button>
                            </div>
                            <p class="description"><?php esc_html_e('Share this link with customers so they can book your services directly.', 'mobooking'); ?></p>
                        </div>
                        <?php if (!empty($public_booking_url)): ?>
                        <div class="form-group">
                            <label><?php esc_html_e('QR Code', 'mobooking'); ?></label>
                            <div class="qr-code-container">
                                <img id="qr-code-image" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($public_booking_url); ?>" alt="<?php esc_attr_e('Booking Form QR Code', 'mobooking'); ?>" style="max-width: 200px; height: auto;">
                                <br>
                                <button type="button" id="download-qr-btn-embed" class="btn btn-secondary btn-sm" style="margin-top: 10px;"><?php esc_html_e('Download QR Code', 'mobooking'); ?></button>
                            </div>
                            <p class="description"><?php esc_html_e('Print this QR code on business cards, flyers, or display it at your location.', 'mobooking'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Embed Code', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="mobooking-embed-code"><?php esc_html_e('Embed Code', 'mobooking'); ?></label>
                            <textarea id="mobooking-embed-code" class="form-textarea code" rows="4" readonly><?php if (!empty($public_booking_url)): ?><iframe src="<?php echo esc_url($public_booking_url); ?>" width="100%" height="600" frameborder="0" scrolling="auto"></iframe><?php endif; ?></textarea>
                            <div class="button-group" style="margin-top: 0.75rem;">
                                <button type="button" class="btn btn-secondary" id="mobooking-copy-embed-code-btn"><?php esc_html_e('Copy Embed Code', 'mobooking'); ?></button>
                            </div>
                            <p class="description"><?php esc_html_e('Use this code to embed the booking form directly into your website or blog.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_embed_width"><?php esc_html_e('Embed Width', 'mobooking'); ?></label>
                                <input name="bf_embed_width" type="text" id="bf_embed_width" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_embed_width', '100%'); ?>" class="form-input">
                                <p class="description"><?php esc_html_e('Width of embedded form (e.g., 100%, 600px).', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_embed_height"><?php esc_html_e('Embed Height', 'mobooking'); ?></label>
                                <input name="bf_embed_height" type="text" id="bf_embed_height" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_embed_height', '600px'); ?>" class="form-input">
                                <p class="description"><?php esc_html_e('Height of embedded form (e.g., 600px, 80vh).', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Success/Error Messages Container -->
<div id="mobooking-settings-feedback" class="notice" style="display:none;"></div>

<style>
/* Additional styles for improved form experience */
.input-group {
    display: flex;
    align-items: stretch;
}

.input-group .form-input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: 0;
}

.input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    white-space: nowrap;
}

.form-group-grid.two-cols {
    grid-template-columns: 1fr 1fr;
}

.qr-code-container {
    text-align: center;
    padding: 1rem;
    background-color: hsl(var(--muted));
    border-radius: var(--radius-sm);
    margin-top: 0.5rem;
}

.button-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.invalid-feedback {
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    vertical-align: -0.125em;
    border: 0.125em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

.spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
    border-width: 0.125em;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Focus management for accessibility */
.mobooking-tab-item:focus {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
    z-index: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-group-grid.two-cols {
        grid-template-columns: 1fr;
    }
    
    .mobooking-settings-tabs {
        flex-wrap: wrap;
        gap: 0.125rem;
    }
    
    .mobooking-tab-item {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
    }
    
    .input-group {
        flex-direction: column;
    }
    
    .input-group .form-input {
        border-radius: var(--radius-sm);
        border-right: 1px solid hsl(var(--input));
        margin-bottom: 0.5rem;
    }
    
    .input-group .btn {
        border-radius: var(--radius-sm);
    }
}
</style>