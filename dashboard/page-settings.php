<?php
/**
 * Dashboard Page: Settings (Business & Email)
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \MoBooking\Classes\Settings();
$user_id = get_current_user_id();
$biz_settings = $settings_manager->get_business_settings($user_id);

// Helper functions (can be moved to a common file if used across multiple pages)
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
<div id="mobooking-business-settings-page" class="wrap">
    <h1><?php esc_html_e('Business Settings', 'mobooking'); ?></h1>
    <p><?php esc_html_e('Manage your core business information, email configurations, and operating hours.', 'mobooking'); ?></p>

    <form id="mobooking-business-settings-form" method="post">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
        <div id="mobooking-settings-feedback" style="margin-bottom:15px; margin-top:10px;"></div>

        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="#mobooking-bizinfo-tab" class="nav-tab nav-tab-active" data-tab="bizinfo"><?php esc_html_e('Business Information', 'mobooking'); ?></a>
            <a href="#mobooking-emailconf-tab" class="nav-tab" data-tab="emailconf"><?php esc_html_e('Email Configuration', 'mobooking'); ?></a>
            <a href="#mobooking-bizhours-tab" class="nav-tab" data-tab="bizhours"><?php esc_html_e('Business Hours', 'mobooking'); ?></a>
        </h2>

        <div id="mobooking-bizinfo-tab" class="mobooking-settings-tab-content">
            <h3><?php esc_html_e('Business Information', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="biz_name"><?php esc_html_e('Business Name', 'mobooking'); ?></label></th>
                    <td><input name="biz_name" type="text" id="biz_name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('The public name of your business.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="biz_email"><?php esc_html_e('Public Business Email', 'mobooking'); ?></label></th>
                    <td><input name="biz_email" type="email" id="biz_email" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address for customer communication. Defaults to your registration email if not set.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="biz_phone"><?php esc_html_e('Business Phone', 'mobooking'); ?></label></th>
                    <td><input name="biz_phone" type="tel" id="biz_phone" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="biz_address"><?php esc_html_e('Business Address', 'mobooking'); ?></label></th>
                    <td><textarea name="biz_address" id="biz_address" class="large-text" rows="4"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                         <p class="description"><?php esc_html_e('Your primary business location or service area main address.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="biz_logo_url"><?php esc_html_e('Business Logo URL', 'mobooking'); ?></label></th>
                    <td><input name="biz_logo_url" type="url" id="biz_logo_url" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_logo_url'); ?>" class="large-text" placeholder="https://example.com/logo.png">
                        <p class="description"><?php esc_html_e('Link to your business logo. Will be used in emails and potentially on the booking form.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="biz_currency_symbol"><?php esc_html_e('Currency Symbol', 'mobooking'); ?></label></th>
                    <td><input name="biz_currency_symbol" type="text" id="biz_currency_symbol" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_currency_symbol', '$'); ?>" class="small-text" maxlength="5">
                        <p class="description"><?php esc_html_e('E.g., $, €, £, INR.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="biz_currency_position"><?php esc_html_e('Currency Position', 'mobooking'); ?></label></th>
                    <td>
                        <select name="biz_currency_position" id="biz_currency_position">
                            <option value="before" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_position', 'before', 'before'); ?>><?php esc_html_e('Before amount (e.g., $100)', 'mobooking'); ?></option>
                            <option value="after" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_position', 'after'); ?>><?php esc_html_e('After amount (e.g., 100$)', 'mobooking'); ?></option>
                        </select>
                         <p class="description"><?php esc_html_e('Position of the currency symbol relative to the amount.', 'mobooking'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="mobooking-emailconf-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Email Configuration', 'mobooking'); ?></h3>
            <p><?php esc_html_e('Customize sender details and content for emails sent to customers and yourself.', 'mobooking'); ?></p>

            <h4><?php esc_html_e('Sender Settings', 'mobooking'); ?></h4>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="email_from_name"><?php esc_html_e('Email "From" Name', 'mobooking'); ?></label></th>
                    <td><input name="email_from_name" type="text" id="email_from_name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_from_name'); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Name displayed as the sender for emails. Defaults to Business Name or Site Title.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="email_from_address"><?php esc_html_e('Email "From" Address', 'mobooking'); ?></label></th>
                    <td><input name="email_from_address" type="email" id="email_from_address" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_from_address'); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address used as the sender. Defaults to Public Business Email or Site Admin Email. Ensure this email is configured for sending to improve deliverability.', 'mobooking'); ?></p></td>
                </tr>
            </table>

            <h4><?php esc_html_e('Customer Booking Confirmation Email', 'mobooking'); ?></h4>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="email_booking_conf_subj_customer"><?php esc_html_e('Subject', 'mobooking'); ?></label></th>
                    <td><input name="email_booking_conf_subj_customer" type="text" id="email_booking_conf_subj_customer" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_booking_conf_subj_customer'); ?>" class="large-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="email_booking_conf_body_customer"><?php esc_html_e('Body', 'mobooking'); ?></label></th>
                    <td><textarea name="email_booking_conf_body_customer" id="email_booking_conf_body_customer" class="large-text code" rows="10" style="font-family: monospace;"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'email_booking_conf_body_customer'); ?></textarea>
                        <p class="description"><?php esc_html_e('Available placeholders: {{customer_name}}, {{business_name}}, {{booking_reference}}, {{service_names}}, {{booking_date_time}}, {{total_price}}, {{service_address}}, {{special_instructions}}.', 'mobooking'); ?></p>
                    </td>
                </tr>
            </table>

            <h4><?php esc_html_e('Admin New Booking Notification Email', 'mobooking'); ?></h4>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="email_booking_conf_subj_admin"><?php esc_html_e('Subject', 'mobooking'); ?></label></th>
                    <td><input name="email_booking_conf_subj_admin" type="text" id="email_booking_conf_subj_admin" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_booking_conf_subj_admin'); ?>" class="large-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="email_booking_conf_body_admin"><?php esc_html_e('Body', 'mobooking'); ?></label></th>
                    <td><textarea name="email_booking_conf_body_admin" id="email_booking_conf_body_admin" class="large-text code" rows="10" style="font-family: monospace;"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'email_booking_conf_body_admin'); ?></textarea>
                        <p class="description"><?php esc_html_e('Available placeholders: {{customer_name}}, {{customer_email}}, {{customer_phone}}, {{business_name}}, {{booking_reference}}, {{service_names}}, {{booking_date_time}}, {{total_price}}, {{service_address}}, {{special_instructions}}, {{admin_booking_link}}.', 'mobooking'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="mobooking-bizhours-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Business Hours', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="biz_hours_json"><?php esc_html_e('Operating Hours (JSON format)', 'mobooking'); ?></label></th>
                    <td>
                        <textarea name="biz_hours_json" id="biz_hours_json" class="large-text code" rows="12" style="font-family: monospace;"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'biz_hours_json', '{}'); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Define your weekly business hours using JSON. Use 24-hour format for times (HH:MM).', 'mobooking'); ?><br>
                            <?php esc_html_e('Example structure:', 'mobooking'); ?><br>
                            <code>{<br>
                            &nbsp;&nbsp;"monday": {"open": "09:00", "close": "17:00", "is_closed": false},<br>
                            &nbsp;&nbsp;"tuesday": {"open": "09:00", "close": "17:00", "is_closed": false},<br>
                            &nbsp;&nbsp;"wednesday": {"open": "09:00", "close": "17:00", "is_closed": false},<br>
                            &nbsp;&nbsp;"thursday": {"open": "09:00", "close": "17:00", "is_closed": false},<br>
                            &nbsp;&nbsp;"friday": {"open": "09:00", "close": "17:00", "is_closed": false},<br>
                            &nbsp;&nbsp;"saturday": {"is_closed": true},<br>
                            &nbsp;&nbsp;"sunday": {"is_closed": true}<br>
                            }</code>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit" style="margin-top:20px;">
            <button type="submit" name="save_business_settings" id="mobooking-save-biz-settings-btn" class="button button-primary"><?php esc_html_e('Save Business Settings', 'mobooking'); ?></button>
        </p>
    </form>
</div>
