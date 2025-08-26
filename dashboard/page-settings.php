<?php
/**
 * Redesigned Dashboard Page: Settings
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \MoBooking\Classes\Settings();
$user_id = get_current_user_id();
$biz_settings = $settings_manager->get_business_settings($user_id);

// Helper functions
function mobooking_get_biz_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function mobooking_get_biz_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function mobooking_select_biz_setting_value($settings, $key, $value, $default_value = '') {
    $current_val = isset($settings[$key]) ? $settings[$key] : $default_value;
    return selected($value, $current_val, false);
}
?>
<div id="mobooking-business-settings-page" class="wrap mobooking-settings-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('settings'); ?>
            </span>
            <h1><?php esc_html_e('Settings', 'mobooking'); ?></h1>
        </div>
        <div class="mobooking-page-header-actions">
            <button type="submit" form="mobooking-business-settings-form" name="save_business_settings" id="mobooking-save-biz-settings-btn" class="btn btn-primary"><?php esc_html_e('Save All Settings', 'mobooking'); ?></button>
        </div>
    </div>
    <p class="page-description"><?php esc_html_e('Manage your core business information and email configurations.', 'mobooking'); ?></p>

    <form id="mobooking-business-settings-form" method="post">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
        <div id="mobooking-settings-feedback" style="margin-bottom:15px; margin-top:10px;"></div>

        <div class="settings-layout">
            <!-- Left Column -->
            <div class="settings-column">
                <!-- Business Details Card -->
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Business Details', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="biz_name"><?php esc_html_e('Business Name', 'mobooking'); ?></label>
                            <input name="biz_name" type="text" id="biz_name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('The public name of your business.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="biz_email"><?php esc_html_e('Public Business Email', 'mobooking'); ?></label>
                            <input name="biz_email" type="email" id="biz_email" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Email address for customer communication.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="biz_phone"><?php esc_html_e('Business Phone', 'mobooking'); ?></label>
                            <input name="biz_phone" type="tel" id="biz_phone" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>" class="regular-text">
                        </div>
                        <div class="form-group">
                            <label for="biz_address"><?php esc_html_e('Business Address', 'mobooking'); ?></label>
                            <textarea name="biz_address" id="biz_address" class="large-text" rows="4"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                            <p class="description"><?php esc_html_e('Your primary business location.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Localization Card -->
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Localization', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="biz_currency_code"><?php esc_html_e('Currency', 'mobooking'); ?></label>
                            <select name="biz_currency_code" id="biz_currency_code" class="regular-text">
                                <option value="USD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'USD', 'USD'); ?>>USD (US Dollar)</option>
                                <option value="EUR" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'EUR'); ?>>EUR (Euro)</option>
                                <option value="GBP" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'GBP'); ?>>GBP (British Pound)</option>
                                <option value="CAD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'CAD'); ?>>CAD (Canadian Dollar)</option>
                                <option value="AUD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'AUD'); ?>>AUD (Australian Dollar)</option>
                            </select>
                            <p class="description"><?php esc_html_e('Select your currency for pricing display.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="biz_user_language"><?php esc_html_e('Language', 'mobooking'); ?></label>
                            <select name="biz_user_language" id="biz_user_language" class="regular-text">
                                <option value="en_US" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'en_US', 'en_US'); ?>>English (US)</option>
                                <option value="sv_SE" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'sv_SE'); ?>>Swedish</option>
                                <option value="nb_NO" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'nb_NO'); ?>>Norwegian</option>
                                <option value="da_DK" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'da_DK'); ?>>Danish</option>
                            </select>
                             <p class="description"><?php esc_html_e("Select the language for the dashboard and booking form.", 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Email Preview Card -->
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Email Preview', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <p class="description"><?php esc_html_e('Send a test email to yourself to see how it looks. This will use your saved settings.', 'mobooking'); ?></p>
                            <button type="button" class="btn btn-secondary" id="mobooking-send-test-email-btn"><?php esc_html_e('Send Test Email', 'mobooking'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="settings-column">
                <!-- Logo Card -->
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Company Logo', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <div class="logo-uploader-wrapper">
                                <div class="logo-preview">
                                    <?php
                                    $logo_url = mobooking_get_biz_setting_value($biz_settings, 'biz_logo_url');
                                    if (!empty($logo_url)) : ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Company Logo', 'mobooking'); ?>">
                                    <?php else : ?>
                                        <div class="logo-placeholder">
                                            <span><?php esc_html_e('No Logo', 'mobooking'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="logo-uploader-actions">
                                    <input type="file" id="mobooking-logo-file-input" accept="image/png, image/jpeg, image/gif" style="display: none;">
                                    <button type="button" class="btn btn-secondary" id="mobooking-upload-logo-btn"><?php esc_html_e('Upload Logo', 'mobooking'); ?></button>
                                    <button type="button" class="btn btn-link" id="mobooking-remove-logo-btn" style="<?php echo empty($logo_url) ? 'display:none;' : ''; ?>"><?php esc_html_e('Remove', 'mobooking'); ?></button>
                                </div>
                            </div>
                            <input name="biz_logo_url" type="hidden" id="biz_logo_url" value="<?php echo esc_url($logo_url); ?>">
                            <div class="progress-bar-wrapper" style="display:none;">
                                <div class="progress-bar"></div>
                            </div>
                            <p class="description"><?php esc_html_e('Upload a logo for your emails and booking form.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Email Settings Card -->
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <h3 class="mobooking-card-title"><?php esc_html_e('Email Settings', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="form-group">
                            <label for="email_from_name"><?php esc_html_e('Email "From" Name', 'mobooking'); ?></label>
                            <input name="email_from_name" type="text" id="email_from_name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_from_name'); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Name displayed as the sender in emails.', 'mobooking'); ?></p>
                        </div>
                        <div class="form-group">
                            <label for="email_from_address"><?php esc_html_e('Email "From" Address', 'mobooking'); ?></label>
                            <input name="email_from_address" type="email" id="email_from_address" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_from_address'); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Address used for sending emails.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden submit button for form context, main button is in header -->
        <button type="submit" style="display:none;"></button>
    </form>
</div>
