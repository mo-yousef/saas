<?php
/**
 * Redesigned Dashboard Page: Settings
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings_manager = new \NORDBOOKING\Classes\Settings();
$user_id = get_current_user_id();
$update_message = '';

// Handle form submission for non-AJAX fallback
if ( isset( $_POST['save_business_settings'] ) ) {
    // Verify nonce
    if ( isset( $_POST['nordbooking_dashboard_nonce_field'] ) && wp_verify_nonce( $_POST['nordbooking_dashboard_nonce_field'], 'nordbooking_dashboard_nonce' ) ) {

        // Handle personal details (user meta) separately
        $personal_details_updated = true;
        if ( isset( $_POST['first_name'] ) ) {
            $first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
            $personal_details_updated = $personal_details_updated && update_user_meta( $user_id, 'first_name', $first_name );
        }
        if ( isset( $_POST['last_name'] ) ) {
            $last_name = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
            $personal_details_updated = $personal_details_updated && update_user_meta( $user_id, 'last_name', $last_name );
        }

        // Sanitize and prepare settings data from POST
        $settings_to_save = [];
        // A list of expected setting keys and their sanitization functions
        $allowed_settings = [
            'biz_name' => 'sanitize_text_field',
            'biz_email' => 'sanitize_email',
            'biz_phone' => 'sanitize_text_field',
            'biz_address' => 'sanitize_textarea_field',
            'biz_currency_code' => 'sanitize_text_field',
            'biz_user_language' => 'sanitize_text_field',
            'biz_logo_url' => 'esc_url_raw',
            'bf_theme_color' => 'sanitize_hex_color',
            'bf_secondary_color' => 'sanitize_hex_color',
            'bf_background_color' => 'sanitize_hex_color',
            // Note: Email templates are complex and saved via AJAX in the new design.
            // This fallback handles the main settings.
        ];

        foreach ( $allowed_settings as $key => $sanitizer ) {
            if ( isset( $_POST[$key] ) ) {
                $settings_to_save[$key] = call_user_func( $sanitizer, wp_unslash($_POST[$key]) );
            }
        }

        // Save the settings
        $result = $settings_manager->save_business_settings( $user_id, $settings_to_save ) && $personal_details_updated;

        if ( $result ) {
            $update_message = '<div class="notice notice-success is-dismissible" style="margin-bottom: 15px;"><p>' . esc_html__( 'Settings saved successfully.', 'NORDBOOKING' ) . '</p></div>';
        } else {
            $update_message = '<div class="notice notice-error is-dismissible" style="margin-bottom: 15px;"><p>' . esc_html__( 'There was an error saving the settings.', 'NORDBOOKING' ) . '</p></div>';
        }

    } else {
        // Nonce verification failed
        $update_message = '<div class="notice notice-error is-dismissible" style="margin-bottom: 15px;"><p>' . esc_html__( 'Security check failed. Please try again.', 'NORDBOOKING' ) . '</p></div>';
    }
}

// Fetch settings for display (re-fetch in case they were just updated)
$biz_settings = $settings_manager->get_business_settings($user_id);
$bf_settings = $settings_manager->get_booking_form_settings($user_id);

// Helper functions
function nordbooking_get_biz_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function nordbooking_get_biz_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function nordbooking_select_biz_setting_value($settings, $key, $value, $default_value = '') {
    $current_val = isset($settings[$key]) ? $settings[$key] : $default_value;
    return selected($value, $current_val, false);
}
?>

<!-- Notification Container for AJAX responses -->
<div id="nordbooking-notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000;"></div>

<div id="NORDBOOKING-business-settings-page" class="wrap NORDBOOKING-settings-page">
    <?php
    // Display any update messages
    if ( ! empty( $update_message ) ) {
        echo $update_message;
    }
    ?>
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('settings'); ?>
            </span>
            <?php
            $biz_name = nordbooking_get_biz_setting_value($biz_settings, 'biz_name');
            $page_title = !empty($biz_name) ? sprintf(esc_html__('%s Settings', 'NORDBOOKING'), esc_html($biz_name)) : esc_html__('Business Settings', 'NORDBOOKING');
            ?>
            <h1><?php echo $page_title; ?></h1>
        </div>
        <div class="nordbooking-page-header-actions">
            <button type="submit" form="NORDBOOKING-business-settings-form" name="save_business_settings" id="NORDBOOKING-save-biz-settings-btn" class="btn btn-primary"><?php esc_html_e('Save All Settings', 'NORDBOOKING'); ?></button>
        </div>
    </div>

    <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General', 'NORDBOOKING'); ?></a>
        <a href="#branding" class="nav-tab" data-tab="branding"><?php esc_html_e('Branding', 'NORDBOOKING'); ?></a>
        <a href="#email-notifications" class="nav-tab" data-tab="email-notifications"><?php esc_html_e('Email Notifications', 'NORDBOOKING'); ?></a>
    </h2>

    <form id="NORDBOOKING-business-settings-form" method="post">
        <?php wp_nonce_field('nordbooking_dashboard_nonce', 'nordbooking_dashboard_nonce_field'); ?>
        <div id="NORDBOOKING-settings-feedback" style="margin-bottom:15px; margin-top:10px;"></div>

        <div id="general-tab" class="settings-tab-content">
            <p class="page-description"><?php esc_html_e('Manage your personal and business information.', 'NORDBOOKING'); ?></p>
            <div class="settings-layout">
                <!-- Left Column -->
                <div class="settings-column">
                    <!-- Personal Details Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Personal Details', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <?php 
                            $current_user = wp_get_current_user();
                            $user_meta = get_user_meta($user_id);
                            $first_name = isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '';
                            $last_name = isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '';
                            ?>
                            <div class="form-group">
                                <label for="first_name"><?php esc_html_e('First Name', 'NORDBOOKING'); ?></label>
                                <input name="first_name" type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="last_name"><?php esc_html_e('Last Name', 'NORDBOOKING'); ?></label>
                                <input name="last_name" type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="primary_email"><?php esc_html_e('Primary Email', 'NORDBOOKING'); ?></label>
                                <input type="email" id="primary_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="regular-text" readonly>
                                <p class="description"><?php esc_html_e('This is your account email used during registration and cannot be changed.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Details Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Business Details', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="biz_name"><?php esc_html_e('Business Name', 'NORDBOOKING'); ?></label>
                                <input name="biz_name" type="text" id="biz_name" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('The public name of your business. This will appear on invoices and billing.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_email"><?php esc_html_e('Business Email', 'NORDBOOKING'); ?></label>
                                <input name="biz_email" type="email" id="biz_email" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('Email address for customer communication and business correspondence.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_phone"><?php esc_html_e('Business Phone', 'NORDBOOKING'); ?></label>
                                <input name="biz_phone" type="tel" id="biz_phone" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="biz_address"><?php esc_html_e('Business Address', 'NORDBOOKING'); ?></label>
                                <textarea name="biz_address" id="biz_address" class="large-text" rows="4"><?php echo nordbooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                                <p class="description"><?php esc_html_e('Your primary business location. This will appear on invoices and billing.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                 <!-- Right Column -->
                <div class="settings-column">
                    <!-- Localization Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Localization', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="biz_currency_code"><?php esc_html_e('Currency', 'NORDBOOKING'); ?></label>
                                <select name="biz_currency_code" id="biz_currency_code" class="regular-text">
                                    <option value="USD" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'USD', 'USD'); ?>>USD (US Dollar)</option>
                                    <option value="EUR" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'EUR'); ?>>EUR (Euro)</option>
                                    <option value="GBP" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'GBP'); ?>>GBP (British Pound)</option>
                                    <option value="CAD" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'CAD'); ?>>CAD (Canadian Dollar)</option>
                                    <option value="AUD" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'AUD'); ?>>AUD (Australian Dollar)</option>
                                </select>
                                <p class="description"><?php esc_html_e('Select your currency for pricing display.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_user_language"><?php esc_html_e('Language', 'NORDBOOKING'); ?></label>
                                <select name="biz_user_language" id="biz_user_language" class="regular-text">
                                    <option value="en_US" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'en_US', 'en_US'); ?>>English (US)</option>
                                    <option value="sv_SE" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'sv_SE'); ?>>Swedish</option>
                                    <option value="nb_NO" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'nb_NO'); ?>>Norwegian</option>
                                    <option value="da_DK" <?php echo nordbooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'da_DK'); ?>>Danish</option>
                                </select>
                                 <p class="description"><?php esc_html_e("Select the language for the dashboard and booking form.", 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional tabs would go here (branding, email-notifications) -->
        <div id="branding-tab" class="settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Branding Settings', 'NORDBOOKING'); ?></h3>
            <p><?php esc_html_e('Customize the appearance and branding of your booking forms.', 'NORDBOOKING'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Company Logo', 'NORDBOOKING'); ?></th>
                    <td>
                        <div class="logo-upload-section">
                            <div class="logo-preview">
                                <div class="logo-placeholder">
                                    <span><?php esc_html_e('No Logo', 'NORDBOOKING'); ?></span>
                                </div>
                            </div>
                            
                            <div class="logo-upload-controls" style="margin-top: 10px;">
                                <button type="button" id="NORDBOOKING-upload-logo-btn" class="button">
                                    <?php esc_html_e('Upload Logo', 'NORDBOOKING'); ?>
                                </button>
                                <button type="button" id="NORDBOOKING-remove-logo-btn" class="button" style="display: none;">
                                    <?php esc_html_e('Remove Logo', 'NORDBOOKING'); ?>
                                </button>
                                <input type="file" id="NORDBOOKING-logo-file-input" accept="image/*" style="display: none;" />
                                <input type="hidden" id="biz_logo_url" name="biz_logo_url" value="" />
                            </div>
                            
                            <div class="progress-bar-wrapper" style="display: none; margin-top: 10px;">
                                <div class="progress-bar" style="width: 0%; height: 20px; background: #0073aa; border-radius: 3px;"></div>
                            </div>
                            
                            <p class="description">
                                <?php esc_html_e('Upload your company logo. Recommended size: 200x80 pixels. Maximum file size: 5MB.', 'NORDBOOKING'); ?>
                            </p>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Primary Color', 'NORDBOOKING'); ?></th>
                    <td>
                        <input type="text" id="bf_theme_color" name="bf_theme_color" 
                               class="NORDBOOKING-color-picker" value="#1abc9c" />
                        <p class="description"><?php esc_html_e('The main color used in your booking forms.', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Secondary Color', 'NORDBOOKING'); ?></th>
                    <td>
                        <input type="text" id="bf_secondary_color" name="bf_secondary_color" 
                               class="NORDBOOKING-color-picker" value="#34495e" />
                        <p class="description"><?php esc_html_e('Secondary color for accents and highlights.', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Background Color', 'NORDBOOKING'); ?></th>
                    <td>
                        <input type="text" id="bf_background_color" name="bf_background_color" 
                               class="NORDBOOKING-color-picker" value="#ffffff" />
                        <p class="description"><?php esc_html_e('Background color for your booking forms.', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Border Radius', 'NORDBOOKING'); ?></th>
                    <td>
                        <input type="number" id="bf_border_radius" name="bf_border_radius" 
                               value="8" min="0" max="50" class="small-text" />
                        <span><?php esc_html_e('px', 'NORDBOOKING'); ?></span>
                        <p class="description"><?php esc_html_e('Roundness of form elements (0 = square, higher = more rounded).', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Custom CSS', 'NORDBOOKING'); ?></th>
                    <td>
                        <textarea id="bf_custom_css" name="bf_custom_css" rows="8" cols="50" class="large-text code" 
                                  placeholder="/* Add your custom CSS here */"></textarea>
                        <p class="description"><?php esc_html_e('Add custom CSS to further customize your booking forms.', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
            </table>
            
            <style>
                .logo-preview {
                    width: 200px;
                    height: 80px;
                    border: 2px dashed #ddd;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f9f9f9;
                    border-radius: 5px;
                }
                
                .logo-preview img {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }
                
                .logo-placeholder {
                    color: #666;
                    font-style: italic;
                }
                
                .progress-bar-wrapper {
                    width: 200px;
                    height: 20px;
                    background: #f0f0f0;
                    border-radius: 3px;
                    overflow: hidden;
                }
            </style>
        </div>

        <div id="email-notifications-tab" class="settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Email Notification Settings', 'NORDBOOKING'); ?></h3>
            <p><?php esc_html_e('Configure when and where email notifications are sent.', 'NORDBOOKING'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Email From Name', 'NORDBOOKING'); ?></th>
                    <td>
                        <input type="text" id="email_from_name" name="email_from_name" class="regular-text" 
                               placeholder="<?php esc_attr_e('Your Business Name', 'NORDBOOKING'); ?>" />
                        <p class="description"><?php esc_html_e('The name that appears in the "From" field of emails.', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Email From Address', 'NORDBOOKING'); ?></th>
                    <td>
                        <input type="email" id="email_from_address" name="email_from_address" class="regular-text" 
                               placeholder="<?php esc_attr_e('noreply@yourdomain.com', 'NORDBOOKING'); ?>" />
                        <p class="description"><?php esc_html_e('The email address that appears in the "From" field of emails.', 'NORDBOOKING'); ?></p>
                    </td>
                </tr>
            </table>

            <h4><?php esc_html_e('Notification Types', 'NORDBOOKING'); ?></h4>
            
            <?php
            $notification_types = [
                'booking_confirmation_customer' => __('Customer Booking Confirmation', 'NORDBOOKING'),
                'booking_confirmation_admin' => __('Admin Booking Notification', 'NORDBOOKING'),
                'staff_assignment' => __('Staff Assignment Notification', 'NORDBOOKING'),
                'welcome' => __('Welcome Email', 'NORDBOOKING'),
                'invitation' => __('Invitation Email', 'NORDBOOKING')
            ];
            
            foreach ($notification_types as $type => $label): ?>
                <div class="email-notification-item" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <label class="email-toggle-switch" style="margin-right: 15px;">
                            <input type="checkbox" name="email_<?php echo esc_attr($type); ?>_enabled" 
                                   id="email_<?php echo esc_attr($type); ?>_enabled" value="1" />
                            <span class="toggle-slider"></span>
                        </label>
                        <strong><?php echo esc_html($label); ?></strong>
                    </div>
                    
                    <div class="email-recipient-settings" style="margin-left: 20px;">
                        <p style="margin: 5px 0;">
                            <label>
                                <input type="radio" name="email_<?php echo esc_attr($type); ?>_use_primary" value="1" checked />
                                <?php esc_html_e('Use primary business email', 'NORDBOOKING'); ?>
                            </label>
                        </p>
                        <p style="margin: 5px 0;">
                            <label>
                                <input type="radio" name="email_<?php echo esc_attr($type); ?>_use_primary" value="0" />
                                <?php esc_html_e('Use custom email:', 'NORDBOOKING'); ?>
                            </label>
                            <input type="email" name="email_<?php echo esc_attr($type); ?>_recipient" 
                                   class="regular-text custom-email-field" 
                                   placeholder="<?php esc_attr_e('custom@email.com', 'NORDBOOKING'); ?>" 
                                   style="margin-left: 10px; opacity: 0.5;" />
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <style>
                .email-toggle-switch {
                    position: relative;
                    display: inline-block;
                    width: 50px;
                    height: 24px;
                }
                
                .email-toggle-switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                
                .toggle-slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 24px;
                }
                
                .toggle-slider:before {
                    position: absolute;
                    content: "";
                    height: 18px;
                    width: 18px;
                    left: 3px;
                    bottom: 3px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }
                
                .email-toggle-switch input:checked + .toggle-slider {
                    background-color: #2196F3;
                }
                
                .email-toggle-switch input:checked + .toggle-slider:before {
                    transform: translateX(26px);
                }
                
                .email-notification-item.disabled {
                    opacity: 0.6;
                }
            </style>
        </div>

        <!-- Save button -->
        <p class="submit" style="margin-top:20px;">
             <button type="submit" form="NORDBOOKING-business-settings-form" name="save_business_settings" id="NORDBOOKING-save-biz-settings-btn-footer" class="btn btn-primary"><?php esc_html_e('Save All Settings', 'NORDBOOKING'); ?></button>
        </p>
    </form>
</div>

<!-- Enhanced Notification System -->
<script>
// Enhanced showAlert function with better styling
window.showAlert = function(message, type = 'info', duration = 5000) {
    const container = document.getElementById('nordbooking-notification-container');
    if (!container) return;
    
    // Remove any existing notifications of the same type
    const existingNotifications = container.querySelectorAll('.nordbooking-notification');
    existingNotifications.forEach(notification => {
        if (notification.classList.contains('nordbooking-notification-' + type)) {
            notification.remove();
        }
    });
    
    const notification = document.createElement('div');
    notification.className = `nordbooking-notification nordbooking-notification-${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
        color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
        border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
        border-radius: 4px;
        padding: 12px 16px;
        margin-bottom: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        animation: slideInRight 0.3s ease-out;
        position: relative;
        min-width: 300px;
        max-width: 400px;
        word-wrap: break-word;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none; 
                border: none; 
                font-size: 18px; 
                cursor: pointer; 
                margin-left: 10px;
                color: inherit;
                opacity: 0.7;
            ">&times;</button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }
};

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Initialize parameters for the main script
window.nordbooking_biz_settings_params = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
    i18n: {
        saving: '<?php esc_html_e('Saving...', 'NORDBOOKING'); ?>',
        save_success: '<?php esc_html_e('Settings saved successfully.', 'NORDBOOKING'); ?>',
        error_saving: '<?php esc_html_e('Error saving settings.', 'NORDBOOKING'); ?>',
        error_ajax: '<?php esc_html_e('An unexpected error occurred.', 'NORDBOOKING'); ?>'
    }
};
</script>

<!-- Load the main settings JavaScript -->
<script src="<?php echo NORDBOOKING_THEME_URI; ?>assets/js/dashboard-business-settings.js?v=<?php echo NORDBOOKING_VERSION; ?>"></script>