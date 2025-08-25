<?php
/**
 * Dashboard Page: Booking Form Settings
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
        <button class="btn btn-sm btn-icon" id="mobooking-copy-public-link-btn" title="<?php esc_attr_e('Copy link', 'mobooking'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 16V18.8C8 19.9201 8 20.4802 8.21799 20.908C8.40973 21.2843 8.71569 21.5903 9.09202 21.782C9.51984 22 10.0799 22 11.2 22H18.8C19.9201 22 20.4802 22 20.908 21.782C21.2843 21.5903 21.5903 21.2843 21.782 20.908C22 20.4802 22 19.9201 22 18.8V11.2C22 10.0799 22 9.51984 21.782 9.09202C21.5903 8.71569 21.2843 8.40973 20.908 8.21799C20.4802 8 19.9201 8 18.8 8H16M5.2 16H12.8C13.9201 16 14.4802 16 14.908 15.782C15.2843 15.5903 15.5903 15.2843 15.782 14.908C16 14.4802 16 13.9201 16 12.8V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H5.2C4.0799 2 3.51984 2 3.09202 2.21799C2.71569 2.40973 2.40973 2.71569 2.21799 3.09202C2 3.51984 2 4.07989 2 5.2V12.8C2 13.9201 2 14.4802 2.21799 14.908C2.40973 15.2843 2.71569 15.5903 3.09202 15.782C3.51984 16 4.07989 16 5.2 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>

    <form id="mobooking-booking-form-settings-form" method="post" class="mobooking-settings-form">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>

        <div class="mobooking-settings-tabs">
            <a href="#general" class="mobooking-tab-item active" data-tab="general"><?php esc_html_e('General', 'mobooking'); ?></a>
            <a href="#form-control" class="mobooking-tab-item" data-tab="form-control"><?php esc_html_e('Form Control', 'mobooking'); ?></a>
            <a href="#design" class="mobooking-tab-item" data-tab="design"><?php esc_html_e('Design', 'mobooking'); ?></a>
            <a href="#advanced" class="mobooking-tab-item" data-tab="advanced"><?php esc_html_e('Advanced', 'mobooking'); ?></a>
            <a href="#share-embed" class="mobooking-tab-item" data-tab="share-embed"><?php esc_html_e('Share & Embed', 'mobooking'); ?></a>
        </div>

        <div class="mobooking-settings-content">
            <!-- General Settings Tab -->
            <div id="general" class="mobooking-settings-tab-pane active">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Basic Information', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="bf_business_slug"><?php esc_html_e('Business Slug', 'mobooking'); ?></label>
                            <input name="bf_business_slug" type="text" id="bf_business_slug" value="<?php echo esc_attr($current_slug); ?>" class="form-input">
                            <p class="description">
                                <?php esc_html_e('Unique slug for your public booking page URL (e.g., your-business-name).', 'mobooking'); ?>
                                <br>
                                <?php esc_html_e('Changing this will change your public booking form URL. Only use lowercase letters, numbers, and hyphens.', 'mobooking'); ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label for="bf_header_text"><?php esc_html_e('Form Header Text', 'mobooking'); ?></label>
                            <input name="bf_header_text" type="text" id="bf_header_text" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>" class="form-input">
                            <p class="description"><?php esc_html_e('The main title displayed at the top of your public booking form.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="bf_success_message"><?php esc_html_e('Success Message', 'mobooking'); ?></label>
                            <textarea name="bf_success_message" id="bf_success_message" class="form-textarea" rows="4"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details. A confirmation email has been sent to you.'); ?></textarea>
                            <p class="description"><?php esc_html_e('Message displayed to customer after successful booking.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Legal & Compliance', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="bf_terms_conditions_url"><?php esc_html_e('Terms & Conditions URL', 'mobooking'); ?></label>
                            <input name="bf_terms_conditions_url" type="url" id="bf_terms_conditions_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_terms_conditions_url'); ?>" class="form-input" placeholder="https://example.com/terms">
                            <p class="description"><?php esc_html_e('If provided, customers must agree to your terms before booking.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Control Tab -->
            <div id="form-control" class="mobooking-settings-tab-pane">
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
                                <label for="bf_form_enabled" class="toggle-label"><?php esc_html_e( 'Enable Booking Form', 'mobooking' ); ?></label>
                                <p class="description"><?php esc_html_e('When disabled, the form will show a maintenance message instead of allowing bookings.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <div class="form-group" id="maintenance-message-group" style="<?php echo mobooking_is_setting_checked($bf_settings, 'bf_form_enabled', true) ? 'display:none;' : ''; ?>">
                            <label for="bf_maintenance_message"><?php esc_html_e('Maintenance Message', 'mobooking'); ?></label>
                            <textarea name="bf_maintenance_message" id="bf_maintenance_message" class="form-textarea" rows="3"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_maintenance_message', 'We are temporarily not accepting new bookings. Please check back later or contact us directly.'); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Booking Steps & Features', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group-grid two-cols">
                            <?php
                                $features = [
                                    'bf_show_progress_bar' => __('Show Progress Bar', 'mobooking'),
                                    'bf_allow_service_selection' => __('Allow service selection', 'mobooking'),
                                    'bf_enable_pet_information' => __('Enable pet information step', 'mobooking'),
                                    'bf_enable_service_frequency' => __('Enable service frequency selection', 'mobooking'),
                                    'bf_enable_datetime_selection' => __('Enable date & time selection', 'mobooking'),
                                    'bf_enable_property_access' => __('Enable property access options', 'mobooking'),
                                    'bf_enable_location_check' => __('Enable location check', 'mobooking'),
                                    'bf_allow_date_time_selection' => __('Allow date and time selection', 'mobooking'),
                                    'bf_require_phone' => __('Require phone number', 'mobooking'),
                                    'bf_allow_special_instructions' => __('Allow special instructions', 'mobooking'),
                                    'bf_show_pricing' => __('Show pricing information', 'mobooking'),
                                    'bf_allow_discount_codes' => __('Allow discount codes', 'mobooking'),
                                ];
                                foreach($features as $key => $label): ?>
                                <div class="form-group form-group-toggle small">
                                    <label class="mobooking-toggle-switch">
                                        <input name="<?php echo $key; ?>" type="checkbox" id="<?php echo $key; ?>" value="1" <?php echo mobooking_is_setting_checked($bf_settings, $key, true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="<?php echo $key; ?>" class="toggle-label"><?php echo $label; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                 <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Booking Window', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_booking_lead_time_hours"><?php esc_html_e('Minimum Lead Time (Hours)', 'mobooking'); ?></label>
                                <input name="bf_booking_lead_time_hours" type="number" id="bf_booking_lead_time_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_booking_lead_time_hours', '24'); ?>" min="0" max="168" class="form-input">
                                <p class="description"><?php esc_html_e('Minimum hours in advance customers must book.', 'mobooking'); ?></p>
                            </div>
                             <div class="form-group">
                                <label for="bf_max_booking_days_ahead"><?php esc_html_e('Maximum Days Ahead', 'mobooking'); ?></label>
                                <input name="bf_max_booking_days_ahead" type="number" id="bf_max_booking_days_ahead" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_max_booking_days_ahead', '30'); ?>" min="1" max="365" class="form-input">
                                <p class="description"><?php esc_html_e('Farthest day in the future a customer can book.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Design & Styling Tab -->
            <div id="design" class="mobooking-settings-tab-pane">
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Color Scheme', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group-grid three-cols">
                            <div class="form-group">
                                <label for="bf_theme_color"><?php esc_html_e('Primary Theme Color', 'mobooking'); ?></label>
                                <input name="bf_theme_color" type="color" id="bf_theme_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="mobooking-color-picker">
                                <p class="description"><?php esc_html_e('Main color for buttons and accents.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_secondary_color"><?php esc_html_e('Secondary Color', 'mobooking'); ?></label>
                                <input name="bf_secondary_color" type="color" id="bf_secondary_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>" class="mobooking-color-picker">
                                <p class="description"><?php esc_html_e('Color for borders and secondary elements.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'mobooking'); ?></label>
                                <input name="bf_background_color" type="color" id="bf_background_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="mobooking-color-picker">
                                <p class="description"><?php esc_html_e('Background of the form container.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Typography & Layout', 'mobooking'); ?></h3>
                    </div>
                    <div class.mobooking-card-content">
                        <div class="form-group-grid two-cols">
                            <div class="form-group">
                                <label for="bf_font_family"><?php esc_html_e('Font Family', 'mobooking'); ?></label>
                                <select name="bf_font_family" id="bf_font_family" class="form-select">
                                    <option value="system-ui" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family', 'system-ui'), 'system-ui'); ?>><?php esc_html_e('System Default', 'mobooking'); ?></option>
                                    <option value="Arial, sans-serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Arial, sans-serif'); ?>><?php esc_html_e('Arial', 'mobooking'); ?></option>
                                    <option value="Helvetica, sans-serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Helvetica, sans-serif'); ?>><?php esc_html_e('Helvetica', 'mobooking'); ?></option>
                                    <option value="Georgia, serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Georgia, serif'); ?>><?php esc_html_e('Georgia', 'mobooking'); ?></option>
                                    <option value="'Times New Roman', serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), "'Times New Roman', serif"); ?>><?php esc_html_e('Times New Roman', 'mobooking'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Font for the booking form text.', 'mobooking'); ?></p>
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
            <div id="advanced" class="mobooking-settings-tab-pane">
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
                                <label for="bf_time_slot_duration"><?php esc_html_e('Time Slot Duration', 'mobooking'); ?></label>
                                <select name="bf_time_slot_duration" id="bf_time_slot_duration" class="form-select">
                                    <option value="15" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '15'); ?>><?php esc_html_e('15 minutes', 'mobooking'); ?></option>
                                    <option value="30" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '30'); ?>><?php esc_html_e('30 minutes', 'mobooking'); ?></option>
                                    <option value="60" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '60'); ?>><?php esc_html_e('1 hour', 'mobooking'); ?></option>
                                    <option value="120" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '120'); ?>><?php esc_html_e('2 hours', 'mobooking'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('The interval between available time slots.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Integrations & Security', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="bf_google_analytics_id"><?php esc_html_e('Google Analytics ID', 'mobooking'); ?></label>
                            <input name="bf_google_analytics_id" type="text" id="bf_google_analytics_id" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_google_analytics_id'); ?>" class="form-input" placeholder="GA4-XXXXXXXXXX">
                            <p class="description"><?php esc_html_e('Track booking form interactions with Google Analytics.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="bf_webhook_url"><?php esc_html_e('Webhook URL', 'mobooking'); ?></label>
                            <input name="bf_webhook_url" type="url" id="bf_webhook_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_webhook_url'); ?>" class="form-input" placeholder="https://your-system.com/webhook">
                            <p class="description"><?php esc_html_e('Receive booking data in real-time for integrations.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group-grid three-cols">
                            <div class="form-group form-group-toggle small">
                                <label class="mobooking-toggle-switch">
                                    <input name="bf_enable_recaptcha" type="checkbox" id="bf_enable_recaptcha" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_recaptcha', false); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_enable_recaptcha" class="toggle-label"><?php esc_html_e( 'Enable reCAPTCHA', 'mobooking' ); ?></label>
                                </div>
                            </div>
                             <div class="form-group form-group-toggle small">
                                <label class="mobooking-toggle-switch">
                                    <input name="bf_enable_ssl_required" type="checkbox" id="bf_enable_ssl_required" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_ssl_required', true); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_enable_ssl_required" class="toggle-label"><?php esc_html_e( 'Require SSL', 'mobooking' ); ?></label>
                                </div>
                            </div>
                            <div class="form-group form-group-toggle small">
                                <label class="mobooking-toggle-switch">
                                    <input name="bf_debug_mode" type="checkbox" id="bf_debug_mode" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_debug_mode', false); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_debug_mode" class="toggle-label"><?php esc_html_e( 'Debug Mode', 'mobooking' ); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('System Tools', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label><?php esc_html_e('Rewrite Rules', 'mobooking'); ?></label>
                            <button type="button" id="mobooking-flush-rewrite-rules-btn" class="btn btn-secondary"><?php esc_html_e('Flush Rewrite Rules', 'mobooking'); ?></button>
                            <p class="description"><?php esc_html_e('If your booking form URLs are not working, flushing rewrite rules might help.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Share & Embed Tab -->
            <div id="share-embed" class="mobooking-settings-tab-pane">
                 <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Direct Link, Embed & QR Code', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="mobooking-public-link-input"><?php esc_html_e('Your Public Link', 'mobooking'); ?></label>
                            <div class="input-group">
                                <input type="text" id="mobooking-public-link-input" value="<?php echo esc_url($public_booking_url); ?>" readonly class="form-input" placeholder="<?php esc_attr_e('Link will appear here once slug is saved.', 'mobooking'); ?>">
                                <button type="button" id="mobooking-copy-public-link-btn-2" class="btn btn-secondary" <?php echo empty($public_booking_url) ? 'disabled' : ''; ?>><?php esc_html_e('Copy', 'mobooking'); ?></button>
                                <?php if (!empty($public_booking_url)): ?>
                                    <a href="<?php echo esc_url($public_booking_url); ?>" target="_blank" class="btn btn-outline"><?php esc_html_e('Preview', 'mobooking'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="mobooking-embed-code"><?php esc_html_e('Embed Code', 'mobooking'); ?></label>
                             <textarea id="mobooking-embed-code" readonly class="form-textarea code" rows="3" placeholder="<?php esc_attr_e('Embed code will appear here once slug is saved.', 'mobooking'); ?>"><?php
                            if (!empty($public_booking_url)) {
                                echo esc_textarea('<iframe src="' . esc_url($public_booking_url) . '" title="' . esc_attr__('Booking Form', 'mobooking') . '" style="width:100%; height:800px; border:1px solid #ccc;"></iframe>');
                            }
                            ?></textarea>
                            <div class="button-group">
                                <button type="button" id="mobooking-copy-embed-code-btn" class="btn btn-secondary" <?php echo empty($public_booking_url) ? 'disabled' : ''; ?>><?php esc_html_e('Copy Code', 'mobooking'); ?></button>
                                <button type="button" id="mobooking-customize-embed-btn" class="btn btn-outline"><?php esc_html_e('Customize', 'mobooking'); ?></button>
                            </div>
                        </div>
                        <div id="embed-customization-row" style="display: none;">
                            <div class="form-group-grid three-cols">
                                <div class="form-group">
                                    <label for="embed-width"><?php esc_html_e('Width', 'mobooking'); ?></label>
                                    <input type="text" id="embed-width" value="100%" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="embed-height"><?php esc_html_e('Height', 'mobooking'); ?></label>
                                    <input type="text" id="embed-height" value="800px" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="embed-border"><?php esc_html_e('Border', 'mobooking'); ?></label>
                                    <input type="text" id="embed-border" value="1px solid #ccc" class="form-input">
                                </div>
                            </div>
                            <button type="button" id="update-embed-code-btn" class="btn btn-primary"><?php esc_html_e('Update Embed Code', 'mobooking'); ?></button>
                        </div>
                    </div>
                    <div class="mobooking-card-footer">
                        <div id="qr-code-container">
                             <?php if (!empty($public_booking_url)): ?>
                                <img id="qr-code-image" src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode($public_booking_url); ?>" alt="<?php esc_attr_e('QR Code for Booking Form', 'mobooking'); ?>">
                            <?php else: ?>
                                <div class="qr-placeholder"><?php esc_html_e('QR Code will appear here', 'mobooking'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="qr-code-info">
                            <p><?php esc_html_e('Scan this QR code on a mobile device to open the booking form directly.', 'mobooking'); ?></p>
                             <?php if (!empty($public_booking_url)): ?>
                                <button type="button" id="download-qr-btn" class="btn btn-secondary"><?php esc_html_e('Download QR Code', 'mobooking'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>