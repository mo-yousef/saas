<?php
/**
 * Redesigned Dashboard Page: Settings
 * @package NORDBOOKING
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \NORDBOOKING\Classes\Settings();
$user_id = get_current_user_id();
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
<div id="NORDBOOKING-business-settings-page" class="wrap NORDBOOKING-settings-page">
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('settings'); ?>
            </span>
            <h1><?php esc_html_e('Settings', 'NORDBOOKING'); ?></h1>
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
            <p class="page-description"><?php esc_html_e('Manage your core business information and localization.', 'NORDBOOKING'); ?></p>
            <div class="settings-layout">
                <!-- Left Column -->
                <div class="settings-column">
                    <!-- Business Details Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Business Details', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="biz_name"><?php esc_html_e('Business Name', 'NORDBOOKING'); ?></label>
                                <input name="biz_name" type="text" id="biz_name" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('The public name of your business.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_email"><?php esc_html_e('Public Business Email', 'NORDBOOKING'); ?></label>
                                <input name="biz_email" type="email" id="biz_email" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e('Email address for customer communication.', 'NORDBOOKING'); ?></p>
                            </div>
                            <div class="form-group">
                                <label for="biz_phone"><?php esc_html_e('Business Phone', 'NORDBOOKING'); ?></label>
                                <input name="biz_phone" type="tel" id="biz_phone" value="<?php echo nordbooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="biz_address"><?php esc_html_e('Business Address', 'NORDBOOKING'); ?></label>
                                <textarea name="biz_address" id="biz_address" class="large-text" rows="4"><?php echo nordbooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                                <p class="description"><?php esc_html_e('Your primary business location.', 'NORDBOOKING'); ?></p>
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

        <div id="branding-tab" class="settings-tab-content" style="display:none;">
            <p class="page-description"><?php esc_html_e('Customize the look and feel of your booking form and emails.', 'NORDBOOKING'); ?></p>
            <div class="settings-layout">
                <div class="settings-column">
                    <!-- Logo Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Company Logo', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <div class="logo-uploader-wrapper">
                                    <div class="logo-preview">
                                        <?php
                                        $logo_url = nordbooking_get_biz_setting_value($biz_settings, 'biz_logo_url');
                                        if (!empty($logo_url)) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Company Logo', 'NORDBOOKING'); ?>">
                                        <?php else : ?>
                                            <div class="logo-placeholder">
                                                <span><?php esc_html_e('No Logo', 'NORDBOOKING'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="logo-uploader-actions">
                                        <input type="file" id="NORDBOOKING-logo-file-input" accept="image/png, image/jpeg, image/gif" style="display: none;">
                                        <button type="button" class="btn btn-secondary" id="NORDBOOKING-upload-logo-btn"><?php esc_html_e('Upload Logo', 'NORDBOOKING'); ?></button>
                                        <button type="button" class="btn btn-link" id="NORDBOOKING-remove-logo-btn" style="<?php echo empty($logo_url) ? 'display:none;' : ''; ?>"><?php esc_html_e('Remove', 'NORDBOOKING'); ?></button>
                                    </div>
                                </div>
                                <input name="biz_logo_url" type="hidden" id="biz_logo_url" value="<?php echo esc_url($logo_url); ?>">
                                <div class="progress-bar-wrapper" style="display:none;">
                                    <div class="progress-bar"></div>
                                </div>
                                <p class="description"><?php esc_html_e('Upload a logo for your emails and booking form.', 'NORDBOOKING'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="settings-column">
                    <!-- Theme Colors Card -->
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Theme Colors', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="bf_theme_color"><?php esc_html_e('Primary Color', 'NORDBOOKING'); ?></label>
                                <input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="form-input NORDBOOKING-color-picker">
                            </div>
                            <div class="form-group">
                                <label for="bf_secondary_color"><?php esc_html_e('Secondary Color', 'NORDBOOKING'); ?></label>
                                <input name="bf_secondary_color" type="text" id="bf_secondary_color" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_secondary_color', '#34495e'); ?>" class="form-input NORDBOOKING-color-picker">
                            </div>
                            <div class="form-group">
                                <label for="bf_background_color"><?php esc_html_e('Background Color', 'NORDBOOKING'); ?></label>
                                <input name="bf_background_color" type="text" id="bf_background_color" value="<?php echo nordbooking_get_biz_setting_value($bf_settings, 'bf_background_color', '#ffffff'); ?>" class="form-input NORDBOOKING-color-picker">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="email-notifications-tab" class="settings-tab-content" style="display:none;">
            <div class="email-settings-layout">
                <div class="email-editor-column">
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Edit Email Template', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <div class="form-group">
                                <label for="email-template-selector"><?php esc_html_e('Select Email to Edit', 'NORDBOOKING'); ?></label>
                                <select id="email-template-selector" class="regular-text">
                                    <?php
                                    $email_templates = $settings_manager->get_email_templates();
                                    foreach ($email_templates as $key => $template) {
                                        echo '<option value="' . esc_attr($key) . '">' . esc_html($template['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div id="email-editor-container">
                                <div class="form-group">
                                    <label for="email-editor-subject"><?php esc_html_e('Subject', 'NORDBOOKING'); ?></label>
                                    <input type="text" id="email-editor-subject" class="regular-text" placeholder="<?php esc_attr_e('Email subject', 'NORDBOOKING'); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php esc_html_e('Body Components', 'NORDBOOKING'); ?></label>
                                    <div id="email-editor-body" class="email-editor-fields-wrapper">
                                        <!-- JS will render sortable components here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Available Variables', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <p class="description"><?php esc_html_e('Click any variable to copy it to your clipboard.', 'NORDBOOKING'); ?></p>
                            <ul id="email-variables-list">
                                <!-- Variables will be loaded by JS -->
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="email-preview-column">
                    <div class="nordbooking-card">
                        <div class="nordbooking-card-header">
                            <h3 class="nordbooking-card-title"><?php esc_html_e('Live Preview', 'NORDBOOKING'); ?></h3>
                        </div>
                        <div class="nordbooking-card-content">
                            <iframe id="email-preview-iframe" src="about:blank"></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden data store for form submission -->
            <div class="NORDBOOKING-hidden-email-data" style="display: none;">
            <?php
            foreach ($email_templates as $key => $template) {
                $subject_key = $template['subject_key'];
                $body_key = $template['body_key'];
                $subject = nordbooking_get_biz_setting_value($biz_settings, $subject_key);
                // Get the raw JSON string. DO NOT use a helper that escapes it here.
                $body_json = isset($biz_settings[$body_key]) ? $biz_settings[$body_key] : '[]';
            ?>
                <input type="hidden"
                       id="hidden-subject-<?php echo esc_attr($key); ?>"
                       name="<?php echo esc_attr($subject_key); ?>"
                       value="<?php echo esc_attr($subject); ?>">

                <textarea class="hidden-body-json"
                          id="hidden-body-<?php echo esc_attr($key); ?>"
                          name="<?php echo esc_attr($body_key); ?>"><?php echo esc_textarea($body_json); ?></textarea>
            <?php
            }
            ?>
            </div>
        </div>

        <!-- This button is outside the tabs for global save -->
        <p class="submit" style="margin-top:20px;">
             <button type="submit" form="NORDBOOKING-business-settings-form" name="save_business_settings" id="NORDBOOKING-save-biz-settings-btn-footer" class="btn btn-primary"><?php esc_html_e('Save All Settings', 'NORDBOOKING'); ?></button>
        </p>
    </form>
</div>
