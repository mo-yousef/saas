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
    $public_booking_url = trailingslashit(site_url()) . 'bookings/' . esc_attr($current_slug) . '/';
}

?>
<div id="mobooking-booking-form-settings-page" class="wrap">
    <h1><?php esc_html_e('Booking Form Settings', 'mobooking'); ?></h1>
    <p><?php esc_html_e('Customize the appearance and behavior of your public booking form.', 'mobooking'); ?></p>

    <form id="mobooking-booking-form-settings-form" method="post">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
        <div id="mobooking-settings-feedback" style="margin-bottom:15px; margin-top:10px;"></div>

        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="#mobooking-general-settings-tab" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General Settings', 'mobooking'); ?></a>
            <a href="#mobooking-form-control-settings-tab" class="nav-tab" data-tab="form-control"><?php esc_html_e('Form Control', 'mobooking'); ?></a>
            <a href="#mobooking-design-settings-tab" class="nav-tab" data-tab="design"><?php esc_html_e('Design & Styling', 'mobooking'); ?></a>
            <a href="#mobooking-advanced-settings-tab" class="nav-tab" data-tab="advanced"><?php esc_html_e('Advanced', 'mobooking'); ?></a>
            <a href="#mobooking-share-embed-settings-tab" class="nav-tab" data-tab="share-embed"><?php esc_html_e('Share & Embed', 'mobooking'); ?></a>
        </h2>

        <!-- General Settings Tab -->
        <div id="mobooking-general-settings-tab" class="mobooking-settings-tab-content">
            <h3><?php esc_html_e('General Settings', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_business_slug"><?php esc_html_e('Business Slug', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_business_slug" type="text" id="bf_business_slug" value="<?php echo esc_attr($current_slug); ?>" class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Unique slug for your public booking page URL (e.g., your-business-name). It will be used like: ', 'mobooking'); ?>
                            <code><?php echo trailingslashit(site_url()); ?>bookings/your-business-slug/</code>
                        </p>
                        <p class="description"><?php esc_html_e('Changing this will change your public booking form URL. Only use lowercase letters, numbers, and hyphens.', 'mobooking'); ?></p>
                        <?php if (!empty($public_booking_url)): ?>
                            <p class="description">
                                <strong><?php esc_html_e('Current URL:', 'mobooking'); ?></strong> 
                                <a href="<?php echo esc_url($public_booking_url); ?>" target="_blank"><?php echo esc_url($public_booking_url); ?></a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_header_text"><?php esc_html_e('Form Header Text', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_header_text" type="text" id="bf_header_text" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('The main title displayed at the top of your public booking form.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_show_progress_bar"><?php esc_html_e('Show Progress Bar', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_show_progress_bar" type="checkbox" id="bf_show_progress_bar" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_progress_bar', true); ?>>
                        <p class="description"><?php esc_html_e('Display a step-by-step progress indicator on the form.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_terms_conditions_url"><?php esc_html_e('Terms & Conditions URL', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_terms_conditions_url" type="url" id="bf_terms_conditions_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_terms_conditions_url'); ?>" class="regular-text" placeholder="https://example.com/terms">
                        <p class="description"><?php esc_html_e('Link to your terms and conditions page. If provided, a checkbox agreeing to terms may be shown.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_success_message"><?php esc_html_e('Success Message', 'mobooking'); ?></label></th>
                    <td>
                        <textarea name="bf_success_message" id="bf_success_message" class="large-text" rows="4"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details. A confirmation email has been sent to you.'); ?></textarea>
                        <p class="description"><?php esc_html_e('Message displayed to customer after successful booking (on Step 6).', 'mobooking'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Form Control Tab -->
        <div id="mobooking-form-control-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Form Control & Features', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_form_enabled"><?php esc_html_e('Enable Booking Form', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_form_enabled" type="checkbox" id="bf_form_enabled" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_form_enabled', true); ?>>
                        <label for="bf_form_enabled"><?php esc_html_e('Allow customers to submit bookings through the public form', 'mobooking'); ?></label>
                        <p class="description"><?php esc_html_e('When disabled, the form will show a maintenance message instead of allowing bookings.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_maintenance_message"><?php esc_html_e('Maintenance Message', 'mobooking'); ?></label></th>
                    <td>
                        <textarea name="bf_maintenance_message" id="bf_maintenance_message" class="large-text" rows="3"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_maintenance_message', 'We are temporarily not accepting new bookings. Please check back later or contact us directly.'); ?></textarea>
                        <p class="description"><?php esc_html_e('Message shown when the booking form is disabled.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Form Features', 'mobooking'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e('Form Features', 'mobooking'); ?></span></legend>
                            <label for="bf_allow_service_selection">
                                <input name="bf_allow_service_selection" type="checkbox" id="bf_allow_service_selection" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_allow_service_selection', true); ?>>
                                <?php esc_html_e('Allow customers to select services', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_allow_date_time_selection">
                                <input name="bf_allow_date_time_selection" type="checkbox" id="bf_allow_date_time_selection" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_allow_date_time_selection', true); ?>>
                                <?php esc_html_e('Allow customers to select date and time', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_require_phone">
                                <input name="bf_require_phone" type="checkbox" id="bf_require_phone" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_require_phone', true); ?>>
                                <?php esc_html_e('Require phone number', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_allow_special_instructions">
                                <input name="bf_allow_special_instructions" type="checkbox" id="bf_allow_special_instructions" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_allow_special_instructions', true); ?>>
                                <?php esc_html_e('Allow special instructions/notes', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_show_pricing">
                                <input name="bf_show_pricing" type="checkbox" id="bf_show_pricing" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_pricing', true); ?>>
                                <?php esc_html_e('Show pricing information', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_allow_discount_codes">
                                <input name="bf_allow_discount_codes" type="checkbox" id="bf_allow_discount_codes" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_allow_discount_codes', true); ?>>
                                <?php esc_html_e('Allow discount code application', 'mobooking'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_booking_lead_time_hours"><?php esc_html_e('Minimum Lead Time (Hours)', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_booking_lead_time_hours" type="number" id="bf_booking_lead_time_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_booking_lead_time_hours', '24'); ?>" min="0" max="168" class="small-text">
                        <p class="description"><?php esc_html_e('Minimum hours in advance customers must book (0-168 hours = 1 week max).', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_max_booking_days_ahead"><?php esc_html_e('Maximum Days Ahead', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_max_booking_days_ahead" type="number" id="bf_max_booking_days_ahead" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_max_booking_days_ahead', '30'); ?>" min="1" max="365" class="small-text">
                        <p class="description"><?php esc_html_e('Maximum number of days in advance customers can book (1-365 days).', 'mobooking'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Design & Styling Tab -->
        <div id="mobooking-design-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Design & Appearance', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_theme_color"><?php esc_html_e('Primary Theme Color', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="mobooking-color-picker" data-default-color="#1abc9c">
                        <p class="description"><?php esc_html_e('Main color for buttons and progress bar accents.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_secondary_color"><?php esc_html_e('Secondary Color', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_secondary_color" type="text" id="bf_secondary_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>" class="mobooking-color-picker" data-default-color="#34495e">
                        <p class="description"><?php esc_html_e('Color for borders, icons, and secondary elements.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_background_color"><?php esc_html_e('Background Color', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_background_color" type="text" id="bf_background_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="mobooking-color-picker" data-default-color="#ffffff">
                        <p class="description"><?php esc_html_e('Background color for the form container.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_font_family"><?php esc_html_e('Font Family', 'mobooking'); ?></label></th>
                    <td>
                        <select name="bf_font_family" id="bf_font_family">
                            <option value="system-ui" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family', 'system-ui'), 'system-ui'); ?>><?php esc_html_e('System Default', 'mobooking'); ?></option>
                            <option value="Arial, sans-serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Arial, sans-serif'); ?>><?php esc_html_e('Arial', 'mobooking'); ?></option>
                            <option value="Helvetica, sans-serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Helvetica, sans-serif'); ?>><?php esc_html_e('Helvetica', 'mobooking'); ?></option>
                            <option value="Georgia, serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), 'Georgia, serif'); ?>><?php esc_html_e('Georgia', 'mobooking'); ?></option>
                            <option value="'Times New Roman', serif" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_font_family'), "'Times New Roman', serif"); ?>><?php esc_html_e('Times New Roman', 'mobooking'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Font family for the booking form text.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_border_radius"><?php esc_html_e('Border Radius (px)', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_border_radius" type="number" id="bf_border_radius" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_border_radius', '8'); ?>" min="0" max="50" class="small-text">
                        <p class="description"><?php esc_html_e('Roundness of form elements (0 = square, higher = more rounded).', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_custom_css"><?php esc_html_e('Custom CSS', 'mobooking'); ?></label></th>
                    <td>
                        <textarea name="bf_custom_css" id="bf_custom_css" class="large-text code" rows="8" placeholder="<?php esc_attr_e('/* Your custom CSS rules here */', 'mobooking'); ?>"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_custom_css'); ?></textarea>
                        <p class="description"><?php esc_html_e('Apply custom styles to the public booking form. Use with caution.', 'mobooking'); ?></p>
                        <p class="description"><strong><?php esc_html_e('Tip:', 'mobooking'); ?></strong> <?php esc_html_e('Use .mobooking-form as the main selector to target form elements.', 'mobooking'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Advanced Settings Tab -->
        <div id="mobooking-advanced-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Advanced Settings', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_allow_cancellation_hours"><?php esc_html_e('Cancellation Lead Time (Hours)', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_allow_cancellation_hours" type="number" id="bf_allow_cancellation_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_allow_cancellation_hours', '24'); ?>" min="0" class="small-text">
                        <p class="description"><?php esc_html_e('Minimum hours before booking time a customer can cancel. Enter 0 if cancellation via form is not allowed or handled differently.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_time_slot_duration"><?php esc_html_e('Time Slot Duration (Minutes)', 'mobooking'); ?></label></th>
                    <td>
                        <select name="bf_time_slot_duration" id="bf_time_slot_duration">
                            <option value="15" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '15'); ?>><?php esc_html_e('15 minutes', 'mobooking'); ?></option>
                            <option value="30" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '30'); ?>><?php esc_html_e('30 minutes', 'mobooking'); ?></option>
                            <option value="60" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '60'); ?>><?php esc_html_e('1 hour', 'mobooking'); ?></option>
                            <option value="120" <?php selected(mobooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '30'), '120'); ?>><?php esc_html_e('2 hours', 'mobooking'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Interval between available time slots.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_google_analytics_id"><?php esc_html_e('Google Analytics ID', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_google_analytics_id" type="text" id="bf_google_analytics_id" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_google_analytics_id'); ?>" class="regular-text" placeholder="GA4-XXXXXXXXXX">
                        <p class="description"><?php esc_html_e('Track booking form interactions with Google Analytics (optional).', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_webhook_url"><?php esc_html_e('Webhook URL', 'mobooking'); ?></label></th>
                    <td>
                        <input name="bf_webhook_url" type="url" id="bf_webhook_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_webhook_url'); ?>" class="regular-text" placeholder="https://your-system.com/webhook">
                        <p class="description"><?php esc_html_e('Optional webhook to receive booking data in real-time (for integrations).', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Advanced Options', 'mobooking'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php esc_html_e('Advanced Options', 'mobooking'); ?></span></legend>
                            <label for="bf_enable_recaptcha">
                                <input name="bf_enable_recaptcha" type="checkbox" id="bf_enable_recaptcha" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_recaptcha', false); ?>>
                                <?php esc_html_e('Enable reCAPTCHA protection', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_enable_ssl_required">
                                <input name="bf_enable_ssl_required" type="checkbox" id="bf_enable_ssl_required" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_enable_ssl_required', true); ?>>
                                <?php esc_html_e('Require SSL/HTTPS', 'mobooking'); ?>
                            </label><br><br>
                            
                            <label for="bf_debug_mode">
                                <input name="bf_debug_mode" type="checkbox" id="bf_debug_mode" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_debug_mode', false); ?>>
                                <?php esc_html_e('Enable debug mode (for troubleshooting)', 'mobooking'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Share & Embed Tab -->
        <div id="mobooking-share-embed-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Share Your Booking Form', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="mobooking-public-link"><?php esc_html_e('Your Public Link', 'mobooking'); ?></label></th>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="mobooking-public-link" value="<?php echo esc_url($public_booking_url); ?>" readonly class="regular-text" style="flex: 1;" placeholder="<?php esc_attr_e('Link will appear here once slug is saved.', 'mobooking'); ?>">
                            <button type="button" id="mobooking-copy-public-link-btn" class="button button-secondary" <?php echo empty($public_booking_url) ? 'disabled' : ''; ?>><?php esc_html_e('Copy Link', 'mobooking'); ?></button>
                            <?php if (!empty($public_booking_url)): ?>
                                <a href="<?php echo esc_url($public_booking_url); ?>" target="_blank" class="button button-secondary"><?php esc_html_e('Preview', 'mobooking'); ?></a>
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php esc_html_e('Share this link with your customers so they can book your services online.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="mobooking-embed-code"><?php esc_html_e('Embed Code', 'mobooking'); ?></label></th>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <textarea id="mobooking-embed-code" readonly class="large-text code" rows="4" placeholder="<?php esc_attr_e('Embed code will appear here once slug is saved.', 'mobooking'); ?>"><?php 
                            if (!empty($public_booking_url)) {
                                echo esc_textarea('<iframe src="' . esc_url($public_booking_url) . '" title="' . esc_attr__('Booking Form', 'mobooking') . '" style="width:100%; height:800px; border:1px solid #ccc;"></iframe>');
                            }
                            ?></textarea>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" id="mobooking-copy-embed-code-btn" class="button button-secondary" <?php echo empty($public_booking_url) ? 'disabled' : ''; ?>><?php esc_html_e('Copy Embed Code', 'mobooking'); ?></button>
                                <button type="button" id="mobooking-customize-embed-btn" class="button button-secondary"><?php esc_html_e('Customize Embed', 'mobooking'); ?></button>
                            </div>
                        </div>
                        <p class="description"><?php esc_html_e('Copy this code and paste it into any website where you want to embed your booking form.', 'mobooking'); ?></p>
                    </td>
                </tr>
                <tr valign="top" id="embed-customization-row" style="display: none;">
                    <th scope="row"><?php esc_html_e('Embed Customization', 'mobooking'); ?></th>
                    <td>
                        <table class="widefat" style="max-width: 600px;">
                            <tr>
                                <td><label for="embed-width"><?php esc_html_e('Width:', 'mobooking'); ?></label></td>
                                <td><input type="text" id="embed-width" value="100%" class="small-text"> <span class="description"><?php esc_html_e('(e.g., 100%, 800px)', 'mobooking'); ?></span></td>
                            </tr>
                            <tr>
                                <td><label for="embed-height"><?php esc_html_e('Height:', 'mobooking'); ?></label></td>
                                <td><input type="text" id="embed-height" value="800px" class="small-text"> <span class="description"><?php esc_html_e('(e.g., 800px, 100vh)', 'mobooking'); ?></span></td>
                            </tr>
                            <tr>
                                <td><label for="embed-border"><?php esc_html_e('Border:', 'mobooking'); ?></label></td>
                                <td><input type="text" id="embed-border" value="1px solid #ccc" class="regular-text"> <span class="description"><?php esc_html_e('(e.g., none, 1px solid #ccc)', 'mobooking'); ?></span></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <button type="button" id="update-embed-code-btn" class="button button-primary"><?php esc_html_e('Update Embed Code', 'mobooking'); ?></button>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('QR Code', 'mobooking'); ?></th>
                    <td>
                        <div id="qr-code-container" style="margin: 10px 0;">
                            <?php if (!empty($public_booking_url)): ?>
                                <img id="qr-code-image" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($public_booking_url); ?>" alt="<?php esc_attr_e('QR Code for Booking Form', 'mobooking'); ?>" style="border: 1px solid #ddd; padding: 10px; background: white;">
                            <?php else: ?>
                                <div style="width: 150px; height: 150px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; background: #f9f9f9; color: #666;">
                                    <?php esc_html_e('QR Code will appear here', 'mobooking'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php esc_html_e('Print this QR code on business cards, flyers, or display it in your physical location.', 'mobooking'); ?></p>
                        <?php if (!empty($public_booking_url)): ?>
                            <button type="button" id="download-qr-btn" class="button button-secondary"><?php esc_html_e('Download QR Code', 'mobooking'); ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit" style="margin-top:20px;">
            <button type="submit" name="save_booking_form_settings" id="mobooking-save-bf-settings-btn" class="button button-primary"><?php esc_html_e('Save Booking Form Settings', 'mobooking'); ?></button>
        </p>
    </form>
</div>

<style>

.form-table th {
    width: 200px;
    font-weight: 600;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"],
.form-table input[type="url"],
.form-table input[type="number"],
.form-table select,
.form-table textarea {
    font-size: 14px;
}

.form-table .description {
    margin-top: 5px;
    font-style: italic;
    color: #666;
}

fieldset label {
    display: inline-block;
    margin-right: 20px;
    font-weight: normal;
}

#mobooking-settings-feedback {
    max-width: 100%;
}

#mobooking-settings-feedback.notice {
    padding: 10px 15px;
    margin: 5px 0 15px 0;
    border-left: 4px solid;
    background: #fff;
}

#mobooking-settings-feedback.notice-success {
    border-left-color: #46b450;
    background: #f7fcf0;
}

#mobooking-settings-feedback.notice-error {
    border-left-color: #dc3232;
    background: #fef7f1;
}

.button.button-secondary:disabled {
    color: #a0a5aa !important;
    border-color: #ddd !important;
    background: #f6f7f7 !important;
    cursor: not-allowed;
}

/* QR Code styling */
#qr-code-container img {
    border-radius: 8px;
}

/* Embed customization */
.widefat td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.widefat td:first-child {
    font-weight: 600;
    width: 100px;
}

/* Color picker alignment */
.wp-picker-container {
    display: inline-block;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .form-table th,
    .form-table td {
        display: block;
        width: auto;
        padding: 10px 0;
    }
    
    .form-table th {
        padding-bottom: 5px;
    }
    
    .nav-tab-wrapper {
        border-bottom: 1px solid #ccd0d4;
    }
    
    .nav-tab {
        display: block;
        margin: 0;
        border-bottom: 1px solid #ccd0d4;
    }
    
    .nav-tab:last-child {
        border-bottom: none;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    'use strict';

    // Use the existing localized parameters from the external JS file
    // The external file already handles initialization and fallbacks for mobooking_bf_settings_params
    
    const form = $('#mobooking-booking-form-settings-form');
    const feedbackDiv = $('#mobooking-settings-feedback');
    const saveButton = $('#mobooking-save-bf-settings-btn');

    // Fix tab navigation to work with our new tab structure
    const navTabs = $('.nav-tab-wrapper .nav-tab');
    const tabContents = $('.mobooking-settings-tab-content');

    navTabs.on('click', function(e) {
        e.preventDefault();
        navTabs.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        tabContents.hide();
        $('#mobooking-' + $(this).data('tab') + '-settings-tab').show();
    });

    // Initialize Color Picker (WordPress handles this)
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.mobooking-color-picker').wpColorPicker();
    }

    // Dynamic update for public link and embed code
    const businessSlugInput = $('#bf_business_slug');
    const publicLinkInput = $('#mobooking-public-link');
    const embedCodeTextarea = $('#mobooking-embed-code');
    const copyLinkBtn = $('#mobooking-copy-public-link-btn');
    const copyEmbedBtn = $('#mobooking-copy-embed-code-btn');
    const qrCodeImage = $('#qr-code-image');
    const downloadQrBtn = $('#download-qr-btn');

    // Use the site URL from the existing parameters
    let baseSiteUrl = (typeof mobooking_bf_settings_params !== 'undefined' && mobooking_bf_settings_params.site_url) 
        ? mobooking_bf_settings_params.site_url 
        : window.location.origin + '/';
        
    if (baseSiteUrl.slice(-1) !== '/') {
        baseSiteUrl += '/';
    }

    function updateShareableLinks(slug) {
        const sanitizedSlug = slug.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
        if (sanitizedSlug) {
            const publicLink = baseSiteUrl + 'bookings/' + sanitizedSlug + '/';
            const embedLink = baseSiteUrl + 'embed-booking/' + sanitizedSlug + '/'; // New embed link structure
            const embedCode = `<iframe src="${embedLink}" title="Booking Form" style="width:100%; height:800px; border:1px solid #ccc;"></iframe>`;
            
            publicLinkInput.val(publicLink);
            embedCodeTextarea.val(embedCode);
            copyLinkBtn.prop('disabled', false);
            copyEmbedBtn.prop('disabled', false);
            
            // Update QR code
            if (qrCodeImage.length) {
                qrCodeImage.attr('src', `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(publicLink)}`);
            }
            if (downloadQrBtn.length) {
                downloadQrBtn.prop('disabled', false);
            }
            
            // Update preview link if it exists
            // Ensure we target the correct preview link associated with the public URL input field
            const previewLink = publicLinkInput.closest('td').find('a[target="_blank"].button-secondary');
            if (previewLink.length) {
                previewLink.attr('href', publicLink);
            }
        } else {
            publicLinkInput.val('').attr('placeholder', 'Link will appear here once slug is saved.');
            embedCodeTextarea.val('').attr('placeholder', 'Embed code will appear here once slug is saved.');
            copyLinkBtn.prop('disabled', true);
            copyEmbedBtn.prop('disabled', true);
            if (downloadQrBtn.length) {
                downloadQrBtn.prop('disabled', true);
            }
        }
    }

    if (businessSlugInput.length) {
        businessSlugInput.on('input', function() {
            updateShareableLinks($(this).val());
        });
        updateShareableLinks(businessSlugInput.val()); // Initial update
    }

    // Copy to clipboard functionality
    function copyToClipboard(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(button);
            }).catch(function() {
                fallbackCopyTextToClipboard(text, button);
            });
        } else {
            fallbackCopyTextToClipboard(text, button);
        }
    }

    function fallbackCopyTextToClipboard(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            showCopyError(button);
        }
        
        document.body.removeChild(textArea);
    }

    function showCopySuccess(button) {
        const originalText = button.text();
        button.text('Copied!').addClass('button-primary').removeClass('button-secondary');
        setTimeout(function() {
            button.text(originalText).removeClass('button-primary').addClass('button-secondary');
        }, 2000);
    }

    function showCopyError(button) {
        const originalText = button.text();
        button.text('Copy failed').addClass('button-secondary');
        setTimeout(function() {
            button.text(originalText);
        }, 2000);
    }

    copyLinkBtn.on('click', function() {
        copyToClipboard(publicLinkInput.val(), $(this));
    });

    copyEmbedBtn.on('click', function() {
        copyToClipboard(embedCodeTextarea.val(), $(this));
    });

    // Embed customization
    $('#mobooking-customize-embed-btn').on('click', function() {
        $('#embed-customization-row').toggle();
    });

    $('#update-embed-code-btn').on('click', function() {
        const width = $('#embed-width').val() || '100%';
        const height = $('#embed-height').val() || '800px';
        const border = $('#embed-border').val() || '1px solid #ccc';
        const url = publicLinkInput.val();
        
        if (url) {
            const customEmbedCode = `<iframe src="${url}" title="Booking Form" style="width:${width}; height:${height}; border:${border};"></iframe>`;
            embedCodeTextarea.val(customEmbedCode);
        }
    });

    // QR Code download
    downloadQrBtn.on('click', function() {
        const qrUrl = qrCodeImage.attr('src');
        if (qrUrl) {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = 'booking-form-qr-code.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });

    // Form state management for enabling/disabling form
    $('#bf_form_enabled').on('change', function() {
        const isEnabled = $(this).is(':checked');
        const maintenanceRow = $('input[name="bf_maintenance_message"]').closest('tr');
        
        if (isEnabled) {
            maintenanceRow.hide();
        } else {
            maintenanceRow.show();
        }
    }).trigger('change'); // Initialize on page load

    // Note: Form submission is handled by the external dashboard-booking-form-settings.js file
    // which already has the proper AJAX handling and localized parameters
    
    console.log('MoBooking Enhanced Booking Form Settings initialized successfully.');
});
</script>