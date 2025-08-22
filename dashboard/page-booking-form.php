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
<div id="mobooking-booking-form-settings-page" class="wrap mobooking-dashboard-wrap">
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
            <button type="submit" name="save_booking_form_settings" id="mobooking-save-bf-settings-btn" class="btn btn-primary"><?php esc_html_e('Save Changes', 'mobooking'); ?></button>
        </div>
    </div>

    <form id="mobooking-booking-form-settings-form" method="post">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>

        <!-- Share & Embed Card -->
        <div class="mobooking-card card-bs">
            <div class="mobooking-card-header">
                <h2 class="mobooking-card-title"><?php esc_html_e('Share & Embed', 'mobooking'); ?></h2>
            </div>
            <div class="mobooking-card-content">
                <div class="form-group">
                    <label for="mobooking-public-link"><?php esc_html_e('Your Public Booking Link', 'mobooking'); ?></label>
                    <div class="input-group">
                        <input type="text" id="mobooking-public-link" value="<?php echo esc_url($public_booking_url); ?>" readonly class="form-input" placeholder="<?php esc_attr_e('Link will appear here once slug is saved.', 'mobooking'); ?>">
                        <button type="button" id="mobooking-copy-public-link-btn" class="btn btn-secondary" <?php echo empty($public_booking_url) ? 'disabled' : ''; ?>><?php esc_html_e('Copy', 'mobooking'); ?></button>
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
                     <div class="input-group">
                         <button type="button" id="mobooking-copy-embed-code-btn" class="btn btn-secondary" <?php echo empty($public_booking_url) ? 'disabled' : ''; ?>><?php esc_html_e('Copy Embed', 'mobooking'); ?></button>
                     </div>
                </div>
            </div>
        </div>

        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General', 'mobooking'); ?></a>
            <a href="#form-control" class="nav-tab" data-tab="form-control"><?php esc_html_e('Form Control', 'mobooking'); ?></a>
            <a href="#design" class="nav-tab" data-tab="design"><?php esc_html_e('Design', 'mobooking'); ?></a>
            <a href="#advanced" class="nav-tab" data-tab="advanced"><?php esc_html_e('Advanced', 'mobooking'); ?></a>
        </h2>

        <div class="mobooking-settings-tabs-container">
            <!-- General Settings Tab -->
            <div id="mobooking-general-settings-tab" class="mobooking-settings-tab-content">
                <div class="mobooking-card">
                    <div class="mobooking-card-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="bf_business_slug"><?php esc_html_e('Business Slug', 'mobooking'); ?></label>
                                <input name="bf_business_slug" type="text" id="bf_business_slug" value="<?php echo esc_attr($current_slug); ?>" class="form-input">
                                <p class="description"><?php esc_html_e('Unique slug for your booking page URL. Use lowercase letters, numbers, and hyphens.', 'mobooking'); ?></p>
                            </div>
                             <div class="form-group">
                                <label for="bf_header_text"><?php esc_html_e('Form Header Text', 'mobooking'); ?></label>
                                <input name="bf_header_text" type="text" id="bf_header_text" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>" class="form-input">
                                <p class="description"><?php esc_html_e('The main title displayed at the top of your public booking form.', 'mobooking'); ?></p>
                            </div>
                             <div class="form-group">
                                <label for="bf_terms_conditions_url"><?php esc_html_e('Terms & Conditions URL', 'mobooking'); ?></label>
                                <input name="bf_terms_conditions_url" type="url" id="bf_terms_conditions_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_terms_conditions_url'); ?>" class="form-input" placeholder="https://example.com/terms">
                                <p class="description"><?php esc_html_e('Link to your terms and conditions page.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_success_message"><?php esc_html_e('Success Message', 'mobooking'); ?></label>
                                <textarea name="bf_success_message" id="bf_success_message" class="form-textarea" rows="4"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details.'); ?></textarea>
                                <p class="description"><?php esc_html_e('Message displayed after a successful booking.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Control Tab -->
            <div id="mobooking-form-control-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
                <div class="mobooking-card">
                    <div class="mobooking-card-content">
                        <div class="form-group-grid">
                            <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_form_enabled" type="checkbox" id="bf_form_enabled" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_form_enabled', true); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_form_enabled"><?php esc_html_e('Enable Booking Form', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('When disabled, the form will show a maintenance message.', 'mobooking'); ?></p>
                                </div>
                            </div>
                             <div class="form-group">
                                <label for="bf_maintenance_message"><?php esc_html_e('Maintenance Message', 'mobooking'); ?></label>
                                <textarea name="bf_maintenance_message" id="bf_maintenance_message" class="form-textarea" rows="3"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_maintenance_message', 'We are temporarily not accepting new bookings. Please check back later or contact us directly.'); ?></textarea>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group-grid">
                            <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_show_progress_bar" type="checkbox" id="bf_show_progress_bar" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_progress_bar', true); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_show_progress_bar"><?php esc_html_e('Show Progress Bar', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Display a step-by-step progress bar on the form.', 'mobooking'); ?></p>
                                </div>
                            </div>
                             <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_show_pricing" type="checkbox" id="bf_show_pricing" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_pricing', true); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_show_pricing"><?php esc_html_e('Show Pricing Information', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Display service prices on the form.', 'mobooking'); ?></p>
                                </div>
                            </div>
                             <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_allow_discount_codes" type="checkbox" id="bf_allow_discount_codes" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_allow_discount_codes', true); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_allow_discount_codes"><?php esc_html_e('Allow Discount Codes', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable the discount code field in the booking form.', 'mobooking'); ?></p>
                                </div>
                            </div>
                             <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_allow_special_instructions" type="checkbox" id="bf_allow_special_instructions" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_allow_special_instructions', true); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_allow_special_instructions"><?php esc_html_e('Allow Special Instructions', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Allow customers to add special instructions to their booking.', 'mobooking'); ?></p>
                                </div>
                            </div>
                             <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_require_phone" type="checkbox" id="bf_require_phone" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_require_phone', true); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_require_phone"><?php esc_html_e('Require Phone Number', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Make the phone number field mandatory for customers.', 'mobooking'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Design & Styling Tab -->
            <div id="mobooking-design-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
                <div class="mobooking-card">
                    <div class="mobooking-card-content">
                        <div class="form-grid-three">
                            <div class="form-group">
                                <label for="bf_theme_color"><?php esc_html_e('Primary Theme Color', 'mobooking'); ?></label>
                                <input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="mobooking-color-picker" data-default-color="#1abc9c">
                            </div>
                            <div class="form-group">
                                <label for="bf_secondary_color"><?php esc_html_e('Secondary Color', 'mobooking'); ?></label>
                                <input name="bf_secondary_color" type="text" id="bf_secondary_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>" class="mobooking-color-picker" data-default-color="#34495e">
                            </div>
                            <div class="form-group">
                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'mobooking'); ?></label>
                                <input name="bf_background_color" type="text" id="bf_background_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="mobooking-color-picker" data-default-color="#ffffff">
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="bf_font_family"><?php esc_html_e('Font Family', 'mobooking'); ?></label>
                                <select name="bf_font_family" id="bf_font_family" class="form-select">
                                    <option value="system-ui" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family', 'system-ui'), 'system-ui'); ?>><?php esc_html_e('System Default', 'mobooking'); ?></option>
                                    <option value="Arial, sans-serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Arial, sans-serif'); ?>><?php esc_html_e('Arial', 'mobooking'); ?></option>
                                    <option value="Helvetica, sans-serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Helvetica, sans-serif'); ?>><?php esc_html_e('Helvetica', 'mobooking'); ?></option>
                                    <option value="Georgia, serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Georgia, serif'); ?>><?php esc_html_e('Georgia', 'mobooking'); ?></option>
                                    <option value="'Times New Roman', serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), "'Times New Roman', serif"); ?>><?php esc_html_e('Times New Roman', 'mobooking'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bf_border_radius"><?php esc_html_e('Border Radius (px)', 'mobooking'); ?></label>
                                <input name="bf_border_radius" type="number" id="bf_border_radius" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_border_radius', '8'); ?>" min="0" max="50" class="form-input">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bf_custom_css"><?php esc_html_e('Custom CSS', 'mobooking'); ?></label>
                            <textarea name="bf_custom_css" id="bf_custom_css" class="form-textarea code" rows="8" placeholder="<?php esc_attr_e('/* Your custom CSS rules here */', 'mobooking'); ?>"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_custom_css'); ?></textarea>
                            <p class="description"><?php esc_html_e('Apply custom styles to the public booking form. Use .mobooking-form to target elements.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings Tab -->
            <div id="mobooking-advanced-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
                <div class="mobooking-card">
                     <div class="mobooking-card-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="bf_booking_lead_time_hours"><?php esc_html_e('Minimum Lead Time (Hours)', 'mobooking'); ?></label>
                                <input name="bf_booking_lead_time_hours" type="number" id="bf_booking_lead_time_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_booking_lead_time_hours', '24'); ?>" min="0" max="168" class="form-input">
                                <p class="description"><?php esc_html_e('Minimum hours in advance customers must book.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_max_booking_days_ahead"><?php esc_html_e('Maximum Days Ahead', 'mobooking'); ?></label>
                                <input name="bf_max_booking_days_ahead" type="number" id="bf_max_booking_days_ahead" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_max_booking_days_ahead', '30'); ?>" min="1" max="365" class="form-input">
                                <p class="description"><?php esc_html_e('Maximum number of days in advance customers can book.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_allow_cancellation_hours"><?php esc_html_e('Cancellation Lead Time (Hours)', 'mobooking'); ?></label>
                                <input name="bf_allow_cancellation_hours" type="number" id="bf_allow_cancellation_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_allow_cancellation_hours', '24'); ?>" min="0" class="form-input">
                                <p class="description"><?php esc_html_e('Minimum hours before booking a customer can cancel.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_time_slot_duration"><?php esc_html_e('Time Slot Duration (Minutes)', 'mobooking'); ?></label>
                                <select name="bf_time_slot_duration" id="bf_time_slot_duration" class="form-select">
                                    <option value="15" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration'), '15'); ?>>15 minutes</option>
                                    <option value="30" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '30'); ?>>30 minutes</option>
                                    <option value="60" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration'), '60'); ?>>1 hour</option>
                                    <option value="120" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration'), '120'); ?>>2 hours</option>
                                </select>
                                <p class="description"><?php esc_html_e('The interval between available time slots.', 'mobooking'); ?></p>
                            </div>
                        </div>
                        <hr>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="bf_google_analytics_id"><?php esc_html_e('Google Analytics ID', 'mobooking'); ?></label>
                                <input name="bf_google_analytics_id" type="text" id="bf_google_analytics_id" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_google_analytics_id'); ?>" class="form-input" placeholder="GA4-XXXXXXXXXX">
                                <p class="description"><?php esc_html_e('Track interactions with Google Analytics.', 'mobooking'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="bf_webhook_url"><?php esc_html_e('Webhook URL', 'mobooking'); ?></label>
                                <input name="bf_webhook_url" type="url" id="bf_webhook_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_webhook_url'); ?>" class="form-input" placeholder="https://your-system.com/webhook">
                                <p class="description"><?php esc_html_e('Receive booking data in real-time.', 'mobooking'); ?></p>
                            </div>
                        </div>
                         <hr>
                        <div class="form-group-grid">
                            <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_enable_recaptcha" type="checkbox" id="bf_enable_recaptcha" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_recaptcha'); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_enable_recaptcha"><?php esc_html_e('Enable reCAPTCHA', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Protect your form from spam with Google reCAPTCHA.', 'mobooking'); ?></p>
                                </div>
                            </div>
                            <div class="form-group-toggle">
                                <label class="switch">
                                    <input name="bf_debug_mode" type="checkbox" id="bf_debug_mode" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_debug_mode'); ?>>
                                    <span class="switch-thumb"></span>
                                </label>
                                <div class="toggle-label-group">
                                    <label for="bf_debug_mode"><?php esc_html_e('Enable Debug Mode', 'mobooking'); ?></label>
                                    <p class="description"><?php esc_html_e('Enable debug mode for troubleshooting.', 'mobooking'); ?></p>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                             <label><?php esc_html_e('Rewrite Rules', 'mobooking'); ?></label>
                             <button type="button" id="mobooking-flush-rewrite-rules-btn" class="btn btn-secondary"><?php esc_html_e('Flush Rewrite Rules', 'mobooking'); ?></button>
                            <p class="description"><?php esc_html_e('If your booking form URL is not working, flushing rewrite rules might help.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>