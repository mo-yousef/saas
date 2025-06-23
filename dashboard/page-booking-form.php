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

?>
<div id="mobooking-booking-form-settings-page" class="wrap">
    <h1><?php esc_html_e('Booking Form Settings', 'mobooking'); ?></h1>
    <p><?php esc_html_e('Customize the appearance and behavior of your public booking form.', 'mobooking'); ?></p>

    <form id="mobooking-booking-form-settings-form" method="post">
        <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
        <div id="mobooking-settings-feedback" style="margin-bottom:15px; margin-top:10px;"></div>

        <!-- Share and Embed Section -->
        <div id="mobooking-share-embed-section" style="margin-bottom: 30px; padding: 15px; border: 1px solid #ccd0d4; background-color: #fff;">
            <h2><?php esc_html_e('Share Your Booking Form', 'mobooking'); ?></h2>
            <?php
            $current_user_id = get_current_user_id();
            $business_slug = get_user_meta($current_user_id, 'mobooking_business_slug', true);
            $public_link = '';
            $link_message = '';

            if (!empty($business_slug)) {
                $public_link = trailingslashit(site_url()) . esc_attr($business_slug) . '/booking/';
            } else {
                // Option 1: Link to profile to set slug
                $profile_url = admin_url('profile.php');
                $link_message = sprintf(
                    wp_kses(
                        __('Please <a href="%s">set your Business Slug</a> in your profile to generate a user-friendly public link.', 'mobooking'),
                        ['a' => ['href' => []]]
                    ),
                    esc_url($profile_url)
                );
                // Option 2: Fallback to ?tid= (requires a generic /booking/ page to be set up by admin)
                // $generic_booking_page_url = site_url('/booking/'); // Assuming a page exists at /booking/
                // $public_link = add_query_arg('tid', $current_user_id, $generic_booking_page_url);
                // $link_message = __('Your generic booking link (requires a page at /booking/ with the public form template assigned):', 'mobooking');
            }
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="mobooking-public-link"><?php esc_html_e('Your Public Link', 'mobooking'); ?></label></th>
                    <td>
                        <?php if ($public_link): ?>
                            <input type="text" id="mobooking-public-link" value="<?php echo esc_url($public_link); ?>" readonly class="regular-text" style="width: 70%;">
                            <button type="button" id="mobooking-copy-public-link-btn" class="button" style="margin-left: 10px;"><?php esc_html_e('Copy Link', 'mobooking'); ?></button>
                            <span id="mobooking-copy-link-feedback" style="margin-left:10px; color: green;"></span>
                        <?php else: ?>
                            <p><?php echo $link_message; ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="mobooking-embed-code"><?php esc_html_e('Embed Code (iframe)', 'mobooking'); ?></label></th>
                    <td>
                        <?php if ($public_link): ?>
                            <textarea id="mobooking-embed-code" readonly class="large-text" rows="4" style="width: 70%;"><?php
                                echo esc_textarea(sprintf('<iframe src="%s" title="%s" style="width:100%%; height:800px; border:1px solid #ccc;"></iframe>', esc_url($public_link), esc_attr__('Booking Form', 'mobooking')));
                            ?></textarea>
                            <button type="button" id="mobooking-copy-embed-code-btn" class="button" style="margin-left: 10px; vertical-align: top;"><?php esc_html_e('Copy Code', 'mobooking'); ?></button>
                            <span id="mobooking-copy-embed-feedback" style="margin-left:10px; color: green; vertical-align: top; display: inline-block;"></span>
                        <?php else: ?>
                            <p><?php esc_html_e('Set your Business Slug to generate the embed code.', 'mobooking'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <!-- End Share and Embed Section -->


        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="#mobooking-general-settings-tab" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General Settings', 'mobooking'); ?></a>
            <a href="#mobooking-design-settings-tab" class="nav-tab" data-tab="design"><?php esc_html_e('Design Settings', 'mobooking'); ?></a>
            <a href="#mobooking-advanced-settings-tab" class="nav-tab" data-tab="advanced"><?php esc_html_e('Advanced', 'mobooking'); ?></a>
        </h2>

        <div id="mobooking-general-settings-tab" class="mobooking-settings-tab-content">
            <h3><?php esc_html_e('General Settings', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_header_text"><?php esc_html_e('Form Header Text', 'mobooking'); ?></label></th>
                    <td><input name="bf_header_text" type="text" id="bf_header_text" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_header_text', 'Book Our Services Online'); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('The main title displayed at the top of your public booking form.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_show_progress_bar"><?php esc_html_e('Show Progress Bar', 'mobooking'); ?></label></th>
                    <td><input name="bf_show_progress_bar" type="checkbox" id="bf_show_progress_bar" value="1" <?php echo mobooking_is_setting_checked($bf_settings, 'bf_show_progress_bar', true); ?>>
                        <p class="description"><?php esc_html_e('Display a step-by-step progress indicator on the form.', 'mobooking'); ?></p></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><label for="bf_terms_conditions_url"><?php esc_html_e('Terms & Conditions URL', 'mobooking'); ?></label></th>
                    <td><input name="bf_terms_conditions_url" type="url" id="bf_terms_conditions_url" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_terms_conditions_url'); ?>" class="regular-text" placeholder="https://example.com/terms">
                         <p class="description"><?php esc_html_e('Link to your terms and conditions page. If provided, a checkbox agreeing to terms may be shown.', 'mobooking'); ?></p></td>
                </tr>
            </table>

            <h4><?php esc_html_e('Step Titles & Messages', 'mobooking'); ?></h4>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_step_1_title"><?php esc_html_e('Step 1 Title (Location/Date)', 'mobooking'); ?></label></th>
                    <td><input name="bf_step_1_title" type="text" id="bf_step_1_title" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_step_1_title', 'Step 1: Location & Date'); ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_step_2_title"><?php esc_html_e('Step 2 Title (Services)', 'mobooking'); ?></label></th>
                    <td><input name="bf_step_2_title" type="text" id="bf_step_2_title" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_step_2_title', 'Step 2: Choose Services'); ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_step_3_title"><?php esc_html_e('Step 3 Title (Options)', 'mobooking'); ?></label></th>
                    <td><input name="bf_step_3_title" type="text" id="bf_step_3_title" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_step_3_title', 'Step 3: Service Options'); ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_step_4_title"><?php esc_html_e('Step 4 Title (Your Details)', 'mobooking'); ?></label></th>
                    <td><input name="bf_step_4_title" type="text" id="bf_step_4_title" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_step_4_title', 'Step 4: Your Details'); ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_step_5_title"><?php esc_html_e('Step 5 Title (Review)', 'mobooking'); ?></label></th>
                    <td><input name="bf_step_5_title" type="text" id="bf_step_5_title" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_step_5_title', 'Step 5: Review & Confirm'); ?>" class="regular-text"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_thank_you_message"><?php esc_html_e('Thank You Message', 'mobooking'); ?></label></th>
                    <td><textarea name="bf_thank_you_message" id="bf_thank_you_message" class="large-text" rows="4"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_thank_you_message', 'Thank you for your booking! A confirmation email has been sent to you.'); ?></textarea>
                        <p class="description"><?php esc_html_e('Message displayed to customer after successful booking (on Step 6).', 'mobooking'); ?></p></td>
                </tr>
            </table>
        </div>

        <div id="mobooking-design-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Design & Appearance', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_theme_color"><?php esc_html_e('Primary Theme Color', 'mobooking'); ?></label></th>
                    <td><input name="bf_theme_color" type="text" id="bf_theme_color" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_theme_color', '#1abc9c'); ?>" class="mobooking-color-picker" data-default-color="#1abc9c">
                        <p class="description"><?php esc_html_e('Main color for buttons and progress bar accents.', 'mobooking'); ?></p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bf_custom_css"><?php esc_html_e('Custom CSS', 'mobooking'); ?></label></th>
                    <td><textarea name="bf_custom_css" id="bf_custom_css" class="large-text" rows="8" placeholder="<?php esc_attr_e('/* Your custom CSS rules here */', 'mobooking'); ?>"><?php echo mobooking_get_setting_textarea($bf_settings, 'bf_custom_css'); ?></textarea>
                        <p class="description"><?php esc_html_e('Apply custom styles to the public booking form. Use with caution.', 'mobooking'); ?></p></td>
                </tr>
            </table>
        </div>

        <div id="mobooking-advanced-settings-tab" class="mobooking-settings-tab-content" style="display:none;">
            <h3><?php esc_html_e('Advanced Settings', 'mobooking'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bf_allow_cancellation_hours"><?php esc_html_e('Cancellation Lead Time (Hours)', 'mobooking'); ?></label></th>
                    <td><input name="bf_allow_cancellation_hours" type="number" id="bf_allow_cancellation_hours" value="<?php echo mobooking_get_setting_value($bf_settings, 'bf_allow_cancellation_hours', '24'); ?>" min="0" class="small-text">
                        <p class="description"><?php esc_html_e('Minimum hours before booking time a customer can cancel. Enter 0 if cancellation via form is not allowed or handled differently.', 'mobooking'); ?></p></td>
                </tr>
                <?php // Future: Option to require login for booking, payment gateway settings if not global, etc. ?>
            </table>
        </div>

        <p class="submit" style="margin-top:20px;">
            <button type="submit" name="save_booking_form_settings" id="mobooking-save-bf-settings-btn" class="button button-primary"><?php esc_html_e('Save Booking Form Settings', 'mobooking'); ?></button>
        </p>
    </form>
</div>
