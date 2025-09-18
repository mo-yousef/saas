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
<div id="nordbooking-booking-form-settings-page" class="wrap NORDBOOKING-settings-page">
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('booking_form'); ?>
            </span>
            <div class="heading-wrapper">
                <h1><?php esc_html_e('Booking Form Settings', 'NORDBOOKING'); ?></h1>
                <p class="dashboard-subtitle"><?php esc_html_e('Customize the appearance and behavior of your public booking form.', 'NORDBOOKING'); ?></p>
            </div>
        </div>
        <div class="nordbooking-page-header-actions">
            <button type="submit" form="nordbooking-booking-form-settings-form" name="save_booking_form_settings" id="NORDBOOKING-save-bf-settings-btn" class="btn btn-primary"><?php esc_html_e('Save Changes', 'NORDBOOKING'); ?></button>
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
            <form id="nordbooking-booking-form-settings-form" method="post" class="NORDBOOKING-settings-form">
                <?php wp_nonce_field('nordbooking_dashboard_nonce', 'nordbooking_dashboard_nonce_field'); ?>

                <!-- Tab Navigation -->
                <div class="NORDBOOKING-settings-tabs" role="tablist" aria-label="<?php esc_attr_e('Booking Form Settings', 'NORDBOOKING'); ?>">
                    <a href="#general" class="NORDBOOKING-tab-item active" data-tab="general" role="tab">
                        <?php echo nordbooking_get_booking_form_tab_icon('cog'); ?>
                        <span><?php esc_html_e('General', 'NORDBOOKING'); ?></span>
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
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                                        <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.579 9.96512 20.2579 9.77251 19.9887C9.5799 19.7194 9.31074 19.5143 9 19.4C8.69838 19.2669 8.36381 19.2272 8.03941 19.286C7.71502 19.3448 7.41568 19.4995 7.18 19.73L7.12 19.79C6.93425 19.976 6.71368 20.1235 6.47088 20.2241C6.22808 20.3248 5.96783 20.3766 5.705 20.3766C5.44217 20.3766 5.18192 20.3248 4.93912 20.2241C4.69632 20.1235 4.47575 19.976 4.29 19.79C4.10405 19.6043 3.95653 19.3837 3.85588 19.1409C3.75523 18.8981 3.70343 18.6378 3.70343 18.375C3.70343 18.1122 3.75523 17.8519 3.85588 17.6091C3.95653 17.3663 4.10405 17.1457 4.29 16.96L4.35 16.9C4.58054 16.6643 4.73519 16.365 4.794 16.0406C4.85282 15.7162 4.81312 15.3816 4.68 15.08C4.55324 14.7842 4.34276 14.532 4.07447 14.3543C3.80618 14.1766 3.49179 14.0813 3.17 14.08H3C2.46957 14.08 1.96086 13.8693 1.58579 13.4942C1.21071 13.1191 1 12.6104 1 12.08C1 11.5496 1.21071 11.0409 1.58579 10.6658C1.96086 10.2907 2.46957 10.08 3 10.08H3.09C3.42099 10.0723 3.742 9.96512 4.01127 9.77251C4.28054 9.5799 4.48571 9.31074 4.6 9C4.73312 8.69838 4.77282 8.36381 4.714 8.03941C4.65519 7.71502 4.50054 7.41568 4.27 7.18L4.21 7.12C4.02405 6.93425 3.87653 6.71368 3.77588 6.47088C3.67523 6.22808 3.62343 5.96783 3.62343 5.705C3.62343 5.44217 3.67523 5.18192 3.77588 4.93912C3.87653 4.69632 4.02405 4.47575 4.21 4.29C4.39575 4.10405 4.61632 3.95653 4.85912 3.85588C5.10192 3.75523 5.36217 3.70343 5.625 3.70343C5.88783 3.70343 6.14808 3.75523 6.39088 3.85588C6.63368 3.95653 6.85425 4.10405 7.04 4.29L7.1 4.35C7.33568 4.58054 7.63502 4.73519 7.95941 4.794C8.28381 4.85282 8.61838 4.81312 8.92 4.68H9C9.29577 4.55324 9.54802 4.34276 9.72569 4.07447C9.90337 3.80618 9.99872 3.49179 10 3.17V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e('Basic Information', 'NORDBOOKING'); ?>
                                </h3>
                            </div>
                            <div class="nordbooking-card-content">
                                <div class="form-group">
                                    <label for="bf_success_message"><?php esc_html_e('Success Message', 'NORDBOOKING'); ?></label>
                                    <textarea name="bf_success_message" id="bf_success_message" class="form-textarea" rows="3"><?php echo nordbooking_get_setting_textarea($bf_settings, 'bf_success_message', 'Thank you for your booking! We will contact you soon to confirm the details.'); ?></textarea>
                                    <p class="description"><?php esc_html_e('Message shown to customers after successful form submission.', 'NORDBOOKING'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Availability Controls -->
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                                        <path d="M16 4H18C18.5304 4 19.0391 4.21071 19.4142 4.58579C19.7893 4.96086 20 5.46957 20 6V20C20 20.5304 19.7893 21.0391 19.4142 21.4142C19.0391 21.7893 18.5304 22 18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V6C4 5.46957 4.21071 4.96086 4.58579 4.58579C4.96086 4.21071 5.46957 4 6 4H8M16 4C16 3.46957 15.7893 2.96086 15.4142 2.58579C15.0391 2.21071 14.5304 2 14 2H10C9.46957 2 8.96086 2.21071 8.58579 2.58579C8.21071 2.96086 8 3.46957 8 4M16 4C16 4.53043 15.7893 5.03914 15.4142 5.41421C15.0391 5.78929 14.5304 6 14 6H10C9.46957 6 8.96086 5.78929 8.58579 5.41421C8.21071 5.03914 8 4.53043 8 4M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e('Form Availability', 'NORDBOOKING'); ?>
                                </h3>
                            </div>
                            <div class="nordbooking-card-content">
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

                        <!-- Form Fields Controls -->
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e('Form Fields', 'NORDBOOKING'); ?>
                                </h3>
                            </div>
                            <div class="nordbooking-card-content">
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_location_check" type="checkbox" id="bf_enable_location_check" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_location_check', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_location_check" class="toggle-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
                                                <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.3639 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php esc_html_e('Enable Location Check', 'NORDBOOKING'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Allow customers to check if you service their area.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_pet_information" type="checkbox" id="bf_enable_pet_information" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_pet_information', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_pet_information" class="toggle-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
                                                <path d="M11.25 16.25C11.25 17.49 10.24 18.5 9 18.5C7.76 18.5 6.75 17.49 6.75 16.25C6.75 15.01 7.76 14 9 14C10.24 14 11.25 15.01 11.25 16.25ZM17.25 16.25C17.25 17.49 16.24 18.5 15 18.5C13.76 18.5 12.75 17.49 12.75 16.25C12.75 15.01 13.76 14 15 14C16.24 14 17.25 15.01 17.25 16.25ZM12 21.5C16.5 21.5 20.5 18.5 20.5 14C20.5 9.5 16.5 6.5 12 6.5C7.5 6.5 3.5 9.5 3.5 14C3.5 18.5 7.5 21.5 12 21.5ZM8.5 10.5C9.33 10.5 10 9.83 10 9C10 8.17 9.33 7.5 8.5 7.5C7.67 7.5 7 8.17 7 9C7 9.83 7.67 10.5 8.5 10.5ZM15.5 10.5C16.33 10.5 17 9.83 17 9C17 8.17 16.33 7.5 15.5 7.5C14.67 7.5 14 8.17 14 9C14 9.83 14.67 10.5 15.5 10.5Z" fill="currentColor"/>
                                            </svg>
                                            <?php esc_html_e('Enable Pet Information', 'NORDBOOKING'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Show fields for pet-related service information.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_service_frequency" type="checkbox" id="bf_enable_service_frequency" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_service_frequency', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_service_frequency" class="toggle-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
                                                <path d="M23 12C23 18.0751 18.0751 23 12 23C5.92487 23 1 18.0751 1 12C1 5.92487 5.92487 1 12 1C18.0751 1 23 5.92487 23 12Z" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M12 5V12L16 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php esc_html_e('Enable Service Frequency', 'NORDBOOKING'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Allow customers to select recurring service options.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_datetime_selection" type="checkbox" id="bf_enable_datetime_selection" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_datetime_selection', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_datetime_selection" class="toggle-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
                                                <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M15.6947 13.7002H15.7037M11.9955 13.7002H12.0045M8.29431 13.7002H8.30329" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php esc_html_e('Enable Date & Time Selection', 'NORDBOOKING'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Show calendar and time slot picker.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                                <div class="form-group form-group-toggle">
                                    <label class="NORDBOOKING-toggle-switch">
                                        <input name="bf_enable_property_access" type="checkbox" id="bf_enable_property_access" value="1" <?php echo nordbooking_is_setting_checked($bf_settings, 'bf_enable_property_access', true); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label-group">
                                        <label for="bf_enable_property_access" class="toggle-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
                                                <path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7M3 7L12 13L21 7M3 7V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php esc_html_e('Enable Property Access Information', 'NORDBOOKING'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Collect information about property access and special instructions.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Control Tab removed - content moved to General tab -->

                    <!-- Design Settings Tab -->
                    <div id="design" class="NORDBOOKING-settings-tab-pane" role="tabpanel">
                        <div class="NORDBOOKING-design-grid">
                            <div class="NORDBOOKING-design-settings">
                                <div class="nordbooking-card">
                                    <div class="nordbooking-card-header">
                                        <h3 class="nordbooking-card-title"><?php esc_html_e('Form Appearance', 'NORDBOOKING'); ?></h3>
                                    </div>
                                    <div class="nordbooking-card-content">
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
                                <div class="nordbooking-card">
                                    <div class="nordbooking-card-header">
                                        <h3 class="nordbooking-card-title"><?php esc_html_e('Theme Colors', 'NORDBOOKING'); ?></h3>
                                    </div>
                                    <div class="nordbooking-card-content">
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
                                <div class="nordbooking-card">
                                    <div class="nordbooking-card-header">
                                        <h3 class="nordbooking-card-title"><?php esc_html_e('Custom CSS', 'NORDBOOKING'); ?></h3>
                                    </div>
                                    <div class="nordbooking-card-content">
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
                                        <!-- Preview Header -->
                                        <div class="preview-form-header">
                                            <h2 id="preview-header-text"><?php echo esc_html(nordbooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online')); ?></h2>
                                            <p id="preview-description"><?php echo esc_html(nordbooking_get_setting_value($bf_settings, 'bf_description', 'Complete the steps below to schedule your service')); ?></p>
                                        </div>

                                        <!-- Preview Form Container -->
                                        <div class="NORDBOOKING-form-preview">
                                            <!-- Progress Bar -->
                                            <div class="preview-progress-container" id="preview-progress-wrapper">
                                                <div class="preview-progress-bar">
                                                    <div class="preview-progress-fill"></div>
                                                </div>
                                            </div>

                                            <!-- Form Card -->
                                            <div class="preview-form-card">
                                                <!-- Step Title -->
                                                <div class="preview-step-header">
                                                    <div class="preview-step-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M20 12V8a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8"></path>
                                                            <polygon points="22 12 18 12 20 7 22 12"></polygon>
                                                        </svg>
                                                    </div>
                                                    <h3><?php esc_html_e('Select service', 'NORDBOOKING'); ?></h3>
                                                </div>

                                                <!-- Service Selection -->
                                                <div class="preview-services-grid">
                                                    <div class="preview-service-card selected">
                                                        <div class="preview-service-content">
                                                            <div id="preview-service-card-image" style="display: none;">
                                                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'%3E%3Crect width='48' height='48' fill='%23f0f0f0' rx='8'/%3E%3Ctext x='24' y='24' text-anchor='middle' dy='.3em' fill='%23999' font-family='Arial, sans-serif' font-size='10'%3E🏠%3C/text%3E%3C/svg%3E" alt="Service">
                                                            </div>
                                                            <div id="preview-service-card-icon" class="preview-service-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M20 12V8a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8"></path>
                                                                    <polygon points="22 12 18 12 20 7 22 12"></polygon>
                                                                </svg>
                                                            </div>
                                                            <div class="preview-service-info">
                                                                <h4><?php esc_html_e('Standard Cleaning', 'NORDBOOKING'); ?></h4>
                                                                <p><?php esc_html_e('Professional home cleaning service', 'NORDBOOKING'); ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="preview-service-check">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="20,6 9,17 4,12"></polyline>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Navigation Buttons -->
                                                <div class="preview-button-group">
                                                    <button type="button" class="preview-btn preview-btn-secondary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <line x1="19" y1="12" x2="5" y2="12"></line>
                                                            <polyline points="12,19 5,12 12,5"></polyline>
                                                        </svg>
                                                    </button>
                                                    <button type="button" class="preview-btn preview-btn-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                                            <polyline points="12,5 19,12 12,19"></polyline>
                                                        </svg>
                                                    </button>
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
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title"><?php esc_html_e('Booking Logic', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="nordbooking-card-content">
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
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title"><?php esc_html_e('Notification Settings', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="nordbooking-card-content">
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
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title"><?php esc_html_e('Public Booking Link', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="nordbooking-card-content">
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
                        <div class="nordbooking-card">
                            <div class="nordbooking-card-header">
                                <h3 class="nordbooking-card-title"><?php esc_html_e('Embed Code', 'NORDBOOKING'); ?></h3>
                            </div>
                            <div class="nordbooking-card-content">
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
