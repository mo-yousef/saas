<?php
/**
 * Dashboard Page: Booking Form Settings - FIXED VERSION
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \NORDBOOKING\Classes\Settings();
$user_id = get_current_user_id();
$bf_settings = $settings_manager->get_booking_form_settings($user_id);
$biz_settings = $settings_manager->get_business_settings($user_id); // For biz_name

// Helper to get value, escape it, and provide default
function nordbooking_get_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function nordbooking_get_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function nordbooking_is_setting_checked($settings, $key, $default_is_checked = false) {
    $val = isset($settings[$key]) ? $settings[$key] : ($default_is_checked ? '1' : '0');
    return checked('1', $val, false);
}

// Get current public booking form URL
$current_slug = nordbooking_get_setting_value($bf_settings, 'bf_business_slug', '');
if (empty($current_slug) && !empty($biz_settings['biz_name'])) {
    $current_slug = sanitize_title($biz_settings['biz_name']);
}

$public_booking_url = '';
if (!empty($current_slug)) {
    // Use the new standardized URL structure
    $public_booking_url = trailingslashit(site_url()) . 'booking/' . esc_attr($current_slug) . '/';
}

?>
<div id="NORDBOOKING-booking-form-settings-page" class="wrap NORDBOOKING-settings-page">
    <div class="NORDBOOKING-page-header">
        <div class="NORDBOOKING-page-header-heading">
            <span class="NORDBOOKING-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('booking_form'); ?>
            </span>
            <div class="heading-wrapper">
                <h1><?php esc_html_e('Booking Form Settings', 'NORDBOOKING'); ?></h1>
                <p class="dashboard-subtitle"><?php esc_html_e('Customize the appearance and behavior of your public booking form.', 'NORDBOOKING'); ?></p>
            </div>
        </div>
        <div class="NORDBOOKING-page-header-actions">
            <button type="submit" form="NORDBOOKING-booking-form-settings-form" name="save_booking_form_settings" id="NORDBOOKING-save-bf-settings-btn" class="btn btn-primary"><?php esc_html_e('Save Changes', 'NORDBOOKING'); ?></button>
        </div>
    </div>

    <?php if (!empty($public_booking_url)): ?>
    <div class="NORDBOOKING-public-link-display">
        <span class="link-label"><?php esc_html_e('Your Booking Form is live at:', 'NORDBOOKING'); ?></span>
        <a href="<?php echo esc_url($public_booking_url); ?>" target="_blank" id="bf-public-link"><?php echo esc_url($public_booking_url); ?></a>
        <button class="btn btn-sm btn-icon" id="NORDBOOKING-copy-public-link-btn" title="<?php esc_attr_e('Copy link', 'NORDBOOKING'); ?>" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 16V18.8C8 19.9201 8 20.4802 8.21799 20.908C8.40973 21.2843 8.71569 21.5903 9.09202 21.782C9.51984 22 10.0799 22 11.2 22H18.8C19.9201 22 20.4802 22 20.908 21.782C21.2843 21.5903 21.5903 21.2843 21.782 20.908C22 20.4802 22 19.9201 22 18.8V11.2C22 10.0799 22 9.51984 21.782 9.09202C21.5903 8.71569 21.2843 8.40973 20.908 8.21799C20.4802 8 19.9201 8 18.8 8H16M5.2 16H12.8C13.9201 16 14.4802 16 14.908 15.782C15.2843 15.5903 15.5903 15.2843 15.782 14.908C16 14.4802 16 13.9201 16 12.8V5.2C16 4.0799 16 3.51984 15.782 3.09202C15.5903 2.71569 15.2843 2.40973 14.908 2.21799C14.4802 2 13.9201 2 12.8 2H5.2C4.0799 2 3.51984 2 3.09202 2.21799C2.71569 2.40973 2.40973 2.71569 2.21799 3.09202C2 3.51984 2 4.07989 2 5.2V12.8C2 13.9201 2 14.4802 2.21799 14.908C2.40973 15.2843 2.71569 15.5903 3.09202 15.782C3.51984 16 4.07989 16 5.2 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <?php if (!empty($public_booking_url)): ?>
            <button type="button" id="download-qr-btn" class="btn btn-secondary btn-sm"><?php esc_html_e('Download QR Code', 'NORDBOOKING'); ?></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="NORDBOOKING-settings-grid">
        <div class="NORDBOOKING-settings-main">
            <form id="NORDBOOKING-booking-form-settings-form" method="post" class="NORDBOOKING-settings-form">
                <?php wp_nonce_field('nordbooking_dashboard_nonce', 'nordbooking_dashboard_nonce_field'); ?>

                <!-- Tab Navigation -->
                <div class="NORDBOOKING-settings-tabs" role="tablist" aria-label="<?php esc_attr_e('Booking Form Settings', 'NORDBOOKING'); ?>">
                    <a href="#general" class="NORDBOOKING-tab-item active" data-tab="general" role="tab">
                        <?php echo nordbooking_get_booking_form_tab_icon('cog'); ?>
                        <span><?php esc_html_e('General', 'NORDBOOKING'); ?></span>
                    </a>
                    <a href="#form-control" class="NORDBOOKING-tab-item" data-tab="form-control" role="tab">
                        <?php echo nordbooking_get_booking_form_tab_icon('toggle'); ?>
                        <span><?php esc_html_e('Form Control', 'NORDBOOKING'); ?></span>
                    </a>
                    <a href="#design" class="NORDBOOKING-tab-item" data-tab="design" role="tab">
                        <?php echo nordbooking_get_booking_form_tab_icon('star'); ?>
                        <span><?php esc_html_e('Design', 'NORDBOOKING'); ?></span>
                    </a>
                    <a href="#advanced" class="NORDBOOKING-tab-item" data-tab="advanced" role="tab">
                        <?php echo nordbooking_get_booking_form_tab_icon('tools'); ?>
                        <span><?php esc_html_e('Advanced', 'NORDBOOKING'); ?></span>
                    </a>
                    <a href="#share-embed" class="NORDBOOKING-tab-item" data-tab="share-embed" role="tab">
                        <?php echo nordbooking_get_booking_form_tab_icon('share'); ?>
                        <span><?php esc_html_e('Share & Embed', 'NORDBOOKING'); ?></span>
                    </a>
                </div>

                <div class="NORDBOOKING-settings-content">
                    <!-- General Settings Tab -->
                    <div id="general" class="NORDBOOKING-settings-tab-pane active" role="tabpanel">
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Basic Information', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group">
                                    <label for="bf_success_message"><?php esc_html_e('Success Message', 'NORDBOOKING'); ?></label>
                                    <textarea name="bf_success_message" id="bf_success_message" class="form-textarea" rows="3"><?php echo nordbooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details.'); ?></textarea>
                                    <p class="description"><?php esc_html_e('Message shown to customers after successful form submission.', 'NORDBOOKING'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Control Tab -->
                    <div id="form-control" class="NORDBOOKING-settings-tab-pane" role="tabpanel">
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Form Availability', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_form_enabled" type="checkbox" id="bf_form_enabled" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_form_enabled', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_form_enabled" class="toggle-label"><?php esc_html_e('Enable Booking Form', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('When disabled, the form will show a maintenance message instead of allowing bookings.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group" id="maintenance-message-group" style="<?php echo nordbooking_is_setting_checked($bf_settings, 'bf_form_enabled', true) ? 'display:none;' : ''; ?>">
                                    <label for="bf_maintenance_message"><?php esc_html_e('Maintenance Message', 'NORDBOOKING'); ?></label>
                                    <textarea name="bf_maintenance_message" id="bf_maintenance_message" class="form-textarea" rows="3"><?php echo nordbooking_get_setting_textarea($bf_settings, 'bf_maintenance_message', 'We are temporarily not accepting new bookings. Please check back later or contact us directly.'); ?></textarea>
                                    <p class="description"><?php esc_html_e('This message will be displayed when the booking form is disabled.', 'NORDBOOKING'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Form Fields', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_location_check" type="checkbox" id="bf_enable_location_check" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_location_check', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_location_check" class="toggle-label"><?php esc_html_e('Enable Location Check', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Allow customers to check if you service their area.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_pet_information" type="checkbox" id="bf_enable_pet_information" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_pet_information', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_pet_information" class="toggle-label"><?php esc_html_e('Enable Pet Information', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Show fields for pet-related service information.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_service_frequency" type="checkbox" id="bf_enable_service_frequency" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_service_frequency', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_service_frequency" class="toggle-label"><?php esc_html_e('Enable Service Frequency', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Allow customers to select recurring service options.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_datetime_selection" type="checkbox" id="bf_enable_datetime_selection" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_datetime_selection', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_datetime_selection" class="toggle-label"><?php esc_html_e('Enable Date & Time Selection', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Show calendar and time slot picker.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_property_access" type="checkbox" id="bf_enable_property_access" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_property_access', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_property_access" class="toggle-label"><?php esc_html_e('Enable Property Access Information', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Collect information about property access and special instructions.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Design Settings Tab -->
                    <div id="design" class="NORDBOOKING-settings-tab-pane" role="tabpanel">
                        <div class="NORDBOOKING-design-grid">
                            <div class="NORDBOOKING-design-settings">
                                <div class="NORDBOOKING-card">
                                    <div class="NORDBOOKING-card-header">
                                        <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Form Appearance', 'NORDBOOKING'); ?></h3>
                                    </div>
                                    <div class="NORDBOOKING-card-content">
                                        <div class="form-group">
                                            <label for="bf_header_text"><?php esc_html_e('Header Text', 'NORDBOOKING'); ?></label>
                                            <input name="bf_header_text" type="text" id="bf_header_text" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>" class="form-input">
                                            <p class="description"><?php esc_html_e('The main heading displayed at the top of your booking form.', 'NORDBOOKING'); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label for="bf_description"><?php esc_html_e('Description', 'NORDBOOKING'); ?></label>
                                            <textarea name="bf_description" id="bf_description" class="form-textarea" rows="3"><?php echo nordbooking_get_setting_textarea($bf_settings, 'bf_description'); ?></textarea>
                                            <p class="description"><?php esc_html_e('Optional description text shown below the header.', 'NORDBOOKING'); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label><?php esc_html_e('Progress Indicator Style', 'NORDBOOKING'); ?></label>
                                            <div class="NORDBOOKING-radio-group-cards">
                                                <label>
                                                    <input type="radio" name="bf_progress_display_style" value="bar" <?php checked(nordbooking_get_setting_value($bf_settings, 'bf_progress_display_style', 'bar'), 'bar'); ?>>
                                                    <div class="card-content">
                                                        <span><?php esc_html_e('Progress Bar', 'NORDBOOKING'); ?></span>
                                                    </div>
                                                </label>
                                                <label>
                                                    <input type="radio" name="bf_progress_display_style" value="none" <?php checked(nordbooking_get_setting_value($bf_settings, 'bf_progress_display_style', 'bar'), 'none'); ?>>
                                                    <div class="card-content">
                                                        <span><?php esc_html_e('None', 'NORDBOOKING'); ?></span>
                                                    </div>
                                                </label>
                                            </div>
                                            <p class="description"><?php esc_html_e('Choose how to display the progress indicator on the booking form.', 'NORDBOOKING'); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label><?php esc_html_e('Service Card Display', 'NORDBOOKING'); ?></label>
                                            <div class="NORDBOOKING-radio-group-cards">
                                                <label>
                                                    <input type="radio" name="bf_service_card_display" value="image" <?php checked(nordbooking_get_setting_value($bf_settings, 'bf_service_card_display', 'image'), 'image'); ?>>
                                                    <div class="card-content">
                                                        <span><?php esc_html_e('Show Image', 'NORDBOOKING'); ?></span>
                                                    </div>
                                                </label>
                                                <label>
                                                    <input type="radio" name="bf_service_card_display" value="icon" <?php checked(nordbooking_get_setting_value($bf_settings, 'bf_service_card_display', 'image'), 'icon'); ?>>
                                                    <div class="card-content">
                                                        <span><?php esc_html_e('Show Icon', 'NORDBOOKING'); ?></span>
                                                    </div>
                                                </label>
                                            </div>
                                            <p class="description"><?php esc_html_e('Choose what to display on the service cards in the booking form.', 'NORDBOOKING'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="NORDBOOKING-card">
                                    <div class="NORDBOOKING-card-header">
                                        <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Theme Colors', 'NORDBOOKING'); ?></h3>
                                    </div>
                                    <div class="NORDBOOKING-card-content">
                                        <div class="form-group-grid two-cols">
                                            <div class="form-group">
                                                <label for="bf_theme_color"><?php esc_html_e('Primary Color', 'NORDBOOKING'); ?></label>
                                                <input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="form-input NORDBOOKING-color-picker">
                                                <p class="description"><?php esc_html_e('Main color used for buttons and accents.', 'NORDBOOKING'); ?></p>
                                            </div>
                                            <div class="form-group">
                                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'NORDBOOKING'); ?></label>
                                                <input name="bf_background_color" type="text" id="bf_background_color" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="form-input NORDBOOKING-color-picker">
                                                <p class="description"><?php esc_html_e('Background color of the form.', 'NORDBOOKING'); ?></p>
                                            </div>
                                        </div>
                                        <div class="form-group-grid two-cols">
                                            <div class="form-group">
                                                <label for="bf_text_color"><?php esc_html_e('Text Color', 'NORDBOOKING'); ?></label>
                                                <input name="bf_text_color" type="text" id="bf_text_color" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_text_color', '#333333'); ?>" class="form-input NORDBOOKING-color-picker">
                                                <p class="description"><?php esc_html_e('Primary text color.', 'NORDBOOKING'); ?></p>
                                            </div>
                                            <div class="form-group">
                                                <label for="bf_border_radius"><?php esc_html_e('Border Radius (px)', 'NORDBOOKING'); ?></label>
                                                <input name="bf_border_radius" type="number" id="bf_border_radius" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_border_radius', '8'); ?>" min="0" max="50" class="form-input">
                                                <p class="description"><?php esc_html_e('Roundness of form elements.', 'NORDBOOKING'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="NORDBOOKING-card">
                                    <div class="NORDBOOKING-card-header">
                                        <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Custom CSS', 'NORDBOOKING'); ?></h3>
                                    </div>
                                    <div class="NORDBOOKING-card-content">
                                        <div class="form-group">
                                            <label for="bf_custom_css"><?php esc_html_e('Custom CSS Rules', 'NORDBOOKING'); ?></label>
                                            <textarea name="bf_custom_css" id="bf_custom_css" class="form-textarea code" rows="8" placeholder="<?php esc_attr_e('/* Your custom CSS rules here */', 'NORDBOOKING'); ?>"><?php echo nordbooking_get_setting_textarea($bf_settings, 'bf_custom_css'); ?></textarea>
                                            <p class="description"><strong><?php esc_html_e('Tip:', 'NORDBOOKING'); ?></strong> <?php esc_html_e('Use <code>.NORDBOOKING-form</code> as the main selector to target form elements.', 'NORDBOOKING'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="NORDBOOKING-design-preview">
                                <div class="NORDBOOKING-preview-box">
                                    <h3 class="preview-box-title"><?php esc_html_e('Live Preview', 'NORDBOOKING'); ?></h3>
                                    <div class="NORDBOOKING-form-preview-wrapper">
                                        <div class="NORDBOOKING-form-preview">
                                            <div class="preview-header">
                                             <h2 id="preview-header-text"><?php echo esc_html(nordbooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online')); ?></h2>
                                                <p id="preview-description"><?php echo esc_html(nordbooking_get_setting_value($bf_settings, 'bf_description')); ?></p>
                                            </div>
                                            <div class="preview-progress-wrapper" style="padding: 1rem 0; min-height: 20px;">
                                                <div class="preview-progress-bar" style="height: 12px; background-color: #e5e7eb; border-radius: 6px; overflow: hidden;">
                                                    <div class="preview-progress-fill" style="width: 25%; height: 100%; background-color: var(--preview-primary, #1abc9c);"></div>
                                                </div>
                                            </div>
                                            <div class="preview-form-content">
                                                <div class="preview-service-card">
                                                    <div id="preview-service-card-image" style="display: none;">
                                                        <img src="https://via.placeholder.com/150" alt="Service Image">
                                                    </div>
                                                    <div id="preview-service-card-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8"></path><polygon points="22 12 18 12 20 7 22 12"></polygon></svg>
                                                    </div>
                                                    <div>
                                                        <strong>Sample Service</strong>
                                                        <p>A brief description.</p>
                                                    </div>
                                                </div>
                                                <div class="preview-form-group">
                                                     <label><?php esc_html_e('Sample Input', 'NORDBOOKING'); ?></label>
                                                     <input type="text" readonly>
                                                </div>
                                                 <div class="preview-form-group">
                                                     <label><?php esc_html_e('Sample Select', 'NORDBOOKING'); ?></label>
                                                     <select readonly><option><?php esc_html_e('Option 1', 'NORDBOOKING'); ?></option></select>
                                                 </div>
                                                <div class="preview-form-group">
                                                    <button type="button" class="preview-button"><?php esc_html_e('Continue', 'NORDBOOKING'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div id="advanced" class="NORDBOOKING-settings-tab-pane" role="tabpanel">
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Booking Logic', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group-grid two-cols">
                                    <div class="form-group">
                                        <label for="bf_allow_cancellation_hours"><?php esc_html_e('Cancellation Lead Time (Hours)', 'NORDBOOKING'); ?></label>
                                        <input name="bf_allow_cancellation_hours" type="number" id="bf_allow_cancellation_hours" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_allow_cancellation_hours', '24'); ?>" min="0" class="form-input">
                                        <p class="description"><?php esc_html_e('Minimum hours before booking a customer can cancel.', 'NORDBOOKING'); ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="bf_booking_advance_days"><?php esc_html_e('Advance Booking Days', 'NORDBOOKING'); ?></label>
                                        <input name="bf_booking_advance_days" type="number" id="bf_booking_advance_days" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_booking_advance_days', '30'); ?>" min="1" max="365" class="form-input">
                                        <p class="description"><?php esc_html_e('How many days in advance customers can book.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group-grid two-cols">
                                    <div class="form-group">
                                        <label for="bf_min_booking_notice"><?php esc_html_e('Minimum Booking Notice (Hours)', 'NORDBOOKING'); ?></label>
                                        <input name="bf_min_booking_notice" type="number" id="bf_min_booking_notice" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_min_booking_notice', '24'); ?>" min="0" class="form-input">
                                        <p class="description"><?php esc_html_e('Minimum hours notice required for new bookings.', 'NORDBOOKING'); ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="bf_time_slot_duration"><?php esc_html_e('Time Slot Duration (Minutes)', 'NORDBOOKING'); ?></label>
                                        <select name="bf_time_slot_duration" id="bf_time_slot_duration" class="form-select">
                                            <option value="15" <?php selected(nordbooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '15'); ?>>15 <?php esc_html_e('minutes', 'NORDBOOKING'); ?></option>
                                            <option value="30" <?php selected(nordbooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '30'); ?>>30 <?php esc_html_e('minutes', 'NORDBOOKING'); ?></option>
                                            <option value="60" <?php selected(nordbooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '60'); ?>>1 <?php esc_html_e('hour', 'NORDBOOKING'); ?></option>
                                            <option value="120" <?php selected(nordbooking_get_setting_value($bf_settings, 'bf_time_slot_duration', '60'), '120'); ?>>2 <?php esc_html_e('hours', 'NORDBOOKING'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Duration of each available time slot.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Notification Settings', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group">
                                    <label for="bf_admin_email"><?php esc_html_e('Admin Notification Email', 'NORDBOOKING'); ?></label>
                                    <input name="bf_admin_email" type="email" id="bf_admin_email" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_admin_email', get_option('admin_email')); ?>" class="form-input">
                                    <p class="description"><?php esc_html_e('Email address to receive new booking notifications.', 'NORDBOOKING'); ?></p>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_send_customer_confirmation" type="checkbox" id="bf_send_customer_confirmation" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_send_customer_confirmation', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_send_customer_confirmation" class="toggle-label"><?php esc_html_e('Send Customer Confirmation Emails', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Automatically send confirmation emails to customers after booking.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_send_admin_notification" type="checkbox" id="bf_send_admin_notification" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_send_admin_notification', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_send_admin_notification" class="toggle-label"><?php esc_html_e('Send Admin Notification Emails', 'NORDBOOKING'); ?></label>
                                        <p class="description"><?php esc_html_e('Receive email notifications when new bookings are made.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Share & Embed Tab -->
                    <div id="share-embed" class="NORDBOOKING-settings-tab-pane" role="tabpanel">
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Public Booking Link', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group">
                                    <label for="bf_business_slug"><?php esc_html_e('Business Slug', 'NORDBOOKING'); ?></label>
                                    <input name="bf_business_slug" type="text" id="bf_business_slug" value="<?php echo esc_attr($current_slug); ?>" class="form-input" pattern="[a-z0-9-]+" title="<?php esc_attr_e('Only lowercase letters, numbers, and hyphens allowed', 'NORDBOOKING'); ?>">
                                    <p class="description">
                                        <?php esc_html_e('Unique slug for your public booking page URL (e.g., your-business-name).', 'NORDBOOKING'); ?>
                                        <br>
                                        <?php esc_html_e('Changing this will change your public booking form URL. Only use lowercase letters, numbers, and hyphens.', 'NORDBOOKING'); ?>
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label for="NORDBOOKING-public-link"><?php esc_html_e('Public Link', 'NORDBOOKING'); ?></label>
                                    <div class="input-group">
                                        <input type="url" id="NORDBOOKING-public-link" value="<?php echo esc_url($public_booking_url); ?>" class="form-input" readonly>
                                        <button type="button" class="btn btn-secondary" id="NORDBOOKING-copy-public-link-btn"><?php esc_html_e('Copy', 'NORDBOOKING'); ?></button>
                                    </div>
                                    <p class="description"><?php esc_html_e('Share this link with customers so they can book your services directly.', 'NORDBOOKING'); ?></p>
                                </div>
                                <?php if (!empty($public_booking_url)): ?>
                                <div class="form-group">
                                    <label><?php esc_html_e('QR Code', 'NORDBOOKING'); ?></label>
                                    <div class="qr-code-container">
                                        <img id="qr-code-image" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($public_booking_url); ?>" alt="<?php esc_attr_e('Booking Form QR Code', 'NORDBOOKING'); ?>" style="max-width: 200px; height: auto;">
                                        <br>
                                        <button type="button" id="download-qr-btn-embed" class="btn btn-secondary btn-sm" style="margin-top: 10px;"><?php esc_html_e('Download QR Code', 'NORDBOOKING'); ?></button>
                                    </div>
                                    <p class="description"><?php esc_html_e('Print this QR code on business cards, flyers, or display it at your location.', 'NORDBOOKING'); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="NORDBOOKING-card">
                            <div class="NORDBOOKING-card-header">
                                <h3 class="NORDBOOKING-card-title"><?php esc_html_e('Embed Code', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="NORDBOOKING-card-content">
                                <div class="form-group">
                                    <label for="NORDBOOKING-embed-code"><?php esc_html_e('Embed Code', 'NORDBOOKING'); ?></label>
                                    <textarea id="NORDBOOKING-embed-code" class="form-textarea code" rows="4" readonly><?php if (!empty($public_booking_url)): ?><iframe src="<?php echo esc_url($public_booking_url); ?>" width="100%" height="600" frameborder="0" scrolling="auto"></iframe><?php endif; ?></textarea>
                                    <div class="button-group" style="margin-top: 0.75rem;">
                                        <button type="button" class="btn btn-secondary" id="NORDBOOKING-copy-embed-code-btn"><?php esc_html_e('Copy Embed Code', 'NORDBOOKING'); ?></button>
                                    </div>
                                    <p class="description"><?php esc_html_e('Use this code to embed the booking form directly into your website or blog.', 'NORDBOOKING'); ?></p>
                                </div>
                                <div class="form-group-grid two-cols">
                                    <div class="form-group">
                                        <label for="bf_embed_width"><?php esc_html_e('Embed Width', 'NORDBOOKING'); ?></label>
                                        <input name="bf_embed_width" type="text" id="bf_embed_width" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_embed_width', '100%'); ?>" class="form-input">
                                        <p class="description"><?php esc_html_e('Width of embedded form (e.g., 100%, 600px).', 'NORDBOOKING'); ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="bf_embed_height"><?php esc_html_e('Embed Height', 'NORDBOOKING'); ?></label>
                                        <input name="bf_embed_height" type="text" id="bf_embed_height" value="<?php echo nordbooking_get_setting_value($bf_settings, 'bf_embed_height', '600px'); ?>" class="form-input">
                                        <p class="description"><?php esc_html_e('Height of embedded form (e.g., 600px, 80vh).', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div><!-- /.NORDBOOKING-settings-main -->
    </div><!-- /.NORDBOOKING-settings-grid -->
</div>

<!-- Success/Error Messages Container -->
<div id="NORDBOOKING-settings-feedback" class="notice" style="display:none;"></div>

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

/* Tab icons */
.NORDBOOKING-tab-item svg {
    width: 16px;
    height: 16px;
    margin-right: 8px;
    vertical-align: middle;
    display: inline-block;
    opacity: 0.7;
    transition: opacity 0.2s ease-in-out;
}
.NORDBOOKING-tab-item:hover svg,
.NORDBOOKING-tab-item.active svg {
    opacity: 1;
}
.NORDBOOKING-tab-item span {
    vertical-align: middle;
    display: inline-block;
}

.NORDBOOKING-settings-grid {
    display: grid;
    grid-template-columns: 1fr; /* Default to single column */
    gap: 2rem;
}

.NORDBOOKING-design-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

@media (min-width: 1024px) {
    .NORDBOOKING-design-grid {
        grid-template-columns: 1fr 400px;
    }
}

.NORDBOOKING-design-preview {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.NORDBOOKING-preview-box {
    background-color: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius-lg);
}

.preview-box-title {
    font-size: 1.125rem;
    font-weight: 600;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.NORDBOOKING-form-preview-wrapper {
    padding: 1.5rem;
}

.NORDBOOKING-form-preview {
    background-color: var(--preview-bg, #ffffff);
    color: var(--preview-text, #333333);
    border-radius: var(--preview-radius, 8px);
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.preview-header {
    text-align: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--preview-border, #e5e7eb);
}

.preview-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--preview-primary, #1abc9c);
    margin-bottom: 0.5rem;
    word-wrap: break-word;
}

.preview-header p {
    font-size: 0.9rem;
    word-wrap: break-word;
}


.preview-progress-bar {
    width: 100%;
    height: 8px;
    background-color: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.preview-progress-fill {
    width: 25%;
    height: 100%;
    background-color: var(--preview-primary, #1abc9c);
    transition: background-color 0.3s ease;
}

.preview-form-group {
    margin-bottom: 1rem;
}

.preview-form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.preview-form-group input,
.preview-button {
    width: 100%;
    padding: 0.65rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: var(--preview-radius, 8px);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.preview-form-group input {
    background-color: #f9fafb;
}

.preview-button {
    border: none;
    color: #fff;
    background-color: var(--preview-primary, #1abc9c);
    font-weight: 600;
    cursor: not-allowed;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-group-grid.two-cols {
        grid-template-columns: 1fr;
    }
    
    .NORDBOOKING-settings-tabs {
        flex-wrap: wrap;
        gap: 0.125rem;
    }
    
    .NORDBOOKING-tab-item {
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressStyleRadios = document.querySelectorAll('input[name="bf_progress_display_style"]');
    const previewWrapper = document.querySelector('.preview-progress-wrapper');

    function updatePreview(style) {
        if (!previewWrapper) return;

        if (style === 'none') {
            previewWrapper.style.display = 'none';
        } else {
            previewWrapper.style.display = 'block';
        }
    }

    progressStyleRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            updatePreview(this.value);
        });
    });

    // Initial update on page load
    const initialStyleEl = document.querySelector('input[name="bf_progress_display_style"]:checked');
    if (initialStyleEl) {
        updatePreview(initialStyleEl.value);
    }
});
</script>