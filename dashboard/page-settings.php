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

    <div class="NORDBOOKING-settings-tabs">
        <div class="settings-tabs-nav">
            <button type="button" class="settings-tab-btn active" data-tab="general">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <?php esc_html_e('General', 'NORDBOOKING'); ?>
            </button>
            <button type="button" class="settings-tab-btn" data-tab="branding">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21,15 16,10 5,21"/>
                </svg>
                <?php esc_html_e('Branding', 'NORDBOOKING'); ?>
            </button>
            <button type="button" class="settings-tab-btn" data-tab="email-notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <?php esc_html_e('Email Notifications', 'NORDBOOKING'); ?>
            </button>
        </div>
    </div>

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

        <!-- Branding Tab -->
        <div id="branding-tab" class="settings-tab-content" style="display:none;">
            <p class="page-description"><?php esc_html_e('Customize the appearance and branding of your booking forms to match your business identity.', 'NORDBOOKING'); ?></p>
            
            <div class="settings-layout">
                <!-- Left Column -->
                <div class="settings-column">
                    <!-- Logo & Visual Identity Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Logo & Visual Identity', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="biz_logo_url"><?php esc_html_e('Company Logo', 'NORDBOOKING'); ?></label>
                                <div class="logo-upload-section">
                                    <div class="logo-preview">
                                        <div class="logo-placeholder">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21,15 16,10 5,21"/>
                                            </svg>
                                            <span><?php esc_html_e('Upload Logo', 'NORDBOOKING'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="logo-upload-controls">
                                        <button type="button" id="NORDBOOKING-upload-logo-btn" class="btn btn-outline btn-sm">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7,10 12,15 17,10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            <?php esc_html_e('Upload Logo', 'NORDBOOKING'); ?>
                                        </button>
                                        <button type="button" id="NORDBOOKING-remove-logo-btn" class="btn btn-outline btn-sm" style="display: none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3,6 5,6 21,6"/>
                                                <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                                            </svg>
                                            <?php esc_html_e('Remove', 'NORDBOOKING'); ?>
                                        </button>
                                        <input type="file" id="NORDBOOKING-logo-file-input" accept="image/*" style="display: none;" />
                                        <input type="hidden" id="biz_logo_url" name="biz_logo_url" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_logo_url'); ?>" />
                                    </div>
                                    
                                    <div class="progress-bar-wrapper" style="display: none;">
                                        <div class="progress-bar"></div>
                                    </div>
                                </div>
                                <p class="description"><?php esc_html_e('Upload your company logo. Recommended size: 200x80 pixels. Maximum file size: 5MB.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Color Scheme Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Color Scheme', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="bf_theme_color"><?php esc_html_e('Primary Color', 'NORDBOOKING'); ?></label>
                                <div class="color-input-wrapper">
                                    <input type="text" id="bf_theme_color" name="bf_theme_color" 
                                           class="NORDBOOKING-color-picker" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" />
                                    <div class="color-preview" style="background-color: <?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>"></div>
                                </div>
                                <p class="description"><?php esc_html_e('The main color used in your booking forms and buttons.', 'NORDBOOKING'); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label for="bf_secondary_color"><?php esc_html_e('Secondary Color', 'NORDBOOKING'); ?></label>
                                <div class="color-input-wrapper">
                                    <input type="text" id="bf_secondary_color" name="bf_secondary_color" 
                                           class="NORDBOOKING-color-picker" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>" />
                                    <div class="color-preview" style="background-color: <?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>"></div>
                                </div>
                                <p class="description"><?php esc_html_e('Secondary color for accents and highlights.', 'NORDBOOKING'); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'NORDBOOKING'); ?></label>
                                <div class="color-input-wrapper">
                                    <input type="text" id="bf_background_color" name="bf_background_color" 
                                           class="NORDBOOKING-color-picker" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" />
                                    <div class="color-preview" style="background-color: <?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>"></div>
                                </div>
                                <p class="description"><?php esc_html_e('Background color for your booking forms.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="settings-column">
                    <!-- Form Styling Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Form Styling', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="bf_border_radius"><?php esc_html_e('Border Radius', 'NORDBOOKING'); ?></label>
                                <div class="input-with-unit">
                                    <input type="number" id="bf_border_radius" name="bf_border_radius" 
                                           value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_border_radius', '8'); ?>" 
                                           min="0" max="50" class="small-text" />
                                    <span class="input-unit">px</span>
                                </div>
                                <p class="description"><?php esc_html_e('Roundness of form elements (0 = square, higher = more rounded).', 'NORDBOOKING'); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label for="bf_font_family"><?php esc_html_e('Font Family', 'NORDBOOKING'); ?></label>
                                <select name="bf_font_family" id="bf_font_family" class="regular-text">
                                    <option value="system-ui" <?php echo nordbooking_select_biz_setting_value($bf_settings, 'bf_font_family', 'system-ui', 'system-ui'); ?>>System Default</option>
                                    <option value="Inter" <?php echo nordbooking_select_biz_setting_value($bf_settings, 'bf_font_family', 'Inter'); ?>>Inter</option>
                                    <option value="Roboto" <?php echo nordbooking_select_biz_setting_value($bf_settings, 'bf_font_family', 'Roboto'); ?>>Roboto</option>
                                    <option value="Open Sans" <?php echo nordbooking_select_biz_setting_value($bf_settings, 'bf_font_family', 'Open Sans'); ?>>Open Sans</option>
                                    <option value="Lato" <?php echo nordbooking_select_biz_setting_value($bf_settings, 'bf_font_family', 'Lato'); ?>>Lato</option>
                                </select>
                                <p class="description"><?php esc_html_e('Choose the font family for your booking forms.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Custom CSS Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Custom CSS', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="bf_custom_css"><?php esc_html_e('Additional CSS', 'NORDBOOKING'); ?></label>
                                <textarea id="bf_custom_css" name="bf_custom_css" rows="8" class="large-text code" 
                                          placeholder="/* Add your custom CSS here */"><?php echo nordbooking_get_biz_setting_textarea($bf_settings, 'bf_custom_css'); ?></textarea>
                                <p class="description"><?php esc_html_e('Add custom CSS to further customize your booking forms. Use with caution.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Notifications Tab -->
        <div id="email-notifications-tab" class="settings-tab-content" style="display:none;">
            <p class="page-description"><?php esc_html_e('Configure email notifications and customize when and where they are sent.', 'NORDBOOKING'); ?></p>
            
            <div class="settings-layout">
                <!-- Left Column -->
                <div class="settings-column">
                    <!-- Email Sender Settings Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Email Sender Settings', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="email_from_name"><?php esc_html_e('From Name', 'NORDBOOKING'); ?></label>
                                <input type="text" id="email_from_name" name="email_from_name" class="regular-text" 
                                       value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'email_from_name'); ?>"
                                       placeholder="<?php esc_attr_e('Your Business Name', 'NORDBOOKING'); ?>" />
                                <p class="description"><?php esc_html_e('The name that appears in the "From" field of emails sent to customers.', 'NORDBOOKING'); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label for="email_from_address"><?php esc_html_e('From Email Address', 'NORDBOOKING'); ?></label>
                                <input type="email" id="email_from_address" name="email_from_address" class="regular-text" 
                                       value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'email_from_address'); ?>"
                                       placeholder="<?php esc_attr_e('noreply@yourdomain.com', 'NORDBOOKING'); ?>" />
                                <p class="description"><?php esc_html_e('The email address that appears in the "From" field. Use a domain you own.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="settings-column">
                    <!-- Email Delivery Status Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Email Delivery Status', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="email-status-indicator">
                                <div class="status-item">
                                    <div class="status-icon status-success">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20,6 9,17 4,12"/>
                                        </svg>
                                    </div>
                                    <div class="status-content">
                                        <strong><?php esc_html_e('Email System Active', 'NORDBOOKING'); ?></strong>
                                        <p><?php esc_html_e('Your email notifications are configured and ready to send.', 'NORDBOOKING'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="email-test-section">
                                <button type="button" id="test-email-btn" class="btn btn-outline btn-sm">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                    <?php esc_html_e('Send Test Email', 'NORDBOOKING'); ?>
                                </button>
                                <p class="description"><?php esc_html_e('Send a test email to verify your settings are working correctly.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Types Section -->
            <div class="nordbooking-card" style="margin-top: 1.5rem;">
                <div class="nordbooking-card-header">
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Notification Types', 'NORDBOOKING'); ?></h3>
                    <p class="nordbooking-card-description"><?php esc_html_e('Configure which email notifications to send and where to send them.', 'NORDBOOKING'); ?></p>
                </div>
                <div class="nordbooking-card-content">
                    <div class="email-notifications-grid">
                        <?php
                        $notification_types = [
                            'booking_confirmation_customer' => [
                                'label' => __('Customer Booking Confirmation', 'NORDBOOKING'),
                                'description' => __('Sent to customers when they make a booking', 'NORDBOOKING'),
                                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16,11 18,13 22,9"/></svg>'
                            ],
                            'booking_confirmation_admin' => [
                                'label' => __('Admin Booking Notification', 'NORDBOOKING'),
                                'description' => __('Sent to you when a new booking is made', 'NORDBOOKING'),
                                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m22 12-3-3 3-3"/></svg>'
                            ],
                            'staff_assignment' => [
                                'label' => __('Staff Assignment Notification', 'NORDBOOKING'),
                                'description' => __('Sent to staff when assigned to a booking', 'NORDBOOKING'),
                                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m22 12-3-3 3-3"/></svg>'
                            ],
                            'welcome' => [
                                'label' => __('Welcome Email', 'NORDBOOKING'),
                                'description' => __('Sent to new customers after their first booking', 'NORDBOOKING'),
                                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 2v4l-3 3h18l-3-3V2z"/><path d="M8 6v10a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"/></svg>'
                            ],
                            'invitation' => [
                                'label' => __('Invitation Email', 'NORDBOOKING'),
                                'description' => __('Sent when inviting staff or team members', 'NORDBOOKING'),
                                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'
                            ]
                        ];
                        
                        foreach ($notification_types as $type => $config): ?>
                            <div class="email-notification-card">
                                <div class="notification-header">
                                    <div class="notification-icon">
                                        <?php echo $config['icon']; ?>
                                    </div>
                                    <div class="notification-info">
                                        <h4><?php echo esc_html($config['label']); ?></h4>
                                        <p><?php echo esc_html($config['description']); ?></p>
                                    </div>
                                    <div class="notification-toggle">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="email_<?php echo esc_attr($type); ?>_enabled" 
                                                   id="email_<?php echo esc_attr($type); ?>_enabled" value="1" 
                                                   <?php checked(nordbooking_get_biz_setting_value($biz_settings, 'email_' . $type . '_enabled', '1'), '1'); ?> />
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="notification-settings">
                                    <div class="recipient-options">
                                        <label class="radio-option">
                                            <input type="radio" name="email_<?php echo esc_attr($type); ?>_use_primary" value="1" 
                                                   <?php checked(nordbooking_get_biz_setting_value($biz_settings, 'email_' . $type . '_use_primary', '1'), '1'); ?> />
                                            <span class="radio-label"><?php esc_html_e('Use primary business email', 'NORDBOOKING'); ?></span>
                                        </label>
                                        
                                        <label class="radio-option">
                                            <input type="radio" name="email_<?php echo esc_attr($type); ?>_use_primary" value="0" 
                                                   <?php checked(nordbooking_get_biz_setting_value($biz_settings, 'email_' . $type . '_use_primary', '1'), '0'); ?> />
                                            <span class="radio-label"><?php esc_html_e('Use custom email:', 'NORDBOOKING'); ?></span>
                                            <input type="email" name="email_<?php echo esc_attr($type); ?>_recipient" 
                                                   class="custom-email-field" 
                                                   value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'email_' . $type . '_recipient'); ?>"
                                                   placeholder="<?php esc_attr_e('custom@email.com', 'NORDBOOKING'); ?>" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
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